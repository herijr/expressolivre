<?php
class ImapException extends Exception
{
	public function __construct( &$imap, $message, $code = 0, $previous = null )
	{
		parent::__construct( 'Server: ' . $imap->get_server() . ': ' . $message, $code, $previous );
	}
}

class imap
{
	private $_delimiter    = '.';
	private $_user_conn    = null;
	private $_adm_conn     = null;
	private $_has_inbox    = null;
	private $_capabilities = null;
	private $_cur_mailbox  = false;
	private $_debug        = false;
	
	private $_server       = false;
	private $_port         = 993;
	private $_user         = false;
	private $_admin        = false;
	private $_passwd       = false;
	
	function __construct( $perfil, $user )
	{
		$this->_user = $user;
		if ( is_array( $perfil ) ) {
			foreach ( $perfil as $key => $value ) {
				switch ( $key ) {
					case 'server'    : case 'imapAdminServer'   : $this->_server    = $value; break;
					case 'port'      : case 'imapAdminPort'     : $this->_port      = $value; break;
					case 'admin'     : case 'imapAdminUsername' : $this->_admin     = $value; break;
					case 'passwd'    : case 'imapAdminPW'       : $this->_passwd    = $value; break;
					case 'delimiter' : case 'imapDelimiter'     : $this->_delimiter = $value; break;
				}
			}
		}
	}
	
	/**
	 * Get string reference for server url.
	 * ex: {<host>:<port><opts>/authuser=<adm>/user=<user>}user[./]<user>
	 * 
	 * @param boolean $user_path - Attach the user path at the end of string
	 * @param boolean $auth_opts - Use authuser and user in the options
	 * @return string
	 */
	private function _get_ref( $user_path = false, $auth_opts = false )
	{
		$opts  = '/novalidate-cert';
		switch ( $this->_port ) {
			case 143: $opts .= $this->has_capability( 'starttls' )? '/tls' : '/notls'; break;
			case 993: $opts .= '/ssl'; break;
			default: break;
		}
		$opts .= $auth_opts? '/authuser=' . $this->_admin                    : '';
		$opts .= $auth_opts? '/user='     . $this->_user                     : '';
		$path  = $user_path? 'user'       . $this->_delimiter . $this->_user : '';
		return '{' . $this->_server . ':' . $this->_port . $opts . '}' . $path;
	}
	
	/**
	 * Throw exception on imap error
	 * 
	 * @throws ImapException
	 */
	private function _throw_error()
	{
		if ( ( $error = imap_errors() ) && ( $error = array_filter( $error, function( $v ){ return !preg_match( '/^SECURITY PROBLEM/i', $v ); } ) ) ) {
			throw new ImapException( $this, implode( ', ', (array)$error ) );
		}
	}
	
	/**
	 * Print debug file
	 * 
	 * @param string $function
	 * @param string | array $params
	 * @param mixed $result
	 */
	private function _debug( $function, $params, $result )
	{
		if ( $this->_debug ) error_log( html_entity_decode(
			$function.'( '.implode( ', ', (array)$params).' ) = '.print_r( $result?: 'false', true )
		) . PHP_EOL, 3, '/tmp/log' );
	}
	
	/**
	 * Load capabilities options of server
	 * @throws Exception
	 * @return imap
	 */
	private function _get_capability()
	{
		if( $this->_capabilities !== null ) return $this->_capabilities;
		$this->_capabilities = array();
		$fp = false;
		try {
			$eol = "\r\n";
			$fp = fsockopen( ($this->_port == 993? 'ssl://' : '' ) . $this->_server, $this->_port, $errno, $errstr, 15 );
			if ( !$fp ) throw new ImapException( $this, $errstr, $errno );
			if ( !stream_set_timeout( $fp, 1 ) ) throw new ImapException( $this, 'Could not set timeout' );
			fgets( $fp, 1024 );
			fputs( $fp, 'C01 CAPABILITY' . $eol );
			while ( $line = fgets( $fp, 2048 ) ) {
				if ( strtoupper( substr( $line, 0, 3 ) ) == 'C01' ) break;
				$this->_capabilities = explode( ' ', preg_filter( array( '/^\* CAPABILITY /','/\\r\\n$/'), '', $line ) );
			}
		} catch ( Exception $e ) {}
		if ( $fp ) fclose( $fp );
		
		return $this->_capabilities;
	}
	
