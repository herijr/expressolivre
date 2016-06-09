<?php

class ChangePasswordResource extends PreferencesAdapter
{

	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/ChangePassword","Altera a senha do usuário.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("currentPassword","string",true,"Senha Atual.");
		$this->addResourceParam("newPassword_1","string",true,"Nova Senha.");
		$this->addResourceParam("newPassword_2","string",true,"Repetir a nova Senha.");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{
			$currentPassword	= urldecode(trim($this->getParam('currentPassword')));
			$newPassword_1		= urldecode(trim($this->getParam('newPassword_1')));
			$newPassword_2		= urldecode(trim($this->getParam('newPassword_2')));

			// If empty current password
			if(  empty($currentPassword) )
			{
				Errors::runException( "EMPTY_CURRENT_PASSWORD" );
			}

			// If empty newPassword1/2
			if( empty($newPassword_1) && empty($newPassword_2) )
			{
				Errors::runException( "EMPTY_NEW_PASSWORD" );
			}

			// Equal $newPassword_1 and $newPassword_2
			if( !( $newPassword_1 === $newPassword_2 ) )
			{
				Errors::runException( "NEW_PASSWORDS_DIFFERENT" );
			}	

			if( $this->getAclPassword() === "true" )
			{
				if( $this->isPassword($currentPassword) )
				{	
					if( strlen($newPassword_1) >= 8 )
					{
						$changePassword	= false;
						$onlyAlfa 		= trim(preg_replace("/[^a-zA-Z0-9]/", "", $newPassword_1));
						$onlyNumbers 	= trim(preg_replace("/[^0-9]/", "", $newPassword_1));

						if( ( strlen($newPassword_1) - strlen($onlyAlfa) ) > 0 )
						{
							$changePassword = true;
						}
						else
						{
							if( strlen($onlyNumbers) >= 2 )
							{
								$changePassword = true;
							}	
						}

						if( $changePassword )
						{ 
							$this->setResult( $this->setPassword( $newPassword_1 , $currentPassword) );
						}
						else
						{
							Errors::runException("NEEDS_2_NUMBERS_OR_SPECIAL_CHARACTERS");
						}
					}
					else
					{
						Errors::runException( "NEEDS_8_OR_MORE_LETTERS" );
					}
				}
				else
				{
					Errors::runException( "CURRENT_PASSWORD_DOES_NOT_MATCH");
				}
			}
			else
			{
				Errors::runException("WITHOUT_PERMISSION");
			}

			return $this->getResponse();
		}	
	}
}

?>