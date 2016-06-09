<?php

class LoginResource extends ExpressoAdapter {

	private function getUserProfile(){
		if($this->getExpressoVersion() != "2.2") {
			$_SESSION['wallet']['user']['uidNumber'] = $GLOBALS['phpgw_info']['user']['account_id'];
		}
	
		return array(
				'contactID'			=> $GLOBALS['phpgw_info']['user']['account_dn'],
				'contactMails' 		=> array($GLOBALS['phpgw_info']['user']['email']),
				'contactPhones' 	=> array($GLOBALS['phpgw_info']['user']['telephonenumber']),
				'contactFullName'	=> $GLOBALS['phpgw_info']['user']['fullname'],
				'contactLID'			=> $GLOBALS['phpgw_info']['user']['account_lid'],
				'contactUIDNumber'		=> $GLOBALS['phpgw_info']['user']['account_id'],
				'contactApps'		=> $this->getUserApps()
		);
	}
	
	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);
		if($sessionid = $GLOBALS['phpgw']->session->create($this->getParam('user'), $this->getParam('password')))
		{
			$result = array(
				'auth' 			=> $sessionid.":".$GLOBALS['phpgw']->session->kp3,
				'profile' 		=> array($this->getUserProfile())
			);
			$this->setResult($result);
		}
		else
		{
			Errors::runException($GLOBALS['phpgw']->session->cd_reason);
		}
		return $this->getResponse();
	}	

}