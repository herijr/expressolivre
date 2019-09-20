<?php

namespace App\Adapters;

use App\Adapters\ExpressoAdapter;

class CalendarAdapter extends ExpressoAdapter
{
	var $bo;

	protected function getUserId()
	{
		return $GLOBALS['phpgw_info']['user']['account_id'];
	}

	protected function getDb()
	{
		return $GLOBALS['phpgw']->db;
	}

	protected function getTimezoneOffset()
	{
		return $GLOBALS['phpgw']->datetime->tz_offset;
	}

	protected function getEventByID($eventID)
	{
		$this->bo = CreateObject('calendar.bocalendar', 1);

		$event = $this->bo->read_entry($eventID);

		//return $event;
		return $this->formatArrayEvent($event);
	}

	protected function formatArrayEvent($event)
	{
		$newArrEvent = array();

		$newArrEvent['eventID'] = "" . $event['id'];
		$newArrEvent['eventOwner'] = "" . $event['owner'];

		$newArrEvent['eventName'] = "" . $event['title'];
		$newArrEvent['eventDescription'] = "" . $event['description'];
		$newArrEvent['eventLocation'] = "" . $event['location'];
		$newArrEvent['eventExParticipants'] = "" . $event['ex_participants'];

		$newArrEvent['eventCategoryID'] = "" . $event['category'];

		$hour_start	= (((int) $event['start']['hour'] < 10) ? "0" . $event['start']['hour'] : $event['start']['hour']) . ":" . (((int) $event['start']['min'] < 10) ? "0" . $event['start']['min'] : $event['start']['min']);
		$hour_end	= (((int) $event['end']['hour'] < 10) ? "0" . $event['end']['hour'] : $event['end']['hour']) . ":" . (((int) $event['end']['min'] < 10) ? "0" . $event['end']['min'] : $event['end']['min']);

		$newArrEvent['eventDateStart'] = "" . $event['start']['mday'] . "/" . $event['start']['month'] . "/" . $event['start']['year'];
		$newArrEvent['eventTimeStart'] = "" . $hour_start;

		$newArrEvent['eventDateEnd'] = "" . $event['end']['mday'] . "/" . $event['end']['month'] . "/" . $event['end']['year'];
		$newArrEvent['eventTimeEnd'] = "" . $hour_end;

		if ($event['priority'] == "0") {
			$event['priority'] = "";
		}

		$newArrEvent['eventPriority'] = "" . $event['priority'];

		$eventParticipants = $event['participants'];

		foreach ($eventParticipants as $participantUID => $participantResponse) {

			$pResponse = 0;

			if ($participantResponse == "U") {
				$pResponse = 0;
			}
			if ($participantResponse == "A") {
				$pResponse = 1;
			}
			if ($participantResponse == "R") {
				$pResponse = 2;
			}

			$participant = array('contactUIDNumber' => "" .  $participantUID, 'contactResponse' =>  "" . $pResponse);

			$newArrEvent['eventParticipants'][] = $participant;
		}


		return $newArrEvent;
	}

	protected function getEvents($month, $year)
	{
		$this->bo = CreateObject('calendar.bocalendar', 1);

		$events = $this->bo->store_to_cache(
			array(
				'syear'	=> $year,
				'smonth' => $month,
				'sday'	=> 1
			)
		);

		return $events;
	}

	protected function getEventCategories($categoryID)
	{
		$this->bo = CreateObject('calendar.bocalendar', 1);
		
		$this->bo->cat->categories($this->bo->bo->owner, 'calendar');

		$categories = $this->bo->cat->return_sorted_array('', False, '', '', '', False, '');

		$arrCategories = array();

		if (count($categories) > 0) {

			foreach ($categories as $category) {
				if (($categoryID == "") || ($categoryID == $category['id'])) {

					$newCategory['eventCategoryID'] = "" . $category['id'];
					$newCategory['eventCategoryName'] = "" . mb_convert_encoding($category['name'], "UTF8", "ISO_8859-1");
					$newCategory['eventCategoryDescription'] = "" . mb_convert_encoding($category['description'], "UTF8", "ISO_8859-1");

					$arrCategories[] = $newCategory;
				}
			}
		}

		return $arrCategories;
	}

	protected function delEvent($eventID)
	{
		$this->bo = CreateObject('calendar.bocalendar', 1);

		$retCode = $this->bo->delete_entry($eventID);
		$this->bo->expunge();

		return $retCode;
	}

	protected function addEvent($params)
	{
		$this->bo = CreateObject('calendar.bocalendar', 1);

		$GLOBALS['server'] = new \xmlrpc_server();

		$events = $this->bo->update($params);


		return $events;
	}

	protected function makeTime($event)
	{
		$bo = CreateObject('calendar.bocalendar', 1);

		return $bo->maketime($event);
	}
}
