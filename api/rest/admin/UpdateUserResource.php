<?php

require_once("CommonFunctions.php");

class UpdateUserResource extends AdminAdapter
{

	public function setDocumentation() {

		$this->setResource("Admin","Admin/UpdateUser","Atualiza um usuário no Expresso, necessário ter a permissão no Módulo ExpressoAdmin",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		
		$this->addResourceParam("accountUidNumber","string",true,"UIDNumber do usuário. Se o campo Login for definido, o UIDNumber será opcional");
		$this->addResourceParam("accountLogin","string",true,"Login do usuário. Se o campo UIDNumber for definido, o Login não será usado");
		$this->addResourceParam("accountEmail","string",false,"Email do usuário");
		$this->addResourceParam("accountName","string",false,"Nome do usuário");
		$this->addResourceParam("accountPassword","string",false,"Senha do usuário");
		$this->addResourceParam("accountRePassword","string",false,"Confirmação do usuário");
		$this->addResourceParam("accountPhone","string",false,"Telefone do usuário. Máscara padrão (00)0000-0000");
		$this->addResourceParam("accountCpf","string",false,"CPF do usuário. Máscara padrão 000.000.000-00");
		$this->addResourceParam("accountRg","string",false,"RG do usuário");
		$this->addResourceParam("accountRgUf","string",false,"UF");
		$this->addResourceParam("accountBirthDate","string",false,"Data de aniversário do usuário. Máscara padrão DD/MM/AAAA");
		$this->addResourceParam("accountSex","string",false,"Sexo");
		$this->addResourceParam("accountCity","string",false,"Cidade");
		$this->addResourceParam("accountSt","string",false,"Estado");
		$this->addResourceParam("accountDescription","string",false,"Descrição do usuário");
		$this->addResourceParam("accountMailQuota","string",false,"Cota de e-mail em MB");
		$this->addResourceParam("accountJpegPhoto","file", false, "Foto do usuario");
		$this->addResourceParam("accountDeletePhoto","string", false, "Deletar foto ( 0 - Nao , 1 - Sim )");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		if ( $this->isLoggedIn() )
		{
			// Permission
			$permission = array();
			$permission['action'] = 'edit_users';
			$permission['apps'] = $this->getUserApps();
			
			//Load Conf Admin
			$this->loadConfAdmin();
			
			if ( $this->validatePermission($permission) )
			{
				//Class CommonFunctions
				$common         = new CommonFunctions();
				$user_functions = CreateObject('expressoAdmin1_2.user');
				$ldap_functions = CreateObject('expressoAdmin1_2.ldap_functions');
				
				$uidNumber		= (int) trim($this->getParam('accountUidNumber'));
				$loginUser		= (string) trim($this->getParam('accountLogin'));
				$emailUser		= trim($this->getParam('accountEmail'));
				$nameUser		= $common->convertChar(trim($this->getParam('accountName')));
				$passwordUser	= trim($this->getParam('accountPassword'));
				$rePasswordUser	= trim($this->getParam('accountRePassword'));
				$phoneUser		= trim($this->getParam('accountPhone'));
				$cpfUser		= trim($this->getParam('accountCpf'));
				$rgUser			= trim($this->getParam('accountRg'));
				$rgUF			= trim($this->getParam('accountRgUf'));
				$description	= $common->convertChar(trim($this->getParam('accountDescription')));
				$mailQuota		= trim($this->getParam('accountMailQuota'));
				$birthDate		= $common->mascaraBirthDate($this->getParam('accountBirthDate'));
				$st				= $this->getParam('accountSt');
				$city			= $this->getParam('accountCity');
				$sex			= $this->getParam('accountSex');
				$deletePhoto = ( $this->getParam('accountDeletePhoto') ) ? trim($this->getParam('accountDeletePhoto')) : null;
				
				if ( $uidNumber === 0 && $loginUser !== '' ) {
					
					$msg = $common->validateCharacters( $loginUser, 'accountLogin' );
					if ( $msg['status'] === false ) Errors::runException( 'ADMIN_FIELDS_VALIDATE', $msg['msg'].' : accountLogin' );
					
					$uidNumber = (int) $ldap_functions->getUidNumber( $loginUser );
					if ( $uidNumber === 0 ) Errors::runException( 'ADMIN_USER_NOT_FOUND' );
				}
				
				if ( $uidNumber === 0 ) Errors::runException( 'ADMIN_UIDNUMBER_EMPTY' );
				if ( !( $usr_info = $user_functions->get_user_info( $uidNumber ) ) ) Errors::runException( 'ADMIN_USER_NOT_FOUND' );
				
				// If rgUser and rgUF
				if ( (trim($rgUser) != "" && trim($rgUF) == "" ) || ( trim($rgUser) == "" && trim($rgUF) != "" ) )
					Errors::runException("ADMIN_RG_UF_EMPTY");
				
				// If not empty
				if ( trim($passwordUser) != "" && isset($passwordUser) )
				{	
					if ( trim($rePasswordUser) != "" && isset($rePasswordUser) )
					{
						// password and repassword are different ?
						if ( trim($passwordUser) != trim($rePasswordUser) ) Errors::runException( "ADMIN_PASSWORD_REPASSWORD" );
						
					}
					
					// validate password, 8 characteres minimum and 2 numbers
					$msg = $common->validatePassword($passwordUser);
					if ( $msg['status'] == false ) Errors::runException( "ADMIN_MINIMUM_CHARACTERS", $msg['msg']);
				}
				
				// CPF is invalid
				if ( trim($cpfUser) != "" && !$common->validateCPF($cpfUser) )
					Errors::runException( "ADMIN_CPF_INVALID" );
				
				//Characters not permited name
				$msg = $common->validateCharacters($nameUser);
				if ( $msg['status'] == false ) Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountName" );
				
				//Characters not permited mailQuota
				$msg = $common->validateCharacters($mailQuota, "accountMailQuota");
				if ( $msg['status'] == false ) Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountMailQuota" );
				
				// Params - Validade / Update Fields
				$fields = array();
				$fields['type']			= 'edit_user';
				$fields['uid']			= $usr_info['uid'];
				$fields['uidnumber']	= $usr_info['uidnumber'];
				$fields['mail']			= ( isset($emailUser) && trim($emailUser) !== '' )? $emailUser : $usr_info['mail'];
				$fields['cpf']			= $common->mascaraCPF($cpfUser);
				
				// Validate Fields
				$msg = $ldap_functions->validate_fields( array( 'attributes' => serialize( $fields ) ) );
				if ( $msg['status'] == false ) Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
				unset($fields['cpf']);
				
				//Name User
				$nameUser = explode(" ", $nameUser);
				
				$fields['givenname'] = $nameUser[0]; 
				
				if ( count($nameUser) > 1 )
					unset( $nameUser[0] );
				
				if ( trim($passwordUser) != "" )
					$fields['password1'] = $passwordUser;
				
				if ( trim($nameUser) != "" )
					$fields['sn'] = implode(" ", $nameUser );
				
				if ( trim($phoneUser) != "" )
					$fields['telephonenumber'] = $common->mascaraPhone($phoneUser);
				
				if ( trim($cpfUser) != "" )
					$fields['corporative_information_cpf'] = $common->mascaraCPF($cpfUser);
				
				if ( trim($rgUser) != "" )
					$fields['corporative_information_rg'] = $rgUser;
				
				if ( trim($rgUF) != "" )
					$fields['corporative_information_rguf'] = $rgUF;
				
				if ( trim($description) != "" )
					$fields['corporative_information_description'] = $description;
				
				if ( trim($mailQuota) != "" )
					$fields['mailquota'] = $mailQuota;
				
				if ( trim($birthDate) != "" )
					$fields['corporative_information_datanascimento'] = $birthDate;
				
				if ( trim($st) != "" )
					$fields['corporative_information_st']	= $st;
				
				if ( trim($city) != "" )
					$fields['corporative_information_city'] = $city;
				
				if ( trim($sex) != "" )
					$fields['corporative_information_sexo'] = $sex;

				if( isset($_FILES) && count($_FILES) > 0 )
				{
					$attrUser = $this->getUserSearchLdap(serialize(array("uid",$fields['uid'])));

					$fields['accountPhoto'] = array(
						'name' => $_FILES['accountPhoto']['name'],
						'type' => $_FILES['accountPhoto']['type'],
						'tmp_name' => $_FILES['accountPhoto']['tmp_name'],
						'size' => $_FILES['accountPhoto']['size'],
						'error' => $_FILES['accountPhoto']['error'],
						'source' => base64_encode(file_get_contents( $_FILES['accountPhoto']['tmp_name'], $_FILES['accountPhoto']['size'])),
								'photo_exist' => ( $attrUser[0]['accountPhoto'] == true ? true :  false )
					);

					unset( $_FILES['accountPhoto'] );
				}
				
				if( !is_null($deletePhoto) )
				{
					$fields['delete_photo'] = ( intval($deletePhoto) === 1 ? true : false );
				}
				
				// Update Fields
				$msg = $this->updateUser( $fields );
				if ( $msg['status'] == false ) Errors::runException( "ADMIN_UPDATE_USER", $msg['msg'] );
				
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

