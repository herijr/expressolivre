<?php

namespace App\Services\Base\Modules\core;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class ExpressoVersionResource extends ExpressoAdapter {

	public function setDocumentation() {
		$this->setResource("Expresso","ExpressoVersion","Retorna a versão do Expresso e a Versão da API instalada.",array("POST","GET"));
		$this->setIsMobile(true);
	}

	public function get($request){
		return $this->post();
	}

	public function post(){

		$result = array(
					'expressoVersion' =>  $this->getExpressoVersion(),
					'apiVersion' => 'API_VERSION'
				);

		$this->setResult($result);

		return $this->getResponse(); 		
	}
}
