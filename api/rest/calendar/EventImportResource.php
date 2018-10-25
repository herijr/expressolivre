<?php

require_once __DIR__.'/../../vendor/autoload.php';

require_once __DIR__.'/../../../phpgwapi/inc/adodb/adodb.inc.php';

require_once __DIR__.'/../../config/DB.php';

use Sabre\VObject;

class EventImportResource extends CatalogAdapter {

	public function get( $request ){
		
		$response = new Response( $request );
		
		$return = json_encode( array( "result" => "false") );
		
		if( isset($_GET['event']) ){

			$hashEvent = $_GET['event'];
			
			$hashEvent = trim(preg_replace("/[^a-z0-9A-Z]/", "", $hashEvent));
						
			$db = NewADOConnection( DBDRIVER );
			$db->connect("host='".DBHOST."' port='".DBPORT."' user='".DBUSER."' password='".DBPASS."' dbname='".DBNAME."'");
			$query = sprintf( "SELECT * FROM phpgw_cal_invite WHERE hash = '%s' AND imported_at is null", $hashEvent ); 
					
			$rsVCalendar = $db->Execute( $query )->getRows();
			
			if( is_array($rsVCalendar) && isset($rsVCalendar[0]['contents']) ){
			
				$vcal = VObject\Reader::read( base64_decode( $rsVCalendar[0]['contents'] ) );

				$event = current( $vcal->select('VEVENT') );

				$mailExternalParticipants	= array();

				foreach($event->ATTENDEE as $attendee ){
					if(isset($attendee['CN']))
						$mailExternalParticipants[] = preg_replace("/mailto:/i", "", $attendee);
				}
				
				$description = ( isset($event->ORGANIZER['CN']) ? 'Organizado por : ' . $event->ORGANIZER['CN'] . ' ( ' . preg_replace("/mailto:/i", "",$event->ORGANIZER ) . ' ) - ' : "" );
				$description .= $event->DESCRIPTION;
				$description = mb_convert_encoding( $description , "ISO-8859-1",
				 													mb_detect_encoding( $description, "auto" ) );
				
				$dtStart = null;
				$dtEnd = null;
				
				$dateStart = $event->DTSTART.'';//get date from ical
				if( preg_match('/Z/',$dateStart) ){
					$dateStart = str_replace('T', '', $dateStart);//remove T
					$dateStart = str_replace('Z', '', $dateStart);//remove Z
					$d    = date('d', strtotime($dateStart));//get date day
					$m    = date('m', strtotime($dateStart));//get date month
					$y    = date('Y', strtotime($dateStart));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateStart));//user friendly date
					$dtStart = new DateTime( $eventdate );
					$dtStart->sub(new DateInterval('PT3H'));
				} else {
					$dateStart = str_replace('T', '', $dateStart);//remove T
					$d    = date('d', strtotime($dateStart));//get date day
					$m    = date('m', strtotime($dateStart));//get date month
					$y    = date('Y', strtotime($dateStart));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateStart));//user friendly date
					$dtStart = new DateTime( $eventdate );
				}

				$dateEnd = $event->DTEND.'';//get date from ical
				if( preg_match('/Z/',$dateEnd) ){
					$dateEnd = str_replace('T', '', $dateEnd);//remove T
					$dateEnd = str_replace('Z', '', $dateEnd);//remove Z
					$d    = date('d', strtotime($dateEnd));//get date day
					$m    = date('m', strtotime($dateEnd));//get date month
					$y    = date('Y', strtotime($dateEnd));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateEnd));//user friendly date
					$dtEnd = new DateTime( $eventdate );
					$dtEnd->sub(new DateInterval('PT3H'));
				} else {
					$dateEnd = str_replace('T', '', $dateEnd);//remove T
					$d    = date('d', strtotime($dateEnd));//get date day
					$m    = date('m', strtotime($dateEnd));//get date month
					$y    = date('Y', strtotime($dateEnd));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateEnd));//user friendly date
					$dtEnd = new DateTime( $eventdate );
				}

				$dateNow = date( strtotime('now') );
				
				$addEvent = array();
				$addEvent['uid'] =  '-@127.0.0.1';
				$addEvent['owner'] =  $rsVCalendar[0]['owner'];
				$addEvent['datetime'] =  $dtStart->getTimestamp();
				$addEvent['mdatetime'] =  $dateNow;
				$addEvent['edatetime'] =  $dtEnd->getTimestamp();
				$addEvent['priority'] =  "1";
				$addEvent['cal_type'] =  "E";
				$addEvent['is_public'] =  "1";
				$addEvent['title'] =  ''.$event->SUMMARY.'';
				$addEvent['description'] =  $description;
				$addEvent['location'] =  ''.$event->LOCATION.'';
				$addEvent['reference'] =  0;
				$addEvent['ex_participants'] =  implode( " ,", $mailExternalParticipants );
				$addEvent['last_status'] =  "N";
				$addEvent['last_update'] =  $dateNow;
				
				$addEvent['title'] = mb_convert_encoding( $addEvent['title'] , "ISO-8859-1",
				 													mb_detect_encoding( $addEvent['title'], "auto" ) );

				$addEvent['title'] = substr( $addEvent['title'], 0 , 80 );
				
				$insert = $db->AutoExecute( "phpgw_cal", $addEvent, 'INSERT' );
			
				// Add
				$addCalUser = array();
				$addCalUser['cal_id'] = $db->GetOne("SELECT cal_id FROM phpgw_cal WHERE oid=".$db->insert_Id());
				$addCalUser['cal_login'] = $rsVCalendar[0]['owner'];
				$addCalUser['cal_status'] = 'A';
				$addCalUser['cal_type'] = 'u';
				
				$insertUser = $db->AutoExecute("phpgw_cal_user", $addCalUser, 'INSERT' );
				
				if($insert && $insertUser){
					
					$updateCalIinvite = $db->AutoExecute( 
							'phpgw_cal_invite', 
							array('imported_at' => date('Y-m-d H:i:s', $dateNow) ),
							'UPDATE',
							'hash = \''. $_GET['event'] .'\'' );
					
					if( $updateCalIinvite ){
						$return = json_encode( array( "result" => "true") );
					}
				}
			}
		}
		
		$response->body = $return;
		
		return $response;
	}
}
