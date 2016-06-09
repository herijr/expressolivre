<?php
function load_lang()
{
	if ( !$_SESSION['phpgw_info']['calendar']['langAlarm'] ) {
		$array_keys = array();
		$fn = '../setup/phpgw_alarm_'.$GLOBALS['phpgw_info']['user']['preferences']['common']['lang'].'.lang';
		if ( file_exists( $fn ) ) {
			$fp = fopen( $fn, 'r' );
			while ( $data = fgets( $fp, 16000 ) ) {
				list( $message_id, $app_name, $null, $content ) = explode( "\t", substr( $data, 0, -1 ) );
				$_SESSION['phpgw_info']['calendar']['langAlarm'][$message_id] =  $content;
			}
			fclose( $fp );
		}
	}
}
