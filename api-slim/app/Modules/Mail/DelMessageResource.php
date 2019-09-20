<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class DelMessageResource extends MailAdapter
{
	public function post($request)
	{

		$result	= array();
		$msgID = $request['msgID'];
		$folderID = $request['folderID'];

		if (!$this->getImap()->folder_exists($folderID))
			return Errors::runException("MAIL_INVALID_FOLDER");

		if ($msgID == "") {
			return Errors::runException("MAIL_INVALID_MESSAGE");
		}

		if (!$this->messageExists($folderID, $msgID)) {
			return Errors::runException("MAIL_INVALID_MESSAGE");
		}

		$trash_folder = array_search(3, $this->defaultFolders);
		$params = array();
		$params['folder'] = $folderID;
		$params['msgs_number'] = $msgID;

		if (($folderID != $trash_folder) && ($this->getImap()->prefs['save_deleted_msg'])) {

			if ($trash_folder == "") {
				return Errors::runException("MAIL_TRASH_FOLDER_NOT_EXISTS");
			}

			$params['new_folder'] = $trash_folder;

			$result = $this->getImap()->move_messages($params);
		} else {
			$result = $this->getImap()->delete_msgs($params);
		}

		if (isset($result['error']) && trim($result['error']) !== "") {
			return false;
		} else {
			return true;
		}
	}
}
