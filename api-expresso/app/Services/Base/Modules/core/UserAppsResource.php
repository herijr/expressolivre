<?php

namespace App\Services\Base\Modules\core;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class UserAppsResource extends ExpressoAdapter {

	public function setDocumentation() {
		$this->setResource("Expresso","UserApps","Retorna os modulos do expresso que o usuario tem acesso.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("user","string",true,"Login do Usuario");
	}
	
	public function post($request){

 		$this->setParams($request);
 		$user_id = $this->getParam('user');
		$account_id = $GLOBALS['phpgw']->accounts->name2id($user_id);

		if( $account_id == "" ) { 
			return Errors::runException(2200);
		} else {
			$apps = $this->getUserApps($account_id);
	 		$result = array('user' => $user_id, 'uidNumber' => $account_id, 'apps' => $apps);
	 		$this->setResult($result);
		}

		return $this->getResponse();
	}
}
