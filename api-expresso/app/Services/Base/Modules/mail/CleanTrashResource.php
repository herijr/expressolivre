<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class CleanTrashResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/CleanTrash","Limpa a Lixeira do Usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("type","string",true,"(1 = Lixeira, 2 = Spam)",false,"1");
	}

	public function post($request){

 		$this->setParams( $request );

		$type = trim( $this->getParam('type') );

		if( $type === "" ){
			$params = array( "type" => "trash" );
		} else {	
			switch( $type ) {
				case "1" : $params = array( "type" => "trash" ); break;
				case "2" : $params = array( "type" => "spam" ); break;
				default  : $params = array( "type" => "trash" ); break;
			}
		}

		if( !$this->getImap()->clean_folder( $params ) ){
			return Errors::runException("MAIL_TRASH_NOT_CLEANED");
		}

		$this->setResult(true);

		 
		return $this->getResponse();
	}
}
