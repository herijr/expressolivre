<?php

namespace App\Http\Controllers\ApiOld\Preferences;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\preferences\UserPreferencesResource;
use Illuminate\Http\Request;

class UserPreferencesController extends Controller
{
		private $resource;
		
		public function __construct( UserPreferencesResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
