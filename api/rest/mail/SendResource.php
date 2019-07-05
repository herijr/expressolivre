<?php

class SendResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/Send","Envia uma mensagem de email. Para enviar anexos na mensagem, a requisição POST deverá enviar arquivos por upload.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("msgID", "string", false, "Id da mensagem( exclusivo para a salvar como rascunho ).");
		$this->addResourceParam("msgTo","string",true,"Enviar mensagem para.");
		$this->addResourceParam("msgCcTo","string",false,"Enviar mensagem com cópia para.");
		$this->addResourceParam("msgBccTo","string",false,"Enviar mensagem com cópia oculta para.");
		$this->addResourceParam("msgReplyTo","string",false,"Responder Mensagem para.");
		$this->addResourceParam("msgType","string",false,"Tipo da mensagem (plain) por padrão. ");
		$this->addResourceParam("msgSubject","string",true,"Assunto da mensagem.");
		$this->addResourceParam("msgBody","text",true,"Conteúdo da mensagem.");
		$this->addResourceParam("msgSaveDraft","string", false, "Salva a mensagem na pasta Rascunhos. True - salva / False - Não salva");
	}


	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if($this-> isLoggedIn())
		{
			$this->loadConfigUser();
			
			$msgSaveDraft = ( $this->getParam('msgSaveDraft') ? $this->getParam('msgSaveDraft') : "false" );
			$msgSaveDraft = strtolower( $msgSaveDraft );
			$msgSaveDraft = ( trim($msgSaveDraft) === "true" ? true : false );

			$params['input_subject'] = $this->getParam("msgSubject");
			$params['input_to'] = $this->getParam("msgTo");
			$params['input_cc'] = $this->getParam("msgCcTo");
			$params['input_cco'] = $this->getParam("msgBccTo");
			$params['input_replyto']	= $this->getParam("msgReplyTo");
			$params['body'] = $this->getParam("msgBody");
			$params['type'] = $this->getParam("msgType") ? $this->getParam("msgType") : "plain";
			$files = array();
			
			if( count($_FILES) ){
				$totalSize = 0;
				foreach( $_FILES as $name => $file ){
					$files[$name] = array('name' => $file['name'],
							'type' => $file['type'],
							'source' => base64_encode(file_get_contents( $file['tmp_name'], $file['size'])),
							'size' => $file['size'],
							'error' => $file['error'], 
							'isbase64' => true
					);
					$totalSize += $file['size'];
				}
				
				$uploadMaxFileSize = str_replace("M","",$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']) * 1024 * 1024;
				if( $totalSize > $uploadMaxFileSize ){
					Errors::runException("MAIL_NOT_SENT_LIMIT_EXCEEDED", $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']);
				}
			}
			
			if( !$msgSaveDraft )
			{
				// parametros recuperados conforme draft
				$msgForwardTo		= $this->getParam("msgForwardTo");
				$originalMsgID		= $this->getParam("originalMsgID");
				$originalUserAction	= $this->getParam("originalUserAction");

				$params['folder'] =	
					$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'] == "-1" ? "null" :
					$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'];

				$returncode = $this->getImap()->send_mail($params);
				
				if (!$returncode || !(is_array($returncode) && $returncode['success'] == true)){
					Errors::runException("MAIL_NOT_SENT");
				}
				
				$this->setResult(true);
				
			} else {

				$params['msg_id'] = ( $this->getParam('msgId') ? $this->getParam('msgId') : '' );
				
				if( isset($_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder']) ){
					$folderDrafts = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'];
				} else {
					$folderDrafts = $this->getImap()->functions->getLang("Drafts");
				}
				
				$params['folder'] = 'INBOX'.$this->imapDelimiter.$folderDrafts;
				$params['insertImg'] = 'false';
				$params['FILES'] = $files;
				$result = $this->getImap()->save_msg( $params );
				
				if( isset($result->status) && !$result->status ){
					Errors::runException("MAIL_NOT_SAVED_DRAFTS");
				}
				
				$this->setResult( array(
						'saveDraft' => ( $result->status ? true : false ),
						'msgId' => $result->uid,
						'folderID' => $result->folder
				));
				
			}
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}
