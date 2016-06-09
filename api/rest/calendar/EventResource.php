<?php

class EventResource extends CalendarAdapter {

	public function setDocumentation() {

		$this->setResource("Calendar","Calendar/Event","Retorna o evento da agenda pessoal do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("eventID","integer",true,"ID do evento que será retornado.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$eventID  = $this->getParam('eventID');

			//VALIDAÇÕES DE CAMPOS 
			$this->validateInteger($eventID,false,"CALENDAR_INVALID_EVENTID");

			if ($eventID != "") {
				$event = $this->getEventByID($eventID);
				if ($event['eventID'] != $eventID) 
				{
					Errors::runException("CALENDAR_INVALID_EVENTID");
				}

				$result = array( 'events' => array( $event ) );

				$this->setResult($result);
			}
			
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}
