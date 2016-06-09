<?php

class ContactsResource extends CatalogAdapter {	

	public function setDocumentation() {

		$this->setResource("Catalog","Catalog/Contacts","Retorna os contatos do catálogo pessoal ou do catálogo geral.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("search","string",false,"Buscar contatos com o nome.");
		$this->addResourceParam("contactType","string",true,"(1 = Catálogo Pessoal, 2 = Catálogo Geral)",false,"1");
		$this->addResourceParam("contactID","string",false,"ID do contato que será retornado.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);	
		
		if($this-> isLoggedIn()) 
		{			
			
			if($this->getParams()) {
				$search = trim($this->getParam('search'));
				$search = ($search ? mb_convert_encoding($search,"ISO_8859-1", "UTF8") : "");
				$contactID = $this->getParam('contactID');

				$search_string = "%$search%";

				$arr_params = array();

				if($this->getParam('contactType') == 1) {
					if($search != "") {	
						$arr_params[] = $search_string;
						$arr_params[] = $search_string;
						$arr_params[] = $search_string;
						$query_contact = "(A.alias ilike ? or A.names_ordered ilike ? or C.connection_value ilike ?) and";
					}
					elseif($this->getParam('contactID') > 0){
						$arr_params[] = $contactID;
						$query_contact = "A.id_contact = ? and ";
					}
				}
				elseif($this->getParam('contactType') == 2){
					if($this-> getMinArgumentSearch() <= strlen($search) || !empty($contactID))
						return $this->getGlobalContacts($search, $contactID );
					else{
						Errors::runException("CATALOG_MIN_ARGUMENT_SEARCH", $this-> getMinArgumentSearch());
					}
				}
			}

			//ADICIONA O ID_OWNER 
			$arr_params[] = $this->getUserId();
		
			$query = 'select 
						  B.id_typeof_contact_connection, 
						  A.photo, 
						  A.id_contact, 
						  A.alias, 
						  A.given_names, 
						  A.family_names, 
						  A.names_ordered, 
						  A.birthdate, 
						  A.notes, 
						  C.connection_value 
						from 
							phpgw_cc_contact A, 
							phpgw_cc_contact_conns B, 
							phpgw_cc_connections C 
						where 
							A.id_contact = B.id_contact and 
							B.id_connection = C.id_connection and 
							'.$query_contact.' 
							A.id_owner = ?
					group by 
						B.id_typeof_contact_connection, 
						A.photo, 
						A.id_contact, 
						A.alias, 
						A.given_names,
						A.family_names,
						A.names_ordered,
						A.birthdate,
						A.notes,
						C.connection_value	
					order by 
						lower(A.names_ordered)';

			$resQuery = $this->getDb()->Link_ID->query($query,$arr_params);

			$contacts = array();
			while($row = $resQuery->fetchRow()) {

				$id = $row['id_contact'];
				$contactType = ($row['id_typeof_contact_connection'] == 2 ? 'contactPhones' : 'contactMails');

				if($contacts[$id] != null){
					$contacts[$id][$contactType][] = $row['connection_value'];
				}
				else{
					$contacts[$id] = array(
							'contactID'		=> $row['id_contact'],
							$contactType	=> array($row['connection_value']),
							'contactAlias' => ($row['alias'] != null ?  mb_convert_encoding($row['alias'],"UTF8", "ISO_8859-1") : ""),
							'contactFirstName'	=> ($row['given_names'] != null ?  mb_convert_encoding($row['given_names'],"UTF8", "ISO_8859-1") : ""),
							'contactLastName' 	=> ($row['family_names'] != null ?  mb_convert_encoding($row['family_names'],"UTF8", "ISO_8859-1") : ""),
							'contactFullName' 	=> ($row['names_ordered'] != null ? mb_convert_encoding($row['names_ordered'],"UTF8", "ISO_8859-1") : ""),
							'contactBirthDate'	=> ($row['birthdate'] != null ? $row['birthdate'] : ""),
							'contactNotes' 		=> ($row['notes'] != null ?  mb_convert_encoding($row['notes'],"UTF8", "ISO_8859-1") : ""),
							'contactHasImagePicture' => ($row['photo'] != null ? 1 : 0),
					);
				}
			}

			$result = array ( 'contacts' => array_values($contacts));
			$this->setResult($result);
		}
		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}	

}
