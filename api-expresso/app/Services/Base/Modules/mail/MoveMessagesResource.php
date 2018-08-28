<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class MoveMessagesResource extends MailAdapter {

	public function setDocumentation() {
		$this->setIsMobile(true);
		$this->setResource("Mail","Mail/MoveMessages", "Move uma mensagem para uma pasta.", array("POST") );
		$this->addResourceParam("folderID","string",true,"ID pasta de origem", false );
		$this->addResourceParam("msgID","string",true,"Msg(s) ID(s),se for mais de um separados por vírgula",true);
		$this->addResourceParam("toFolderID","string",true,"ID pasta de destino");
	}

	public function post($request)
	{
 		$this->setParams( $request );

		$result = array();

		$folderID 	= trim( $this->getParam('folderID') );
		$msgID		= trim( $this->getParam('msgID') );
		$toFolderID	= trim( $this->getParam('toFolderID') );

		$msgArray	= array();
		
		$msgArray = ( strrpos( $msgID, ",") !== FALSE ) ? explode(",", $msgID ) : array( $msgID );

		foreach( $msgArray as $msg ) {
			$result[] = $this->moveMessage( $folderID, $msg, $toFolderID );
		}

		$this->setResult( $result );

		return $this->getResponse();
	}
}
