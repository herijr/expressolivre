<?php

namespace App\Adapters;

/**
 * SMSAdapter
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @version		1.5
 */

use App\Adapters\ExpressoAdapter;

class SMSAdapter extends ExpressoAdapter
{
	protected $_sms = null;
	/**
	 * Constructor
	 *
	 * @throws Errors::runException('E_LOAD_EXTENSION');
	 */

	 public function __construct()
	{
		try {
			if(!@is_object($GLOBALS['phpgw']->sms)) $GLOBALS['phpgw']->sms = CreateObject('phpgwapi.sms');
			$this->_sms = $GLOBALS['phpgw']->sms;
		} catch (Exception $e) {}
	}

	public function sendCheckCodeToPhoneNumber( $number ) { return $this->_sms->sendCheckCodeToPhoneNumber( $number ); }
	public function savePreferences() { return $this->_sms->savePreferences(); }
	public function send_message( $ids, $message ) { return $this->_sms->send_message( $ids, $message ); }
	public function validateCheckCodeToPhoneNumber( $code, $number) { return $this->_sms->validateCheckCodeToPhoneNumber( $code, $number ); }
	public function setAuth( $value ) { return $this->_sms->setAuth( $value ); }
	public function getAuth() { return $this->_sms->getAuth(); }
	public function getLastPhoneNumberWasSendCode() { return $this->_sms->getLastPhoneNumberWasSendCode(); }
	public function getCheckedListPhoneNumbers() { return $this->_sms->getCheckedListPhoneNumbers(); }
	public function hasSendAuth() { return $this->_sms->hasSendAuth(); }
}
