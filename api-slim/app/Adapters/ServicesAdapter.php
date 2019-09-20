<?php

namespace App\Adapters;

use App\Adapters\ExpressoAdapter;

class ServicesAdapter extends ExpressoAdapter
{
	protected function authChat()
	{
		$im = CreateObject('phpgwapi.messenger');
		if ($im->checkAuth()) {
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
