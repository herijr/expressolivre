<?php

class ChangeUserPreferencesResource extends PreferencesAdapter
{
	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{

			$appName 	= mb_convert_encoding($this->getParam('module'), "UTF8", "ISO_8859-1");
			$preference = mb_convert_encoding($this->getParam('preference'), "UTF8", "ISO_8859-1");
			$value 		= mb_convert_encoding($this->getParam('value'), "UTF8", "ISO_8859-1"); ;


			if ($preference == "") { $preference = ""; }
			if (($appName == "mail") || ($appName == "")) { $appName = "expressoMail"; }

			$GLOBALS['phpgw']->preferences->user[$appName][$preference] = $value;

			$GLOBALS['phpgw']->preferences->save_repository(True,"user");

			$this->setResult(true);
			
			return $this->getResponse();
		}	
	}
}

?>