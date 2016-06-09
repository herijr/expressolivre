<?php
/****************************************************************************\
 * Expresso Livre - SMS - administration									*
 * 																			*
 * -------------------------------------------------------------------------*
 * This program is free software; you can redistribute it and/or modify it	*
 * under the terms of the GNU General Public License as published by the	*
 * Free Software Foundation; either version 2 of the License, or (at your	*
 * option) any later version.												*
 \**************************************************************************/
class uisms
{
	var $public_functions = array(
		'add'			=> True,
		'edit_conf'		=> True,
	);

	var $bo;

	final function __construct()
	{
		if(!isset($_SESSION['admin']['ldap_host']))
		{
			$_SESSION['admin']['server']['ldap_host'] = $GLOBALS['phpgw_info']['server']['ldap_host'];
			$_SESSION['admin']['server']['ldap_root_dn'] = $GLOBALS['phpgw_info']['server']['ldap_root_dn'];
			$_SESSION['admin']['server']['ldap_host_pw'] = $GLOBALS['phpgw_info']['server']['ldap_root_pw'];
			$_SESSION['admin']['server']['ldap_context'] = $GLOBALS['phpgw_info']['server']['ldap_context'];
		}
		$this->bo = CreateObject('admin.bosms');
	}

	final function edit_conf()
	{
		if($GLOBALS['phpgw']->acl->check('applications_access',1,'admin'))
		{
			$GLOBALS['phpgw']->redirect_link('/index.php');
		}

		$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Admin') .' - ' . lang('SMS settings');

		if(!@is_object($GLOBALS['phpgw']->js))
		{
			$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
		}
		$GLOBALS['phpgw']->js->add('src','./prototype/plugins/jquery/jquery-latest.min.js');
		$GLOBALS['phpgw']->js->add('src','./prototype/plugins/jquery/jquery-ui-latest.min.js');
		$GLOBALS['phpgw']->css->validate_file('prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css');

		$webserver_url = $GLOBALS['phpgw_info']['server']['webserver_url'];
		$webserver_url = ( !empty($webserver_url) ) ? $webserver_url : '/';

		if(strrpos($webserver_url,'/') === false || strrpos($webserver_url,'/') != (strlen($webserver_url)-1))
			$webserver_url .= '/';

		$js = array('connector','xtools','functions');
		
		foreach( $js as $tmp )
			$GLOBALS['phpgw']->js->validate_file('ldap',$tmp,'admin');

		$GLOBALS['phpgw']->common->phpgw_header();
		echo parse_navbar();
		echo '<script type="text/javascript">var path_adm="'.$webserver_url .'"</script>';

		$ous = "<option value='-1'>-- ".lang('Select Organization')." --</option>";
		if( ($LdapOus = $this->bo->getOuLdap()) )
		{
			foreach($LdapOus as $tmp )
				$ous .= "<option value='".$tmp."'>".$tmp."</option>";
		}

		$groups_sms = $GLOBALS['phpgw_info']['server']['sms_groups'];

		$lang_user = lang('user');
		$lang_passwd = lang('password');

		$grps_sortable = '';
		if( $groups_sms )
		{
			$gsms = explode(',', $groups_sms);
			natcasesort($gsms);

			$grps_sort_arr = array('ord' => array(), 'name' => array());
			foreach( $gsms as $tmp ){
				$option = explode(";",$tmp);
				$gsms .= "<option value='".$tmp."'>".$option[0]."</option>";
				
				$grp_pref = new preferences($option[1]);
				$grp_pref->read_repository();
				$grps_sort_arr[(isset($grp_pref->user['security']['sms']['priority'])?'ord':'name')][] = array(
					'id'	=> $option[1],
					'name'	=> $option[0],
					'ord'	=> $grp_pref->user['security']['sms']['priority'],
					'user'	=> $grp_pref->user['security']['sms']['user'],
				);
			}
			$i = 0;
			foreach( $grps_sort_arr as $key => $value ){
				usort($value,create_function('$a,$b', ($key=='ord')?'return ($a["ord"] > $b["ord"]);':'return strnatcmp($a["name"],$b["name"]);'));
				foreach( $value as $tmp ){
					$grps_sortable .= $this->create_li($lang_user,$lang_passwd,$tmp['id'],$tmp['name'],$tmp['user'],$i);
					$i = $i + 1;
				}
			}
		}
		
		$GLOBALS['phpgw']->template->set_file(array('sms' => 'sms.tpl'));
		$GLOBALS['phpgw']->template->set_block('sms','sms_page','sms_page');
		$GLOBALS['phpgw']->template->set_var(array(
			'action_url'				=> $GLOBALS['phpgw']->link('/index.php','menuaction=admin.uisms.add'),
			'lang_title'				=> lang('SMS settings'),
			'lang_save'					=> lang('Save'),
			'lang_cancel'				=> lang('Cancel'),
			'lang_yes'					=> lang('Yes'),
			'lang_no'					=> lang('No'),
			'lang_load'					=> lang('Wait Loading...!'),
			'lang_groups_selected'		=> lang('selected groups'),
			'lang_groups_ldap'			=> lang('groups ldap'),
			'lang_organizations'		=> lang('Organizations'),
			'lang_sms_enabled'			=> lang('sms module enabled'),
			'lang_sms_wsdl'				=> lang('uri of the wsdl file'),
			'lang_sms_user'				=> lang('username sms system'),
			'lang_sms_passwd'			=> lang('password sms system'),
			'lang_credentials'			=> lang('credentials'),
			'value_sms_enabled_true'	=> ($GLOBALS['phpgw_info']['server']['sms_enabled']) ? ' selected="selected"' : '',
			'value_sms_enabled_false'	=> ($GLOBALS['phpgw_info']['server']['sms_enabled']) ? '' : ' selected="selected"',
			'value_sms_wsdl'			=> ($GLOBALS['phpgw_info']['server']['sms_wsdl'   ]) ? $GLOBALS['phpgw_info']['server']['sms_wsdl'] : '',
			'value_sms_user'			=> ($GLOBALS['phpgw_info']['server']['sms_user'   ]) ? $GLOBALS['phpgw_info']['server']['sms_user'] : '',
			'grps_sortable'				=> $grps_sortable,
			'grp_default'				=> $this->create_li($lang_user,$lang_passwd),
			'groups_sms'				=> $gsms,
			'ous_ldap'					=> $ous
		));

		$GLOBALS['phpgw']->template->pparse('out','sms_page');
	}

