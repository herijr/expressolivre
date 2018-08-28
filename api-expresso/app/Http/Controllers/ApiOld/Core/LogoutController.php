<?php

namespace App\Http\Controllers\ApiOld\Core;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\core\LogoutResource;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
		private $resource;
		
    public function __construct( LogoutResource $resource )
    {
    	$this->resource = $resource;
    }

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
