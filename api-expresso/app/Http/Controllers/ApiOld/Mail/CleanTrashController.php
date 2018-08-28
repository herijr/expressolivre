<?php

namespace App\Http\Controllers\ApiOld\Mail;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\mail\CleanTrashResource;
use Illuminate\Http\Request;

class CleanTrashController extends Controller
{
		private $resource;
		
		public function __construct( CleanTrashResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
