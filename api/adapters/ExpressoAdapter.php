<?php
include_once dirname(__FILE__).'/../library/tonic/lib/tonic.php';
include_once dirname(__FILE__).'/../library/utils/Errors.php';
class ExpressoAdapter extends Resource {

	private $cannotModifyHeader;
	private $expressoVersion;
	private $request;
	private $params;
	private $result;
	private $error;	
	private $id;

	private $method_module;
	private $method_route;
	private $method_description;
	private $method_types;
	private $method_params = array();
	private $method_isMobile = false;

	function __construct($id){
		
		if (!isset($GLOBALS['phpgw_info'])) {
			
			$GLOBALS['phpgw_info'] = array(
				'flags' => array(
					'currentapp'				=> "login",
					'noheader'					=> True,
					'disable_Template_class'	=> True
				)
			);

			include_once(API_EXPRESSO_PATH.'/header.inc.php');
			include_once(API_EXPRESSO_PATH.'/phpgwapi/inc/class.xmlrpc_server.inc.php');
		}
		//define('PHPGW_TEMPLATE_DIR', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir', 'phpgwapi'));
		$this->expressoVersion = substr($GLOBALS['phpgw_info']['server']['versions']['phpgwapi'],0,3);
		$this->setCannotModifyHeader(false);

	}

	public function setResource($module,$route,$description,$method_types) {
		$this->method_module        = $module;
		$this->method_route 		= $route;
		$this->method_description 	= utf8_encode( $description );
		$this->method_types 		= $method_types;
	}

	public function setDocumentation() {

	}

	public function setIsMobile($isMobile) {
		$this->method_isMobile        = $isMobile;
	}

	public function addResourceParam($paramName,$type,$obrigatory,$description,$enabled = true,$default_value = "", $field_type = "text") {
		$enabled_string = "0";
		if ($enabled) {
			$enabled_string = "1";
		}
		if ($obrigatory) {
			$obrigatory_string = "1";
		} else {
			$obrigatory_string = "0";
		}
		$param = 
		array("param" => $paramName,
			  "enabled" => $enabled_string, 
			  "type" => $type, 
			  "obrigatory" => $obrigatory_string, 
			  "default_value" => $default_value, 
			  "description" => utf8_encode( $description ),
			  "field_type" => $field_type );
		$this->method_params[$paramName] = $param;
		return $param;
	}

	public function getDocumentation() {
		$method_id = strtolower(str_replace("/", "_", $this->method_route));
		$result = array(
			"id" => $method_id,
			"module" => $this->method_module,
			"rest" => $this->method_route,
			"description" =>  $this->method_description,
			"method" => $this->method_types,
			"mobile" => $this->method_isMobile,
			"params" => $this->method_params,
		); 
		return $result;
	}

	protected function addModuleTranslation($module) {
		$lang = $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'];
		$GLOBALS['phpgw']->translation->add_app($module,$lang);
	}
	
	protected function setRequest($request){
		$this->request = $request;
	}
	
	public function getRequest(){
		return $this->request;
	}
	
	protected function getExpressoVersion(){
		return $this->expressoVersion;
	}
	
	protected function setResult($result){
		$this->result = $result;
	}
	
	public function getResult(){
		return $this->result;
	}
	
	protected function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	protected function setParams($params){		
		$this->params = $params;
	}
	
	public function getParams(){	
		return $this->params;
	}
	
	public function getParam($param){
		return is_string($this->params->$param)? mb_convert_encoding($this->params->$param, "ISO_8859-1", "UTF8") : $this->params->$param;
	}
	
	public function setError($error) {
		$this-> error = $error;
	}
	
	protected function getError() {
		return $this-> error;
	}
	
	protected function setCannotModifyHeader($boolean){
		$this-> cannotModifyHeader = $boolean;
	}
	protected function getCannotModifyHeader(){
		return $this-> cannotModifyHeader;
	}

