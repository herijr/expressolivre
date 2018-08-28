<?php

namespace App\Services\Base\adapters;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class CalendarAdapter extends ExpressoAdapter {

	private $bo;

	protected function checkEvent($hash_vcalendar){

		$select = sprintf( "SELECT * FROM phpgw_cal_invite WHERE 
												hash = '%s'", pg_escape_string( $hash_vcalendar ) );
		
		if (!$this->getDb()->query($select)){
			return false;
		} else {
			if( $this->getDb()->next_record()){
				$data = $this->getDb()->row();
				if( is_null($data['imported_at'])){
					return $data;
				}
			}
		}
		return false;
	}
	
	protected function importEvent( $vcalendar ){
		$boCalendar = CreateObject('calendar.boicalendar');
		$result = ( $boCalendar->import( $vcalendar ) ? true : false );
		return false;
	}

	protected function getUserId(){
		return $GLOBALS['phpgw_info']['user']['account_id'];
	}

	protected function getDb(){
		return $GLOBALS['phpgw']->db;
	}

	protected function getTimezoneOffset(){
		return $GLOBALS['phpgw']->datetime->tz_offset;
	}

	protected function getEventByID($eventID){
		$this->bo = CreateObject('calendar.bocalendar',1);

		$event = $this->bo->read_entry($eventID);

		//return $event;
		return $this->formatArrayEvent($event);
	}

	protected function formatArrayEvent($event){
		$newArrEvent = array();
		if( $event && is_array($event) ){
			$newArrEvent['eventID'] = "" . $event['id'];
			$newArrEvent['eventOwner'] = "" . $event['owner'];

			$newArrEvent['eventName'] = "" . mb_convert_encoding("".$event['title'],"UTF8","ISO_8859-1");
			$newArrEvent['eventDescription'] = "" . mb_convert_encoding("".$event['description'],"UTF8","ISO_8859-1");
			$newArrEvent['eventLocation'] = "" . mb_convert_encoding("".$event['location'],"UTF8","ISO_8859-1");
			$newArrEvent['eventExParticipants'] = "" . mb_convert_encoding("".$event['ex_participants'],"UTF8","ISO_8859-1");

			$newArrEvent['eventCategoryID'] = "" . $event['category'];

			$hour_start	= (((int)$event['start']['hour'] < 10 ) ? "0".$event['start']['hour'] : $event['start']['hour']).":".(((int)$event['start']['min'] < 10 ) ? "0".$event['start']['min'] : $event['start']['min'] );
			$hour_end	= (((int)$event['end']['hour'] < 10 ) ? "0".$event['end']['hour'] : $event['end']['hour']).":".(((int)$event['end']['min'] < 10 ) ? "0".$event['end']['min'] : $event['end']['min'] );

			$newArrEvent['eventDateStart'] = "" . $event['start']['mday'] . "/" . $event['start']['month'] . "/" . $event['start']['year'];
			$newArrEvent['eventTimeStart'] = "" . $hour_start;

			$newArrEvent['eventDateEnd'] = "" . $event['end']['mday'] . "/" . $event['end']['month'] . "/" . $event['end']['year'];
			$newArrEvent['eventTimeEnd'] = "" . $hour_end;

			if( $event['priority'] == "0") { $event['priority'] = ""; }

			$newArrEvent['eventPriority'] = "" . $event['priority'];

			$eventParticipants = $event['participants'];

			foreach ($eventParticipants as $participantUID => $participantResponse) {
				$pResponse = 0;
				if ($participantResponse == "U") { $pResponse = 0; }
				if ($participantResponse == "A") { $pResponse = 1; }
				if ($participantResponse == "R") { $pResponse = 2; }
				$participant = array( 'contactUIDNumber' => "" .  $participantUID, 'contactResponse' =>  "" . $pResponse);
				$newArrEvent['eventParticipants'][] = $participant;
			}
		}
		
		return $newArrEvent;
	}

	protected function getEvents($month, $year){
		$this->bo = CreateObject('calendar.bocalendar',1);

		$events = $this->bo->store_to_cache(
					Array(
						'syear'	=> $year,
						'smonth'=> $month,
						'sday'	=> 1
					)
				);
		
		return $events;
	}

	protected function getEventCategories($categoryID){
		$this->bo = CreateObject('calendar.bocalendar',1);
		$this->bo->cat->categories($this->bo->bo->owner,'calendar');

		$categories = $this->bo->cat->return_sorted_array('',False,'','','',False, $cat_id);
		$arrCategories = array();
		if( count($categories) > 0 ){
			foreach ($categories as $category) {
				if (($categoryID == "") || ($categoryID == $category['id'])) {
					
					$newCategory['eventCategoryID'] = "" . $category['id'];
					$newCategory['eventCategoryName'] = "" . mb_convert_encoding($category['name'], "UTF-8", "ISO-8859-1");
					$newCategory['eventCategoryDescription'] = "" . mb_convert_encoding($category['description'], "UTF-8", "ISO-8859-1");

					$arrCategories[] = $newCategory;
				}
			}
		}
		return $arrCategories;
	}

	protected function delEvent($eventID){
		$this->bo = CreateObject('calendar.bocalendar',1);
		$retCode = $this->bo->delete_entry($eventID);
		$this->bo->expunge();
		return $retCode;
	}

	protected function addEvent($params){
		$this->bo = CreateObject('calendar.bocalendar',1);
		$GLOBALS['server'] = new \xmlrpc_server();
		$events = $this->bo->update($params);
		return $events;
	}
	
	protected function makeTime($event){
		$bo = CreateObject('calendar.bocalendar',1);
		return $bo->maketime($event);
	}
}
