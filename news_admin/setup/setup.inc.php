<?php
	/**************************************************************************\
	* eGroupWare - News                                                        *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	/* Basic information about this app */
	$setup_info['news_admin']['name']      = 'news_admin';
	$setup_info['news_admin']['title']     = 'News Admin';
	$setup_info['news_admin']['version']   = '2.5.1';
	$setup_info['news_admin']['app_order'] = 16;
	$setup_info['news_admin']['enable']    = 1;

	$setup_info['news_admin']['author'] = 'eGroupWare coreteam';

	$setup_info['news_admin']['maintainer'][] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
		);

	$setup_info['news_admin']['license']  = 'GPL';
	$setup_info['news_admin']['description'] = 'Bulletim Board Application';

	/* The tables this app creates */
	$setup_info['news_admin']['tables']    = array('phpgw_news','phpgw_news_export');

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['news_admin']['hooks'][] = 'admin';
	$setup_info['news_admin']['hooks'][] = 'home';
	$setup_info['news_admin']['hooks'][] = 'sidebox_menu';
	$setup_info['news_admin']['hooks'][] = 'settings';
	$setup_info['news_admin']['hooks'][] = 'preferences';

	/* Dependencies for this app to work */
	$setup_info['news_admin']['depends'][] = array(
		 'appname' => 'phpgwapi',
		 'versions' => Array('2.2','2.5.1.1')
	);
