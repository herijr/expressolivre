<?php

namespace App\Modules\Calendar;

use App\Errors;
use App\Adapters\CalendarAdapter;
use Respect\Validation\Validator as v;

class EventResource extends CalendarAdapter
{
	public function post($request)
	{
		//VALIDACOES DE CAMPOS 
		if (!v::notEmpty()->validate($request['eventID'])) {
			return Errors::runException("CALENDAR_INVALID_EVENTID");
		}

		if (trim($request['eventID']) !== "") {

			$event = $this->getEventByID($request['eventID']);

			if (trim($event['eventID']) !== trim($request['eventID'])) {

				return Errors::runException("CALENDAR_INVALID_EVENTID");
			}

			return array('events' => array($event));
		}
	}
}
