<?php
	/**************************************************************************\
	* phpGroupWare - Setup                                                     *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	* This program is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU General Public License as published by the    *
	* Free Software Foundation; either version 2 of the License, or (at your   *
	* option) any later version.                                               *
	\**************************************************************************/	
	$test[] = '1.2';
	function expressoAdmin1_2_upgrade1_2()
	{
		$GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '1.21';
		return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}
	
	$test[] = '1.21';
	function expressoAdmin1_2_upgrade1_21()
	{
		$oProc = $GLOBALS['phpgw_setup']->oProc;

		$oProc->CreateTable(
			'phpgw_expressoadmin_samba', array(
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
		
		$GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '1.240';
		return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '1.240';
	function expressoAdmin1_2_upgrade1_240()
	{
		$GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '1.250';
		return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}
	
	$test[] = '1.261';
	function expressoAdmin1_2_upgrade1_261()
	{
		$GLOBALS['phpgw_setup']->oProc->DropColumn('phpgw_expressoadmin_log','','appinfo');
		$GLOBALS['phpgw_setup']->oProc->DropColumn('phpgw_expressoadmin_log','','groupinfo');
		$GLOBALS['phpgw_setup']->oProc->DropColumn('phpgw_expressoadmin_log','','msg');
	}

?>
