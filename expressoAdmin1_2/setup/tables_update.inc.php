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
		$GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.000';
		return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.000';
	function expressoAdmin1_2_upgrade2_0_000()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.001';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.001';
	function expressoAdmin1_2_upgrade2_0_001()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.002';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.002';
	function expressoAdmin1_2_upgrade2_0_002()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.003';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.003';
	function expressoAdmin1_2_upgrade2_0_003()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.004';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.004';
	function expressoAdmin1_2_upgrade2_0_004()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.0.005';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.0.005';
	function expressoAdmin1_2_upgrade2_0_005()
	{
        $GLOBALS['phpgw_setup']->db->query("alter table phpgw_expressoadmin_log drop groupinfo");
        $GLOBALS['phpgw_setup']->db->query("alter table phpgw_expressoadmin_log drop appinfo");
        $GLOBALS['phpgw_setup']->db->query("alter table phpgw_expressoadmin_log drop msg");             

        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.1.000';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.1.000';
	function expressoAdmin1_2_upgrade2_1_000()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.000';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.2.000';
	function expressoAdmin1_2_upgrade2_2_000()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.1';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.2.1';
	function expressoAdmin1_2_upgrade2_2_1()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.2';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.2.2';
	function expressoAdmin1_2_upgrade2_2_2()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.3';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.2.3';
	function expressoAdmin1_2_upgrade2_2_3()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.6';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}

	$test[] = '2.2.6';
	function expressoAdmin1_2_upgrade2_2_6()
	{
        $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'] = '2.2.8';
        return $GLOBALS['setup_info']['expressoAdmin1_2']['currentver'];
	}
		