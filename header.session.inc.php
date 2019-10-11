<?php

	/***************************************************************************\
	 * eGroupWare                                                               *
	 * http://www.egroupware.org                                                *
	 * The file written by Joseph Engo <jengo@phpgroupware.org>                 *
	 * This file modified by Greg Haygood <shrykedude@bellsouth.net>            *
	 * --------------------------------------------                             *
	 *  This program is free software; you can redistribute it and/or modify it *
	 *  under the terms of the GNU General Public License as published by the   *
	 *  Free Software Foundation; either version 2 of the License, or (at your  *
	 *  option) any later version.                                              *
	\***************************************************************************/

$sessionID = false;

if (isset($_COOKIE['sessionid'])) {
	$sessionID = trim($_COOKIE['sessionid']);
	session_write_close();
	session_id($sessionID);
	session_start();
}

$isController = strstr($_SERVER['SCRIPT_NAME'], '/controller.php');

if ($sessionID && isset($GLOBALS['phpgw'])) {

	$filter          = function ($str) {
		return trim(preg_replace('#FirePHP[/ ][0-9.]+#', '', $str));
	};
	$httpUserAgent   = $filter(substr($_SERVER['HTTP_USER_AGENT'], 0, 199));
	$loginID         = (isset($_COOKIE['last_loginid']) ? $_COOKIE['last_loginid'] : "");

	$query = sprintf(
		'SELECT * FROM phpgw_access_log ' .
			'WHERE account_id <> 0 AND lo = 0 ' .
			'AND sessionid = \'%s\' AND loginid = \'%s\' LIMIT 1',
		pg_escape_string($sessionID),
		pg_escape_string($loginID)
	);

	$GLOBALS['phpgw']->db->query($query, __LINE__, __FILE__);

	$GLOBALS['phpgw']->db->next_record();

	$dataSetDB = $GLOBALS['phpgw']->db->row();

	$testLoginId = (trim($loginID) === trim($_SESSION['phpgw_session']['session_lid'])) ? true : false;
	$testBrowser = (md5($httpUserAgent) === md5($dataSetDB['browser'])) ? true : false;
	$testUserIP  = true;

	if( isset( $GLOBALS['phpgw_info']['server']['sessions_checkip'] ) ){
		$testUserIP = (trim($GLOBALS['phpgw']->session->getuser_ip()) === trim($dataSetDB['ip'])) ? true : false;
	}

	if ( !$testLoginId || !$testBrowser || !$testUserIP ) {

		if (is_array($dataSetDB) && count($dataSetDB) > 0) {

			$query = sprintf(
				'UPDATE phpgw_access_log SET lo = \'%s\' WHERE sessionid = \'%s\' AND loginid = \'%s\';',
				pg_escape_string(time()),
				pg_escape_string($sessionID),
				pg_escape_string($loginID)
			);

			$GLOBALS['phpgw']->db->query($query, __LINE__, __FILE__);
		}

		// Session Close
		session_write_close();

		// Removing session cookie.
		setcookie(session_name(), '', 0);

		// From Ajax response "nosession"
		if ($isController) {
			if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json')) {
				echo json_encode(array('nosession' => true));
			} else {
				echo serialize(array('nosession' => true));
			}
		} else {
			$GLOBALS['phpgw']->redirect($GLOBALS['phpgw_info']['server']['webserver_url'] . '/login.php?cd=10');
		}

		die();
	}

	// From ExpressoAjax update session_dla (datetime last access).
	if ($isController) $_SESSION['phpgw_session']['session_dla'] = time();
}
