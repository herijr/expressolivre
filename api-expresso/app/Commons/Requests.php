<?php

namespace App\Commons;

use Illuminate\Http\Request;

class Requests
{
  public function getRequest( Request $request )
  {
		// Form-urlencoded
		if( preg_match( "/(application\/x-www-form-urlencoded)/i", $request->headers->get('Content-type') ) ) {
			return json_decode( $request['params'] , true );
		}

		// Json
		if( preg_match( "/(application\/json)/i", $request->headers->get('Content-Type') ) ) {
			return $request->all();
		}
	
		if( preg_match("/(multipart\/form-data)/i", $request->headers->get('Content-type') ) ){
			return json_decode( $request['params'] , true );
		}

    return false;
  }
}
