<?php

require_once( PHPGW_API_INC . '/class.configserial.inc.php' );
require_once( PHPGW_API_INC . '/class.SoapClientX.inc.php' );

class EventWS extends ConfigSerial
{
	const CONFIG_ENABLED        = 'b_EWS_enabled';
	const CONFIG_URL            = 's_EWS_URL';
	const CONFIG_ADMIN          = 's_EWS_admin';
	const CONFIG_PASSWD         = 'p_EWS_passwd';

	protected $_soap            = false;
	protected $_error           = false;

	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	private function _conn()
	{
		$this->_error = false;
		if ( !$this->enabled ) { $this->_error = 'disabled'; return false; }
		if ( !$this->_soap ) {
			try {
				if ( preg_match( '/wsdl$/i', $this->url ) ) {
					$this->_soap = new SoapClientX( $this->url, array( 'trace' => true, 'exceptions' => true ) );
				} else {
					list( $location, $uri ) = explode( '#', $this->url );
					$this->_soap = new SoapClient( null, array( 'location' => $location, 'uri' => $uri, 'trace' => true, 'exceptions' => true ) );
				}
			} catch( Exception $e ) {
				$this->_error = $e->getMessage();
				return false;
			}
		}
		return true;
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	
	public function getError()
	{
		return $this->_error;
	}
	
	public function send( $event, $dn, $args = array() )
	{
		if ( $this->_conn() ) {
			$result = $this->_soap->event( base64_encode( serialize( (object)array(
				'admin'  => $this->admin,
				'passwd' => $this->passwd,
				'dn'     => $dn,
				'event'  => $event,
				'args'   => $args,
			) ) ) );
		}
		return $this->_error;
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
}