	/**
	 * Open main connection
	 * @throws Exception
	 * @return boolean | resource
	 */
	private function _open_connections( $as_user = true )
	{
		$ref = $this->_get_ref( null, $as_user );
		$conn = imap_open( $ref, $this->_admin, $this->_passwd, OP_HALFOPEN );
		$this->_debug( 'imap_open', $ref, $conn );
		$this->_throw_error();
		return $as_user? $this->_user_conn = $conn : $this->_adm_conn = $conn ;
	}
	
	/**
	 * Get main connection
	 * @throws Exception
	 * @return boolean | resource
	 */
	private function _get_conn( $as_user = true )
	{
		$conn = $as_user? $this->_user_conn : $this->_adm_conn;
		return ( $conn === null )? $this->_open_connections( $as_user ) : $conn;
	}
	
	/**
	 * Get delimiter char from user path
	 * @throws Exception
	 * @return boolean | imap
	 */
	private function _get_delimiter()
	{
		$ref = $this->_get_ref();
		$list = imap_getmailboxes( $this->_get_conn(), $ref, 'INBOX' );
		$this->_debug( 'imap_getmailboxes', array( $ref, 'INBOX' ), $list );
		$this->_throw_error();
		if ( count( $list ) ) $this->_delimiter = current( $list )->delimiter;
		else throw new ImapException( $this, 'Mailbox not found' );
		return $this;
	}
	
	/**
	 * Convert mailbox array to path
	 * 
	 * @param array $mailbox
	 * @return string
	 */
	private function _to_path( $mailbox )
	{
		return implode( $this->_delimiter, (array)$mailbox );
	}
	
	/**
	 * Search INBOX user
	 * @throws Exception
	 * @return boolean | imap
	 */
	private function _get_inbox()
	{
		$ref = $this->_get_ref();
		$list = imap_list( $this->_get_conn(), $ref, 'INBOX' );
		$this->_debug( 'imap_list', array( $ref, 'INBOX' ), $list );
		$this->_throw_error();
		return ( $this->_has_inbox = ( is_array( $list ) && count( $list ) === 1 ) );
	}
	
	/**
	 * Parse header information flags
	 * @return string
	 */
	private function _parse_mail_flags( $hdr )
	{
		$flags = array();
		if ( ( isset( $hdr->Recent ) && $hdr->Recent   == 'R' ) || !( isset( $hdr->Unseen ) && $hdr->Unseen == 'U' ) ) $flags[] = '\Seen';
		if ( isset( $hdr->Answered ) && $hdr->Answered == 'A' ) $flags[] = '\Answered';
		if ( isset( $hdr->Flagged  ) && $hdr->Flagged  == 'F' ) $flags[] = '\Flagged';
		if ( isset( $hdr->Deleted  ) && $hdr->Deleted  == 'D' ) $flags[] = '\Deleted';
		if ( isset( $hdr->Draft    ) && $hdr->Draft    == 'X' ) $flags[] = '\Draft';
		return implode( ' ', $flags );
	}
	
	/**
	 * Get server.
	 * @return string
	 */
	public function get_server()
	{
		return ( $this->_server )? $this->_server : 'none';
	}
	
	/**
	 * Has INBOX user?
	 * @return boolean
	 */
	public function has_inbox()
	{
		return ( $this->_has_inbox === null )? $this->_get_inbox() : $this->_has_inbox;
	}
	
	/**
	 * Has Capability?
	 * @return boolean
	 */
	public function has_capability( $param )
	{
		return in_array( strtoupper( $param ), $this->_get_capability() );
	}
	
