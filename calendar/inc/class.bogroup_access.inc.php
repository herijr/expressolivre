<?php

  /**************************************************************************\
  * Expresso Livre - Grant Group Access - administration                     *
  *															                 *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


class bogroup_access
{
	public final function __construct()
	{
		// Including ExpressoMail Classes
		if (file_exists("../expressoMail1_2/inc/class.db_functions.inc.php")) {
			include_once("../expressoMail1_2/inc/class.db_functions.inc.php");
		}
		if (file_exists("../expressoMail1_2/inc/class.ldap_functions.inc.php")) {
			include_once("../expressoMail1_2/inc/class.ldap_functions.inc.php");
		}
	}

	public final function search_user($params){			
		$objLdap = new ldap_functions();
		$objLdap->ldapConnect();
		$ldap = $objLdap -> ds;		
		$search = $params['search'];
		$accounttype = $params['type'];
		$justthese = array("cn","uid", "uidNumber","gidNumber");
    	$users_list=ldap_search($ldap, $_SESSION['phpgw_info']['expressomail']['server']['ldap_context'], "(&(phpgwAccountType=$accounttype) (|(cn=*$search*)(mail=$search*)) )", $justthese);    	
    	if (ldap_count_entries($ldap, $users_list) == 0)
    	{
    		$return['status'] = 'false';
    		$return['msg'] = 'Nenhum resultado encontrado.';
    		return $return;
    	}    	
    	ldap_sort($ldap, $users_list, "cn");    	
    	$entries = ldap_get_entries($ldap, $users_list);    	    	
		$options = '';
		for ($i=0; $i<$entries['count']; $i++)
		{
			$value = $entries[$i][$accounttype == "u" ? 'uidnumber' : 'gidnumber'][0];		
			if($entries[$i]['mail'][0])
				$mail = "(".$entries[$i]['mail'][0].")";
			$options .= "<option value=" . $value. ">" . $entries[$i]['cn'][0] . " $mail" . "</option>";
		}    	
    	return $options;		
	} 
	
	public function get_grants($app){
		$db2 = $GLOBALS['phpgw']->db;
		$db2->select('phpgw_acl',array('acl_location','acl_account','acl_rights'),"acl_appname='".$app."' AND acl_location <> 'run'",__LINE__,__FILE__);
			
		$grants = array();
		while ($db2->next_record())	{				
			$objectID = $db2->f('acl_account');
			$type = $GLOBALS['phpgw']->accounts->get_type($objectID);				
			if($type == 'g') {
				$userID	= $db2->f('acl_location');
				$rights = $db2->f('acl_rights');				
				$grants[$userID.";".$objectID] = array( "userID"=> $userID,"groupID"=> $objectID, "rights" => $rights);
			}
		}	
		unset($db2);
		return $grants;
	}
	
	public final function add_user($params) {
		
		list($user,$group,$rights) = explode(";",$params['id']);
		$objDB = new db_functions();
		$db = $objDB -> db;
		$db -> select('phpgw_acl','count(*)',array(
						'acl_appname'  => "calendar",
						'acl_location' => $user,
						'acl_account'  => $group),__LINE__,__FILE__);
						
		// Verify if already exists....
		if ($db->next_record() && $db->f(0)) {
			return false; 		
		}	
		
		$where = false;						
		$db -> insert('phpgw_acl',array(
						'acl_appname'  => "calendar",
						'acl_location' => $user,
						'acl_account'  => $group,
						'acl_rights'   => $rights
			), $where, __LINE__,__FILE__);
		
		return true;
	}
	
	public final function rem_user($params){
		
		list($user,$group) = explode(";",$params['id']);
		$objDB = new db_functions();
		$db = $objDB -> db;
		$db -> delete('phpgw_acl',array(
						'acl_appname'  => "calendar",
						'acl_location' => $user,
						'acl_account'  => $group),__LINE__,__FILE__);
		return true;
	}
}
?>
