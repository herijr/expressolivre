<?php
include_once("class.imap_functions.inc.php");
include_once("class.functions.inc.php");

class ldap_functions
{
	var $ds = null;
	var $ldap_host;
	var $ldap_context;
	var $imap;
	var $max_result;
	var $functions;
	var $sector_search_ldap;
	var $_my_org_units;
	var $_server_conf;

	function __construct()
	{
		$this->max_result    = 200;
		$this->functions     = new functions();
		$this->_server_conf  = $_SESSION['phpgw_info']['server'];
	}
	
	function my_org_units()
	{
		if ( is_null($this->_my_org_units) ) {
			
			$this->_my_org_units = new stdClass();
			$this->_my_org_units->allow = isset($this->_server_conf['my_org_units'])? unserialize($this->_server_conf['my_org_units']) : array();
			
			$conn = ldap_connect($this->_server_conf['ldap_host']);
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
			$entries = ldap_get_entries( $conn, ldap_list( $conn, $this->_server_conf['ldap_context'], '(objectClass=organizationalUnit)', array('ou') ) );
			
			$this->_my_org_units->denied = array();
			for ( $i = 0; $i < $entries['count']; $i++ )
			if ( !in_array($entries[$i]['ou'][0], $this->_my_org_units->allow ) )
				$this->_my_org_units->denied[] = $entries[$i]['ou'][0];
		}
		return $this->_my_org_units;
	}
	
	function denied( $dn )
	{
		$ous = $this->my_org_units();
		$use_array = ( count($ous->allow) > count($ous->denied) );
		foreach ( ($use_array? $ous->denied : $ous->allow) as $ou )
		if ( preg_match('/^.*'.preg_quote('ou='.strtolower($ou).','.strtolower($this->_server_conf['ldap_context']),'/').'$/', strtolower($dn)) )
			return $use_array;
		return !$use_array;
	}

