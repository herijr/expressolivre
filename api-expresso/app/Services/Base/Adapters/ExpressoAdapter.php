<?php

namespace App\Services\Base\adapters;

use App\Services\Base\Commons\Errors;

//class ExpressoAdapter extends Resource {
class ExpressoAdapter {

	// private $cannotModifyHeader;
	private $expressoVersion;
	private $request;
	private $params;
	private $preferences;
	// private $result;
	// private $error;	
	// private $id;
	// 
	// private $method_module;
	// private $method_route;
	// private $method_description;
	// private $method_types;
	// private $method_params = array();
	// private $method_isMobile = false;

	function __construct(){
		
		if (!isset($GLOBALS['phpgw_info'])) {
			
			preg_match( "/.*(Mail\/Send)/" , $_SERVER['REQUEST_URI'], $foundResources );

			$GLOBALS['phpgw_info'] = array(
				'flags' => array(
					'currentapp'				=> "login",
					'noheader'					=> True,
					'disable_Template_class'	=> True,
					'disable_modify_request'  	=> ( ( count($foundResources) > 0 ) ? True : False ),
				)
			);

			require_once( dirname( __FILE__ ) . '/../../../../../header.inc.php');
			require_once( dirname( __FILE__ ) . '/../../../../../phpgwapi/inc/class.xmlrpc_server.inc.php');
		}
		//define('PHPGW_TEMPLATE_DIR', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir', 'phpgwapi'));
		$this->expressoVersion = substr($GLOBALS['phpgw_info']['server']['versions']['phpgwapi'],0,3);
		//$this->setCannotModifyHeader(false);

	}

	// public function setResource($module,$route,$description,$method_types) {
	// 	$this->method_module        = $module;
	// 	$this->method_route 		= $route;
	// 	$this->method_description 	= utf8_encode( $description );
	// 	$this->method_types 		= $method_types;
	// }

	// public function setDocumentation() {
	// 
	// }

	// public function setIsMobile($isMobile) {
	// 	$this->method_isMobile        = $isMobile;
	// }

	// public function addResourceParam($paramName,$type,$obrigatory,$description,$enabled = true,$default_value = "", $field_type = "text") {
	// 	$enabled_string = "0";
	// 	if ($enabled) {
	// 		$enabled_string = "1";
	// 	}
	// 	if ($obrigatory) {
	// 		$obrigatory_string = "1";
	// 	} else {
	// 		$obrigatory_string = "0";
	// 	}
	// 	$param = 
	// 	array("param" => $paramName,
	// 		  "enabled" => $enabled_string, 
	// 		  "type" => $type, 
	// 		  "obrigatory" => $obrigatory_string, 
	// 		  "default_value" => $default_value, 
	// 		  "description" => utf8_encode( $description ),
	// 		  "field_type" => $field_type );
	// 	$this->method_params[$paramName] = $param;
	// 	return $param;
	// }

	// public function getDocumentation() {
	// 	$method_id = strtolower(str_replace("/", "_", $this->method_route));
	// 	$result = array(
	// 		"id" => $method_id,
	// 		"module" => $this->method_module,
	// 		"rest" => $this->method_route,
	// 		"description" =>  $this->method_description,
	// 		"method" => $this->method_types,
	// 		"mobile" => $this->method_isMobile,
	// 		"params" => $this->method_params,
	// 	); 
	// 	return $result;
	// }

	protected function addModuleTranslation($module) {
		$lang = $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'];
		$GLOBALS['phpgw']->translation->add_app($module,$lang);
	}
	
	// protected function setRequest($request){
	// 	$this->request = $request;
	// }
	
	// public function getRequest(){
	// 	return $this->request;
	// }
	
	protected function getExpressoVersion(){
		return $this->expressoVersion;
	}
	
	protected function setResult($result){
		$this->result = $result;
	}
	
	public function getResult(){
		return $this->result;
	}
	
	// protected function setId($id){
	// 	$this->id = $id;
	// }
	
	// public function getId(){
	// 	return $this->id;
	// }
	
	public function setParams($params){
		$this->params = $params;
	}
	
	public function getParams(){
		return $this->params;
	}
	
	public function getParam($param){
		return isset($this->params[$param] )? mb_convert_encoding($this->params[$param], "ISO-8859-1", "UTF-8") : null;
	}
	
	// public function setError($error) {
	// 	$this-> error = $error;
	// }
	
	// protected function getError() {
	// 	return $this-> error;
	// }
	
	// protected function setCannotModifyHeader($boolean){
	// 	$this-> cannotModifyHeader = $boolean;
	// }
	
	// protected function getCannotModifyHeader(){
	// 	return $this-> cannotModifyHeader;
	// }

