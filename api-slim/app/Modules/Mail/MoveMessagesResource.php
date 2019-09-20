<?php

namespace App\Modules\Mail;

use App\Adapters\MailAdapter;

class MoveMessagesResource extends MailAdapter
{

	public function post($request)
	{
		$_result = array();

		$folderID 	= trim($request['folderID']);
		$msgID		= trim($request['msgID']);
		$toFolderID	= trim($request['toFolderID']);

		$msgArray = array();

		$msgArray = (strrpos($msgID, ",") !== FALSE) ? explode(",", $msgID) : array($msgID);

		foreach ($msgArray as $msg) {
			$_result[] = $this->moveMessage($folderID, $msg, $toFolderID);
		}

		return $_result;
	}
}
