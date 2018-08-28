<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class SpamMessageResource extends MailAdapter {	

	public function setDocumentation() {
		$this->setResource("Mail","Mail/SpamMessages","Retorna as mensagens do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("folderID","string",true,"Pasta base retornar as mensagens.",true,"INBOX");
		$this->addResourceParam("msgID","string",true,"Msg(s) ID(s),se for mais de um separados por vírgula",true);
		$this->addResourceParam("spam","string",true,"1 - SPAM, 2 - Não é SPAM",true);
	}

	public function post($request)
	{
 		$this->setParams( $request ); 		

 		$result = "false";

		$folderID 	= trim($this->getParam('folderID'));
		$msgsID		= trim($this->getParam('msgID'));
		$spam		= trim($this->getParam('spam'));

		if( ( $folderID !== "" && $msgID !== "" ) && $spam !== "" ){	
			if( $spam === "1" || $spam === "2" ){
				$result = $this->spamMessage( $folderID, $msgsID, $spam );
			}
		}	

		$this->setResult( $result );

		return $this->getResponse();		
	}
}
