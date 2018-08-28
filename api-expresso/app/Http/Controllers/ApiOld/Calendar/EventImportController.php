<?php

namespace App\Http\Controllers\ApiOld\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\calendar\EventImportResource;
use Illuminate\Http\Request;

class EventImportController extends Controller
{
		public function index(Request $request)
		{
			$result = array( "result" => false );
			
			if( $request->has('event') ){

				$resource = new EventImportResource();
		
				$result = array( "result" => $resource->get( $request->input('event') ) ) ;
			}
			
			return json_encode( $result );
		}
}
