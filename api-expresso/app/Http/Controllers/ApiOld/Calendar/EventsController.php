<?php

namespace App\Http\Controllers\ApiOld\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\calendar\EventsResource;
use Illuminate\Http\Request;

class EventsController extends Controller
{
		private $resource;
		
		public function __construct( EventsResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
