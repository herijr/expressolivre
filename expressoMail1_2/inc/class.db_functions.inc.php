<?php

if ( !isset( $_SESSION['phpgw_info']['expressomail']['server']['db_name'] ) ) {
	include_once( dirname( __FILE__ ) . '/../../header.inc.php' );
	$_SESSION['phpgw_info']['expressomail']['server']['db_name'] = $GLOBALS['phpgw_info']['server']['db_name'];
	$_SESSION['phpgw_info']['expressomail']['server']['db_host'] = $GLOBALS['phpgw_info']['server']['db_host'];
	$_SESSION['phpgw_info']['expressomail']['server']['db_port'] = $GLOBALS['phpgw_info']['server']['db_port'];
	$_SESSION['phpgw_info']['expressomail']['server']['db_user'] = $GLOBALS['phpgw_info']['server']['db_user'];
	$_SESSION['phpgw_info']['expressomail']['server']['db_pass'] = $GLOBALS['phpgw_info']['server']['db_pass'];
	$_SESSION['phpgw_info']['expressomail']['server']['db_type'] = $GLOBALS['phpgw_info']['server']['db_type'];
} else {
	define( 'PHPGW_INCLUDE_ROOT', dirname( __FILE__ ) . '/../..');
	define( 'PHPGW_API_INC', PHPGW_INCLUDE_ROOT.'/phpgwapi/inc' );
	include_once( PHPGW_API_INC.'/class.db_egw.inc.php' );
}

include_once( 'class.dynamic_contacts.inc.php' );
	
class db_functions
{	
	
	var $db;
	var $user_id;
	var $related_ids; 
	
	function db_functions() {
		$this->db = new db_egw();		
		$this->db->Halt_On_Error = 'no';
		$this->db->connect(
				$_SESSION['phpgw_info']['expressomail']['server']['db_name'], 
				$_SESSION['phpgw_info']['expressomail']['server']['db_host'],
				$_SESSION['phpgw_info']['expressomail']['server']['db_port'],
				$_SESSION['phpgw_info']['expressomail']['server']['db_user'],
				$_SESSION['phpgw_info']['expressomail']['server']['db_pass'],
				$_SESSION['phpgw_info']['expressomail']['server']['db_type']
		);		
		$this->user_id = $_SESSION['phpgw_info']['expressomail']['user']['account_id'];	
	}

	// BEGIN of functions.
	function get_cc_contacts()
	{				
		$result = array();
		$stringDropDownContacts = '';		
		
		$query_related = $this->get_query_related('A.id_owner'); // field name for owner
			
		// Traz os contatos pessoais e compartilhados
		$query = 'select A.names_ordered, C.connection_value from phpgw_cc_contact A, '.
			'phpgw_cc_contact_conns B, phpgw_cc_connections C where '.
			'A.id_contact = B.id_contact and B.id_connection = C.id_connection '.
			'and B.id_typeof_contact_connection = 1 and ('.$query_related.') group by '. 
			'A.names_ordered,C.connection_value	order by lower(A.names_ordered)';
		
        if (!$this->db->query($query))
        	return null;
		while($this->db->next_record())
			$result[] = $this->db->row();

		if (count($result) != 0) 
		{
			// Monta string				
			foreach($result as $contact)
				$stringDropDownContacts = $stringDropDownContacts . urldecode(urldecode($contact['names_ordered'])). ';' . $contact['connection_value'] . ',';
			//Retira ultima virgula.
			$stringDropDownContacts = substr($stringDropDownContacts,0,(strlen($stringDropDownContacts) - 1));
		}
		else 
			return null;

		return $stringDropDownContacts;
	}
	// Get Related Ids for sharing contacts or groups.
	function get_query_related($field_name){		
		$query_related = $field_name .'='.$this -> user_id;
		// Only at first time, it gets all related ids...
		if(!$this->related_ids) {
			$query = 'select id_related from phpgw_cc_contact_rels where id_contact='.$this -> user_id.' and id_typeof_contact_relation=1';		
			if (!$this->db->query($query)){
    	    	return $query_related;
			}
			
			$result = array( );
			while($this->db->next_record()){
				$row = $this->db->row();
				$result[] = $row['id_related'];
			}
			if($result)
				$this->related_ids = implode(",",$result);
		}
		if($this->related_ids)
			$query_related .= ' or '.$field_name.' in ('.$this->related_ids.')';
		
		return $query_related;
	}
	function get_cc_groups() 
	{
		// Pesquisa no CC os Grupos Pessoais.
		$stringDropDownContacts = '';			
		$result = array();
		$query_related = $this->get_query_related('owner'); // field name for 'owner'		
		$query = 'select title, short_name, owner from phpgw_cc_groups where '.$query_related.' order by lower(title)';

		// Executa a query 
		if (!$this->db->query($query))
        	return null;
		// Retorna cada resultado            	
		while($this->db->next_record())
			$result[] = $this->db->row();

		// Se houver grupos ....				
		if (count($result) != 0) 
		{
			// Create Ldap Object, if exists related Ids for sharing groups.
			if($this->related_ids){
				$_SESSION['phpgw_info']['expressomail']['user']['cc_related_ids']= array();
				include_once("class.ldap_functions.inc.php");
				$ldap = new ldap_functions();
			}
			$owneruid = '';
			foreach($result as $group){
				// Searching uid (LDAP), if exists related Ids for sharing groups.
				// Save into user session. It will used before send mail (verify permission).
				if(!isset($_SESSION['phpgw_info']['expressomail']['user']['cc_related_ids'][$group['owner']]) && isset($ldap)){					
					$_SESSION['phpgw_info']['expressomail']['user']['cc_related_ids'][$group['owner']] = $ldap -> uidnumber2uid($group['owner']);
				}
				if($this->user_id != $group['owner'])
					$owneruid = "::".$_SESSION['phpgw_info']['expressomail']['user']['cc_related_ids'][$group['owner']];
				else
					$owneruid = '';

				$stringDropDownContacts .=  $group['title']. ';' . ($group['short_name'].$owneruid) . ',';
			}
			//Retira ultima virgula.
			$stringDropDownContacts = substr($stringDropDownContacts,0,(strlen($stringDropDownContacts) - 1));
		}
		else
			return null;		
		return $stringDropDownContacts;
	}
	
