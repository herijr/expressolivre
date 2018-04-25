<?php

class PreferencesAdapter extends ExpressoAdapter
{
	function __construct($id)
	{
		parent::__construct($id);
	}

	protected function getAclPassword()
	{
		$acl = $GLOBALS['phpgw_info']['user']['acl'];

		$return = 'false';

		foreach( $acl as $value )
		{
			if( in_array('changepassword', $value) ) { $return = 'true' ; }
		}

		return $return;
	}

	protected function isPassword( $currentPassword )
	{
		// Conf Ldap
		$ldapServer 	= $GLOBALS['phpgw_info']['server']['ldap_host'];
		$ldapContext	= $GLOBALS['phpgw_info']['user']['account_dn'];
		$_return		= false;

		// Connect Ldap
		$connLdap = ldap_connect( $ldapServer );
		
		if( $GLOBALS['phpgw_info']['server']['ldap_version3'] ){ ldap_set_option($connLdap, LDAP_OPT_PROTOCOL_VERSION, 3); }

		if( $connLdap )
		{
			if( ldap_bind($connLdap, $ldapContext, $currentPassword) ){ $_return = true; }

			ldap_close($connLdap);
		}

		return $_return;
	}

	protected function setPassword( $newPassword, $currentPassword )
	{
		return $this->updatePasswordLdap( $newPassword, $currentPassword );
	}

	private function updatePasswordLdap( $newPassword, $currentPassword )
	{
		$auth 		= CreateObject('phpgwapi.auth_egw');
		$_return	= "false";

		if( $GLOBALS['phpgw_info']['server']['certificado'] )
		{
			Errors::runException("NO_SUPPORT_FOR_CERTIFICATE");
		}
		else
		{
			if( $auth->change_password($currentpasswd, $newPassword) )
			{
				$_return = "true";
			}
		}

		return $_return;
	}
	
	public function readUserApp()
	{
		$config = parse_ini_file( API_DIRECTORY . '/../config/user.ini',true);
		
		return $config['Preferences.mapping'];
	}
}

?>
