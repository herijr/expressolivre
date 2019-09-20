<?php

namespace App\Modules\Core;

use App\Errors;
use App\Adapters\ExpressoAdapter;

class UserApiResource extends ExpressoAdapter
{
	public function post($request)
	{
		if (!file_exists(dirname(__FILE__) . '/../../Config/profileHomeServer.ini')) {
			return Errors::runException(2201);
		} else {
			$user_id = $request['user'];

			$profiles = parse_ini_file(dirname(__FILE__) . '/../../Config/profileHomeServer.ini', true);
			$ldapHost = $profiles['ldap.server']['LDAP'];
			$ldapDN = $profiles['ldap.server']['BASE_DN'];

			// Get user
			$ldapConn = ldap_connect($ldapHost) or die(Errors::runException(2202));
			$result = ldap_search($ldapConn, $ldapDN, "(uid={$user_id})") or die(Errors::runException(2202));
			$data = ldap_get_entries($ldapConn, $result);

			// Verify use slash
			$useSlash = false;

			if (isset($profiles['misc.config']['USE_SLASH'])) {
				$_slash = strtolower($profiles['misc.config']['USE_SLASH']);
				$_slash = trim($_slash);
				$_slash = intval($_slash);

				$useSlash = (is_int($_slash) && $_slash == 1 ? true : false);
			}

			$api['userAPI'] = false;

			if (isset($data['count']) && $data['count']) {

				if (isset($data[0]['dn'])) {

					$tmpValue = $profiles['home.server']['DEFAULT'];

					foreach ($profiles['home.server'] as $key => $value) {
						if (preg_match('/ou=' . $key . ',dc/i', $data[0]['dn'])) {
							$tmpValue = trim($value);
						}
					}

					if (preg_match('/\/$/', $tmpValue)) {
						$tmpValue = preg_replace('/\/$/', '', $tmpValue);
					}

					$api['userAPI'] = ($useSlash ? $tmpValue . "/" : $tmpValue);
				}
			}

			if ($api['userAPI']) {

				$api['apis'][] = array(
					"api" => $api['userAPI'],
					"apps" => array("calendar", "catalog", "mail")
				);

				return $api;
			} else {
				return Errors::runException(2200);
			}
		}
	}
}