	function getContactsByGroupAlias($alias)
	{
		list($alias,$uid) = explode("::",$alias);		
		$cc_related_ids = $_SESSION['phpgw_info']['expressomail']['user']['cc_related_ids'];		
		// Explode personal group, If exists related ids (the user has permission to send email).
		if(is_array($cc_related_ids) && $uid){
			$owner =  array_search($uid,$cc_related_ids);			
		}
		
		$query = "select C.id_connection, A.names_ordered, C.connection_value from phpgw_cc_contact A, ".
		"phpgw_cc_contact_conns B, phpgw_cc_connections C,phpgw_cc_contact_grps D,phpgw_cc_groups E where ".
		"A.id_contact = B.id_contact and B.id_connection = C.id_connection ".
		"and B.id_typeof_contact_connection = 1 and ".
		"A.id_owner =".($owner ? $owner : $this->user_id)." and ".			
		"D.id_group = E.id_group and ".
		"D.id_connection = C.id_connection and E.short_name = '".$alias."'";

		if (!$this->db->query($query))
		{
			exit ('Query failed! File: '.__FILE__.' on line'.__LINE__);
		}

		$return = false;

		while($this->db->next_record())
		{
			$return[] = $this->db->row(); 
		}

		return $return;
	}

	function getAddrs($array_addrs) {
		$array_addrs_final = array();				

		for($i = 0; $i < count($array_addrs); $i++){
			$j = count($array_addrs_final);

			if(!strchr($array_addrs[$i],'@') 
					&& strchr($array_addrs[$i],'<')
					 && strchr($array_addrs[$i],'>')) {		

				$alias = substr($array_addrs[$i], strpos($array_addrs[$i],'<'), strpos($array_addrs[$i],'>'));				
		 		$alias = str_replace('<','', str_replace('>','',$alias));
		 		 		        	           		
		 		$arrayContacts = $this -> getContactsByGroupAlias($alias);

				if($arrayContacts) {
					foreach($arrayContacts as $index => $contact){
						if($contact['names_ordered']) {
							$array_addrs_final[$j] = '"'.$contact['names_ordered'].'" <'.$contact['connection_value'].'>';
						}
						else 
							$array_addrs_final[$j] = $contact['connection_value'];

						$j++;
					}
				}
	   		}
	   		else
	   			$array_addrs_final[$j++] = $array_addrs[$i]; 							
		}
		return $array_addrs_final;
	}

