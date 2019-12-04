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

$GLOBALS['phpgw_info']['flags'] = array(
	'currentapp' => 'expressoAdmin1_2',
	'nonavbar'   => true,
	'noheader'   => true,
);

include('../header.inc.php');

// Get Headers
$headers = headers_list();

// Prepare parameters for execution.
$params = null;

$action = "";

// Explode action from cExecuteForm function
$cExecuteFormReturn = false;

if( isset($_POST['_action']) )
{
	if ($_FILES)
	{
		$count_files = $_POST['countFiles'];
		$array_files = array();
		for ($idx = 1; $idx <= $count_files; $idx++)
		{
			if (isset($_FILES['file_'.$idx]) && !$_FILES['file_'.$idx]['error'])
			{
				$array_files[] = $_FILES['file_'.$idx];
			}
		}
		$_POST['FILES'] = $array_files;
	}
	
	$action = $_POST['_action'];
	unset( $_POST['_action'] );
	$params = $_POST;
	$cExecuteFormReturn = true;
	
}
else if( isset($_POST['action']) )
{
	$action = $_POST['action'];
	unset( $_POST['action'] );
	$params = $_POST;
}
else if( isset($_GET['action']) )
{
	$action = $_GET['action'];
	unset( $_GET['action'] );
	$params = $_GET;
}
else return $_SESSION['response'] = 'Post-Content-Length';

// Load dinamically class file.
if( strpos($action, '/') === false)
{
	list($app,$class,$method) = explode( '.' , $action );
	
	include_once( ( $app == '$this' ) ? 'inc/class.'.$class.'.inc.php' : '../'.$app.'/inc/class.'.$class.'.inc.php' );
	
	// Create new Object  (class loaded).
	$obj = new $class;
	
	// Call method
	$result = array();
	$result = ( $params ) ? $obj->$method( $params ) : $obj->$method();
	
	// Return result serialized.
	if ( $cExecuteFormReturn ) $_SESSION['response'] = $result;
	else set_response( $result, isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'json' ), false );

	session_write_close();
}
else if ( substr($action, 0, 5) == '$this' )
{
	include_once(substr($action, 6).'.php');
}