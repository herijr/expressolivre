<?php
/**
 * SendCheckCodeResource - Send a check code to phone number
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @property	apps *
 * @property	rest SMS/SendCheckCodeResource
 * @version		1.0
 */
include_once dirname(__FILE__).'/../../adapters/SMSAdapter.php';
class SendCheckCodeResource extends SMSAdapter {
	
	/**
	 * POST method request
	 *
	 * @param		string phoneNumber - phone number
	 * @property	method post
	 * @return		boolean - successfully sent
	 */
	public function post($request){
		
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		// Send cheking code and store security preferences
		$this->sendCheckCodeToPhoneNumber($this->getParam('phoneNumber'));
		
		// Save preferences before returning the resource
		$this->savePreferences();
		
		$this->setResult(true);
		
		return $this->getResponse();
	}
}
