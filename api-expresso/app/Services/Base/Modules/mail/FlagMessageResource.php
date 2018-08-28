<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class FlagMessageResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/FlagMessage","Altera o estado da mensagem",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("folderID","string",true," 	Pasta base para a busca.",false);
		$this->addResourceParam("msgID","string",true,"ID da mensagem.",false);
		$this->addResourceParam("flagType","string",true,"(1 - Importante, 2 - Normal, 3 - Lida, 4 - Não Lida, 5 - Respondida, 6 - SPAM, 7 - Não é SPAM).",false);
	}

	public function post($request){

 		$this->setParams( $request );

		$folderID 	= trim($this->getParam('folderID'));	
		$msgID		= trim($this->getParam('msgID'));
		$flagType	= trim($this->getParam('flagType'));

		$result = false;

		if( $folderID !== "" ) {
			if( $msgID !== "" ) {
				if( $flagType !== "" ) {
					switch ($flagType){
						case '1':
							$result = $this->flagMessage( $folderID, $msgID, "flagged" );
							break;
						case '2':
							$result = $this->flagMessage( $folderID, $msgID, "unflagged" );
							break;
						case '3':
							$result = $this->flagMessage( $folderID, $msgID, "seen" );
							break;
						case '4':
							$result = $this->flagMessage( $folderID, $msgID, "unseen" );
							break;
						case '5':
							$result = $this->flagMessage( $folderID, $msgID, "answered" );
							break;
						case '6':
							$result = $this->flagMessage( $folderID, $msgID, "forwarded" );
							break;
					}
				} else { 
					return Errors::runException("MAIL_FLAGTYPE_EMPTY");
				}	
			} else {
				return Errors::runException("MAIL_MSG_ID_EMPTY");
			}
		} else {
			return Errors::runException("MAIL_FOLDER_ID_EMPTY");
		}
	
		$this->setResult( $result );
		
		 
		return $this->getResponse();
	}
}
