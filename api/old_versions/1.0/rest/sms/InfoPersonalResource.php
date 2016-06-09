<?php
/**
 * InfoPersonalResource - Get informations of user
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @property	apps *
 * @property	rest SMS/InfoPersonalResource
 * @version		1.0
 */
include_once dirname(__FILE__).'/../../adapters/SMSAdapter.php';
class InfoPersonalResource extends SMSAdapter {
	
	/**
	 * POST method request
	 *
	 * @property	method post
	 * @return		array - user info
	 */
	public function post($request){
		
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		$this->setResult(array(
			'LastPhoneNumberWasSendCode' => $this->getLastPhoneNumberWasSendCode(),
			'CheckedListPhoneNumbers' => $this->getCheckedListPhoneNumbers(),
			'SMSAuth' => $this->getAuth(),
			'SendAuth' => $this->hasSendAuth(),
		));
		
		return $this->getResponse();
	}
}