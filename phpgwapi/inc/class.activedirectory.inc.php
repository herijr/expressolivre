<?php

require_once( PHPGW_API_INC . '/class.configserial.inc.php' );
require_once( PHPGW_API_INC . '/class.SoapClientX.inc.php' );

class ActiveDirectory extends ConfigSerial
{
	const CONFIG_ENABLED        = 'b_AD_enabled';
	const CONFIG_URL            = 's_AD_URL';
	const CONFIG_ADMIN          = 's_AD_admin';
	const CONFIG_PASSWD         = 'p_AD_passwd';
	const CONFIG_OU_LIST        = 'a_AD_ou_list';

	protected $_soap            = false;
	protected $_error           = false;

	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	private function _conn()
	{
		$this->_error = false;
		if ( !$this->enabled ) return false;
		if ( !$this->_soap ) {
			try {
				$this->_soap = new SoapClientX( $this->url, array( 'trace' => true, 'exceptions' => true ) );
			} catch( Exception $e ) {
				$this->_error = $e->getMessage();
				return false;
			}
		}
		return true;
	}
	
	private function _getLogin( $username )
	{
		if ( strlen( $username ) > 20 || strlen( $username ) === 0 ) {
			$this->_error = 'Login field overflow';
			return false;
		}
		return $username;
	}
	
	private function _getDepartament( $dn )
	{
		$department = trim( strtolower( preg_replace( '/,ou=/', '/', preg_replace( array( '/,dc=.*/', '/^[^,]*,ou=/' ), '', ','.$dn ) ) ) );
		if ( strlen( $department ) > 64 ) {
			$this->_error = 'Department field overflow';
			return false;
		}
		return $department;
	}
	
	private function _getPasswd()
	{
		$chars = array(
			'abcdefghijklmnopqrstuvwxyz',
			'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			'0123456789',
			'!@#$%^&*()_-=+;:,.?',
		);
		$i = 0;
		$str = '';
		while( strlen( $str ) < 8 ) {
			$str .= $chars[$i%4][mt_rand( 0, strlen( $chars[$i%4] )-1)];
			$i++;
		}
		return str_shuffle( $str );
	}
	
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	
	public function getError()
	{
		return $this->_error;
	}
	
	public function info( $username )
	{
		if ( !( $this->_conn() && $info = $this->_soap->GetUser( $username ) ) ) return false;
		$this->_error = $info->GetUserResult->Success? false : $info->GetUserResult->Error;
		error_log(html_entity_decode(Zend_Debug::dump($info,'$info',false)).PHP_EOL,3,'/tmp/log');
		return $info->GetUserResult->Success? $info->GetUserResult->Content->enc_value : false;
	}

	public function create( $username, $gn, $sn, $dn, $passwd = null, $ou = null )
	{
		return (
			$this->_getLogin( $username ) &&
			( $department = $this->_getDepartament( $dn ) ) &&
			$this->_conn() &&
			$this->_soap->CreateUser( $username, trim( $gn.' '.$sn ), $gn, $sn, $department, $ou, $passwd?: $this->_getPasswd() )
		);
	}

	public function update( $username, $gn, $sn, $dn )
	{
		return (
			( $department = $this->_getDepartament( $dn ) ) &&
			$this->_conn() &&
			$this->_soap->UpdateUser( $username, trim( $gn.' '.$sn ), $gn, $sn, $department )
		);
	}

	public function rename( $username_from, $username_to )
	{
		return (
			$this->_getLogin( $username_to ) &&
			$this->_conn() &&
			$this->_soap->RenameUser( $username_from, $username_to )
		);
	}

	public function passwd( $username, $passwd )
	{
		return ( $this->_conn() && $this->_soap->ChangePasswordUser( $username, $passwd ) );
	}

	public function enable( $username )
	{
		return ( $this->_conn() && $this->_soap->EnableUser( $username ) );
	}

	public function disable( $username )
	{
		return ( $this->_conn() && $this->_soap->DisableUser( $username ) );
	}

	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------
}
