<?php
defined('PHPGW_INCLUDE_ROOT') || define('PHPGW_INCLUDE_ROOT','../');
defined('PHPGW_API_INC') || define('PHPGW_API_INC','../phpgwapi/inc');	
include_once(PHPGW_API_INC.'/class.common.inc.php');
include_once('class.functions.inc.php');

function ldapRebind($ldap_connection, $ldap_url)
{
	// Enquanto estivermos utilizando referral na arvore ldap, teremos que continuar a utilizar o usuário sistemas:expresso.
	// Depois, quando não existir mais referral, não existirá a necessidade de ldapRebind.
	//ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_master_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_master_root_pw']);
	if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
	{
		@ldap_bind($ldap_connection, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']);
	}
}

class ldap_functions
{
	var $ldap;
	var $current_config;
	var $functions;
	var $manager_contexts;
	var $radius;
	
	function ldap_functions()
	{
		if ( !is_array($GLOBALS['phpgw_info']['server']) )
			$GLOBALS['phpgw_info']['server'] = $_SESSION['phpgw_info']['expresso']['server'];
		
		$this->current_config = $_SESSION['phpgw_info']['expresso']['expressoAdmin'];
		$common = new common();
		
		if ( (!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) &&
			 (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) &&
			 (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw'])) )
		{
			$this->ldap = $common->ldapConnect($GLOBALS['phpgw_info']['server']['ldap_master_host'],
											   $GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
											   $GLOBALS['phpgw_info']['server']['ldap_master_root_pw']);
		}
		else
		{
			$this->ldap = $common->ldapConnect();
		}
		
		$account_lid = isset( $_SESSION['phpgw_info']['expresso']['user']['account_lid'] )?
			$_SESSION['phpgw_info']['expresso']['user']['account_lid'] : $GLOBALS['phpgw']->accounts->data['account_lid'];
		
		$this->functions = new functions;
		$manager_acl = $this->functions->read_acl( $account_lid );
		$this->manager_contexts = $manager_acl['contexts'];
		
		$this->radius = CreateObject('expressoAdmin1_2.soradius');
	}
	function getRadiusConf()
	{
		return $this->radius->getRadiusConf();
	}
	/* expressoAdmin: email lists : deve utilizar o ldap Host Master com o usuario e senha do CC*/
	/* ldap connection following referrals and using Master config, from setup */
	function ldapMasterConnect()
	{
		/*
		$common = new common();
		if ( (!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) &&
			 (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) &&
			 (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw'])) )
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_master_host']);
			ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_set_rebind_proc($ldap_connection, ldapRebind);
			ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_master_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_master_root_pw']);
		}
		else
		{
			$ldap_connection = $common->ldapConnect($GLOBALS['phpgw_info']['server']['ldap_host'],
											   $GLOBALS['phpgw_info']['server']['ldap_root_dn'],
											   $GLOBALS['phpgw_info']['server']['ldap_root_pw'], true);
		}
		
		// If success, return follow_referral connection. Else, return normal connection.
		if ($ldap_connection)
			return $ldap_connection;
		else
			return $this->ldap;
		*/
		
		// Este if é para utilizar o master. (para replicação)
		if ( (!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) && ($ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_master_host'])) )
		{
			/*
			ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_set_rebind_proc($ldap_connection, ldapRebind);
			if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
			{
				if ( ! ldap_bind($ldap_connection, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']) )
				{
					return false;
				}
			}
			return $ldap_connection;
			*/
			ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, false);
			
			if ( ($GLOBALS['phpgw_info']['server']['ldap_root_dn'] != '') && ($GLOBALS['phpgw_info']['server']['ldap_root_pw'] != '') )
			{
				if ( ! ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']) )
				{
					return false;
				}
			}
			return $ldap_connection;
		}
		else
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			if ($ldap_connection)
			{
				ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
				if ( ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']) )
					return $ldap_connection;
			}
		}
		
		return false;
	}
		
	function validate_fields($params)
	{
		/* ldap connection following referals and using Contac Center config*/
		if (is_array($_SESSION['phpgw_info']['expresso']['cc_ldap_server']))
		{
			$ldap_connection = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
			if ($ldap_connection)
			{
				ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
				
				if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
				{
					if ( !@ldap_bind($ldap_connection, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']) )
					{
						$result['status'] = false;
						$result['msg'] = $this->functions->lang('Invalid credentials: cc_ldap_server.') . ".";
						return $result;
					}
				}
				$context = $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['dn'];
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
				return $result;
			}
		}
		else
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
			$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		}
		
		$result['status'] = true;
		
		$params = unserialize($params['attributes']);
		$type = $params['type'];
		$uid = $params['uid'];
		$mail = isset($params['mail'])? $params['mail'] : '';
		$mailalternateaddress = isset($params['mailalternateaddress'])? $params['mailalternateaddress'] : '';
		$cpf = isset($params['cpf'])? $params['cpf'] : '';
		
		if (isset($_SESSION['phpgw_info']['expresso']['global_denied_users'][$uid]))
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('this login can not be used because is a system account') . ".";
			return $result;
		}
		
		if (($type == 'create_user') || ($type == 'rename_user')) 
		{
			if ($this->current_config['expressoAdmin_prefix_org'] == 'true')
			{
				//Obtenho UID sem a organização. Na criação o uid já vem sem a organização
				$tmp_uid_without_org = explode("-", $params['uid']);
				$tmp_reverse_uid_without_org = array_reverse($tmp_uid_without_org);
				array_pop($tmp_reverse_uid_without_org);
				$uid_without_org = implode("-", $tmp_reverse_uid_without_org);
				$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|(uid=$uid)(uid=$uid_without_org)))";
			}
			else
			{
				$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid=$uid))";
			}
			/*
			//UID
			if (($type == 'rename_user') && ($this->current_config['expressoAdmin_prefix_org'] == 'true'))
			{
				//Obtenho UID sem a organização. Na criação o uid já vem sem a organização
				$tmp_uid_without_org = explode("-", $params['uid']);
				$tmp_reverse_uid_without_org = array_reverse($tmp_uid_without_org);
				array_pop($tmp_reverse_uid_without_org);
				$uid_without_org = implode("-", $tmp_reverse_uid_without_org);
				$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|(uid=$uid)(uid=$uid_without_org)))";
			}
			else
			{
				$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid=$uid))";
			}
			*/
			
			$justthese = array("uid", "mail", "cn");
			$search = ldap_search($ldap_connection, $context, $filter, $justthese);
			$count_entries = ldap_count_entries($ldap_connection,$search);
			if ($count_entries > 0)
			{
				$entries = ldap_get_entries($ldap_connection, $search);
				
				for ($i=0; $i<$entries['count']; $i++)
				{
					$users .= $entries[$i]['cn'][0] . ' - ' . $entries[$i]['mail'][0] . "\n";
				}
				
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('this login is already used by') . ":\n" . $users;
				return $result;
			}

			// GRUPOS
			$filter = "(&(phpgwAccountType=g)(cn=$uid))";
			$justthese = array("cn");
			$search = ldap_search($ldap_connection, $context, $filter, $justthese);
			$count_entries = ldap_count_entries($ldap_connection,$search);
			if ($count_entries > 0)
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('This login is being used by a group') . ".";
				return $result;
			}
			
			
			// UID em outras organizações, pesquiso apenas na maquina local e se utilizar prefix_org
			if ($this->current_config['expressoAdmin_prefix_org'] == 'true')
			{
				$ldap_connection2 = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
				ldap_set_option($ldap_connection2,LDAP_OPT_PROTOCOL_VERSION,3);
				ldap_set_option($ldap_connection2, LDAP_OPT_REFERRALS, false);
				ldap_bind($ldap_connection2, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
				$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
				
				//Obtenho UID sem a organização
				/*
				$tmp_uid_without_org = explode("-", $params['uid']);
				if (count($tmp_uid_without_org) < 2)
				{
					$result['status'] = false;
					$result['msg'] = 'Novo login sem organização.';
					return $result;
				}
				$tmp_reverse_uid_without_org = array_reverse($tmp_uid_without_org);
				array_pop($tmp_reverse_uid_without_org);
				$uid_without_org = implode("-", $tmp_reverse_uid_without_org);
				*/
				
				$filter = "(ou=*)";
				$justthese = array("ou");
				$search = ldap_list($ldap_connection2, $context, $filter, $justthese);
				$entries = ldap_get_entries($ldap_connection2	,$search);
				
				foreach ($entries as $index=>$org)
				{
					$organization = $org['ou'][0];
					$organization = strtolower($organization);
				
					$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid=$organization-$uid))";
					
					$justthese = array("uid");
					$search = ldap_search($ldap_connection2, $context, $filter, $justthese);
					$count_entries = ldap_count_entries($ldap_connection2,$search);
					if ($count_entries > 0)
					{
						$result['status'] = false;
						$result['msg'] = $this->functions->lang('this login is already used by a user in another organization') . ".";
						ldap_close($ldap_connection2);
						return $result;
					}
				}
				ldap_close($ldap_connection2);
			}
		}
		
		if ($type == 'rename_user')
		{
			return $result;
		}
		
		// MAIL
		if (!empty($mail)) {
			$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|(mail=$mail)(mailalternateaddress=$mail)))";
			$justthese = array("mail", "uid");
			$search = ldap_search($ldap_connection, $context, $filter, $justthese);
			$entries = ldap_get_entries($ldap_connection,$search);
			if ($entries['count'] == 1){
				if ($entries[0]['uid'][0] != $uid){
					$result['status'] = false;
					$result['msg'] = $this->functions->lang('this email address is being used by 1 user') . ": " . $entries[0]['uid'][0];
					return $result;
				}
			}
			else if ($entries['count'] > 1){
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('this email address is being used by 2 or more users') . ".";
				return $result;
			}
		} else {
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Email field is empty') . ".";
			return $result;
		}
		
		// MAILAlternateAddress
		if (!empty($mailalternateaddress)) {
			foreach ( $mailalternateaddress as $value ) {
				$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|(mail=$value)(mailalternateaddress=$value)))";
				$justthese = array("mail", "uid");
				$search = ldap_search($ldap_connection, $context, $filter, $justthese);
				$entries = ldap_get_entries($ldap_connection,$search);
				if ($entries['count'] == 1){
					if ($entries[0]['uid'][0] != $uid){
						$result['status'] = false;
						$result['msg'] = $this->functions->lang('alternative email is being used by 1 user') . ": " . $entries[0]['uid'][0];
						return $result;
					}
				}
				else if ($entries['count'] > 1){
					$result['status'] = false;
					$result['msg'] = $this->functions->lang('alternative email is being used by 2 or more users') . ".";
					return $result;
				}
			}
		}
		
		//Begin: Check CPF, only if the manager has access to this field.
		if ($this->functions->check_acl($_SESSION['phpgw_session']['session_lid'], 'manipulate_corporative_information'))
		{
			if (!empty($cpf))
			{
				if (!$this->functions->checkCPF($cpf))
				{
					$result['status'] = false;
					$result['msg'] = $this->functions->lang('Field CPF is invalid') . '.';
					return $result;
				}
				else
				{
					//retira caracteres que não são números.
					$cpf = preg_replace("/[^0-9]/", "", $cpf);
				
					$local_ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
					if ($ldap_connection)
					{
						ldap_set_option($local_ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
						ldap_set_option($local_ldap_connection, LDAP_OPT_REFERRALS, false);
						ldap_bind($local_ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
					}
					else
					{
						$result['status'] = false;
						$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
						return $result;
					}
				
					$filter = "(&(phpgwAccountType=u)(cpf=$cpf))";
					$justthese = array("cn","uid");
					$search = ldap_search($local_ldap_connection, $context, $filter, $justthese);
					$entries = ldap_get_entries($local_ldap_connection,$search);
				
					if ( ($entries['count'] != 1) && (strcasecmp($uid, $entries[0]['uid'][0]) != 0) )
					{
						if ($entries['count'] > 0)
						{
							$result['question'] = $this->functions->lang('Field CPF used by') . ":\n";
							for ($i=0; $i<$entries['count']; $i++)
							{
								if (strcasecmp($uid, $entries[$i]['uid'][0]) != 0)
									$result['question'] .= "- " . $entries[$i]['cn'][0] . "\n";
							}
							$result['question'] .= $this->functions->lang("Do you want to continue anyway") . "?";
							return $result;
						}
					}
					ldap_close($local_ldap_connection);
				}
			}
			else if (isset($this->current_config['expressoAdmin_cpf_obligation']) && $this->current_config['expressoAdmin_cpf_obligation'])
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Field CPF must be completed') . '.';
				return $result;
			}
		}
		//End: Check CPF

		return $result;
	}
	
	function validate_fields_group2( $params )
	{
		$params = unserialize($params['attributes']);
		
		$cn         = $params['cn'];
		$mail       = $params['email'];
		$type       = $params['type'];
		$grp_type   = isset( $params['grp_of_names'] );
		$gidnumber  = $params['gidnumber'];
		$in_context = $params['context'];
		
		/* ldap connection following referals and using Contac Center config*/
		if (is_array($_SESSION['phpgw_info']['expresso']['cc_ldap_server']))
		{
			$ldap_connection = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
			if ($ldap_connection)
			{
				ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
				if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
				{
					if (!ldap_bind($ldap_connection, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']))
					{
						$result['status'] = false;
						$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
						return $result;
					}
				}
				$context = $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['dn'];
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
				return $result;
			}
		}
		else
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
			$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		}
		
		if ( $type == 'create_group' ) {
			
			if ( $grp_type ) $search = ldap_list( $ldap_connection, $in_context, '(objectclass='.$cn.')', array() );
			else {
				// CN & UID & MAIL
				$filter = '(|'.
					'(&(phpgwAccountType=g)(cn='.$cn.'))'.
					'(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid='.$cn.'))'.
					(($mail != '')? '(mail='.$mail.')' : '').
				')';
				
				$search = ldap_search( $ldap_connection, $context, $filter, array() );
			}
			if ( !is_resource( $search ) )
				return array( 'status' => false, 'msg' => $this->functions->lang( 'It was not possible to determine if the name or email is in use' ).'.' );
			
			if ( ldap_count_entries( $ldap_connection, $search ) > 0 )
				return array( 'status' => false, 'msg' => $this->functions->lang( 'This group name or group mail is already used' ).'.' );
		}
		else if ($type == 'edit_group')
		{
			$mail_filter = "";
			$cn_filter = "";
			
			$local_ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($local_ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($local_ldap_connection, LDAP_OPT_REFERRALS, false);
			ldap_bind($local_ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
			
			$filter = "(&(phpgwAccountType=g)(gidnumber=$gidnumber))";
			$justthese = array("cn","mail");
			$search = ldap_search($local_ldap_connection, $context, $filter, $justthese);
			$entrie = ldap_get_entries($local_ldap_connection, $search);
			ldap_close($local_ldap_connection);
			
			$old_group_cn 	= $entrie[0]['cn'][0];
			$old_group_mail = $entrie[0]['mail'][0];
			
			if ($old_group_mail != $mail)
			{
				$mail_filter = "(mail=$mail)";
			}
			if ($old_group_cn != $cn)
			{
				$cn_filter = "(| (&(phpgwAccountType=g)(cn=$cn)) (&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid=$cn)) )";
			}
			
			if (($mail_filter != '') && ($cn_filter != '')) 
			{
				$filter = "(|$cn_filter $mail_filter)";
			}
			else if ($mail_filter != '')
			{
				$filter = $mail_filter;
			}
			else if ($cn_filter != '')
			{
				$filter = $cn_filter;
			}
			else return array( 'status' => true );
			
			$justthese = array("1.1");
			$search = ldap_search($ldap_connection, $context, $filter, $justthese);
			$count_entries = ldap_count_entries($ldap_connection,$search);
			if ($count_entries > 0)
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('This group name or group mail is already used') . ".";
				return $result;
			}
		}
		return array( 'status' => true );
	}
	
	function validate_fields_group($params)
	{
		/* ldap connection following referals and using Contac Center config*/
		if (is_array($_SESSION['phpgw_info']['expresso']['cc_ldap_server']))
		{
			$ldap_connection = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
			if ($ldap_connection)
			{
				ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
				if ( ($GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
					ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['acc'], $GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['pw']);
				$context = $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['dn'];
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
				return $result;
			}
		}
		else
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
			$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		}

		$cn = $params['cn'];
		$result['status'] = true;
		
		if ($_SESSION['phpgw_info']['expresso']['global_denied_groups'][$cn])
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('This group name can not be used because is a System Account') . ".";
			return $result;
		}
		
		// CN
		$filter = "(&(phpgwAccountType=g)(cn=$cn))";
		$justthese = array("cn");
		$search = ldap_search($ldap_connection, $context, $filter, $justthese);
		$count_entries = ldap_count_entries($ldap_connection,$search);
		if ($count_entries > 0)
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('This name is already used') . ".";
			return $result;
		}
		
		// UID
		$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uid=$cn))";
		$justthese = array("uid");
		$search = ldap_search($ldap_connection, $context, $filter, $justthese);
		$count_entries = ldap_count_entries($ldap_connection,$search);
		if ($count_entries > 0)
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('This grupo name is already used by an user') . ".";
			return $result;
		}
		
		return $result;	
	}
	
	function validate_fields_maillist($params)
	{
		/* ldap connection following referals and using Contac Center config*/
		if (is_array($_SESSION['phpgw_info']['expresso']['cc_ldap_server']))
		{
			$ldap_connection = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
			if ($ldap_connection)
			{
				ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
				if ( ($GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
					ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['acc'], $GLOBALS['phpgw_info']['expresso']['cc_ldap_server']['pw']);
				$context = $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['dn'];
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Connection with ldap fail') . ".";
				return $result;
			}
		}
		else
		{
			$ldap_connection = ldap_connect($GLOBALS['phpgw_info']['server']['ldap_host']);
			ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, true);
			ldap_bind($ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_root_dn'], $GLOBALS['phpgw_info']['server']['ldap_root_pw']);
			$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		}
		
		$uid = $params['uid'];
		$mail = $params['mail'];
		$result['status'] = true;
		
		if ($_SESSION['phpgw_info']['expresso']['global_denied_users'][$uid])
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('This LOGIN can not be used because is a System Account') . ".";
			return $result;
		}
		
		// UID
		$filter = "(&(phpgwAccountType=l)(uid=$uid))";
		$justthese = array("uid");
		$search = ldap_search($ldap_connection, $context, $filter, $justthese);
		$count_entries = ldap_count_entries($ldap_connection,$search);
		if ($count_entries > 0)
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('this email list login is already used') . ".";
			return $result;
		}
		
		// MAIL
		$filter = "(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|(mail=$mail)(mailalternateaddress=$mail)))";
		$justthese = array("mail");
		$search = ldap_search($ldap_connection, $context, $filter, $justthese);
		$count_entries = ldap_count_entries($ldap_connection,$search);
		if ($count_entries > 0)
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('this email address is already used') . ".";
			return $result;
		}
		
		return $result;	
	}

	//Busca usuários de um contexto e já retorna as options do select;
	function get_available_users($params)
	{
		$context = $params['context'];
		$recursive = $params['recursive'];
		$justthese = array("cn", "uidNumber");
		$filter="(phpgwAccountType=u)";
		
		if ($recursive == 'true') {
			if ( $context === $GLOBALS['phpgw_info']['server']['ldap_context'] ) return '';
			$groups_list=ldap_search($this->ldap, $context, $filter, $justthese);
		} else
    		$groups_list=ldap_list($this->ldap, $context, $filter, $justthese);
    	
    	$entries = ldap_get_entries($this->ldap, $groups_list);
    	
		for ($i=0; $i<$entries["count"]; $i++){
			$u_tmp[$entries[$i]["uidnumber"][0]] = $entries[$i]["cn"][0];
		}
			
		if (count($u_tmp))
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

    	return $options;
	}

	//Busca usuários e listas de um contexto e já retorna as options do select;
	function get_available_users_and_maillist($params)
	{
		$context = $params['context'];
		$recursive = $params['recursive'];
		
		//Usado para retirar a própria lista das possibilidades de inclusão.
		$denied_uidnumber = $params['denied_uidnumber'];
		
		$justthese = array("cn", "uidNumber", "mail");
		
		$users_filter="(phpgwAccountType=u)";
		$lists_filter = $denied_uidnumber == '' ? "(phpgwAccountType=l)" : "(&(phpgwAccountType=l)(!(uidnumber=$denied_uidnumber)))";
		
		$users = Array();
		$lists = Array();		

		/* folling referral connection */
		//$ldap_conn_following_ref = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
		$ldap_conn_following_ref = false;
		if ($ldap_conn_following_ref)
		{
			ldap_set_option($ldap_conn_following_ref, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_conn_following_ref, LDAP_OPT_REFERRALS, 1);

			if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
				ldap_bind($ldap_conn_following_ref, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']);
		}
		else
		{
			$ldap_conn_following_ref = $this->ldap;
		}

		if ($recursive == 'true')
		{
			if ( $context === $GLOBALS['phpgw_info']['server']['ldap_context'] ) return '';
			$lists_search = ldap_search($ldap_conn_following_ref, $context, $lists_filter, $justthese);
			$users_search = ldap_search($ldap_conn_following_ref, $context, $users_filter, $justthese);
		}
		else
		{
			$lists_search = ldap_list($ldap_conn_following_ref, $context, $lists_filter, $justthese);
			$users_search = ldap_list($ldap_conn_following_ref, $context, $users_filter, $justthese);
		}
		
		/* email lists */
		$lists_entries = ldap_get_entries($ldap_conn_following_ref, $lists_search);
		
		for ($i=0; $i<$lists_entries["count"]; $i++)
		{
			$l_tmp[$lists_entries[$i]["mail"][0]] = $lists_entries[$i]["cn"][0];
		}
			
		if (count($l_tmp))
			natcasesort($l_tmp);
			
		$i = 0;
		$lists = array();
		
		$options .= '<option  value="-1" disabled>------------------------------&nbsp;&nbsp;&nbsp;&nbsp;'.$this->functions->lang('email lists').'&nbsp;&nbsp;&nbsp;&nbsp;------------------------------ </option>'."\n";	
		if (count($l_tmp))
		{
			foreach ($l_tmp as $mail => $cn)
			{
				$options .= "<option value=$mail>$cn</option>";
			}
			unset($l_tmp);
		}
		
		/* users */
		$users_entries = ldap_get_entries($ldap_conn_following_ref, $users_search);
		for ($i=0; $i<$users_entries["count"]; $i++)
		{
			$u_tmp[$users_entries[$i]["mail"][0]] = $users_entries[$i]["cn"][0];
		}
			
		if (count($u_tmp))
			natcasesort($u_tmp);
			
		$i = 0;
		$users = array();
		
		$options .= '<option  value="-1" disabled>-----------------------------&nbsp;&nbsp;&nbsp;&nbsp;'.$this->functions->lang('users').'&nbsp;&nbsp;&nbsp;&nbsp;---------------------------- </option>'."\n";
			
		if (count($u_tmp))
		{
			foreach ($u_tmp as $mail => $cn)
			{
				$options .= "<option value=$mail class='line-above'>$cn</option>";
			}
			unset($u_tmp);
		}
		
		//ldap_close($ldap_conn_following_ref);
   		return $options;
	}

	function get_available_groups($params)
	{
		$context = $params['context'];
		$justthese = array("cn", "gidNumber");
    	$groups_list=ldap_list($this->ldap, $context, ("(phpgwAccountType=g)"), $justthese);
    	ldap_sort($this->ldap, $groups_list, "cn");
    	
    	$entries = ldap_get_entries($this->ldap, $groups_list);
    	    	
		$options = '';
		for ($i=0; $i<$entries['count']; $i++)
		{
			$options .= "<option value=" . $entries[$i]['gidnumber'][0] . ">" . $entries[$i]['cn'][0] . "</option>";
		}
    	
    	return $options;		
	}
	
	function get_available_maillists($params)
	{
		if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
			return false;
		
		$recursive = $params['recursive'];
		$context = $params['context'];
		
		if ($context == $GLOBALS['phpgw_info']['server']['ldap_context'])
			$recursive = false;
		
		$justthese = array("uid","mail","uidNumber");
		if ($recursive === 'true')
			$maillists=ldap_search($ldapMasterConnect, $context, ("(phpgwAccountType=l)"), $justthese);
		else
    		$maillists=ldap_list($ldapMasterConnect, $context, ("(phpgwAccountType=l)"), $justthese);
    	ldap_sort($ldapMasterConnect, $maillists, "uid");
    	
    	$entries = ldap_get_entries($ldapMasterConnect, $maillists);
    	
		$options = '';			
		for ($i=0; $i<$entries['count']; $i++)
		{
			$options .= "<option value=" . $entries[$i]['uid'][0] . ">" . $entries[$i]['uid'][0] . " (" . $entries[$i]['mail'][0] . ")" . "</option>";
		}
    	
    	//ldap_close($ldapMasterConnect);
    	return $options;
	}
	
	function ldap_add_entry($dn, $entry)
	{
		$result = array();
		if (!ldap_add ( $this->ldap, $dn, $entry ))
		{
			$result['status']		= false;
			$result['error_number']	= ldap_errno($this->ldap);
			$result['msg']			= $this->functions->lang('Error on function') . " ldap_functions->ldap_add_entry ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ('.ldap_errno($this->ldap).') '.ldap_error($this->ldap);
		}
		else
			$result['status'] = true;
		
		return $result;
	}
	
	function ldap_save_photo($dn, $pathphoto, $photo_exist=false)
	{
		$fd = fopen($pathphoto, "r");
		$fsize = filesize($pathphoto);
		$jpegStr = fread($fd, $fsize);
		fclose ($fd);
		$attrs['jpegPhoto'] = $jpegStr;
			
		if ($photo_exist)
			$res = @ldap_mod_replace($this->ldap, $dn, $attrs);
		else
			$res = @ldap_mod_add($this->ldap, $dn, $attrs);
			
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->ldap_save_photo ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		
		return $result;
	}
	
	function ldap_remove_photo($dn)
	{
		$attrs['jpegPhoto'] = array();
		$res = ldap_mod_del($this->ldap, $dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->ldap_remove_photo ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		
		return $result;
	}	
	
	// Pode receber tanto um único memberUid quanto um array de memberUid's
	function add_user2group($gidNumber, $memberUid)
	{
		$filter = "(&(phpgwAccountType=g)(gidNumber=$gidNumber))";
		$justthese = array("dn");
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($this->ldap, $search);
		$group_dn = $entry[0]['dn'];
		$attrs['memberUid'] = $memberUid;
		
		$res = @ldap_mod_add($this->ldap, $group_dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
			$result['group_dn'] = $group_dn;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->add_user2group ($memberUid -> $group_dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		return $result;
	}
	
	function remove_user2group($gidNumber, $memberUid)
	{
		$filter = "(&(phpgwAccountType=g)(gidNumber=$gidNumber))";
		$justthese = array("dn");
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($this->ldap, $search);
		$group_dn = $entry[0]['dn'];
		$attrs['memberUid'] = $memberUid;
		$res = @ldap_mod_del($this->ldap, $group_dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
			$result['group_dn'] = $group_dn;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->remove_user2group ($memberUid -> $group_dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		return $result;
	}
	
	function add_user2maillist($uid, $mail)
	{
		if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Ldap connection fail') . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
			return $result;
		}
			
		$filter = "(&(phpgwAccountType=l)(uid=$uid))";
		$justthese = array("dn");
		$search = ldap_search($ldapMasterConnect, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($ldapMasterConnect, $search);
		$group_dn = $entry[0]['dn'];
		$attrs['mailForwardingAddress'] = $mail;
		$res = @ldap_mod_add($ldapMasterConnect, $group_dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			if (ldap_errno($ldapMasterConnect) == '50')
			{
				$result['msg'] =	$this->functions->lang('Error on the function') . ' ldap_functions->add_user2maillist' . ".\n" .
									$this->functions->lang('The user used for record on LDAP, must have write access') . ".\n";
									$this->functions->lang('The user') . ' ' . $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] . ' ' . $this->functions->lang('does not have this access') . ".\n";
									$this->functions->lang('Edit Global Catalog Config, in the admin module, and add an user with write access') . ".\n";
			}					 
			else
				$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->add_user2maillist ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
		}
		
		//ldap_close($ldapMasterConnect);
		return $result;
	}
	
	function add_user2maillist_scl($dn, $array_emails)
	{
		$attrs['mailSenderAddress'] = $array_emails;
		
		$res = @ldap_mod_add($this->ldap, $dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->add_user2maillist_scp ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		return $result;
	}

	function remove_user2maillist($uid, $mail)
	{
		if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Ldap connection fail') . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
			return $result;
		}
		
		$filter = "(&(phpgwAccountType=l)(uid=$uid))";
		$justthese = array("dn");
		$search = ldap_search($ldapMasterConnect, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($ldapMasterConnect, $search);
		$group_dn = $entry[0]['dn'];
		$attrs['mailForwardingAddress'] = $mail;
		$res = @ldap_mod_del($ldapMasterConnect, $group_dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			if (ldap_errno($ldapMasterConnect) == '50')
			{
				$result['msg'] =	$this->functions->lang('Error on the function') . ' ldap_functions->remove_user2maillist' . ".\n" .
									$this->functions->lang('The user used for record on LDAP, must have write access') . ".\n";
									$this->functions->lang('The user') . ' ' . $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] . ' ' . $this->functions->lang('does not have this access') . ".\n";
									$this->functions->lang('Edit Global Catalog Config, in the admin module, and add an user with write access') . ".\n";
			}					 
			else
				$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->remove_user2maillist ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
		}
		//ldap_close($ldapMasterConnect);
		return $result;
	}

	function remove_user2maillist_scl($dn, $array_emails)
	{
		$attrs['mailSenderAddress'] = $array_emails;
		$res = @ldap_mod_del($this->ldap, $dn, $attrs);
		
		if ($res)
		{
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->remove_user2maillist_scp ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		return $result;
	}

	function replace_user2maillists($new_mail, $old_mail)
	{
		$filter = "(&(phpgwAccountType=l)(mailforwardingaddress=$old_mail))";
		$justthese = array("dn");
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entries = ldap_get_entries($this->ldap, $search);
		$result['status'] = true;
		for ($i=0; $i<$entries['count']; $i++)
		{
			$attrs['mailforwardingaddress'] = $old_mail;
			$res1 = @ldap_mod_del($this->ldap, $entries[$i]['dn'], $attrs);
			$attrs['mailforwardingaddress'] = $new_mail;
			$res2 = @ldap_mod_add($this->ldap, $entries[$i]['dn'], $attrs);
		
			if ((!$res1) || (!$res2))
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Error on function') . " ldap_functions->replace_user2maillists ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
			}
		}
		
		return $result;
	}
	
	function get_user_info($uidnumber)
	{
		foreach ($this->manager_contexts as $index=>$context)
		{
			$filter="(&(phpgwAccountType=u)(uidNumber=".$uidnumber."))";
			$search = ldap_search($this->ldap, $context, $filter);
			$entry = ldap_get_entries($this->ldap, $search);
			
			if ($entry['count'])
			{
				//Pega o dn do setor do usuario.
				$entry[0]['dn'] = strtolower($entry[0]['dn']);
				$sector_dn_array = explode(",", $entry[0]['dn']);
				$sector_dn = '';
				for($i=1; $i<count($sector_dn_array); $i++)
					$sector_dn .= $sector_dn_array[$i] . ',';
				//Retira ultimo pipe.
				$sector_dn = substr($sector_dn,0,(strlen($sector_dn) - 1));
		
				$result['context']				= $sector_dn;
				$result['uid']					= isset($entry[0]['uid'][0])? $entry[0]['uid'][0] : '';
				$result['uidnumber']			= isset($entry[0]['uidnumber'][0])? $entry[0]['uidnumber'][0] : '';
				$result['gidnumber']			= isset($entry[0]['gidnumber'][0])? $entry[0]['gidnumber'][0] : '';
				$result['departmentnumber']		= isset($entry[0]['departmentnumber'][0])? $entry[0]['departmentnumber'][0] : '';
				$result['givenname']			= isset($entry[0]['givenname'][0])? $entry[0]['givenname'][0] : '';
				$result['sn']					= isset($entry[0]['sn'][0])? $entry[0]['sn'][0] : '';
				$result['birthdate']			= isset($entry[0]['birthdate'][0])? $entry[0]['birthdate'][0] : '';
				$result['telephonenumber']		= isset($entry[0]['telephonenumber'][0])? $entry[0]['telephonenumber'][0] : '';
				$result['passwd_expired']		= isset($entry[0]['phpgwlastpasswdchange'][0])? (int)$entry[0]['phpgwlastpasswdchange'][0] : 1;
				$result['phpgwaccountstatus']	= isset($entry[0]['phpgwaccountstatus'][0])? $entry[0]['phpgwaccountstatus'][0] : '';
				$result['phpgwaccountvisible']	= isset($entry[0]['phpgwaccountvisible'][0])? $entry[0]['phpgwaccountvisible'][0] : '';
				$result['accountstatus']		= isset($entry[0]['accountstatus'][0])? $entry[0]['accountstatus'][0] : '';
				$result['mail']					= isset($entry[0]['mail'][0])? $entry[0]['mail'][0] : '';
				$result['mailalternateaddress']	= isset($entry[0]['mailalternateaddress'])? $entry[0]['mailalternateaddress'] : '';
				$result['mailforwardingaddress']= isset($entry[0]['mailforwardingaddress'])? $entry[0]['mailforwardingaddress'] : '';
				$result['deliverymode']			= isset($entry[0]['deliverymode'][0])? $entry[0]['deliverymode'][0] : '';
				$result['userPasswordRFC2617']	= isset($entry[0]['userpasswordrfc2617'][0])? $entry[0]['userpasswordrfc2617'][0] : '';

				//Photo
				if (isset($entry[0]['jpegphoto']['count']) && $entry[0]['jpegphoto']['count'] == 1)
					$result['photo_exist'] = 'true';
				
				// Samba - Radius
				$raduis_config = $this->getRadiusConf();
				for ($i=0; $i<$entry[0]['objectclass']['count']; $i++)
				{
					switch ( $entry[0]['objectclass'][$i] )
					{
						case 'sambaSamAccount' : $result['sambaUser'] = true; break;
						case $raduis_config->profileClass : $result[$raduis_config->profileClass] = true; break;
					}
				}
				
				if (($this->current_config['expressoAdmin_samba_support'] == 'true'))
				{
					if (isset($result['sambaUser']) && $result['sambaUser'])
					{
						$result['sambaaccflags'] = $entry[0]['sambaacctflags'][0];
						$result['sambalogonscript'] = $entry[0]['sambalogonscript'][0];
						$a_tmp = explode("-", $entry[0]['sambasid'][0]);
						array_pop($a_tmp);
						$result['sambasid'] = implode("-", $a_tmp);
					}

					$result['loginshell'] = $entry[0]['loginshell'][0];
					$result['homedirectory'] = $entry[0]['homedirectory'][0];
				}
				
				if ( $raduis_config->enabled && isset( $result[$raduis_config->profileClass] ) && $result[$raduis_config->profileClass] )
				{
					$result[$raduis_config->groupname_attribute] = array();
					for ( $i = 0; $i < $entry[0][strtolower($raduis_config->groupname_attribute)]['count']; $i++ )
						$result[$raduis_config->groupname_attribute][] = $entry[0][strtolower($raduis_config->groupname_attribute)][$i];
				}

				// Verifica o acesso do gerente aos atributos corporativos
				if ($this->functions->check_acl($_SESSION['phpgw_session']['session_lid'], 'manipulate_corporative_information'))
				{
					$result['corporative_information_employeenumber']	= isset($entry[0]['employeenumber'][0])? $entry[0]['employeenumber'][0] : '';
					$result['corporative_information_cpf']				= isset($entry[0]['cpf'][0])? $entry[0]['cpf'][0] : '';
					$result['corporative_information_rg']				= isset($entry[0]['rg'][0])? $entry[0]['rg'][0] : '';
					$result['corporative_information_rguf']				= isset($entry[0]['rguf'][0])? $entry[0]['rguf'][0] : '';
					$result['corporative_information_description']		= isset($entry[0]['description'][0])? utf8_decode($entry[0]['description'][0]) : '';
				}
				
				// MailLists
				$result['maillists_info'] = $this->get_user_maillists($result['mail']);
				if($result['maillists_info'])
				{
					foreach ($result['maillists_info'] as $maillist)
					{
						$result['maillists'][] = $maillist['uid'];
					}
				}
				
				// Groups
				$justthese = array("gidnumber","cn");
				$filter="(&(phpgwAccountType=g)(memberuid=".$result['uid']."))";
				$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
    			ldap_sort($this->ldap, $search, "cn");
    			$entries = ldap_get_entries($this->ldap, $search);
    			for ($i=0; $i<$entries['count']; $i++)
	    		{
    				$result['groups_ldap'][ $entries[$i]['gidnumber'][0] ] = $entries[$i]['cn'][0];
    			}
			}
		}
		if (is_array($result))
			return $result;
		else
			return false;
	}
		
	function get_user_maillists($mail)
	{
		if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
			return false;
		
		$return = array();
		$result = array();
		$a_tmp = array();
		
		//Mostra somente os mailists dos contextos do gerente
		$justthese = array("uid","mail","uidnumber");
		$filter="(&(phpgwAccountType=l)(mailforwardingaddress=$mail))";
		
		foreach ($this->manager_contexts as $index=>$context)
		{
			$search = ldap_search($ldapMasterConnect, $context, $filter, $justthese);
    		$entries = ldap_get_entries($ldapMasterConnect, $search);
    		
	    	for ($i=0; $i<$entries['count']; $i++)
    		{
				$result[ $entries[$i]['uid'][0] ]['uid']		= $entries[$i]['uid'][0];
				$result[ $entries[$i]['uid'][0] ]['mail']		= $entries[$i]['mail'][0];
				
				$a_tmp[] = $entries[$i]['uid'][0];
    		}
		}
    	
    	if(count($a_tmp)) {
    		natcasesort($a_tmp);
    	
    		foreach ($a_tmp as $uid)
    		{
				$return[$uid]['uid']		= $result[$uid]['uid'];
				$return[$uid]['mail']		= $result[$uid]['mail'];
    		}
    	}
    	
		return $return;
	}
	
	function get_object( $base_dn, $attributes = array() )
	{
		foreach ( $this->manager_contexts as $context )
			if ( preg_match( '/'.preg_quote( strtolower( $context ) ).'$/', strtolower( $base_dn ) ) )
				return ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $base_dn, '(objectClass=*)', $attributes ) );
		return false;
	}
	
	function get_group_type( $dn )
	{
		$result = $this->get_object( $dn, array( 'objectclass', 'gidnumber' ) );
		if ( !is_array( $result[0]['objectclass'] ) ) return false;
		if ( in_array( 'posixGroup', $result[0]['objectclass'] ) ) return array( 'type' => 0, 'gidnumber' => $result[0]['gidnumber'][0] );
		if ( in_array( 'groupOfNames', $result[0]['objectclass'] ) ) return array( 'type' => 1 );
		if ( in_array( 'groupOfUniqueNames', $result[0]['objectclass'] ) ) return array( 'type' => 2 );
		return false;
	}
	
	function get_group_members( $members, $attr = 'uid' ) {
		$result = array();
		unset( $members['count'] );
		while ( count( $members ) ) {
			$filter = '(&(phpgwAccountType=u)(|('.$attr.'='.implode( ')('.$attr.'=', array_splice( $members, 0, 10 ) ).')))';
			$user_entry = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array( 'cn', 'uid', 'uidnumber' ) ) );
			unset( $user_entry['count'] );
			foreach ( $user_entry as $entry ) $result[$entry['uid'][0]] = array( 'cn' => $entry['cn'][0], 'uidnumber' => $entry['uidnumber'][0], 'type' => 'u' );
		}
		return $result;
	}
	
	function get_group_info($gidnumber)
	{
		foreach ($this->manager_contexts as $index=>$context)
		{
			$filter="(&(phpgwAccountType=g)(gidNumber=".$gidnumber."))";
			$search = ldap_search($this->ldap, $context, $filter);
			$entry = ldap_get_entries($this->ldap, $search);
			
			if ($entry['count'])
			{
				$result['context']                = implode( ',', array_splice( explode( ',', $entry[0]['dn'] ), 1 ) );
				$result['cn']                     = $entry[0]['cn'][0];
				$result['description']            = utf8_decode($entry[0]['description'][0]);
				$result['gidnumber']              = $entry[0]['gidnumber'][0];
				$result['memberuid_info']         = $this->get_group_members( isset( $entry[0]['memberuid'] )? $entry[0]['memberuid'] : array() );
				$result['memberuid_scm_info']     = $this->get_group_members( isset( $entry[0]['mailsenderaddress'] )? $entry[0]['mailsenderaddress'] : array(), 'mail' );
				if ( isset( $entry[0]['phpgwaccountvisible'][0] )    ) $result['phpgwaccountvisible']    = $entry[0]['phpgwaccountvisible'][0];
				if ( isset( $entry[0]['mail'][0] )                   ) $result['email']                  = $entry[0]['mail'][0];
				if ( isset( $entry[0]['accountrestrictive'][0] )     ) $result['accountrestrictive']     = $entry[0]['accountrestrictive'][0];
				if ( isset( $entry[0]['participantcansendmail'][0] ) ) $result['participantcansendmail'] = $entry[0]['participantcansendmail'][0];
				
				// Checamos e-mails que não fazem parte do expresso.
				// Criamos um array temporario
				$tmp_array = array();
				if($result['memberuid_info'])
					foreach ($result['memberuid_info'] as $uid => $user_data)
					{
						$tmp_array[] = $uid;
					}
		
				if($entry[0]['memberuid']) {
					// Retira o count do array
					array_shift($entry[0]['memberuid']);
					// Vemos a diferença
					$array_diff = array_diff($entry[0]['memberuid'], $tmp_array);
					// Incluimos no resultado			
					foreach ($array_diff as $index=>$uid)
					{
						$result['memberuid_info'][$uid]['cn'] = $uid;
					}
				}
		
				// Samba
				if ( in_array( 'sambaGroupMapping', $entry[0]['objectclass'] ) ) {
					$result['use_attrs_samba'] = true;
					$result['sambasid']        = preg_filter( '/-[^-]*$/', '', $entry[0]['sambasid'][0] );
				}
				
				return $result;
			}
		}
	}	
	
	function get_maillist_info($uidnumber)
	{
		/* folling referral connection */
		//$ldap_conn_following_ref = ldap_connect($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['host']);
		$ldap_conn_following_ref = false;
		if ($ldap_conn_following_ref)
		{
			ldap_set_option($ldap_conn_following_ref, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap_conn_following_ref, LDAP_OPT_REFERRALS, 1);

			if ( ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] != '') && ($_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw'] != '') )
				ldap_bind($ldap_conn_following_ref, $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'], $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['pw']);
		}
		else
		{
			$ldap_conn_following_ref = $this->ldap;
		}
		
		foreach ($this->manager_contexts as $index=>$context)
		{
			$filter="(&(phpgwAccountType=l)(uidNumber=".$uidnumber."))";
			$search = ldap_search($this->ldap, $context, $filter);
			$entry = ldap_get_entries($this->ldap, $search);
			
			if ($entry['count'])
			{
				//Pega o dn do setor do usuario.
				$entry[0]['dn'] = strtolower($entry[0]['dn']);
				$sector_dn_array = explode(",", $entry[0]['dn']);
				for($i=1; $i<count($sector_dn_array); $i++)
					$sector_dn .= $sector_dn_array[$i] . ',';
				//Retira ultimo pipe.
				$sector_dn = substr($sector_dn,0,(strlen($sector_dn) - 1));
			
				$result['context']				= $sector_dn;
				$result['uidnumber']			= $entry[0]['uidnumber'][0];
				$result['uid']					= strtolower($entry[0]['uid'][0]);
				$result['cn']					= $entry[0]['cn'][0];
				$result['mail']					= $entry[0]['mail'][0];
				$result['description']			= utf8_decode($entry[0]['description'][0]);
				$result['accountStatus']		= $entry[0]['accountstatus'][0];
				$result['phpgwAccountVisible']	= $entry[0]['phpgwaccountvisible'][0];
			
				//Members
				$result['mailForwardingAddress'] = array();
				for ($i=0; $i<$entry[0]['mailforwardingaddress']['count']; $i++)
				{
					$justthese = array("cn", "uidnumber", "uid", "phpgwaccounttype", "mail");
				
					// Montagem dinamica do filtro, para nao ter muitas conexoes com o ldap
					$filter="(&(|(phpgwAccountType=u)(phpgwAccountType=l))(|";
					for ($k=0; (($k<10) && ($i<$entry[0]['mailforwardingaddress']['count'])); $k++)
					{
						$filter .= "(|(mail=".$entry[0]['mailforwardingaddress'][$i].")(uid=".$entry[0]['mailforwardingaddress'][$i]."))";
						$i++;
					}
					$i--;
					$filter .= "))";
					
					$search = ldap_search($ldap_conn_following_ref, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
					$user_entry = ldap_get_entries($ldap_conn_following_ref, $search);
									
					for ($j=0; $j<$user_entry['count']; $j++)
					{
						$result['mailForwardingAddress_info'][$user_entry[$j]['mail'][0]]['uid'] = $user_entry[$j]['uid'][0];
						$result['mailForwardingAddress_info'][$user_entry[$j]['mail'][0]]['cn'] = $user_entry[$j]['cn'][0];
						$result['mailForwardingAddress_info'][$user_entry[$j]['mail'][0]]['type'] = $user_entry[$j]['phpgwaccounttype'][0];
						$result['mailForwardingAddress'][] = $user_entry[$j]['mail'][0];
					}
				}

				// Emails não encontrados no ldap
				array_shift($entry[0]['mailforwardingaddress']); //Retira o count do array
				$missing_emails = array_diff($entry[0]['mailforwardingaddress'], $result['mailForwardingAddress']);
				
				// Incluimos estes no resultado
				foreach ($missing_emails as $index=>$mailforwardingaddress)
				{
					// Verifico se não é um uid ao inves de um email
					$filter="(&(phpgwAccountType=u)(uid=$mailforwardingaddress))";
					$search = ldap_search($ldap_conn_following_ref, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
					$count_entries = ldap_count_entries($ldap_conn_following_ref, $search);
					if (!$count_entries)
					{
						$result['mailForwardingAddress_info'][$mailforwardingaddress]['uid'] = $mailforwardingaddress;
						$result['mailForwardingAddress_info'][$mailforwardingaddress]['cn'] = 'E-Mail nao encontrado';
						$result['mailForwardingAddress'][] = $mailforwardingaddress;
					}
				}
				
				//ldap_close($ldap_conn_following_ref);
				return $result;
			}
		}
	}	

	function get_maillist_scl_info($uidnumber)
	{
		foreach ($this->manager_contexts as $index=>$context)
		{
			$filter="(&(phpgwAccountType=l)(uidNumber=$uidnumber))";
			$search = ldap_search($this->ldap, $context, $filter);
			$entry = ldap_get_entries($this->ldap, $search);

			if ($entry['count'])
			{
				//Pega o dn do setor do usuario.
				$entry[0]['dn'] = strtolower($entry[0]['dn']);
				$sector_dn_array = explode(",", $entry[0]['dn']);
				for($i=1; $i<count($sector_dn_array); $i++)
					$sector_dn .= $sector_dn_array[$i] . ',';
				//Retira ultimo pipe.
				$sector_dn = substr($sector_dn,0,(strlen($sector_dn) - 1));
		
				$result['dn']						= $entry[0]['dn'];
				$result['context']					= $sector_dn;
				$result['uidnumber']				= $entry[0]['uidnumber'][0];
				$result['uid']						= $entry[0]['uid'][0];
				$result['cn']						= $entry[0]['cn'][0];
				$result['mail']						= $entry[0]['mail'][0];
				$result['accountStatus']			= $entry[0]['accountstatus'][0];
				$result['phpgwAccountVisible']		= $entry[0]['phpgwaccountvisible'][0];
				$result['accountRestrictive']		= $entry[0]['accountrestrictive'][0];
				$result['participantCanSendMail']	= $entry[0]['participantcansendmail'][0];
		
				//Senders
				for ($i=0; $i<$entry[0]['mailsenderaddress']['count']; $i++)
				{
					$justthese = array("cn", "uidnumber", "uid", "mail");
					$filter="(&(phpgwAccountType=u)(mail=".$entry[0]['mailsenderaddress'][$i]."))";
					$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
					$user_entry = ldap_get_entries($this->ldap, $search);
			
					$result['senders_info'][$user_entry[0]['mail'][0]]['uid'] = $user_entry[0]['uid'][0];
					$result['senders_info'][$user_entry[0]['mail'][0]]['cn'] = $user_entry[0]['cn'][0];
					$result['members'][] = $user_entry[0]['mail'][0];
				}
				return $result;
			}
		}
	}	

	function group_exist($gidnumber)
	{
		$justthese = array("cn");
		$filter="(&(phpgwAccountType=g)(gidNumber=".$gidnumber."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
				
		$entry = ldap_get_entries($this->ldap, $search);
		if ($entry['count'] == 0)
			return false;
		else
			return true;
	}

	function gidnumbers2cn($gidnumbers)
	{
		$result = array();
		if (count($gidnumbers))
		{
			$justthese = array("cn");
			$i = 0;
			foreach ($gidnumbers as $gidnumber)
			{
				$filter="(&(phpgwAccountType=g)(gidNumber=".$gidnumber."))";
				$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
				
				$entry = ldap_get_entries($this->ldap, $search);
				if ($entry['count'] == 0)
					$result['groups_info'][$i]['cn'] = '_' . $this->functions->lang('group only exist on DB, but does not exist on ldap');
					
				else
					$result['groups_info'][$i]['cn'] = $entry[0]['cn'][0];
				$result['groups_info'][$i]['gidnumber'] = $gidnumber;
			
				/* o gerente pode excluir um grupo de um usuario onde este grupo esta em outra OU ? */
				/* é o mesmo que o manager editar um grupo de outra OU */
				$result['groups_info'][$i]['group_disabled'] = 'true';
				foreach ($this->manager_contexts as $index=>$context)
				{
					if (strpos(strtolower($entry[0]['dn']), strtolower($context)))
					{
						$result['groups_info'][$i]['group_disabled'] = 'false';
					}
				}

				$i++;
			}
		}
		return $result;
	}

	function uidnumber2dn($uidnumber)
	{
		$filter ='(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uidNumber='.$uidnumber.'))';
		$search = ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array() );
		$entry  = ldap_get_entries( $this->ldap, $search );
		return $entry[0]['dn'];
	}

	function uidnumber2uid($uidnumber)
	{
		$justthese = array("uid");
		$filter="(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uidNumber=".$uidnumber."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($this->ldap, $search);
		return $entry[0]['uid'][0];
	}

	function uidnumber2mail($uidnumber)
	{
		$justthese = array("mail");
		$filter="(&(|(phpgwAccountType=u)(phpgwAccountType=l))(uidNumber=".$uidnumber."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($this->ldap, $search);
		return $entry[0]['mail'][0];
	}
	
	function change_user_context($old_dn, $newrdn, $newparent)
	{
		if ( !ldap_rename ( $this->ldap, $old_dn, $newrdn, $newparent, true ) )
			return array(
				'status' => false,
				'msg'    => $this->functions->lang('Error on function') . " ldap_functions->change_user_context ($old_dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap),
			);
		
		// Update user from groupOfNames and groupOfUniqueNames
		$new_dn = $newrdn.','.$newparent;
		$params = array(
			'groupOfNames'       => array( 'old_val' => $old_dn,  'new_val' => $new_dn,  'attr' => 'member' ),
			'groupOfUniqueNames' => array( 'old_val' => $old_dn,  'new_val' => $new_dn,  'attr' => 'uniqueMember' ),
		);
		foreach ( $params as $obj => $attrs ) {
			$filter  = '(&(objectclass='.$obj.')('.$attrs['attr'].'='.$attrs['old_val'].'))';
			$entries = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array( 'dn' ) ) );
			for ( $i = 0; $i < $entries['count']; $i++ ) {
				ldap_mod_del( $this->ldap, $entries[$i]['dn'], array( $attrs['attr'] => array( $attrs['old_val'] ) ) );
				ldap_mod_add( $this->ldap, $entries[$i]['dn'], array( $attrs['attr'] => array( $attrs['new_val'] ) ) );
			}
		}
		return array( 'status' => true );
	}
	
	function replace_user_attributes($dn, $ldap_mod_replace)
	{
		if (!@ldap_mod_replace ( $this->ldap, $dn, $ldap_mod_replace ))
		{
			$return['status'] = false;
			$return['error_number'] = ldap_errno($this->ldap);
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->replace_user_attributes ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		else
			$return['status'] = true;
		
		return $return;
	}
	
	function add_user_attributes($dn, $ldap_add)
	{
		if (!@ldap_mod_add ( $this->ldap, $dn, $ldap_add ))
		{
			$return['status'] = false;
			$return['error_number'] = ldap_errno($this->ldap);
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->add_user_attributes ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		else
			$return['status'] = true;
		
		return $return;
	}
	
	function remove_user_attributes($dn, $ldap_remove)
	{
		if (!@ldap_mod_del ( $this->ldap, $dn, $ldap_remove ))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->remove_user_attributes ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		else
			$return['status'] = true;
		
		return $return;
	}
	
	function set_user_password($uid, $password)
	{
		$justthese = array("userPassword");
		$filter="(&(phpgwAccountType=u)(uid=".$uid."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
	    $entry = ldap_get_entries($this->ldap, $search);
		$dn = $entry[0]['dn'];
		$userPassword = $entry[0]['userpassword'][0];
		$ldap_mod_replace['userPassword'] = $password;
		$this->replace_user_attributes($dn, $ldap_mod_replace);
		return $userPassword;
	}
	
	function delete_user($user_info)
	{
		// Verifica acesso do gerente (OU) ao tentar deletar um usuário.
		$manager_access = false;
		foreach ($this->manager_contexts as $index=>$context)
		{
			if ( (strpos(strtolower($user_info['context']), strtolower($context))) || (strtolower($user_info['context']) == strtolower($context)) )
			{
				$manager_access = true;
				break;
			}
		}
		if (!$manager_access)
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have access to delete this user') . ".";
			return $return;
		}
		
		$return['status'] = true;
		$return['msg'] = "";
		
		// Remove user from posixGroup, groupOfNames and groupOfUniqueNames
		$uid = $user_info['uid'];
		$udn = 'uid='.$uid.','.$user_info['context'];
		$params = array(
			'posixGroup'         => array( 'value' => $uid, 'attr' => 'memberUid',    'filter' => '(phpgwAccountType=g)' ),
			'groupOfNames'       => array( 'value' => $udn, 'attr' => 'member',       'filter' => '' ),
			'groupOfUniqueNames' => array( 'value' => $udn, 'attr' => 'uniqueMember', 'filter' => '' ),
		);
		foreach ( $params as $obj => $attrs ) {
			$filter  = '(&(objectclass='.$obj.')'.$attrs['filter'].'('.$attrs['attr'].'='.$attrs['value'].'))';
			$entries = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array( 'dn' ) ) );
			for ( $i = 0; $i < $entries['count']; $i++ ) {
				ldap_mod_del( $this->ldap, $entries[$i]['dn'], array( $attrs['attr'] => array( $attrs['value'] ) ) );
			}
		}
		
		//INSTITUTIONAL ACCOUNTS
		$attrs = array();
		$attrs['mailForwardingAddress'] = $user_info['mail'];
		
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=i)(mailforwardingaddress=".$user_info['mail']."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
	    $entries = ldap_get_entries($this->ldap, $search);
		
		for ($i=0; $i<$entries['count']; $i++)
		{
			if ( !@ldap_mod_del($this->ldap, $entries[$i]['dn'], $attrs) )
			{
				$return['status'] = false;
				$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_user, institutional accounts ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
				return $return;
			}
		}
		
		// MAILLISTS
		$attrs = array();
		$attrs['mailForwardingAddress'] = $user_info['mail'];
		
		if (count($user_info['maillists_info']))
		{
			
			if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
			{
				$return['status'] = false;
				$return['msg'] = $this->functions->lang('Connection with ldap_master fail') . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
				return $return;
			}
			
			foreach ($user_info['maillists_info'] as $maillists_info)
			{
				$uid = $maillists_info['uid'];
				$justthese = array("dn");
				$filter="(&(phpgwAccountType=l)(uid=".$uid."))";
				$search = ldap_search($ldapMasterConnect, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
	    		$entry = ldap_get_entries($ldapMasterConnect, $search);
				$dn = $entry[0]['dn'];
			
				if (!@ldap_mod_del($ldapMasterConnect, $dn, $attrs))
				{
					$return['status'] = false;
					if (ldap_errno($ldapMasterConnect) == '50')
					{
						$return['msg'] =	$this->functions->lang('Error on the function') . ' ldap_functions->add_user2maillist' . ".\n" .
											$this->functions->lang('The user used for record on LDAP, must have write access') . ".\n";
											$this->functions->lang('The user') . ' ' . $_SESSION['phpgw_info']['expresso']['cc_ldap_server']['acc'] . ' ' . $this->functions->lang('does not have this access') . ".\n";
											$this->functions->lang('Edit Global Catalog Config, in the admin module, and add an user with write access') . ".\n";
					}
					else
					{
						$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_user, email lists ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
					}
					return $return;
				}
			}
		}
			
		// UID
		if (!@ldap_delete($this->ldap, $udn))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_user, email lists ($udn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($ldapMasterConnect);
			return $return;
		}
		/* jakjr */
		return $return;
	}
	
	function delete_maillist($uidnumber, $mail)
	{
		$return['status'] = true;
		
		$justthese = array("dn");
		
		// remove listas dentro de listas
		$filter="(&(phpgwAccountType=l)(mailForwardingAddress=".$mail."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entry = ldap_get_entries($this->ldap, $search);
		$attrs['mailForwardingAddress'] = $mail;
		for ($i=0; $i<=$entry['count']; $i++)
	    {
			$dn = $entry[$i]['dn'];
	    	@ldap_mod_del ( $this->ldap, $dn,  $attrs);
	    }
		
		$filter="(&(phpgwAccountType=l)(uidnumber=".$uidnumber."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
   		$entry = ldap_get_entries($this->ldap, $search);
		$dn = $entry[0]['dn'];
		
		if (!@ldap_delete($this->ldap, $dn))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_maillist ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		
		return $return;
	}

	function delete_group($gidnumber)
	{
		$return['status'] = true;
		
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=g)(gidnumber=".$gidnumber."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
   		$entry = ldap_get_entries($this->ldap, $search);
		$dn = $entry[0]['dn'];
		
		if (!@ldap_delete($this->ldap, $dn))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_group ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		
		return $return;
	}

	function delete_groupOfNames( $dn )
	{
		$return = array( 'status' => true );
		if ( ldap_count_entries( $this->ldap, ldap_read( $this->ldap, $dn, '(|(objectclass=groupOfNames)(objectclass=groupOfUniqueNames))', array( 'dn' ) ) ) !== 1 )
			$return = array(
				'status' => false,
				'msg' => $this->functions->lang('Error on function').' ldap_functions->delete_group ('.$dn.').'."\n".$this->functions->lang('group not found'),
			);
		else if ( !ldap_delete( $this->ldap, $dn ) )
			$return = array(
				'status' => false,
				'msg' => $this->functions->lang('Error on function') . " ldap_functions->delete_group ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap),
			);
		return $return;
	}

	function check_access_to_renamed($uid)
	{
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=u)(uid=$uid))";
		
		foreach ($this->manager_contexts as $index=>$context)
		{
			$search = ldap_search($this->ldap, $context, $filter, $justthese);
			$entry = ldap_get_entries($this->ldap, $search);
			if ($entry['count'])
				return true;
		}
	    return false;
	}

	function check_rename_new_uid($uid)
	{
		if ( !$ldapMasterConnect = $this->ldapMasterConnect() )
			return false;
		
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=u)(uid=$uid))";
		
		$search = ldap_search($ldapMasterConnect, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$count_entries = @ldap_count_entries($ldapMasterConnect, $search);
		
		if ($count_entries)
			return false;
			
		return true;
	}
	
	function rename_uid( $old_uid, $new_uid )
	{
		$filter  = '(&(phpgwAccountType=u)(uid='.$old_uid.'))';
		$entry   = ldap_get_entries($this->ldap, ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array( 'dn' ) ) );
		$old_dn  = $entry[0]['dn'];
		$context = preg_replace( '/^[^,]*,/', '', $old_dn );
		$new_dn  = 'uid='.$new_uid.','.$context;
		
		// Rename object
		if ( !ldap_rename( $this->ldap, $old_dn, 'uid='.$new_uid, $context, true ) )
			return array(
				'status' => false,
				'msg'    => $this->functions->lang('Error on function')." ldap_functions->rename_uid ($old_dn)".".\n".$this->functions->lang( 'Server returns' ).': '.ldap_error( $this->ldap ),
			);
		
		// Update user from posixGroup, groupOfNames and groupOfUniqueNames
		$params = array(
			'posixGroup'         => array( 'old_val' => $old_uid, 'new_val' => $new_uid, 'attr' => 'memberUid',    'filter' => '(phpgwAccountType=g)' ),
			'groupOfNames'       => array( 'old_val' => $old_dn,  'new_val' => $new_dn,  'attr' => 'member',       'filter' => '' ),
			'groupOfUniqueNames' => array( 'old_val' => $old_dn,  'new_val' => $new_dn,  'attr' => 'uniqueMember', 'filter' => '' ),
		);
		foreach ( $params as $obj => $attrs ) {
			$filter  = '(&(objectclass='.$obj.')'.$attrs['filter'].'('.$attrs['attr'].'='.$attrs['old_val'].'))';
			$entries = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, array( 'dn' ) ) );
			for ( $i = 0; $i < $entries['count']; $i++ ) {
				ldap_mod_del( $this->ldap, $entries[$i]['dn'], array( $attrs['attr'] => array( $attrs['old_val'] ) ) );
				ldap_mod_add( $this->ldap, $entries[$i]['dn'], array( $attrs['attr'] => array( $attrs['new_val'] ) ) );
			}
		}
		return array( 'status' => true, 'new_dn' => $new_dn );
	}

	function rename_cn($cn, $new_cn)
	{
		$return['status'] = true;
		
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=g)(uid=".$cn."))";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
	    $entry = ldap_get_entries($this->ldap, $search);
		$dn = $entry[0]['dn'];
		
		$explode_dn = ldap_explode_dn($dn, 0);
		$rdn = "cn=" . $new_cn;

		$parent = array();
		for ($j=1; $j<(count($explode_dn)-1); $j++)
			$parent[] = $explode_dn[$j];
		$parent = implode(",", $parent);
		
		$return['new_dn'] = $rdn . ',' . $parent;
			
		if (!@ldap_rename($this->ldap, $dn, $rdn, $parent, false))
		{
			$return['status'] = false;
		}
		
		return $return;
	}
	
	function exist_sambadomains($contexts, $sambaDomainName)
	{
		$justthese = array("dn");
		$filter="(&(objectClass=sambaDomain)(sambaDomainName=$sambaDomainName))";
		
		foreach ($contexts as $index=>$context)
		{
			$search = ldap_search($this->ldap, $context, $filter, $justthese);
		    $entry = ldap_get_entries($this->ldap, $search);
	    
			if ($entry['count'])
				return true;
		}
		return false;
	}
	
	// Primeiro nivel de organização.
	function exist_sambadomains_in_context($params)
	{
		$dn = $GLOBALS['phpgw_info']['server']['ldap_context'];
		$array_dn = ldap_explode_dn ( $dn, 0 );
		
		$context = $params['context'];
		$array_context = ldap_explode_dn ( $context, 0 );
		
		// Pego o setor no caso do contexto ser um sub-setor.
		if (($array_dn['count']+1) < ($array_context['count']))
		{
			// inverto o array_dn para poder retirar o count
			$array_dn_reverse  = array_reverse ( $array_dn, false );
			
			//retiro o count
			array_pop($array_dn_reverse);
			
			//incluo o setor no dn
			array_push ( $array_dn_reverse,  $array_context[ $array_context['count'] - 1 - $array_dn['count']]);
			
			// Volto a ordem natural
			$array_dn  = array_reverse ( $array_dn_reverse, false );
			
			// Implodo
			$context = implode ( ",", $array_dn );
		}
		
		$justthese = array("dn","sambaDomainName");
		$filter="(objectClass=sambaDomain)";
		$search = ldap_list($this->ldap, $context, $filter, $justthese);
	    $entry = ldap_get_entries($this->ldap, $search);
	    
   	    for ($i=0; $i<$entry['count']; $i++)
	    {
			$return['sambaDomains'][$i] = $entry[$i]['sambadomainname'][0];
	    }
	    
		if ($entry['count'])
			$return['status'] = true;
		else
			$return['status'] = false;
			
		return $return;
	}
	function exist_domain_name_sid($sambadomainname, $sambasid)
	{
		$context = $GLOBALS['phpgw_info']['server']['ldap_context'];

		$justthese = array("dn","sambaDomainName");
		$filter="(&(objectClass=sambaDomain)(sambaSID=$sambasid)(sambaDomainName=$sambadomainname))";
		$search = ldap_search($this->ldap, $context, $filter, $justthese);
	    $count_entries = ldap_count_entries($this->ldap, $search);
	    
	    if ($count_entries > 0)
	    	return true;
	    else
	    	return false;
	}
	
	function add_sambadomain($sambadomainname, $sambasid, $context)
	{
		$dn 								= "sambaDomainName=$sambadomainname,$context";
		$entry['sambaSID'] 					= $sambasid;
		$entry['objectClass'] 				= 'sambaDomain';
		$entry['sambaAlgorithmicRidBase']	= '1000';
		$entry['sambaDomainName']			= $sambadomainname;
		
		if (!@ldap_add ( $this->ldap, $dn, $entry ))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->add_sambadomain ($dn)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
		}
		else
			$return['status'] = true;
		
		return $return;
	}
	
	function delete_sambadomain($sambadomainname)
	{
		$return['status'] = true;
		$filter="(sambaDomainName=$sambadomainname)";
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter);
	    $entry = ldap_get_entries($this->ldap, $search);
	 
	 	if ($entry['count'] != 0)
	    {
			$dn = $entry[0]['dn'];
			
			if (!@ldap_delete($this->ldap, $dn))
			{
				$return['status'] = false;
				$return['msg'] = $this->functions->lang('Error on function') . " ldap_functions->delete_sambadomain ($sambadomainname)" . ".\n" . $this->functions->lang('Server returns') . ': ' . ldap_error($this->ldap);
			}
	    }
	    
		return $return;
	}
	
	function search_user($params)
	{
		$search = $params['search'];
		$justthese = array("cn","uid", "mail");
    	$users_list=ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], "(&(phpgwAccountType=u) (|(cn=*$search*)(mail=$search*)) )", $justthese);
    	
    	if (ldap_count_entries($this->ldap, $users_list) == 0)
    	{
    		$return['status'] = 'false';
    		$return['msg'] = $this->functions->lang('Any result was found') . '.';
    		return $return;
    	}
    	
    	ldap_sort($this->ldap, $users_list, "cn");
    	
    	$entries = ldap_get_entries($this->ldap, $users_list);
    	    	
		$options = '';
		for ($i=0; $i<$entries['count']; $i++)
		{
			$options .= "<option value=" . $entries[$i]['uid'][0] . ">" . $entries[$i]['cn'][0] . " (".$entries[$i]['mail'][0].")" . "</option>";
		}
    	
    	return $options;		
	}
	
	function create_institutional_accounts($params)
	{
		/* Begin: Access verification */
		if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], 'add_institutional_accounts'))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have right to create institutional accounts') . ".";
			return $return;
		}
		
		$access_granted = false;
		foreach ($this->manager_contexts as $idx=>$manager_context)
		{
			if (stristr($params['context'], $manager_context))
			{
				$access_granted = true;
				break;
			}
		}
		if (!$access_granted)
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have access to this organization') . ".";
			return $return;
		}
		/* End: Access verification */

		/* Begin: Validation */
		if ( (empty($params['cn'])) || (empty($params['mail'])) )
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Field mail or name is empty');
			return $return;
		}

		if (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$/i", $params['mail']) )
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Field mail is not formed correcty') . '.';
			return $return;
		}

		$uid = 'institutional_account_' . $params['mail'];
		$dn = "uid=$uid," . $params['context'];

		$filter = "(mail=".$params['mail'].")";
		$justthese = array("cn");
		$search = @ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entries = @ldap_get_entries($this->ldap,$search);
		if ($entries['count'] != 0)
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Field mail already in use');
			return $return;
		}
		/* End: Validation */
						
		$info = array();
		$info['cn']					= $params['cn'];
		$info['sn']					= $params['cn'];
		$info['uid']				= $uid;
		$info['mail']				= $params['mail'];
		$info['phpgwAccountType']	= 'i';
		$info['objectClass'][]		= 'inetOrgPerson';
		$info['objectClass'][]		= 'phpgwAccount';
		$info['objectClass'][]		= 'top';
		$info['objectClass'][]		= 'person';
		$info['objectClass'][]		= 'qmailUser';
		$info['objectClass'][]		= 'organizationalPerson';
		
		if ($params['accountStatus'] == 'on')
		{
			$info['accountStatus'] = 'active';
		}
		if ($params['phpgwAccountVisible'] == 'on')
		{
			$info['phpgwAccountVisible'] = '-1';
		}
		
		if (!empty($params['owners']))
		{
			foreach($params['owners'] as $index=>$uidnumber)
			{
				$info['mailForwardingAddress'][] = $this->uidnumber2mail($uidnumber);
			}
		}		
		
		$return = array();
		if (!@ldap_add ( $this->ldap, $dn, $info ))
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Error on function') . ' ldap_functions->create_institutional_accounts';
			$return['msg'] .= "\n" . $this->functions->lang('Server return') . ': ' . ldap_error($this->ldap);
		}
		else
			$return['status'] = true;
		
		return $return;
	}
	
	function save_institutional_accounts($params)
	{
		/* Begin: Access verification */
		if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], 'edit_institutional_accounts'))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have right to edit institutional accounts') . ".";
			return $return;
		}
		$access_granted = false;
		foreach ($this->manager_contexts as $idx=>$manager_context)
		{
			if (stristr($params['context'], $manager_context))
			{
				$access_granted = true;
				break;
			}
		}
		if (!$access_granted)
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have access to this organization') . ".";
			return $return;
		}
		/* End: Access verification */
		
		/* Begin: Validation */
		if ( (empty($params['cn'])) || (empty($params['mail'])) )
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Field mail or name is empty') . '.';
			return $return;
		}

		if (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$/i", $params['mail']) )
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Field mail is not formed correcty') . '.';
			return $return;
		}

		$uid = 'institutional_account_' . $params['mail'];
		$dn = strtolower("uid=$uid," . $params['context']);
		$anchor = strtolower($params['anchor']);

		$filter = "(mail=".$params['mail'].")";
		$justthese = array("cn");
		$search = @ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entries = @ldap_get_entries($this->ldap,$search);
		
		if ( ($entries['count'] > 1) || (($entries['count'] == 1) && ($entries[0]['dn'] != $anchor)) )
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('Field mail already in use.');
			return $return;
		}
		/* End: Validation */
		
		$return = array();
		$return['status'] = true;
		
		if ($anchor != $dn)
		{
			if (!@ldap_rename($this->ldap, $anchor, "uid=$uid", $params['context'], true))
			{
				$return['status'] = false;
				$return['msg']  = $this->functions->lang('Error on function') . ' ldap_functions->save_institutional_accounts: ldap_rename';
				$return['msg'] .= "\n" . $this->functions->lang('Server return') . ': ' . ldap_error($this->ldap);
			}
		}
		
		$info = array();
		$info['cn']					= $params['cn'];
		$info['sn']					= $params['cn'];
		$info['uid']				= $uid;
		$info['mail']				= $params['mail'];
		
		if ($params['accountStatus'] == 'on')
			$info['accountStatus'] = 'active';
		else
			$info['accountStatus'] = array();
		
		if ($params['phpgwAccountVisible'] == 'on')
			$info['phpgwAccountVisible'] = '-1';
		else
			$info['phpgwAccountVisible'] = array();
		
		if ($params['description'] != '')
			$info['description'] = utf8_encode($params['description']);
		else
			$info['description'] = array();
		
		if (!empty($params['owners']))
		{
			foreach($params['owners'] as $index=>$uidnumber)
			{
				$mailForwardingAddress = $this->uidnumber2mail($uidnumber);
				if ($mailForwardingAddress != '')
					$info['mailForwardingAddress'][] = $mailForwardingAddress;
			}
		}
		else
			$info['mailForwardingAddress'] = array();
		
		if (!@ldap_modify ( $this->ldap, $dn, $info ))
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Error on function') . ' ldap_functions->save_institutional_accounts: ldap_modify';
			$return['msg'] .= "\n" . $this->functions->lang('Server return') . ': ' . ldap_error($this->ldap);
		}

		return $return;
	}
	
	function get_institutional_accounts($params)
	{
		if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], 'list_institutional_accounts'))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have right to list institutional accounts') . ".";
			return $return;
		}

		$input = $params['input'];
		$justthese = array("cn", "mail", "uid");
		$trs = array();
				
		foreach ($this->manager_contexts as $idx=>$context)
		{
	    	$institutional_accounts = ldap_search($this->ldap, $context, ("(&(phpgwAccountType=i)(|(mail=$input*)(cn=*$input*)))"), $justthese);
    		$entries = ldap_get_entries($this->ldap, $institutional_accounts);
    	    	
			for ($i=0; $i<$entries['count']; $i++)
			{
				$tr = "<tr class='normal' onMouseOver=this.className='selected' onMouseOut=this.className='normal'><td onClick=edit_institutional_account('".$entries[$i]['uid'][0]."')>" . $entries[$i]['cn'][0] . "</td><td onClick=edit_institutional_account('".$entries[$i]['uid'][0]."')>" . $entries[$i]['mail'][0] . "</td><td align='center' onClick=delete_institutional_accounts('".$entries[$i]['uid'][0]."')><img HEIGHT='16' WIDTH='16' src=./expressoAdmin1_2/templates/default/images/delete.png></td></tr>";
				$trs[$tr] = $entries[$i]['cn'][0];
			}
		}
    	
    	$trs_string = '';
    	if (count($trs))
    	{
    		natcasesort($trs);
    		foreach ($trs as $tr=>$cn)
    		{
    			$trs_string .= $tr;
    		}
    	}
    	
    	$return['status'] = 'true';
    	$return['trs'] = $trs_string;
    	return $return;
	}
	
	function get_institutional_account_data($params)
	{
		if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], 'edit_institutional_accounts'))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have right to list institutional accounts') . ".";
			return $return;
		}
		
		$uid = $params['uid'];
		//$justthese = array("accountStatus", "phpgwAccountVisible", "cn", "mail", "mailForwardingAddress", "description");
				
    	$institutional_accounts = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], ("(&(phpgwAccountType=i)(uid=$uid))"));
    	$entrie = ldap_get_entries($this->ldap, $institutional_accounts);
		
		if ($entrie['count'] != 1)
		{
			$return['status'] = 'false';
			$return['msg'] = $this->functions->lang('Problems loading datas') . '.';
		}
		else
		{
			$tmp_user_context = explode(",", $entrie[0]['dn']);
			$tmp_reverse_user_context = array_reverse($tmp_user_context);
			array_pop($tmp_reverse_user_context);
			$return['user_context'] = implode(",", array_reverse($tmp_reverse_user_context));
			
			$return['status'] = 'true';
			$return['accountStatus']		= $entrie[0]['accountstatus'][0];
			$return['phpgwAccountVisible']	= $entrie[0]['phpgwaccountvisible'][0];
			$return['cn']					= $entrie[0]['cn'][0];
			$return['mail']					= $entrie[0]['mail'][0];
			$return['description']			= utf8_decode($entrie[0]['description'][0]);

			if ($entrie[0]['mailforwardingaddress']['count'] > 0)
			{
				$a_cn = array();
				for ($i=0; $i<$entrie[0]['mailforwardingaddress']['count']; $i++)
				{
					$tmp = $this->mailforwardingaddress2uidnumber($entrie[0]['mailforwardingaddress'][$i]);
					if (!$tmp) {}
					else
						$a_cn[$tmp['uidnumber']] = $tmp['cn'];
				}
				natcasesort($a_cn);
				foreach($a_cn as $uidnumber => $cn)
				{
					$return['owners'] .= '<option value='. $uidnumber .'>' . $cn . '</option>';
				}
			}
		}
		
		return $return;
	}
	
	function mailforwardingaddress2uidnumber($mail)
	{
		$justthese = array("uidnumber","cn");
    	$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], ("(&(phpgwAccountType=u)(mail=$mail))"), $justthese);
    	$entrie = ldap_get_entries($this->ldap, $search);
		if ($entrie['count'] != 1)
			return false;
		else
		{
			$return['uidnumber'] = $entrie[0]['uidnumber'][0];
			$return['cn'] = $entrie[0]['cn'][0];
			return $return;
		}
	}
	
	function delete_institutional_account_data($params)
	{
		if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], 'remove_institutional_accounts'))
		{
			$return['status'] = false;
			$return['msg'] = $this->functions->lang('You do not have right to delete institutional accounts') . ".";
			return $return;
		}

		$uid = $params['uid'];
		$return['status'] = true;
				
		$justthese = array("cn");
    	$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], ("(&(phpgwAccountType=i)(uid=$uid))"), $justthese);
    	$entrie = ldap_get_entries($this->ldap, $search);
		if ($entrie['count'] > 1)
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('More then one uid was found');
			return $return;
		}		
		if ($entrie['count'] == 0)
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('No uid was found');
			return $return;
		}		
		
		$dn = $entrie[0]['dn'];
		if (!@ldap_delete($this->ldap, $dn))
		{
			$return['status'] = false;
			$return['msg']  = $this->functions->lang('Error on function') . " ldap_functions->delete_institutional_accounts: ldap_delete";
			$return['msg'] .= "\n" . $this->functions->lang('Server return') . ': ' . ldap_error($this->ldap);
			return $return;
		}
		
		return $return;
	}
	
	function replace_mail_from_institutional_account($newMail, $oldMail)
	{
		$filter = "(&(phpgwAccountType=i)(mailforwardingaddress=$oldMail))";
		$justthese = array("dn");
		$search = ldap_search($this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
		$entries = ldap_get_entries($this->ldap, $search);
		$return['status'] = true;
		for ($i=0; $i<$entries['count']; $i++)
		{
			$attrs['mailforwardingaddress'] = $oldMail;
			$res1 = @ldap_mod_del($this->ldap, $entries[$i]['dn'], $attrs);
			$attrs['mailforwardingaddress'] = $newMail;
			$res2 = @ldap_mod_add($this->ldap, $entries[$i]['dn'], $attrs);
		
			if ((!$res1) || (!$res2))
			{
				$return['status'] = false;
				$return['msg']  = $this->functions->lang('Error on function') . " ldap_functions->replace_mail_from_institutional_account.";
			}
		}
		
		return $return;
	}
	
	function manager_exist($uid)
	{
		$context = $GLOBALS['phpgw_info']['server']['ldap_context'];

		$justthese = array("uid");
		$filter="(&(phpgwaccounttype=u)(uid=$uid))";
		$search = ldap_search($this->ldap, $context, $filter, $justthese);
	    $count_entries = ldap_count_entries($this->ldap, $search);
	    
	    if ($count_entries > 0)
	    	return true;
	    else
	    	return false;	
	}
	function uid2dn($uid)
	{
		$justthese = array("dn");
		$filter="(&(phpgwAccountType=u)(uid=$uid))";
		foreach ($this->manager_contexts as $index=>$context)
		{
			$search = ldap_search($this->ldap, $context, $filter, $justthese);
			$entry = ldap_get_entries($this->ldap, $search);
			if ($entry['count'])
				return $entry[0]['dn'];
		}
		return false;
	}
	function getUidNumber( $uid )
	{
		foreach ( $this->manager_contexts as $index => $context )
		{
			$search = ldap_search( $this->ldap, $context, '(&(phpgwAccountType=u)(uid='.$uid.'))', array( 'uidnumber' ) );
			$entry  = ldap_get_entries( $this->ldap, $search );
			if ( $entry['count'] ) return $entry[0]['uidnumber'][0];
		}
		return false;
	}
}
?>
