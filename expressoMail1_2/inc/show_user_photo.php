<?php

require_once '../../header.session.inc.php'; 

if( $_GET && isset($_GET['mail']) )
{
	$mail = $_GET['mail'];

	if( preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$/i", $mail) )
	{
		$ldapHost 	 = $_SESSION['phpgw_info']['expressomail']['server']['ldap_host'];
		$ldapContext = $_SESSION['phpgw_info']['expressomail']['server']['ldap_context'];
		$ldapDN   	 = $_SESSION['phpgw_info']['expressomail']['server']['ldap_root_dn'];
		$ldapPW   	 = $_SESSION['phpgw_info']['expressomail']['server']['ldap_root_pw'];

		$conn = ldap_connect( $ldapHost );

		if( $conn )
		{	
			ldap_set_option( $conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option( $conn, LDAP_OPT_REFERRALS, false );			
			ldap_bind( $conn , $ldapDN , $ldapPW );

			$search  = ldap_search( $conn, $ldapContext ,"(mail=$mail)" , array("jpegPhoto") );
			$entry   = ldap_first_entry($conn, $search);
			$contact = ldap_get_attributes($conn, $entry);

			if( $contact['jpegPhoto'] )
			{
				$contact['jpegPhoto'] = ldap_get_values_len ( $conn , $entry, "jpegPhoto" );
				$image = imagecreatefromstring ($contact['jpegPhoto'][0]); 
			}
			else
			{
				$loadFile = "../templates/default/images/photo.jpg";
				$image    = imagecreatefromjpeg($loadFile);
			}

			ldap_close( $conn);
			
			header("Content-Type: image/jpeg");

			if( $image )
			{
				$width   = imagesx($image);
				$height  = imagesy($image);
				$twidth  = 60; # width of the thumb 160 pixel
				$theight = $twidth * $height / $width; # calculate height
				$thumb   = imagecreatetruecolor ($twidth, $theight);
				imagecopyresampled($thumb, $image, 0, 0, 0, 0,$twidth, $theight, $width, $height); # resize image into thumb
				imagejpeg($thumb,NULL,80); # Thumbnail as JPEG
			}
		}
	} 
}
