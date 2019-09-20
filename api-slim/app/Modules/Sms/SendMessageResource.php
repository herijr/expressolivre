<?php

namespace App\Modules\Sms;

/**
 * SendMessageResource - Send a message for phone number of a account
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @property	apps *
 * @property	rest SMS/SendMessageResource
 * @version		1.0
 */

use App\Adapters\SMSAdapter;

class SendMessageResource extends SMSAdapter {

	/**
	 * POST method request
	 * 
	 * @param		int|array(int) $acc_id - accounts id to send
	 * @param		string $message - text to send
	 * @property	method post
	 * @return		int $count - counter successfully sent
	 */
	public function post($request){
		
		return array('count' => $this->send_message($request['acc_id'], $request['message']));
	}
}