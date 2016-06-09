<?php

class AddEventResource extends CalendarAdapter {

	

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$eventDateStart	 					= $this->getParam('eventDateStart');
			$eventDateEnd	 					= $this->getParam('eventDateEnd');
			$eventTimeStart 					= $this->getParam('eventTimeStart');
			$eventTimeEnd 						= $this->getParam('eventTimeEnd');

			$eventID 	 						= $this->getParam('eventID');
			$eventCategoryID					= $this->getParam('eventCategoryID');
			$eventType 	 						= $this->getParam('eventType');
			$eventName							= $this->getParam('eventName');
			
			$eventDescription 					= $this->getParam('eventDescription');
			$eventLocation 						= $this->getParam('eventLocation');
			$eventPriority 						= $this->getParam('eventPriority');
			$eventOwnerIsParticipant 			= $this->getParam('eventOwnerIsParticipant');
			$eventParticipants		 			= $this->getParam('eventParticipants');
			$eventExternalParticipants		 	= $this->getParam('eventExternalParticipants');

			//USADO PARA FORÇAR A INCLUSÃO DE UM EVENTO IGNORANDO CONFLITOS DA AGENDA.
			$eventIgnoreConflicts               = $this->getParam('eventIgnoreConflicts');

			$this->addModuleTranslation("calendar");

			//VALIDAÇÕES DE CAMPOS 
			$this->validateInteger($eventID,false,"CALENDAR_INVALID_EVENTID");

			if ($eventID != "") {
				$updateEvent = $this->getEventByID($eventID);
				if ($updateEvent['eventID'] != $eventID) 
				{
					Errors::runException("CALENDAR_INVALID_EVENTID");
				}
			}

			$eventValidCategories = array();
			if (!empty($eventCategoryID))
			{
				$eventCategories = explode(",", $eventCategoryID);

				foreach ($eventCategories as $eventCategory) 
				{
					$this->validateInteger($eventCategory, false, "CALENDAR_INVALID_CATEGORY");
					if ($eventCategory != "") 
					{
						$evtCategory = $this->getEventCategories($eventCategory);

						if ($evtCategory[0]['eventCategoryID'] != $eventCategory) 
							Errors::runException("CALENDAR_INVALID_CATEGORY");
						else
							$eventValidCategories[] = $evtCategory[0]['eventCategoryID'];
					}
				}
			}
			else
				Errors::runException("CALENDAR_INVALID_CATEGORY");

			$this->validateDate($eventDateStart,true,"CALENDAR_INVALID_START_DATE");
			$this->validateDate($eventDateEnd,true,"CALENDAR_INVALID_END_DATE");
			$this->validateTime($eventTimeStart,true,"CALENDAR_INVALID_START_TIME");
			$this->validateTime($eventTimeEnd,true,"CALENDAR_INVALID_END_TIME");
			$this->validateString($eventType,false,"CALENDAR_INVALID_EVENT_TYPE",array("1","2"));
			$this->validateString($eventPriority,false,"CALENDAR_INVALID_EVENT_PRIORITY",array("1","2","3"));
			$this->validateString($eventName,true,"CALENDAR_INVALID_EVENT_NAME");
			$this->validateString($eventIgnoreConflicts,false,"CALENDAR_INVALID_EVENT_TYPE",array("1"));
			$this->validateString($eventOwnerIsParticipant,false,"CALENDAR_INVALID_EVENT_OWNER_IS_PARTICIPANT",array("0","1"));
			if ($eventOwnerIsParticipant == "0") {
				$this->validateString($eventParticipants,true,"CALENDAR_INVALID_EVENT_PARTICIPANTS");
			}

			$participants = array();
			if ($eventParticipants != "") {
				$arrParticipants = explode(",",$eventParticipants);
				foreach ($arrParticipants as $participantID) {
					$this->validateInteger(trim($participantID),false,"CALENDAR_INVALID_EVENT_PARTICIPANTS");
					$accountData = $GLOBALS['phpgw']->accounts->get_account_data($participantID);
					//CHECK IF THE PARTICIPANT EXISTS IN LDAP.

					if (isset($accountData[""])) {
						if ($accountData[""]["lid"] == null) {
							Errors::runException("CALENDAR_INVALID_EVENT_PARTICIPANTS");
						}
					}
					$eventParticipant['contactUIDNumber'] = $participantID;
					$eventParticipant['contactFullName'] = $accountData[$participantID]["fullname"];
					$resultEventParticipants[] = $eventParticipant;
					$participants[] = trim($participantID) . $this->getParticipantResponseInEvent($updateEvent,trim($participantID));
				}
			}


