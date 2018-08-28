<?php

namespace App\Services\Base\adapters;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class PreferencesAdapter extends ExpressoAdapter
{
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
			if( @ldap_bind($connLdap, $ldapContext, $currentPassword) ){ $_return = true; }
			
			if( is_resource($connLdap)){ ldap_close($connLdap); }
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
			$_return = array( "error" => Errors::runException("NO_SUPPORT_FOR_CERTIFICATE"));
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
		$config = parse_ini_file( __DIR__ . '/../Config/user.ini',true);
		
		return $config['Preferences.mapping'];
	}
}

?>
