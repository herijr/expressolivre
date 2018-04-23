<?php

class DocumentationResource extends ExpressoAdapter {


	public function setDocumentation() {

		$this->setResource("Expresso","Documentation","Retorna o json com a Documentação da API, as informações deste recurso são utilizadas para gerar a documentação automática da API.",array("POST","GET"));

	}

	public function get($request) {
		return $this->post($request);
	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request); 	

 		$config 	= parse_ini_file( API_DIRECTORY . '/../config/Tonic.srv', true );
 		$documentation = array();

		foreach( $config as $uri => $classFile )
		{
			foreach( $classFile as $className => $filePath )
			{
				$class = new $className( 0 );
				$class->setDocumentation();
				$class_doc = $class->getDocumentation();
				if ($class_doc["id"] != "") {
					$documentation[$class_doc["id"]] = $class_doc; 
				}
				//array_push($documentation,$class_doc);
			}

		}
		$errors = Errors::getInstance();
		$all_errors = $errors->getErrors();
		$result = array("resources" => $documentation, "possible_errors" => $all_errors);
		//print_r($result);
 		
 		$this->setResult($result);

		//to Send Response (JSON RPC format)
		return $this->getResponse(); 		
	}

}
