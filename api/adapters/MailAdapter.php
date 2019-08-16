<?php

class MailAdapter extends ExpressoAdapter {
	protected $defaultFolders;
	protected $imap;
	protected $imapDelimiter;

	protected function formatMailObject($str) {
		$str = html_entity_decode($str);
		$str = preg_replace('/[<>\'"]/', '', $str);
		$return = array();            
		if(preg_match('/[[:alnum:]\._\-]+@[[:alnum:]_\-\.]+/',$str, $matches1) &&
			preg_match('/[[:alnum:]\._\-\ ]+/',$str, $matches2)){            		            
			return array(					            
					'fullName' 	  => str_replace($matches1[0],'', $str),
					'mailAddress' => $matches1[0]
					);
        }
        else{            	
           	return array('mailAddress' => $str);           	
		}		
    }
    
	protected function loadLang($lang_user){		
		$fn = PHPGW_INCLUDE_ROOT."/expressoMail1_2/setup/phpgw_".$lang_user.'.lang';
		if (file_exists($fn)){
			$fp = fopen($fn,'r');
			while ($data = fgets($fp,16000)){
				list($message_id,$app_name,$null,$content) = explode("\t",substr($data,0,-1));
				$_SESSION['phpgw_info']['expressomail']['lang'][$message_id] = $content;
			}
			fclose($fp);
		}
	}
	