	// usa o host e context do setup.
	function ldapConnect( $refer = false, $connectLdapMaster = false )
	{
		$this->ldap_host 	= $_SESSION['phpgw_info']['expressomail']['server']['ldap_host'];
		$this->ldap_context = $_SESSION['phpgw_info']['expressomail']['server']['ldap_context'];

		if( $this->ds == null )
		{
			if( $connectLdapMaster )
			{
				$ldapMaster = false;
				if( isset( $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_host'] ) )
				{
					$ldapMaster = $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_host'];
				}

				$ldapMasterDN = false;
				if( isset( $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_root_dn'] ) )
				{
					$ldapMasterDN = $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_root_dn'];
				}

				$ldapMasterPW = false;
				if( isset( $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_root_pw'] ) )
				{
					$ldapMasterPW = $_SESSION['phpgw_info']['expressomail']['server']['ldap_master_root_pw'];
				}

				if( $ldapMaster && ( $ldapMasterDN && $ldapMasterPW ) )
				{
					$this->ds = ldap_connect( $ldapMaster );
					
					ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($this->ds, LDAP_OPT_REFERRALS , 0 );				 
					ldap_bind( $this->ds, $ldapMasterDN, $ldapMasterPW );
				}
				else
				{
					$this->ldapConnect( $refer, false );
				}
			}
			else
			{
				$ldapHost = $this->ldap_host;
				$ldapDN   = $_SESSION['phpgw_info']['expressomail']['server']['ldap_root_dn'];
				$ldapPW   = $_SESSION['phpgw_info']['expressomail']['server']['ldap_root_pw'];

				$this->ds = ldap_connect( $ldapHost );

				ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($this->ds, LDAP_OPT_REFERRALS, $refer);			
	    		ldap_bind( $this->ds , $ldapDN , $ldapPW );
			}
		}
	}

	function quickSearch( $params )
	{
		$searchFor = ( isset( $params['search_for'] ) ? utf8_encode($params['search_for']) : "" );
		$field     = ( isset( $params['field'] ) ? $params['field'] : "" );
		$ID        = ( isset( $params['ID'] ) ? $params['ID'] : "" );

		$extendedInfo = false;

		if( isset( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] ) )
		{
			if( trim( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] ) === "1" )
			{
				$extendedInfo = true;
			}
		}

		$searchFor = trim($searchFor);

		$searchFor = preg_replace( '/\*/', "", $searchFor );

		$searchFor = implode("*", explode(" ", $searchFor ) );

		$searchFor = preg_replace('/\*\*/', "*", $searchFor );

		$return = array();

		$this->ldapConnect(false);

		if( $this->ds )
		{
			$justThese = array( "cn", "mail", "telephoneNumber", "phpgwAccountVisible", "uid" );

			if( $extendedInfo )
			{
				$justThese = array_merge( $justThese , array("mobile", "employeeNumber", "ou" ) );
			}

			$filter = "(&(phpgwAccountType=u)(cn=*$searchFor*)(!(phpgwaccountvisible=-1)))";

			if( ( $field != 'null') && ($ID != 'null') )
			{
				$filter = "(&(&(|(phpgwAccountType=u)(phpgwAccountType=g)(phpgwAccountType=l)(phpgwAccountType=i)(phpgwAccountType=s))(mail=*)) (|(cn=*$searchFor*)(mail=*$searchFor*)) (!(phpgwaccountvisible=-1)))";
			}
			else
			{
				$justThese = array_merge( $justThese, array( "jpegPhoto") );
			}
			
			$ldapSearch = ldap_search( $this->ds, $this->ldap_context, $filter, $justThese, 0, $this->max_result + 1 );


			if( !$ldapSearch )
			{
				$return = array(
					'status' => false,
					'error'  => 'Search fail'
				);
			}
			else
			{				
				$count_entries = ldap_count_entries( $this->ds, $ldapSearch );

				// Get user org dn.
				$user_dn = $_SESSION['phpgw_info']['expressomail']['user']['account_dn'];
				$user_sector_dn = ldap_explode_dn ( $user_dn, false );
				array_shift($user_sector_dn);
				array_shift($user_sector_dn);
				$user_sector_dn = implode(",", $user_sector_dn);
				$quickSearch_only_in_userSector = false;

				// New search only on user sector
				if( $count_entries > $this->max_result )
	 			{
	 				unset( $ldapSearch );

					$ldapSearch = ldap_search( $this->ds, $user_sector_dn, $filter, $justThese , 0 , $this->max_result + 1 );

					$quickSearch_only_in_userSector = true;
				}

				$count_entries = ldap_count_entries( $this->ds, $ldapSearch );

				if(  $count_entries > $this->max_result )
				{
					$return = array(
						'status' => false,
						'error'  => "many results",
					);
				}
				else
				{
					$info = ldap_get_entries( $this->ds, $ldapSearch );
					
					$tmp  = array();
			        
			        $tmp_users_from_user_org = array();

					for( $i = 0; $i < $info["count"]; $i++ )
					{
						$_user = ( isset( $info[$i]["mail"][0] ) ? $info[$i]["mail"][0].'%' : '%') . 
							     ( isset( $info[$i]["telephonenumber"][0] ) ? $info[$i]["telephonenumber"][0] . '%' : '%' ) .
							     ( isset( $info[$i]["mobile"][0] )? $info[$i]["mobile"][0] . '%' : '%' ) .
							     ( isset( $info[$i]["uid"][0] ) ? $info[$i]["uid"][0] . '%' : '%') .
							     ( isset( $info[$i]["jpegphoto"]['count'] ) ?  $info[$i]["jpegphoto"]['count'] .'%' : '%' ) .
							     ( isset( $info[$i]["employeenumber"][0] ) ? $info[$i]["employeenumber"][0] . '%' : '%' ) .
							     ( isset( $info[$i]["ou"][0] ) ? $info[$i]["ou"][0] : '' );

						if( $quickSearch_only_in_userSector )		     
						{	
							$tmp[ $_user ] = utf8_decode( $info[$i]["cn"][0] );
						}
						else
						{
							if ( preg_match("/$user_sector_dn/i", $info[$i]['dn']) )
							{
								$tmp_users_from_user_org[ $_user ] = utf8_decode( $info[$i]["cn"][0] );
							}
							else
							{
								$tmp[ $_user ] = utf8_decode( $info[$i]["cn"][0] );	
							}
						}
					}

					natcasesort($tmp_users_from_user_org);
					
					natcasesort($tmp);

					if (($field != 'null') && ($ID != 'null'))
					{
						$i = 0;

						$tmp = array_merge($tmp, $tmp_users_from_user_org);

						natcasesort($tmp);

						foreach ($tmp as $info => $cn)
						{
							$contacts_result[$i] = array();
							$contacts_result[$i]["cn"] = $cn;
							list ($contacts_result[$i]["mail"], $contacts_result[$i]["phone"], $contacts_result[$i]["mobile"], $contacts_result[$i]["uid"], $contacts_result[$i]["jpegphoto"], $contacts_result[$i]["employeenumber"], $contacts_result[$i]["ou"]) = explode('%', $info);
							$i++;
						}

						$contacts_result['quickSearch_only_in_userSector'] = $quickSearch_only_in_userSector;
						$contacts_result['field'] = $field;
						$contacts_result['ID'] = $ID;		
					}
					else
					{
						$options_users_from_user_org = '';
						$options = '';

						/* List of users from user org */
						$i = 0;
						foreach ($tmp_users_from_user_org as $info => $cn)
						{
							$contacts_result[$i] = array();
							$options_users_from_user_org .= $this->make_quicksearch_card($info, $cn);
							$i++;
						}

						/* List of users from others org */
						foreach ($tmp as $info => $cn)
						{
							$contacts_result[$i] = array();
							$options .= $this->make_quicksearch_card($info, $cn);
							$i++;
						}

						if ($quickSearch_only_in_userSector)
						{
							if ($options != '')
							{
								$head_option =
									'<tr class="quicksearchcontacts_unselected">' .
										'<td colspan="2" width="100%" align="center">' .
											str_replace("%1", $this->max_result,$this->functions->getLang('More than %1 results were found')) . '.<br>' .
											$this->functions->getLang('Showing only the results found in your organization') . '.';
										'</td>' .
									'</tr>';
								
								$contacts_result = $head_option . $options_users_from_user_org . $options;
							}
						}
						else
						{
							$head_option0 = "";

							$head_option1 = "";

							if (($options_users_from_user_org != '') && ($options != ''))
							{
								$head_option0 =
									'<tr class="quicksearchcontacts_unselected">' .
										'<td colspan="2" width="100%" align="center" style="background:#EEEEEE"><B>' .
											$this->functions->getLang('Users from your organization') . '</B> ['.count($tmp_users_from_user_org).']';
										'</td>' .
									'</tr>';

								$head_option1 =
									'<tr class="quicksearchcontacts_unselected">' .
										'<td colspan="2" width="100%" align="center" style="background:#EEEEEE"><B>' .
											$this->functions->getLang('Users from others organizations') . '</B> ['.count($tmp).']';
										'</td>' .
									'</tr>';
							}
							
							$contacts_result = $head_option0 . $options_users_from_user_org . $head_option1 . $options;
						}
					}

					$return = $contacts_result;
				}
			}
		}
		else
		{
			$return = array( 'status' => false, 'error' => 'No connect Ldap' );
		}

		return $return;
	}

	function make_quicksearch_card($info, $cn)
	{
		$contacts_result = array();

		$contacts_result["cn"] = $cn;

		if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'])
		    $extendedinfo=true;
		else
		    $extendedinfo=false;

		list ($contacts_result["mail"], $contacts_result["phone"], $contacts_result["mobile"], $contacts_result["uid"], $contacts_result["jpegphoto"], $contacts_result["employeenumber"], $contacts_result["ou"]) = explode('%', $info);

		if ($contacts_result['jpegphoto'])
			$photo_link = '<img src="./inc/show_user_photo.php?mail='.$contacts_result['mail'].'">';
		else
			$photo_link = '<img src="./templates/default/images/photo.jpg">';

		$phoneUser = $contacts_result['phone'];
		$mobileUser = $contacts_result["mobile"];
		if($mobileUser){
			$phoneUser .= " / $mobileUser";
		}
		
		if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['voip_enabled']) {
			$phoneUser = $contacts_result['phone'];
			if($phoneUser)
				$phoneUser = '<a title="'.$this->functions->getLang("Call to Comercial Number").'" href="#" onclick="InfoContact.connectVoip(\''.$phoneUser.'\',\'com\')">'.$phoneUser.'</a>';
			if($mobileUser)
				$phoneUser .= ' / <a title="'.$this->functions->getLang("Call to Mobile Number").'" href="#" onclick="InfoContact.connectVoip(\''.$mobileUser.'\',\'mob\')">'.$mobileUser.'</a>';
		}

		$empNumber = $contacts_result["employeenumber"];
	    if($empNumber) {
		    $empNumber = "$empNumber - ";
	    }
	    $ou = $contacts_result["ou"];
	    if($ou) {
		    $ou = "<br/>$ou" ;
	    }

		// Begin: nickname, firstname and lastname for QuickAdd.
		$fn = $contacts_result["cn"];
		$array_name = explode(" ", $fn);
		if(count($array_name) > 1){			
			$fn = $array_name[0];
			array_shift($array_name);
			$sn = implode(" ",$array_name);
		}
		// End:

		$option =
			'<tr class="quicksearchcontacts_unselected">' .
				'<td class="cc" width="1%">' .
					'<a title="'.$this->functions->getLang("Write message").'" onClick="javascript:QuickSearchUser.create_new_message(\''.$contacts_result["cn"].'\', \''.$contacts_result["mail"].'\')">' .
						$photo_link .
					'</a>' .
				'</td>' .
				'<td class="cc">' .
					'<span name="cn">' . $empNumber . $contacts_result['cn'] . '</span>' . '<br>' .
					'<a title="'.$this->functions->getLang("Write message").'" onClick="javascript:QuickSearchUser.create_new_message(\''.$contacts_result["cn"].'\', \''.$contacts_result["mail"].'\')">' .
						'<font color=blue>' .
						'<span name="mail">' . $contacts_result['mail'] . '</span></a></font>'.
						'&nbsp;&nbsp;<img src="templates/default/images/user_card.png" style="cursor: pointer;" title="'.$this->functions->getLang("Add Contact").'" onclick="javascript:connector.loadScript(\'ccQuickAdd\');ccQuickAddOne.showList(\''.$fn.','.$fn.','.$sn.','.$contacts_result["mail"].'\')">&nbsp;&nbsp;'.
						'<img src="templates/default/images/add_user.png" style="cursor: pointer;" title="'.$this->functions->getLang("Add Contact Messenger Expresso").'" onclick="addContactMessenger(\''.$contacts_result["uid"].'\', \''.$contacts_result["cn"].'\')">' .						
					'<br>' .
					$phoneUser .
					$ou .
				'</td>' .
				'</tr>';
		
		return $option;
	}

	function get_catalogs(){
		$catalogs = array();
		$catalogs[0] = $this->functions->getLang("Global Catalog");
		return $catalogs;
	}
	
	function get_organizations( $params )
	{
		$organizations = array();

		$this->ldapConnect();

		if( $this->ds )
		{
			$filter    = "(&(objectClass=organizationalUnit)(!(phpgwAccountVisible=-1)))";
			$justthese = array("ou");
			$sr        = ldap_list($this->ds, $this->ldap_context, $filter, $justthese);
			$info      = ldap_get_entries( $this->ds, $sr );

			if( isset( $params['catalog'] ) )
			{
				if( $info["count"] == 0 )
				{
					$organizations[0]['ou'] = $this->ldap_context;
				}

				for( $i=0; $i < $info["count"]; $i++ )
				{			
					$organizations[$i] = $info[$i]["ou"][0];
				}	
			}
			else
			{				
				if( $info["count"] == 0 )
				{
				    $organizations[0]['ou'] = $this->ldap_context;
				    $organizations[0]['dn'] = $this->ldap_context;
				}
				else
				{
				    for( $i = 0; $i < $info["count"]; $i++ )
				    {
					    $organizations[$i]['ou'] = $info[$i]["ou"][0];
					    $organizations[$i]['dn'] = $info[$i]["dn"];
				    }
				}
			}

			sort( $organizations );

			return $organizations;
		}

		return false;
	}
	
	//Busca usuarios de um contexto e ja retorna as options do select - usado por template serpro;
	function search_users($params)
    {
    	$this->ldapConnect();
        //Monta lista de Grupos e Usuarios
        $users = Array();
        $groups = Array();
        $user_context= $this->ldap_context;
        $owner = $_SESSION['phpgw_info']['expressomail']['user']['owner'];
        $filtro =utf8_encode($params['filter']);
        $context =utf8_encode($params['context']);//adicionado
		$use_my_org_units = ( isset($params['use_my_org_units']) && $params['use_my_org_units'] === 'true' );

    	if( $this->ds )
        {
        	// Search groups;
			$groups 	= array();
            $justthese	= array("gidNumber","cn");
            
            if ($params['type'] == 'search')
            {
                $sr = ldap_search($this->ds, $context, ("(&(phpgwaccounttype=g)(!(phpgwaccountvisible=-1))(cn=*$filtro*))"),$justthese);
            }
            else
            {
                $sr = ldap_list($this->ds,  $context ? $context : $user_context, ("(&(phpgwaccounttype=g)(!(phpgwaccountvisible=-1))(cn=*".$filtro."*))"),$justthese);
            }
            
            $info = ldap_get_entries($this->ds, $sr);

            for( $i=0; $i<$info["count"]; $i++)
            {
                $groups[$uids=$info[$i]["gidnumber"][0]] = Array('name'=> $uids=$info[$i]["cn"][0], 'type'    =>    'g');
            }

            // Search users
            $users		= array();
            $justthese 	= array("phpgwaccountvisible","uidNumber","cn");

            if ($params['type'] == 'search')
            {
				$sr = ldap_search($this->ds, $context, ("(&(phpgwaccounttype=u)(!(phpgwaccountvisible=-1))(phpgwaccountstatus=A)(|(cn=*$filtro*)(mail=$filtro*)))"),$justthese);
			}              
            else
			{            	
				$sr = ldap_list($this->ds, $context ? $context : $user_context, ("(&(phpgwaccounttype=u)(!(phpgwaccountvisible=-1))(phpgwaccountstatus=A)(|(cn=*$filtro*)(mail=$filtro*)))"),$justthese);
            }   

            $info = ldap_get_entries($this->ds, $sr);

            for( $i=0; $i<$info["count"]; $i++ )
            {
                if ( isset($info[$i]["phpgwaccountvisible"][0]) && $info[$i]["phpgwaccountvisible"][0] == '-1'){ continue; }
				
				if ( $use_my_org_units && $this->denied($info[$i]['dn']) ){ continue; }
				
            	$users[$uids=$info[$i]["uidnumber"][0]] = Array('name'    =>    $uids=$info[$i]["cn"][0], 'type'    =>    'u');
            }
        }
        
        ldap_close($this->ds);

    	@asort($users);
        @reset($users);
        @asort($groups);
        @reset($groups);
        $user_options ='';
        $group_options ='';

        if( count($groups) > 0 )
        {
	    	foreach( $groups as $id => $user_array )
	    	{
	                $newId = $id.'U';
	                $group_options .= '<option  value="'.$newId.'">'.utf8_decode($user_array['name']).'</option>'."\n";
	        }
	    }
    	
    	if( count($users) > 0 )
    	{
	    	foreach( $users as $id => $user_array )
	    	{
	            if( $owner != $id )
	            {
	                $newId = $id.'U';
	                $user_options .= '<option  value="'.$newId.'">'.utf8_decode($user_array['name']).'</option>'."\n";
	            }
	        }
	    }
        
        return array("users" => $user_options, "groups" => $group_options);
    }

	function catalogsearch($params)
	{
		$cn	= $params['search_for'] ? "*".utf8_encode($params['search_for'])."*" : "*";
		$max_result	  = $params['max_result'] ? $params['max_result'] : $this->max_result;
		$catalog = $params['catalog'];
		$error = False;
		if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'])
		    $extendedinfo=true;
		else
		    $extendedinfo=false;


		$this->ldapConnect();

		$params['organization'] == 'all' ? $user_context = $this->ldap_context : $user_context = "ou=".$params['organization'].",".$this->ldap_context;

		if ($this->ds) {
			if ($catalog == 0){
				//os atributos "employeeNumber" e "ou" foram adicionado ao vetor de busca;
				if($extendedinfo)
				$justthese = array("cn", "mail", "phpgwaccounttype", "phpgwAccountVisible", "employeeNumber", "ou");
				else
				    $justthese = array("cn", "mail", "phpgwaccounttype", "phpgwAccountVisible");

				$filter="(&(|(phpgwAccountType=u)(phpgwAccountType=l))(cn=".$cn."))";
				//$user_context = "ou=".$params['organization'].",".$this->ldap_context;
			}else {
				//os atributos "employeeNumber" e "ou" foram adicionado ao vetor de busca;
				if($extendedinfo)
				$justthese = array("cn", "mail", "employeeNumber", "ou");
				else
				    $justthese = array("cn", "mail");
				$filter="(&(objectClass=".$this->object_class.")(cn=".$cn."))";
			}

			$sr=@ldap_search($this->ds, $user_context, $filter, $justthese, 0, $max_result+1);
			if(!$sr)
				return null;
			$count_entries = ldap_count_entries($this->ds,$sr);
			if ($count_entries > $max_result){
				$info = null;
				$error = True;
			}
			else
				$info = ldap_get_entries($this->ds, $sr);

			ldap_close($this->ds);

			$u_tmp = array();
			$g_tmp = array();

			for ($i=0; $i<$info["count"]; $i++){
				if((!$catalog==0)||(strtoupper($info[$i]["phpgwaccounttype"][0]) == 'U') && ($info[$i]["phpgwaccountvisible"][0] != '-1'))
					//aqui eh feita a concatenacao do departamento ao cn;
					$u_tmp[$info[$i]["mail"][0]] = utf8_decode($info[$i]["cn"][0]). '%' . $info[$i]["ou"][0];
				if((!$catalog==0)||(strtoupper($info[$i]["phpgwaccounttype"][0]) == 'L') && ($info[$i]["phpgwaccountvisible"][0] != '-1'))
					$g_tmp[$info[$i]["mail"][0]] = utf8_decode($info[$i]["cn"][0]);
			}

			natcasesort($u_tmp);
			natcasesort($g_tmp);

			$i = 0;
			$users = array();

			foreach ($u_tmp as $mail => $cn){

				$tmp = explode("%", $cn); //explode o cn pelo caracter "%" e joga em $tmp;
				$name = $tmp[0]; //pega o primeiro item (cn) do vetor resultante do explode acima;
				$department = $tmp[1]; //pega o segundo item (ou) do vetor resultanto do explode acima;
				$users[$i++] = array("name" => $name, "email" => $mail, "department" => $department);

			}
			unset($u_tmp);

			$i = 0;
			$groups = array();

			foreach ($g_tmp as $mail => $cn){
				$groups[$i++] = array("name" => $cn, "email" => $mail);
			}
			unset($g_tmp);

			return  array('users' => $users, 'groups' => $groups, 'error' => $error);
		}else
		return null;
	}

	function get_emails_ldap(){

		$result['mail']= array();
		$result['mailalter']= array();
		$user = $_SESSION['phpgw_info']['expressomail']['user']['account_lid'];
		$this->ldapConnect();
		if ($this->ds) {
			$filter="uid=".$user;
			$justthese = array("mail","mailAlternateAddress");
			$sr = ldap_search($this->ds,$this->ldap_context, $filter, $justthese);
			$ent = ldap_get_entries($this->ds, $sr);
			ldap_close($this->ds);

			for ($i=0; $i<$ent["count"]; $i++){
				$result['mail'][] = $ent[$i]["mail"][0];
				$result['mailalter'][] = $ent[$i]["mailalternateaddress"][0];
			}
		}
		return $result;
	}

	//Busca usuarios de um contexto e ja retorna as options do select;
	function get_available_users($params)
    {
        $this->ldapConnect();
        //Monta lista de Grupos e Usuarios
        $users = Array();
        $groups = Array();
        $user_context= $params['context'];
        $owner = $_SESSION['phpgw_info']['expressomail']['user']['owner'];

        if ($this->ds)
        {
            $justthese = array("gidNumber","cn");
            if ($params['type'] == 'search')
                $sr=ldap_search($this->ds, $user_context, ("(&(cn=*)(phpgwaccounttype=g)(!(phpgwaccountvisible=-1)))"),$justthese);
            else
                $sr=ldap_list($this->ds, $user_context, ("(&(cn=*)(phpgwaccounttype=g)(!(phpgwaccountvisible=-1)))"),$justthese);
            $info = ldap_get_entries($this->ds, $sr);
            for ($i=0; $i<$info["count"]; $i++)
                $groups[$uids=$info[$i]["gidnumber"][0]] = Array('name'    =>    $uids=$info[$i]["cn"][0], 'type'    =>    "g");
            $justthese = array("phpgwaccountvisible","uidNumber","cn");
            if ($params['type'] == 'search')
                $sr=ldap_search($this->ds, $user_context, ("(&(cn=*)(phpgwaccounttype=u)(!(phpgwaccountvisible=-1)))"),$justthese);
            else
                $sr=ldap_list($this->ds, $user_context, ("(&(cn=*)(phpgwaccounttype=u)(!(phpgwaccountvisible=-1)))"),$justthese);

            $info = ldap_get_entries($this->ds, $sr);
            for ($i=0; $i<$info["count"]; $i++)
            {
                if ($info[$i]["phpgwaccountvisible"][0] == '-1')
                    continue;
                $users[$uids=$info[$i]["uidnumber"][0]] = Array('name'    =>    $uids=$info[$i]["cn"][0], 'type'    =>    "u");
            }
        }
        ldap_close($this->ds);

        @asort($users);
        @reset($users);
        @asort($groups);
        @reset($groups);
        $user_options ='';
        $group_options ='';

        foreach($groups as $id => $user_array) {
                $newId = $id.'U';
                $group_options .= '<option  value="'.$newId.'">'.utf8_decode($user_array['name']).'</option>'."\n";
        }
        foreach($users as $id => $user_array) {
            if($owner != $id){
                $newId = $id.'U';
                $user_options .= '<option  value="'.$newId.'">'.utf8_decode($user_array['name']).'</option>'."\n";
            }
        }
        return array("users" => $user_options, "groups" => $group_options);
    }

	//Busca usuarios de um contexto e ja retorna as options do select;
	function get_available_users2($params)
	{
            $this->ldapConnect();
            $context    = $params['context'];
            $justthese  = array("cn", "uid", "cn");
            $filter     = ( isset($params['cn']) ) ? "(&(cn=*".$params['cn']."*)(phpgwaccounttype=u)(!(phpgwaccountvisible=-1)))" : 
                                "(&(phpgwaccounttype=u)(!(phpgwaccountvisible=-1)))"; 
            
            if ($this->ds)
	    {
                $sr = ldap_search($this->ds, $context, $filter, $justthese);
                
                $entries = ldap_get_entries($this->ds, $sr);

                for ($i=0; $i<$entries["count"]; $i++)
                {
                    if($_SESSION['phpgw_info']['expressomail']['user']['account_lid'] != $entries[$i]["uid"][0])
                    {
                        $u_tmp[$entries[$i]["uid"][0]] = $entries[$i]["cn"][0];
                    }
                }

                natcasesort($u_tmp);

                $i = 0;
                $users = array();

                if (count($u_tmp))
                {
                    foreach ($u_tmp as $uidnumber => $cn)
                    {
                        $options .= "<option value=$uidnumber>$cn</option>";
                    }
                    
                    unset($u_tmp);
                }

                ldap_close($this->ds);
                return $options;
            }
	}

	function uid2cn($uid)
	{
		// do not follow the referral
		$this->ldapConnect();
		if ($this->ds)
		{
			$filter="(&(phpgwAccountType=u)(uid=$uid))";
			$justthese = array("cn");
			$sr=@ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
			if(!$sr)
				return false;
			$info = ldap_get_entries($this->ds, $sr);
			return utf8_decode($info[0]["cn"][0]);
		}
		return false;
	}

	function uidNumber2cn($uidNumber)
	{
		$uidNumber = json_decode($uidNumber);

		if (is_array($uidNumber))
			$uidNumber = implode(')(uidnumber=', $uidNumber);

		// do not follow the referral
		$this->ldapConnect();

		if ($this->ds)
		{
			// $filter="(&(phpgwAccountType=u)(|)";
			$filter="(&(phpgwAccountType=u)(|(uidnumber=$uidNumber)))";
			$justthese = array("cn", "mail");

			$sr = @ldap_search($this->ds, $this->ldap_context, $filter, $justthese);

			if (!$sr)
				return false;

			$info = ldap_get_entries($this->ds, $sr);

			$list_contacts = array();
			foreach ($info as $entry) 
			{
				if (is_array($entry) && isset($entry['cn']))
				{
					$contact = array();
					$contact['cn'] = $entry['cn'][0];
					$contact['mail'] = $entry['mail'][0];
					$contact['dn'] = $entry['dn'];

					$list_contacts[] = $contact;
				}
			}

			return $list_contacts;
		}

		return false;
	}

	function groupcn2gidNumber($cn)
	{
		// do not follow the referral
		$this->ldapConnect();
		if ($this->ds)
		{
			$filter="(&(phpgwAccountType=g)(objectClass=posixGroup)(cn=$cn))";
			$justthese = array("cn","gidnumber");
			$sr=@ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
			if(!$sr)
				return false;
			$info = ldap_get_entries($this->ds, $sr);
			return utf8_decode($info[0]["gidnumber"][0]);
		}
		return false;
	}
	function uidnumber2uid($uidnumber)
	{
		// do not follow the referral
		$this->ldapConnect();
		if ($this->ds)
		{
			$filter="(&(phpgwAccountType=u)(uidnumber=$uidnumber))";
			$justthese = array("uid");
			$sr=@ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
			if(!$sr)
				return false;
			$info = ldap_get_entries($this->ds, $sr);
			return $info[0]["uid"][0];
		}
		return false;
	}
	function getSharedUsersFrom($params){
		$filter = '';
		$i = 0;		
		//Added to save if must save sent messages in shared folder
		$acl_save_sent_in_shared = array();
		
		if($params['uids']) {
			$uids = explode(";",$params['uids']);
			$this->imap = new imap_functions();			
			foreach($uids as $index => $uid){
				$params = array();
				//Added to save if user has create permission 
				$acl_create_message = array();
				$acl = $this->imap->getacltouser($uid);
				if ( preg_match("/a/",$acl )){				
					$filter .= "(uid=$uid)";					
					if ( preg_match("/p/",$acl )){				
						$acl_save_sent_in_shared[ $i ] =$uid;
						$i++;
					}					
				}							
			}			
		}
		
		$this->ldapConnect();
		if ($this->ds) {
			$justthese = array("cn","mail","uid");
			if($filter) {
				$filter="(&(|(phpgwAccountType=u)(phpgwAccountType=s))(|$filter))";
				$sr		=	ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
				ldap_sort($this->ds,$sr,"cn");
				$info 	= 	ldap_get_entries($this->ds, $sr);
				$var = print_r($acl_save_sent_in_shared, true);				
				for ($i = 0;$i < $info["count"]; $i++){
					$info[$i]['cn'][0] = utf8_decode($info[$i]['cn'][0]);
					//verify if user has permission to save sent messages in a shared folder
					if ( in_array( $info[$i]['uid'][0],$acl_save_sent_in_shared) ){						
						$info[$i]['save_shared'][0] = 'y';
					} else $info[$i]['save_shared'][0] = 'n';
				}
			}
			
			// Conf full name for display on sending email
			$fullNameUser = $_SESSION['phpgw_info']['expressomail']['user']['fullname'];
			if( trim($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['display_user_email']) != "" )
			{
				$fullNameUser = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['display_user_email'];
			}

			$info['myname'] = $fullNameUser;

			//Find institucional_account.
			$filter="(&(phpgwAccountType=i)(mailForwardingAddress=".$_SESSION['phpgw_info']['expressomail']['user']['email']."))";
			$sr	= ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
			##
			# @AUTHOR Rodrigo Souza dos Santos
			# @DATE 2008/09/17
			# @BRIEF Changing to ensure that the variable session is always with due value.
			##
			if(ldap_count_entries($this->ds,$sr))
			{
				ldap_sort($this->ds,$sr,"cn");
				$result = ldap_get_entries($this->ds, $sr);
				for ($j = 0;$j < $result["count"]; $j++){
					$info[$i]['cn'][0] = utf8_decode($result[$j]['cn'][0]);
					$info[$i]['mail'][0] = $result[$j]['mail'][0];
					$info[$i]['save_shared'][0] = 'n';
					$info[$i++]['uid'][0] = $result[$j]['uid'][0];					
				}
			}

			$_SESSION['phpgw_info']['expressomail']['user']['shared_mailboxes'] = $info;
			
			return $info;
		}
	}

	function getUserByEmail($params)
	{
		$expires = 60*60*24*30; /* 30 days */

		header("Cache-Control: maxage=".$expires);
		header("Pragma: public");
		header("Expires: ".gmdate('D, d M Y H:i:s', time()+$expires));	
		
		$filter = "(&(phpgwAccountType=u)(mail=".$params['email']."))";
		
		$ldap_context = $_SESSION['phpgw_info']['expressomail']['ldap_server']['dn'];
		
		if( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] )
		{
		    $extendedinfo = true;
		}
		else
		{
		    $extendedinfo = false;
		}

		if( $extendedinfo )
		{
		    $justthese = array("cn","uid","telephoneNumber","jpegPhoto","mobile","ou","employeeNumber");
		}
		else
		{
		    $justthese = array("cn","uid","telephoneNumber","jpegPhoto");
		}

		// Follow the referral
		$this->ldapConnect();
		
		if( $this->ds )
		{
			$sr = @ldap_search( $this->ds, $ldap_context, $filter, $justthese );

			if ( !$sr )
			{
				return null;
			}
			else
			{
				$entry = ldap_first_entry( $this->ds, $sr );

				if( $entry )
				{
					$obj =  array(
						    "cn"             => utf8_decode(current(ldap_get_values($this->ds, $entry, "cn"))),
							"email"          => $params['email'],
							"uid"            => ldap_get_values($this->ds, $entry, "uid"),
							"type"           => "global",
							"mobile"         => @ldap_get_values($this->ds, $entry, "mobile"),
							"telefone"       => @ldap_get_values($this->ds, $entry, "telephonenumber"),
							"ou"             => @ldap_get_values($this->ds, $entry, "ou"),
							"employeeNumber" => @ldap_get_values($this->ds, $entry, "employeeNumber")
					);

					$_SESSION['phpgw_info']['expressomail']['contact_photo'] = @ldap_get_values_len( $this->ds, $entry, "jpegphoto" );
					
					ldap_close( $this->ds );
					
					return $obj;
				}
			}
		}
		
		return null;
	}
	
	function uid2uidnumber($uid)
	{
		// do not follow the referral
		$this->ldapConnect();
		if ($this->ds)
		{
			$filter="(&(phpgwAccountType=u)(uid=$uid))";
			$justthese = array("uidnumber");
			$sr=@ldap_search($this->ds, $this->ldap_context, $filter, $justthese);
			if(!$sr)
				return false;
			$info = ldap_get_entries($this->ds, $sr);
			return $info[0]["uidnumber"][0];
		}
		return false;
	}

	function save_telephoneNumber($params){
		$return = array();
		if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['blockpersonaldata']){
			$return['error'] = $this->functions->getLang("You can't modify your Commercial Telephone.");
			return $return; 
		}
		$old_telephone = 0;

  		$params['number'] = preg_replace("/[^0-9]/", "", $params['number']);
  
		$length = strlen($params['number']);
		  
		switch($length)
		{
			case 10:
				$params['number'] = preg_replace("/([0-9]{2})([0-9]{4})([0-9]{4})/", "($1)$2-$3", $params['number']);
				break;
			case 11:
				$params['number'] = preg_replace("/([0-9]{2})([0-9]{5})([0-9]{4})/", "($1)$2-$3", $params['number']);
				break;	
		}

		$pattern = '/\([0-9]{2,3}\)[0-9]{4,5}-[0-9]{4}$/';		
		if ((strlen($params['number']) != 0) && (!preg_match($pattern, $params['number'])))
		{
			$return['error'] = $this->functions->getLang('The format of telephone number is invalid');
			return $return;
		}
		if($params['number'] != $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number']) {
			$old_telephone = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number'];
			$this->ldapConnect(false, true );
			if(strlen($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number']) == 0) {
				$info['telephonenumber'] = $params['number'];
				$result = @ldap_mod_add($this->ds, $_SESSION['phpgw_info']['expressomail']['user']['account_dn'], $info);
			}					
			else {
				$info['telephonenumber'] = $params['number'];
				$result = @ldap_mod_replace($this->ds, $_SESSION['phpgw_info']['expressomail']['user']['account_dn'], $info);
			}
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number'] = $info['telephonenumber'];
			// 	Log updated telephone number by user action
			include_once('class.db_functions.inc.php');
			$db_functions = new db_functions();
			$db_functions->write_log('modified user telephone',"User changed its own telephone number in preferences $old_telephone => ".$info['telephonenumber']);			
			unset($info['telephonenumber']);			
		}
		return array( 'ok' => true, 'number' => $params['number'] );
	}
	
	function simpleSearch( $params ) {
		
		$result = array();
		
		if ( isset( $params['search'] ) ) {
			if ( strlen( $params['search'] ) < 3 ) $this->return_json( array( 'status' => 'FEW CHARACTERS TO SEARCH' ) );
			
			if ( !$this->ds ) $this->ldapConnect();
			
			$search = $this->ldap_escape( $params['search'] );
			$sr = ldap_search(
				$this->ds, $this->ldap_context,
				'(|(uid='.$search.')(cn=*'.str_replace( ' ', '*', $search ).'*))', array( 'cn', 'uid' ), 0,
				100, isset( $params['timelimit'] )? $params['timelimit'] : 10
			);
			
			$result['status'] = strtoupper( ldap_error( $this->ds ) );
			if ( connection_aborted() || !$sr ) $this->return_json( $result );
			
			if ( ldap_count_entries( $this->ds, $sr ) > 0 ) {
				$entries = ldap_get_entries( $this->ds, $sr );
				if ( $entries['count'] == 1 ) {
					$params['get'] = $entries[0]['uid'][0];
				} else {
					for ( $i = 0; $i < $entries['count']; $i++ )
						$result['users'][$entries[$i]['uid'][0]] = $entries[$i]['cn'][0];
					asort( $result['users'] );
					$this->return_json( $result );
				}
			}
		}
		
		if ( isset( $params['get'] ) ) {
			if ( strlen( $params['get'] ) < 1 ) $this->return_json( array( 'status' => 'FEW CHARACTERS TO SEARCH' ) );
			
			if ( !$this->ds ) $this->ldapConnect();
			
			$sr = ldap_search(
				$this->ds, $this->ldap_context, '(uid='.$this->ldap_escape( $params['get'] ).')', array( 'cn', 'uid', 'jpegPhoto' )
			);
			
			$result['status'] = strtoupper( ldap_error( $this->ds ) );
			if ( connection_aborted() || !$sr ) $this->return_json( $result );
			
			if ( ldap_count_entries( $this->ds, $sr ) == 1 ) {
				$entry = ldap_get_entries( $this->ds, $sr );
				$result['user'] = array(
					'cn'  => $entry[0]['cn'][0],
					'uid' => $entry[0]['uid'][0],
					'img' => isset( $entry[0]['jpegphoto'][0] )?'data:image/jpeg;base64,'.base64_encode( $entry[0]['jpegphoto'][0] ) : false,
				);
			}
		}
		
		$this->return_json( $result );
	}
	
	function return_json( $value ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $value );
		exit;
	}
	
	function ldap_escape( $string ) {
		return str_replace( array( '\\', '*', '(', ')', "\x00" ), array( '\\\\', '\*', '\(', '\)', "\\x00" ), $string );
	}

	function __destruct()
	{
		if( $this->ds != null ){ ldap_close( $this->ds ); }
	}
}
