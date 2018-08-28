<?php

namespace App\Http\Controllers\ApiOld\Mail;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\mail\MoveMessagesResource;
use Illuminate\Http\Request;

class MoveMessagesController extends Controller
{
		private $resource;
		
		public function __construct( MoveMessagesResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
