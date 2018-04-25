<?php

class UserPreferencesResource extends PreferencesAdapter
{

	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/UserPreferences","Retorna as preferências do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("module","string",false,"Módulo da Preferência (Default = mail).",false,"mail");
		$this->addResourceParam("preference","string",false,"ID da preferência.");

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
			if (($module == "mail") || ( $module == "")) { $appName = "expressoMail"; }
			else { $appName = $module; }

			$prefs_forced = (isset(&$GLOBALS['phpgw']->preferences->forced[$appName]) ? &$GLOBALS['phpgw']->preferences->forced[$appName] : array());
			$prefs_default = (isset(&$GLOBALS['phpgw']->preferences->default[$appName]) ? &$GLOBALS['phpgw']->preferences->default[$appName] : array());
			$prefs_user = (isset(&$GLOBALS['phpgw']->preferences->user[$appName]) ? &$GLOBALS['phpgw']->preferences->user[$appName] : array());

			$prefs = array_merge( $prefs_default, $prefs_user);

			foreach( $prefs as $k => $pref) {
				$prefs[$k] = is_string( $pref )? mb_convert_encoding($pref, "UTF8","ISO_8859-1") : $pref;
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
