<?php

require_once("CommonFunctions.php");

class CreateUserResource extends AdminAdapter
{
	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{
			// Permission
			$permission = array();
			$permission['action'] = 'add_users';
			$permission['apps'] = $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();

			if( $this->validatePermission($permission) ) 	
			{	
				//Class CommonFunctions
				$common	= new CommonFunctions();

				$loginUser 		= trim($this->getParam('accountLogin'));
				$emailUser		= trim($this->getParam('accountEmail'));
				$nameUser		= $common->convertChar(trim($this->getParam('accountName')));
				$profileUser	= trim($this->getParam('accountProfile'));
				$passwordUser	= trim($this->getParam('accountPassword'));
				$rePasswordUser	= trim($this->getParam('accountRePassword'));
				$phoneUser		= trim($this->getParam('accountPhone'));
				$cpfUser		= trim($this->getParam('accountCpf'));
				$rgUser			= trim($this->getParam('accountRg'));
				$rgUF			= trim($this->getParam('accountRgUf'));
				$birthDate		= $common->mascaraBirthDate($this->getParam('accountBirthDate'));
				$st 			= $this->getParam('accountSt');
				$city			= $this->getParam('accountCity');
				$sex			= $this->getParam('accountSex');
				$description 	= $common->convertChar(trim($this->getParam('accountDescription')));

				// Field Validation
				if( trim($loginUser) == "" && isset($loginUser) )	
					Errors::runException( "ADMIN_LOGIN_EMPTY" );

				if( trim($nameUser) == "" && isset($nameUser) )
					Errors::runException( "ADMIN_NAME_EMPTY" );

				if( trim($profileUser) == "" && isset($profileUser) )
					Errors::runException( "ADMIN_PROFILE_USER_EMPTY" );
				
				if( trim($emailUser) == "" && isset($emailUser) )
					Errors::runException( "ADMIN_EMAIL_EMPTY" );

				if( trim($passwordUser) == "" && isset($passwordUser) )
					Errors::runException( "ADMIN_PASSWORD_EMPTY" );

				if( trim($rePasswordUser) == "" && isset($rePasswordUser) )
					Errors::runException( "ADMIN_RE_PASSWORD_EMPTY" );
			
				//If rgUser and rgUF
				if((trim($rgUser) != "" && trim($rgUF) == "" ) || ( trim($rgUser) == "" && trim($rgUF) != "" ))
				{
					Errors::runException("ADMIN_RG_UF_EMPTY");
				}

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

				// CPF is invalid
				if( trim($cpfUser) != "" && !$common->validateCPF($cpfUser) )
				{
					Errors::runException( "ADMIN_CPF_INVALID" );
				}

				// Characters not permited login
				$msg = $common->validateCharacters( $loginUser, "accountLogin" );

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

				// Params
				$fields = array();
				$fields['profileUser'] 	= $profileUser;
				$fields['type'] 		= "create_user";
				$fields['uid']			= $loginUser;
				$fields['mail']			= $emailUser;

				//Name User
				$nameUser = explode(" ", $nameUser);
				$fields['givenname'] = $nameUser[0]; 
				if( count($nameUser) > 1 )
				{
					unset( $nameUser[0] );
				}
				$fields['sn'] = implode(" ", $nameUser );	
				$fields['password1'] = $passwordUser;
				$fields['password2'] = $rePasswordUser;
				$fields['telephonenumber'] = $common->mascaraPhone($phoneUser);
				$fields['cpf'] = $common->mascaraCPF($cpfUser);
				$fields['corporative_information_cpf']	= $common->mascaraCPF($cpfUser);
				$fields['corporative_information_rg'] 	= $rgUser;
				$fields['corporative_information_rguf']	= $rgUF;
				$fields['corporative_information_description'] = $description;
				$fields['corporative_information_datanascimento'] = $birthDate;
				$fields['corporative_information_st'] 	= $st;
				$fields['corporative_information_city'] = $city;
				$fields['corporative_information_sexo'] = $sex;

				// Validate Fields
				$msg = $this->validateFields( array("attributes" => serialize($fields)) );

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
				}

				// Create User
				unset($fields['cpf']);

				$msg = $this->createUser( $fields );

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_CREATE_USER", $msg['msg'] );
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
