<?php

namespace App\Services\Base\Modules\admin;

use App\Services\Base\Adapters\AdminAdapter;
use App\Services\Base\Commons\Errors;
use App\Services\Base\Modules\admin;

class CreateUserResource extends AdminAdapter
{
	public function setDocumentation() {
		$this->setResource("Admin","Admin/CreateUser","Cria um usuário no Expresso, necessário ter a permissão no Módulo ExpressoAdmin.",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("accountLogin","string",true,"Login do usuário");
		$this->addResourceParam("accountEmail","string",true,"Email do usuário");
		$this->addResourceParam("accountName","string",true,"Nome do usuário");
		$this->addResourceParam("accountProfile","string",true,"Perfil do usuário( Verifique se o perfil está disponível no servidor)");
		$this->addResourceParam("accountPassword","string",true,"Senha do usuário");
		$this->addResourceParam("accountRePassword","string",true,"Confirmação da senha do usuário");
		$this->addResourceParam("accountPhone","string",true,"Telefone do usuário. Máscara padrão (00)0000-0000");
		$this->addResourceParam("accountCpf","string",true,"CPF do usuário. Máscara padrão 000.000.000-00");
		$this->addResourceParam("accountRg","string",true,"RG do usuário");
		$this->addResourceParam("accountRgUf","string",true,"UF");
		$this->addResourceParam("accountBirthDate","string",true,"Data de aniversário do usuário. Máscara padrão DD/MM/AAAA");
		$this->addResourceParam("accountSex","string",true,"Sexo");
		$this->addResourceParam("accountCity","string",true,"Cidade");
		$this->addResourceParam("accountSt","string",true,"Estado");
		$this->addResourceParam("accountDescription","string",true,"Descrição do usuário");
		$this->addResourceParam("accountJpegPhoto","file", false, "Foto do usuario");
	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		$this->setParams($request);

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

			if( isset($_FILES) && count($_FILES) > 0 )
			{
			  $accountPhoto = array(
			    'name' => $_FILES['accountPhoto']['name'],
			    'type' => $_FILES['accountPhoto']['type'],
			    'tmp_name' => $_FILES['accountPhoto']['tmp_name'],
			    'size' => $_FILES['accountPhoto']['size'],
			    'error' => $_FILES['accountPhoto']['error'],
			    'source' => base64_encode(file_get_contents( $_FILES['accountPhoto']['tmp_name'], $_FILES['accountPhoto']['size']))
			  );
	
			  unset( $_FILES['accountPhoto'] );
			}

			// Field Validation
			if( trim($loginUser) == "" && isset($loginUser) )	
				return Errors::runException( "ADMIN_LOGIN_EMPTY" );

			if( trim($nameUser) == "" && isset($nameUser) )
				return Errors::runException( "ADMIN_NAME_EMPTY" );

			if( trim($profileUser) == "" && isset($profileUser) )
				return Errors::runException( "ADMIN_PROFILE_USER_EMPTY" );
			
			if( trim($emailUser) == "" && isset($emailUser) )
				return Errors::runException( "ADMIN_EMAIL_EMPTY" );

			if( trim($passwordUser) == "" && isset($passwordUser) )
				return Errors::runException( "ADMIN_PASSWORD_EMPTY" );

			if( trim($rePasswordUser) == "" && isset($rePasswordUser) )
				return Errors::runException( "ADMIN_RE_PASSWORD_EMPTY" );
		
			//If rgUser and rgUF
			if((trim($rgUser) != "" && trim($rgUF) == "" ) || ( trim($rgUser) == "" && trim($rgUF) != "" ))
			{
				return Errors::runException("ADMIN_RG_UF_EMPTY");
			}

			// password and repassword are different ? 				
			if( trim($passwordUser) != trim($rePasswordUser) )
			{
				return Errors::runException( "ADMIN_PASSWORD_REPASSWORD" );
			}
			
			// validate password, 8 characteres minimum and 2 numbers
			$msg = $common->validatePassword($passwordUser);

			if( $msg['status'] == false )
			{
				return Errors::runException( "ADMIN_MINIMUM_CHARACTERS", $msg['msg']);
			}

			// CPF is invalid
			if( trim($cpfUser) != "" && !$common->validateCPF($cpfUser) )
			{
				return Errors::runException( "ADMIN_CPF_INVALID" );
			}

			// Characters not permited login
			$msg = $common->validateCharacters( $loginUser, "accountLogin" );

			if( $msg['status'] == false )
			{
				return Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountLogin" );
			}
			
			//Characters not permited name
			$msg = $common->validateCharacters($nameUser);

			if( $msg['status'] == false )
			{
				return Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountName" );
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
			$fields['accountPhoto'] = $accountPhoto;

			// Validate Fields
			$msg = $this->validateFields( array("attributes" => serialize($fields)) );

			if( $msg['status'] == false )
			{
				return Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
			}

			// Create User
			unset($fields['cpf']);

			$msg = $this->createUser( $fields );

			if( $msg['status'] == false ) {
				return Errors::runException( "ADMIN_CREATE_USER", $msg['msg'] );
			}

			$this->setResult(true);
		} else {
			return Errors::runException( "ACCESS_NOT_PERMITTED" );
		}

		return $this->getResponse();
	}
}
