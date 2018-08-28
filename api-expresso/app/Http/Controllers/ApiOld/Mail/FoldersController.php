<?php

namespace App\Http\Controllers\ApiOld\Mail;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\mail\FoldersResource;
use Illuminate\Http\Request;

class FoldersController extends Controller
{
		private $resource;
		
		public function __construct( FoldersResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
