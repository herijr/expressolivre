<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class DelFolderResource extends MailAdapter
{
	public function post($request)
	{
		$params = array();

		$params['del_past'] = $folder_id = $request['folderID'];

		if (!$this->getImap()->folder_exists($folder_id)){
			return Errors::runException("MAIL_INVALID_FOLDER");
		}

		$default_folders = array_keys($this->defaultFolders);
		
		if (in_array($folder_id, $default_folders)){
			return Errors::runException("MAIL_CANNOT_DEL_DEFAULT_FOLDER");
		}

		$personal_folders = $this->getImap()->get_folders_list(array('noSharedFolders' => true, 'folderType' => 'personal'));
		
		if ( $personal_folders) {

			foreach ($personal_folders as $personal_folder) {
				if ($personal_folder['folder_id'] == $folder_id && $personal_folder['folder_hasChildren']){
					return Errors::runException("MAIL_FOLDER_NOT_EMPTY");
				}
			}

			if ($this->getImap()->get_num_msgs(array('folder' => $folder_id)) > 0){
				return Errors::runException("MAIL_FOLDER_NOT_EMPTY");
			}

			$this->imap = null;

			$result = $this->getImap()->delete_mailbox($params);

			if (!$result['status']) {
				return Errors::runException("MAIL_FOLDER_NOT_DELETED");
			}
		}

		return false;
	}
}
