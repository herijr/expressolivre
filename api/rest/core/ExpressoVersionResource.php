<?php

class ExpressoVersionResource extends ExpressoAdapter {


	public function setDocumentation() {

		$this->setResource("Expresso","ExpressoVersion","Retorna a versão do Expresso e a Versão da API instalada.",array("POST","GET"));
		$this->setIsMobile(true);

	}

	public function get($request) {
		return $this->post($request);
	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request); 		 
 		
		$result = array('expressoVersion' =>  $this->getExpressoVersion(),
						'apiVersion' => API_VERSION);
 		$this->setResult($result);

 		$config 	= parse_ini_file( API_DIRECTORY . '/../config/Tonic.srv', true );
		$autoload 	= array();
		$classpath 	= array();

		foreach( $config as $uri => $classFile )
		{
			foreach( $classFile as $className => $filePath )
			{
				$autoload[ $uri ] = $className;
				$classpath[ $className ] = $filePath;
			}
			
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse(); 		
	}

}
