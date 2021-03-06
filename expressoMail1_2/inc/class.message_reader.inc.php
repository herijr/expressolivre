<?php

class MessageReader
{
	public static $type     = array(
		TYPETEXT            => 'text',
		TYPEMULTIPART       => 'multipart',
		TYPEMESSAGE         => 'message',
		TYPEAPPLICATION     => 'application',
		TYPEAUDIO           => 'audio',
		TYPEIMAGE           => 'image',
		TYPEVIDEO           => 'video',
		TYPEMODEL           => 'model',
		TYPEOTHER           => 'other',
	);

	public static $encoding = array(
		ENC7BIT             => '7bit',
		ENC8BIT             => '8bit',
		ENCBINARY           => 'binary',
		ENCBASE64           => 'base64',
		ENCQUOTEDPRINTABLE  => 'quoted-printable',
		ENCOTHER            => 'other',
	);


	private $_uid               = false;
	private $_mbox              = false;
	private $_folder            = false;
	private $_sections          = false;
	private $_isCripted         = false;
	private $_hash_vcalendar    = false;
	private $_content_plain     = array();
	private $_content_html      = array();
	private $_attachs           = array();
	private $_imagesCid         = array();

	private function _clear()
	{
		$this->_uid             = false;
		$this->_mbox            = false;
		$this->_folder          = false;
		$this->_sections        = false;
		$this->_isCripted       = false;
		$this->_hash_vcalendar  = false;
		$this->_content_plain   = array();
		$this->_content_html    = array();
		$this->_attachs         = array();
	}

	public function __construct()
	{
		$this->_clear();
	}

	protected function getPartType( $type )
	{
		return isset( self::$type[$type] )? self::$type[$type] :'unknown';
	}

	protected function getPartEncoding( $encoding )
	{
		return isset( self::$encoding[$encoding] )? self::$encoding[$encoding] :'unknown';
	}

	public function setMessage( $mbox, $folder, $uid )
	{
		if ( $this->_uid === $uid && $this->_folder === $folder ) return $this;
		$this->_clear();
		$this->_uid    = $uid;
		$this->_mbox   = $mbox;
		$this->_folder = $folder;
		$this->_load();
		return $this;
	}

	public function getInfo()
	{
		return (object)array(
			'uid'     => $this->_uid,
			'folder'  => $this->_folder,
			'attachs' => $this->getAttachInfo(),
		);
	}

	public function peekBody()
	{
		return $this->getBody( FT_PEEK );
	}

	public function getBody( $flag = false )
	{
		$is_html = count( $this->_content_html )? true : false;
		$obj     = (object)array( 'type' => $is_html? 'html' : 'plain' );
		$plain   = ( count( $this->_content_plain ) === 0 )? '' : implode( PHP_EOL, array_map( array( $this, '_fetchBody' ), $this->_content_plain, array_fill( 0, count( $this->_content_plain ), $flag ) ) );
		$obj->body = ( !$is_html )? $plain :
			( ( count( $this->_content_html ) === 0 )? '' :
				( ( count( $this->_content_html ) === 1 )? $this->_fetchBody( $this->_content_html[0], $flag ) :
					'<div>'.implode( '</div><div>', array_map( array( $this, '_fetchBody' ), $this->_content_html, array_fill( 0, count( $this->_content_html ), $flag ) ) ).'</div>'
				)
			);

		if(isset( $this->_sections[ $this->_content_html[0] ]->type ) ){	
			$obj->infoType = $this->_sections[ $this->_content_html[0] ]->type;
		}

		if ( $is_html ) {
			$obj->body = $this->_replaceCID( $obj->body );
			if( !empty( $plain ) ) $obj->body_alternative = $plain;
		}

		return $obj;
	}

	public function getAttachInfo( $section = false )
	{
		if ( $section !== false ){ return $this->_sections[$section]; }
		
		$result = array();
		
		foreach ( $this->_attachs as $section ){
			$result[] = $this->_sections[$section];
		}
		
		return $result;
	}

	public function getAttach( $section )
	{
		return $this->_getData( $this->_sections[$section] );
	}

	public function isCripted()
	{
		return $this->_isCripted;
	}

	public function getHashCalendar()
	{
		return $this->_hash_vcalendar;
	}

