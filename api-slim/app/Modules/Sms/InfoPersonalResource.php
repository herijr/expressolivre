<?php

namespace App\Modules\Sms;

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

use App\Adapters\SMSAdapter;

class InfoPersonalResource extends SMSAdapter {

	public function post($request){
		
		return array(
			'LastPhoneNumberWasSendCode' => $this->getLastPhoneNumberWasSendCode(),
			'CheckedListPhoneNumbers' => $this->getCheckedListPhoneNumbers(),
			'SMSAuth' => $this->getAuth(),
			'SendAuth' => $this->hasSendAuth(),
		);
	}
}