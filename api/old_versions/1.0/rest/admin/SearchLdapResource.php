<?php

class SearchLdapResource extends AdminAdapter
{
	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
 		{
			// Permission
			$permission = array();
			$permission['action'] 	= 'list_users';
			$permission['apps'] 	= $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();
			
			if( $this->validatePermission($permission) )
			{
				$accountSearchUID = ( $this->getParam('accountSearchUID') ) ? trim($this->getParam('accountSearchUID')) : null;

				if( !is_null($accountSearchUID) )	
				{
					$accountSearchUID = trim(preg_replace("/[^a-z_0-9_-_.\\s]/", "", strtolower($accountSearchUID)));

					if( $accountSearchUID != "")
					{
						$accountSearchUID = trim($this->getParam('accountSearchUID'));

						$this->setResult( array( "result" => $this->getUserSearchLdap($accountSearchUID)) );
					}
					else
					{
						Errors::runException( "ADMIN_SEARCH_LDAP_CHARACTERS_NOT_ALLOWED" );
					}
				}
				else
				{
					Errors::runException( "ADMIN_SEARCH_LDAP_VAR_IS_NULL" );
				}
			}
			else
			{
				Errors::runException( "ACCESS_NOT_PERMITTED" );
			}
 		}

 		return $this->getResponse();
	}
}

?>