<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class AttachmentResource extends MailAdapter
{
	public function post($request)
	{
		$folderID 		= $request['folderID'];
		$msgID 			= $request['msgID'];
		$attachmentID 	= $request['attachmentID'];

		if ($folderID && $msgID && $attachmentID) {
			include(PHPGW_INCLUDE_ROOT . '/expressoMail1_2/inc/class.exporteml.inc.php');
			$exp = new ExportEml();
			$exp->exportAttachments(array(
				'folder'     => $folderID,
				'msg_number' => $msgID,
				'section'    => $attachmentID,
			));
			// Dont modify header of Response Method to 'application/json'
			$this->setCannotModifyHeader(true);
			return $this->getResponse();
		} else {
			Errors::runException("MAIL_ATTACHMENT_NOT_FOUND");
		}
	}
}
