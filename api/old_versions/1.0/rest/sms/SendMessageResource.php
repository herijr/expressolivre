<?php
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
include_once dirname(__FILE__).'/../../adapters/SMSAdapter.php';
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
		
		parent::post($request);
		
		$this->setResult(array('count' => $this->send_message($this->getParam('acc_id'), $this->getParam('message'))));
		
		return $this->getResponse();
	}
}