<?php
session_id($_COOKIE['jupload']);
session_start();
$files = $_SESSION['juvar.files'];
unset($_SESSION['juvar.files']);
session_write_close();

include_once("../../header.session.inc.php");

$GLOBALS['phpgw_info']['flags'] = array
	(
		'currentapp'    => 'filemanager',
		'noheader'      => True,
		'nonavbar' => True,
		'nofooter'      => True,
		'noappheader'   => True,
		'enable_browser_class'  => True
	);


include_once("../../header.inc.php");

function convert_char( $String )
{
	$array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç"
	, "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	
	$array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c"
	, "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	
	return str_replace( $array1, $array2, $param);
}

$bo = CreateObject('filemanager.bofilemanager');

foreach ($files as $f)
{
	$newName = convert_char( $f['name'] );
	
	$_array = array(
			'from'		=> $f['fullName'],
			'to'		=> $newName,
			'relatives'	=> array(RELATIVE_NONE|VFS_REAL, RELATIVE_ALL)
	);
	
	if ( $bo->vfs->cp($_array) )
	{
		$bo->vfs->set_attributes(array(
			'string'		=> $newName,
			'relatives'		=> array( RELATIVE_ALL ),
			'attributes'	=> array( 'mime_type' => $f['mimetype'] )
		));
		
		$fullName = $f['fullName'];
		
		if( file_exists($fullName) )
		{
			exec("rm -f ".escapeshellcmd(escapeshellarg($fullName)));
		}
	}
}

echo "<script type='text/javascript' src='../js/after_upload.js'></script>";

?>