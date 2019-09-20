<?php

namespace App\Modules\Calendar;

use App\Errors;
use App\Adapters\CalendarAdapter;
use Respect\Validation\Validator as v;

class DelEventResource extends CalendarAdapter
{
	public function post($request)
	{
		//VALIDACOES DE CAMPOS 
		if (!v::notEmpty()->validate($request['eventID'])) {
			return Errors::runException("CALENDAR_INVALID_EVENTID");
		}

		$status = $this->delEvent($request['eventID']);

		return ($status == 16) ? true : Errors::runException("CALENDAR_EVENT_DELETE_ERROR");
	}
}
