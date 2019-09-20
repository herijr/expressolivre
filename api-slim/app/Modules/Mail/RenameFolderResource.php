<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class RenameFolderResource extends MailAdapter
{
	public function post($request)
	{
		$old_id  = $request['folderID'];
		$new_name = $request['folderName'];

		if (!$this->getImap()->folder_exists($old_id)) {
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		$default_folders = array_keys($this->defaultFolders);

		if (in_array($old_id, $default_folders)) {
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		if (empty($new_name) || preg_match('/[\/\\\!\@\#\$\%\&\*\(\)]/', $new_name)) {
			return Errors::runException("MAIL_INVALID_NEW_FOLDER_NAME");
		}

		$old_id_arr = explode($this->getImap()->imap_delimiter, $old_id);

		$new_id     = implode($this->getImap()->imap_delimiter, array_slice($old_id_arr, 0, count($old_id_arr) - 1)) . $this->getImap()->imap_delimiter . $new_name;

		$params['current'] = $old_id;
		$params['rename']  = $new_id;

		$result = $this->getImap()->ren_mailbox($params);

		if (!$result['status']) {
			return Errors::runException("MAIL_FOLDER_NOT_RENAMED");
		}

		return array('folderID' => $new_id);
	}
}
