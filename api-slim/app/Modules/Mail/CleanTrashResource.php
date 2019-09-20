<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class CleanTrashResource extends MailAdapter
{
	public function post($request)
	{
		$type = trim($request['type']);

		if ($type === "") {
			$params = array("type" => "trash");
		} else {
			switch ($type) {
				case "1":
					$params = array("type" => "trash");
					break;
				case "2":
					$params = array("type" => "spam");
					break;
				default:
					$params = array("type" => "trash");
					break;
			}
		}

		if (!$this->getImap()->clean_folder($params)) {
			return Errors::runException("MAIL_TRASH_NOT_CLEANED");
		}

		return true;
	}
}
