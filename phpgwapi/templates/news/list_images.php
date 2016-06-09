<?php

$base_dir  = 'phpgwapi/templates/news/';
$img_dir   = 'src_images/';
$conf_name = 'config_images.ini';
$dh        = opendir( $img_dir );
$files     = array();
$finfo     = finfo_open( FILEINFO_MIME_TYPE ); 

while ( ( $filename = readdir( $dh ) ) !== false )
	if( is_file( $img_dir.$filename ) && preg_match( '/^image/', finfo_file( $finfo, $img_dir . $filename ) ) )
		$files[] = array( 'name' => $base_dir.$img_dir.basename( $filename ) );

finfo_close( $finfo );

if ( !count( $files ) ) return false;

shuffle( $files );
$files = array_combine( array_map( function( $arr ) { return basename( $arr['name'] ); }, $files ), $files );

if ( is_file( $conf_name ) )
	foreach( parse_ini_file( $conf_name, true ) as $ini )
		if( isset( $files[$ini['name']]) )
			foreach ( array( 'text', 'title', 'link' ) as $field )
				$files[$ini['name']][$field]  = ( $ini[$field] )? utf8_encode( $ini[$field] ) : '';

echo json_encode( $files );
