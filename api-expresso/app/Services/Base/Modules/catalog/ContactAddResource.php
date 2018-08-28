<?php

namespace App\Services\Base\Modules\catalog;

use App\Services\Base\Adapters\CatalogAdapter;
use App\Services\Base\Commons\Errors;

class ContactAddResource extends CatalogAdapter {

	public function setDocumentation() {

		$this->setResource("Catalog","Catalog/ContactAdd","Adiciona um contato no catálogo pessoal.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("contactAlias","string",false,"Apelido do contato.");
		$this->addResourceParam("contactGivenName","string",false,"Primeiro nome do contato.");
		$this->addResourceParam("contactFamilyName","string",false,"Sobrenome do contato.");
		$this->addResourceParam("contactPhone","string",false,"Telefone do contato");
		$this->addResourceParam("contactEmail","string",true,"Email do contato.");
	}

	public function post($request)
	{
 		$this->setParams($request);

		//New Contact
		$newContact 	= array();
		$newContact[0]	= trim($this->getParam('contactAlias'));
		$newContact[1]	= trim($this->getParam('contactGivenName'));			
		$newContact[2]	= trim($this->getParam('contactFamilyName'));
		$newContact[3]	= trim($this->getParam('contactPhone'));			
		$newContact[4]	= trim($this->getParam('contactEmail'));			

		// Field Validation
		if( $newContact[4] === "" ) {
			return Errors::runException( "CATALOG_EMAIL_EMPTY" );
		} else {	
			if( !preg_match("/^[[:alnum:]]+([\.\_\-]?([[:alnum:]]+))+\@(([[:alnum:]\-]+)\.)+[[:alpha:]]{2,4}$/", $newContact[4]) ){
				return Errors::runException( "CATALOG_EMAIL_INVALID" );		        	
			}
		}
		
		// is serialized
		$isSerialized = function( $data ){
			if ( !is_string( $data ) ){ return false; }
			$data = trim( $data );
			if ( 'N;' == $data ){ return true; }
			if ( !preg_match( '/^([adObis]):/', $data, $badions ) ){ return false; }
			switch ( $badions[1] ) {
				case 'a' :
				case 'O' :
				case 's' :
					if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) ){ return true; }
					break;
				case 'b' :
				case 'i' :
				case 'd' :
					if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) ){ return true; }
				break;
			}
			return false;			
		};

		$result = $this->addContact($newContact);
		$result = (( $isSerialized($result) ) ? unserialize( $result ) : $result );

		if( $result['status'] === "false") {	
			return Errors::runException( $result['msg'] );
		} else {
			$this->setResult(true);
		}
		
		 
		return $this->getResponse();		
	}
}
