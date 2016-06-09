<?php

class UserPreferencesResource extends PreferencesAdapter
{

	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/UserPreferences","Retorna as preferкncias do usuбrio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticaзгo do Usuбrio.",false);

		$this->addResourceParam("module","string",false,"Mуdulo da Preferкncia (Default = mail).",false,"mail");
		$this->addResourceParam("preference","string",false,"ID da preferкncia.");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{

			$module = $this->getParam('module');
			$preference = $this->getParam('preference');

			if ($preference == "") { $preference = ""; }
			if (($module == "mail") || ($module == "")) { $appName = "expressoMail"; }

			$prefs_forced = &$GLOBALS['phpgw']->preferences->forced[$appName];
			$prefs_default = &$GLOBALS['phpgw']->preferences->default[$appName];
			$prefs_user = &$GLOBALS['phpgw']->preferences->user[$appName];

			$prefs = array_merge($prefs_default,$prefs_user);

			foreach( $prefs as $k => $pref) {
				$prefs[$k] = mb_convert_encoding($pref, "UTF8","ISO_8859-1");
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
			
			$this->setResult($result);
			
			return $this->getResponse();
		}	
	}
}

?>