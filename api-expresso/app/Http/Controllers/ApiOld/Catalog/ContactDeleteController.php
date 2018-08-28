<?php

namespace App\Http\Controllers\ApiOld\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\catalog\ContactDeleteResource;
use Illuminate\Http\Request;

class ContactDeleteController extends Controller
{
		private $resource;
		
		public function __construct( ContactDeleteResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