	// Migrate MailBox
	function getMigrateMailBox()
	{
		$uid = $_SESSION['phpgw_info']['expressomail']['user']['account_lid'];

		$query =
			'SELECT '.
				'def.uid, '.
				'def.profileid_orig, '.
				'def.profileid_dest, '.
				'def.data, '.
				'def.status, '.
				'( '.
					'SELECT count(cnt.mboxmigrateid) '.
					'FROM phpgw_emailadmin_mbox_migrate AS cnt '.
					'WHERE def.mboxmigrateid >= cnt.mboxmigrateid AND cnt.uid NOT IN ( '.
						'SELECT err.uid '.
						'FROM phpgw_emailadmin_mbox_migrate AS err '.
						'WHERE err.status = -1 '.
					') '.
				') AS queue, '.
				'array_to_string(array( '.
					'SELECT DISTINCT prev.status::text '.
					'FROM phpgw_emailadmin_mbox_migrate AS prev '.
					'WHERE def.uid = prev.uid AND def.mboxmigrateid > prev.mboxmigrateid '.
					'ORDER BY status '.
				'),\',\') AS previous_status '.
			'FROM phpgw_emailadmin_mbox_migrate AS def '.
			'WHERE uid = \''.$uid.'\' '.
			'ORDER BY def.mboxmigrateid DESC '.
			'LIMIT 1';
		
		$this->db->query($query, __LINE__, __FILE__);

		if( $this->db->next_record() )
		{
			$result 	= $this->db->row();
			$status 	= $result['status'];
			$prevStatus = explode( ",", $result['previous_status'] );

			if( in_array("-1", $prevStatus) )
			{
				$status = "-1";
			}
			else 
			{
				if( $result['status'] == "-1" ) $status = $result['status'];
			}

			return array( "uid" => $result['uid'], "status" => $status , "queue" => $result['queue'] );
		}
		else
			return false;
	}

	//Gera lista de contatos para ser gravado e acessado pelo expresso offline.
	function get_dropdown_contacts_to_cache() {
		return $this->get_dropdown_contacts();
	}
	
