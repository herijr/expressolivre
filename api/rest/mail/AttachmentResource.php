<?php

class AttachmentResource extends MailAdapter {	
	
	public function setDocumentation() {

		$this->setResource("Mail","Mail/Attachment","Retorna para download o conteúdo de um Anexo.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("folderID","string",true,"ID da pasta da mensagem.");
		$this->addResourceParam("msgID","string",true,"ID da mensagem que contém o Anexo.");
		$this->addResourceParam("attachmentID","string",true,"ID do Anexo.");
	}

	public function post($request){		
		// to Receive POST Params (use $this->params)		
 		parent::post($request); 		
 		$folderID 		= $this->getParam('folderID');
 		$msgID 			= $this->getParam('msgID');
 		$attachmentID 	= $this->getParam('attachmentID');
 		
		if($this-> isLoggedIn()) {
								
			if( $folderID && $msgID && $attachmentID) {
				include ( PHPGW_INCLUDE_ROOT.'/expressoMail1_2/inc/class.exporteml.inc.php' );
				$exp = new ExportEml();
				$exp->exportAttachments( array(
					'folder'     => $folderID,
					'msg_number' => $msgID,
					'section'    => $attachmentID,
				) );
				// Dont modify header of Response Method to 'application/json'
				$this->setCannotModifyHeader(true);
				return $this->getResponse();
			}
			else{
				Errors::runException("MAIL_ATTACHMENT_NOT_FOUND");
			}
		}
	}
}