	// public function post($request){
	// 	if(!$request->data)
	// 		$request->data = $_POST;
	// 	$this->setRequest($request);		
	// 	if(!is_array($request->data))
	// 		parse_str(urldecode($request->data), $request->data);
	// 	$data = (object)$request->data;		
	// 	if($data){
	// 		if($data->params){
	// 			$this->setParams( is_array($data->params)? (object)$data->params : json_decode($data->params) );
	// 		}
	// 		if($data->id)
	// 			$this->setId($data->id);
	// 	}
	// }	
	
	// public function get($request){
	// 	$response = new Response($request);
	// 	$response->code = Response::OK;
	// 	$response->addHeader('content-type', 'text/html');		
	// 	$response->body = "<H4>Metodo GET nao permitido para este recurso.</H4>";		
	// 	return $response;
	// }
	
	public function getResponse(){
		
		$body = array();
		
		$result = $this->getResult();
		
		if( $result ){
			$body = isset($result['error']) ? $result : array( "result" => $result );
		} else {
			$body['error'] = Errors::runException("E_UNKNOWN_ERROR");			
		}
		
		return json_encode($body);
	}

	public function isLoggedIn(){
		if( !is_null($this->getParam('auth')) ) {
			list($sessionid, $kp3) = explode(":", $this->getParam('auth'));
			if($GLOBALS['phpgw']->session->verify($sessionid, $kp3)){							
				return $sessionid;
			}
			else{
				return false;
			}
		} elseif($sessionid = $GLOBALS['_COOKIE']['sessionid']) {
			if($GLOBALS['phpgw']->session->verify($sessionid)) {
				return $sessionid;
			} else {
				return false;
			}
		} else {
			return false;
		}		
	}
	
	protected function getServices(){
		// Enable/Disable Expresso Messenger
		$im = CreateObject('phpgwapi.messenger');
		$_return = array();
		if ( $im->checkAuth() ) {
			$_return['chat'] = array(
				'chatDomain' => $im->domain,
				'chatUrl'    => $im->url,
			);
		}
		return $_return;
	}

	protected function getUserApps($user_id = ""){
		// Load Granted Apps for Web Service
		$config = parse_ini_file( dirname( __FILE__ ) . '/../Config/user.ini',true);
		$apps 	= $config['Applications.mapping'];
		// Load Granted Apps for User
		$contactApps = array();
		$acl 	= CreateObject('phpgwapi.acl');
    $user_id = ( trim($user_id) !== "" ? $user_id : $GLOBALS['phpgw_info']['user']['account_id']);
    $applicationsACL = $acl->get_user_applications($user_id);
	
    if( is_array($applicationsACL) && count($applicationsACL) > 0 ){
      foreach($applicationsACL as $app => $value){
        $enabledApp = array_search($app, $apps);
        if( $enabledApp !== FALSE ){
          $contactApps[] = $enabledApp;
        }
  		}
    }
	
		return $contactApps;
	}

	public function validateDate($date,$obrigatory = true,$exception = "EXPRESSO_INVALID_DATE"){
		
		if ($date != "") {
			$regex_date  = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/([12][0-9]{3})( ([01][0-9]|2[0-3])(:[0-5][0-9]){2})?$/';
			if(!preg_match($regex_date, $date)){
				return array( "error" => Errors::runException($exception));
			}
		} else {
			if ($obrigatory) {
				return array( "error" => Errors::runException($exception));
			}
		}
		
		return $date;
	}

	public function validateTime($value,$obrigatory = true, $exception = "EXPRESSO_INVALID_TIME"){
		if ($value != "") {
			$regex  = '/^(([0-1][0-9])|([2][0-3])):([0-5][0-9])?$/';
			if(!preg_match($regex, $value)){
				return array( "error" => Errors::runException($exception));
			}
		} else {
			if ($obrigatory) {
				return array( "error" => Errors::runException($exception));
			}
		}
		return $date;
	}

	public function validateInteger($value,$obrigatory = true,$exception = "EXPRESSO_INVALID_INTEGER"){
		if (($obrigatory) && ($value == "")) {
			return array( "error" => Errors::runException($exception));
		}
		if ($value != "") {
			if (!is_numeric($value)) {
				return array( "error" => Errors::runException($exception));
			}
 		}
 		return (int)$value;
	}

	public function validateString($string,$obrigatory = true,$exception = "EXPRESSO_INVALID_OBRIGATORY_FIELD",$possible_values = array()){
		if (($obrigatory) && ($string == "")) {
			return array( "error" => Errors::runException($exception));
		}
		if ($string != "") {
			if (!empty($possible_values)) 
			{
				$found = false;
				foreach($possible_values as $value) {
					if ($value == $string) {
						$found = true;
					}
				}
				if (!$found) {
					return array( "error" =>Errors::runException($exception));
				}
			}
		} 
		return true;
	}

}
