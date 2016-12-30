<?php

	require_once '../../header.session.inc.php'; 
	$username = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
	$password = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
	$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
	$imap_port 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
	if ($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes')
	{
		$imap_options = '/tls/novalidate-cert';
	}
	else
	{
		$imap_options = '/notls/novalidate-cert';
	}

	$file_type = $_GET['file_type'];
	$msg_num = $_GET['msg_num'];
	$msg_part = $_GET['msg_part'];
	$msg_folder = $_GET['msg_folder'];
	$msg_folder = mb_convert_encoding($msg_folder,"UTF7-IMAP", mb_detect_encoding($msg_folder, "UTF-8, ISO-8859-1", true));
	$mb = imap_open("{".$imap_server.":".$imap_port.$imap_options."}".$msg_folder, $username, $password);

	$image_mail = imap_fetchbody($mb, $msg_num, $msg_part, FT_UID);
	$image = imap_base64($image_mail);
	$pic = @imagecreatefromstring ($image);
	if($pic !== FALSE) { 
		header("Content-Type: ".$file_type); 
		header("Content-Disposition: inline");
		$width = imagesx($pic);
		$height = imagesy($pic);
		$twidth = 160; # width of the thumb 160 pixel
		$theight = $twidth * $height / $width; # calculate height
		$theight =  $theight < 1 ? 1 : $theight; 
		$thumb = imagecreatetruecolor ($twidth, $theight);
		imagecopyresized($thumb, $pic, 0, 0, 0, 0,$twidth, $theight, $width, $height); # resize image into thumb
		imagejpeg($thumb,NULL,75); # Thumbnail as JPEG
	}
?>
