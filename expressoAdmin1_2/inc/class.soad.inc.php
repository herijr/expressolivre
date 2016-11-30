<?php
class soad
{
	protected $_ad = null;
	
	function soad()
	{
		$this->_ad = CreateObject('phpgwapi.activedirectory');
	}
	
	public function getConfs()
	{
		return array(
			ActiveDirectory::CONFIG_ENABLED => $this->_ad->enabled,
			ActiveDirectory::CONFIG_URL     => $this->_ad->url,
			ActiveDirectory::CONFIG_ADMIN   => $this->_ad->admin,
			ActiveDirectory::CONFIG_PASSWD  => '',
			ActiveDirectory::CONFIG_OU_LIST => $this->_ad->ou_list,
		);
	}
	
	public function setConf( $params )
	{
		$error      = array();
		$status     = array();
		$commit     = false;
		
		foreach ( $this->_ad->getConfigs() as $key => $value ) {
			if ( $value == ActiveDirectory::CONFIG_PASSWD && $params[$val] === '' ) continue;
			if ( strtolower( substr( $value, 0, 1 ) ) == 'a' ) {
				$value = array_combine( $params[$value.'_key'], $params[$value.'_val'] );
				if ( $value === false ) continue;
				$status[$key] = $this->_ad->setConfig( $key, $value );
			} else $status[$key] = $this->_ad->setConfig( $key, isset( $params[$value] )? $params[$value] : '' );
		}
		foreach ( $status as $key => $value ) {
			switch ($value) {
				case 'skipped': break;
				case 'changed': $commit = true; break;
				default: $error[$key][] = utf8_encode( $value );
			}
		}
		if ( count($error) ) return $error;
		if ( $commit ) $this->_ad->commit();
		
		return true;
	}
	
}
