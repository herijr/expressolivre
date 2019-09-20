<?php

namespace App\Modules\Mail;

use App\Errors;
use App\Adapters\MailAdapter;

class FoldersResource extends MailAdapter {	

	public function post($request){

		$search = isset( $request['search'] ) ? $request['search'] : null;

		$folders = $this->getImap()->get_folders_list( array( 'onload' => true ) );

		$result = array();

		foreach ($folders as $key => $value) {
			if (is_int($key)) {

				$folderName = $value['folder_name'];
				$folderName = (strtoupper($folderName) === "INBOX" ? $this->getImap()->functions->getLang("Inbox") : $folderName);
				$folderParentID = $value['folder_parent'];
				$folderHasChildren = $value['folder_hasChildren'];

				$folderID = $value['folder_id'];
				$folderID = trim($folderID);

				$folderType = (substr($value['folder_id'], 0, 4) == "user" ? "6" : "5");
				$folderType = array_key_exists($folderID, $this->defaultFolders) !== false ? strval($this->defaultFolders[$folderID]) : $folderType;

				$qtdUnreadMessages = $value['folder_unseen'];

				$result['folders'][] = array(
					'folderName' => $folderName,
					'folderParentID' => $folderParentID,
					'folderHasChildren' => $folderHasChildren,
					'qtdUnreadMessages' => $qtdUnreadMessages,
					'qtdMessages' => '0',
					'folderID' => $folderID,
					'folderType' => $folderType,
					'diskSizeUsed' => '0',
					'diskSizePercent' => '0',
				);
			}
		}

		$matches = array();

		if( $search != null ){
			foreach( $result['folders'] as $key => $value ){
				if( preg_match('/'.$search.'/i', $value['folderName'] ) ){
					$matches['folders'][] = $value;
				}
			}

			unset( $result['folders'] );
			$result = ( count($matches) > 0 ) ? $matches : array( "folders"=> array() );
		}

		$result["diskSizeUsed"] = intval($folders['quota_used']) > 0 ? $folders['quota_used'] * 1024 : 0;
		$result["diskSizeLimit"] = intval($folders['quota_limit']) > 0 ? $folders['quota_limit'] * 1024 : 0;
		$result["diskSizePercent"] = intval($folders['quota_percent']) > 0 ? $folders['quota_percent'] / 100 : 0;

		return $result;
	}	
}
