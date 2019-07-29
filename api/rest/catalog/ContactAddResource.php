<?php

class ContactAddResource extends CatalogAdapter {

	public function setDocumentation() {

		$this->setResource("Catalog","Catalog/ContactAdd","Adiciona um contato no cat�logo pessoal.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("contactID","string",false,"C�digo do contato (Informado quando for alterar o contato existente.");
		$this->addResourceParam("contactAlias","string",false,"Apelido do contato.");
		$this->addResourceParam("contactGivenName","string",false,"Primeiro nome do contato.");
		$this->addResourceParam("contactFamilyName","string",false,"Sobrenome do contato.");
		$this->addResourceParam("contactPhone","string",false,"Telefone do contato");
		$this->addResourceParam("contactEmail","string",true,"Email do contato.");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{
			$contactID = $this->getParam('contactID');
			//New Contact
			$newContact 	= array();
			$newContact[0]	= trim($this->getParam('contactAlias'));
			$newContact[1]	= trim($this->getParam('contactGivenName'));			
			$newContact[2]	= trim($this->getParam('contactFamilyName'));
			$newContact[3]	= trim($this->getParam('contactPhone'));			
			$newContact[4]	= trim($this->getParam('contactEmail'));			

			// Field Validation
			$lastChar = substr($newContact[4], -1);
			if ($lastChar == ",") {
				$newContact[4] = substr($newContact[4],0,-1);
			}

			$lastChar = substr($newContact[3], -1);
			if ($lastChar == ",") {
				$newContact[3] = substr($newContact[3],0,-1);
			}

			$contactEmails = explode(",", $newContact[4]);
			foreach ($contactEmails as $contactEmail) {
				$contactEmail = trim($contactEmail);
				if( $contactEmail === "" )
				{
					Errors::runException( "CATALOG_EMAIL_EMPTY" );
				}
				else
				{
					if( !preg_match("/^[[:alnum:]]+([\.\_\-]?([[:alnum:]]+))+\@(([[:alnum:]\-]+)\.)+[[:alpha:]]{2,4}$/", $contactEmail) )
					{
						Errors::runException( "CATALOG_EMAIL_INVALID" );
					}
				}
			}

			if ($contactID != "") {
				$result = unserialize($this->updateContact($contactID,$newContact));
			} else {
				$result = unserialize($this->addContact($newContact));
			}

			if( $result['status'] === "false")
			{	
				Errors::runException( $result['msg'] );
			}
			else
			{
				$this->setResult(true);
			}

		}
		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}
}