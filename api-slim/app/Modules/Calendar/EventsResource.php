<?php

namespace App\Modules\Calendar;

use App\Errors;
use App\Adapters\CalendarAdapter;
use Respect\Validation\Validator as v;

class EventsResource extends CalendarAdapter
{
	public function post($request)
	{
		$date_start  = $request['dateStart'];
		$date_end    = $request['dateEnd'];

		// VALIDACOES
		if (v::notEmpty()->validate($date_start) && !v::date('d/m/Y')->validate($date_start)) {
			return Errors::runException("CALENDAR_INVALID_START_DATE");
		}

		if (v::notEmpty()->validate($date_end) && !v::date('d/m/Y')->validate($date_end)) {
			return Errors::runException("CALENDAR_INVALID_END_DATE");
		}

		// get the start timestamp UNIX from the parameter
		$start_arr      = explode(' ', $date_start);
		$start_date_arr = explode('/', $start_arr[0]);

		// get the end timestamp UNIX from the parameter
		$end_arr        = explode(' ', $date_end);
		$end_date_arr   = explode('/', $end_arr[0]);

		$return = array();

		$start_year = (int) $start_date_arr[2];
		$end_year = (int) $end_date_arr[2];

		for ($j = $start_year; $j <= $end_year; $j++) {
			$start_month = (int) $start_date_arr[1];
			$end_month = (int) $end_date_arr[1];

			if ($start_year != $end_year) {
				if ($j == $start_year) {
					$end_month = 12;
				} else {
					$start_month = 1;
				}
			}

			for ($i = $start_month; $i <= $end_month; $i++) {
				if ((int) $i < 10)
					$result = $this->getEvents("0" . $i, $j);
				else
					$result = $this->getEvents($i, $j);


				if (count($result) > 0) {
					$return[] = $result;
				}
			}
		}

		if (count($return) > 0) {
			$i = 0;
			for ($j = 0; $j < count($return); $j++) {
				foreach ($return[$j] as $key => $event) {
					foreach ($event as $value) {

						$events[$i]['eventID']			= "" . $value['id'];
						$events[$i]['eventDate']		= "" . $key;
						$events[$i]['eventName']		= "" . $value['title'];
						$events[$i]['eventDescription']	= "" . $value['description'];
						$events[$i]['eventLocation']	= "" . $value['location'];
						$events[$i]['eventParticipants'] = $value['participants'];

						$starttime	= $this->makeTime($value['start']);
						$endtime	= $this->makeTime($value['end']);
						$actualdate = mktime(0, 0, 0, substr($key, 4, 2), substr($key, 6), substr($key, 0, 4));
						$rawdate_offset = $actualdate - $this->getTimezoneOffset();
						$nextday = mktime(0, 0, 0, substr($key, 4, 2), substr($key, 6) + 1, substr($key, 0, 4)) - $this->getTimezoneOffset();

						if ($starttime <= $rawdate_offset && $endtime >= $nextday - 60) {
							$events[$i]['eventStartDate']	= substr($key, 6) . "/" . substr($key, 4, 2) . "/" . substr($key, 0, 4) . " 00:00";
							$events[$i]['eventEndDate']		= substr($key, 6) . "/" . substr($key, 4, 2) . "/" . substr($key, 0, 4) . " 23:59";
							$events[$i]['eventAllDay']		= "1";
						} else {
							if ($value['start']['mday'] === $value['end']['mday']) {
								$hour_start	= (((int) $value['start']['hour'] < 10) ? "0" . $value['start']['hour'] : $value['start']['hour']) . ":" . (((int) $value['start']['min'] < 10) ? "0" . $value['start']['min'] : $value['start']['min']);
								$hour_end	= (((int) $value['end']['hour'] < 10) ? "0" . $value['end']['hour'] : $value['end']['hour']) . ":" . (((int) $value['end']['min'] < 10) ? "0" . $value['end']['min'] : $value['end']['min']);
							} else {
								if ($events[$i - 1] && $events[$i - 1]['eventID'] == $value['id']) {
									$hour_start	= "00:00";
									$hour_end	= (((int) $value['end']['hour'] < 10) ? "0" . $value['end']['hour'] : $value['end']['hour']) . ":" . (((int) $value['end']['min'] < 10) ? "0" . $value['end']['min'] : $value['end']['min']);
								} else {
									$hour_start	= (((int) $value['start']['hour'] < 10) ? "0" . $value['start']['hour'] : $value['start']['hour']) . ":" . (((int) $value['start']['min'] < 10) ? "0" . $value['start']['min'] : $value['start']['min']);
									$hour_end	= "23:59";
								}
							}

							$events[$i]['eventStartDate']	= substr($key, 6) . "/" . substr($key, 4, 2) . "/" . substr($key, 0, 4) . " " . $hour_start;
							$events[$i]['eventEndDate']		= substr($key, 6) . "/" . substr($key, 4, 2) . "/" . substr($key, 0, 4) . " " . $hour_end;
							$events[$i]['eventAllDay']		= "0";
						}

						$events[$i++]['eventExParticipants'] = $value['ex_participants'];
					}
				}
			}

			return array('events' => $events);

		} else {

			return array('events' => array());
		}
	}
}
