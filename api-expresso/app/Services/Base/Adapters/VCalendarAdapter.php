<?php

include_once dirname(__FILE__).'/../library/tonic/lib/tonic.php';
include_once dirname(__FILE__).'/../library/utils/Errors.php';

class VCalendarAdapter extends Resource { 

	public function get( $request ) {
		$response = new Response($request);
		$response->code = Response::OK;
		$response->addHeader('content-type', 'application/json');
		$response->body = json_encode("Metodo GET nao permitido para este recurso.");		
		return true;//$response;
	}

}
