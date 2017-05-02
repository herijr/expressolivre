<?php

class UserAppsResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","UserApps","Retorna os modulos do expresso que o usuario tem acesso.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("user","string",true,"Login do Usuario");
	}
	
	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		$user_id = $this->getParam('user');

 		$account_id = $GLOBALS['phpgw']->accounts->name2id($user_id);

		if ($account_id == "") { 
			Errors::runException(2200);
		} else {
			$apps = $this->getUserApps($account_id);
	 		$result = array('user' => $user_id, 'uidNumber' => $account_id, 'apps' => $apps);
	 		$this->setResult($result);

		}

		return $this->getResponse();
	}

}