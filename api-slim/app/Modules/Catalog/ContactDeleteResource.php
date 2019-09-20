<?php

namespace App\Modules\Catalog;

use App\Errors;
use App\Adapters\CatalogAdapter;

class ContactDeleteResource extends CatalogAdapter
{
	public function post($request)
	{
		//New Contact
		$contactID	= trim($request['contactID']);
		$contactID	= trim(preg_replace("/[^0-9]/", "", $contactID));

		// Field Validation
		if ($contactID === "") {
			return Errors::runException("CATALOG_ID_EMPTY");
		}

		return unserialize($this->deleteContact($contactID));
	}
}