			//FORMATAÇÃO DE CAMPOS
			if ($eventOwnerIsParticipant == "") {
				$eventOwnerIsParticipant = "1";
			}

			if ($eventType == "") {
				$eventType = "1";
			}

			if ($eventPriority == "") {
				$eventPriority = "0";
			}

			$eventFormatedType = 'normal';
			if ($eventType == '1') {
				$eventFormatedType = 'normal';
			} 
			if ($eventType == '2') {
				$eventFormatedType = 'private';
			}
			if ($eventType == '3') {
				$eventFormatedType = 'privateHiddenFields';
			}
			if ($eventType == '4') {
				$eventFormatedType = 'hourAppointment';
			}

			$arrTimeStart = explode(":",$eventTimeStart);
			$arrTimeEnd = 	explode(":",$eventTimeEnd);

			$cal['id']				= $eventID;
			$cal['title']			= $eventName;
			$cal['description']		= $eventDescription;
			$cal['location'] 		= $eventLocation;	
			$cal['owner']			= $GLOBALS['phpgw_info']['user']['account_id'];
			$cal['priority']		= $eventPriority;
			$cal['type'] 			= $eventFormatedType;

			//ALARMES E REPETIÇÃO DE EVENTOS NÃO SÃO UTILIZADAS NA API
			$cal['uid']				= '';
			$cal['recur_interval']	= '0';
			$cal['recur_type']		= '0';
			$cal['alarmdays']		= '0';
			$cal['alarmhours']		= '0';
			$cal['alarmminutes'] 	= '0';
			$recur_enddate['str']	= $eventDateEnd;
			
			//DATA DE INICIO
			$start['hour']			= $arrTimeStart[0];
			$start['min']			= $arrTimeStart[1];
			$start['str']			= $eventDateStart;

			//DATA DE TERMINO
			$end['hour'] 			= $arrTimeEnd[0];
			$end['min']				= $arrTimeEnd[1];
			$end['str'] 			= $eventDateEnd;
			
			//PARTICIPANTES
			if ($eventOwnerIsParticipant == "1") {
				$participants[]	= $GLOBALS['phpgw_info']['user']['account_id'] . 'A'; // A = ACCEPTED
			}
			
			$params = array();
			$params['cal'] 				= $cal;
			$params['ex_participants'] 	= $eventExternalParticipants;
			$params['participants'] 	= $participants;
			$params['recur_enddate'] 	= $recur_enddate;
			$params['start'] 			= $start;
			$params['end'] 				= $end;
			$params['categories']	    = $eventValidCategories;

			//PARAMETRO SENDTOUI ADICIONADO AO BO.CALENDAR PARA EVITAR QUE O BO FAÇA O REDIRECIONAMENTO PARA UM TEMPLATE.
			$params['sendToUi'] 		= '0';
			$params['forceOverlapEvents']  = $eventIgnoreConflicts;

			$eventID = $this->addEvent($params);

			if (is_array($eventID)) 
			{
				Errors::runException("CALENDAR_EVENT_UNKNOW_EXCEPTION");
			}

			$result['events'][] = array('eventID' => "". $eventID );


			$this->setResult($result);

		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}


	private function getParticipantResponseInEvent($event,$participantUIDNumber) 
	{
		$response = "U";
		foreach ($event['eventParticipants'] as $participant) {
			if ($participant['contactUIDNumber'] == $participantUIDNumber) 
			{
				$pResponse = $participant['contactResponse'];

				if ($pResponse == "0") {
					$response = "U"; 
				}
				if ($pResponse == "1") {
					$response = "A"; 
				}
				if ($pResponse == "2") {
					$response = "R"; 
				}
			}
		}
		return $response;
	}
}
