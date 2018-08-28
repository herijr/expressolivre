<?php

namespace App\Services\Base\Modules\preferences;

use App\Services\Base\Adapters\PreferencesAdapter;
use App\Services\Base\Commons\Errors;

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
 		$this->setParams( $request );

		$appName = $this->getParam('module');
		$preference = $this->getParam('preference');

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
		} else {
			$result = array( $module => array( "" . $preference => "") );
		}
		
		$this->setResult($result);
		
		return $this->getResponse();
	}
}
