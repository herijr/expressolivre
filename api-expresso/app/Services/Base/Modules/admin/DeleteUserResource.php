<?php

namespace App\Services\Base\Modules\admin;

use App\Services\Base\Adapters\AdminAdapter;
use App\Services\Base\Commons\Errors;
use App\Services\Base\Modules\admin;

class DeleteUserResource extends AdminAdapter
{
	public function setDocumentation() {
		$this->setResource("Admin","Admin/DeleteUser","Exclui um usário no Expresso, necessário ter a permissão no Módulo ExpressoAdmin",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("accountUid","string",true,"UID do usuário");
		$this->addResourceParam("accountUidNumber","string",true,"UIDNumber do usuário");
	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		$this->setParams( $request );

		// Permission
		$permission = array();
		$permission['action'] = 'delete_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if( $this->validatePermission($permission) ) 	
		{	
			$uidUser 		= $this->getParam('accountUid');
			$uidNumberUser	= $this->getParam('accountUidNumber');

			//Field Validation
			if(trim($uidUser) == "" && isset($uidUser))
				return Errors::runException( "ADMIN_UID_EMPTY" );

			if(trim($uidNumberUser) == "" && isset($uidNumberUser))	
				return Errors::runException( "ADMIN_UIDNUMBER_EMPTY" );

			// Delete User
			$params = array();
			$params['uid'] = $uidUser;
			$params['uidnumber'] = $uidNumberUser;

			$msg = $this->deleteUser( $params );

			if( $msg['status'] == false )
			{
				return Errors::runException( "ADMIN_DELETE_USER", $msg['msg'] );
			}

			$this->setResult(true);
		}
		else
		{
			return Errors::runException( "ACCESS_NOT_PERMITTED" );
		}			

		return $this->getResponse();
	}
}
