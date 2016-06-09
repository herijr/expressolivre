<?php

class DelEventResource extends CalendarAdapter {
	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$eventID  = $this->getParam('eventID');
			

			$retCode = $this->delEvent($eventID);

			if ($retCode == 16) {
				$this->setResult(true);
			} else {
				Errors::runException("CALENDAR_EVENT_DELETE_ERROR");
			}

		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}
