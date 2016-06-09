<?php

class ContactAddResource extends CatalogAdapter {

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{
			//New Contact
			$newContact 	= array();
			$newContact[0]	= trim($this->getParam('contactAlias'));
			$newContact[1]	= trim($this->getParam('contactGivenName'));			
			$newContact[2]	= trim($this->getParam('contactFamilyName'));
			$newContact[3]	= trim($this->getParam('contactPhone'));			
			$newContact[4]	= trim($this->getParam('contactEmail'));			

			// Field Validation
		    if( $newContact[4] === "" )
		    {
				Errors::runException( "CATALOG_EMAIL_EMPTY" );
		    }
		    else
		    {	
		        if( !preg_match("/^[[:alnum:]]+([\.\_\-]?([[:alnum:]]+))+\@(([[:alnum:]\-]+)\.)+[[:alpha:]]{2,4}$/", $newContact[4]) )
		        {
					Errors::runException( "CATALOG_EMAIL_INVALID" );		        	
		        }
		    }

			$result = unserialize($this->addContact($newContact));

			if( $result['status'] === "false")
			{	
				Errors::runException( $result['msg'] );
			}
			else
			{
				$this->setResult(true);
			}

		}
		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}
}