<?php
	/**************************************************************************\
	* eGroupWare - Webpage news admin                                          *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	* --------------------------------------------                             *
	* This program was sponsered by Golden Glair productions                   *
	* http://www.goldenglair.com                                               *
	\**************************************************************************/
	// Since Expresso 1.2 using news_admin 1.0.0 
	$test[] = '1.0.0';
	function news_admin_upgrade1_0_0()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.0.000';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}
	$test[] = '2.0.000';
	function news_admin_upgrade2_0_000()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.0.001';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}	
	$test[] = '2.0.001';
	function news_admin_upgrade2_0_001()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.1.000';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}
	$test[] = '2.1.000';
	function news_admin_upgrade2_1_000()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.2.000';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}	
	$test[] = '2.2.000';
	function news_admin_upgrade2_2_000()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.2.1';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}
	$test[] = '2.2.1';
	function news_admin_upgrade2_2_1()
	{
		$GLOBALS['setup_info']['news_admin']['currentver'] = '2.2.6';
		return $GLOBALS['setup_info']['news_admin']['currentver'];
	}
?>
