<?php

namespace App\Modules\Core;

use App\Adapters\ExpressoAdapter;

class LoginResource extends ExpressoAdapter
{

	private function getUserProfile()
	{
		if ($this->getExpressoVersion() != "2.2") {
			$_SESSION['wallet']['user']['uidNumber'] = $GLOBALS['phpgw_info']['user']['account_id'];
		}

		return array(
			'contactID'				=> $GLOBALS['phpgw_info']['user']['account_dn'],
			'contactMails' 			=> array($GLOBALS['phpgw_info']['user']['email']),
			'contactPhones' 		=> array($GLOBALS['phpgw_info']['user']['telephonenumber']),
			'contactFullName'		=> $GLOBALS['phpgw_info']['user']['fullname'],
			'contactLID'			=> $GLOBALS['phpgw_info']['user']['account_lid'],
			'contactUIDNumber'		=> $GLOBALS['phpgw_info']['user']['account_id'],
			'contactApps'			=> $this->getUserApps(),
			'contactServices'		=> $this->getServices()

		);
	}

	public function post($request)
	{

		if ($sessionid = $GLOBALS['phpgw']->session->create($request['user'], $request['password'])) {
			$result = array(
				'auth' 			=> $sessionid . ":" . $GLOBALS['phpgw']->session->kp3,
				'profile' 		=> array($this->getUserProfile())
			);

			return $result;
		}

		return false;
	}
}
