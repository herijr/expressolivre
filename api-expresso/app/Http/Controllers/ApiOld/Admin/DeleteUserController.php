<?php

namespace App\Http\Controllers\ApiOld\Admin;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\admin\DeleteUserResource;
use Illuminate\Http\Request;

class DeleteUserController extends Controller
{
		private $resource;
		
		public function __construct( DeleteUserResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