	private function _getData( &$node ) {
		if ( !isset( $node->data ) ) {
			$node->data = imap_fetchbody( $this->_mbox, $this->_uid, $node->section, FT_UID );
			if ( $node->encoding === self::$encoding[ENCQUOTEDPRINTABLE] ) $node->data = quoted_printable_decode( $node->data );
			if ( $node->encoding === self::$encoding[ENCBASE64]) $node->data = base64_decode( $node->data );
		}
		return $node;
	}

	private function _load()
	{
		if ( ( $data = imap_fetchstructure( $this->_mbox, $this->_uid, FT_UID ) ) === false ) return false;
		$this->_readSection( $data );
	}

	private function _readSection( $node, $prefix = '' )
	{
		$read_deep = true;
		$obj       = (object)array();

		$obj->type = strtolower( $this->getPartType( $node->type ).( $node->ifsubtype? '/'.$node->subtype : '' ) );
		if ( strpos( $prefix, '.' ) === false && preg_match( '/^(?:x-|)pkcs7-mime$/', strtolower( $node->subtype ) ) ) $this->_isCripted = true;
		if ( $node->ifid ) $obj->cid= trim( $node->id, '<>' );
		if ( isset( $node->lines ) ) $obj->lines = $node->lines;
		if ( isset( $node->bytes ) ) {
			$obj->encoding = strtolower( $this->getPartEncoding( $node->encoding ) );
			$obj->size     = $node->bytes;
		}
		$obj->section = ( $node->type === TYPEMESSAGE || $prefix === '' )? ( $prefix.($prefix?'.':'').'0' ) : $prefix;
		
		// PARAMETERS
		$params = array();
		if ( $node->parameters  ) foreach ( $node->parameters  as $x ) $params = array_merge( $params, $this->_attr_decode( $x->attribute, $x->value ) );
		if ( $node->dparameters ) foreach ( $node->dparameters as $x ) $params = array_merge( $params, $this->_attr_decode( $x->attribute, $x->value ) );
		if ( count( $params ) ) $obj->params = (object)$params;

		//IMAGE CID
		if( $node->type == 5 && strtolower( $node->disposition ) === 'inline' ){
			$cID = preg_replace('/(<|>)/','', $node->id );
			$this->_imagesCid[ md5( $cID ) ] = array( 'id' => $cID, 'size' => $node->bytes ); 
		}

		// ATTACHMENTS
		if ( ( $node->ifdisposition && ( strtolower( $node->disposition ) === 'attachment' ) ) || $params['filename'] || $params['name'] ) {
			$read_deep = false;
			$this->_attachs[] = $obj->section;

			$obj->filename = isset( $params['filename'] )? $params['filename'] : ( isset( $params['name'] )? $params['name'] : false );
			if ( $obj->filename === false ) {
				if (function_exists('imap_fetchmime')) {				
					preg_match('/name=["\']?(.*)["\']?/', imap_fetchmime( $this->_mbox, $this->_uid , $obj->section, FT_UID ), $matchs );
				}
				$obj->filename = isset( $matchs[1] )? $this->_str_decode( $matchs[1] ) : 'attachment.bin';
			}
			$obj->filename = $this->_stripWinBadChars( $obj->filename );

		} else if ( ( $node->type === TYPETEXT || $node->type === TYPEMESSAGE ) ) {
			if ( strtolower( $node->subtype ) === 'plain' ) $this->_content_plain[] = $obj->section;
			else $this->_content_html[] = $obj->section;
		}

		$this->_sections[$obj->section] = $obj;

		if ( $read_deep && isset( $node->parts ) ) foreach ( $node->parts as $i => $part ) $this->_readSection( $part, $prefix.($prefix?'.':'').($i+1) );
	}

	private function _attr_decode( $attr, $value )
	{
		if ( preg_match( '/\*$/', $attr ) ) {
			$charset = false;
			if ( preg_match( '/([^\']*)\'([^\']*)\'(.*)/', $value, $matches ) ) list( , $charset, $lang, $value ) = $matches;
			return array( strtolower( mb_substr( $attr, 0, -1 ) ) => $this->_str_decode( urldecode( $value ), $charset ) );
		}
		return array( strtolower( $attr ) => $this->_str_decode( $value ) );
	}


	private function _str_decode( $str, $charset = false )
	{
		if ( preg_match( '/=\?[\w-#]+\?[BQ]\?[^?]*\?=/i', $str ) ) $str = mb_decode_mimeheader( $str );
		return $this->_toUTF8( $str, $charset );
	}
	
