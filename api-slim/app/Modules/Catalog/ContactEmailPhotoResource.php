<?php

namespace App\Modules\Catalog;

use App\Errors;
use App\Adapters\CatalogAdapter;

class ContactEmailPhotoResource extends CatalogAdapter
{
    protected function getUserJpegPhotoByEmail($mail)
    {
        $filter = "(&(phpgwAccountType=u)(mail=" . $mail . "))";
        $ldap_context = $GLOBALS['phpgw_info']['server']['ldap_context'];
        $justthese = array('jpegPhoto');
        $ds = $this->getLdapCatalog()->ds;
        if ($ds) {
            $sr = @ldap_search($ds, $ldap_context, $filter, $justthese);
            if ($sr) {
                $entry = ldap_first_entry($ds, $sr);
                if ($entry) {
                    $photo = @ldap_get_values_len($ds, $entry, "jpegphoto");
                    return $photo[0];
                }
            }
        }
        return false;
    }

    public function post($request)
    {
        $email = $request['email'];
        $this->getLdapCatalog()->ldapConnect(true);
        $photo = $this->getUserJpegPhotoByEmail($email);
        $contact[] = array('contactMail'     => $email, 'contactPicture'   => ($photo != null ? base64_encode($photo) : ""));
        return array('contacts' => $contact);
    }
}