	/**
	 * Create INBOX
	 * @throws Exception
	 * @return boolean
	 */
	public function create_inbox()
	{
		$this->_has_inbox = null;
		$ref = $this->_get_ref( true );
		$result = imap_createmailbox( $this->_get_conn( false ), $ref );
		$this->_debug( 'imap_createmailbox', $ref, $result );
		$this->_throw_error();
		$this->clean_shared();
		return $this->_has_inbox = ( $result !== false );
	}
	
	/**
	 * Delete INBOX
	 * @throws Exception
	 * @return boolean
	 */
	public function delete_inbox()
	{
		$this->_has_inbox = null;
		$shared = $this->get_shared();
		$this->set_acl( 'INBOX', array( $this->_admin => 'lrswipcda' ) );
		$ref = $this->_get_ref( true, true );
		$result = imap_deletemailbox( $this->_get_conn( false ), $ref );
		$this->_debug( 'imap_deletemailbox', $ref, $result );
		$this->_throw_error();
		$this->clean_shared( $shared );
		return $this->_has_inbox = ( $result !== false );
	}
	
	/**
	 * Read the list of mailboxes
	 * @throws Exception
	 * @return boolean|array
	 */
	public function list_folders()
	{
		$ref = $this->_get_ref( true ) . $this->_delimiter;
		$list = array( array( 'user', $this->_user ) );
		$sub_list = imap_list( $this->_get_conn(), $ref, "*" );
		$this->_debug( 'imap_list', array( $ref, "*" ), $sub_list );
		$this->_throw_error();
		if ( is_array( $sub_list ) )
			foreach ( $sub_list as $value )
				$list[] = explode( $this->_delimiter, preg_filter( '/^{.*}/', '', $value ) );
		return $list;
	}
	
	/**
	 * Create mailbox
	 * @param array $mailbox
	 * @throws Exception
	 * @return boolean
	 */
	public function create_mailbox( $mailbox )
	{
		$ref = $this->_get_ref() . $this->_to_path( $mailbox );
		if ( $ref === $this->_get_ref( true ) ) return $this->has_inbox()? false : $this->create_inbox();
		$result = imap_createmailbox( $this->_get_conn(), $ref );
		$this->_debug( 'imap_createmailbox', $ref, $result );
		$this->_throw_error();
		return $result;
	}
	
	/**
	 * Delete mailbox
	 * @throws Exception
	 * @return boolean
	 */
	/*public function delete_mailbox( $mailbox )
	{
		if ( !$this->_get_conn() ) return false;
		if ( !$this->has_inbox() ) return false;
		$result = imap_deletemailbox( $this->_get_conn(), $this->_get_ref() );
		$this->_throw_error();
		$this->_has_inbox = !(boolean)($result && count($result));
		return $result;
	}*/
	
	/**
	 * Reopen IMAP stream to new mailbox
	 * @param array $mailbox
	 * @throws Exception
	 * @return boolean
	 */
	public function set_mailbox( $mailbox )
	{
		$ref = $this->_get_ref( false, true ) . $this->_to_path( $mailbox );
		$result = imap_reopen( $this->_get_conn(), $ref );
		$this->_debug( 'imap_reopen', $ref, $result );
		$this->_throw_error();
		$this->_cur_mailbox = $ref;
		return $result;
	}
	
	/**
	 * Get information about the current mailbox
	 * @throws Exception
	 * @return boolean|Object->(Date, Driver, Nmsgs, Recent, Unread, Deleted, Size)
	 */
	public function get_info()
	{
		if ( !$this->_cur_mailbox ) $this->set_mailbox( 'INBOX' );
		$result = imap_mailboxmsginfo( $this->_get_conn() );
		$this->_debug( 'imap_mailboxmsginfo', '', $result );
		$this->_throw_error();
		return $result;
	}
	
