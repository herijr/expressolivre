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
include_once("class.bogroup_access.inc.php");

class uigroup_access
{
	var $public_functions = array(
		'add'      	=> True,
		'index'		=> True,
	);

	var $bo;
	var $template;
	var $template_dir;
	var $html;
	var $theme;

	final function __construct()
	{
		
		$this->bo = new bogroup_access();				
	}

	function index(){
		
		if (!$GLOBALS['phpgw']->acl->check('run',1,'admin'))
		{
		      $GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/admin/index.php'));
		}

		$GLOBALS['phpgw_info']['flags'] = array(
					'currentapp' => 'calendar',
					'nonavbar'   => false,
					'app_header' => lang('Grant Access by Group'),
					'noheader'   => false
					);
		
		$GLOBALS['phpgw']->common->phpgw_header();
		$this->template_dir = 'calendar/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'];
		$this->template = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);		
		if (!is_object($GLOBALS['phpgw']->html)){
			$GLOBALS['phpgw']->html = CreateObject('phpgwapi.html');
		}
		
		$this->html = &$GLOBALS['phpgw']->html;
		$this->theme = $GLOBALS['phpgw_info']['theme'];
				
		$_SESSION['phpgw_info']['server'] = $GLOBALS['phpgw_info']['server'];
		$this->template->set_file(Array('main'	=> 'grant_group_access.tpl'	));	
		$this->template->set_block('main','list','list_t');			
		
		// if ExpressoMail 1.2 has been installed and enabled, show the plugin using AJAX. 
		if($GLOBALS['phpgw_info']['server']['cal_expressoMail']) {							
			$module_name = 'expressoMail'.(str_replace("1.","1_",$GLOBALS['phpgw_info']['server']['cal_expressoMail']));
			if($GLOBALS['phpgw_info']['user']['apps'][$module_name]){								
				$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');
				$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];				
				$_SESSION['phpgw_info']['expressomail']['user']['owner'] = $GLOBALS['phpgw_info']['user']['account_id'];
				$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
				$_SESSION['phpgw_info']['expressomail']['ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
				// Carrega todos scripts necessarios				
				$scripts =	"<script src='".$module_name."/js/connector.js' type='text/javascript'></script>".
							"<script type='text/javascript'>var DEFAULT_URL = '".$module_name."/controller.php?action=';</script> ".											
							"<script src='calendar/js/search.js' type='text/javascript'></script>";				
				// Fim				
				
				$this->template->set_var('lang_Loading',lang("Loading"));
				$this->template->set_var('lang_Searching', lang("Searching"));
				$this->template->set_var('lang_Users', lang("Users"));
				$this->template->set_var('lang_Groups', lang("Groups"));
				$this->template->set_var('template_set', $this->template_dir);
				$this->template->set_var('lang_Event_participants', lang("Event's participants"));
				$this->template->set_var('lang_Add', lang("Add"));
				$this->template->set_var('lang_Remove', lang("Remove"));
				$this->template->set_var('lang_Organization', lang("Organization"));
				$this->template->set_var('lang_Search_for', lang("Search for"));
				$this->template->set_var('lang_Available_users_and_groups', lang("Available users and groups"));
				$this->template->set_var('lang_User_to_grant_access', lang("User to grant access"));
				$this->template->set_var('lang_Group_to_share_calendar', lang("Group to share calendar"));
				$this->template->set_var('scripts',$scripts);
			}
		}	
				
		$this->template->set_var('tr_color',$this->theme['th_bg']);		
		$this->template->set_var('lang_granted_user',lang("Granted user"));			
		$this->template->set_var('lang_shared_group',lang("Shared group"));
		
		$this->template->set_var('lang_rights',lang("Rights"));
		$this->template->set_var('lang_read',lang("Read"));
		$this->template->set_var('lang_add',lang("Add"));
		$this->template->set_var('lang_edit',lang("Edit"));
		$this->template->set_var('lang_delete',lang("Delete"));
		$this->template->set_var('lang_private',lang("Private"));
		
		$this->template->set_var('lang_access_type',lang("Permission"));		
		$this->template->set_var('lang_remove',lang("remove"));
		
		$this->template->set_var('lang_confirm',lang("Do you confirm this action?"));
		$this->template->set_var('lang_success',lang("The sharing was granted."));
		$this->template->set_var('lang_exist',lang("This sharing already exist."));
		$this->template->set_var('lang_nouser',lang("No user was selected"));
		$this->template->set_var('lang_nogroup',lang("No group was selected"));
		$this->template->set_var('lang_nopermissiontype',lang("No permission type was selected"));
		$this->template->set_var('lang_typemoreletters',lang("Type more %1 letters.","X"));		
									
		$grants = $this->get_grants('calendar');
		
		$i = 0;
		$data = array();
		if($grants) {		
			foreach($grants as $key => $acl) {				
				$GLOBALS['phpgw']->accounts->get_account_name($acl['userID'], &$lid, &$fuser, &$luser);
				$GLOBALS['phpgw']->accounts->get_account_name($acl['groupID'], &$lid, &$groupname, &$lname);			
				$rights = $acl['rights'] & PHPGW_ACL_READ ? "L" : "";
				$rights.= $acl['rights'] & PHPGW_ACL_ADD ? "A" : "";
				$rights.= $acl['rights'] & PHPGW_ACL_EDIT ? "E" : "";
				$rights.= $acl['rights'] & PHPGW_ACL_DELETE ? "R" : "";
				$rights.= $acl['rights'] & PHPGW_ACL_PRIVATE ? "P" : "";
			
				$data[$fuser." ".$luser.$i++] = array('granted_user'    => $fuser." ".$luser,
						'shared_group'     => $groupname,
						'access_type'      => $rights,
						'tr_color_header'  => "#DCDCDC",
						'select'   => $key
					);
			}
			ksort($data);
			foreach($data as $key => $var){
				$this->template->set_var($var);			
				$this->template->parse('list_t','list',$var);				
			}
		}		
		
		$this->template->pfp('out', 'main');
	}

	private function get_grants($app){
		return $this->bo->get_grants($app);		
	}
	public function search_user($params){		
		return $this->bo->search_user($params);	
	} 	
	public final function add_user($params) {				
		$rights  = strstr($params['rights'],"L") ? 1 : 0;
		$rights |= strstr($params['rights'],"A") ? 2 : 0;
		$rights |= strstr($params['rights'],"E") ? 4 : 0;
		$rights |= strstr($params['rights'],"R") ? 8 : 0;
		$rights |= strstr($params['rights'],"P") ? 16 : 0;
		$params["id"] .= ";".$rights;
		return $this->bo->add_user($params);
	}
	public final function rem_user($params) {
		return $this->bo->rem_user($params);
	}
}
?>