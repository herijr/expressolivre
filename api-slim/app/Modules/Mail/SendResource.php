<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class SendResource extends MailAdapter
{
	public function post($request)
	{
		$this->loadConfigUser();

		$msgSaveDraft = ( isset($request['msgSaveDraft']) ? $request['msgSaveDraft'] : "false");
		$msgSaveDraft = strtolower($msgSaveDraft);
		$msgSaveDraft = (trim($msgSaveDraft) === "true" ? true : false);

		$params['input_subject'] = $request["msgSubject"];
		$params['input_to'] = $request["msgTo"];
		$params['input_cc'] = $request["msgCcTo"];
		$params['input_cco'] = $request["msgBccTo"];
		$params['input_replyto'] = $request["msgReplyTo"];
		$params['body'] = $request["msgBody"];
		$params['type'] = $request["msgType"] ? $request["msgType"] : "plain";
		$files = array();

		if (count($_FILES)) {
			$totalSize = 0;
			foreach ($_FILES as $name => $file) {
				$files[$name] = array(
					'name' => $file['name'],
					'type' => $file['type'],
					'source' => base64_encode(file_get_contents($file['tmp_name'], $file['size'])),
					'size' => $file['size'],
					'error' => $file['error'],
					'isbase64' => true
				);
				$totalSize += $file['size'];
			}

			$uploadMaxFileSize = str_replace("M", "", $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']) * 1024 * 1024;
			if ($totalSize > $uploadMaxFileSize) {
				return Errors::runException("MAIL_NOT_SENT_LIMIT_EXCEEDED", $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']);
			}
		}

		if (!$msgSaveDraft) {

			// parametros recuperados conforme draft
			$msgForwardTo		= $request["msgForwardTo"];
			$originalMsgID		= $request["originalMsgID"];
			$originalUserAction	= $request["originalUserAction"];

			$params['folder'] =
				$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'] == "-1" ? "null" : $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'];

			$returncode = $this->getImap()->send_mail($params);

			if (!$returncode || !(is_array($returncode) && $returncode['success'] == true)) {
				return Errors::runException("MAIL_NOT_SENT");
			}

			return true;

		} else {

			$params['msg_id'] = ( isset($request['msgId']) ? $request['msgId'] : '');

			if (isset($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'])) {
				$folderDrafts = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'];
			} else {
				$folderDrafts = $this->getImap()->functions->getLang("Drafts");
			}

			$params['folder'] = 'INBOX' . $this->imapDelimiter . $folderDrafts;
			$params['insertImg'] = 'false';
			$params['FILES'] = $files;
			$result = $this->getImap()->save_msg($params);

			if (isset($result->status) && !$result->status) {
				return Errors::runException("MAIL_NOT_SAVED_DRAFTS");
			}

			return array(
				'saveDraft' => ($result->status ? true : false),
				'msgId' => $result->uid,
				'folderID' => $result->folder
			);
		}
	}
}
