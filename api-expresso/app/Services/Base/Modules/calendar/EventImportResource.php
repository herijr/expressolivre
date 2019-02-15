<?php

namespace App\Services\Base\Modules\calendar;

require_once dirname( __FILE__ ) . '/../../../../../../api-expresso/bootstrap/app.php';

use App\Models\Calendar\PhpgwCalModel;
use App\Models\Calendar\PhpgwCalUserModel;
use App\Models\Calendar\PhpgwCalInviteModel;
use Carbon\Carbon;
use Sabre\VObject;

class EventImportResource {

	public function get( $request ){

		if( trim($request) !== "" ) {

			$hashEvent = trim(preg_replace("/[^a-z0-9A-Z]/", "", $request));

			$tbInvite = new PhpgwCalInviteModel();
			
			$vCalendar = $tbInvite->where('hash', $hashEvent)
														->whereNull( 'imported_at' )
														->first();
			
			if( count($vCalendar) > 0 ){
				
				$vcal = VObject\Reader::read( base64_decode( $vCalendar['contents'] ) );

				$event = current( $vcal->select('VEVENT') );

				$mailExternalParticipants	= array();

				foreach($event->ATTENDEE as $attendee ){
					if(isset($attendee['CN']))
						$mailExternalParticipants[] = preg_replace("/mailto:/i", "", $attendee);
				}
				
				$date = new \Carbon\Carbon();
				$dateNow = $date->now();

				$tbCal = new PhpgwCalModel();
				
				$description = ( isset($event->ORGANIZER['CN']) ? 'Organizado por : ' . $event->ORGANIZER['CN'] . ' ( ' . preg_replace("/mailto:/i", "",$event->ORGANIZER ) . ' )' : "" );
				$description .= '  -  ' . $event->DESCRIPTION;
				
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
					$dtStart->sub(new \DateInterval('PT3H'));
				} else {
					$dateStart = str_replace('T', '', $dateStart);//remove T
					$d    = date('d', strtotime($dateStart));//get date day
					$m    = date('m', strtotime($dateStart));//get date month
					$y    = date('Y', strtotime($dateStart));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateStart));//user friendly date
					$dtStart = new \DateTime( $eventdate );
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
					$dtEnd->sub(new \DateInterval('PT3H'));
				} else {
					$dateEnd = str_replace('T', '', $dateEnd);//remove T
					$d    = date('d', strtotime($dateEnd));//get date day
					$m    = date('m', strtotime($dateEnd));//get date month
					$y    = date('Y', strtotime($dateEnd));//get date year
					$now = date('Y-m-d G:i:s');//current date and time
					$eventdate = date('Y-m-d G:i:s', strtotime($dateEnd));//user friendly date
					$dtEnd = new \DateTime( $eventdate );
				}

				// Add
				$tbCalNew = $tbCal->create([
					'uid' => '-@127.0.0.1',
					'owner' => $vCalendar['owner'],
					'datetime' => $dtStart->getTimestamp(),
					'mdatetime' => $dateNow->timestamp,
					'edatetime' => $dtEnd->getTimestamp(),
					'priority' => "1",
					'cal_type' => "E",
					'is_public' => "1",
					'title' => mb_convert_encoding( ''.$event->SUMMARY.'' ,"UTF-8", "ISO-8859-1" ),
					'description' => $description,
					'location' => mb_convert_encoding( ''.$event->LOCATION.'' ,"UTF-8", "ISO-8859-1" ),
					'reference' => 0,
					'ex_participants' => implode( " ,", $mailExternalParticipants ),
					'last_status' => "N",
					'last_update' => $dateNow->timestamp
				]);
					
				$tbCalUser = new PhpgwCalUserModel();
				
				// Add
				$tbCalUserNew = $tbCalUser->create([
					'cal_id' => $tbCalNew->cal_id,
					'cal_login' => $vCalendar['owner'],
					'cal_status' => 'A',
					'cal_type' => 'u',
				]);

				// Update cal_invite
				$tbInviteUpdate = $tbInvite->where('hash', $hashEvent)
															->whereNull( 'imported_at' )
															->update(['imported_at' => $date->format( 'Y-m-d H:i:s' ) ]);

				return true;
			}
		}
		return false;
	}
}
