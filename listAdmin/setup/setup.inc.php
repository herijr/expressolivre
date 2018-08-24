<?php
	/***********************************************************************************\
	*  This program is free software; you can redistribute it and/or modify it		   *
	*  under the terms of the GNU General Public License as published by the		   *
	*  Free Software Foundation; either version 2 of the License, or (at your		   *
	*  option) any later version.													   *
	\***********************************************************************************/

	$setup_info['listAdmin']['name']      = 'listAdmin';
	$setup_info['listAdmin']['title']     = 'Mailman Admin';
	$setup_info['listAdmin']['version']   = '2.2.1';
	$setup_info['listAdmin']['app_order'] = 10;
	$setup_info['listAdmin']['enable']    = 1;

	$setup_info['listAdmin']['author'] = 'Rommel de Brito Cysne';
	
	$setup_info['listAdmin']['maintainer'] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
	);
	
	$setup_info['listAdmin']['license']  = 'GPL';
	$setup_info['listAdmin']['description'] = 'EmailList Management Application';
	$setup_info['listAdmin']['note'] = 'Based on Mailman Application';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['listAdmin']['hooks'][] = 'admin';

	/* Dependencies for this app to work */
	$setup_info['listAdmin']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => Array('2.0','2.1','2.2')
	);
?>
