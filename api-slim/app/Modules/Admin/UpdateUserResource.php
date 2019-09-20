<?php

namespace App\Modules\Admin;

use App\Modules\Admin\CommonFunctions;
use App\Adapters\AdminAdapter;
use App\Errors;

class UpdateUserResource extends AdminAdapter
{
	public function post($request)
	{
		// Permission
		$permission = array();
		$permission['action'] = 'edit_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if ($this->validatePermission($permission)) {
			//Class CommonFunctions
			$common         = new CommonFunctions();
			$user_functions = CreateObject('expressoAdmin1_2.user');
			$ldap_functions = CreateObject('expressoAdmin1_2.ldap_functions');

			$uidNumber		= (int) trim($request['accountUidNumber']);
			$loginUser		= (string) trim($request['accountLogin']);
			$emailUser		= trim($request['accountEmail']);
			$nameUser		= $common->convertChar(trim($request['accountName']));
			$passwordUser	= trim($request['accountPassword']);
			$rePasswordUser	= trim($request['accountRePassword']);
			$phoneUser		= trim($request['accountPhone']);
			$cpfUser		= trim($request['accountCpf']);
			$rgUser			= trim($request['accountRg']);
			$rgUF			= trim($request['accountRgUf']);
			$description	= $request['accountDescription'];
			$description	= mb_convert_encoding($description, "ISO-8859-1", mb_detect_encoding($description, 'UTF-8, ISO-8859-1', true));
			$mailQuota		= trim($request['accountMailQuota']);
			$birthDate		= $common->mascaraBirthDate($request['accountBirthDate']);
			$st				= $request['accountSt'];
			$city			= $request['accountCity'];
			$sex			= $request['accountSex'];
			$deletePhoto = ($request['accountDeletePhoto']) ? trim($request['accountDeletePhoto']) : null;

			if ($uidNumber === 0 && $loginUser !== '') {

				$msg = $common->validateCharacters($loginUser, 'accountLogin');
				if ($msg['status'] === false) return Errors::runException('ADMIN_FIELDS_VALIDATE', $msg['msg'] . ' : accountLogin');

				$uidNumber = (int)$ldap_functions->getUidNumber($loginUser);
				if ($uidNumber === 0) return Errors::runException('ADMIN_USER_NOT_FOUND');
			}
			
			if ($uidNumber === 0){ 
				return Errors::runException('ADMIN_UIDNUMBER_EMPTY');
			}
			
			if (!($usr_info = $user_functions->get_user_info($uidNumber))){
				return Errors::runException('ADMIN_USER_NOT_FOUND');
			}

			// If rgUser and rgUF
			if ((trim($rgUser) != "" && trim($rgUF) == "") || (trim($rgUser) == "" && trim($rgUF) != "")){
				return Errors::runException("ADMIN_RG_UF_EMPTY");
			}

			// If not empty
			if (trim($passwordUser) != "" && isset($passwordUser)) {
				if (trim($rePasswordUser) != "" && isset($rePasswordUser)) {
					// password and repassword are different ?
					if (trim($passwordUser) != trim($rePasswordUser)){ 
						return Errors::runException("ADMIN_PASSWORD_REPASSWORD");
					}
				}

				// validate password, 8 characteres minimum and 2 numbers
				$msg = $common->validatePassword($passwordUser);
				if ($msg['status'] == false) return Errors::runException("ADMIN_MINIMUM_CHARACTERS", $msg['msg']);
			}

			// CPF is invalid
			if (trim($cpfUser) != "" && !$common->validateCPF($cpfUser)){ 
				return Errors::runException("ADMIN_CPF_INVALID");
			}

			//Characters not permited name
			$msg = $common->validateCharacters($nameUser);
			if ($msg['status'] == false){ return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountName"); }

			//Characters not permited mailQuota
			$msg = $common->validateCharacters($mailQuota, "accountMailQuota");
			if ($msg['status'] == false){ return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg'] . " : accountMailQuota"); }

			// Params - Validade / Update Fields
			$fields = array();
			$fields['type']			= 'edit_user';
			$fields['uid']			= $usr_info['uid'];
			$fields['uidnumber']	= $usr_info['uidnumber'];
			$fields['mail']			= (isset($emailUser) && trim($emailUser) !== '') ? $emailUser : $usr_info['mail'];
			$fields['cpf']			= $common->mascaraCPF($cpfUser);

			// Validate Fields
			$msg = $ldap_functions->validate_fields(array('attributes' => serialize($fields)));
			if ($msg['status'] == false){ return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg']); }
			unset($fields['cpf']);

			//Name User
			$nameUser = explode(" ", $nameUser);

			$fields['givenname'] = $nameUser[0];

			if (count($nameUser) > 1)
				unset($nameUser[0]);

			if (trim($passwordUser) != "")
				$fields['password1'] = $passwordUser;
			
			if (is_array($nameUser))
				$fields['sn'] = implode(" ", $nameUser);
				
			if (trim($phoneUser) != "")
				$fields['telephonenumber'] = $common->mascaraPhone($phoneUser);

			if (trim($cpfUser) != "")
				$fields['corporative_information_cpf'] = $common->mascaraCPF($cpfUser);

			if (trim($rgUser) != "")
				$fields['corporative_information_rg'] = $rgUser;

			if (trim($rgUF) != "")
				$fields['corporative_information_rguf'] = $rgUF;

			if (trim($description) != "")
				$fields['corporative_information_description'] = $description;
				
			if (trim($mailQuota) != "")
				$fields['mailquota'] = $mailQuota;

			if (trim($birthDate) != "")
				$fields['corporative_information_datanascimento'] = $birthDate;

			if (trim($st) != "")
				$fields['corporative_information_st']	= $st;

			if (trim($city) != "")
				$fields['corporative_information_city'] = $city;

			if (trim($sex) != "")
				$fields['corporative_information_sexo'] = $sex;

			if (isset($_FILES) && count($_FILES) > 0) {
				$attrUser = $this->getUserSearchLdap(serialize(array("uid", $fields['uid'])));

				$fields['accountPhoto'] = array(
					'name' => $_FILES['accountPhoto']['name'],
					'type' => $_FILES['accountPhoto']['type'],
					'tmp_name' => $_FILES['accountPhoto']['tmp_name'],
					'size' => $_FILES['accountPhoto']['size'],
					'error' => $_FILES['accountPhoto']['error'],
					'source' => base64_encode(file_get_contents($_FILES['accountPhoto']['tmp_name'], $_FILES['accountPhoto']['size'])),
					'photo_exist' => ($attrUser[0]['accountPhoto'] == true ? true : false)
				);

				unset($_FILES['accountPhoto']);
			}

			if (!is_null($deletePhoto)) {
				$fields['delete_photo'] = (intval($deletePhoto) === 1 ? true : false);
			}

			// Update Fields
			$msg = $this->updateUser($fields);
			
			if ($msg['status'] == false){
				return Errors::runException("ADMIN_UPDATE_USER", $msg['msg']);
			}

			return true;

		} else {
			return Errors::runException("ACCESS_NOT_PERMITTED");
		}
	}
}
