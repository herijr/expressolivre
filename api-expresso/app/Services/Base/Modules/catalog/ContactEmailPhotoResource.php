<?php

namespace App\Services\Base\Modules\catalog;

use App\Services\Base\Adapters\CatalogAdapter;
use App\Services\Base\Commons\Errors;

class ContactEmailPhotoResource extends CatalogAdapter {

	public function setDocumentation() {
		$this->setResource("Catalog","Catalog/Photo","Retorna a Foto do Usuário.",array("GET"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("email","string",false,"Email do usuário que será buscado a foto.");
	}

	private function getUserJpegPhotoByEmail($mail)
	{
		$filter="(&(phpgwAccountType=u)(mail=".$mail."))";
		$ldap_context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		$justthese = array('jpegPhoto');
		$ds = $this->getLdapCatalog()->ds;
		if( $ds ){
				$sr = @ldap_search($ds, $ldap_context, $filter, $justthese);
			if ($sr) {
				$entry = ldap_first_entry($ds, $sr);
				if($entry) {
					$photo = @ldap_get_values_len($ds, $entry, "jpegphoto");
					return $photo[0];
				}
			}
		}
		return false;
	}

	public function post($request){
	
		$this->setParams( $request );
		
		$email = $this->getParam('email');
		
		$this->getLdapCatalog()->ldapConnect(true);
		
		$photo = $this->getUserJpegPhotoByEmail($email);
		
		$contact[] = array('contactMail' => $email, 'contactPicture' => ($photo != null ? base64_encode($photo) : ""));
		
		$result = array('contacts' => $contact);
		
		$this->setResult($result);

		return $this->getResponse();
	}

	public function get($request) {
		
		// $this->setRequest($request);
		// 
		// if( $this->isLoggedIn() ) {
		// 	$email = $this->getParam('email');
		// 	$this->getLdapCatalog()->ldapConnect(true);
		// 	$photo = $this->getUserJpegPhotoByEmail($email);
		// }

		// if (!$photo) {
		// 	$response = new Response($request);
		// 	$response->code = 204;
		// 	return $response;
		// } else {
		// 	$response = new Response($request);
		// 	$response->code = Response::OK;
		// 	//print_r($photo);
		// 	$response->addHeader('content-type', 'image/jpeg');
		// 	$response->body = $photo;
		// 	return $response;
		// }
	}
}
