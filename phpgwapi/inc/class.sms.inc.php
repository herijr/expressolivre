<?php
/**
 * SMS
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMS
 * @version		1.5
 */

class sms
{
	
	public $public_functions = array
	(
		'SendCheckCode'			=> True,
		'SubmitPersonalForm'	=> True,
	);
	
	private $_error_messages = array(
		'E_LOAD_EXTENSION'			=> array('code' => -32604,	'message' => 'Extension \'%1\' is not loaded'),
		'E_UNKNOWN_ERROR'			=> array('code' => 500,		'message' => 'HTTP Unknown error'),
		'LOGIN_NOT_LOGGED_IN'		=> array('code' => 3,		'message' => 'You are not logged in'),
		'SMS_DISABLED'				=> array('code' => 2120,	'message' => 'sms disabled'),
		'WS_SMS_ERROR'				=> array('code' => 2121,	'message' => '%1'),
		'UNKNOWN_OPTION'			=> array('code' => 2122,	'message' => 'unknown option \'%1\''),
		'SMS_CHECK_CODE_REACHED'	=> array('code' => 2123,	'message' => 'sms daily limit reached'),
		'SMS_CHECK_CODE_INVALID'	=> array('code' => 2124,	'message' => 'checking code invalid'),
		'SMS_CHECK_CODE_EXPIRED'	=> array('code' => 2125,	'message' => 'checking code expired'),
		'SMS_CHECK_CODE_NOT_FOUND'	=> array('code' => 2126,	'message' => 'checking code not found'),
		'SMS_RECV_DENIED'			=> array('code' => 2127,	'message' => 'sms receive denied'),
		'MOBILE_INVALID'			=> array('code' => 2128,	'message' => 'invalid phone number'),
		'SMS_SEND_DENIED'			=> array('code' => 2129,	'message' => 'sms send denied'),
		'SMS_EMPTY_MESSAGE'			=> array('code' => 2130,	'message' => 'sms empty message'),
	);
	private $_error = null;
	private $_result = false;
	
	/**
	 * User account id
	 * @var int
	 */
	private $_account_id = null;
	
	/**
	 * Storage user send credential
	 * @var array
	 */
	private $_sendAuth = null;
	
	/**
	 * User preferences object
	 * @var Preferences
	 */
	private $_pref;
	
	/**
	 * SoapClient object
	 * @var SoapClient
	 */
	private $_soap;
	
