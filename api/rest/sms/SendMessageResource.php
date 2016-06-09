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

	public function setDocumentation() {

		$this->setResource("SMS","SMS/SendMessage","Envia uma mensagem para o Login.",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("acc_id","string",false,"Login que será enviado a mensagem.");
		$this->addResourceParam("message","string",false,"Mensagem que será enviada.");

	}
	
	
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