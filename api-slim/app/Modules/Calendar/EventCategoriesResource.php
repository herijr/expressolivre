<?php

namespace App\Modules\Calendar;

use App\Errors;
use App\Adapters\CalendarAdapter;
use Respect\Validation\Validator as v;

class EventCategoriesResource extends CalendarAdapter
{
	public function post($request)
	{
		$eventCategoryID = $request['eventCategoryID'];

		if ( !v::numeric()->validate($eventCategoryID)) {
			return Errors::runException("CALENDAR_INVALID_CATEGORY");
		}

		return array("eventCategories" => $this->getEventCategories($eventCategoryID));
	}
}
