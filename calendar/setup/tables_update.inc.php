<?php
  /**************************************************************************\
  * eGroupWare - Setup                                                       *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/
	function addSpecialColumn($table,$column, $attrs){
		$result = $GLOBALS['phpgw_setup']->db->metadata($table);
		if($result){
			foreach($result as $idx => $col){
				if($col['name'] == $column)
					return;
			}
		}		
		$GLOBALS['phpgw_setup']->db->query("ALTER TABLE ".$table." ADD COLUMN ".$column." ".$attrs);
	}  
/// Since Expresso 1.2 using Calendar 0.9.3 
	$test[] = '0.9.3';
	function calendar_upgrade0_9_3()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.000';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.0.000';
	function calendar_upgrade2_0_000()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.001';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.0.001';
	function calendar_upgrade2_0_001()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.002';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.0.002';
	function calendar_upgrade2_0_002()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.003';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.0.003';
	function calendar_upgrade2_0_003()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.004';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}	
	$test[] = '2.0.004';
	function calendar_upgrade2_0_004()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.005';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}	
	$test[] = '2.0.005';
	function calendar_upgrade2_0_005()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.006';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}	
	$test[] = '2.0.006';
	function calendar_upgrade2_0_006()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.0.007';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}		
	$test[] = '2.0.007';
	function calendar_upgrade2_0_007()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.1.000';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.1.000';
	function calendar_upgrade2_1_000()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.2.000';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.2.000';
	function calendar_upgrade2_2_000()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.2.1';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
	$test[] = '2.2.1';
	function calendar_upgrade2_2_1()
	{
		$GLOBALS['setup_info']['calendar']['currentver'] = '2.2.6';
		return $GLOBALS['setup_info']['calendar']['currentver'];
	}
?>