<?php

namespace App\Modules\Catalog;

use App\Errors;
use App\Adapters\CatalogAdapter;

class ContactPictureResource extends CatalogAdapter
{
	public function post($request)
	{
		$contact = array();

		$contactID = $request['contactID'];

		// User Contact
		if ($request['contactType'] == 1 && $contactID != null) {

			$query = "select A.id_contact, A.photo from phpgw_cc_contact A where A.id_contact= ? and A.id_owner = ? ";

			$result = $this->getDb()->Link_ID->query($query, array($contactID, $this->getUserId()));

			if ($result) {

				while ($row = $result->fetchRow()) {
					if ($row['photo'] != null) {
						$contact[] = array(
							'contactID'     => $row['id_contact'],
							'contactImagePicture'   => ($row['photo'] != null ? base64_encode($row['photo']) : "")
						);
					}
				}
			}
		} elseif ($request['contactType'] == 2) { // Global Catalog
			if (!$contactID) {
				$contactID = $GLOBALS['phpgw_info']['user']['account_dn'];
			}
			$photo = $this->getUserLdapPhoto(urldecode($contactID));
			$contact[] = array(
				'contactID' => $contactID,
				'contactImagePicture' => ($photo ? base64_encode($photo) : '')
			);
		}

		return array('contacts' => $contact);
	}
}
