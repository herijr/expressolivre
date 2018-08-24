<?php
	/***********************************************************************************\
	* Expresso Administração                 										   *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  *
	* ---------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it		   *
	*  under the terms of the GNU General Public License as published by the		   *
	*  Free Software Foundation; either version 2 of the License, or (at your		   *
	*  option) any later version.													   *
	\***********************************************************************************/

	$setup_info['expressoAdmin1_2']['name']      = 'expressoAdmin1_2';
	$setup_info['expressoAdmin1_2']['title']     = 'ExpressoAdmin 1.2';
	$setup_info['expressoAdmin1_2']['version']   = '2.2.8';
	$setup_info['expressoAdmin1_2']['app_order'] = 1;
	$setup_info['expressoAdmin1_2']['enable']    = 1;

	$setup_info['expressoAdmin1_2']['author'] = 'João Alfredo Knopik Junior';

	$setup_info['expressoAdmin1_2']['maintainer'] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
		);

	$setup_info['expressoAdmin1_2']['license']  = 'GPL';
	$setup_info['expressoAdmin1_2']['description'] = 'Administration Module for Users, Groups and Lists';

	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_apps';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_passwords';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_log';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_samba';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['expressoAdmin1_2']['hooks'][] = 'admin';

	/* Dependencies for this app to work */
	$setup_info['expressoAdmin1_2']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => array('2.0','2.2')
	);
?>
