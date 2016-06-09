<?php

	require_once '../../header.session.inc.php'; 

	$username = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
	$password = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
	$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
	$imap_port 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
	$msg_folder = $_GET['msg_folder'];
	$msg_folder = mb_convert_encoding($msg_folder,"UTF7-IMAP", mb_detect_encoding($msg_folder, "UTF-8, ISO-8859-1", true));
	if ($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes')
	{
		$imap_options = '/tls/novalidate-cert';
	}
	else
	{
		$imap_options = '/notls/novalidate-cert';
	}
	
	if (is_array($_SESSION['phpgw_info']['expressomail']['email_server']))
	{
		$mb = imap_open("{".$imap_server.":".$imap_port.$imap_options."}".$msg_folder, $username, $password);
	
		if ($mb)
		{
			$msgno = $_GET['msg_num'];
			$embedded_part = $_GET['msg_part'];
	
			$embedded_body = imap_fetchbody($mb, $msgno, $embedded_part, FT_UID);
	
			header("Content-Type: image/jpeg");
			header("Content-Disposition: inline");
			echo imap_base64($embedded_body);
		}
	}
?>
