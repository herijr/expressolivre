<?php
require_once '../../header.session.inc.php';

if ( !( isset( $_REQUEST['msg_folder'] ) && isset( $_REQUEST['msg_num'] ) && isset( $_REQUEST['msg_part'] ) ) && is_array( $_SESSION['phpgw_info']['expressomail']['email_server'] ) ) exit;

// TODO: Remove user password from session

$folder      = mb_convert_encoding( $_REQUEST['msg_folder'], 'UTF7-IMAP', mb_detect_encoding( $_REQUEST['msg_folder'], 'UTF-8, ISO-8859-1', true ) );
$msg_num     = $_REQUEST['msg_num'];
$msg_part    = $_REQUEST['msg_part'];
$is_thumb    = isset( $_REQUEST['thumb'] );
$username    = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
$password    = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
$imap_port   = $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
$imap_opts   = '/'.($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes'? '' : 'no' ).'tls/novalidate-cert';

if ( ( $mailbox = imap_open( '{'.$imap_server.':'.$imap_port.$imap_opts.'}'.$folder, $username, $password ) ) === false ) exit;
if ( ( $header  = imap_bodystruct( $mailbox, imap_msgno( $mailbox, $msg_num ), $msg_part ) ) === false ) exit;
if ( ! ( $header->type === TYPEIMAGE && $header->encoding === ENCBASE64 ) ) exit;

$out_type = strtolower( isset( $_REQUEST['file_type'] )? $_REQUEST['file_type'] : $header->subtype );
$out_type = in_array( $out_type, array( 'jpg', 'jpeg', 'jpe', 'jif', 'jfif', 'jfi' ) )? 'jpeg' : $out_type;
$out_type = in_array( $out_type, array( 'gif', 'wbmp', 'png' ) )? $out_type : 'jpeg';

$body = imap_base64( imap_fetchbody( $mailbox, $msg_num, $msg_part, FT_UID ) );

if ( $is_thumb || $out_type !== strtolower( $header->subtype ) ) {
	
	$img = @imagecreatefromstring( $body );
	if ( $img === false ) $out_type = $header->subtype;
	else {
		if ( $is_thumb ) {
			$width   = imagesx( $img );
			$height  = imagesy( $img );
			$twidth  = 160; # width of the thumb 160 pixel
			$theight = max( $twidth * $height / $width, 10 ); # calculate height
			$thumb   = imagecreatetruecolor( $twidth, $theight );
			imagecopyresized( $thumb, $img, 0, 0, 0, 0,$twidth, $theight, $width, $height ); # resize image into thumb
			$img = $thumb;
		}
		ob_start();
		switch ( $out_type ) {
			case 'jpeg': imagejpeg( $img, NULL, 75 ); break; // 75 is default IJG quality 
			case 'gif':  imagegif(  $img, NULL );     break;
			case 'png':  imagepng(  $img, NULL, 9 );  break; // 9 max compression
			case 'wbmp': imagewbmp( $img, NULL );     break;
		}
		$body = ob_get_contents();
		ob_end_clean();
	}
}

header( 'Content-Type: image/'.$out_type );
header( 'Content-Disposition: inline' );
echo $body;
