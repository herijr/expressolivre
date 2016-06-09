<?php

require_once '../../header.session.inc.php';

if ( $ftmp = $_FILES['import_file']['tmp_name'] ) {
	
	// This script upload an CSV file to import contacts ....
	$temp_dir = empty($_SESSION['phpgw_info']['server']['temp_dir'])? '/tmp' : $_SESSION['phpgw_info']['server']['temp_dir'];
	$fname = $temp_dir.'/contacts_'.md5(microtime()).'.swp';
	
	if ( move_uploaded_file($ftmp, $fname) ) $_SESSION['contactcenter']['importCSV'] = $fname;
	
} else if ( $_GET['file_name'] ) {
	
	// ... or download an CSV file to export contacts.
	$file_name = $_GET['file_name'];
	$file_path = $_GET['file_path'];
	header( 'Content-Type: application/octet-stream' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Length: '.filesize($file_path) );
	header( 'Content-disposition: attachment; filename='.$file_name );
	
	readfile( $file_path );
}