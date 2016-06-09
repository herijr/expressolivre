<?php

class ldap_functions
{
	var $conn;
	var $context;
	
	function ldap_functions()
	{
		$common = new common();
		if (
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) &&
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) &&
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw']))
		) {
			$this->conn = $common->ldapConnect(
				$GLOBALS['phpgw_info']['server']['ldap_master_host'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_pw']
			);
		} else $this->conn = $common->ldapConnect();
		$this->context = $GLOBALS['phpgw_info']['server']['ldap_context'];
	}
	
	function listOU()
	{
		$entries = ldap_get_entries( $this->conn, ldap_list( $this->conn, $this->context, '(objectClass=organizationalUnit)', array('ou') ) );
		$result = array();
		for ( $i = 0; $i < $entries['count']; $i++ ) $result[] = $entries[$i]['ou'][0];
		return $result;
	}
	
	function check_user( $uid, $uidnumber )
	{
		return ( ldap_count_entries( $this->conn, ldap_search( $this->conn, $this->context, '(&(uid='.$uid.')(uidNumber='.(int)$uidnumber.'))', array( 'dn' ) ) ) === 1 );
	}
}
?>
