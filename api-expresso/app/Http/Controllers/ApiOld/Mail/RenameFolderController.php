<?php

namespace App\Http\Controllers\ApiOld\Mail;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\mail\RenameFolderResource;
use Illuminate\Http\Request;

class RenameFolderController extends Controller
{
		private $resource;
		
		public function __construct( RenameFolderResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
