<?php

class MoveMessagesResource extends MailAdapter {

	public function setDocumentation() {

		$this->setIsMobile(true);
		$this->setResource("Mail","Mail/MoveMessages", "Move uma mensagem para uma pasta.", array("POST") );
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("folderID","string",true,"ID pasta de origem", false );
		$this->addResourceParam("msgID","string",true,"Msg(s) ID(s),se for mais de um separados por vírgula",true);
		$this->addResourceParam("toFolderID","string",true,"ID pasta de destino");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		$_result = array();

		if( $this->isLoggedIn() )
		{
			$folderID 	= trim( $this->getParam('folderID') );
			$msgID		= trim( $this->getParam('msgID') );
			$toFolderID	= trim( $this->getParam('toFolderID') );

			$msgArray	= array();

			if( strrpos( $msgID, ",") !== FALSE )
			{
				$msgArray = explode(",", $msgID );
			}
			else
			{
				$msgArray[0] = $msgID;
			}

			foreach( $msgArray as $msg )
			{
				$_result[] = $this->moveMessage( $folderID, $msg, $toFolderID );
			}

			$this->setResult( $_result );
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}

}
