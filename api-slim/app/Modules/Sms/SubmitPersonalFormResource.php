<?php

namespace App\Modules\Sms;

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

 use App\Adapters\SMSAdapter;

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
		
		$phoneNumber = preg_replace('/[^\d]/', '', $request['phoneNumber']);
		
		// Always first validity check code in some failure cases, save all preferences changed
		if (!empty($phoneNumber)) $this->validateCheckCodeToPhoneNumber($request['checkCode'], $phoneNumber);
		
		// Set user preference authorization SMS
		$this->setAuth($request['SMSAuth']);
		
		// Save preferences before returning the resource
		$this->savePreferences();
		
		return true;
	}
}
