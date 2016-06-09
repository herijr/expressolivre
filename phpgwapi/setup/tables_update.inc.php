<?php
	/**************************************************************************\
	* phpGroupWare - Setup                                                     *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	// Since Expresso 1.2 using API EgroupWare 1.0.0.007 
	$test[] = '1.0.0.007';
	function phpgwapi_upgrade1_0_0_007()
	{

		global $setup_info,$phpgw_setup;

		$phpgw_setup->oProc->AddColumn('phpgw_access_log','browser', array ('type' => 'varchar', 'precision' => 200));
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '1.0.0.008';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];

	}
	
	$test[] = '1.0.0.008';
	function phpgwapi_upgrade1_0_0_008()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.0.pre-alpha';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];

	}

	$test[] = '2.0.0.pre-alpha';
	function phpgwapi_upgrade2_0_0_prealpha()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.000';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.000';
	function phpgwapi_upgrade2_0_000()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.001';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.001';
	function phpgwapi_upgrade2_0_001()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.002';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}		
	$test[] = '2.0.002';
	function phpgwapi_upgrade2_0_002()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.003';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.003';
	function phpgwapi_upgrade2_0_003()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.004';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.004';
	function phpgwapi_upgrade2_0_004()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.005';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.005';
	function phpgwapi_upgrade2_0_005()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.006';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}			
	$test[] = '2.0.006';
	function phpgwapi_upgrade2_0_006()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.007';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}	
	$test[] = '2.0.007';
	function phpgwapi_upgrade2_0_007()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.008';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}	
	$test[] = '2.0.008';
	function phpgwapi_upgrade2_0_008()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.009';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.0.009';
	function phpgwapi_upgrade2_0_009()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.0.010';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}	
	$test[] = '2.0.010';
	function phpgwapi_upgrade2_0_010()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.1.000';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}	
	$test[] = '2.1.000';
	function phpgwapi_upgrade2_1_000()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.000';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.2.000';
	function phpgwapi_upgrade2_2_000()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.1';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.2.1';
	function phpgwapi_upgrade2_2_1()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.2';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.2.2';
	function phpgwapi_upgrade2_2_2()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.3';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.2.3';
	function phpgwapi_upgrade2_2_3()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.4';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
	$test[] = '2.2.4';
	function phpgwapi_upgrade2_2_4()
	{
		$GLOBALS['setup_info']['phpgwapi']['currentver'] = '2.2.6';
		return $GLOBALS['setup_info']['phpgwapi']['currentver'];
	}
?>
