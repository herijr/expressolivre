<?php

require_once '../../header.session.inc.php';

header( 'Content-Type: image/jpeg' );

if ( isset($_GET['msg_num']) )
{
	if( isset($_GET['msg_part']) && isset($_GET['msg_folder']) )
	{	
		$username     = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
		$password     = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
		$imap_server  = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
		$imap_port    = $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
		$use_tls      = $_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes';
		$imap_options = ( $use_tls? '/tls' : '/notls' ).'/novalidate-cert';
		
		$msg_num      = $_GET['msg_num'];
		$msg_part     = $_GET['msg_part'];
		$msg_folder   = $_GET['msg_folder'];
		$encode       = mb_detect_encoding( $msg_folder, 'UTF-8, ISO-8859-1', true );
		$msg_folder   = mb_convert_encoding( $msg_folder, 'UTF7-IMAP', $encode );
		
		$mb = imap_open( '{'.$imap_server.':'.$imap_port.$imap_options.'}'.$msg_folder, $username, $password );
		
		$image_mail = imap_fetchbody( $mb, $msg_num, $msg_part, FT_UID );
		
		header( 'Content-Disposition: inline' );
		
		$image = imap_base64( $image_mail );
		
		echo $image;
	}
}