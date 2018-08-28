<?php

namespace App\Services\Base\Modules\catalog;

use App\Services\Base\Adapters\CatalogAdapter;
use App\Services\Base\Commons\Errors;

class ContactDeleteResource extends CatalogAdapter {

	public function setDocumentation() {
		$this->setResource("Catalog","Catalog/ContactDelete","Exclui um contato do catálogo pessoal.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("contactID","string",false,"ID do contato que será excluído.");
	}

	public function post($request)
	{
 		$this->setParams( $request );

		//New Contact
		$contactID	= trim( $this->getParam('contactID') );
		$contactID	= trim( preg_replace("/[^0-9]/", "", $contactID) );

		// Field Validation
		if( $contactID === "" ) {
			return Errors::runException( "CATALOG_ID_EMPTY" );
		}

		$result = unserialize($this->deleteContact($contactID));
		
		$this->setResult( $result );

		 
		return $this->getResponse();		
	}
}
