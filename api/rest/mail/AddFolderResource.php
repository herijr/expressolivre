<?php

class AddFolderResource extends MailAdapter {

	public function setDocumentation() {

		$this->setResource("Mail","Mail/AddFolder","Adiciona uma nova pasta.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("parentfolderID","string",true,"Pasta base para adicionar a nova pasta.",true,"INBOX");
		$this->addResourceParam("folderName","string",true,"Nome da nova Pasta.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if($this-> isLoggedIn())
		{
			$parent_id = $this->getParam('parentFolderID');
			$parent_id = empty($parent_id) ? 'INBOX' : $parent_id;
			$new_name  = $this->getParam('folderName');
			$new_name = mb_convert_encoding($new_name, "UTF-8", "ISO-8859-1");

			$all_folders = $this->getImap()->get_folders_list();
			if(!$all_folders){
				return $this->getResponse();
			}

			$max_folders = $this->getImap()->prefs['imap_max_folders'];
			if(count($all_folders) == $max_folders)
				Errors::runException("MAIL_FOLDER_LIMIT_REACHED");

			if(empty($new_name) || preg_match('/[\/\\\!\@\#\$\%\&\*\(\)]/', $new_name))
				Errors::runException("MAIL_INVALID_NEW_FOLDER_NAME");

			$new_id = $parent_id . $this->getImap()->imap_delimiter . $new_name;

			$params['newp'] = $new_id;

			$result = $this->getImap()->create_mailbox($params);
			if($result != 'Ok')
				Errors::runException("MAIL_FOLDER_NOT_ADDED");
		}

		$this->setResult(array('folderID' => $new_id));

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}

}
