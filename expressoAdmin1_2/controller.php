<?php
$GLOBALS['phpgw_info'] = array(
	'flags' => array(
		'currentapp'	=> 'expressoAdmin1_2',
		'nonavbar'		=> true,
		'noheader'		=> true,
	),
);
include('../header.inc.php');

// Prepare parameters for execution.
$params = null;

// Explode action from cExecuteForm function
$cExecuteFormReturn = false;

if (isset($_POST['_action']) && $_POST['_action']) {
	
	if ($_FILES) {
		
		$count_files = $_POST['countFiles'];
		$array_files = array();
		for ($idx = 1; $idx <= $count_files; $idx++) {
			if (isset($_FILES['file_'.$idx]) && !$_FILES['file_'.$idx]['error'])
				$array_files[] = $_FILES['file_'.$idx];
		}
		$_POST['FILES'] = $array_files;
	}
	
	$action = $_POST['_action'];
	unset($_POST['_action']);
	$params = $_POST;
	$cExecuteFormReturn = true;
	
} else if (isset($_GET['action']) && $_GET['action']) {
	
	$action = $_GET['action'];
	unset($_GET['action']);
	$params = $_GET;
	
} else return $_SESSION['response'] = 'false';

// Load dinamically class file.
if (strpos($action, '/') === false) {
	
	list($app,$class,$method) = explode('.',$action);
	
	include_once(($app == '$this')? 'inc/class.'.$class.'.inc.php' : '../'.$app.'/inc/class.'.$class.'.inc.php');
	
	// Create new Object  (class loaded).
	$obj = new $class;
	
	// Call method
	//if (in_array($method,$obj->public_functions()))
	$result = $obj->$method( $params );
	
	// Return result serialized.
	if ($cExecuteFormReturn) $_SESSION['response'] = $result;
	else echo serialize($result);
	
} else if ( substr($action, 0, 5) == '$this' ) {
	
	include_once(substr($action, 6).'.php');
	
}
