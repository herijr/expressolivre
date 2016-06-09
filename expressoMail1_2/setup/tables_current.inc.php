<?php
	/**************************************************************************\
	* Expresso Administração                 										                                 *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	 *
	* -----------------------------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it					 *
	*  under the terms of the GNU General Public License as published by the				 *
	*  Free Software Foundation; either version 2 of the License, or (at your					 *
	*  option) any later version.																						 *
	\**************************************************************************/
	$phpgw_baseline = array(
		'phpgw_expressomail_contacts' => array(
			'fd' => array(
				'id_owner' => array( 'type' => 'int', 'precision' => 8, 'nullable' => false),
				'data' => array( 'type' => 'text')
			),
			'pk' => array('id_owner'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
        'phpgw_certificados' => array(
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
?>
