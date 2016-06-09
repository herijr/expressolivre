<?php
	/**************************************************************************\
	* ExpressoLivre - Setup                                                     *
	* http://www.expressolivre.org                                              *
	* --------------------------------------------                             *
	* This program is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU General Public License as published by the    *
	* Free Software Foundation; either version 2 of the License, or (at your   *
	* option) any later version.                                               *
	\**************************************************************************/
	//	Since Expresso 1.2 using ExpressoMail 1.233		
	$test[] = '1.233';
	function expressoMail1_2_upgrade1_233() {
		$setup_info['expressoMail1_2']['currentver'] = '1.234';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '1.234';
	function expressoMail1_2_upgrade1_234() {
    	$oProc = $GLOBALS['phpgw_setup']->oProc;            
            $oProc->CreateTable('phpgw_certificados',array(
			'fd' => array(
				'email' => array( 'type' => 'varchar', 'precision' => 60, 'nullable' => false),
				'chave_publica' => array( 'type' => 'text'),
				'expirado' => array('type' => 'bool', 'default' => 'false'),
				'revogado' => array('type' => 'bool', 'default' => 'false'),
				'serialnumber' => array('type' => 'int', 'precision' => 8, 'nullable' => false),
				'authoritykeyidentifier' => array( 'type' => 'text', 'nullable' => false),
			),
			'pk' => array('email','serialnumber','authoritykeyidentifier'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
			)
		);
		$GLOBALS['setup_info']['expressoMail1_2']['currentver'] = '1.235';
        return $GLOBALS['setup_info']['expressoMail1_2']['currentver'];
	}
	$test[] = '1.235';
	function expressoMail1_2_upgrade1_235() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.000';
		return $setup_info['expressoMail1_2']['currentver'];
	}		
	$test[] = '2.0.000';
	function expressoMail1_2_upgrade2_0_000() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.001';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.0.001';
	function expressoMail1_2_upgrade2_0_001() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.002';
		return $setup_info['expressoMail1_2']['currentver'];
	}	
	$test[] = '2.0.002';
	function expressoMail1_2_upgrade2_0_002() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.003';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.0.003';
	function expressoMail1_2_upgrade2_0_003() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.004';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.0.004';
	function expressoMail1_2_upgrade2_0_004() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.005';
		return $setup_info['expressoMail1_2']['currentver'];
	}	
	$test[] = '2.0.005';
	function expressoMail1_2_upgrade2_0_005() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.006';
		return $setup_info['expressoMail1_2']['currentver'];
	}	
	$test[] = '2.0.006';
	function expressoMail1_2_upgrade2_0_006() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.007';
		return $setup_info['expressoMail1_2']['currentver'];
	}		
	$test[] = '2.0.007';
	function expressoMail1_2_upgrade2_0_007() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.008';
		return $setup_info['expressoMail1_2']['currentver'];
	}	
	$test[] = '2.0.008';
	function expressoMail1_2_upgrade2_0_008() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.009';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.0.009';
	function expressoMail1_2_upgrade2_0_009() {
		$setup_info['expressoMail1_2']['currentver'] = '2.0.010';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.0.010';
	function expressoMail1_2_upgrade2_0_010() {
		$setup_info['expressoMail1_2']['currentver'] = '2.1.000';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.1.000';
	function expressoMail1_2_upgrade2_1_000() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.000';
		return $setup_info['expressoMail1_2']['currentver'];
	}	
	$test[] = '2.2.000';
	function expressoMail1_2_upgrade2_2_000() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.1';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.2.1';
	function expressoMail1_2_upgrade2_2_1() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.2';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.2.2';
	function expressoMail1_2_upgrade2_2_2() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.3';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.2.3';
	function expressoMail1_2_upgrade2_2_3() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.4';
		return $setup_info['expressoMail1_2']['currentver'];
	}
	$test[] = '2.2.4';
	function expressoMail1_2_upgrade2_2_4() {
		$setup_info['expressoMail1_2']['currentver'] = '2.2.6';
		return $setup_info['expressoMail1_2']['currentver'];
	}
?>