	function display_row($label, $value)
	{
		$GLOBALS['phpgw']->template->set_var('tr_color',$this->nextmatchs->alternate_row_color());
		$GLOBALS['phpgw']->template->set_var('label',$label);
		$GLOBALS['phpgw']->template->set_var('value',$value);
		$GLOBALS['phpgw']->template->parse('rows','row',True);
	}

	function add()
	{

		if($GLOBALS['phpgw']->acl->check('applications_access',1,'admin'))
		{
			$GLOBALS['phpgw']->redirect_link('/index.php');
		}

		if ($_POST['cancel'])
		{
			$GLOBALS['phpgw']->redirect_link('/admin/index.php');
		}

		if ( $_POST['save'] )
		{
			$conf['sms_enabled']	= $_POST['sms_enabled'];
			$conf['sms_wsdl']		= $_POST['sms_wsdl'];
			$conf['sms_user']		= $_POST['sms_user'];
			if (isset($_POST['sms_passwd']) && strlen($_POST['sms_passwd']) > 0) $conf['sms_passwd'] = $_POST['sms_passwd'];

			if ( is_array($_POST['sms_groups']) )
			{
				foreach ($_POST['sms_groups'] as $tmp)
				{
					$id = array_pop (explode(";",$tmp));
					$conf['sms_groups'] = (count($conf['sms_groups']) > 0 ) ? $conf['sms_groups'] . "," . $tmp : $tmp;
					$grp_pref = new preferences($id);
					$grp_pref->read_repository();
					foreach (array('priority','user','passwd') as $key)
						if (isset($_POST['grp_'.$key][$id]) && $_POST['grp_'.$key][$id] !== '')
							$grp_pref->user['security']['sms'][$key] = $_POST['grp_'.$key][$id];
					$grp_pref->save_repository(true);
				}
			}
			else $conf['sms_groups'] = '';
			
			$this->bo->setConfDB($conf);
		}

		$GLOBALS['phpgw']->redirect_link('/admin/index.php');
	}

	function create_li($lb_user, $lb_passwd, $id = 'default', $title = '', $user = '', $priority = -1)
	{
		$mkname = ($id != 'default');
		$str  = '<li id="grp_'.$id.'" class="ui-state-default">';
		$str .= '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
		$str .= '<div>'.$lb_passwd.': <input type="password" class="grp_passwd" value=""'.($mkname?' name="grp_passwd['.$id.']"':'').'/></div>';
		$str .= '<div>'.$lb_user.': <input type="text" class="grp_user" value="'.$user.'"'.($mkname?' name="grp_user['.$id.']"':'').'/></div>';
		$str .= '<div class="grp_title">'.$title.'</div>';
		$str .= '<input type="hidden" class="grp_priority" value="'.$priority.'"'.($mkname?' name="grp_priority['.$id.']"':'').'/>';
		$str .= '</li>';
		return $str;
	}
}
?>