	/**
	 * Gets the number of messages in the current mailbox
	 * @throws Exception
	 * @return int
	 */
	public function get_num_msgs()
	{
		if ( !$this->_cur_mailbox ) $this->set_mailbox( 'INBOX' );
		$result = imap_num_msg( $this->_get_conn() );
		$this->_debug( 'imap_num_msg', '', $result );
		$this->_throw_error();
		return $result;
	}
	
	/**
	 * Save a specific body section to a file.
	 * @param int $msg_number				Message number
	 * @param string|resource $handle		Output file
	 * @return boolean|object				Message date and flags
	 */
	public function get_mail( $msg_number, &$handle )
	{
		if ( !$this->_cur_mailbox ) $this->set_mailbox( 'INBOX' );
		$header = imap_headerinfo( $this->_get_conn(), $msg_number );
		$result = imap_savebody( $this->_get_conn(), $handle, $msg_number, null, FT_PEEK );
		$this->_throw_error();
		if ( is_resource( $handle ) ) rewind( $handle );
		if ( !$result ) return false;
		
		$opts = new stdClass();
		$opts->date  = strftime( '%d-%b-%Y %H:%M:%S %z', $header->udate );
		$opts->flags = $this->_parse_mail_flags( $header );
		return $opts;
	}
	
	/**
	 * Put a string message to current mailbox
	 * @param string|resource $handle		Input file
	 * @param string $flags					Flags ('\Recent', '\Seen', ... ) separated by spaces
	 * @param string $internal_date			Date rfc2060 formatted '%d-%b-%Y %H:%M:%S %z'
	 * @throws Exception
	 * @return boolean
	 */
	public function put_mail( &$handle, $flags = null, $internal_date = null )
	{
		if ( !$this->_cur_mailbox ) $this->set_mailbox( 'INBOX' );
		$result = imap_append(
			$this->_get_conn(), $this->_cur_mailbox,
			is_resource( $handle )? stream_get_contents( $handle ) : file_get_contents( $stream ),
			$flags,
			$internal_date
		);
		$this->_throw_error();
		return $result;
	}
	
	/**
	 * Retrieve the quota settings per user
	 * @throws Exception
	 * @return boolean|int
	 */
	public function get_quota()
	{
		$result = imap_get_quotaroot( $this->_get_conn(), 'INBOX' );
		$this->_debug( 'imap_get_quotaroot', 'INBOX', $result );
		$this->_throw_error();
		return isset( $result['limit'] )? $result['limit'] : -1;
	}
	
	public function set_quota( $quota )
	{
		$quota = (int)$quota;
		if ( $quota < 0 ) return $this->rem_quota();
		$ref = 'user'.$this->_delimiter.$this->_user;
		$result = imap_set_quota( $this->_get_conn( false ), $ref, $quota );
		$this->_debug( 'imap_set_quota', array( $ref, $quota ), $result );
		$this->_throw_error();
		return $result;
	}
	
	public function rem_quota()
	{
		$result = false;
		$fp = false;
		try {
			$ref = 'user'.$this->_delimiter.$this->_user;
			$eol = "\r\n";
			$fp = fsockopen( ($this->_port == 993? 'ssl://' : '' ) . $this->_server, $this->_port, $errno, $errstr, 15 );
			if ( !$fp ) throw new ImapException( $this, $errstr, $errno );
			if ( !stream_set_timeout( $fp, 1 ) ) throw new ImapException( $this, 'Could not set timeout' );
			fgets( $fp, 1024 );
			fputs( $fp, 'L01 LOGIN '.$this->_admin.' '.$this->_passwd.$eol );
			while ( $line = fgets( $fp, 2048 ) ) {
				$cod = strtoupper( substr( $line, 0, 3 ) );
				if ( $cod == 'L01' ) fputs( $fp, 'L02 SETQUOTA "'.$ref.'" ()'.$eol );
				else if ( $cod == 'L02' ) {
					$result = (strtoupper( substr( $line, 4, 2 ) ) === 'OK');
					break;
				}
			}
		} catch ( Exception $e ) {
			throw $e;
		}
		if ( $fp ) fclose( $fp );
		return $result;
	}
	
