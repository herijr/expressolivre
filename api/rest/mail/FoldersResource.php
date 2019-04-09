<?php

class FoldersResource extends MailAdapter {	

	public function setDocumentation() {

		$this->setResource("Mail","Mail/Folders","Lista as pastas do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("folderID","string",true,"Pasta base para a busca.",true,"INBOX");
		$this->addResourceParam("search","string",true,"Buscar pastas com o nome.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)		
 		parent::post($request);	 		

		if ($this->isLoggedIn()) {
			
			$search = $this->getParam('search') ? mb_convert_encoding($this->getParam('search'),"ISO-8859-1", "UTF-8") : null;

			$folders = $this->getImap()->get_folders_list( array( 'onload' => true ) );

			$result = array();

			foreach ($folders as $key => $value) {
				if (is_int($key)) {

					$folderName = mb_convert_encoding($value['folder_name'], "UTF-8", "ISO-8859-1");
					$folderName = (strtoupper($folderName) === "INBOX" ? $this->getImap()->functions->getLang("Inbox") : $folderName);
					$folderParentID = mb_convert_encoding($value['folder_parent'], 'UTF-8', 'ISO-8859-1');
					$folderHasChildren = $value['folder_hasChildren'];

					$folderID = mb_convert_encoding($value['folder_id'], 'UTF-8', 'ISO-8859-1');
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

			$this->setResult( $result );
		}
		
		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}	

}
