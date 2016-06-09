<?php
if ( $GLOBALS['phpgw_info']['server']['deny_all_logins'] ) {
	if ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
		if ( preg_match( '#application/json#', $_SERVER['HTTP_ACCEPT'] ) ) {
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'error' => 'server maintenance' ) );
			exit;
		} else  if ( preg_match( '#application/[^ ,]*serialized#', $_SERVER['HTTP_ACCEPT'] ) ) {
			header( 'Content-Type: application/php.serialized' );
			echo serialize( array( 'error' => 'server maintenance' ) );
			exit;
		}
	}
	echo file_get_contents( __DIR__.'/maintenance.html' );
	exit;
}