<?php

require_once("CommonFunctions.php");

class UpdateUserResource extends AdminAdapter
{
	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
 			// Permission
			$permission = array();
			$permission['action'] = 'edit_users';
			$permission['apps'] = $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();

			if( $this->validatePermission($permission) ) 	
			{	
				//Class CommonFunctions
				$common	= new CommonFunctions();

				$uidNumber		= trim($this->getParam('accountUidNumber'));
				$loginUser 		= trim($this->getParam('accountLogin'));
				$emailUser		= trim($this->getParam('accountEmail'));
				$nameUser		= $common->convertChar(trim($this->getParam('accountName')));
				$passwordUser	= trim($this->getParam('accountPassword'));
				$rePasswordUser	= trim($this->getParam('accountRePassword'));
				$phoneUser		= trim($this->getParam('accountPhone'));
				$cpfUser		= trim($this->getParam('accountCpf'));
				$rgUser			= trim($this->getParam('accountRg'));
				$rgUF			= trim($this->getParam('accountRgUf'));
				$description 	= $common->convertChar(trim($this->getParam('accountDescription')));
				$mailQuota		= trim($this->getParam('accountMailQuota'));
				$birthDate		= $common->mascaraBirthDate($this->getParam('accountBirthDate'));
				$st 			= $this->getParam('accountSt');
				$city			= $this->getParam('accountCity');
				$sex			= $this->getParam('accountSex');

				// Field Validation
				if( trim($uidNumber) == "" && isset($uidNumber) )
					Errors::runException( "ADMIN_UIDNUMBER_EMPTY" );
				
				if( trim($loginUser) == "" && isset($loginUser) )	
					Errors::runException( "ADMIN_LOGIN_EMPTY" );

				// If rgUser and rgUF
				if( (trim($rgUser) != "" && trim($rgUF) == "" ) || ( trim($rgUser) == "" && trim($rgUF) != "" ) )
				{
					Errors::runException("ADMIN_RG_UF_EMPTY");
				}

				// If not empty
				if( trim($passwordUser) != "" && trim($rePasswordUser) != "" )
				{	
					if( isset($passwordUser) && isset($rePasswordUser) )
					{
						// password and repassword are different ? 				
						if( trim($passwordUser) != trim($rePasswordUser) )
						{
							Errors::runException( "ADMIN_PASSWORD_REPASSWORD" );
						}
					
						// validate password, 8 characteres minimum and 2 numbers
						$msg = $common->validatePassword($passwordUser);

						if( $msg['status'] == false )
						{
							Errors::runException( "ADMIN_MINIMUM_CHARACTERS", $msg['msg']);
						}
					}
				}

				// CPF is invalid
				if( trim($cpfUser) != "" && !$common->validateCPF($cpfUser) )
				{
					Errors::runException( "ADMIN_CPF_INVALID" );
				}

				// Characters not permited login
				$msg = $common->validateCharacters($loginUser, "accountLogin");

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountLogin" );
				}

				//Characters not permited name
				$msg = $common->validateCharacters($nameUser);

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountName" );
				}

				//Characters not permited mailQuota
				$msg = $common->validateCharacters($mailQuota, "accountMailQuota");

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountMailQuota" );
				}

				// Params - Validade / Update Fields
				$fields = array();
				$fields['type'] 		= "edit_user";
				$fields['uid']			= $loginUser;
				$fields['uidnumber'] 	= $uidNumber;
				$fields['mail']			= $emailUser;
				$fields['cpf']			= $common->mascaraCPF($cpfUser);

				// Validate Fields
				$msg = $this->validateFields( array("attributes" => serialize($fields)) );

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
				}

				//Name User
				$nameUser = explode(" ", $nameUser);
				
				$fields['givenname'] = $nameUser[0]; 
				
				if( count($nameUser) > 1 )
				{
					unset( $nameUser[0] );
				}

				if( trim($passwordUser) != "" )
				{
					$fields['password1'] = $passwordUser;
					$fields['password2'] = $rePasswordUser;
				}

				if( trim($nameUser) != "" )
					$fields['sn'] = implode(" ", $nameUser );	
				
				if( trim($phoneUser) != "" )
					$fields['telephonenumber'] = $common->mascaraPhone($phoneUser);
				
				if( trim($cpfUser) != "" )
					$fields['corporative_information_cpf'] = $common->mascaraCPF($cpfUser);
				
				if( trim($rgUser) != "" )
					$fields['corporative_information_rg'] = $rgUser;
				
				if( trim($rgUF) != "" )
					$fields['corporative_information_rguf'] = $rgUF;
				
				if( trim($description) != "" )
					$fields['corporative_information_description'] = $description;

				if( trim($mailQuota) != "" )
					$fields['mailquota'] = $mailQuota;

				if( trim($birthDate) != "" )
					$fields['corporative_information_datanascimento'] = $birthDate;

				if( trim($st) != "" )
					$fields['corporative_information_st'] 	= $st;
	
				if( trim($city) != "" )	
					$fields['corporative_information_city'] = $city;

				if( trim($sex) != "" )			
					$fields['corporative_information_sexo'] = $sex;

				// Update Fields
				unset($fields['cpf']);

				$msg = $this->updateUser($fields);

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_UPDATE_USER", $msg['msg'] );
				}

				$this->setResult(true);
			}
			else
			{
				Errors::runException( "ACCESS_NOT_PERMITTED" );
			}			
		}

		return $this->getResponse();	
 	}	
}

?>
