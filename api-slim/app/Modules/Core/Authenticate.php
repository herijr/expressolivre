<?php

namespace App\Modules\Core;

use App\Adapters\ExpressoAdapter;

class Authenticate
{
	private $resource;

	public function __construct()
	{
		$this->resource = new ExpressoAdapter();
	}

	public function isLoggedIn($request)
	{
		return $this->resource->isLoggedIn($request);
	}
}
