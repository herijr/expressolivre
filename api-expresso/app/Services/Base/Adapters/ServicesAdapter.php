<?php

namespace App\Services\Base\adapters;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class ServicesAdapter extends ExpressoAdapter
{
	protected function authChat()
	{
		$im = CreateObject('phpgwapi.messenger');
		if ( $im->checkAuth() ) {
			return array(
				'B' => $im->url,
				'C' => $im->domain,
				'A' => $im->getAuth()->client,
				'D' => $im->getAuth()->user,
				'E' => $im->getAuth()->auth,
			);
		}
		return false;
	}
}
