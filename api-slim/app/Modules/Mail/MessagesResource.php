<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class MessagesResource extends MailAdapter
{
	public function post($request)
	{
		$imap_msgs = null;

		$all_msgs = array();

		if ($request['folderID'] && $request['msgID'] > 0) {
			$msg = $this->getMessage();
			if (!$msg) {
				return Errors::runException("MAIL_MESSAGE_NOT_FOUND", $request['folderID']);
			} else {
				$result = array('messages' => array($msg));
				$this->setResult($result);
				return $this->getResponse();
			}
		} elseif ($request['search'] != "") {
			$imap = $this->getImap();
			$condition = array();
			$imap_folders =  $imap->get_folders_list();

			if ($this->getExpressoVersion() == "2.2") {

				foreach ($imap_folders as $i => $imap_folder) {
					if (is_int($i)) {
						$folder = $imap_folder['folder_id'];
						$condition[] = "$folder##ALL <=>" . $request['search'] . "##";
					}
				}

				$params = array(
					'condition' => implode(",", $condition),
					'page' 		=> (intval($request['page'] ? $request['page'] : "1")) - 1,
					'sort_type' => "SORTDATE"
				);

				$this->getImap()->prefs['preview_msg_subject'] = "1";

				$imap_msgs = $this->getImap()->search_msg($params);

				if ($imap_msgs['num_msgs'] > 0) {
					foreach ($imap_msgs['data'] as $imap_msg) {
						$msg = array();
						$msg['msgID'] = $imap_msg['uid'];
						$msg['folderID'] = $imap_msg['boxname'];
						$msg['msgDate']	= $imap_msg['udate'] . " 00:00";
						$msg['msgSubject'] = $imap_msg['subject'];
						$msg['msgSize'] = $imap_msg['size'];
						$msg['msgFrom']	= array('fullName' => $imap_msg['from'], 'mailAddress' => $imap_msg['fromaddress']);
						$msg['msgFlagged']	= strpos($imap_msg['flag'], "F") !== false ? "1" : "0";
						$msg['msgSeen']		= strpos($imap_msg['flag'], "U") !== false ? "0" : "1";
						$msg['msgHasAttachments'] = strpos($imap_msg['flag'], "T") !== false ? "1" : "0";
						$msg['msgForwarded'] = (strpos($imap_msg['flag'], "A") !== false && strpos($imap_msg['flag'], "X") !== false) ? "1" : "0";
						$msg['msgAnswered'] = $msg['msgForwarded'] != "1" && strpos($imap_msg['flag'], "A") !== false  ? "1" : "0";
						$msg['msgDraft'] 	= $msg['msgForwarded'] != "1" && strpos($imap_msg['flag'], "X") !== false ? "1" : "0";
						$all_msgs[] = $msg;
					}
				}
			}
		} else {

			$max_email_per_page = intval($request['resultsPerPage'] ? $request['resultsPerPage'] : $this->getImap()->prefs['max_email_per_page']);

			$current_page = intval($request['page'] ? $request['page'] : 1);

			$msg_range_begin = ($max_email_per_page * ($current_page - 1)) + 1;
			$msg_range_end = $msg_range_begin + ($max_email_per_page  - 1);

			$this->getImap()->prefs['preview_msg_subject'] = "1";

			$imap_msgs = $this->getImap()->get_range_msgs2(
				array(
					"folder"			=> $request['folderID'],
					"msg_range_begin" 	=> $msg_range_begin,
					"msg_range_end"	 	=> $msg_range_end,
					"search_box_type"	=> "ALL",
					"sort_box_reverse"	=> "1",
					"sort_box_type"		=> "SORTARRIVAL"
				)
			);
			if (!$imap_msgs) {
				return $this->getResponse();
			}

			$folderID = $request['folderID'];

			foreach ($imap_msgs as $i => $imap_msg) {
				if (!is_int($i)) {
					continue;
				}

				$msg = array();
				$msg['msgID'] = $imap_msg['msg_number'];
				$msg['folderID'] = $folderID;

				$msg['msgDate']	= gmdate('d/m/Y H:i', $imap_msg['timestamp']);
				$msg['msgFrom']['fullName'] = $imap_msg['from']['name'];
				$msg['msgFrom']['mailAddress'] = $imap_msg['from']['email'];
				$msg['msgTo'] = array();
				if ($this->getExpressoVersion() != "2.2") {
					foreach ($imap_msg['to'] as $to) {
						$msg['msgTo'][] = array('fullName' => $to['name'], 'mailAddress' => $to['email']);
					}
				} else {
					$msg['msgTo'][] = array('fullName' => $to['name'], 'mailAddress' => $imap_msg['to']['email']);
				}
				$msg['msgReplyTo'][0] = $this->formatMailObject($imap_msg['reply_toaddress']);
				$msg['msgSubject']  = $imap_msg['subject'];

				if ($this->getExpressoVersion() != "2.2") {
					$msg['msgHasAttachments'] = $imap_msg['attachment'] ? "1" : "0";
				} else {
					$msg['msgHasAttachments'] = $imap_msg['attachment']['number_attachments'] ? "1" : "0";
				}

				$msg['msgFlagged'] 	= $imap_msg['Flagged'] == "F" ? "1" : "0";
				$msg['msgForwarded'] = $imap_msg['Forwarded'] == "F" ? "1" : "0";
				$msg['msgAnswered'] = $imap_msg['Answered'] == "A" ? "1" : "0";
				$msg['msgDraft']	= $imap_msg['Draft'] == "X" ? "1" : "0";
				$msg['msgSeen'] 	= $imap_msg['Unseen'] == "U" ? "0" : "1";

				$msg['ContentType']	= $imap_msg['ContentType'];
				$msg['msgSize'] 	= $imap_msg['Size'];

				$msg['msgBodyResume'] = $imap_msg['msg_sample']['body'];

				if ($this->getExpressoVersion() != "2.2") {
					$msg['msgBodyResume'] =  base64_decode($msg['msgBodyResume']);
				}

				$msg['msgBodyResume'] = mb_substr($msg['msgBodyResume'], 2);
				$msg['msgBodyResume'] = str_replace(array("\r\n", "\n"), ' ', $msg['msgBodyResume']);
				$msg['msgBodyResume'] = trim(preg_replace('/\s+/', ' ', $msg['msgBodyResume']));

				$all_msgs[] = $msg;
			}
		}

		return array(
			'messages' 	  => $all_msgs,
			'timeZone'	  => $imap_msgs['offsetToGMT'] ? $imap_msgs['offsetToGMT'] : "",
			'totalUnseen' => $imap_msgs['tot_unseen'] ? $imap_msgs['tot_unseen'] : ""
		);
	}
}
