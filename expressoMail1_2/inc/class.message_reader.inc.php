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

	private $_uid           = false;
	private $_mbox          = false;
	private $_folder        = false;
	private $_attachs       = array();
	private $_attachs_root  = array();
	private $_sections      = false;
	//private $_structure     = null;
	//private $_plain         = '';
	//private $_charset       = '';
	//private $_html          = '';

	protected function getPartType( $type )
	{
		return isset( self::$type[$type] )? self::$type[$type] :'unknown';
	}

	protected function getPartEncoding( $encoding )
	{
		return isset( self::$encoding[$encoding] )? self::$encoding[$encoding] :'unknown';
	}

	public function __construct()
	{
		$this->_clear();
	}

	public function setMessage( $mbox, $folder, $uid )
	{
		if ( $this->_uid !== $uid || $this->_folder !== $folder ) $this->_clear();
		$this->_uid       = $uid;
		$this->_mbox      = $mbox;
		$this->_folder    = $folder;
		return $this;
	}

	public function getInfo()
	{
		if ( $this->_sections === false ) $this->_load();
		return (object)array(
			'uid'     => $this->_uid,
			'folder'  => $this->_folder,
			'attachs' => $this->getAttachInfo(),
		);
	}

	public function getAttachInfo( $section = false, $deep = false )
	{
		if ( $this->_sections === false ) $this->_load();
		if ( $section !== false ) return $this->_sections[$section];
		$result = array();
		foreach ( $this->{$deep?'_attachs':'_attachs_root'} as $section ) $result[] = $this->_sections[$section];
		return $result;
	}

	public function getAttach( $section )
	{
		if ( $this->_sections === false ) $this->_load();
		return $this->_getData( $this->_sections[$section] );
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

	private function _clear()
	{
		$this->_uid          = false;
		$this->_mbox         = false;
		$this->_folder       = false;
		$this->_attachs      = array();
		$this->_attachs_root = array();
		$this->_sections     = false;
	}

	private function _readSection( $node, $prefix = '' )
	{
		$obj = (object)array();
		$obj->type     = strtolower( $this->getPartType( $node->type ).( $node->ifsubtype? '/'.$node->subtype : '' ) );
		if ( $node->ifid ) $obj->cid= trim( $node->id, '<>' );
		if ( isset( $node->lines ) ) $obj->lines = $node->lines;
		if ( isset( $node->bytes ) ) {
			$obj->encoding = strtolower( $this->getPartEncoding( $node->encoding ) );
			$obj->size     = $node->bytes;
		}

		$obj->section = ( $node->type === TYPEMESSAGE || $prefix === '' )? ( $prefix.($prefix?'.':'').'0' ) : $prefix;

		// PARAMETERS
		$params = array();
		if ( $node->parameters  ) foreach ( $node->parameters as $x  ) $params[strtolower( $x->attribute )] = $this->_str_decode( $x->value );
		if ( $node->dparameters ) foreach ( $node->dparameters as $x ) $params[strtolower( $x->attribute )] = $this->_str_decode( $x->value );
		if ( count( $params ) ) $obj->params = $params;

		// ATTACHMENTS
		if ( ( $node->ifdisposition && strtolower( $node->disposition ) === 'attachment' ) || $params['filename'] || $params['name'] ) {
			$this->_attachs[] = $obj->section;
			if ( strlen( $prefix ) === 1 ) $this->_attachs_root[] = $obj->section;
			$obj->filename = isset( $params['filename'] )? $params['filename'] : ( isset( $params['name'] )? $params['name'] : false );
			if ( $obj->filename === false ) {
				preg_match('/name=["\']?(.*)["\']?/', imap_fetchmime( $this->_mbox, $this->_uid , $obj->section, FT_UID ), $matchs );
				$obj->filename = isset( $matchs[1] )? $this->_str_decode( $matchs[1] ) : 'attachment.bin';
			}
		}

		$this->_sections[$obj->section] = $obj;

		if ( isset( $node->parts ) ) foreach ( $node->parts as $i => $part ) $this->_readSection( $part, $prefix.($prefix?'.':'').($i+1) );
	}

	private function _str_decode( $str )
	{
		if ( preg_match( '/=\?[\w-#]+\?[BQ]\?[^?]*\?=/', $str ) ) $str = mb_decode_mimeheader( $str );
		return mb_convert_encoding( $str, 'UTF-8', mb_detect_encoding( $str, 'UTF-8, ISO-8859-1', true ) );
	}
	/*
	public function getSections() {
		$sections = $this->_readSections( $struct = imap_fetchstructure( $this->_mbox, $this->_uid, FT_UID ) );
		return array(
			'body'        => $sections['text'],
			'attachments' => $sections['attachments']?:array(),
		);
	}
	
	private function _initSections() {
		$structure = imap_fetchstructure( $this->_mbox, $this->_uid, FT_UID );
		if ( !$structure->parts ) $this->_getpart( $structure, '0' );
		else foreach ( $structure->parts as $key => $node ) $this->_getpart( $node, (string)($key + 1) );
	}
	
	private function _getpart( $node, $section ) {
		$this->_sections[] = array( 'section' => $section, 'type' => MessageReader::$type[$node->type].( $node->ifsubtype? '/'.strtolower( $node->subtype ) : '' ) );
		
		// DECODE DATA
		$data = ($section)? imap_fetchbody( $this->_mbox, $this->_uid, $section, FT_UID ): imap_body( $this->_mbox, $this->_uid, FT_UID );
		if ( $node->encoding == 4 ) $data = quoted_printable_decode( $data );
		if ( $node->encoding == 3 ) $data = base64_decode( $data );
		
		// PARAMETERS
		$params = array();
		if ( $node->parameters ) foreach ( $node->parameters as $x ) $params[strtolower( $x->attribute )] = $x->value;
		if ( $node->dparameters ) foreach ( $node->dparameters as $x ) $params[strtolower( $x->attribute )] = $x->value;
		
		// ATTACHMENT
		if ( $params['filename'] || $params['name'] ) {
			$filename               = ( $params['filename'] )? $params['filename'] : $params['name'];
			$this->_attachs[$section] = $filename;
		}
		
		// TEXT
		if ( $node->type == 0 && $data ) {
			if ( strtolower( $node->subtype ) == 'plain' ) $this->_plain .= trim( '$data' ) ."\n\n";
			else $this->_html .= '$data' ."<br><br>";
			$this->_charset = $params['charset'];
		} else if ( $node->type == 2 && $data ) $this->_plain .= '$data'."\n\n";
		
		// SUBPART RECURSION
		if ( $node->parts ) foreach ( $node->parts as $key => $sub_node ) $this->_getpart( $sub_node, $section.'.'.( $key + 1 ) );
	}
	
	private function _readSections( $node, $num = array() ) {
		$node->section = count( $num )? implode( '.', $num ) : '0';
		//public static $type = array( 'text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'model', 'other' );
		
		if ( $node->ifid ) {
			$params = $this->_getParams( $node );
			return array( 'cids' => array( array(
				'cid'       => trim( $node->id, '<>' ),
				'pid'      => $node->section,
				'name'     => $params['name']?: 'attachment.bin',
				'encoding' => $node->encoding,
				'fsize'    => $node->bytes,
			) ) );
		}
		
		if ( $node->ifdisposition && strtolower( $node->disposition ) === 'attachment' ) {
			$params = $this->_getParams( $node );
			return array( 'attachments' => array( array(
				'pid'      => $node->section,
				'name'     => $params['name']?: 'attachment.bin',
				'encoding' => $node->encoding,
				'fsize'    => $node->bytes,
			) ) );
		}
		
		switch ( MessageReader::$type[$node->type] ) {
			case 'text':      return $this->_getText( $node );
			case 'multipart': return $this->_getMultipart( $node, $num );
		}
	}
	
	private function _getMultipart( &$node, &$num ) {
		switch ( strtolower( $node->subtype ) ) {
			case 'alternative': return $this->_getMultipartAlternative( $node, $num );
			case 'related':     return $this->_getMultipartRelated( $node, $num );
			case 'mixed':       return $this->_getMultipartMixed( $node, $num );
			case 'signed':      return $this->_getMultipartSigned( $node, $num );
			case 'report':      return $this->_getMultipartReport( $node, $num );
			default:            return false;
		}
	}
	
	private function _getMultipartReport( &$node, &$num ) {
		if ( ( !isset( $node->parts ) ) || ( count( $node->parts ) < 2 ) ) return false;
		$result = $this->_readSections( $node->parts[0], array_merge( $num, array( 1 ) ) );
		//$cert   = $this->_readSections( $node->parts[1], array_merge( $num, array( 2 ) ) );
		
		return $result;
	}
	
	private function _getMultipartSigned( &$node, &$num ) {
		if ( ( !isset( $node->parts ) ) || ( count( $node->parts ) !== 2 ) ) return false;
		$result = $this->_readSections( $node->parts[0], array_merge( $num, array( 1 ) ) );
		$cert   = $this->_readSections( $node->parts[1], array_merge( $num, array( 2 ) ) );
		
		return $result;
	}
	
	private function _getMultipartRelated( &$node, &$num ) {
		if ( !isset( $node->parts ) ) return false;
		$result = array();
		foreach ( $node->parts as $key => $obj ) $result = array_merge_recursive( $result, $this->_readSections( $obj, array_merge( $num, array( $key + 1 ) ) ) );
		if ( isset( $result['cids'] ) && count( $result['cids'] ) ) {
			foreach ( $result['cids'] as $key => $obj ) {
				if ( isset( $result['text'] ) ) $result['text'] = $this->_makeCIDReplace( $result['text'], $obj );
				if ( isset( $result['alternative'] ) )
					foreach ( $result['alternative'] as $i => $value )
						$result['alternative'][$i]['text'] = $this->_makeCIDReplace( $result['alternative'][$i]['text'], $obj );
			}
		}
		return $result;
	}
	
	private function _makeCIDReplace( $text, $params ) {
		$str = './inc/show_embedded_attach.php?msg_folder='.$this->_folder.'&msg_num='.$this->_uid.'&msg_part='.$params['pid'];
		return preg_replace( '/cid:'.preg_quote( $params['cid'], '/' ).'/', $str, $text );
	}
	
	private function _getMultipartMixed( &$node, &$num ) {
		if ( !isset( $node->parts ) ) return false;
		$result = array();
		foreach ( $node->parts as $key => $obj )
			$result = array_merge_recursive( $result, $this->_readSections( $obj, array_merge( $num, array( $key + 1 ) ) ) );
		return $result;
	}
	
	private function _getMultipartAlternative( &$node, &$num ) {
		if ( !isset( $node->parts ) ) return false;
		$priority = array( 'plain' => 1, 'html' => 9 );
		$result = null;
		foreach ( $node->parts as $key => $obj ) {
			$partial = $this->_readSections( $obj, array_merge( $num, array( $key + 1 ) ) );
			if ( is_null( $result ) ) $result = $partial;
			else {
				$p = isset( $priority[$partial['type']] )? $priority[$partial['type']] : 0;
				$r = isset( $priority[$result['type']] )? $priority[$result['type']] : 0;
				if ( $p > $r ) {
					$temp = array();
					foreach ( $partial as $key => $value ) {
						if ( isset( $result[$key] ) ) $temp[$key] = $result[$key];
						$result[$key] = $value;
					}
					$partial = $temp;
				}
				if ( !isset( $result['alternative'] ) ) $result['alternative'] = array();
				$result['alternative'][] = $partial;
			}
		}
		return $result;
	}
	
	private function _getText( &$node ) {
		return array(
			'text'    => $this->_getData( $node ),
			'type'    => $node->ifsubtype? strtolower( $node->subtype ) : 'plain',
			'charset' => $this->_getParams( $node, 'charset' ),
		);
	}
	
	private function _getParams( &$node, $param = null ) {
		$params = array();
		if ( isset( $node->parameters  ) ) foreach ( $node->parameters  as $x ) $params[strtolower( $x->attribute )] = $x->value;
		if ( isset( $node->dparameters ) ) foreach ( $node->dparameters as $x ) $params[strtolower( $x->attribute )] = $x->value;
		return is_null( $param )? $params : ( isset( $params[$param] )? $params[$param] : false );
	}
	
	
	public function hasCID() {
		
	}*/
}
