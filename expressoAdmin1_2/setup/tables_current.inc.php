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

	$phpgw_baseline = array(
		'phpgw_expressoadmin' => array(
			'fd' => array(
				'manager_lid'	=> array('type' => 'varchar','precision' => 50,'nullable' => false),
				'context'		=> array('type' => 'varchar','precision' => 255,'nullable' => false),
				'acl'			=> array('type' => 'int','precision' => 8,'nullable' => false)
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		
		'phpgw_expressoadmin_apps' => array(
			'fd' => array(
				'manager_lid'	=> array('type' => 'varchar','precision' => 50,'nullable' => false),
				'context'		=> array('type' => 'varchar','precision' => 255,'nullable' => false),
				'app'			=> array('type' => 'varchar','precision' => 100,'nullable' => false)
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),

		'phpgw_expressoadmin_passwords' => array(
			'fd' => array(
				'uid'		=> array('type' => 'varchar','precision' => 100,'nullable' => false),
				'password'	=> array('type' => 'varchar','precision' => 255,'nullable' => false)
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		
		'phpgw_expressoadmin_log' => array(
			'fd' => array(
				'date'			=> array('type' => 'timestamp','nullable' => false),
				'manager'		=> array('type' => 'varchar','precision' => 50,'nullable' => false),
				'action'		=> array('type' => 'varchar','precision' => 255,'nullable' => false),
				'userinfo'		=> array('type' => 'varchar','precision' => 255,'nullable' => false)
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		
		'phpgw_expressoadmin_samba' => array(
			'fd' => array(
				'samba_domain_name' => array( 'type' => 'varchar', 'precision' => 50),
				'samba_domain_sid' => array( 'type' => 'varchar', 'precision' => 100)
			),
			'pk' => array('samba_domain_name'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		)
	);
?>