	public function get_acl( $mailbox )
	{
		$ref = $this->_to_path( $mailbox );
		$result = imap_getacl( $this->_get_conn(), $ref );
		$this->_debug( 'imap_getacl', $ref, $result );
		$this->_throw_error();
		return $result;
	}
	
	public function set_acl( $mailbox, $acl )
	{
		if ( is_array( $acl ) ) {
			foreach ( $acl as $id => $rights ) {
				$ref = $this->_to_path( $mailbox );
				$result = imap_setacl( $this->_get_conn(), $ref, $id, $rights );
				$this->_debug( 'imap_setacl', array( $ref, $id, $rights ), $result );
				$this->_throw_error();
			}
		}
		return true;
	}
	
	public function rename_acl( $mailbox, $from, $to )
	{
		$acl = $this->get_acl( $mailbox );
		if ( !isset( $acl[$from] ) ) throw new ImapException( $this, 'ACL not found' );
		$this->set_acl( $mailbox, array( $from => '', $to => $acl[$from] ) );
		$this->_throw_error();
		return true;
	}
	
	public function get_shared()
	{
		$result = array();
		$ref = $this->_get_ref() . 'user';
		$list = imap_list( $this->_get_conn(), $ref, '*' );
		$this->_debug( 'imap_list', array( $ref, '*' ), $list );
		$this->_throw_error();
		if ( is_array( $list ) ) {
			foreach ( $list as $mailbox ) {
				$path = preg_replace( "/({[^}].*})(.*)/", '$2', $mailbox );
				$acl = imap_getacl( $this->_get_conn(), $path );
				$this->_debug( 'imap_getacl', $path, $acl );
				if ( isset( $acl[$this->_user] ) ) {
					$result = array_merge_recursive( $result, array_reduce( array_reverse( explode( $this->_delimiter, $path, 3 ) ), function( $acc, $a ) {
						return is_array( $acc )? array( $a => array( 'mbox' => $acc ) ) : array( $a => $acc );
					} , $acl[$this->_user] ) );
				}
			}
		}
		return isset( $result['user']['mbox'] )? $result['user']['mbox'] : $result;
	}
	
	public function clean_shared( $shared = null )
	{
		$shared = ( is_null( $shared ) )? $this->get_shared() : $shared;
		foreach ( $shared as $username => $data ) {
			
			if ( isset( $data[0] ) ) {
				$ref = $this->_to_path( array( 'user', $username ) );
				$result = imap_setacl( $this->_get_conn( false ), $ref, $this->_user, '' );
				$this->_debug( 'imap_setacl', array( $ref, $this->_user, '' ), $result );
				$this->_throw_error();
			}
			
			if ( isset( $data['mbox'] ) ) {
				foreach ( $data['mbox'] as $path => $acl ) {
					$ref = $this->_to_path( array( 'user', $username, $path ) );
					$result = imap_setacl( $this->_get_conn( false ), $ref, $this->_user, '' );
					$this->_debug( 'imap_setacl', array( $ref, $this->_user, '' ), $result );
					$this->_throw_error();
				}
			}
		}
	}

	public function empty_inbox()
	{
		$this->set_mailbox( 'INBOX' );
		$result = imap_delete( $this->_get_conn(), "1:*" );
		$this->_debug( 'empty_inbox:delete', array( 'INBOX', $this->_user ), $result );
		$this->_throw_error();
		$result = imap_expunge( $this->_get_conn() );
		$this->_debug( 'empty_inbox:expunge', array( 'INBOX', $this->_user ), $result );
		$this->_throw_error();
	}

	function __destruct() {
		if ( $this->_adm_conn ) imap_close( $this->_adm_conn );
		if ( $this->_user_conn ) imap_close( $this->_user_conn );
	}
}
