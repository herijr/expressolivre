<?php

namespace App\Modules\Admin;

use App\Modules\Admin\CommonFunctions;
use App\Adapters\AdminAdapter;
use App\Errors;

class CreateUserResource extends AdminAdapter
{
	public function post($request)
	{
		// Permission
		$permission = array();
		$permission['action'] = 'add_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if ($this->validatePermission($permission)) {
			//Class CommonFunctions
			$common	= new CommonFunctions();

			$loginUser 		= trim($request['accountLogin']);
			$emailUser		= trim($request['accountEmail']);
			$nameUser		= $common->convertChar(trim($request['accountName']));
			$profileUser	= trim($request['accountProfile']);
			$passwordUser	= trim($request['accountPassword']);
			$rePasswordUser	= trim($request['accountRePassword']);
			$phoneUser		= trim($request['accountPhone']);
			$cpfUser		= trim($request['accountCpf']);
			$rgUser			= trim($request['accountRg']);
			$rgUF			= trim($request['accountRgUf']);
			$birthDate		= $common->mascaraBirthDate($request['accountBirthDate']);
			$st 			= $request['accountSt'];
			$city			= $request['accountCity'];
			$sex			= $request['accountSex'];
			$description 	= $common->convertChar(trim($request['accountDescription']));

			if (isset($_FILES) && count($_FILES) > 0) {
				$accountPhoto = array(
					'name' => $_FILES['accountPhoto']['name'],
					'type' => $_FILES['accountPhoto']['type'],
					'tmp_name' => $_FILES['accountPhoto']['tmp_name'],
					'size' => $_FILES['accountPhoto']['size'],
					'error' => $_FILES['accountPhoto']['error'],
					'source' => base64_encode(file_get_contents($_FILES['accountPhoto']['tmp_name'], $_FILES['accountPhoto']['size']))
				);

				unset($_FILES['accountPhoto']);
			}

			// Field Validation
			if (trim($loginUser) == "" && isset($loginUser))
				return Errors::runException("ADMIN_LOGIN_EMPTY");

			if (trim($nameUser) == "" && isset($nameUser))
				return Errors::runException("ADMIN_NAME_EMPTY");

			if (trim($profileUser) == "" && isset($profileUser))
				return Errors::runException("ADMIN_PROFILE_USER_EMPTY");

			if (trim($emailUser) == "" && isset($emailUser))
				return Errors::runException("ADMIN_EMAIL_EMPTY");

			if (trim($passwordUser) == "" && isset($passwordUser))
				return Errors::runException("ADMIN_PASSWORD_EMPTY");

			if (trim($rePasswordUser) == "" && isset($rePasswordUser))
				return Errors::runException("ADMIN_RE_PASSWORD_EMPTY");

			//If rgUser and rgUF
			if ((trim($rgUser) != "" && trim($rgUF) == "") || (trim($rgUser) == "" && trim($rgUF) != "")) {
				return Errors::runException("ADMIN_RG_UF_EMPTY");
			}

			// password and repassword are different ? 				
			if (trim($passwordUser) != trim($rePasswordUser)) {
				return Errors::runException("ADMIN_PASSWORD_REPASSWORD");
			}

			// validate password, 8 characteres minimum and 2 numbers
			$msg = $common->validatePassword($passwordUser);

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_MINIMUM_CHARACTERS", $msg['msg']);
			}

			// CPF is invalid
			if (trim($cpfUser) != "" && !$common->validateCPF($cpfUser)) {
				return Errors::runException("ADMIN_CPF_INVALID");
			}

			// Characters not permited login
			$msg = $common->validateCharacters($loginUser, "accountLogin");

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountLogin");
			}

			//Characters not permited name
			$msg = $common->validateCharacters($nameUser);

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountName");
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
			if (count($nameUser) > 1) {
				unset($nameUser[0]);
			}
			$fields['sn'] = implode(" ", $nameUser);
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
			$msg = $this->validateFields(array("attributes" => serialize($fields)));

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg']);
			}

			// Create User
			unset($fields['cpf']);

			$msg = $this->createUser($fields);

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_CREATE_USER", $msg['msg']);
			}

			return true;
		} else {
			return Errors::runException("ACCESS_NOT_PERMITTED");
		}
	}
}