	public function post($request){
		if(!$request->data)
			$request->data = $_POST;
		$this->setRequest($request);		
		if(!is_array($request->data))
			parse_str(urldecode($request->data), $request->data);
		$data = (object)$request->data;		
		if($data){
			if($data->params){
				$this->setParams( is_array($data->params)? (object)$data->params : json_decode($data->params) );
			}
			if($data->id)
				$this->setId($data->id);
		}
	}	
	
	public function get($request){
		$response = new Response($request);
		$response->code = Response::OK;
		$response->addHeader('content-type', 'text/html');		
		$response->body = "<H4>Metodo GET nao permitido para este recurso.</H4>";		
		return $response;
	}
	
	public function getResponse(){
		$response = new Response($this->getRequest());
		
		if($this->getCannotModifyHeader())
			return $response;
		
		$response->code = Response::OK;
		$response->addHeader('content-type', 'application/json');

		if($this->getId())
			$body['id']	= $this->getId();
		if($this->getResult())
			$body['result']	= $this->getResult();
		else {
			Errors::runException("E_UNKNOWN_ERROR");			
		}
		
		
		$response->body = json_encode($body);
		
		return $response;
	}
	
	protected function isLoggedIn(){
		if($this->getParam('auth') != null) {
			list($sessionid, $kp3) = explode(":", $this->getParam('auth'));
			if($GLOBALS['phpgw']->session->verify($sessionid, $kp3)){							
				return $sessionid;
			}
			else{
				Errors::runException("LOGIN_AUTH_INVALID");							
			}
		}
		elseif($sessionid = $GLOBALS['_COOKIE']['sessionid']) {
			if($GLOBALS['phpgw']->session->verify($sessionid)) {
				return $sessionid;
			}
			else{
				Errors::runException("LOGIN_NOT_LOGGED_IN");
			}
		}
		else{
			Errors::runException("LOGIN_NOT_LOGGED_IN");			
		}		
	}

	
	protected function getServices()
	{
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
		$config = parse_ini_file( API_DIRECTORY . '/../config/user.ini',true);
		$apps 	= $config['Applications.mapping'];
	
		// Load Granted Apps for User
		$contactApps = array();
		$acl 	= CreateObject('phpgwapi.acl');
		if ($user_id == "") {
			$user_id = $GLOBALS['phpgw_info']['user']['account_id']['acl'];
		}
		foreach($acl->get_user_applications($user_id) as $app => $value){
			$enabledApp = array_search($app, $apps);
			if($enabledApp !== FALSE)
				$contactApps[] = $enabledApp;
		}
	
		return $contactApps;
	}

	public function validateDate($date,$obrigatory = true,$exception = "EXPRESSO_INVALID_DATE") 
	{
		if ($date != "") {

			$regex_date  = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/([12][0-9]{3})( ([01][0-9]|2[0-3])(:[0-5][0-9]){2})?$/';

			if(!preg_match($regex_date, $date))
				Errors::runException($exception);
		} else {
			if ($obrigatory) {
				Errors::runException($exception);
			}
		}
		return $date;
	}

	public function validateTime($value,$obrigatory = true, $exception = "EXPRESSO_INVALID_TIME")
	{
		if ($value != "") {

			$regex  = '/^(([0-1][0-9])|([2][0-3])):([0-5][0-9])?$/';

			if(!preg_match($regex, $value))
				Errors::runException($exception);
		} else {
			if ($obrigatory) {
				Errors::runException($exception);
			}
		}
		return $date;
	}

	public function validateInteger($value,$obrigatory = true,$exception = "EXPRESSO_INVALID_INTEGER")
	{
		if (($obrigatory) && ($value == "")) {
			Errors::runException($exception);
		}
		if ($value != "") {
			if (!is_numeric($value)) {
				Errors::runException($exception);
			}
 		}
 		return (int)$value;
	}

	public function validateString($string,$obrigatory = true,$exception = "EXPRESSO_INVALID_OBRIGATORY_FIELD",$possible_values = array()) 
	{

		if (($obrigatory) && ($string == "")) {
			Errors::runException($exception);
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
					Errors::runException($exception);
				}
			}
		} 
	}
				
}
