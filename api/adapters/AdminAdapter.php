<?php

class AdminAdapter extends ExpressoAdapter
{
	function __construct($id)
	{
		parent::__construct($id);
	}

	protected function loadConfAdmin()
	{
		$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
		$c->read_repository();

		$current_config = $c->config_data;
		$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');
		$boemailadmin = CreateObject('emailadmin.bo');
		$emailadmin_profile = $boemailadmin->getProfileList();

		$_SESSION['phpgw_info']['expresso']['email_server'] = $boemailadmin->getProfile($emailadmin_profile[0]['profileID']);
		$_SESSION['phpgw_info']['expresso']['user'] = $GLOBALS['phpgw_info']['user'];
		$_SESSION['phpgw_info']['expresso']['server'] = $GLOBALS['phpgw_info']['server'];
		$_SESSION['phpgw_info']['expresso']['cc_ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
		$_SESSION['phpgw_info']['expresso']['expressoAdmin'] = $current_config;
		$_SESSION['phpgw_info']['expresso']['global_denied_users'] = $GLOBALS['phpgw_info']['server']['global_denied_users'];
		$_SESSION['phpgw_info']['expresso']['global_denied_groups'] = $GLOBALS['phpgw_info']['server']['global_denied_groups'];
	}

	protected function createUser($params)
	{
		// Get Profile for Expresso
		$default = $this->getProfile($params['profileUser']);

		if( $default == false )
		{
			return array( "status" => false, "msg" => "Profile Invalid" );
		}

		// Get Groups,Context
		$firstLetter = strtolower(substr( $params['givenname'], 0, 1));

		$isLetter = trim(preg_replace("/[^a-z]/", "", $firstLetter));

		if( !$isLetter )
		{
			return array( "status" => false, "msg" => "Type a letter" );
		}
		else
		{
			$groupFormat = isset( $default['groupFormat'] ) ?  $default['groupFormat'] : 'normal';

			switch ( $groupFormat ) {

				case "letter-separated":
					$groups = $default['groups_user-'.$firstLetter];

					unset( $default['groups_user-'.$firstLetter] );

					foreach( $default as $key => $value )
					{
						if( strpos( $key, "gidnumber-" ) !== false )
						{
							unset( $default[$key] );
						}
					}

					$default['context'] = "ou=".$firstLetter.",".$default['context'];
					break;

				case "normal":
					$groups = $default['groups_user'];

					unset( $default['groups_user'] );
					break;

				default:
					return array( "status" => false, "msg" => "Group format invalid in profile" );
			}

			unset( $default['groupFormat'] );
		}

		// Groups
		if( strpos($groups, ",") !== false )
		{
			$groups_user = explode( "," , $groups );

			$params['groups'] = $groups_user;
			$params['gidnumber'] = $groups_user[0];
		}
		else
		{
			$params['groups'][0] = $groups;
			$params['gidnumber'] = $groups;
		}

		// Merge Conf default + params
		$createUser = array_merge( $params, $default );

		$adminCreateUser = CreateObject('expressoAdmin1_2.user');

		$_return = $adminCreateUser->create($createUser);

		return $_return;
	}

	protected function deleteUser($params)
	{
		$adminDeleteUser = CreateObject('expressoAdmin1_2.user');

		$result = $adminDeleteUser->delete( $params );

		return $result;
	}

	protected function editUser($params)
	{
		$adminEditUser = CreateObject('expressoAdmin1_2.user');

		$user = $adminEditUser->get_user_info($params);

		return $user;
	}

	protected function getUserSearchLdap($params)
	{
		$adminFunctions = CreateObject('expressoAdmin1_2.functions');
		
		$result	= $adminFunctions->get_list('api', $params, array($GLOBALS['phpgw_info']['server']['ldap_context']));
		
		if( isset($result[0]['accountDn']) )
		{
			$dn = $result[0]['accountDn'];
			$homeServer = substr( $dn, ( strrpos($dn, "ou=") + 3 ), strlen($dn) );
			$homeServer = substr( $homeServer, 0, strpos($homeServer,",") );
			$result[0]['accountHomeServer'] = $this->getProfileHomeServer($homeServer);
		}

		return $result;
	}

	protected function listUsersLiteral($params)
	{
		$user = $this->listUsers($params, true);

		return $user;
	}

	protected function listUsers($params, $exactly = false)
	{
		$adminListUser = CreateObject('expressoAdmin1_2.functions');

		$acl = $adminListUser->read_acl($GLOBALS['phpgw']->accounts->data['account_lid']);

		$_exp = ( $exactly ) ? "/[^a-z0-9A-Z\_\-\.\@]/" : "/[^a-z0-9A-Z\_\-\.\@\\s]/";

		$search = trim(preg_replace( $_exp , "", $params));

		$methodType = ($exactly) ? 'uid' : 'accounts';

		$accounts = $adminListUser->get_list( $methodType, $search, $acl['contexts'] );

		return $accounts;
	}

	protected function renameUser($params)
	{
		$adminRenameUser = CreateObject('expressoAdmin1_2.user');

		$result = $adminRenameUser->rename( $params );

		return $result;
	}

	protected function updateUser($params)
	{
		$adminUpdateUser = CreateObject('expressoAdmin1_2.user');

		// Get User Info
		$userInfo = array();
		$userInfo = $this->editUser($params['uidnumber']);

		// Unset
		unset($userInfo['groups_ldap']);
		unset($userInfo['groups_info']);
		unset($userInfo['passwd_expired']);

		foreach( $userInfo as $key => $value )
		{
			if( trim($value) == "" && !is_array($value) )
			{
				unset( $userInfo[$key] );
			}
		}

		// Protected Fields
		$protectedFields = $this->getProfileProtectedFields();

		if( $protectedFields && is_array($protectedFields) )
		{
			$protectedFields['user'] = ( isset($protectedFields['user']) ? trim($protectedFields['user']) : "" );

			if( !empty($protectedFields['user']) )
			{
				if( trim($protectedFields['user']) === trim( $_SESSION['phpgw_info']['expresso']['user']['account_lid']) )
				{
					$fields = rtrim( $protectedFields['fields'], "," );
					
					$fields = preg_replace( "/\s/", "", $fields );
					
					$fields = explode( "," , $fields );

					foreach( $fields as $field )
					{
						if( isset( $params[ $field ] ) )
						{
							$params['protected_fields'] .= $field . ",";
						}
					}
				}
			}
		}

		if( isset($params['protected_fields']) )
		{
			$params['protected_fields'] = rtrim( $params['protected_fields'], "," );
		}

		//Array Merge
		$updateUser = array();
		$updateUser = array_merge( $userInfo, $params );

		$result = $adminUpdateUser->save($updateUser);

		return $result;
	}

	protected function validateFields($params )
	{
		$ldap_functions = CreateObject('expressoAdmin1_2.ldap_functions');

		return $ldap_functions->validate_fields($params);
	}

	protected function validatePermission( $params )
	{
		$action 	= $params['action'];
		$apps		= $params['apps'];
		$manager 	= $_SESSION['phpgw_info']['expresso']['user']['account_lid'];
		$functions  = CreateObject('expressoAdmin1_2.functions');
		$result 	= false;

		if( array_search('admin',$apps) !== False )
		{
			if( $functions->check_acl( $manager, $action ) )
			{
				$result = true;
			}
		}

		return $result;
	}

	private function getProfileProtectedFields()
	{
		$default = false;

		if( file_exists(API_DIRECTORY.'/../config/profileProtectedFields.ini') )
		{
			$default = $this->readProfile('profileProtectedFields.ini', 'protected-fields');
		}

		return $default;
	}

	private function getProfile($profileUser)
	{
		$default = false;

		if( file_exists(API_DIRECTORY.'/../config/profileCreateUser.ini') )
		{
			$default = $this->readProfile('profileCreateUser.ini', $profileUser);
		}

		return $default;
	}

	private function getProfileHomeServer($OU)
	{
		$default	= "";
		$homeServer = false;
		
		if( file_exists( API_DIRECTORY.'/../config/profileHomeServer.ini') )
		{
			$profileHomeServer = $this->readProfile('profileHomeServer.ini','home.server');
			
			if( is_array($profileHomeServer) )
			{
				foreach( $profileHomeServer as $key => $value )
				{
					if( strtoupper($key) === strtoupper($OU) ) { $homeServer = $value; }

					if( strtoupper($key) === "DEFAULT" ) { $default = $value; }	
				}

				if( !$homeServer ){ $homeServer = $default; }
			}
		}

		return $homeServer;
	}

	private function readProfile($profile, $param = false )
	{
		$default	= false;

		$profiles	= parse_ini_file(API_DIRECTORY.'/../config/'.$profile, true);

		foreach( $profiles as $key => $values )
		{
			if( $param != false )
			{
				if( $key == $param )
				{
					$default = $values;
				}
			}
			else
			{
				$default = $values;
			}
		}

		return $default;
	}
}

?>
