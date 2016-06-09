<?php

class EventCategoriesResource extends CalendarAdapter {
	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{

			$eventCategoryID = $this->getParam('eventCategoryID');

			$this->validateInteger($eventCategoryID,false,"CALENDAR_INVALID_CATEGORY");

			$result = array("eventCategories" => $this->getEventCategories($eventCategoryID));

			$this->setResult($result);


		}

		return $this->getResponse();
	}
}
