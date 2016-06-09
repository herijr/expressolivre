<?php

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
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this-> isLoggedIn() )
		{
			$folderID 	= trim($this->getParam('folderID'));	
			$msgID		= trim($this->getParam('msgID'));
			$flagType	= trim($this->getParam('flagType'));

			$_result = false;

			if( $folderID !== "" )
			{
				if( $msgID !== "" )
				{
					if( $flagType !== "" )
					{
						switch ($flagType)
						{
							case '1':
								$_result = $this->flagMessage( $folderID, $msgID, "flagged" );
								break;
							case '2':
								$_result = $this->flagMessage( $folderID, $msgID, "unflagged" );
								break;
							case '3':
								$_result = $this->flagMessage( $folderID, $msgID, "seen" );
								break;
							case '4':
								$_result = $this->flagMessage( $folderID, $msgID, "unseen" );
								break;
							case '5':
								$_result = $this->flagMessage( $folderID, $msgID, "answered" );
								break;
							case '6':
								$_result = $this->flagMessage( $folderID, $msgID, "forwarded" );
								break;
						}
					}
					else
					{ 
						Errors::runException("MAIL_FLAGTYPE_EMPTY");
					}	
				}
				else
				{
					Errors::runException("MAIL_MSG_ID_EMPTY");
				}
			}
			else
			{
				Errors::runException("MAIL_FOLDER_ID_EMPTY");
			}
		
			$this->setResult( $_result );
		}
		
		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}

}
