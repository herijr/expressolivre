<?php
	/**************************************************************************\
	* eGroupWare - Administration                                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	$setup_info['admin']['name']      = 'admin';
	$setup_info['admin']['title']      = 'Admin';
	$setup_info['admin']['version']   = '2.2.1';
	$setup_info['admin']['app_order'] = 1;
	$setup_info['admin']['enable']    = 1;

	$setup_info['admin']['author'] = 'eGroupWare coreteam';

	$setup_info['admin']['maintainer'][] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
	);

	$setup_info['admin']['license']  = 'GPL';
	$setup_info['admin']['description'] = 'Main Administration Application';

	$setup_info['admin']['tables']    = '';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['admin']['hooks'] = array(
		'acl_manager',
		'add_def_pref',
		'admin',
		'after_navbar',
		'config',
		'deleteaccount',
		'view_user' => 'admin.uiaccounts.edit_view_user_hook',
		'edit_user' => 'admin.uiaccounts.edit_view_user_hook',
		'sidebox_menu'
	);

	/* Dependencies for this app to work */
	$setup_info['admin']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => Array('2.2')
	);
?>
