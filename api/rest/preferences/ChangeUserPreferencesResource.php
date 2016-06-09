<?php

class ChangeUserPreferencesResource extends PreferencesAdapter
{
	
	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/ChangeUserPreferences","Altera as preferкncias do usuбrio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticaзгo do Usuбrio.",false);

		$this->addResourceParam("module","string",true,"Mуdulo da Preferкncia.");
		$this->addResourceParam("preference","string",true,"ID da preferкncia.");
		$this->addResourceParam("value","string",true,"Novo valor da preferкncia.");


	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{

			$appName 	= $this->getParam('module');
			$preference = $this->getParam('preference');
			$value 		= $this->getParam('value');

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