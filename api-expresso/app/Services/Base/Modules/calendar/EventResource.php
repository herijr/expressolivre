<?php

namespace App\Services\Base\Modules\calendar;

use App\Services\Base\Adapters\CalendarAdapter;
use App\Services\Base\Commons\Errors;

class EventResource extends CalendarAdapter {

	public function setDocumentation() {
		$this->setResource("Calendar","Calendar/Event","Retorna o evento da agenda pessoal do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("eventID","integer",true,"ID do evento que será retornado.");
	}

	public function post($request){

 		$this->setParams( $request );

		$eventID  = $this->getParam('eventID');

		//VALIDAÇÕES DE CAMPOS 
		$this->validateInteger($eventID,false,"CALENDAR_INVALID_EVENTID");

		$result = array();
		
		if ($eventID != "") {
			$event = $this->getEventByID($eventID);
			if ($event['eventID'] != $eventID) {
				return Errors::runException("CALENDAR_INVALID_EVENTID");
			}
			$result = array( 'events' => array( $event ) );
		}
		
		$this->setResult($result);
		
		 
		return $this->getResponse();
	}
}
