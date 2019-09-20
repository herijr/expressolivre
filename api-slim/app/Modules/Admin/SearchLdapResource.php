<?php

namespace App\Modules\Admin;

use App\Adapters\AdminAdapter;
use App\Errors;

class SearchLdapResource extends AdminAdapter
{
	public function post($request)
	{
		// Permission
		$permission = array();
		$permission['action'] = 'list_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if ($this->validatePermission($permission)) {
			$accountSearchUID 	= ($request['accountSearchUID']) ? trim($request['accountSearchUID']) : null;
			$accountSearchCPF 	= ($request['accountSearchCPF']) ? trim($request['accountSearchCPF']) : null;
			$accountSearchRG 	= ($request['accountSearchRG']) ? trim($request['accountSearchRG']) : null;
			$accountSearchMail 	= ($request['accountSearchMail']) ? trim($request['accountSearchMail']) : null;

			if (!is_null($accountSearchUID) || !is_null($accountSearchCPF) || !is_null($accountSearchRG) || !is_null($accountSearchMail)) {
				if ($accountSearchUID != "") {
					$accountSearchUID = trim(preg_replace("/[^a-z_0-9_-_.\\s]/", "", strtolower($accountSearchUID)));

					$accountSearchUID = trim($this->getParam('accountSearchUID'));

					return $this->getUserSearchLdap(serialize(array("uid", $accountSearchUID)));
				} else if ($accountSearchCPF != "") {
					$accountSearchCPF = trim(preg_replace("/[^0-9]/", "", $accountSearchCPF));

					if (strlen($accountSearchCPF) == 11) {
						return $this->getUserSearchLdap(serialize(array("cpf", $accountSearchCPF)));
					} else {
						return Errors::runException("ADMIN_CPF_IS_NOT_VALID");
					}
				} else if ($accountSearchRG != "") {
					$accountSearchRG = trim(preg_replace("/[^0-9]/", "", $accountSearchRG));

					if (!empty($accountSearchRG)) {
						return $this->getUserSearchLdap(serialize(array("rg", $accountSearchRG)));
					} else {
						return Errors::runException("ADMIN_RG_UF_EMPTY");
					}
				} else if ($accountSearchMail != "") {
					if (!empty($accountSearchMail)) {
						return $this->getUserSearchLdap(serialize(array("mail", $accountSearchMail)));
					} else {
						return Errors::runException("ADMIN_MAIL_EMPTY");
					}
				} else {
					return Errors::runException("ADMIN_SEARCH_LDAP_CHARACTERS_NOT_ALLOWED");
				}
			} else {
				return Errors::runException("ADMIN_SEARCH_LDAP_VAR_IS_NULL");
			}
		} else {
			return Errors::runException("ACCESS_NOT_PERMITTED");
		}
	}
}
