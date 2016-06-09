<?php
	/**********************************************************************************\
	* Expresso Administração                 										   *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  *
	* ---------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it         *
	*  under the terms of the GNU General Public License as published by the           *
	*  Free Software Foundation; either version 2 of the License, or (at your          *
	*  option) any later version.											           *
	\**********************************************************************************/

	$setup_info['expressoMail1_2']['name']      	= 'expressoMail1_2';
	$setup_info['expressoMail1_2']['title']     	= 'Expresso Mail';
	$setup_info['expressoMail1_2']['version']   	= '2.2.6';
	$setup_info['expressoMail1_2']['app_order'] 	= 2;
	$setup_info['expressoMail1_2']['tables'][]		= 'phpgw_expressomail_contacts';
    $setup_info['expressoMail1_2']['tables'][]		= 'phpgw_certificados';
	$setup_info['expressoMail1_2']['enable']    	= 1;

	$setup_info['expressoMail1_2']['author'] = 	'João Alfredo Knopik Junior'.
												' (joao.alfredo@gmail.com / jakjr@celepar.pr.gov.br)<br>'.
												'Nilton Emílio Bührer Neto'.
												' (nilton.neto@gmail.com / niltonneto@celepar.pr.gov.br)';
	$setup_info['expressoMail1_2']['maintainer'] =  'Empresa ou Instituição onde o seu Expresso está instalado.';
	$setup_info['expressoMail1_2']['maintainer_email'] = '';

	$setup_info['expressoMail1_2']['license']  = 'GPL';
	$setup_info['expressoMail1_2']['description'] = 'Módulo de Email, usando metodologia AJAX';

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['expressoMail1_2']['hooks'][] = 'preferences';
	$setup_info['expressoMail1_2']['hooks'][] = 'admin';
	$setup_info['expressoMail1_2']['hooks'][] = 'home';	
	$setup_info['expressoMail1_2']['hooks'][] = 'settings';	
	
	/* Dependencies for this app to work */
	$setup_info['expressoMail1_2']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => Array('2.2')
	);
?>
