<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class FlagMessageResource extends MailAdapter
{
	public function post($request)
	{
		$folderID 	= trim($request['folderID']);
		$msgID		= trim($request['msgID']);
		$flagType	= trim($request['flagType']);

		$_result = false;

		if ($folderID !== "") {
			if ($msgID !== "") {
				if ($flagType !== "") {
					switch ($flagType) {
						case '1':
							$_result = $this->flagMessage($folderID, $msgID, "flagged");
							break;
						case '2':
							$_result = $this->flagMessage($folderID, $msgID, "unflagged");
							break;
						case '3':
							$_result = $this->flagMessage($folderID, $msgID, "seen");
							break;
						case '4':
							$_result = $this->flagMessage($folderID, $msgID, "unseen");
							break;
						case '5':
							$_result = $this->flagMessage($folderID, $msgID, "answered");
							break;
						case '6':
							$_result = $this->flagMessage($folderID, $msgID, "forwarded");
							break;
					}
				} else {
					return Errors::runException("MAIL_FLAGTYPE_EMPTY");
				}
			} else {
				return Errors::runException("MAIL_MSG_ID_EMPTY");
			}
		} else {
			return Errors::runException("MAIL_FOLDER_ID_EMPTY");
		}

		return $_result;
	}
}
