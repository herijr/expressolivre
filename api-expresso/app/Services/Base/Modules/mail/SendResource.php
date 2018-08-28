<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class SendResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/Send","Envia uma mensagem de email. Para enviar anexos na mensagem, a requisição POST deverá enviar arquivos por upload.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("msgTo","string",true,"Enviar mensagem para.");
		$this->addResourceParam("msgCcTo","string",false,"Enviar mensagem com cópia para.");
		$this->addResourceParam("msgBccTo","string",false,"Enviar mensagem com cópia oculta para.");
		$this->addResourceParam("msgReplyTo","string",false,"Responder Mensagem para.");
		$this->addResourceParam("msgType","string",false,"Tipo da mensagem (plain) por padrão. ");
		$this->addResourceParam("msgSubject","string",true,"Assunto da mensagem.");
		$this->addResourceParam("msgBody","text",true,"Conteúdo da mensagem.");
	}

	public function post($request){

 		$this->setParams( $request );

		$msgForwardTo		= $this->getParam("msgForwardTo");
		$originalMsgID		= $this->getParam("originalMsgID");
		$originalUserAction	= $this->getParam("originalUserAction");

		$params['input_subject']	= $this->getParam("msgSubject");
		$params['input_to']			= $this->getParam("msgTo");
		$params['input_cc']			= $this->getParam("msgCcTo");
		$params['input_cco']		= $this->getParam("msgBccTo");
		$params['input_replyto']	= $this->getParam("msgReplyTo");
		$params['body']				= $this->getParam("msgBody");
		$params['type']				= $this->getParam("msgType") ? $this->getParam("msgType") : "plain";
		$params['folder'] =	
		$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'] == "-1" ? "null" :
		$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'];

		if(count($_FILES))
		{
			$files = array();
			$totalSize = 0;
			foreach( $_FILES as $name => $file ){
				
				$files[$name] = array('name' => $file['name'],
						'type' => $file['type'],
						'source' => base64_encode(file_get_contents( $file['tmp_name'], $file['size'])),
						'size' => $file['size'],
						'error' => $file['error']
				);
				$totalSize += $file['size'];
			}
			
			$uploadMaxFileSize = str_replace("M","",$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']) * 1024 * 1024;
			if( $totalSize > $uploadMaxFileSize ){
				return Errors::runException("MAIL_NOT_SENT_LIMIT_EXCEEDED", $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size']);
			}
		}
		$returncode = $this->getImap()->send_mail($params);
		if (!$returncode || !(is_array($returncode) && $returncode['success'] == true)){
			return Errors::runException("MAIL_NOT_SENT");
		}

		$this->setResult(true);

		return $this->getResponse();
	}
}
