<?php

namespace App\Modules\Catalog;

use App\Errors;
use App\Adapters\CatalogAdapter;
use App\Encoding\ISO8859;

class ContactAddResource extends CatalogAdapter
{
	private $iso;

	public function __construct()
	{
		$this->iso = new ISO8859();
	}

	public function post($request)
	{
		$contactID = $request['contactID'];
		//New Contact
		$newContact 	= array();
		$newContact[0]	= $this->iso->encoding(trim($request['contactAlias']));
		$newContact[1]	= $this->iso->encoding(trim($request['contactGivenName']));
		$newContact[2]	= $this->iso->encoding(trim($request['contactFamilyName']));
		$newContact[3]	= $this->iso->encoding(trim($request['contactPhone']));
		$newContact[4]	= $this->iso->encoding(trim($request['contactEmail']));

		// Field Validation
		$lastChar = substr($newContact[4], -1);
		if ($lastChar == ",") {
			$newContact[4] = substr($newContact[4], 0, -1);
		}

		$lastChar = substr($newContact[3], -1);
		if ($lastChar == ",") {
			$newContact[3] = substr($newContact[3], 0, -1);
		}

		$contactEmails = explode(",", $newContact[4]);
		foreach ($contactEmails as $contactEmail) {
			$contactEmail = trim($contactEmail);
			if ($contactEmail === "") {
				return Errors::runException("CATALOG_EMAIL_EMPTY");
			} else {
				if (!preg_match("/^[[:alnum:]]+([\.\_\-]?([[:alnum:]]+))+\@(([[:alnum:]\-]+)\.)+[[:alpha:]]{2,4}$/", $contactEmail)) {
					return Errors::runException("CATALOG_EMAIL_INVALID");
				}
			}
		}

		if ($contactID != "") {
			$result = unserialize($this->updateContact($contactID, $newContact));
		} else {
			$result = $this->addContact($newContact);
		}

		if ($result['status'] === "false") {
			return Errors::runException($result['msg']);
		}

		return true;
	}
}
