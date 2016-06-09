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
	 * 
	 * @param array $params = array(
	 *    'json' => '{"INBOX":[1,2,3,...],"INBOX/subdir":[1,2,...],"INBOX/other":[...,100,...]}',
	 *    'flags' => 'keep_directory',
	 * )
	 */
	public function exportMessages( $params = array() )
	{
		$numFiles = 0;
		$flags    = isset( $params['flags'] )? explode( ',', $params['flags'] ) : array();
		$kpdir    = in_array( 'keep_directory', $flags );
		$folders  = isset( $params['json'] )? json_decode( urldecode( $params['json'] ), true ) : array();
		$dir_exp  = false;
		
		if ( !count( $folders ) ) return $this->_resultNotFound();
		
		foreach ( $folders as $folder => $msgs ) {
			// Connect to imap folder
			if ( !$this->_getImapStream( $folder ) ) continue;
			
			// Format internal zip folder
			$ifolder = $kpdir? trim( preg_replace( '/^INBOX/', '', $folder ), '/' ).'/' : '';
			
			// Get all messages
			if ( $msgs === '*' ) $msgs = imap_search( $this->_getImapStream(), 'ALL', SE_UID );
			
			// One select on folder
			if ( is_int( $msgs ) ) $msgs = (array)$msgs;
			
			if ( is_array( $msgs ) ) {
				
				foreach ( $msgs as $key => $id ) {
					
					// Lazy make temporary directory
					if ( $dir_exp === false ) $dir_exp = $this->_makeTmpDir();
					
					// Add message to zip
					$num = ( $kpdir? $key : $numFiles ) + 1;
					$fname = $dir_exp.'/'.$this->_utf8Encode($ifolder.$this->_squeeze( '_', $this->_subjectToFilename( $id ) . '_' . $num . '.eml' ));
					
					if ( !file_exists( dirname( $fname ) ) ) mkdir( dirname( $fname ), 0770, true );
					
					file_put_contents ( $fname, $this->_getSourceMessage( $id ) );
					$numFiles++;
				}
			}
		}
		
		if ( $dir_exp === false ) return $this->_resultNotFound();
		
		//exec( 'nice '.escapeshellarg(PHPGW_SERVER_ROOT.'/prototype/bin/zip/zip').' '.escapeshellarg($dir_exp) );
		exec( 'cd '.escapeshellarg($dir_exp).' && find -type f ! -name messages.zip | nice zip -9 messages.zip -@' );
		
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename='.lang( 'messages' ).'.zip;' );
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
		$args       = isset($params['json'])? json_decode( urldecode( $params['json'] ), true ) : array();
		$folder     = isset($args['folder'])? $args['folder'] : false;
		$msg_number = isset($args['msg_number'])? $args['msg_number'] : false;
		$section    = isset($args['section'])? $args['section'] : false;
		
		if ( !$this->_getImapStream( $folder ) ) return $this->_resultNotFound();
		
		include_once 'class.imap_attachment.inc.php';
		$imap_att = new imap_attachment();
		$attachments = $imap_att->download_attachmentByPid( $this->_getImapStream(), $msg_number );
		if ( !count( $attachments ) ) return $this->_resultNotFound();
		
		if ( $section === '*' ) {
			
			$dir_exp = false;
			
			foreach ( $attachments as $attch ) {
				
				// Lazy make temporary directory
				if ( $dir_exp === false ) $dir_exp = $this->_makeTmpDir();
				
				file_put_contents (
					$dir_exp.'/'.$this->_utf8Encode( trim( str_replace('\\', '/', $attch['name']), '/' ) ),
					$this->_getAttachmentContent( $msg_number, $attch['pid'], $attch['encoding'] )
				);
			}
			
			if ( $dir_exp === false ) return $this->_resultNotFound();
			
			//exec( 'nice '.escapeshellarg(PHPGW_SERVER_ROOT.'/prototype/bin/zip/zip').' '.escapeshellarg($dir_exp) );
			exec( 'cd '.escapeshellarg($dir_exp).' && find -type f ! -name messages.zip | nice zip -9 messages.zip -@' );
			
			header( 'Content-Type: application/zip' );
			header( 'Content-Disposition: attachment; filename='.lang( 'messages' ).'.zip;' );
			header( 'Content-Length: '.filesize( $dir_exp.'/messages.zip' ) );
			readfile( $dir_exp.'/messages.zip' );
			
			$this->_removeTmpDir( $dir_exp );
			
		} else {
			
			if ( !( $attch = isset( $attachments[$section] )? $attachments[$section] : false ) ) return $this->_resultNotFound();
			
			$content = $this->_getAttachmentContent( $msg_number, $attch['pid'], $attch['encoding'] );
			header( 'Content-Type: '.$this->_getFileType( $attch['name'] ) );
			header( 'Content-Disposition: attachment; filename="'.$attch['name'].'";' );
			header( 'Content-Length: '.strlen( $content ) );

			ob_flush();
			flush();
			
			echo $content;
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
		$folder = ( $folder === null )? '' : mb_convert_encoding( $folder, 'UTF7-IMAP',
			mb_detect_encoding( $folder, 'UTF-8, ISO-8859-1', true )
		);
		return '{'.$this->_getImapServer().$this->_getImapOpts().'}'.$folder;
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
	
	private function _getAttachmentContent( $msg_number, $section, $encoding = 'none' )
	{
		$content = imap_fetchbody( $this->_getImapStream(), $msg_number, $section, FT_UID | FT_PEEK );
		switch ( $encoding ) {
			case 'base64':           return base64_decode( $content );
			case 'quoted-printable': return quoted_printable_decode( $content );
		}
		return $content;
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
			case '.eml': return 'text/plain';
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
		$subject = $this->_stripWinBadChars( trim( $this->_decode_subject( $header->fetchsubject ) ) );
		$subject = $this->_squeeze( '_', str_replace( ' ', '_', $this->_squeeze( ' ', $subject ) ) );
		$subject = ( strlen($subject) > 60 ) ? substr( $subject, 0, 59 ) : $subject;
		$subject = ( strlen($subject) == 0 ) ? $this->_squeeze( '_', lang('No Subject')) : $subject;
		
		return $subject;
	}
	
	private function _stripWinBadChars( $filename )
	{
		$bad = array_merge( array_map( 'chr', range( 0, 31 ) ), array('<', '>', ':', '"', '/', '\\', '|', '?', '*') );
		
		return str_replace( $bad, '', $filename );
	}
	
	private function _squeeze( $ch, $str ) {
		return preg_replace( '/(['.preg_quote($ch,'/').'])\1+/', '$1', $str );
	}
	
	private function _decode_subject( $string )
	{
		$lstr = strtolower( $string );
		$result = '';
		
		if ( strpos( $lstr, '=?utf-8' ) !== false ) {
			
			$elements = imap_mime_header_decode( $string );
			
			foreach ( $elements as $el ) {
				$charset = $el->charset;
				$text    = $el->text;
				
				if ( !strcasecmp( $charset, 'utf-8' ) || !strcasecmp( $charset, 'utf-7' ) ) {
					$text = iconv( $charset, 'ISO-8859-1', $text );
				}
				$result .= $text;
			}
			
		} else if ( ( strpos( $lstr, '=?iso-8859-1' ) !== false ) || ( strpos( $lstr, '=?windows-1252') !== false ) ) {
			
			$elements = imap_mime_header_decode( $string );
			
			foreach ( $elements as $el ) $result .= $el->text;
			
		} else $result = $string;
		
		return $result;
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
	
	/**
	 * Encode to UTF-8 with check
	 * @param string $str
	 * @return string
	 */
	private function _utf8Encode( $str )
	{
		return ( mb_check_encoding( $str, 'UTF-8' ) )? $str : utf8_encode( $str );
	}
	
	private function _closeImap()
	{
		if ( !$this->_imap_stream ) return false;
		imap_close( $this->_imap_stream );
		$this->_imap_stream = false;
		return true;
	}
	
	public function __destruct()
	{
		$this->_closeImap();
	}
}
