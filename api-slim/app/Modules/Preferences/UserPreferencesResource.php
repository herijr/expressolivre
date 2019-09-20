<?php

namespace App\Modules\Preferences;

use App\Errors;
use App\Adapters\PreferencesAdapter;

class UserPreferencesResource extends PreferencesAdapter
{
	public function post($request)
	{
		$appName = $request['module'];

		$preference = $request['preference'];

		if( trim($appName) === "" ) { $appName = "mail"; }

		$apps = $this->readUserApp();
		
		$module = $appName;
		
		if( array_key_exists( $appName, $apps ) )
		{
			$appName = $apps[$appName];
			$prefs_forced = (isset($GLOBALS['phpgw']->preferences->forced[$appName]) ? $GLOBALS['phpgw']->preferences->forced[$appName] : array());
			$prefs_default = (isset($GLOBALS['phpgw']->preferences->default[$appName]) ? $GLOBALS['phpgw']->preferences->default[$appName] : array());
			$prefs_user = (isset($GLOBALS['phpgw']->preferences->user[$appName]) ? $GLOBALS['phpgw']->preferences->user[$appName] : array());

			$prefs = array_merge( $prefs_default, $prefs_user);

			if( strtolower($appName) === "expressomail" )
			{
				$defaultSignature = $this->getDefaultSignature();

				if( $defaultSignature ){
					$defaultSignature = preg_replace('/&#65279;/','', $defaultSignature );
					$prefs['auto_signature'] = 1;
					$prefs['signature']      = $defaultSignature;
					$prefs['type_signature'] = 'html';
				} else  {
					$prefs['auto_signature'] = 0;
				}
			}

			foreach( $prefs as $k => $pref) {
				$prefs[$k] = is_string( $pref )? mb_convert_encoding($pref, "UTF-8","ISO-8859-1") : $pref;
			}

			if ($preference == "") {
				$result = array( $module => $prefs );
			} else {
				if (isset($prefs[$preference])) {
					$result = array( $module => array( "" . $preference => $prefs[$preference]) );
				} else {
					$result = array( $module => array( "" . $preference => "") );
				}
				
			}
		} else {
			$result = array( $module => array( "" . $preference => "") );
		}
		
		return $result;
	}
}
