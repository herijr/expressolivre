<?php

namespace App\Modules\Admin;

use App\Adapters\AdminAdapter;
use App\Errors;

class RenameUserResource extends AdminAdapter
{
	public function post($request)
	{
		$common	= new CommonFunctions();

		// Permission
		$permission = array();
		$permission['action'] = 'rename_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if ($this->validatePermission($permission)) {
			$uidUser 	= $request['accountUidRename'];
			$uidNewUser = $request['accountUidNewRename'];

			// Field Validation
			if (trim($uidUser) == "" && isset($uidUser)) {
				return Errors::runException("ADMIN_UID_EMPTY");
			}

			if (trim($uidNewUser) == "" && isset($uidNewUser)) {
				return Errors::runException("ADMIN_NEW_UID_EMPTY");
			}

			// Params
			$fieldsValidate = array();
			$fieldsValidate['type'] = "rename_user";
			$fieldsValidate['uid']	= $uidNewUser;

			// Validate Fields
			$msg = $this->validateFields(array("attributes" => serialize($fieldsValidate)));

			if (isset($msg['status']) && $msg['status'] == false) {
				return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg']);
			}

			// Characters not permited
			$msg = $common->validateCharacters($uidNewUser);

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_FIELDS_VALIDATE", $msg['msg']);
			}

			// Rename User
			$fieldsRename = array();
			$fieldsRename['uid'] = $uidUser;
			$fieldsRename['new_uid'] = $uidNewUser;

			$msg = $this->renameUser($fieldsRename);

			if ($msg['status'] == false) {
				return Errors::runException("ADMIN_CREATE_USER", $msg['msg']);
			}
			return true;
		} else {
			return Errors::runException("ACCESS_NOT_PERMITTED");
		}
	}
}
