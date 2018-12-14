<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class RenameFolderResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/RenameFolder","Renomeia uma pasta, recebe como parametros o \"folderID\" da pasta a ser renomeada, e o nome da nova pasta \"folderName\".",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("folderID","string",true,"ID da pasta que será renomeada.",false,"");
		$this->addResourceParam("folderName","string",true,"Nome da nova Pasta.");
	}

	public function post($request){

 		$this->setParams($request);

		$old_id  = mb_convert_encoding($this->getParam('folderID'), "UTF-8", "ISO-8859-1");
		$new_name = mb_convert_encoding($this->getParam('folderName'), "UTF-8", "ISO-8859-1");

		if(!$this->getImap()->folder_exists(mb_convert_encoding($old_id, "UTF7-IMAP", "UTF-8"))){
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		$default_folders = array_keys($this->defaultFolders);
		if(in_array($old_id, $default_folders)){
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		if(empty($new_name) || preg_match('/[\/\\\!\@\#\$\%\&\*\(\)]/', $new_name)){
			return Errors::runException("MAIL_INVALID_NEW_FOLDER_NAME");
		}

		$old_id_arr = explode($this->getImap()->imap_delimiter, $old_id);

		$new_id = implode($this->getImap()->imap_delimiter, array_slice($old_id_arr, 0, count($old_id_arr) - 1)) . $this->getImap()->imap_delimiter . $new_name;

		$params['current'] = $old_id;
		$params['rename']  = $new_id;

		$result = $this->getImap()->ren_mailbox($params);
		if( $result !== 'Ok' ){
			return Errors::runException("MAIL_FOLDER_NOT_RENAMED");
		}

		$this->setResult(array('folderID' => $new_id));

		return $this->getResponse();
	}

}
