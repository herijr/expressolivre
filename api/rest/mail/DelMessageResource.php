<?php

class DelMessageResource extends MailAdapter {

	public function setDocumentation() {

		$this->setResource("Mail","Mail/DelMessage","Exclui uma mensagem.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("folderID","string",true,"ID da pasta que a mensagem que será excluída está.");
		$this->addResourceParam("msgID","string",true,"IDs das mensagens a serem excluídas, separados por vírgula.");

	}


	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if($this-> isLoggedIn())
		{
			$_result	= array();
			$msgID		= $this->getParam('msgID');
			$folderID 	= $this->getParam('folderID');

			if(!$this->getImap()->folder_exists($folderID))
				Errors::runException("MAIL_INVALID_FOLDER");
				
			if ($msgID == "") {
				Errors::runException("MAIL_INVALID_MESSAGE");
			}
			
			if (!$this->messageExists($folderID,$msgID)) {
				Errors::runException("MAIL_INVALID_MESSAGE");
			}
			
			$trash_folder = array_search(3,$this->defaultFolders);
			$params = array();
			$params['folder'] = $folderID;
			$params['msgs_number'] = $msgID;
			
			if (($folderID != $trash_folder) && ($this->getImap()->prefs['save_deleted_msg'])) {
				
				if ($trash_folder == "") 
					Errors::runException("MAIL_TRASH_FOLDER_NOT_EXISTS");
				
				$params['new_folder'] = $trash_folder;
				$this->getImap()->move_messages($params);
			}
			else
			{
				$this->getImap()->delete_msgs( $params ); 
			}
			
			$this->setResult( true );
		}

		return $this->getResponse();
	}
}
