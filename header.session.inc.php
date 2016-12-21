<?php
	/**************************************************************************\
	* eGroupWare                                                               *
	* http://www.egroupware.org                                                *
	* The file written by Joseph Engo <jengo@phpgroupware.org>                 *
	* This file modified by Greg Haygood <shrykedude@bellsouth.net>            *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

if ( isset( $_COOKIE[ 'sessionid' ] ) )
{ 
	session_id( $_COOKIE[ 'sessionid' ] );
}

session_start();

$invalidSession = false;

$userAgent = array();

// Update and Load DB information auth
if ( isset( $GLOBALS['phpgw'] ) && !isset( $_SESSION['connection_db_info'] ) )
{
	$_SESSION['phpgw_info']['admin']['server']['sessions_checkip'] = $GLOBALS['phpgw_info']['server']['sessions_checkip'];

	if ( intval( $GLOBALS['phpgw_info']['server']['use_https'] ) == 1 )
	{
		$newIP = "";

		if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
		{
			$newIP = $_SERVER['HTTP_X_FORWARDED_FOR'] . ",";
		}

		$newIP = $newIP . $_SERVER['REMOTE_ADDR'];

		$query = "UPDATE phpgw_access_log SET ip = '".$newIP."' " .
				 "WHERE account_id <> 0 AND lo = 0 AND sessionid='".$GLOBALS['sessionid']."'";

		$GLOBALS['phpgw']->db->query( $query, __LINE__, __FILE__ );
		
		unset( $newIP );

		unset( $query );
	}
	
	$sessionsCheckip = ( isset( $_SESSION['phpgw_info']['admin']['server']['sessions_checkip'] ) ? ',ip' : '' );

	$query = "SELECT trim(sessionid)".$sessionsCheckip.",browser " .
			 "FROM phpgw_access_log " .
			 "WHERE account_id <> 0 AND lo = 0 " .
			 "AND sessionid = '".$GLOBALS['sessionid']."' " .
			 "LIMIT 1";

	$GLOBALS['phpgw']->db->query( $query, __LINE__, __FILE__ );
	
	$GLOBALS['phpgw']->db->next_record();
	
	if ( $GLOBALS['phpgw']->db->row() )
	{
		$userAuth = "";

		if( isset($GLOBALS['phpgw']->db->row()['btrim']) )
		{
			$userAuth = $GLOBALS['phpgw']->db->row()['btrim'];
		}

		if( isset($GLOBALS['phpgw']->db->row()['ip']) )
		{
			$userAuth .= "{".$GLOBALS['phpgw']->db->row()['ip']."}";
		}

		if( isset($GLOBALS['phpgw']->db->row()['browser']) )
		{
			$userAuth .= $GLOBALS['phpgw']->db->row()['browser'];
		}

		$_SESSION['connection_db_info']['user_auth'] = $userAuth;

		unset( $userAuth );
	}
}

// Check User Agent
if ( isset($_SESSION['connection_db_info']['user_auth']) && $_SESSION['connection_db_info']['user_auth'] )
{
	$invalidSession  = true;
	$sess            = $_SESSION['phpgw_session'];
	$filter          = function( $str ){ return trim( preg_replace( '#FirePHP[/ ][0-9.]+#', '', $str ) ); };
	$http_user_agent = $filter( substr($_SERVER[ 'HTTP_USER_AGENT' ], 0, 199 ) );
	
	// userIP
	$userIP = array( $_SERVER['REMOTE_ADDR'] );
	if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	{
		$userIP = array( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	// userAgent
	$userAgent = array( $sess['session_id'] . $http_user_agent );
	if( isset( $_SESSION['phpgw_info']['admin']['server']['sessions_checkip'] ) )
	{
		$userAgent = array( $sess['session_id']."{".$userIP[0]."}".$http_user_agent );
	}
	
	if ( count( $userIP ) == 2 )
	{
		$userAgent[] = $sess['session_id']."{".$userIP[1]."}".$http_user_agent;
		$userAgent[] = $sess['session_id']."{".implode(',', array_reverse( $userIP ))."}".$http_user_agent;
	}
	
	$pconnectionID = $filter( $_SESSION['connection_db_info']['user_auth'] );

	if ( array_search( $pconnectionID, $userAgent ) !== false )
	{
		$invalidSession = false;
	}
	
	foreach ( $userAgent as $agent )
	{
		if ( strpos( $pconnectionID, $agent ) !== false )
		{
			$invalidSession = false;
		}
	}
	
	unset( $sess );
	unset( $filter );
	unset( $userIP );
	unset( $http_user_agent );
	unset( $pconnectionID );
	
}

$isController = strstr( $_SERVER['SCRIPT_NAME'] , '/controller.php' );

// Session Invalid
if ( empty( $_SESSION['phpgw_session']['session_id'] ) || $invalidSession )
{
	if( $isController === false && isset( $_SESSION['connection_db_info']['user_auth'] ) )
	{
		error_log( '[ INVALID SESSION ] >>>>'.$_SESSION['connection_db_info']['user_auth'].'<<<< - >>>>' . implode( '', $userAgent ), 0 );
	
		$GLOBALS['phpgw']->session->phpgw_setcookie( 'sessionid' );
	
		$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw_info']['server']['webserver_url'].'/login.php?cd=10' );
	}
	
	// Removing session cookie.
	setcookie( session_name(), '', 0 );
	
	// Removing session values.
	unset( $_SESSION );
	
	// From ExpressoAjax response "nosession"
	if ( $isController )
	{
		echo serialize( array( 'nosession' => true ) );
		exit;
	}
	else if ( strpos( $_SERVER['SCRIPT_NAME'], 'login.php' ) === false )
	{
		header( 'Location: /login.php?cd=2' );
		exit;
	}
}

// From ExpressoAjax update session_dla (datetime last access).
if ( $isController ) $_SESSION['phpgw_session']['session_dla'] = time();

unset( $invalidSession );
unset( $userAgent );
unset( $isController );