	protected function loadConfigUser(){

		$configUser = CreateObject('phpgwapi.config','expressoMail1_2');
		$configUser->read_repository();
		$current_config = $configUser->config_data;
		$boemailadmin	= CreateObject('emailadmin.bo');
		$emailadmin_profile = $boemailadmin->getProfileList();
		$this->imapDelimiter = $emailadmin_profile[0]['imapdelimiter'];
		$_SESSION['phpgw_info']['expressomail']['email_server'] = $boemailadmin->getProfile($emailadmin_profile[0]['profileid']);

		if( !isset($_SESSION['phpgw_info']['user']['preferences']['expressoMail']) ){
			$preferences = $GLOBALS['phpgw']->preferences->read();
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail'] = $preferences['expressoMail'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['outoffice'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['outoffice'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number'] = $GLOBALS['phpgw_info']['user']['telephonenumber'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_cache'] = $current_config['expressoMail_enable_cache'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_x_origin'] = $current_config['expressoMail_use_x_origin'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['number_of_contacts'] = $current_config['expressoMail_Number_of_dynamic_contacts'] ? $current_config['expressoMail_Number_of_dynamic_contacts'] : "0";
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['notification_domains'] = $current_config['expressoMail_notification_domains'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] : "50";
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] : "4";			
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size'] = $current_config['expressoMail_Max_attachment_size'] ? $current_config['expressoMail_Max_attachment_size']."M" : ini_get('upload_max_filesize');
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] : "0";
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['imap_max_folders'] = $current_config['expressoMail_imap_max_folders'];
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] : "50";
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['extended_info']?$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['extended_info']:'0';
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] : "0";
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['return_recipient_deafault'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['return_recipient_deafault'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['return_recipient_deafault'] : "0";			
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] : 1;
		}

		// setting timezone preference
		$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['timezone'] =
			$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] ?
			$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] : 'America/Sao_Paulo';

		if( !isset($_SESSION['phpgw_info']['expressomail']) ){
			$_SESSION['phpgw_info']['expressomail']['email_server'] = $boemailadmin->getProfile($emailadmin_profile[0]['profileid']);
		}
		
		if( !isset($_SESSION['phpgw_info']['expressomail']['user']) ){
			$_SESSION['phpgw_info']['expressomail']['user']['userid'] = $GLOBALS['phpgw_info']['user']['userid'];
			$_SESSION['phpgw_info']['expressomail']['user']['passwd'] = $GLOBALS['phpgw_info']['user']['passwd'];
			$_SESSION['phpgw_info']['expressomail']['user']['email'] = $GLOBALS['phpgw']->preferences->values['email'];
		}
	}
	
	protected function getImap(){
		
		if( $this->imap == null ) {

			$this->loadConfigUser();

			$this->loadLang( $GLOBALS['phpgw_info']['user']['preferences']['common']['lang']);

			$this->imap = CreateObject("expressoMail1_2.imap_functions");

			if( $this->defaultFolders == null ) {
				$sent   = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'] = empty($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder']) ?
							$this->imap->functions->getLang("Sent") : $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'];
				$spam   = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'] = empty($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']) ?
							$this->imap->functions->getLang("Spam"): $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'];
				$drafts = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'] = empty($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder']) ?
							$this->imap->functions->getLang("Drafts") : $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'];
				$trash  = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'] = empty($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder']) ?
							$this->imap->functions->getLang("Trash") : $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'];

				$this->defaultFolders = array (
						'INBOX' => 0,
						'INBOX'.$this->imapDelimiter.$spam   => 2,
						'INBOX'.$this->imapDelimiter.$sent   => 1,
						'INBOX'.$this->imapDelimiter.$drafts => 4,
						'INBOX'.$this->imapDelimiter.$trash  => 3
					);
			}
		}

		return $this->imap;
	}
		
	protected function messageExists($folderID,$msgID) {
		 $info_msg = $this -> getImap()-> get_info_msg(
				array( 'msg_folder'	 => urlencode($folderID),
						'msg_number' => $msgID				
				));

		if($info_msg['status_get_msg_info'] == 'false'){
			return false;
		} else {
			return true;
		}
	}
	
	protected function spamMessage( $folderID, $msgsID, $spam )
	{
		$this->getImap();

		$msgArray =  array();

		if( strrpos( $msgsID, "," ) !== FALSE ) 
		{
			$msgArray = explode( "," , $msgsID );
		}
		else
		{
			$msgArray[0] = $msgsID;
		}

		$params = array();

		for( $i = 0 ; $i < count($msgArray); $i++ )
		{
			$params['folders'][$folderID][$i] = $msgArray[$i];
		}

		$params['spam'] = ( $spam === "1"  ? "true" : "false" );

		$_result = $this->imap()->spam( $params );

		if( isset($_result['status']) && $_result['status'] )
		{
			$toFolderID = ( $params['spam'] === "true" ) ? array_search( "2" , $this->defaultFolders ) : array_search( "0" , $this->defaultFolders );

			foreach( $msgArray as $msg )
			{
				$_return[] = $this->moveMessage( $folderID, $msg, $toFolderID );
			}
		}

		return $_return;
	}

	protected function moveMessage( $folderID, $msgID, $toFolderID )
	{
		return $this->getImap()->move_messages( 
			array(
			    "folder"		 	=> $folderID,
			    "msgs_number"		=> $msgID,
			    "new_folder" 		=> $toFolderID
			)			
		);
	}

	protected function flagMessage( $folderID, $msgID, $flagType )
	{
		return $this->getImap()->set_messages_flag( 
			array(
				'folder'	 	=> $folderID,
				'msgs_to_set'	=> $msgID,
				'flag'			=> $flagType
			)
		);
	}

	protected function getMessage(){				
		$info_msg = $this -> getImap()-> get_info_msg(
				array( 'msg_folder'	 => urlencode($this->getParam('folderID')),
						'msg_number' => $this->getParam('msgID')				
				));

		if($info_msg['status_get_msg_info'] == 'false'){
			return false;
		}

		$msg['msgID']    = $info_msg['msg_number'];
		$msg['folderID'] = $info_msg['msg_folder'];
		//$msg['msgDate']	 =  $info_msg['fulldate'];
		$msg['msgDate']  =  $info_msg['msg_day']." ".$info_msg['msg_hour'];
		if($info_msg['from']) {
			$msg['msgFrom']['fullName'] 	= $info_msg['from']['name'];
			$msg['msgFrom']['mailAddress'] 	= $info_msg['from']['email'];
		}
		if($info_msg['sender'] != null){
			$msg['msgSender']['fullName'] 	= $info_msg['sender']['name'];
			$msg['msgSender']['mailAddress']= $info_msg['sender']['email'];
		}		
		if($info_msg['toaddress2'] != null){
			$toaddresses = explode(",",$info_msg['toaddress2']);
			if(count($toaddresses) > 1) {
				foreach ($toaddresses as $i => $toaddress){
					$msg['msgTo'][$i] = $this->formatMailObject($toaddress);
				}
			}
			else{
				$msg['msgTo'][0] = $this->formatMailObject($info_msg['toaddress2']);
			}
		}
		if($info_msg['cc'] != null) {
			$ccaddresses = explode(",",$info_msg['cc']);
			if(count($ccaddresses) > 1) {
				foreach ($ccaddresses as $i => $ccaddress){
					$msg['msgCC'][$i] = $this->formatMailObject($ccaddress);
				}
			}
			else{
				$msg['msgCC'][0] = $this->formatMailObject($info_msg['cc']);
			}
		}		
		if($info_msg['reply_toaddress'] != null) {
			$msg['msgReplyTo'][0] = $this->formatMailObject($info_msg['reply_toaddress']);
		}						
		$msg['msgSubject']  = ($info_msg['subject'] ? $info_msg['subject'] : "");		
		$msg['msgHasAttachments'] = "0";

		if(count($info_msg['attachments']) > 0) {
			$msg['msgAttachments'] = array();
			foreach($info_msg['attachments'] as $i => $attachment){
				$msg['msgAttachments'][] = array (
					'attachmentID'			=> "".$attachment->section,
					'attachmentIndex'		=> "".$i,
					'attachmentName' 		=> "".$attachment->filename,
					'attachmentSize'		=> "".$attachment->size,
					'attachmentEncoding'	=> $attachment->encoding,
					'attachamentType'		=> $attachment->type
				);
			}
			$msg['msgHasAttachments'] = "1";
		}
		$msg['msgFlagged'] 	= $info_msg['Flagged'] == "F" ? "1" : "0";
		$msg['msgForwarded']= $info_msg['Forwarded'] == "F" ? "1" : "0";
		$msg['msgAnswered'] = $info_msg['Answered'] == "A" ? "1" : "0";
		$msg['msgDraft']	= $info_msg['Draft'] == "X" ? "1" : "0";
		$msg['msgSeen'] 	= $info_msg['Unseen'] == "U" ? "0" : "1";				
		$msg['msgSize'] 	= $info_msg['Size'];
		$msg['msgBody']		= ($info_msg['body'] ? $info_msg['body'] : "");

		if( isset($info_msg['hash_vcalendar']) ){
			$msg['msgHashVcalendar'] = $info_msg['hash_vcalendar'];
		}
		
		return $msg;		
	}
		
}
