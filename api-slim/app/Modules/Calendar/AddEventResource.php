<?php

namespace App\Modules\Calendar;

use App\Errors;
use App\Adapters\CalendarAdapter;
use App\Encoding\ISO8859;
use Respect\Validation\Validator as v;

class AddEventResource extends CalendarAdapter
{
	private $iso;

	public function __construct()
	{
		$this->iso = new ISO8859();
	}

	public function post($request)
	{
		$eventDateStart = $request['eventDateStart'];
		$eventDateEnd   = $request['eventDateEnd'];
		$eventTimeStart = $request['eventTimeStart'];
		$eventTimeEnd   = $request['eventTimeEnd'];

		$eventID         = $request['eventID'];
		$eventCategoryID = $request['eventCategoryID'];
		$eventType       = $request['eventType'];

		$eventName         = $this->iso->encoding($request['eventName']);
		$eventDescription  = $this->iso->encoding($request['eventDescription']);
		$eventLocation     = $this->iso->encoding($request['eventLocation']);

		$eventPriority             = $request['eventPriority'];
		$eventOwnerIsParticipant   = $request['eventOwnerIsParticipant'];
		$eventParticipants         = $request['eventParticipants'];
		$eventExternalParticipants = $request['eventExternalParticipants'];

		//USADO PARA FORCAR A INCLUSAO DE UM EVENTO IGNORANDO CONFLITOS DA AGENDA.
		$eventIgnoreConflicts = $request['eventIgnoreConflicts'];

		$this->addModuleTranslation("calendar");

		//VALIDACOES DE CAMPOS
		$eventID = trim($eventID);

		if (v::notEmpty()->validate($eventID) && !v::numeric()->validate($eventID))
			return Errors::runException("CALENDAR_INVALID_EVENTID");

		if ($eventID != "") {
			$updateEvent = $this->getEventByID($eventID);
			if ($updateEvent['eventID'] != $eventID) {
				return Errors::runException("CALENDAR_INVALID_EVENTID");
			}
		}

		$eventValidCategories = array();
		if (!empty($eventCategoryID)) {
			$eventCategories = explode(",", $eventCategoryID);

			foreach ($eventCategories as $eventCategory) {
				if (v::notEmpty()->validate($eventCategory) && !v::numeric()->validate($eventCategory)) {
					return Errors::runException("CALENDAR_INVALID_CATEGORY");
				}
				if ($eventCategory != "") {
					$evtCategory = $this->getEventCategories($eventCategory);

					if ($evtCategory[0]['eventCategoryID'] != $eventCategory) {
						return Errors::runException("CALENDAR_INVALID_CATEGORY");
					} else {
						$eventValidCategories[] = $evtCategory[0]['eventCategoryID'];
					}
				}
			}
		}

		if (v::notEmpty()->validate($eventDateStart) && !v::date('d/m/Y')->validate($eventDateStart)) {
			return Errors::runException("CALENDAR_INVALID_START_DATE");
		}

		if (v::notEmpty()->validate($eventDateEnd) && !v::date('d/m/Y')->validate($eventDateEnd)) {
			return Errors::runException("CALENDAR_INVALID_END_DATE");
		}

		if (v::notEmpty()->validate($eventTimeStart) && !v::date('H:i')->validate($eventTimeStart)) {
			return Errors::runException("CALENDAR_INVALID_START_TIME");
		}

		if (v::notEmpty()->validate($eventTimeEnd) && !v::date('H:i')->validate($eventTimeEnd)) {
			return Errors::runException("CALENDAR_INVALID_END_TIME");
		}

		if (v::notEmpty()->validate($eventType) && !v::numeric()->between(1, 2, true)->validate($eventType)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_TYPE");
		}

		if (v::notEmpty()->validate($eventPriority) && !v::numeric()->between(0, 3, true)->validate($eventPriority)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_PRIORITY");
		}

		if (!v::notEmpty()->validate($eventName)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_NAME");
		}

		if (v::notEmpty()->validate($eventIgnoreConflicts) && !v::numeric()->between(0, 1, true)->validate($eventIgnoreConflicts)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_TYPE");
		}

		if (v::notEmpty()->validate($eventOwnerIsParticipant) && !v::numeric()->between(0, 1, true)->validate($eventOwnerIsParticipant)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_OWNER_IS_PARTICIPANT");
		}

		if (v::equals(0)->validate($eventOwnerIsParticipant)) {
			return Errors::runException("CALENDAR_INVALID_EVENT_PARTICIPANTS");
		}

		$participants = array();
		if ($eventParticipants != "") {
			$arrParticipants = explode(",", $eventParticipants);
			foreach ($arrParticipants as $participantID) {
				if (v::notEmpty()->validate($participantID) && !v::numeric()->validate($participantID)) {
					return Errors::runException("CALENDAR_INVALID_EVENT_PARTICIPANTS");
				}
				$accountData = $GLOBALS['phpgw']->accounts->get_account_data($participantID);
				//CHECK IF THE PARTICIPANT EXISTS IN LDAP.

				if (isset($accountData[""])) {
					if ($accountData[""]["lid"] == null) {
						return Errors::runException("CALENDAR_INVALID_EVENT_PARTICIPANTS");
					}
				}
				$eventParticipant['contactUIDNumber'] = $participantID;
				$eventParticipant['contactFullName'] = $accountData[$participantID]["fullname"];
				$participants[] = trim($participantID) . $this->getParticipantResponseInEvent($updateEvent, trim($participantID));
			}
		}


		//FORMATACAO DE CAMPOS
		if ($eventOwnerIsParticipant == "") { $eventOwnerIsParticipant = "1"; }

		if ($eventType == "") { $eventType = "1"; }

		if ($eventPriority == "") { $eventPriority = "0"; }

		$eventFormatedType = 'normal';
		
		if ($eventType == '1') { $eventFormatedType = 'normal'; }
		if ($eventType == '2') { $eventFormatedType = 'private'; }
		if ($eventType == '3') { $eventFormatedType = 'privateHiddenFields'; }
		if ($eventType == '4') { $eventFormatedType = 'hourAppointment'; }

		$arrTimeStart = explode(":", $eventTimeStart);
		$arrTimeEnd = 	explode(":", $eventTimeEnd);

		$cal['id']				= $eventID;
		$cal['title']			= $eventName;
		$cal['description']		= $eventDescription;
		$cal['location'] 		= $eventLocation;
		$cal['owner']			= $GLOBALS['phpgw_info']['user']['account_id'];
		$cal['priority']		= $eventPriority;
		$cal['type'] 			= $eventFormatedType;

		//ALARMES E REPETICAO DE EVENTOS NAO SAO UTILIZADAS NA API
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

		//PARAMETRO SENDTOUI ADICIONADO AO BO.CALENDAR PARA EVITAR QUE O BO FACA O REDIRECIONAMENTO PARA UM TEMPLATE.
		$params['sendToUi'] 		= '0';
		$params['forceOverlapEvents']  = $eventIgnoreConflicts;

		$eventID = $this->addEvent($params);

		if (is_array($eventID)) {
			return Errors::runException("CALENDAR_EVENT_UNKNOW_EXCEPTION");
		}

		return array('eventID' => "" . $eventID);
	}

	private function getParticipantResponseInEvent($event, $participantUIDNumber)
	{
		$response = "U";

		foreach ($event['eventParticipants'] as $participant) {

			if ($participant['contactUIDNumber'] == $participantUIDNumber) {

				$pResponse = $participant['contactResponse'];

				if ($pResponse == "0") { $response = "U"; }
				if ($pResponse == "1") { $response = "A"; }
				if ($pResponse == "2") { $response = "R"; }
			}
		}

		return $response;
	}
}
