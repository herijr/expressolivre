<?php

namespace App\Adapters;

class ExpressoAdapter
{
	private $expressoVersion;

	public function __construct()
	{
		if (!isset($GLOBALS['phpgw_info'])) {

			$GLOBALS['phpgw_info'] = array(
				'flags' => array(
					'currentapp'				=> "login",
					'noheader'					=> True,
					'disable_Template_class'	=> True,
				)
			);

			include_once(dirname(__FILE__) . '/../../../header.inc.php');
			include_once( dirname( __FILE__ ) . '/../../../phpgwapi/inc/class.xmlrpc_server.inc.php');
		}

		$this->expressoVersion = substr($GLOBALS['phpgw_info']['server']['versions']['phpgwapi'], 0, 3);
	}

	protected function addModuleTranslation($module)
	{
		$lang = $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'];

		$GLOBALS['phpgw']->translation->add_app($module, $lang);
	}

	protected function getExpressoVersion()
	{
		return $this->expressoVersion;
	}

	protected function getServices()
	{
		// Enable/Disable Expresso Messenger
		$im = CreateObject('phpgwapi.messenger');
		$_return = array();
		if ($im->checkAuth()) {
			$_return['chat'] = array(
				'chatDomain' => $im->domain,
				'chatUrl'    => $im->url,
			);
		}
		return $_return;
	}

	protected function getUserApps($user_id = "")
	{
		// Load Granted Apps for Web Service
		$config = parse_ini_file(dirname(__FILE__) . "/../Config/user.ini", true);
		$apps 	= $config['Applications.mapping'];

		// Load Granted Apps for User
		$contactApps = array();
		$acl 	= CreateObject('phpgwapi.acl');
		$user_id = (trim($user_id) !== "" ? $user_id : $GLOBALS['phpgw_info']['user']['account_id']);
		$applicationsACL = $acl->get_user_applications($user_id);

		if (is_array($applicationsACL) && count($applicationsACL) > 0) {
			foreach ($applicationsACL as $app => $value) {
				$enabledApp = array_search($app, $apps);
				if ($enabledApp !== FALSE) {
					$contactApps[] = $enabledApp;
				}
			}
		}

		return $contactApps;
	}

	public function isLoggedIn($request)
	{
		if (isset($request['auth'])) {

			list($sessionid, $kp3) = explode(":", $request['auth']);

			if ($GLOBALS['phpgw']->session->verify($sessionid, $kp3)) {
				return array('status' => true, 'sessionid' => $sessionid);
			} else {
				return array('status' => false, 'msg' => 'LOGIN_AUTH_INVALID');
			}
		} else {
			return array('status' => false, 'msg' => 'LOGIN_NOT_LOGGED_IN');
		}
	}
}
