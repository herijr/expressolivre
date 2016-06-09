<?php

$GLOBALS['phpgw_info']['flags'] = array(
	'disable_Template_class' => True,
	'login'                  => True,
	'currentapp'             => 'login',
	'noheader'               => True
);
	require_once('./header.inc.php');
	$GLOBALS['phpgw_info']['server']['template_dir'] = PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . $GLOBALS['phpgw_info']['login_template_set'];
	$tmpl = CreateObject('phpgwapi.Template', $GLOBALS['phpgw_info']['server']['template_dir']);
	$tmpl->set_file(array('login_form' => 'help.tpl'));
	$tmpl->set_var('website_title', $GLOBALS['phpgw_info']['server']['site_title']);
    $tmpl->set_var('template_set',$GLOBALS['phpgw_info']['login_template_set']);
	$GLOBALS['phpgw']->translation->init();
	$GLOBALS['phpgw']->translation->add_app('loginhelp');
	$GLOBALS['phpgw']->translation->add_app('loginhelp',$_GET['lang']);
	if(lang('loginhelp_message') != 'loginhelp_message*')
	{
		$tmpl->set_var('login_help',lang('loginhelp_message'));
	}
	$tmpl->pfp('loginout','login_form');
?>

