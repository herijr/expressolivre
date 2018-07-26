<?php

function set_response( $data, $isJSON = true, $isUTF8 = true ){
	header( 'Content-Type: '.( $isJSON? 'application/json' : 'text/html' ).'; charset='.( $isUTF8? 'utf-8' : 'iso-8859-1' ).';' );
	$datachr = ( $isUTF8? utf8enc( $data ) : $data );
	echo $isJSON? json_encode( $datachr ) : serialize( $datachr );
}

function utf8enc( $data ) {
	if ( !is_array( $data ) ) return $data;
	$result = array();
	foreach ( $data as $k => $v ) $result[sconv($k)] = is_array($v) ? utf8enc($v) : sconv($v);
	return $result;
}

function sconv( $obj ) {
	return is_string( $obj )? utf8_encode($obj) : $obj;
}

if ( !isset( $GLOBALS['phpgw_info'] ) ) {
	$GLOBALS['phpgw_info']['flags'] = array(
		'currentapp' => 'expressoMail1_2',
		'nonavbar'   => true,
		'noheader'   => true,
	);
}

require_once '../header.session.inc.php';

// Get Headers
$headers = headers_list();

// Explode action from cExecuteForm function
$cExecuteFormReturn = false;

if ( isset( $_POST['_action'] ) ) {
	if ( $_FILES ) {
		$count_files = $_POST['countFiles'];
		$array_files = array();
		for ( $idx = 1; $idx <= $count_files; $idx++ ) {
			if ( isset($_FILES['file_'.$idx]) && !$_FILES['file_'.$idx]['error'] ) $array_files[] = $_FILES['file_'.$idx];
		}
		$_POST['FILES'] = $array_files;
	}
	list( $app, $class, $method ) = explode( '.', @$_POST['_action'] );
	$cExecuteFormReturn = true;
}
// Explode action from cExecute function
else if ( isset( $_GET['action'] ) && $_GET['action'] ) list( $app, $class, $method ) = explode( '.', @$_GET['action'] );
else if ( isset( $_POST['action'] ) && $_POST['action'] ) list( $app, $class, $method ) = explode( '.', @$_POST['action'] );
// NO ACTION
else return $_SESSION['response'] = 'Post-Content-Length';

// Load dinamically class file.
include_once( ( ($app == '$this')? '' : '../'.$app.'/' ).'inc/class.'.$class.'.inc.php' );

// Create new Object  (class loaded).
$obj = new $class;

// Prepare parameters for execution.
$params = array();

// If array $_POST is not null , the submit method is POST.
if ( $_POST ) $params = $_POST;
// If array $_POST is null , and the array $_GET > 1, the submit method is GET.
else if ( count( $_GET ) > 1) {
	array_shift( $_GET );
	$params = $_GET;
}

// if params is not empty, then class method with parameters.
$result = array();
if ( $params ) $result = $obj->$method( $params );
else $result = $obj->$method();

if ( $cExecuteFormReturn ) $_SESSION['response'] = $result;
else set_response( $result, isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'json' ), false );