	/**
	 * Options array
	 * @var array
	 */
	private $_options = array(
		'enabled'		=> false,
		'wsdl'			=> null,
		'groups'		=> null,
		'user'			=> null,
		'passwd'		=> null,
		'country_code'	=> '55',
	);
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		try {
			if (!extension_loaded('soap')) $this->runException('E_LOAD_EXTENSION');
			
			foreach ($this->_options as $key => $value)
				if (isset($GLOBALS['phpgw_info']['server']['sms_'.$key]))
					$options[$key] = $GLOBALS['phpgw_info']['server']['sms_'.$key];
			
			$this->_setOptions($options);
			
			if ($this->isEnabled()) $this->_setUser();
			
		} catch (Exception $e) {}
	}
	
	/**
	 * Allows setting options as an associative array of option => value pairs.
	 *
	 * @param  array|Zend_Config $options
	 * @return SMSAdapter $this
	 * @throws $this->runException('UNKNOWN_OPTION');
	 */
	private function _setOptions($options)
	{
		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		}
		
		foreach ($options as $key => $value) {
			if(array_key_exists($key,$this->_options)) $this->_options[$key] = $value;
			else $this->runException("UNKNOWN_OPTION",$key);
		}
		
		if (!is_array($this->_options['groups'])) {
			$this->_options['groups'] = explode(',',$this->_options['groups']);
			foreach ($this->_options['groups'] as $key => $value) $this->_options['groups'][$key] = (int)preg_replace('/.*;/','',$value);
		}
		
		return $this;
	}
	
	/**
	 * 
	 */
	private function _setUser()
	{
		if (!isset($GLOBALS['phpgw_info']['user']['account_id'])) $this->runException('LOGIN_NOT_LOGGED_IN');
		if ( $this->_account_id == $GLOBALS['phpgw_info']['user']['account_id']) return $this;
		$this->_error = null;
		$this->_account_id = $GLOBALS['phpgw_info']['user']['account_id'];
		$this->_pref = new preferences();
		$this->_pref->read_repository();
		return $this;
	}
	
	public function getError()
	{
		return $this->error;
	}
	/**
	 * Return array of options suitable for using with SMSAdapter constructor
	 *
	 * @return array $options
	 */
	public function getOptions()
	{
		$options = $this->_options;
		foreach ($options as $key => $value) if ($value == null) unset($options[$key]);
		return $options;
	}
	
	/**
	 * Check if module SMS is enabled
	 *
	 * @return boolean $enabled
	 */
	public function isEnabled()
	{
		return (bool)$this->_options['enabled'];
	}
	
	/**
	 * Check if module SMS is enabled
	 *
	 * @return SMSAdapter $this
	 * @throws $this->runException('SMS_DISABLED');
	 */
	public function checkEnabled()
	{
		if (!$this->isEnabled()) $this->runException("SMS_DISABLED");
		return $this;
	}
	
	/**
	 * Save user preferences
	 *
	 * @return SMSAdapter $this
	 */
	public function savePreferences()
	{
		$this->_setUser();
		$this->_pref->save_repository(true);
		return $this;
	}
	
	/**
	 * Search ldap properties
	 * 
	 * @param string $uid
	 * @param string|array $params
	 * @return stdClass
	 */
	private function _ldapSearch( $uid, $params )
	{
		$uid = (string)$uid;
		$params = is_array($params)? $params : array($params);
		
		$ldap_conn = $GLOBALS['phpgw']->common->ldapConnect();
		$entries = ldap_get_entries($ldap_conn, ldap_search($ldap_conn, $GLOBALS['phpgw_info']['server']['ldap_context'], 'uid='.$uid, $params));
		
		$result = new stdClass();
		foreach ($params as $param)
			$result->{$param} = isset($entries[0][strtolower($param)][0])? $entries[0][strtolower($param)][0] : null;
		
		return $result;
	}
	
	/**
	 * Get authorization global send sms to phone number
	 * Case the preference is not set, return true
	 * 
	 * @param preferences $pref
	 * @return boolean $auth
	 */
	public function getAuth($pref = null)
	{
		$this->_setUser();
		$pref = ($pref)? $pref : $this->_pref;
		return isset($pref->user['security']['sms_auth'])? (bool)$pref->user['security']['sms_auth'] : true;
	}
	
	/**
	 * Set authorization global send sms to phone number
	 *
	 * @param boolean $value
	 * @return SMSAdapter $this
	 */
	public function setAuth($value)
	{
		$this->_setUser();
		$this->_pref->user['security']['sms_auth'] = (bool)$value;
		return $this;
	}
	
	/**
	 * Check once require current user send authority
	 * 
	 * @return SMSAdapter $this
	 * @throws $this->runException('SMS_SEND_DENIED');
	 */
	public function checkSendAuth()
	{
		$this->_setUser();
		
		// Get credencial to send message
		$this->_sendAuth = ($this->_sendAuth)? $this->_sendAuth : $this->getSendAuth($this->_account_id);
		
		// Denied send if get authority fail
		if (!$this->_sendAuth) $this->runException('SMS_SEND_DENIED');
		
		return $this;
	}
	/**
	 * Test checkSendAuth is valid
	 * 
	 * @return bool
	 */
	public function hasSendAuth()
	{
		$this->_setUser();
		
		$result = true;
		try {
			$this->checkSendAuth();
			if (!(isset($this->_sendAuth['user']) && isset($this->_sendAuth['passwd']))) throw new Exception();
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Get webservice credential to send message
	 * 
	 * @param int $account_id
	 * @return boolean|array $sendAuth
	 */
	public function getSendAuth( $account_id )
	{
		$this->_setUser();
		
		$sendAuth = false;
		try {
			// Get groups that the user belongs
			if (isset($GLOBALS['phpgw_info']['accounts']['cache']['membership_list'][$account_id])) {
				
				$member_of = $GLOBALS['phpgw_info']['accounts']['cache']['membership_list'][$account_id];
				
			} else {
				
				$acc = new accounts();
				$member_of = $acc->membership($account_id);
			}
			
			// Empty membership array
			if ( !is_array($member_of) ) throw new Exception();
			
			// Make a integer array
			foreach ($member_of as $key => $value) $member_of[$key] = (int)$value['account_id'];
			
			// Intersect user groups and sms credential groups
			foreach (array_intersect($member_of,$this->_options['groups']) as $group_id) {
				
				$grp_pref = new preferences($group_id);
				$grp_pref->read_repository();
				
				// Get credential of group 
				if (isset($grp_pref->user['security']['sms']['priority'])) {
					
					// Set only the highest priority
					if ((!$sendAuth) || (
							$grp_pref->user['security']['sms']['priority'] < $sendAuth['priority'] &&
							strlen($grp_pref->user['security']['sms']['user']) > 0 &&
							strlen($grp_pref->user['security']['sms']['passwd']) > 0
					)) $sendAuth = $grp_pref->user['security']['sms'];
					
				}
				
			}
		} catch (Exception $e) {}
		
		return $sendAuth;
	}
	
	/**
	 * Search a valid phone number, with all validations include
	 * 
	 * @param int $account_id
	 * @return string $number
	 * @throws $this->runException('SMS_RECV_DENIED');
	 * @throws $this->runException('MOBILE_INVALID');
	 * @throws $this->runException('SMS_CHECK_CODE_INVALID');
	 */
	public function checkRecvAuth( $account_id )
	{
		$this->_setUser();
		
		// Init user and preferences data
		$account_id = (int)$account_id;
		$acc = new accounts($account_id);
		$acc->read_repository();
		$acc_pref = new preferences($account_id);
		$acc_pref->read_repository();
		
		// Check permission of receive sms on phone number
		if (!$this->getAuth($acc_pref)) $this->runException('SMS_RECV_DENIED');
		
		// Search current mobile phone number on ldap
		$number = preg_replace('/[^\d]/', '', $this->_ldapSearch($acc->data['account_lid'],'mobile')->mobile);
		if (strlen($number) <= 0) $this->runException('MOBILE_INVALID');
		
		// Check if ldap number is valid
		if (!in_array($number,$this->getCheckedListPhoneNumbers($acc_pref),true))  $this->runException('SMS_CHECK_CODE_INVALID');
		
		return $number;
	}
	
	/**
	 * Get the last phone numbers wich was send checking code
	 * 
	 * @return string $number
	 */
	public function getLastPhoneNumberWasSendCode()
	{
		$this->_setUser();
		return (string)$this->_pref->user['security']['sms']['number'];
	}
	
	/**
	 * Get the list phone numbers checked
	 * 
	 * @param preferences $pref
	 * @return array $mobiles
	 */
	public function getCheckedListPhoneNumbers($pref = null)
	{
		$this->_setUser();
		$pref = ($pref)? $pref : $this->_pref;
		return (is_array($pref->user['security']['mobiles']))? $pref->user['security']['mobiles'] : array();
	}
	
	/**
	 * Verifies that reached the daily limit
	 * 
	 * @param string $number
	 * @return boolean $checked
	 */
	public function isCheckedPhoneNumber( $number )
	{
		$this->_setUser();
		$number = (string)preg_replace('/[^\d]/', '', $number);
		return in_array ($number, $this->getCheckedListPhoneNumbers());
	}
	
	/**
	 * Validate ckeck code
	 * NOTICE: this action save preferences
	 * 
	 * @param string $code
	 * @param string $number
	 * @return SMSAdapter $this
	 * @throws $this->runException('SMS_CHECK_CODE_NOT_FOUND');
	 * @throws $this->runException('SMS_CHECK_CODE_INVALID');
	 * @throws $this->runException('SMS_CHECK_CODE_EXPIRED');
	 */
	public function validateCheckCodeToPhoneNumber( $code, $number )
	{
		$this->_setUser();
		$code = (string)preg_replace('/[^\d]/', '', $code);
		$number = (string)preg_replace('/[^\d]/', '', $number);
		
		// Ignore if number already checked
		if ($this->isCheckedPhoneNumber($number)) return $this;
		
		// Force send code to validate
		if (!isset($this->_pref->user['security']['sms']['md5'])) $this->runException('SMS_CHECK_CODE_NOT_FOUND');;
		
		// Compare stored md5 with code and number 
		if($this->_pref->user['security']['sms']['md5'] == md5($code.'-'.$number)) {
			
			// Init mobiles security array
			if (!is_array($number,$this->_pref->user['security']['mobiles']))
				$this->_pref->user['security']['mobiles'] = array();
			
			// Prevent duplicate in array
			if (!in_array($number,$this->_pref->user['security']['mobiles']))
				$this->_pref->user['security']['mobiles'][] = $number;
			
			// Clean stored data
			unset($this->_pref->user['security']['sms']['md5']);
			unset($this->_pref->user['security']['sms']['number']);
			
		} else {
			
			// Get new validate attempt 
			$cont = ((int)$this->_pref->user['security']['sms']['attempts']) + 1;
			
			// Reached limit attempts or store new attempt
			if ($cont >= 3) {
				
				// Clean stored data
				unset($this->_pref->user['security']['sms']['md5']);
				unset($this->_pref->user['security']['sms']['number']);
				
			} else $this->_pref->user['security']['sms']['attempts'] = $cont;
			
			// NOTICE: this action save preferences changed before this method
			$this->savePreferences();
			
			$this->runException(($cont >= 3)? 'SMS_CHECK_CODE_EXPIRED' : 'SMS_CHECK_CODE_INVALID');
		}
		
		return $this;
	}
	
	/**
	 * Send checking code with five digits to phone number
	 * 
	 * @param string $number
	 * @return SMSAdapter $this
	 * @throws $this->runException('SMS_CHECK_CODE_INVALID');
	 * @throws $this->runException('SMS_CHECK_CODE_REACHED');
	 * @throws $this->runException('WS_SMS_ERROR');
	 */
	public function sendCheckCodeToPhoneNumber( $number )
	{
		$this->_setUser();
		
		$number = (string)preg_replace('/[^\d]/', '', $number);
		
		// Send code to a number already checked is an error
		if ($this->isCheckedPhoneNumber($number)) $this->runException('SMS_CHECK_CODE_INVALID');
		
		// Renew daily limit
		if (((int)date('Ymd')) > ((int)$this->_pref->user['security']['sms']['date'])) {
			$this->_pref->user['security']['sms']['date'] = (int)date('Ymd');
			$this->_pref->user['security']['sms']['cont'] = (int)0;
		}
		
		// Verifies that reached the daily limit
		$cont = (int)$this->_pref->user['security']['sms']['cont'];
		if ($cont >= 3) $this->runException('SMS_CHECK_CODE_REACHED');
		
		// Make random code, with five digits
		$code = substr(uniqid('', true), -5);
		
		// Make message
		$message = lang('Cheking code Expresso: %1', $code);
		
		try {
			
			// Send message using system uidNumber
			$this->_send($this->getSendAuth($this->_ldapSearch($this->_options['user'],'uidNumber')->uidNumber), $number, $message);
			
		} catch (Exception $e) {
			
			$this->runException('WS_SMS_ERROR',$e->getMessage());
		}
		
		// Store data in preferences
		$this->_pref->user['security']['sms']['md5'] = md5($code.'-'.$number);
		$this->_pref->user['security']['sms']['cont'] = (int)($cont + 1);
		$this->_pref->user['security']['sms']['number'] = $number;
		$this->_pref->user['security']['sms']['attempts'] = 0;
		
		return $this;
	}
	
	/**
	 * Resource to send message
	 *
	 * @param int|array(int) $ids
	 * @param string $message
	 * @return SMSAdapter $this
	 * @throws $this->runException('SMS_EMPTY_MESSAGE');
	 * @throws $this->runException('WS_SMS_ERROR');
	 */
	public function send_message($ids, $message)
	{
		$this->_setUser();
		
		// Dont send empty messages
		if (empty($message)) $this->runException('SMS_EMPTY_MESSAGE');
		
		// Check user authority to send sms
		$this->checkSendAuth();
		
		// Init counter send success status
		$cnt_success = 0;
		
		// Convert ids in array
		$ids = is_array($ids)? $ids : array($ids);
		
		// Ids loop
		foreach ($ids as $id) {
			
			try {
				
				// Try get receiver phone number, but without throwing exception
				$number = $this->checkRecvAuth($id);
				
			} catch (Exception $e) { $number = false; }
			
			// Is a valid phone number?
			if ($number) {
				try {
					
					// Send message
					$this->_send($this->_sendAuth, $number, $message);
					
					// If nothing throw count one success
					$cnt_success = $cnt_success + 1;
					
				} catch (Exception $e) {
					
					$this->runException('WS_SMS_ERROR', $e->getMessage());
				}
			}
		}
		return $cnt_success;
	}
	
	/**
	 * Send message to webservice
	 * 
	 * @param string $number
	 * @param string $message
	 * @return $request
	 * @throws SoapClient Exceptions
	 * @throws $this->runException('SMS_SEND_DENIED');
	 */
	private function _send( $sendAuth, $number, $message )
	{
		$number = $this->_options['country_code'].(string)$number;
		$message = (string)$message;
		
		// Check send authority
		if (!(isset($sendAuth['user']) && isset($sendAuth['passwd']))) $this->runException('SMS_SEND_DENIED');
		
		// Get instance of SoapClient
		if ( is_null($this->_soap) ) $this->_soap = new SoapClient($this->_options['wsdl'],array(
			'encoding' => 'UTF-8',
			'trace'    => true,
			'stream_context' => stream_context_create( array( 'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			) ) ),
		));
		
		try {
			// Encode to UTF-8
			if ( !mb_detect_encoding( $message, 'UTF-8', true ) ) $message = utf8_encode( $message );
			
			// Send to webservice
			$response = $this->_soap->enviarMensagem($sendAuth['user'], $sendAuth['passwd'], $number, $message, null, 0, 23, null);
			
			// Success register
			$this->log($sendAuth, $number, $message, $response->id, $response->resultado, null);
			
		} catch (Exception $e) {
			
			// Fail register and rethrow exception
			$this->log($sendAuth, $number, $message, null, ($e->faultcode? $e->faultcode : $e->getCode()), $e->getMessage());
			throw $e;
		}
		
		return $this;
	}
	
	/**
	 * Creates a record of sms on log table
	 * 
	 * @param array $sendAuth
	 * @param string $number
	 * @param string $message
	 * @param string $id
	 * @param int $status
	 * @param string $status_msg
	 * @return SMSAdapter
	 */
	private function log( $sendAuth, $number, $message, $id, $status, $status_msg )
	{
		$data = array(
			'log_sms_acc'		=> $this->_account_id,
			'log_sms_cred'		=> $sendAuth['user'],
			'log_sms_number'	=> $number,
			'log_sms_text'		=> $message,
			'log_sms_id'		=> $id,
			'log_sms_cod'		=> $status,
			'log_sms_msg'		=> $status_msg,
		);
		$data = array_filter($data, create_function('$a','return !is_null($a);'));
		
		$cols = implode(',',array_keys($data));
		$sql = 'INSERT INTO phpgw_log_sms ('.$cols.') VALUES('.preg_replace('/[^,]+/', '?', $cols).')';
		
		if (!$GLOBALS['phpgw']->db->Link_ID->query($sql,$data)) {
			
			openlog(get_class(), LOG_PID, LOG_LOCAL0);
			syslog(LOG_NOTICE,print_r($data,true));
			
		}
		
		return $this;
	}
	private function _char_filter( $string )
	{
		return str_replace(
			array('á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç'),
			array('a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C'),
			$string
		);
	}
	
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	
	public function SendCheckCode()
	{
		$this->_setUser();
		try {
			if (!is_null($this->_error)) throw new Exception();
			
			// Send cheking code and store security preferences
			$this->sendCheckCodeToPhoneNumber( $this->getParam('phoneNumber') );
			
			// Save preferences before returning the resource
			$this->savePreferences();
			
			$this->setResult( true );
			
		} catch (Exception $e) {}
		
		$this->getResponse();
	}
	
	public function SubmitPersonalForm()
	{
		$this->_setUser();
		try {
			if (!is_null($this->_error)) throw new Exception();
			
			// Filter numbers only
			$phoneNumber = preg_replace('/[^\d]/', '', $this->getParam('phoneNumber'));
			
			// Always first validity check code in some failure cases, save all preferences changed
			if (!empty($phoneNumber)) $this->validateCheckCodeToPhoneNumber($this->getParam('checkCode'), $phoneNumber);
			
			// Set user preference authorization SMS
			$this->setAuth( $this->getParam('SMSAuth') );
			
			// Save preferences before returning the resource
			$this->savePreferences();
			
			$this->setResult( true );
			
		} catch (Exception $e) {}
		
		$this->getResponse();
	}
	
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	//---------------------------------------------------------------------------------------------------------------------------------------------------------
	
	private function runException( $error_idx = 'E_UNKNOWN_ERROR' )
	{
		$this->_error = isset($this->_error_messages[$error_idx])? $this->_error_messages[$error_idx] : $this->_error_messages['E_UNKNOWN_ERROR'];
		$args = func_get_args();
		array_shift($args);
		$this->_error['message'] = utf8_encode( lang( $this->_error['message'], $args ) );
		throw new Exception( $this->_error['message'], $this->_error['code'] );
	}
	
	private function getParam( $name )
	{
		$value = isset($_POST['params'][$name])? $_POST['params'][$name] : ( isset($_GET['params'][$name])? $_GET['params'][$name] : null );
		return is_string($value)? mb_convert_encoding($value, "ISO_8859-1", "UTF8") : $value;
	}
	
	private function setResult( $result )
	{
		$this->_result = $result;
	}
	
	private function getResponse()
	{
		header('Content-Type: application/json');
		echo json_encode( is_null($this->_error)? array('result' => $this->_result) : array('error' => $this->_error) );
		exit;
	}
}
