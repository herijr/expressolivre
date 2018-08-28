<?php

namespace App\Http\Controllers\ApiOld\Admin;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\admin\GetUsersResource;
use Illuminate\Http\Request;

class GetUsersController extends Controller
{
		private $resource;
		
		public function __construct( GetUsersResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
