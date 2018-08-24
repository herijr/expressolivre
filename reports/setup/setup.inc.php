<?php
	/***********************************************************************************\
	* Expresso relatório                 										   *
	* by Elvio Rufino da Silva (elviosilva@yahoo.com.br, elviosilva@cepromat.mt.gov.br)  *
	* ---------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it		   *
	*  under the terms of the GNU General Public License as published by the		   *
	*  Free Software Foundation; either version 2 of the License, or (at your		   *
	*  option) any later version.													   *
	\***********************************************************************************/

	$setup_info['reports']['name']      	= 'reports';
	$setup_info['reports']['title']     	= 'Expresso Reports';
	// After new version increment, don't forget to declare a function in tables_update.inc.php
	$setup_info['reports']['version']   	= '2.2.8';
	$setup_info['reports']['app_order']	= 17;
	$setup_info['reports']['enable']		= 1;

	$setup_info['reports']['author'] = 'Elvio Rufino da Silva';

	$setup_info['reports']['maintainer'][] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
		);

	$setup_info['reports']['license']  = 'GPL';
	$setup_info['reports']['description'] = 'Reports Application for Users, Groups and Lists';

	$setup_info['reports']['tables']    = '';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['reports']['hooks'] = array(
//		'acl_manager',
//		'add_def_pref',
//		'view_user' => 'admin.uiaccounts.edit_view_user_hook'
//		'after_navbar',
//		'config',
//		'deleteaccount',
//		'edit_user' => 'admin.uiaccounts.edit_view_user_hook',
//		'sidebox_menu',
		'admin'
	);

	/* Dependencies for this app to work */
	$setup_info['reports']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => Array('2.2')
	);
?>
