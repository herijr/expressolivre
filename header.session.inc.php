<?php

if ( isset( $_COOKIE[ 'sessionid' ] ) ) session_id( $_COOKIE[ 'sessionid' ] );

session_start( );

$invalidSession = false;
$user_agent = array();

// Update and Load DB information auth
if ( isset( $GLOBALS['phpgw'] ) && !isset( $_SESSION['connection_db_info'] ) ) {
	
	$_SESSION['phpgw_info']['admin']['server']['sessions_checkip'] = $GLOBALS['phpgw_info']['server']['sessions_checkip'];
	
	if ( $GLOBALS['phpgw_info']['server']['use_https'] == 1 ) {
		
		$new_ip = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )? $_SERVER['HTTP_X_FORWARDED_FOR'].',' : '' ).$_SERVER['REMOTE_ADDR'];
		$GLOBALS['phpgw']->db->query(
			'UPDATE phpgw_access_log '.
			'SET ip=\''.$new_ip.'\' '.
			'WHERE account_id <> 0 AND lo = 0 AND sessionid=\''.$GLOBALS['sessionid'].'\'',
			__LINE__,
			__FILE__
		);
		unset( $new_ip );
	}
	
	$GLOBALS['phpgw']->db->query(
		'SELECT trim(sessionid)'.( $_SESSION['phpgw_info']['admin']['server']['sessions_checkip']? ', ip' : '').', browser '.
		'FROM phpgw_access_log '.
		'WHERE account_id <> 0 AND lo = 0 AND sessionid=\''.$GLOBALS['sessionid'].'\' '.
		'LIMIT 1',
		__LINE__,
		__FILE__
	);
	
	$GLOBALS['phpgw']->db->next_record();
	if ( $GLOBALS['phpgw']->db->row() )
		$_SESSION['connection_db_info']['user_auth'] = implode( '', $GLOBALS['phpgw']->db->row() );
}

// Check User Agent
if ( isset($_SESSION['connection_db_info']['user_auth']) && $_SESSION['connection_db_info']['user_auth'] ) {

	$invalidSession  = true;
	$sess            = $_SESSION[ 'phpgw_session' ];
	$filter          = function( $str ) { return trim( preg_replace( '#FirePHP[/ ][0-9.]+#', '', $str ) ); };
	$user_ip         = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? array( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'] ) : array( $_SERVER['REMOTE_ADDR'] );
	$http_user_agent = $filter( substr($_SERVER[ 'HTTP_USER_AGENT' ], 0, 199 ) );
	$user_agent[]    = ( $_SESSION['phpgw_info']['admin']['server']['sessions_checkip'] ? "{$sess['session_id']}{$user_ip[0]}" : "{$sess['session_id']}" ).$http_user_agent;
	
	if ( count( $user_ip ) == 2 ) {
		$user_agent[] = "{$sess['session_id']}{$user_ip[1]}".$http_user_agent;
		$user_agent[] = $sess['session_id'].implode( ',', array_reverse( $user_ip ) ).$http_user_agent;
	}
	
	$pconnection_id = $filter( $_SESSION['connection_db_info']['user_auth'] );
	if ( array_search( $pconnection_id, $user_agent ) !== false ) $invalidSession = false;
	foreach ( $user_agent as $agent ) if ( strpos( $pconnection_id, $agent ) !== false ) $invalidSession = false;
	
	unset( $sess );
	unset( $filter );
	unset( $user_ip );
	unset( $http_user_agent );
	unset( $pconnection_id );
	
}

$is_controller = strstr( $_SERVER['SCRIPT_NAME'], '/controller.php' );

// Session Invalid
if ( empty( $_SESSION['phpgw_session']['session_id'] ) || $invalidSession ) {
	
	if ( ( !$is_controller ) && isset( $_SESSION['connection_db_info']['user_auth'] ) ) {
		
		error_log( '[ INVALID SESSION ] >>>>'.$_SESSION['connection_db_info']['user_auth'].'<<<< - >>>>'.implode( '', $user_agent ), 0 );
		$GLOBALS['phpgw']->session->phpgw_setcookie( 'sessionid' );
		$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw_info']['server']['webserver_url'].'/login.php?cd=10' );
		
	}
	
	// Removing session cookie.
	setcookie( session_name(), '', 0 );
	
	// Removing session values.
	unset( $_SESSION );
	
	// From ExpressoAjax response "nosession"
	if ( $is_controller ) {
		
		echo serialize( array( 'nosession' => true ) );
		exit;
		
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], 'login.php' ) === false ) {
		header( 'Location: /login.php?cd=2' );
		exit;
	}
	
}

// From ExpressoAjax update session_dla (datetime last access).
if ( $is_controller ) $_SESSION['phpgw_session']['session_dla'] = time();

unset( $invalidSession );
unset( $user_agent );
unset( $is_controller );
