<?php

namespace App\Modules\Mail;

use App\Adapters\MailAdapter;

class SpamMessageResource extends MailAdapter {	

	public function post($request)
	{
 		$result = "false";

		$folderID 	= trim($request['folderID']);
		$msgID		= trim($request['msgID']);
		$spam		= trim($request['spam']);

		if( ( $folderID !== "" && $msgID !== "" ) && $spam !== "" )
		{	
			if( $spam === "1" || $spam === "2" )
			{
				$result = $this->spamMessage( $folderID, $msgID, $spam );
			}
		}	

		return $result;
	}
}
