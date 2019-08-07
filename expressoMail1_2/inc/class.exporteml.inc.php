<?php
/***************************************************************************************\
* Export EML Format Message Mail														*
* Written by Nilton Neto (Celepar) <niltonneto@celepar.pr.gov.br>						*
* ------------------------------------------------------------------------------------	*
*  This program is free software; you can redistribute it and/or modify it				*
*   under the terms of the GNU General Public License as published by the				*
*  Free Software Foundation; either version 2 of the License, or (at your				*
*  option) any later version.															*
\****************************************************************************************/

class ExportEml
{
	protected $_imap_stream             = false;
	
	public function __construct()
	{
		include_once dirname( __FILE__ ).'/../../header.inc.php';
		
		// Disable all output buffers
		ini_set( 'output_buffering', 'off' );
		ini_set( 'zlib.output_compression', false );
		ini_set( 'implicit_flush', true );
		ob_implicit_flush( true );
		
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false );
		header( 'Content-Transfer-Encoding: binary' );
	}
	
	/**
	 * Export messages: Create a zip file with messages.eml
	 * If none message found, return file not found.
	 */
	public function exportMessages( $params )
	{
		$numFiles = 0;
		$dir_exp  = false;
		$folders  = isset( $params['folders']        )? $params['folders'] : false;
		$kpdir    = isset( $params['keep_directory'] );
		
		if ( !count( $folders ) ) return $this->_resultNotFound();
		
		foreach ( $folders as $folder => $msgs ) {
			// Connect to imap folder
			if ( !$this->_getImapStream( $folder ) ) continue;
			
			// Format internal zip folder
			$ifolder = $kpdir? trim( preg_replace( '/^INBOX/', '', $folder ), '/' ).'/' : '';
			
			// Get all messages
			if ( $msgs === '*' ) $msgs = imap_search( $this->_getImapStream(), 'ALL', SE_UID );
			
			// One select on folder
			if ( is_numeric( $msgs ) ) $msgs = (array)$msgs;
			
			if ( is_array( $msgs ) ) {
				if ( count( $folders) === 1 && count( $msgs ) === 1 ) {
					$id    = current( $msgs );
					$fname = $this->_squeeze( '_', $this->_subjectToFilename( $id ) ).'.eml';
					$data  = $this->_getSourceMessage( $id );
					header( 'Content-Type: message/rfc822' );
					$this->_setContentDisposition( $fname );
					header( 'Content-Length: '.strlen( $data ) );
					echo $data;
					ob_flush();
					flush();
					exit;
				}
				
				foreach ( $msgs as $key => $id ) {
					
					// Lazy make temporary directory
					if ( $dir_exp === false ) $dir_exp = $this->_makeTmpDir();
					
					// Add message to zip
					$num   = ( $kpdir? $key : $numFiles ) + 1;
					$fname = $dir_exp.'/'.$ifolder.$this->_squeeze( '_', $this->_subjectToFilename( $id ).'_'.$num.'.eml' );
					
					if ( !file_exists( dirname( $fname ) ) ) mkdir( dirname( $fname ), 0770, true );
					
					file_put_contents ( $fname, $this->_getSourceMessage( $id ) );
					$numFiles++;
				}
			}
		}
		
		if ( $dir_exp === false ) return $this->_resultNotFound();
		
		//exec( 'nice '.escapeshellarg(PHPGW_SERVER_ROOT.'/prototype/bin/zip/zip').' '.escapeshellarg($dir_exp) );
		exec( 'cd '.escapeshellarg($dir_exp).' && find -type f ! -name messages.zip | nice zip -9 messages.zip -@' );
		
		if ( !file_exists( $dir_exp.'/messages.zip' ) ) exit;
		header( 'Content-Type: application/zip' );
		$this->_setContentDisposition( lang( 'messages' ).'.zip' );
		header( 'Content-Length: '.filesize( $dir_exp.'/messages.zip' ) );
		readfile( $dir_exp.'/messages.zip' );
		$this->_removeTmpDir( $dir_exp );
		ob_flush();
		flush();
		exit;
	}
	
	/**
	 * Export Attachments
	 * @param array $params
	 */
	public function exportAttachments( $params = array() )
	{
		if ( !$this->_getImapStream( $params['folder'] ) ) return $this->_resultNotFound();

		include_once 'class.message_reader.inc.php';
		$mail_reader = new MessageReader();
		$info        = $mail_reader->setMessage( $this->_getImapStream(), $params['folder'], $params['msg_number'] )->getAttachInfo( false, true );

		if ( $params['section'] === '*' ) {

			$dir_exp = false;
			foreach ( $info as $attch ) {

				// Lazy make temporary directory
				if ( $dir_exp === false ) $dir_exp = $this->_makeTmpDir();

				$obj      = $mail_reader->getAttach( $attch->section );
				$filename = $obj->filename;
				$_count   = 1;
				while ( file_exists( $dir_exp.'/'.$filename ) ) {
					$infoFile = pathinfo( $obj->filename );
					$filename = $infoFile['filename'].'_'.$_count.'.'.$infoFile['extension'];
					$_count++;
				}

				file_put_contents( $dir_exp.'/'.$filename, $obj->data );
			}

			if ( $dir_exp === false ) return $this->_resultNotFound();

			exec( 'cd '.escapeshellarg($dir_exp).' && find -type f ! -name messages.zip | nice zip -9 messages.zip -@' );

			header( 'Content-Type: application/zip' );
			$this->_setContentDisposition( lang( 'attachments' ).'.zip' );
			header( 'Content-Length: '.filesize( $dir_exp.'/messages.zip' ) );
			readfile( $dir_exp.'/messages.zip' );

			$this->_removeTmpDir( $dir_exp );

		} else {
			if ( array_search( $params['section'], array_map( function( $obj ) { return $obj->section; }, $info ) ) === false ) return $this->_resultNotFound();
			$obj = $mail_reader->getAttach( $params['section'] );
			
			header( 'Content-Type: '.$this->_getFileType( $obj->filename ) );
			$this->_setContentDisposition( $obj->filename );
			header( 'Content-Length: '.strlen( $obj->data ) );

			ob_flush();
			flush();
			
			echo $obj->data;
		}
		ob_flush();
		flush();
		exit;
	}
	
	private function _getImapServer()
	{
		return $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'].(
			isset($_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'])?
				':'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'] : ''
		);
	}
	
	private function _getImapOpts()
	{
		return '/novalidate-cert'.(
			($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes')? '/tls' : '/notls'
		);
	}

	private function _getImapMailbox( $folder = null )
	{
		return '{'.$this->_getImapServer().$this->_getImapOpts().'}'.$this->_toMaibox( $folder );
	}

	private function _getImapStream( $folder = null )
	{
		if ( $folder !== null ) $this->_closeImap();
		if ( !$this->_imap_stream ) {
			$this->_imap_stream = imap_open(
				$this->_getImapMailbox( $folder ),
				$_SESSION['phpgw_info']['expressomail']['user']['userid'],
				$_SESSION['phpgw_info']['expressomail']['user']['passwd']
			);
		}
		return $this->_imap_stream;
	}
	
	private function _getSourceMessage( $msg_number )
	{
		return imap_fetchheader( $this->_getImapStream(), $msg_number, FT_UID ).
			"\r\n\r\n".
			imap_body( $this->_getImapStream(), $msg_number, FT_UID | FT_PEEK );
	}
	
	private function _getFileType( $fname )
	{
		switch ( strtolower( substr( $fname, strrpos( $fname, '.' ) ) ) ) {
			case '.eml': return 'message/rfc822';
			case '.asf': return 'video/x-ms-asf';
			case '.avi': return 'video/avi';
			case '.doc': return 'application/msword';
			case '.zip': return 'application/zip';
			case '.xls': return 'application/vnd.ms-excel';
			case '.gif': return 'image/gif';
			case '.png': return 'image/png';
			case '.jpg': case 'jpeg': return 'image/jpeg';
			case '.wav': return 'audio/wav';
			case '.mp3': return 'audio/mpeg3';
			case '.mpg': case 'mpeg': return 'video/mpeg';
			case '.rtf': return 'application/rtf';
			case '.htm': case 'html': return 'text/html';
			case '.xml': return 'text/xml';
			case '.xsl': return 'text/xsl';
			case '.css': return 'text/css';
			case '.php': return 'text/php';
			case '.asp': return 'text/asp';
			case '.pdf': return 'application/pdf';
			case '.txt': return 'text/plain';
			case '.log': return 'text/plain';
			case '.wmv': return 'video/x-ms-wmv';
			case '.sxc': return 'application/vnd.sun.xml.calc';
			case '.odt': return 'application/vnd.oasis.opendocument.text';
			case '.stc': return 'application/vnd.sun.xml.calc.template';
			case '.sxd': return 'application/vnd.sun.xml.draw';
			case '.std': return 'application/vnd.sun.xml.draw.template';
			case '.sxi': return 'application/vnd.sun.xml.impress';
			case '.sti': return 'application/vnd.sun.xml.impress.template';
			case '.sxm': return 'application/vnd.sun.xml.math';
			case '.sxw': return 'application/vnd.sun.xml.writer';
			case '.sxq': return 'application/vnd.sun.xml.writer.global';
			case '.stw': return 'application/vnd.sun.xml.writer.template';
			case '.ps':  return 'application/postscript';
			case '.pps': return 'application/vnd.ms-powerpoint';
			case '.odt': return 'application/vnd.oasis.opendocument.text';
			case '.ott': return 'application/vnd.oasis.opendocument.text-template';
			case '.oth': return 'application/vnd.oasis.opendocument.text-web';
			case '.odm': return 'application/vnd.oasis.opendocument.text-master';
			case '.odg': return 'application/vnd.oasis.opendocument.graphics';
			case '.otg': return 'application/vnd.oasis.opendocument.graphics-template';
			case '.odp': return 'application/vnd.oasis.opendocument.presentation';
			case '.otp': return 'application/vnd.oasis.opendocument.presentation-template';
			case '.ods': return 'application/vnd.oasis.opendocument.spreadsheet';
			case '.ots': return 'application/vnd.oasis.opendocument.spreadsheet-template';
			case '.odc': return 'application/vnd.oasis.opendocument.chart';
			case '.odf': return 'application/vnd.oasis.opendocument.formula';
			case '.odi': return 'application/vnd.oasis.opendocument.image';
			case '.ndl': return 'application/vnd.lotus-notes';
		}
		return 'application/octet-stream';
	}
	
	private function _subjectToFilename( $id )
	{
		// Header
		$header = imap_headerinfo( $this->_getImapStream(), imap_msgno( $this->_getImapStream(), $id ), 80, 255 );
		// Subject
		$subject = trim( $this->_str_decode( $header->fetchsubject ) );
		$subject = ( strlen( $subject ) == 0 )? lang( 'No Subject' ) : $subject;
		$subject = $this->_squeeze( '_', $this->_squeeze( ' ', $subject ) );
		$subject = $this->_stripWinBadChars( $subject );
		return $subject;
	}

	private function _stripWinBadChars( $filename )
	{
		return preg_replace( '/[<>:"|?*\/\\\]/', '-', preg_replace( '/[\x00-\x1F\x7F]/u', '', $filename ) );
	}

	private function _squeeze( $ch, $str ) {
		return preg_replace( '/(['.preg_quote($ch,'/').'])\1+/', '$1', $str );
	}

	private function _str_decode( $str, $charset = false )
	{
		if ( preg_match( '/=\?[\w-#]+\?[BQ]\?[^?]*\?=/', $str ) ) $str = mb_decode_mimeheader( $str );
		return $this->_toUTF8( $str, $charset );
	}

	private function _toMaibox( $folder )
	{
		return $this->_toUTF8( $folder, false, 'UTF7-IMAP' );
	}

	private function _toUTF8( $str, $charset = false, $to = 'UTF-8' )
	{
		return mb_convert_encoding( $str, $to, ( $charset === false? mb_detect_encoding( $str, 'UTF-8, ISO-8859-1', true ) : $charset ) );
	}

	private function _setContentDisposition( $filename )
	{
		header( 'Content-Disposition: attachment; filename="'.$this->_mimeEncode( $filename ).'"' );
	}

	private function _mimeEncode( $str )
	{
		$str = $this->_str_decode( $str );
		$qpe = str_replace( "=\r\n", '', quoted_printable_encode( $str ) );
		$enc = ( ceil( 4*strlen( $str )/3 ) < strlen( $qpe ) )? 'B' : 'Q';
		return '=?UTF-8?'.$enc.'?'.( $enc=='Q'? $qpe : base64_encode( $str ) ).'?=';
	}

	private function _resultNotFound()
	{
		header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404 );
		ob_flush();
		flush();
		exit;
	}

	private function _makeTmpDir()
	{
		$tmp_dir = ( isset( $_SESSION['phpgw_info']['server']['temp_dir'] ) ) ? $_SESSION['phpgw_info']['server']['temp_dir']: '/tmp';
		$acc_id = isset( $_SESSION['phpgw_info']['expressomail']['user']['account_id'] )? $_SESSION['phpgw_info']['expressomail']['user']['account_id'] : '';
		$tmstp = time();
		
		if ( empty($tmp_dir) || empty($acc_id) || empty($tmstp) ) return $this->_resultNotFound();
		
		$new_dir = implode( '/', array( $tmp_dir, $acc_id, $tmstp ) );
		
		if ( file_exists( $new_dir ) || !mkdir( $new_dir, 0770, true ) ) return $this->_resultNotFound();
		
		return $new_dir;
	}
	
	private function _removeTmpDir( $dir )
	{
		if ( is_dir( $dir ) ) {
			exec( 'rm -rf '.escapeshellarg( $dir ) );
			if ( is_dir( dirname( $dir ) ) ) rmdir( dirname( $dir ) );
		} else return false;
		return true;
	}
	
	private function _closeImap()
	{
		$return = false;
		
		if( is_resource($this->_imap_stream) ){
			$errors = imap_errors();
			if(is_array($errors)){
				if( preg_match('/SECURITY PROBLEM: insecure server advertised AUTH=PLAIN/i', $errors[0]) === false){
				  throw new Exception('IMAP error detected');
				}
			}
			imap_close( $this->_imap_stream );
			$return = true;
		}
		
		return $return;
	}
	
	public function __destruct()
	{
		$this->_closeImap();
	}
}
