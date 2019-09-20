<?php

namespace App\Modules\Core;

use App\Errors;
use App\Adapters\ExpressoAdapter;

class LogoutResource extends ExpressoAdapter {

	public function post($request){
		
		$result = $this->isLoggedIn($request);

		if( $result['status'] ){

			$GLOBALS['phpgw']->hooks->process('logout');

			$GLOBALS['phpgw']->session->destroy($_SESSION['phpgw_session']['session_id'], $GLOBALS['kp3']);

			return true;
		}

		//to Send Response (JSON RPC format)
		return Errors::runException( $result['msg'] );
	}
}