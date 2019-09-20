<?php

namespace App\Modules\Preferences;

use App\Errors;
use App\Adapters\PreferencesAdapter;

class ChangeUserPreferencesResource extends PreferencesAdapter
{
	public function post($request)
	{
		$appName 	= $request['module'];
		$preference = $request['preference'];
		$value 		= $request['value'];

		if ($preference == "") { $preference = ""; }
		
		if (($appName == "mail") || ($appName == "")) { $appName = "expressoMail"; }

		$GLOBALS['phpgw']->preferences->user[$appName][$preference] = $value;

		$GLOBALS['phpgw']->preferences->save_repository( true ,"user");

		return true;
	}
}
