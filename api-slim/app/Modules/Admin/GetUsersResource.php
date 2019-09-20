<?php

namespace App\Modules\Admin;

use App\Adapters\AdminAdapter;
use App\Errors;

class GetUsersResource extends AdminAdapter
{
	public function post($request)
	{
		// Permission
		$permission = array();
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		$uidUser 	= $request['accountUidNumber'];
		$searchUser = $request['accountSearchUser'];
		$searchUserLiteral = $request['accountSearchUserLID'];

		//Validate Fields
		$uidUser = str_replace("*", "", $uidUser);
		$uidUser = str_replace("%", "", $uidUser);

		$searchUser = str_replace("*", "", $searchUser);
		$searchUser = str_replace("%", "", $searchUser);

		$searchUserLiteral = str_replace("*", "", $searchUserLiteral);
		$searchUserLiteral = str_replace("%", "", $searchUserLiteral);

		if (trim($uidUser) != "" && isset($uidUser)) {
			$permission['action'] = 'edit_users';

			if ($this->validatePermission($permission)) {
				// Get User
				$fields = $this->editUser($uidUser);

				if ($fields != false) {
					// Return fields
					$return = array();
					$return[] = array(
						'accountUidnumber'		=> $fields['uidnumber'],
						'accountLogin'			=> $fields['uid'],
						'accountEmail'			=> $fields['mail'],
						'accountName'			=> $fields['givenname'] . " " . $fields['sn'],
						'accountPhone'			=> $fields['telephonenumber'],
						'accountCpf'			=> $fields['corporative_information_cpf'],
						'accountRg'				=> $fields['corporative_information_rg'],
						'accountRgUf'			=> $fields['corporative_information_rguf'],
						'accountDescription'	=> $fields['corporative_information_description'],
						'accountMailQuota'		=> $fields['mailquota']
					);

					return array("users" => $return);
				} else {

					return Errors::runException("ADMIN_USER_NOT_FOUND");
				}
			} else {

				return Errors::runException("ACCESS_NOT_PERMITTED");
			}
		} else {

			$permission['action'] = 'list_users';

			if ($this->validatePermission($permission)) {
				// Return list
				$return = array();

				if (trim($searchUser) != "" && isset($searchUser)) {
					$list = $this->listUsers($searchUser);

					foreach ($list as $key => $users) {
						$return[] = array(
							'accountId' 	=> $users['account_id'],
							'accountLid'	=> $users['account_lid'],
							'accountCn'		=> $users['account_cn'],
							'accountMail'	=> $users['account_mail']
						);
					}

					if (count($return) > 0) {
						return array("users" => $return);
					} else {
						return Errors::runException("ADMIN_USERS_NOT_FOUND");
					}
				} else {
					$user = $this->listUsersLiteral($searchUserLiteral);

					if ($user) {
						$return[] = array(
							'accountId' 	=> $user['account_id'],
							'accountLid'	=> $user['account_lid'],
							'accountCn'		=> $user['account_cn'],
							'accountMail'	=> $user['account_mail']
						);

						return array("users" => $return);
					} else {
						return Errors::runException("ADMIN_USER_NOT_FOUND");
					}
				}
			} else {
				return Errors::runException("ACCESS_NOT_PERMITTED");
			}
		}
	}
}
