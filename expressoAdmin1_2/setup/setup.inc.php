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

	$setup_info['expressoAdmin1_2']['name']      	= 'expressoAdmin1_2';
	$setup_info['expressoAdmin1_2']['title']     	= 'ExpressoAdmin 1.2';
	/* Ao incrementar versão, não esquecer de declarar função do tables_update.inc.php*/
	$setup_info['expressoAdmin1_2']['version']   	= '2.2.8';
	$setup_info['expressoAdmin1_2']['app_order']	= 1;
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_apps';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_passwords';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_log';
	$setup_info['expressoAdmin1_2']['tables'][]		= 'phpgw_expressoadmin_samba';
	$setup_info['expressoAdmin1_2']['enable']		= 1;

	$setup_info['expressoAdmin1_2']['author'] = 'João Alfredo Knopik Junior (jakjr@celepar.pr.gov.br)';

	$setup_info['expressoAdmin1_2']['maintainer'] = 'João Alfredo Knopik Junior (jakjr@celepar.pr.gov.br)';

	$setup_info['expressoAdmin1_2']['license']  = 'GPL';
	$setup_info['expressoAdmin1_2']['description'] = 'Modulo de Administração de Usuários, Grupos e Listas do ExpressoLivre';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['expressoAdmin1_2']['hooks'][] = 'admin';
	
	/* Dependencies for this app to work */
	$setup_info['expressoAdmin1_2']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => array('2.0','2.2')
	);
?>
