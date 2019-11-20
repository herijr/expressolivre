<?php

// Load the lang of the module.

//Expresso offline, quando o gears sincroniza com uma nova versao, nao eh dado acesso as sessoes do usuario, e eh preciso o nome do arquivo de linguagens na session abaixo.
if ( isset( $offline_language ) ) {
	$_SESSION['phpgw_info']['expressomail']['user']['preferences']['common']['lang'] = $offline_language;
}
$f_lang = 'setup/phpgw_'.$_SESSION['phpgw_info']['expressomail']['user']['preferences']['common']['lang'].'.lang';

function get_js_src( $f_in ) {
	$array_keys = array();
	if ( file_exists( $f_in) ) {
		$fp = fopen($f_in,'r');
		while ( $data = fgets( $fp, 16000 ) ) {
			list( $message_id, $app_name, $null, $content ) = explode( "\t", substr( $data, 0, -1 ) );
			$array_keys[$message_id] = $content;
			$_SESSION['phpgw_info']['expressomail']['lang'][$message_id] = $content;
		}
		fclose($fp);
	}
	$buf = '';
	foreach ( $array_keys as $key => $value )
		$buf .= "array_lang['".str_replace("'","\'",strtolower($key))."'] = '".str_replace("'","\'",$value)."';\n";
	return $buf;
}
if ( isset( $GLOBALS['phpgw']->js ) ) {
	$f_JS = 'js/lang/'.basename($f_lang).'.js';
	$f_out    = dirname( $_SERVER['SCRIPT_FILENAME'] ).SEP.$f_JS;
	$stat1 = stat( $f_lang );
	$stat2 = stat( $f_out );
	if ( $stat2 === false || $stat1['mtime'] !== $stat2['mtime'] ) {
		file_put_contents( $f_out, get_js_src( $f_lang ) );
		touch( $f_out,$stat1['mtime'] );
	} else get_js_src( $f_lang );
	$GLOBALS['phpgw']->js->add( 'file', $f_JS );
} else {
	echo '<script type="text/javascript">'.get_js_src( $f_lang ).'</script>';
}