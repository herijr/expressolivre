<?php

namespace App\Services\Base\Modules\preferences;

use App\Services\Base\Adapters\PreferencesAdapter;
use App\Services\Base\Commons\Errors;

class ChangeUserPreferencesResource extends PreferencesAdapter
{
	public function setDocumentation() {
		$this->setResource("Preferences","Preferences/ChangeUserPreferences","Altera as preferências do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("module","string",true,"Módulo da Preferência.");
		$this->addResourceParam("preference","string",true,"ID da preferência.");
		$this->addResourceParam("value","string",true,"Novo valor da preferência.");
	}

	public function post($request)
	{
		$this->setParams( $request );

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
