<?php

class UserApiResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","UserApi","Retorna a API do expresso que o usuario podera se logar.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("user","string",true,"Login do Usuario");
		$this->addResourceParam("modules","string",false,"Modulos esperados que o usuario tenha (separados por virgula).");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( !file_exists( dirname( __FILE__ ) . '/../../config/profileHomeServer.ini') ){
			Errors::runException(2201);
		} else {

			$user_id = $this->getParam('user');
			
			$profiles = parse_ini_file( dirname( __FILE__ ) . '/../../config/profileHomeServer.ini', true);
			$ldapHost = $profiles['ldap.server']['LDAP'];
			$ldapDN = $profiles['ldap.server']['BASE_DN'];

			// Get user
			$ldapConn = ldap_connect( $ldapHost ) or die( Errors::runException(2202) );
			$result = ldap_search($ldapConn, $ldapDN, "(uid={$user_id})") or die( Errors::runException(2202) );
			$data = ldap_get_entries($ldapConn, $result);

			$this->setResult( $data );

			$api['userAPI'] = false;

			if( isset($data['count']) && $data['count'] ){

				if( isset($data[0]['dn']) ){

					$api['userAPI'] = $profiles['home.server']['DEFAULT'];

					foreach( $profiles['home.server'] as $key => $value ) {
						if( preg_match('/ou='.$key.',dc/i', $data[0]['dn'], $matches ) ){
							$api['userAPI'] = $value;
						}
					}
				}
			} 

			if( $api['userAPI'] ){
				$this->setResult( $api );
			} else {
				Errors::runException(2200);
			}

			return $this->getResponse();
		}
	}
}
