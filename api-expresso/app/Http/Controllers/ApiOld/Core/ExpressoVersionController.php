<?php

namespace App\Http\Controllers\ApiOld\Core;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\core\ExpressoVersionResource;
use Illuminate\Http\Request;

class ExpressoVersionController extends Controller
{
		private $resource;
		
    public function __construct( ExpressoVersionResource $resource )
    {
    	$this->resource = $resource;
    }

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );
			
			return $this->resource->post( $_request );
		}
}