	private function _toUTF8( $str, $charset = false, $to = 'UTF-8' )
	{
		return mb_convert_encoding( $str, $to, ( $charset === false? mb_detect_encoding( $str, 'UTF-8, ISO-8859-1', true ) : $charset ) );
	}

	private function _stripWinBadChars( $filename )
	{
		return preg_replace( '/[<>:"|?*\/\\\]/', '-', preg_replace( '/[\x00-\x1F\x7F]/u', '', $this->_str_decode( $filename ) ) );
	}

	private function _fetchBody( $section, $flag = false )
	{
		$sec = $this->_sections[$section];
		$body = ( $section === '0' )? imap_body( $this->_mbox, $this->_uid, FT_UID|$flag ) : imap_fetchbody( $this->_mbox, $this->_uid, $sec->section, FT_UID|$flag );
		$body = $this->_decodeBody( $body, $sec->encoding, $sec->params->charset );
		if ( $sec->type === 'text/calendar' ) $body = $this->_decodeBody( $body, 'calendar' );
		return $body;
	}

	private function _decodeBody( $body, $encoding, $charset = false )
	{
		switch ( strtolower( $encoding ) ) {
			case 'base64':           $body = base64_decode( $body );           break;
			case 'calendar':         $body = $this->_calendar_decode( $body ); break;
			case 'quoted-printable': $body = quoted_printable_decode( $body ); break;
		}
		return $this->_toUTF8( $body, $charset );
	}

	private function _replaceCID( $body )
	{
		// CID : content-disposition: attachment;
		foreach ( $this->_attachs as $section ){
			if ( isset( $this->_sections[$section]->cid ) )
				$body = preg_replace( '/[Cc][Ii][Dd]:'.preg_quote( $this->_sections[$section]->cid ).'/',
					'./inc/show_img.php?msg_folder='.$this->_folder.'&msg_num='.$this->_uid.'&msg_part='.$section, $body );
		}

		// CID : content-disposition: inline;
		foreach( $this->_sections as $section ){
			if( isset( $section->cid ) && isset( $this->_imagesCid[ md5( $section->cid ) ] ) ){
				if( $this->_imagesCid[ md5( $section->cid ) ]['size'] &&  isset( $section->size ) ){
					$body = preg_replace( '/[Cc][Ii][Dd]:'. preg_quote( $section->cid ).'/',
										  './inc/show_img.php?msg_folder='.$this->_folder.'&msg_num='.$this->_uid.'&msg_part='.$section->section, 
										  $body );
				}
			}
		}

		return $body;
	}

	public function getThumbs()
	{
		$thumbs_array = array();
		$i = 0;
		foreach ( $this->_attachs as $section ) {
			$section = $this->_sections[$section];
			if ( !preg_match( '#^image/(p?jpeg|gif|png)$#', $section->type ) ) continue;
			if ( $section->encoding !== 'base64' ) continue;
			$url = urlencode( $this->_folder ).';;'.$this->_uid.';;'.$i.';;'.$section->section.';;'.$section->encoding;
			$thumbs_array[] = '<a '.
				'onMouseDown="save_image(event,this,\''.$this->_folder.'\',\''.$this->_uid.'\',\''.$section->section.'\')" '.
				'href="#'.$url.'" '.
				'onClick="window.open(\'/expressoMail1_2/inc/show_img.php?msg_num='.$this->_uid.'&msg_folder='.$this->_folder.'&msg_part='.$section->section.'\',\'mywindow\',\'width=700,height=600,scrollbars=yes\');" '.
			'>'.
				'<img id="'.$url.'" style="border:2px solid #fde7bc;padding:5px" title="'.$this->getLang( 'Click here do view (+)' ).'" '.
					'src="./inc/show_img.php?msg_num='.$this->_uid.'&msg_folder='.$this->_folder.'&msg_part='.$section->section.'&thumb=true&file_type=jpeg">'.
			'</a>';
			$i++;
		}
		return $thumbs_array;
	}

	public function getLang( $key )
	{
		if ( !isset( $_SESSION['phpgw_info']['expressomail']['lang'][$key] ) ) return ( $key.'*' );
		return $_SESSION['phpgw_info']['expressomail']['lang'][$key];
	}

	private function _calendar_decode( $body )
	{
		include_once( 'class.db_functions.inc.php' );
		include_once( 'class.imap_functions.inc.php' );
		$db   = new db_functions();
		$imap = new imap_functions();
		$this->_hash_vcalendar = $db->import_vcard( $body, $this->_uid );
		return $imap->vCalImport( $body );
	}
}
