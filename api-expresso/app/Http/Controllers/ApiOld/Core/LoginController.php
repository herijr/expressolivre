<?php

namespace App\Http\Controllers\ApiOld\Core;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\core\LoginResource;
use Illuminate\Http\Request;

class LoginController extends Controller
{
		private $resource;
		
    public function __construct( LoginResource $resource )
    {
    	$this->resource = $resource;
    }

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );
			
			return $this->resource->post( $_request );
		}
}