	function get_dropdown_contacts(){
		
		$contacts = $this -> get_cc_contacts();
		$groups = $this -> get_cc_groups();
		
		if(($contacts) && ($groups))
			$stringDropDownContacts = $contacts . ',' . $groups;
		elseif ((!$contacts) && (!$groups))
			$stringDropDownContacts = '';
		elseif (($contacts) && (!$groups))
			$stringDropDownContacts = $contacts;
		elseif ((!$contacts) && ($groups))
			$stringDropDownContacts = $groups;
					
		if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['number_of_contacts'] &&
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_dynamic_contacts']) {
			// Free others requests 
                        session_write_close(); 
			$dynamic_contact = new dynamic_contacts();
			$dynamic = $dynamic_contact->dynamic_contact_toString();
			if ($dynamic)
				$stringDropDownContacts .= ($stringDropDownContacts ? ',' : '') . $dynamic;
		}
		return $stringDropDownContacts;	
	}
	function getUserByEmail($params){	
		// Follow the referral
		$email = $params['email'];
		$query = 'select A.names_ordered, C.connection_name, C.connection_value, A.photo'. 
				' from phpgw_cc_contact A, phpgw_cc_contact_conns B, '.
				'phpgw_cc_connections C where A.id_contact = B.id_contact'. 
 				' and B.id_connection = C.id_connection and A.id_contact ='. 
				'(select A.id_contact from phpgw_cc_contact A, phpgw_cc_contact_conns B,'. 
				'phpgw_cc_connections C where A.id_contact = B.id_contact'. 
				' and B.id_connection = C.id_connection and A.id_owner = '.$this -> user_id.
				' and C.connection_value = \''.$email.'\') and '.
				'C.connection_is_default = true and B.id_typeof_contact_connection = 2';

        if (!$this->db->query($query))
        	return null;


		if($this->db->next_record()) {
			$result = $this->db->row();

			$obj =  array("cn" => $result['names_ordered'],
					  "email" => $email,
					  "type" => "personal",
					  "telefone" =>  $result['connection_value']);

			if($result['photo'])
				$_SESSION['phpgw_info']['expressomail']['contact_photo'] =  array($result['photo']);				

			return $obj;
		}
		return $result;
	}
	
	function get_dynamic_contacts()
	{				
		// Pesquisa os emails e ultima inserção nos contatos dinamicos.
 		if(!$this->db->select('phpgw_expressomail_contacts','data',
						  'id_owner ='.$this -> user_id,
						  __LINE__,__FILE__))
		{
        	return $this->db->Error;
}
		while($this->db->next_record())
		{
			$result[] = $this->db->row();
		}
		if($result) foreach($result as $item) 
		{
			$contacts = unserialize($item['data']);
		}
		if (count($contacts) == 0)
		{			
			return null;
		}	
		//Sort by email
		function cmp($a, $b) { return strcmp($a["email"], $b["email"]);} 
		usort($contacts,"cmp");
		return $contacts;
	}
	function update_contacts($contacts=array())
	{			
		// Atualiza um email nos contatos dinamicos.
		if(!$this->db->update('phpgw_expressomail_contacts ','data=\''.serialize($contacts).'\'',
			'id_owner ='.$this -> user_id,
			__LINE__,__FILE__))
		{
			return $this->db->Error;
		}
		return $contacts;
	}	
	function update_preferences($params){
		$string_serial = urldecode($params['prefe_string']);				

		// problema com as preferencias e o fora do escritorio
		//$string_serial = get_magic_quotes_gpc() ? $string_serial : addslashes($string_serial);

		$query = "update phpgw_preferences set preference_value = '".addslashes($string_serial)."' where preference_app = 'expressoMail'".
			" and preference_owner = '".$this->user_id."'";

		if (!$this->db->query($query))
			return $this->db->error;
		else
			return array("success" => true);
	}
	
	function insert_contact($contact)	
	{
		$contacts[] = array( 'timestamp' 	=> time(),
								'email'		=> $contact );

		// Insere um email nos contatos dinamicos.	
		$query = 'INSERT INTO phpgw_expressomail_contacts (data, id_owner)  ' .
					'values ( \''.serialize($contacts).'\', '.$this->user_id.')';
		
		if(!$this->db->query($query,__LINE__,__FILE__))
    	  	return $this->db->Error;
    	return $contacts;
	}
	
	function remove_dynamic_contact($user_id,$line,$file)
	{
		$where = $user_id.' = id_owner';
		$this->db->delete('phpgw_expressomail_contacts',$where,$line,$file);	
	}
	
	function import_vcard( $vcalendar, $msgNumber ){
		
		if( isset($_SESSION['phpgw_info']['expressomail']['user']['account_id']) ){
			$owner = $_SESSION['phpgw_info']['expressomail']['user']['account_id'];
		}

		if( isset($GLOBALS['phpgw_info']['user']['account_id']) ){
			$owner = $GLOBALS['phpgw_info']['user']['account_id'];
		}
		
		$hash = sha1($vcalendar).( $msgNumber ? sha1($msgNumber) : "" ).sha1($owner);

		$select = 'SELECT hash, owner 
								FROM phpgw_cal_invite 
									WHERE hash = \''.$hash.'\' AND owner = \''.$owner.'\';';

		$this->db->query( $select, __LINE__, __FILE__);

		if( !$this->db->next_record() )
		{
			$insert = 'INSERT INTO phpgw_cal_invite ( hash, contents, owner ) '.
									'values (\''.$hash.'\',\''.base64_encode($vcalendar).'\', \''.$owner.'\' );';
			
			return ( $this->db->query( $insert ,__LINE__,__FILE__) ? $hash : false );
		}

		return $hash;
	}

  function insert_certificate($email,$certificate,$serialnumber,$authoritykeyidentifier=null)
	{
		if(!$email || !$certificate || !$serialnumber || !$authoritykeyidentifier)
			return false;
		// Insere uma chave publica na tabela phpgw_certificados.
		$data = array	('email' => $email,
						 'chave_publica' => $certificate,
						 'serialnumber' => $serialnumber,
						 'authoritykeyidentifier' => $authoritykeyidentifier);

		if(!$this->db->insert('phpgw_certificados',$data,array(),__LINE__,__FILE__)){
          	return $this->db->Error;
        }
    	return true;
	}

	function get_certificate($email=null)
	{
		if(!$email) return false;
		$result = array();

		$where = array ('email' => $email,
						'revogado' => 0,
						'expirado' => 0);

 		if(!$this->db->select('phpgw_certificados','chave_publica', $where, __LINE__,__FILE__))
        {
            $result['dberr1'] = $this->db->Error;
            return $result;
        }
		$regs = array();
		while($this->db->next_record())
        {
            $regs[] = $this->db->row();
        }
		if (count($regs) == 0)
        {
            $result['dberr2'] = ' Certificado nao localizado.';
            return $result;
        }
		$result['certs'] = $regs;
		return $result;
	}

	function update_certificate($serialnumber=null,$email=null,$authoritykeyidentifier,$expirado,$revogado)
	{
		if(!$email || !$serialnumber) return false;
		if(!$expirado)
			$expirado = 0;
		if(!$revogado)
			$revogado = 0;

		$data = array	('expirado' => $expirado,
						 'revogado' => $revogado);

		$where = array	('email' => $email,
						 'serialnumber' => $serialnumber,
						 'authoritykeyidentifier' => $authoritykeyidentifier);

		if(!$this->db->update('phpgw_certificados',$data,$where,__LINE__,__FILE__))
		{
			return $this->db->Error;
		}
		return true;
	}

	function write_log( $action, $about, $manager = false )
	{
		$manager = $manager?: $_SESSION['phpgw_info']['expressomail']['user']['account_lid'];
		$sql = "INSERT INTO phpgw_expressoadmin_log (date, manager, action, userinfo) "
		. "VALUES('now','" . $manager . "','" . strtolower($action) . "','" . strtolower($about) . "')";
		
		if (!$this->db->query($sql)) {
			return false;
		}
		return true;
	}

}
?>
