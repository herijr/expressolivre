<?php

namespace App\Http\Controllers\ApiOld\Core;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\core\UserAppsResource;
use Illuminate\Http\Request;

class UserAppsController extends Controller
{
		private $resource;
		
    public function __construct( UserAppsResource $resource )
    {
    	$this->resource = $resource;
    }

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );
			
			return $this->resource->post( $_request );
		}
}
