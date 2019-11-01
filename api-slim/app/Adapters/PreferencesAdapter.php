<?php

namespace App\Adapters;

use App\Adapters\ExpressoAdapter;

class PreferencesAdapter extends ExpressoAdapter
{
	protected function getAclPassword()
	{
		$acl = $GLOBALS['phpgw_info']['user']['acl'];

		$return = 'false';

		foreach ($acl as $value) {
			if (in_array('changepassword', $value)) {
				$return = 'true';
			}
		}

		return $return;
	}

	protected function isPassword($currentPassword)
	{
		// Conf Ldap
		$ldapServer 	= $GLOBALS['phpgw_info']['server']['ldap_host'];
		$ldapContext	= $GLOBALS['phpgw_info']['user']['account_dn'];
		$_return		= false;

		// Connect Ldap
		$connLdap = ldap_connect($ldapServer);

		if ($GLOBALS['phpgw_info']['server']['ldap_version3']) {
			ldap_set_option($connLdap, LDAP_OPT_PROTOCOL_VERSION, 3);
		}

		if ($connLdap) {
			if (ldap_bind($connLdap, $ldapContext, $currentPassword)) {
				$_return = true;
			}

			ldap_close($connLdap);
		}

		return $_return;
	}

	protected function setPassword($newPassword, $currentPassword)
	{
		return $this->updatePasswordLdap($newPassword, $currentPassword);
	}

	private function updatePasswordLdap($newPassword, $currentPassword)
	{
		$auth = CreateObject('phpgwapi.auth_egw');
		$return = false;

		if ($GLOBALS['phpgw_info']['server']['certificado']) {
			return $return;
		} else {
			if ($auth->change_password($currentPassword, $newPassword)) {
				$return = true;
			}
		}

		return $return;
	}

	public function readUserApp()
	{
		$config = parse_ini_file(dirname(__FILE__) . "/../Config/user.ini", true);

		return $config['Preferences.mapping'];
	}

	protected function getDefaultSignature()
	{
		$soemailadmin = CreateObject( 'emailadmin.so' );
		return $soemailadmin->getDefaultSignature( $GLOBALS['phpgw_info']['user']['email'] );
	}
}
