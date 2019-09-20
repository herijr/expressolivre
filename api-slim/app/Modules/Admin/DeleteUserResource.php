<?php

namespace App\Modules\Admin;

use App\Adapters\AdminAdapter;
use App\Errors;

class DeleteUserResource extends AdminAdapter
{
	public function post($request)
	{
		// Permission
		$permission = array();
		$permission['action'] = 'delete_users';
		$permission['apps'] = $this->getUserApps();

		//Load Conf Admin
		$this->loadConfAdmin();

		if( $this->validatePermission($permission) ) 	
		{	
			$uidUser 		= $request['accountUid'];
			$uidNumberUser	= $request['accountUidNumber'];

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

			return true;
		}
		else
		{
			return Errors::runException( "ACCESS_NOT_PERMITTED" );
		}			
	}
}
