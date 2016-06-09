<?php

class RenameUserResource extends AdminAdapter
{
	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$common	= new CommonFunctions();

			// Permission
			$permission = array();
			$permission['action'] = 'rename_users';			
			$permission['apps'] = $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();

			if( $this->validatePermission($permission) ) 	
			{	
				$uidUser 	= $this->getParam('accountUidRename');
				$uidNewUser = $this->getParam('accountUidNewRename');
											   
				// Field Validation
				if( trim($uidUser) == "" && isset($uidUser) )	
					Errors::runException( "ADMIN_UID_EMPTY" );

				if( trim($uidNewUser) == "" && isset($uidNewUser) )	
					Errors::runException( "ADMIN_NEW_UID_EMPTY" );

				// Params
				$fieldsValidate = array();
				$fieldsValidate['type'] = "rename_user";
				$fieldsValidate['uid']	= $uidNewUser;

				// Validate Fields
				$msg = $this->validateFields( array("attributes" => serialize($fields)) );

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
				}

				// Characters not permited
				$msg = $common->validateCharacters($uidNewUser);

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
				}
				
				// Rename User
				$fieldsRename = array();
				$fieldsRename['uid'] = $uidUser;
				$fieldsRename['new_uid'] = $uidNewUser;	

				$msg = $this->renameUser( $fieldsRename );

				if( $msg['status'] == false )
				{
					Errors::runException( "ADMIN_CREATE_USER", $msg['msg'] );
				}


				$this->setResult(true);
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
