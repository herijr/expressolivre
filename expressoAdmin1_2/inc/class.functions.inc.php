<?php
	/**********************************************************************************\
	* Expresso Administração                 									      *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br) *
	* --------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it		  *
	*  under the terms of the GNU General Public License as published by the		  *
	*  Free Software Foundation; either version 2 of the License, or (at your		  *
	*  option) any later version.													  *
	\**********************************************************************************/

	include_once('class.db_functions.inc.php');
	include_once(PHPGW_API_INC.'/class.config.inc.php');

	class functions
	{
		var $public_functions = array
		(
			'check_acl'			=> True,
			'read_acl'			=> True,
			'exist_account_lid'	=> True,
			'exist_email'		=> True,
			'array_invert'		=> True
		);

		var $nextmatchs;
		var $sectors_list = array();
		var $current_config;
		var $sector_search_ldap;

		function functions()
		{
			$this->db_functions = new db_functions;
			$GLOBALS['phpgw']->db = $this->db_functions->db;

			//$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c = new config;
			$c->read_repository();
			$this->current_config = $c->config_data;
		}
		
		function denied( $dn )
		{
			if ( is_null($this->sector_search_ldap) ) $this->sector_search_ldap = CreateObject('phpgwapi.sector_search_ldap');
			return $this->sector_search_ldap->denied( $dn );
		}

		function net_match($network, $ip)
		{
			//determines if a network in the form of 192.168.17.1/16 or 127.0.0.1/255.255.255.255 or 10.0.0.1 matches a given ip
     		$ip_arr = explode('/', $network);
     		$network_long = ip2long($ip_arr[0]);

     		$x = ip2long($ip_arr[1]);
     		$mask =  long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
     		$ip_long = ip2long($ip);

     		return ($ip_long & $mask) == ($network_long & $mask);
		}

		function check_allow_address()
		{
			$networks = explode(",", $this->current_config['expressoAdmin_allowed_networks']);

			foreach ($networks as $idx=>$network)
			{
				if ($this->net_match($network, $_SERVER['REMOTE_ADDR']))
				{
					return true;
				}
			}

			return false;
		}

		// Account and type of access. Return: Have access ? (true/false)
		function check_acl($account_lid, $access)
		{
			include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

			// Allow only internal addresses.
			if (isset($this->current_config['expressoAdmin_allowed_networks']) &&
				$this->current_config['expressoAdmin_allowed_networks'] &&
				(!$this->check_allow_address())
			) {
				return false;
			}

			$acl = $this->read_acl($account_lid);
			$params = func_get_args();
			$params[0] = $acl['acl'];
			return call_user_func_array( array( 'ACL_Managers', 'isAllow' ), $params );
		}

		// Read acl from db
		function read_acl( $account_lid )
		{
			$acl = $this->db_functions->read_acl( $account_lid );
			
			$result['acl']         = isset( $acl[0]['acl'] )? $acl[0]['acl'] : 0;
			$result['manager_lid'] = isset( $acl[0]['manager_lid'] )? $acl[0]['manager_lid'] : false;
			$result['raw_context'] = isset( $acl[0]['context'] )? $acl[0]['context'] : '';
			$result['contexts']    = array();
			
			foreach ( explode( '%', $result['raw_context'] ) as $context ) {
				if ( !empty( $context ) ) {
					$result['contexts'][]         = $context;
					$result['contexts_display'][] = str_replace( ', ', '.', ldap_dn2ufn( $context ) );
				}
			}
			return $result;
		}

		function get_list($type, $query, $contexts)
		{
			$dn			= $GLOBALS['phpgw_info']['server']['ldap_root_dn'];
			$passwd		= $GLOBALS['phpgw_info']['server']['ldap_root_pw'];
			$ldap_conn	= ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
			ldap_bind($ldap_conn,$dn,$passwd);

			if ($type == 'uid')
			{
				$justthese = array("uidnumber", "uid", "cn", "mail");
				$filter="(&(phpgwAccountType=u)(uid=".$query."))";

				$return = false;

				foreach ($contexts as $index=>$context)
				{
					$search=ldap_search($ldap_conn, $context, $filter, $justthese);
					$info = ldap_get_entries($ldap_conn, $search);

					if ($info['count'])
					{
						if ( $this->denied( $info[0]['dn'] ) )
							continue;

						$user_uid = $info[0]['uid'][0];

						$return['account_id']	= $info[0]['uidnumber'][0];
						$return['account_lid']	= $info[0]['uid'][0];
						$return['account_cn']	= $info[0]['cn'][0];
						$return['account_mail']	= $info[0]['mail'][0];

						break;
					}
				}
				ldap_close($ldap_conn);

				return $return;
			}
			elseif( $type == 'api')
			{
				$queryLDAP = ( ( unserialize($query) ) ? unserialize($query) : $query );
				$justthese	= array("uidnumber", "uid", "cn", "mail", "dn", "cpf", "rg", "rgUF");
				$search		= ldap_search( $ldap_conn, $contexts[0], "(&(phpgwAccountType=u)(".$queryLDAP[0]."=".$queryLDAP[1]."))", $justthese );
				$info 		= ldap_get_entries( $ldap_conn, $search );
				$return 	= false;

				if( $info['count'] > 0  )
				{
					$return[0]['accountId']		= $info[0]['uidnumber'][0];
					$return[0]['accountLid']	= $info[0]['uid'][0];
					$return[0]['accountDn']		= $info[0]['dn'];
					$return[0]['accountCn']		= $info[0]['cn'][0];
					$return[0]['accountMail']	= $info[0]['mail'][0];
					$return[0]['accountCpf']	= $info[0]['cpf'][0];
					$return[0]['accountRG']		= $info[0]['rg'][0];
					$return[0]['accountRgUF']	= $info[0]['rguf'][0];
				}		

				ldap_close($ldap_conn);

				return $return;
			}
			elseif ($type == 'accounts')
			{
				$justthese = array("uidnumber", "uid", "cn", "mail");
				$filter="(&(phpgwAccountType=u)(|(uid=*".$query."*)(sn=*".$query."*)(cn=*".$query."*)(givenName=*".$query."*)(mail=$query*)(mailAlternateAddress=$query*)))";

				$tmp = array();
				foreach ($contexts as $index=>$context)
				{
					$search=ldap_search($ldap_conn, $context, $filter, $justthese);
					$info = ldap_get_entries($ldap_conn, $search);

					for ($i=0; $i < $info['count']; $i++)
					{
						if ( $this->denied( $info[$i]['dn'] ) )
							continue;

						$tmp[$info[$i]['uid'][0]]['account_id']	 = $info[$i]['uidnumber'][0];
						$tmp[$info[$i]['uid'][0]]['account_lid'] = $info[$i]['uid'][0];
						$tmp[$info[$i]['uid'][0]]['account_cn']	 = $info[$i]['cn'][0];
						$tmp[$info[$i]['uid'][0]]['account_mail']= $info[$i]['mail'][0];
						$sort[] = $info[$i]['uid'][0];
					}
				}
				ldap_close($ldap_conn);

				if (count($sort))
				{
					natcasesort($sort);
					foreach ($sort as $user_uid)
						$return[$user_uid] = $tmp[$user_uid];
				}

				return $return;
			}
			elseif($type == 'groups')
			{
				$filter="(&(phpgwAccountType=g)(|(cn=*$query*)(mail=$query*)))";
				$filter="(&(|(phpgwAccountType=g)(objectclass=groupOfNames)(objectclass=groupOfUniqueNames))(|(cn=*$query*)(mail=$query*)))";
				$justthese = array("gidnumber", "cn", "description", "mail", "objectclass");

				$tmp = array();
				foreach ($contexts as $index=>$context)
				{
					$search=ldap_search($ldap_conn, $context, $filter, $justthese);
					$info = ldap_get_entries($ldap_conn, $search);
					for ($i=0; $i < $info['count']; $i++)
					{
						if ( $this->denied( $info[$i]['dn'] ) )
							continue;

						$cn = $sort[] = $info[$i]['cn'][0];
						$tmp[$cn]['cn']          = $cn;
						$tmp[$cn]['dn']          = $info[$i]['dn'];
						$tmp[$cn]['mail']        = $info[$i]['mail'][0];
						$tmp[$cn]['gidnumber']   = $info[$i]['gidnumber'][0];
						$tmp[$cn]['description'] = utf8_decode( $info[$i]['description'][0] );
						$tmp[$cn]['type']        = in_array( 'posixGroup', $info[$i]['objectclass'] )? 0 : ( in_array( 'groupOfNames', $info[$i]['objectclass'] )? 1 : 2 );
					}
				}
				ldap_close($ldap_conn);
				if ( count($sort) )
				{
					natcasesort($sort);
					foreach ($sort as $group_cn)
						$return[$group_cn] = $tmp[$group_cn];
				}
				return $return;
			}
			elseif($type == 'maillists')
			{
				$filter="(&(phpgwAccountType=l)(|(cn=*".$query."*)(uid=*".$query."*)(mail=$query*)))";
				$justthese = array("uidnumber", "cn", "uid", "mail");

				$tmp = array();
				foreach ($contexts as $index=>$context)
				{
					$search=ldap_search($ldap_conn, $context, $filter, $justthese);
					$info = ldap_get_entries($ldap_conn, $search);

					for ($i=0; $i < $info['count']; $i++)
					{
						if ( $this->denied( $info[$i]['dn'] ) )
							continue;

						$tmp[$info[$i]['uid'][0]]['uid']		= $info[$i]['uid'][0];
						$tmp[$info[$i]['uid'][0]]['name']		= $info[$i]['cn'][0];
						$tmp[$info[$i]['uid'][0]]['uidnumber']	= $info[$i]['uidnumber'][0];
						$tmp[$info[$i]['uid'][0]]['email']		= $info[$i]['mail'][0];
						$sort[] = $info[$i]['uid'][0];
					}
				}
				ldap_close($ldap_conn);

				natcasesort($sort);
				foreach ($sort as $maillist_uid)
					$return[$maillist_uid] = $tmp[$maillist_uid];

				return $return;
			}
			elseif($type == 'computers')
			{
				$filter="(&(objectClass=sambaSAMAccount)(|(sambaAcctFlags=[W          ])(sambaAcctFlags=[DW         ])(sambaAcctFlags=[I          ])(sambaAcctFlags=[S          ]))(cn=*".$query."*))";
				$justthese = array("cn","uidNumber","description");

				$tmp = array();
				foreach ($contexts as $index=>$context)
				{
					$search=ldap_search($ldap_conn, $context, $filter, $justthese);
					ldap_sort($ldap_conn, $search, 'cn');
					$info = ldap_get_entries($ldap_conn, $search);
					for ($i=0; $i < $info['count']; $i++)
					{
						if ( $this->denied( $info[$i]['dn'] ) )
							continue;

						$return[$info[$i]['uidnumber'][0]]['cn']          = $info[$i]['cn'][0];
                        $return[$info[$i]['uidnumber'][0]]['uidNumber']   = $info[$i]['uidnumber'][0];
                        $return[$info[$i]['uidnumber'][0]]['description'] = utf8_decode($info[$i]['description'][0]);

						/*
						$tmp[$info[$i]['cn'][0]]['cn']			= $info[$i]['cn'][0];
						$tmp[$info[$i]['cn'][0]]['uidNumber']	= $info[$i]['uidnumber'][0];
						$tmp[$info[$i]['cn'][0]]['description']	= utf8_decode($info[$i]['description'][0]);
						$sort[] = $info[$i]['cn'][0];
						*/
					}

				}
				ldap_close($ldap_conn);

				/*
				if (!empty($sort))
				{
					natcasesort($sort);
					foreach ($sort as $computer_cn)
						$return[$computer_cn] = $tmp[$computer_cn];
				}*/

				return $return;
			}
		}

		function get_organizations($context, $selected='', $referral=false, $show_invisible_ou=true, $master=false, $filter_config=false)
		{
			$s = CreateObject('phpgwapi.sector_search_ldap');
			$sectors_info = $s->get_organizations($context, $selected, $referral, $show_invisible_ou, $master, $filter_config);
			return $sectors_info;
		}

		function get_sectors($selected='', $referral=false, $show_invisible_ou=true)
		{
			$s = CreateObject('phpgwapi.sector_search_ldap');
			$sectors_info = $s->get_sectors($selected, $referral, $show_invisible_ou);
			return $sectors_info;
		}

		// Get list of all levels, this function is used for sectors module.
		function get_sectors_list($contexts)
		{
			$a_sectors = array();

			$dn			= $GLOBALS['phpgw_info']['server']['ldap_root_dn'];
			$passwd		= $GLOBALS['phpgw_info']['server']['ldap_root_pw'];
			$ldap_conn	= ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);

			ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
			ldap_bind($ldap_conn,$dn,$passwd);

			$justthese = array("dn");
			$filter = "(objectClass=organizationalUnit)";

			$systemName = strtolower($GLOBALS['phpgw_info']['server']['system_name']);
			if ($systemName != '')
				$filter = "(&$filter(phpgwSystem=$systemName))";

			foreach ($contexts as $context)
			{
				$search=ldap_search($ldap_conn, $context, $filter, $justthese);
        		$info = ldap_get_entries($ldap_conn, $search);
		        for ($i=0; $i<$info["count"]; $i++)
    		    {
    		    	$a_sectors[] = $info[$i]['dn'];
    		    }
			}
			ldap_close($ldap_conn);

			// Retiro o count do array info e inverto o array para ordenação.
	        foreach ($a_sectors as $context)
    	    {
				$array_dn = ldap_explode_dn ( $context, 1 );

                $array_dn_reverse  = array_reverse ( $array_dn, true );

				// Retirar o indice count do array.
				array_pop ( $array_dn_reverse );

				$inverted_dn[$context] = implode ( "#", $array_dn_reverse );
			}

			// Ordenação
			natcasesort($inverted_dn);

			// Construção do select
			$level = 0;
			$options = array();
			foreach ($inverted_dn as $dn=>$invert_ufn)
			{
                $display = '';

                $array_dn_reverse = explode ( "#", $invert_ufn );
                $array_dn  = array_reverse ( $array_dn_reverse, true );

                $level = count( $array_dn ) - (int)(count(explode(",", $GLOBALS['phpgw_info']['server']['ldap_context'])) + 1);

                if ($level == 0)
                        $display .= '+';
                else
                {
					for ($i=0; $i<$level; $i++)
						$display .= '---';
                }

                reset ( $array_dn );
                $display .= ' ' . (current ( $array_dn ) );

				$dn = trim(strtolower($dn));
				$options[$dn] = $display;
        	}
    	    return $options;
		}

		function exist_account_lid($account_lid)
		{
			$conection = $GLOBALS['phpgw']->common->ldapConnect();
			$sri = ldap_search($conection, $GLOBALS['phpgw_info']['server']['ldap_context'], "uid=" . $account_lid);
			$result = ldap_get_entries($conection, $sri);
			return $result['count'];
		}

		function exist_email($mail)
		{
			$conection = $GLOBALS['phpgw']->common->ldapConnect();
			$sri = ldap_search($conection, $GLOBALS['phpgw_info']['server']['ldap_context'], "mail=" . $mail);
			$result = ldap_get_entries($conection, $sri);
			ldap_close($conection);

			if ($result['count'] == 0)
				return false;
			else
				return true;
		}

		function array_invert($array)
		{
			$result[] = end($array);
			while ($item = prev($array))
				$result[] = $item;
			return $result;
		}

		function get_next_id()
		{
			// Busco o ID dos accounts
			$query_accounts = "SELECT id FROM phpgw_nextid WHERE appname = 'accounts'";
			$GLOBALS['phpgw']->db->query($query_accounts);
			while($GLOBALS['phpgw']->db->next_record())
			{
				$result_accounts[] = $GLOBALS['phpgw']->db->row();
			}
			$accounts_id = $result_accounts[0]['id'];

			// Busco o ID dos groups
			$query_groups = "SELECT id FROM phpgw_nextid WHERE appname = 'groups'";
			$GLOBALS['phpgw']->db->query($query_groups);
			while($GLOBALS['phpgw']->db->next_record())
			{
				$result_groups[] = $GLOBALS['phpgw']->db->row();
			}
			$groups_id = $result_groups[0]['id'];

			//Retorna o maior dos ID's
			if ($accounts_id >= $groups_id)
				return $accounts_id;
			else
				return $groups_id;
		}

		function make_list_app($account_lid, $user_applications='', $disabled='')
		{
			// create list of ALL available apps
			$availableAppsGLOBALS = $GLOBALS['phpgw_info']['apps'];

			// create list of available apps for the user
			$query = "SELECT * FROM phpgw_expressoadmin_apps WHERE manager_lid = '".$account_lid."'";
			$GLOBALS['phpgw']->db->query($query);
			while($GLOBALS['phpgw']->db->next_record())
			{
				$availableApps[] = $GLOBALS['phpgw']->db->row();
			}

			// Retira alguns modulos
			if (count($availableApps))
			{
				foreach ($availableApps as $key => $value)
				{
					if ($value['app'] != 'phpgwapi')
						$tmp[] = $availableApps[$key];
				}
			}
			$availableApps = $tmp;

			// Cria um array com as aplicacoes disponiveis para o manager, com as atributos das aplicacoes.
			$availableAppsUser = array();
			if (count($availableApps))
			{
				foreach($availableApps as $app => $title)
				{
					if ($availableAppsGLOBALS[$title['app']])
						$availableAppsUser[$title['app']] = $availableAppsGLOBALS[$title['app']];
				}
			}

			// Loop para criar dinamicamente uma tabela com 3 colunas, cada coluna com um aplicativo e um check box.
			$applications_list = '';
			$app_col1 = '';
			$app_col2 = '';
			$app_col3 = '';
			$total_apps = count($availableAppsUser);
			$i = 0;
			foreach($availableAppsUser as $app => $data)
			{
				// 1 coluna
				if (($i +1) % 3 == 1)
				{
					$checked = $user_applications[$app] ? 'CHECKED' : '';
					$app_col1 = sprintf("<td>%s</td><td width='10'><input type='checkbox' name='apps[%s]' value='1' %s %s></td>\n",
					$data['title'],$app,$checked, $disabled);
					if ($i == ($total_apps-1))
						$applications_list .= sprintf('<tr bgcolor="%s">%s</tr>','#DDDDDD', $app_col1);
				}

				// 2 coluna
				if (($i +1) % 3 == 2)
				{
					$checked = $user_applications[$app] ? 'CHECKED' : '';
					$app_col2 = sprintf("<td>%s</td><td width='10'><input type='checkbox' name='apps[%s]' value='1' %s %s></td>\n",
					$data['title'],$app,$checked, $disabled);

					if ($i == ($total_apps-1))
						$applications_list .= sprintf('<tr bgcolor="%s">%s%s</tr>','#DDDDDD', $app_col1,$app_col2);
				}
				// 3 coluna
				if (($i +1) % 3 == 0)
				{
					$checked = $user_applications[$app] ? 'CHECKED' : '';
					$app_col3 = sprintf("<td>%s</td><td width='10'><input type='checkbox' name='apps[%s]' value='1' %s %s></td>\n",
					$data['title'],$app,$checked, $disabled);

					// Cria nova linha
					$applications_list .= sprintf('<tr bgcolor="%s">%s%s%s</tr>','#DDDDDD', $app_col1, $app_col2, $app_col3);
				}
				$i++;
			}
			return $applications_list;
		}

		function exist_attribute_in_ldap($dn, $attribute, $value)
		{
			$connection = $GLOBALS['phpgw']->common->ldapConnect();
			$search = ldap_search($connection, $dn, $attribute. "=" . $value);
			$result = ldap_get_entries($connection, $search);
			ldap_close($connection);
			//_debug_array($result);
			if ($result['count'] == 0)
				return false;
			else
				return true;
		}

		function getReturnExecuteForm(){
			$response = isset($_SESSION['response'])? $_SESSION['response'] : 'false';
			$_SESSION['response'] = null;
			return $response;
		}

		function lang($key)
		{
			if (isset($_SESSION['phpgw_info']['expressoAdmin']['lang'][$key]))
				return $_SESSION['phpgw_info']['expressoAdmin']['lang'][$key];
			else
				return $key . '*';
		}


		function checkCPF($cpf)
		{
			$nulos = array("12345678909","11111111111","22222222222","33333333333",
        		       "44444444444","55555555555","66666666666","77777777777",
            		   "88888888888","99999999999","00000000000");

			/* formato do CPF */
			if (!(preg_match("/^[0-9]{3}[.][0-9]{3}[.][0-9]{3}[-][0-9]{2}$/",$cpf)))
				return false;

			/* Retira todos os caracteres que nao sejam 0-9 */
			$cpf = preg_replace("/[^0-9]/", "", $cpf);

			/*Retorna falso se houver letras no cpf */
			if (!(preg_match("/[0-9]/",$cpf)))
    			return false;

			/* Retorna falso se o cpf for nulo */
			if( in_array($cpf, $nulos) )
    			return false;

			/*Calcula o penúltimo dígito verificador*/
			$acum=0;
			for($i=0; $i<9; $i++)
			{
  				$acum+= $cpf[$i]*(10-$i);
			}

			$x=$acum % 11;
			$acum = ($x>1) ? (11 - $x) : 0;
			/* Retorna falso se o digito calculado eh diferente do passado na string */
			if ($acum != $cpf[9]){
  				return false;
			}
			/*Calcula o último dígito verificador*/
			$acum=0;
			for ($i=0; $i<10; $i++)
			{
  				$acum+= $cpf[$i]*(11-$i);
			}

			$x=$acum % 11;
			$acum = ($x > 1) ? (11-$x) : 0;
			/* Retorna falso se o digito calculado eh diferente do passado na string */
			if ( $acum != $cpf[10])
			{
  				return false;
			}
			/* Retorna verdadeiro se o cpf eh valido */
			return true;
		}
		function make_list_personal_data_fields($account_lid, $acl = '')
		{
			// Sem restricao nenhuma na edicao dos campos pessoais	=> $acl=0;
			// Com restricao apenas na edicao do Tel. Comercial 	=> $acl=1;
			// Com restricao apenas na edicao do Tel. Celular		=> $acl=2;
			// Com restricao na edicao do Tel. Comercial e Celular	=> $acl=3;

			$personal_data_fields = array(
				array( 'text' => lang( '%1 telephone number', lang( 'Commercial' ) ), 'acl' => 1 ),
				array( 'text' => lang( '%1 telephone number', lang( 'Mobile' ) ),     'acl' => 2 ),
				array( 'text' => lang( '%1 telephone number', lang( 'Home' ) ),       'acl' => 4 ),
				array( 'text' => lang( 'Birthdate' ),                                 'acl' => 8 ),
			);
			$list_personal_data = "<tr>";

			foreach($personal_data_fields as $i => $data_field)	{
				$checked = ($data_field['acl'] & $acl) ? "CHECKED" : "";
				$list_personal_data .= "<td align=right bgcolor='#DDDDDD'>{$data_field['text']}</td>".
				"<td bgcolor='#DDDDDD' width='10'><input type='checkbox' name='acl_block_personal_data[]'".
				" value='{$data_field['acl']}' $checked></td>";
			}
			$list_personal_data .= "</tr>";
			return $list_personal_data;
		}

		function make_lang($ram_lang)
		{
			$a_lang = explode("_", $ram_lang);
			$a_lang_reverse  = array_reverse ( $a_lang, true );
			array_pop ( $a_lang_reverse );
			$a_lang  = array_reverse ( $a_lang_reverse, true );
			$a_new_lang = implode ( " ", $a_lang );
			return lang($a_new_lang);
		}

		function make_dinamic_lang($template_obj, $block)
		{
			$tpl_vars = $template_obj->get_undefined($block);
			$array_langs = array();

			foreach ($tpl_vars as $atribute)
			{
				$lang = strstr($atribute, 'lang_');
				if($lang !== false)
				{
					//$template_obj->set_var($atribute, $this->make_lang($atribute));
					$array_langs[$atribute] = $this->make_lang($atribute);
				}
			}
			return $array_langs;
		}
		
		function isMembership( $groups )
		{
			$memberships = array_map(create_function('$a', 'return $a[\'account_id\'];'), $GLOBALS['phpgw']->accounts->memberships);
			foreach ( (array)$groups as $grp ) if ( in_array((int)$grp, $memberships) ) return true;
			return false;
		}
	}

	class sectors_object
	{
		var $sector_name;
		var $sector_context;
		var $sector_level;
		var $sector_leaf;
		var $sectors_list = array();
		var $level;

		function sectors_object($sector_name, $sector_context, $sector_level, $sector_leaf)
		{
			$this->sector_name = $sector_name;
			$this->sector_context = $sector_context;
			$this->sector_level = $sector_level;
			$this->sector_leaf = $sector_leaf;
		}
	}
