<?php
class soeventws
{
	protected $_eventws = null;
	
	function soeventws()
	{
		$this->_eventws = CreateObject('phpgwapi.eventws');
	}
	
	public function getConfs()
	{
		return array(
			EventWS::CONFIG_ENABLED => $this->_eventws->enabled,
			EventWS::CONFIG_URL     => $this->_eventws->url,
			EventWS::CONFIG_ADMIN   => $this->_eventws->admin,
			EventWS::CONFIG_PASSWD  => '',
		);
	}
	
	public function setConf( $params )
	{
		$error      = array();
		$status     = array();
		$commit     = false;
		
		foreach ( $this->_eventws->getConfigs() as $key => $value ) {
			if ( $value == EventWS::CONFIG_PASSWD && $params[$val] === '' ) continue;
			if ( strtolower( substr( $value, 0, 1 ) ) == 'a' ) {
				$value = array_combine( $params[$value.'_key'], $params[$value.'_val'] );
				if ( $value === false ) continue;
				$status[$key] = $this->_eventws->setConfig( $key, $value );
			} else $status[$key] = $this->_eventws->setConfig( $key, isset( $params[$value] )? $params[$value] : '' );
		}
		foreach ( $status as $key => $value ) {
			switch ($value) {
				case 'skipped': break;
				case 'changed': $commit = true; break;
				default: $error[$key][] = utf8_encode( $value );
			}
		}
		if ( count($error) ) return $error;
		if ( $commit ) $this->_eventws->commit();
		
		return true;
	}
	
}
