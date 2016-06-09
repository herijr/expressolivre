<?php

class Messenger
{
	const CONFIG_ENABLED        = 'b_IM_enabled';
	const CONFIG_DOMAIN         = 's_IM_domain';
	const CONFIG_URL            = 's_IM_URL';
	const CONFIG_PUBKEY         = 's_IM_pubkey';
	const CONFIG_GROUPENABLED   = 'b_IM_group_enabled';
	const CONFIG_GROUPBASE      = 'o_IM_group_base';
	const CONFIG_GROUPFILTER    = 's_IM_group_filter';
	const CLIENT_NAME           = 'Expresso';
	
	var $_ref                   = null;
	var $_auth                  = null;
	var $_has_auth              = null;
	var $_data                  = null;
	var $_ldap_conn             = null;
	var $_cache_configs         = null;
	var $_config                = null;
	
	function __construct()
	{
		$this->_ref = new ReflectionClass( $this );
		$this->_config = CreateObject('phpgwapi.config','phpgwapi');
		$this->_config->read_repository();
		$this->_data = isset( $this->_config->config_data[$this->_ref->getShortName()] )?
			$this->_config->config_data[$this->_ref->getShortName()] : array();
		if ( is_string( $this->_data ) ) $this->_data = unserialize( $this->_data );
	}
	
