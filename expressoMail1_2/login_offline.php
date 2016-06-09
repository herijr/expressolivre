<?php
	/**************************************************************************\
	* ExpressoMail Offline login                                                         *
	* http://www.egroupware.org                                                *
	* Originaly written by Dan Kuykendall <seek3r@phpgroupware.org>            *
	*                      Joseph Engo    <jengo@phpgroupware.org>             *
	* Updated by Nilton Emilio Buhrer Neto <niltonneto@celepar.pr.gov.br>      *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/
ini_set("display_errors","1");
	$phpgw_info = array();
	$submit = False;			// set to some initial value

	$GLOBALS['phpgw_info']['flags'] = array(
		'disable_Template_class' => True,
		'login'                  => True,
		'currentapp'             => 'expressoMail1_2',
		'noheader'               => True
	);

	include('../header.inc.php');

	$tmpl = CreateObject('phpgwapi.Template', 'templates/default');

	// read the images from the login-template-set, not the (maybe not even set) users template-set
	$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'] = $GLOBALS['phpgw_info']['login_template_set'];

	$tmpl->set_file(array('login_form' => 'login_offline.tpl'));

		$GLOBALS['phpgw']->translation->init();	// this will set the language according to the (new) set prefs
		$GLOBALS['phpgw']->translation->add_app('login');
		$GLOBALS['phpgw']->translation->add_app('loginscreen');
		if(lang('loginscreen_message') == 'loginscreen_message*')
		{
			$GLOBALS['phpgw']->translation->add_app('loginscreen','en');	// trying the en one
		}
		if(lang('loginscreen_message') != 'loginscreen_message*')
		{
			$tmpl->set_var('lang_message',stripslashes(lang('loginscreen_message')));
		}

	$GLOBALS['phpgw_info']['server']['template_set'] = $GLOBALS['phpgw_info']['login_template_set'];

	$tmpl->set_var('register_link',$reg_link);
	$tmpl->set_var('charset',$GLOBALS['phpgw']->translation->charset());
	$tmpl->set_var('login_url', $GLOBALS['phpgw_info']['server']['webserver_url'] . '/login.php' . $extra_vars);
	$tmpl->set_var('registration_url',$GLOBALS['phpgw_info']['server']['webserver_url'] . '/registration/');
	$tmpl->set_var('version',$GLOBALS['phpgw_info']['server']['versions']['phpgwapi']);
	$tmpl->set_var('cookie',$last_loginid);
	//david
	$tmpl->set_var('teste_compatibilidade',$GLOBALS['phpgw_info']['server']['teste_compatibilidade']);

	$tmpl->set_var('lang_username',lang('username'));
	$tmpl->set_var('lang_password',lang('password'));
	$tmpl->set_var('lang_login',lang('login'));

	$tmpl->set_var('website_title', $GLOBALS['phpgw_info']['server']['site_title']);
	$tmpl->set_var('template_set',$GLOBALS['phpgw_info']['login_template_set']);
	$tmpl->set_var('bg_color',($GLOBALS['phpgw_info']['server']['login_bg_color']?$GLOBALS['phpgw_info']['server']['login_bg_color']:'FFFFFF'));
	$tmpl->set_var('bg_color_title',($GLOBALS['phpgw_info']['server']['login_bg_color_title']?$GLOBALS['phpgw_info']['server']['login_bg_color_title']:'486591'));

	if($GLOBALS['phpgw_info']['server']['use_frontend_name'])
		$tmpl->set_var('frontend_name', " - ".$GLOBALS['phpgw_info']['server']['use_frontend_name']);

	if (substr($GLOBALS['phpgw_info']['server']['login_logo_file'],0,4) == 'http')
	{
		$var['logo_file'] = $GLOBALS['phpgw_info']['server']['login_logo_file'];
	}
	else
	{
		$var['logo_file'] = $GLOBALS['phpgw']->common->image('phpgwapi',$GLOBALS['phpgw_info']['server']['login_logo_file']?$GLOBALS['phpgw_info']['server']['login_logo_file']:'logo');
	}
	$var['logo_url'] = $GLOBALS['phpgw_info']['server']['login_logo_url']?$GLOBALS['phpgw_info']['server']['login_logo_url']:'http://www.eGroupWare.org';
	if (substr($var['logo_url'],0,4) != 'http')
	{
		$var['logo_url'] = 'http://'.$var['logo_url'];
	}
	$var['logo_title'] = $GLOBALS['phpgw_info']['server']['login_logo_title']?$GLOBALS['phpgw_info']['server']['login_logo_title']:'www.eGroupWare.org';
	$tmpl->set_var($var);

	if (@$GLOBALS['phpgw_info']['server']['login_show_language_selection'])
	{
		$select_lang = '<select name="lang" onchange="'."location.href=location.href+(location.search?'&':'?')+'lang='+this.value".'">';
		$langs = $GLOBALS['phpgw']->translation->get_installed_langs();
		uasort($langs,'strcasecmp');
		foreach ($langs as $key => $name)	// if we have a translation use it
		{
			$select_lang .= "\n\t".'<option value="'.$key.'"'.($key == $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'] ? ' selected="1"' : '').'>'.$name.'</option>';
		}
		$select_lang .= "\n</select>\n";
		$tmpl->set_var(array(
			'lang_language' => lang('Language'),
			'select_language' => $select_lang,
		));
	}
	else
	{
		$tmpl->set_block('login_form','language_select');
		$tmpl->set_var('language_select','');
	}
	
	if (count($GLOBALS['phpgw_info']['server']['login_logo_file']) > 0) {
		$logo_config = $GLOBALS['phpgw_info']['server']['login_logo_file'];
		$logo_config = preg_replace("#phpgwapi/#","../phpgwapi/",$logo_config);
		$tmpl->set_var('logo_config',$logo_config);	
	}
		else
			$tmpl->set_var('logo_config','<a title="Governo do Paran&aacute" href="http://www.pr.gov.br" target="_blank"><img src="../phpgwapi/templates/'.$GLOBALS['phpgw_info']['login_template_set'].'/images/logo_govparana_93x70.gif" border="0"></a></td>
			<td><div align="center"><font color="#9a9a9a" face="Verdana, Arial, Helvetica, sans-serif" size="1">
	<a title="Celepar Inform&aacute;tica do Paran&aacute;" target="_blank" href="http://www.celepar.pr.gov.br/">
	<img src="../phpgwapi/templates/'.$GLOBALS['phpgw_info']['login_template_set'].'/images/logo_celepar_104x49.png" border="0"></a>');
	
	$tmpl->set_var('autocomplete', ($GLOBALS['phpgw_info']['server']['autocomplete_login'] ? 'autocomplete="off"' : ''));

	$tmpl->pfp('loginout','login_form');
?>
