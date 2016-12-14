<?php

require_once( PHPGW_API_INC . '/class.singleton.inc.php' );

class ConfigSerial extends Singleton
{
	protected $_ref                   = null;
	protected $_data                  = null;
	protected $_cache_configs         = null;
	protected $_config                = null;
	
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
		$config = CreateObject('phpgwapi.config','phpgwapi');
		$config->config_data[$this->_ref->getShortName()] = ( $this->_data );
		return $config->save_repository();
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	
	private function _castConfig( $type, $value )
	{
		switch( strtolower( substr( $type, 0, 1 ) ) ) {
			case 's': case 'p': return (string)$value;
			case 'b': return (bool)$value;
			case 'a': return (array)$value;
		}
		return $value;
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
}
