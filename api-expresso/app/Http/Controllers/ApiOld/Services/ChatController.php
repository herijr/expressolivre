<?php

namespace App\Http\Controllers\ApiOld\Services;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\services\ChatResource;
use Illuminate\Http\Request;

class ChatController extends Controller
{
		private $resource;
		
		public function __construct( ChatResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
