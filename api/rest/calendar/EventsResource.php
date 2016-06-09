<?php

class EventsResource extends CalendarAdapter {

	public function setDocumentation() {

		$this->setResource("Calendar","Calendar/Events","Retorna os Eventos da agenda pessoal do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("dateStart","string",false,"Data de início.");
		$this->addResourceParam("dateEnd","string",false,"Data de término.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$date_start  = $this->getParam('dateStart');
			$date_end    = $this->getParam('dateEnd');
			
			// check the dates parameters formats (ex: 31/12/2012 23:59:59, but the time is optional)
			$regex_date  = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/([12][0-9]{3})( ([01][0-9]|2[0-3])(:[0-5][0-9]){2})?$/';

			if(!preg_match($regex_date, $date_start))
				Errors::runException("CALENDAR_INVALID_START_DATE");

			if(!preg_match($regex_date, $date_end))
				Errors::runException("CALENDAR_INVALID_END_DATE");

			// get the start timestamp UNIX from the parameter
			$start_arr      = explode(' ', $date_start);
			$start_date_arr = explode('/', $start_arr[0]);

			// get the end timestamp UNIX from the parameter
			$end_arr        = explode(' ', $date_end);
			$end_date_arr   = explode('/', $end_arr[0]);
			
			$return = array();


			$start_year = (int)$start_date_arr[2];
            $end_year = (int)$end_date_arr[2];

			for( $j = $start_year; $j <= $end_year; $j++ )
			{
				$start_month = (int)$start_date_arr[1];
				$end_month = (int)$end_date_arr[1];

				if ($start_year != $end_year) {
					if ($j == $start_year) {
						$end_month = 12;
					} else {
						$start_month = 1;
					}
				}       

				for( $i = $start_month; $i <= $end_month; $i++ )
				{
					if( (int)$i < 10 )
						$result = $this->getEvents("0".$i,$j);
					else
						$result = $this->getEvents($i,$j);


					if( count($result) > 0 )
					{
						$return[] = $result;
					}
				}
			}



			if( count($return) > 0 )
			{
				$i = 0;
				
				for( $j = 0 ; $j < count( $return); $j++)
				{
					foreach( $return[$j] as $key => $event )
					{

						foreach ($event as $value) {


							$events[$i]['eventID']			= "".$value['id'];
							$events[$i]['eventDate']		= "".$key;
							$events[$i]['eventName']		= mb_convert_encoding("".$value['title'],"UTF8","ISO_8859-1");
							$events[$i]['eventDescription']	= mb_convert_encoding("".$value['description'], "UTF8","ISO_8859-1");
							$events[$i]['eventLocation']	= mb_convert_encoding("".$value['location'], "UTF8","ISO_8859-1");
							$events[$i]['eventParticipants']= $value['participants'];

							$starttime	= $this->makeTime($value['start']);
							$endtime	= $this->makeTime($value['end']);
							$actualdate = mktime(0,0,0,substr($key,4,2),substr($key, 6 ),substr($key,0,4));
							$rawdate_offset = $actualdate - $this->getTimezoneOffset();
							$nextday = mktime(0,0,0,substr($key,4,2),substr($key, 6 )+1,substr($key,0,4)) - $this->getTimezoneOffset();

							if( $starttime <= $rawdate_offset && $endtime >= $nextday - 60 )
							{
								$events[$i]['eventStartDate']	= substr($key, 6 )."/".substr($key,4,2)."/".substr($key,0,4)." 00:00";
								$events[$i]['eventEndDate']		= substr($key, 6 )."/".substr($key,4,2)."/".substr($key,0,4)." 23:59";
								$events[$i]['eventAllDay']		= "1";
							}
							else
							{
								if( $value['start']['mday'] === $value['end']['mday'] )
								{
									$hour_start	= (((int)$value['start']['hour'] < 10 ) ? "0".$value['start']['hour'] : $value['start']['hour']).":".(((int)$value['start']['min'] < 10 ) ? "0".$value['start']['min'] : $value['start']['min'] );
									$hour_end	= (((int)$value['end']['hour'] < 10 ) ? "0".$value['end']['hour'] : $value['end']['hour']).":".(((int)$value['end']['min'] < 10 ) ? "0".$value['end']['min'] : $value['end']['min'] );
								}
								else
								{
									if( $events[$i-1] && $events[$i-1]['eventID'] == $value['id'])
									{
										$hour_start	= "00:00";
										$hour_end	= (((int)$value['end']['hour'] < 10 ) ? "0".$value['end']['hour'] : $value['end']['hour']).":".(((int)$value['end']['min'] < 10 ) ? "0".$value['end']['min'] : $value['end']['min'] );
									}
									else
									{
										$hour_start	= (((int)$value['start']['hour'] < 10 ) ? "0".$value['start']['hour'] : $value['start']['hour']).":".(((int)$value['start']['min'] < 10 ) ? "0".$value['start']['min'] : $value['start']['min'] );
										$hour_end	= "23:59";
									}
								}

								$events[$i]['eventStartDate']	= substr($key, 6 )."/".substr($key,4,2)."/".substr($key,0,4)." ".$hour_start;
								$events[$i]['eventEndDate']		= substr($key, 6 )."/".substr($key,4,2)."/".substr($key,0,4)." ".$hour_end;
								$events[$i]['eventAllDay']		= "0";
							}

							$events[$i++]['eventExParticipants'] = $value['ex_participants'];
						}

					}

				}

				$this->setResult(array('events' => $events));
			}
			else
			{
				$this->setResult(array('events' => array()));
			}
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}