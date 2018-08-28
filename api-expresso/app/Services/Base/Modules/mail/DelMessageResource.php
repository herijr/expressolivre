<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class DelMessageResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/DelMessage","Exclui uma mensagem.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("folderID","string",true,"ID da pasta que a mensagem que será excluída está.");
		$this->addResourceParam("msgID","string",true,"ID da mensagem que será excluída.");
	}

	public function post($request){

 		$this->setParams( $request );

		$msgID		= $this->getParam('msgID');

		$folderID 	= $this->getParam('folderID');

		if(!$this->getImap()->folder_exists($folderID)){
			return Errors::runException("MAIL_INVALID_FOLDER");
		}
			
		if ($msgID == "") {
			return Errors::runException("MAIL_INVALID_MESSAGE");
		}
		
		if (!$this->messageExists($folderID,$msgID)) {
			return Errors::runException("MAIL_INVALID_MESSAGE");
		}
		
		$trash_folder = array_search(3,$this->defaultFolders);
		$params = array();
		$params['folder'] = $folderID;
		$params['msgs_number'] = $msgID;
		
		if (($folderID != $trash_folder) && ($this->getImap()->prefs['save_deleted_msg'])) {
			if ($trash_folder == ""){ 
				return Errors::runException("MAIL_TRASH_FOLDER_NOT_EXISTS");
			}
			$params['new_folder'] = $trash_folder;
			$this->getImap()->move_messages($params);
		} else {
			$this->getImap()->delete_msgs( $params ); 
		}
		
		$this->setResult( true );


		return $this->getResponse();
	}
}
