<?php
/**
 * SubmitPersonalFormResource - Submit/Validade form number and check code
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @property	apps *
 * @property	rest SMS/SubmitPersonalFormResource
 * @version		1.0
 */
include_once dirname(__FILE__).'/../../adapters/SMSAdapter.php';
class SubmitPersonalFormResource extends SMSAdapter {
	
	/**
	 * POST method request
	 *
	 * @param		string phoneNumber - phone number
	 * @param		string checkCode - check code
	 * @param		boolean SMSAuth - Authorization to receive
	 * @property	method post
	 * @return		boolean - successfully sent
	 */
	public function post($request){
		
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		$phoneNumber = preg_replace('/[^\d]/', '', $this->getParam('phoneNumber'));
		
		// Always first validity check code in some failure cases, save all preferences changed
		if (!empty($phoneNumber)) $this->validateCheckCodeToPhoneNumber($this->getParam('checkCode'), $phoneNumber);
		
		// Set user preference authorization SMS
		$this->setAuth($this->getParam('SMSAuth'));
		
		// Save preferences before returning the resource
		$this->savePreferences();
		
		$this->setResult(true);
		
		return $this->getResponse();
	}
}