	public function __get( $name )
	{
		$const = 'CONFIG_'.strtoupper( $name );
		return $this->_ref->hasConstant( $const ) && isset( $this->_data[$this->_ref->getConstant( $const )] )?
			$this->_data[$this->_ref->getConstant( $const )] : false;
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	
	public function getConfigs()
	{
		if ( is_null( $this->_cache_configs ) ) {
			$this->_cache_configs = array();
			foreach ( $this->_ref->getConstants() as $key => $val )
				if ( preg_match('/^CONFIG_(.+)$/', $key, $matches) )
					$this->_cache_configs[$matches[1]] = $val;
		}
		return $this->_cache_configs;
	}
	
	public function setConfig( $const, $value )
	{
		$const = strtoupper( $const );
		if ( is_null( $this->_cache_configs ) ) $this->getConfigs();
		if ( !isset( $this->_cache_configs[$const] ) ) return 'Config unknown: '.$const;
		$idx = $this->_cache_configs[$const];
		if ( is_null( $value ) ) {
			if ( isset($this->_data[$idx]) ) { unset( $this->_data[$idx] ); return 'changed'; }
			return 'skipped';
		}
		$value = $this->_castConfig( $idx, $value );
		if ( $this->_data[$idx] === $value ) return 'skipped';
		if ( !$value ) unset( $this->_data[$idx] );
		else $this->_data[$idx] = $value;
		return 'changed';
	}
	
	public function listConfigFunc( $type )
	{
		if ( !( is_string( $type ) && !empty( $type ) ) ) return false;
		$result = array();
		foreach ( $this->_ref->getMethods( ReflectionMethod::IS_PRIVATE ) as $obj )
			if ( preg_match( '/^_conf'.preg_quote( $type ).'_(.+)$/', $obj->name, $matches ) )
				$result[$matches[1]] = $obj;
		return $result;
	}
	
	/**
	 * Commit data to persistent storage
	 * 
	 * @return unknown
	 */
	public function commit()
	{
		$this->_config->config_data[$this->_ref->getShortName()] = ( $this->_data );
		return $this->_config->save_repository();
	}
	
	/**
	 * Check if user connection is fully functional with jabber server.
	 * Check server configuration, enabled and configured.
	 * Check organization permissions.
	 * 
	 * @return boolean
	 */
	public function checkAuth()
	{
		if ( $this->_has_auth === null ) $this->_has_auth = $this->_checkAuth();
		return $this->_has_auth;
	}
	
	/**
	 * Return user info connection with jabber.
	 * stdClass->{ client, user, auth }
	 * 
	 * @return stdClass | false
	 */
	public function getAuth()
	{
		if ( $this->_auth === null ) $this->_auth = $this->_makeAuth();
		return $this->checkAuth()? $this->_auth : false;
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	
	private function _getConfigFunct( $type )
	{
		if ( is_null( $this->_cache_configs ) ) $this->getConfigs();
		
		if ( !( is_string( $type ) && isset($this->_cache_configs[$type]) ) ) return false;
		
		$idx = $this->_cache_configs[$type];
		if ( !isset( $this->_data[$idx] ) ) return false;
		
		$method = '_conf'.$type.'_'.$this->_data[$idx];
		if ( !$this->_ref->hasMethod( $method ) ) return false;
		
		$params = array();
		return call_user_func_array( array( $this, $method ), $params );
	}
	
	private function _confGROUPBASE_RootDn()
	{
		return $this->_config->config_data['ldap_context'];
	}
	
	private function _confGROUPBASE_FirstParent()
	{
		$info     = $this->_getUserInfo();
		$rootdn   = preg_quote($this->_config->config_data['ldap_context'],'/');
		$username = isset( $info['account_dn'] )? $info['account_dn'] : false;
		preg_match( '/.+,([^,]+=[^,]+,'.$rootdn.')$/', $username, $matches );
		return isset( $matches[1] )? $matches[1] :false;
	}
	
	private function _confGROUPBASE_LastParent()
	{
		$info = $this->_getUserInfo();
		$rootdn = preg_quote($this->_config->config_data['ldap_context'],'/');
		$username = isset( $info['account_dn'] )? $info['account_dn'] : false;
		preg_match( '/[^,]+=[^,]+,((?:[^,]+=[^,]+,)+'.$rootdn.')$/', $username, $matches );
		return isset( $matches[1] )? $matches[1] :false;
	}
	
	private function _checkAuth()
	{
		$info = $this->_getUserInfo();
		
		if ( !( $this->enabled && $this->domain && $this->url ) ) return false;
		
		if ( !$this->groupenabled ) return true;
		
		if ( !( $this->groupbase && $this->groupfilter ) ) return false;
		
		$filter = str_replace( '%u', $info['account_lid'], $this->groupfilter );
		
		if ( !( $result = ldap_search(
			$this->_getLdapConn(),
			$this->_getConfigFunct( 'GROUPBASE' ),
			str_replace( '%u', $info['account_lid'], $this->groupfilter ),
			array( 'dn' )
		) ) ) return false;
		
		return ( ldap_count_entries( $this->_getLdapConn(), $result ) > 0 );
	}
	
	private function _getUserInfo()
	{
		return is_array($GLOBALS['phpgw_info']['user'])?
			$GLOBALS['phpgw_info']['user'] : $_SESSION['phpgw_info']['expressomail']['user'];
	}

	private function _getLdapConn()
	{
		if ( is_null( $this->_ldap_conn ) ) $this->_ldap_conn = $GLOBALS['phpgw']->common->ldapConnect();
		return $this->_ldap_conn;
	}
	
	private function _makeAuth()
	{
		if ( !$this->checkAuth() ) return null;
		$info = $this->_getUserInfo();
		
		$obj = new stdClass();
		$obj->client = self::CLIENT_NAME;
		$obj->user   = $info['account_lid'];
		$obj->auth   = base64_encode( $obj->user.'@'.$obj->domain."\0".$obj->user."\0".$this->_encrypt( $info['passwd'] ) );
		return $obj;
	}
	
	private function _castConfig( $type, $value )
	{
		switch( strtoupper( substr( $type, 0, 1 ) ) ) {
			case 'S': return (string)$value;
			case 'B': return (bool)$value;
		}
		return $value;
	}
	
	private function _encrypt( $passwd )
	{
		if ( !$this->_data[self::CONFIG_PUBKEY] ) return $passwd;
		$pub = openssl_get_publickey( $this->_data[self::CONFIG_PUBKEY] );
		if ( !pub ) return $passwd;
		openssl_public_encrypt( $passwd, $passwd_encrypted, $pub, OPENSSL_PKCS1_OAEP_PADDING );
		return base64_encode( $passwd_encrypted );
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
}
