<?php

namespace App\Http\Controllers\ApiOld\Preferences;

use App\Http\Controllers\Controller;
use App\Services\Base\Modules\preferences\ChangeUserPreferencesResource;
use Illuminate\Http\Request;

class ChangeUserPreferencesController extends Controller
{
		private $resource;
		
		public function __construct( ChangeUserPreferencesResource $resource )
		{
			$this->resource = $resource;
		}

		public function index( Request $request )
		{
			$_request = App('get-requests')->getRequest( $request );

			return $this->resource->post( $_request );
		}
}
