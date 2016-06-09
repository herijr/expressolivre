<?php

$isJSON = (preg_match('/application\/json/', $_SERVER['HTTP_ACCEPT']));

if ( empty($_SESSION['phpgw_info']['expressoAdmin']['lang']) ) {
	$fn = __DIR__.'/../setup/phpgw_'.$GLOBALS['phpgw_info']['user']['preferences']['common']['lang'].'.lang';
	if (file_exists($fn)) {
		$fp = fopen($fn,'r');
		while ($data = fgets($fp,16000)) {
			list($message_id,$app_name,$null,$content) = explode("\t",substr($data,0,-1));
			$_SESSION['phpgw_info']['expressoAdmin']['lang'][$message_id] = $content;
		}
		fclose($fp);
	}
}

$lang = array();
foreach($_SESSION['phpgw_info']['expressoAdmin']['lang'] as $message_id => $content) {
	$id = str_replace(" ", "_", (strtolower($message_id)) );
	$lang[$isJSON? utf8_encode($id) : $id] = $isJSON? utf8_encode($content) : $content;
}

if ( $isJSON ) header( 'Content-Type: application/json' );

echo $isJSON? json_encode( $lang ) : serialize( $lang );

exit;