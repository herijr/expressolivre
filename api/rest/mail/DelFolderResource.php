<?php

class DelFolderResource extends MailAdapter {

	public function setDocumentation() {

		$this->setResource("Mail","Mail/DelFolder","Exclui uma pasta.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("folderID","string",true,"ID da pasta que será excluída.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if($this->isLoggedIn())
		{
			$params = array();
			
			$params['del_past'] = $folder_id = mb_convert_encoding($this->getParam('folderID'), "UTF-8","ISO-8859-1");

			if(!$this->getImap()->folder_exists(mb_convert_encoding($folder_id, "UTF7-IMAP","UTF-8")))
				Errors::runException("MAIL_INVALID_FOLDER");

			$default_folders = array_keys($this->defaultFolders);
			if(in_array($folder_id, $default_folders))
				Errors::runException("MAIL_CANNOT_DEL_DEFAULT_FOLDER");

			$personal_folders = $this->getImap()->get_folders_list(array('noSharedFolders' => true, 'folderType' => 'personal'));
			if(!$personal_folders){
				return $this->getResponse();
			}
			foreach($personal_folders AS $personal_folder){
				if($personal_folder['folder_id'] == $folder_id && $personal_folder['folder_hasChildren'])
					Errors::runException("MAIL_FOLDER_NOT_EMPTY");
			}

			if($this->getImap()->get_num_msgs(array('folder' => $folder_id)) > 0)
				Errors::runException("MAIL_FOLDER_NOT_EMPTY");

			// TODO: verificar o que ocorre com o objeto imap nas validações acima. Por algum motivo, recriando o objeto, o método delete_mailbox funciona, mas sem recriar, não funciona.
			$this->imap = null;

			$result = $this->getImap()->delete_mailbox($params);

			if( !$result['status'] ) {
				Errors::runException("MAIL_FOLDER_NOT_DELETED");
			}
		}

		$this->setResult(true);

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}

}
