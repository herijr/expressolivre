<?php

namespace App\Services\Base\Modules\admin;

use App\Services\Base\Adapters\AdminAdapter;
use App\Services\Base\Commons\Errors;
use App\Services\Base\Modules\admin;

class RenameUserResource extends AdminAdapter
{
	public function setDocumentation() {
		$this->setResource("Admin","Admin/RenameUser","Renomeia um usuário no Expresso, necessário ter a permissão no Módulo ExpressoAdmin.",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("accountUidRename","string",true,"UID do usuário a ser renomeado.");
		$this->addResourceParam("accountUidNewRename","string",true,"Novo UID do usuário ser renomeado.");
	}

	public function post($request)
	{
 		$this->setParams($request);

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
			if( trim($uidUser) == "" && isset($uidUser) ){	
				return Errors::runException( "ADMIN_UID_EMPTY" );
			}

			if( trim($uidNewUser) == "" && isset($uidNewUser) ){ 	
				return Errors::runException( "ADMIN_NEW_UID_EMPTY" );
			}

			// Params
			$fieldsValidate = array();
			$fieldsValidate['type'] = "rename_user";
			$fieldsValidate['uid']	= $uidNewUser;

			// Validate Fields
			$msg = $this->validateFields( array("attributes" => serialize($fieldsValidate)) );

			if( isset($msg['status']) && $msg['status'] == false ){
				return Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
			}

			// Characters not permited
			$msg = $common->validateCharacters($uidNewUser);

			if( $msg['status'] == false ){
				return Errors::runException( "ADMIN_FIELDS_VALIDATE", $msg['msg'] );
			}
			
			// Rename User
			$fieldsRename = array();
			$fieldsRename['uid'] = $uidUser;
			$fieldsRename['new_uid'] = $uidNewUser;	

			$msg = $this->renameUser( $fieldsRename );

			if( $msg['status'] == false ){
				return Errors::runException( "ADMIN_CREATE_USER", $msg['msg'] );
			}
			$this->setResult(true);
		} else {
			return Errors::runException( "ACCESS_NOT_PERMITTED" );
		}			
		
		return $this->getResponse();
	}
}
