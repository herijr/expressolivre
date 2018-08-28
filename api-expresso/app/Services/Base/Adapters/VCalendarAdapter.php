<?php

include_once dirname(__FILE__).'/../library/tonic/lib/tonic.php';
include_once dirname(__FILE__).'/../library/utils/Errors.php';

class VCalendarAdapter extends Resource { 

	public function get( $request ) {
		$response = new Response($request);
		$response->code = Response::OK;
		error_log( print_r("1", true ), 3, "/var/www/expresso/debug.log" );
		$response->addHeader('content-type', 'application/json');
		$response->body = json_encode("Metodo GET nao permitido para este recurso.");		
		error_log( print_r( is_object( $response ), true ), 3, "/var/www/expresso/debug.log" );
		return true;//$response;
	}

}
