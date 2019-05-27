<?php
	/***************************************************************************\
	* EGroupWare - EMailAdmin                                                   *
	* http://www.egroupware.org                                                 *
	* Written by : Lars Kneschke [lkneschke@egroupware.org]                     *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/

class so
{
	var $db;
	var $ldap;
	var $tables;
	var $current_account = false;

	public function __construct()
	{
		$this->db = $GLOBALS['phpgw']->db;
		$this->ldap = CreateObject('phpgwapi.common')->ldapConnect();

		include(PHPGW_INCLUDE_ROOT.'/emailadmin/setup/tables_current.inc.php');

		$this->tables = &$phpgw_baseline;

		unset($phpgw_baseline);

		$this->table = &$this->tables['phpgw_emailadmin'];
	}

	function addMBoxMigrate($mailBoxes)
	{
		$query = "INSERT INTO phpgw_emailadmin_mbox_migrate(uid, profileid_orig, profileid_dest, data) VALUES ";

		foreach( $mailBoxes as $value )
		{
			$query .= "('".$this->db->db_addslashes($value['uid'])."',";
			$query .= "'".$this->db->db_addslashes($value['profileid_orig'])."',";
			$query .= "'".$this->db->db_addslashes($value['profileid_dest'])."',";
			$query .= "'".$this->db->db_addslashes($value['data'])."'),";
		}

		$query = substr($query, 0, strlen($query) -1 ) . " RETURNING mboxmigrateid;";

		if (!$this->db->query($query)) return false;

		$async = CreateObject('phpgwapi.asyncservice');

		foreach ($this->db->Query_ID->GetArray() as $row) {
			$async->add(
				'mbox:'.$row['mboxmigrateid'],
				time()+10,
				'expressoAdmin1_2.user.mbox_migrate',
				array('id' => $row['mboxmigrateid']),
				false,
				20
			);
		}
		return true;
	}

	function deleteDomains( $profileID )
	{
		$query = 'DELETE FROM phpgw_emailadmin_domains WHERE profileid='.intval( $profileID ).';';

		$this->db->query($query,__LINE__ , __FILE__);
	}

	function deleteProfile( $profileID )
	{
		$query = 'DELETE FROM phpgw_emailadmin WHERE profileid='.intval( $profileID ).';';

		$this->db->query($query,__LINE__ , __FILE__);
	}

	function get_last_insert_id( $table, $field )
	{
		return $this->db->get_last_insert_id( $table, $field );
	}

	function colMap( $arr )
	{
		$mapped = array();
		foreach ($this->table['fd'] as $key => $value)
			if (isset($arr[strtolower($key)]))
				$mapped[$key] = $arr[strtolower($key)];
		return $mapped;
	}

	function getProfile( $mode, $value )
	{
		$query = 'SELECT phpgw_emailadmin.* FROM phpgw_emailadmin'.
			(($mode == 'mail')? ' INNER JOIN phpgw_emailadmin_domains ON (phpgw_emailadmin.profileid = phpgw_emailadmin_domains.profileid)' : '').
			' WHERE ('.(($mode == 'mail')? 'phpgw_emailadmin_domains.domain' : 'phpgw_emailadmin.profileid').' = \''.$value.'\' )';
		$this->db->query($query, __LINE__, __FILE__);
		return $this->db->next_record()? array_merge(
			$this->colMap( $this->db->row() ),
			array(
				'defaultUserQuota'     => $this->getDefaultUserQuota( ( $mode === 'mail' )? $value : '' ),
				'defaultUserSignature' => $this->getDefaultSignature( $value ),
			)
		) : false;
	}

	function getDefaultUserQuota( $domain = '' )
	{
		if ( !isset( $_SESSION['phpgw_info']['expresso']['expressoAdmin'] ) ) {
			$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c->read_repository();
			$_SESSION['phpgw_info']['expresso']['expressoAdmin'] = $c->config_data;
		}
		if ( ( $result = $this->getExtras( $domain, 'defaultUserQuota' ) ) !== false ) return $result;
		return isset( $_SESSION['phpgw_info']['expresso']['expressoAdmin']['expressoAdmin_defaultUserQuota'] )?
		(int)$_SESSION['phpgw_info']['expresso']['expressoAdmin']['expressoAdmin_defaultUserQuota'] : 20;
	}

	public function getDefaultSignature( $domain = '', $account = false, $type = 'orgchart', $extra = 'defaultUserSignature' )
	{
		$account = $account?: $this->getCurrentAccount();

		$signature = $this->getExtras( $domain, $extra );
		if ( !$signature ) return false;

		return ( $type == 'orgchart' )? $this->renderSignatureOrgChart( $signature, $account ) : (
			( $type == 'ldap' )? $this->renderSignatureLDAP( $signature, $account, $extra ) : false
		);
	}

	protected function renderSignatureLDAP( $signature, $account, $extra )
	{
		$filter = '(&'.
			'('.( ( $extra == 'defaultInstitucionalSignature' )? 'mail' : 'uidnumber' ).'='.$account.')'.
			'(phpgwAccountType='.( ( $extra == 'defaultInstitucionalSignature' )? 'i' : 'u' ).')'.
		')';

		preg_match_all( '/#([\w ]+)#/', $signature, $matchesA );
		preg_match_all( '/%([\w ]+)%/', $signature, $matchesB );
		$attrs = array_merge( $matchesA[1], $matchesB[1] );

		if ( !( is_array( $attrs ) && count( $attrs ) ) ) return $signature;

		$entry = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $attrs ) );
		$data  = array_reduce( $attrs, function( $carry, $item ) use ( $entry ) {
			$carry[strtolower( iconv( 'ISO-8859-1', 'ASCII//TRANSLIT', $item) )] = htmlentities( is_array( $entry[0][$item] )? $entry[0][$item][0] : $entry[0][$item] );
			return $carry;
		}, array() );

		return $this->drawSignature( $signature, $data );
	}

	protected function renderSignatureOrgChart( $signature, $account )
	{
		// DB Config
		$query = "SELECT * FROM phpgw_config where config_app = 'workflow'";

		$this->db->query( $query, __LINE__, __FILE__ );

		$dtSetWrk = array();

		while( $this->db->next_record() ){

			if( $this->db->f('config_name') === 'database_host' ){
				$dtSetWrk['host'] = $this->db->f('config_value');
			}

			if( $this->db->f('config_name') === 'database_port' ){
				$dtSetWrk['port'] = $this->db->f('config_value');
			}

            if( $this->db->f('config_name') === 'database_name' ){
                $dtSetWrk['dbname'] = $this->db->f('config_value');
			}

            if( $this->db->f('config_name') === 'database_admin_user' ){
                $dtSetWrk['username'] = $this->db->f('config_value');
			}

            if( $this->db->f('config_name') === 'database_admin_password' ){
                $dtSetWrk['password'] = $this->db->f('config_value');
			}
		}

		//DB WORKFLOW
		$dbWrkConn = pg_connect("host='".$dtSetWrk['host']."' port='".$dtSetWrk['port']."' dbname='".$dtSetWrk['dbname']."' user='".$dtSetWrk['username']."' password='".$dtSetWrk['password']."'");

		$query = 'SELECT func.funcionario_id, '.
			'area.sigla, area.descricao, func.funcao, '.
			'func.apelido, org.sitio FROM funcionario AS func '.
			'INNER JOIN area AS area ON func.area_id = area.area_id '.
			'INNER JOIN organizacao as org on func.organizacao_id = org.organizacao_id '.
			'WHERE func.funcionario_id =\''.$account.'\'';

		$result = pg_fetch_array( pg_query( $dbWrkConn, $query ), NULL, PGSQL_ASSOC );

		pg_close( $dbWrkConn );

		if( !is_array($result) ){ return false; }

		// LDAP
		$filter = '(&(uidnumber='.$account.')(phpgwAccountType=u))';
		$attrs = array('cn','mail','telephonenumber','mobile','homephone','employeenumber');
		$entry = ldap_get_entries( $this->ldap, ldap_search( $this->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $attrs ) );

		$data['info'] = array();

		$data['info'][] = array(
			'field' => 'mobilephone',
			'value' => ( isset($entry[0]['mobile'][0]) ? $entry[0]['mobile'][0] : '' )
		);

		$data['info'][] = array(
			'field' => 'homephone',
			'value' => ( isset($entry[0]['homephone'][0]) ? $entry[0]['homephone'][0] : '' )
		);

		$data['info'][] = array(
			'field' => 'fullname',
			'value' => ( isset($entry[0]['cn'][0]) ? $entry[0]['cn'][0] : '' )
		);

		$data['info'][] = array(
			'field' => 'nickname',
			'value' => ( !empty($result['apelido']) ? $result['apelido'] : $entry[0]['cn'][0] )
		);

		$data['info'][] = array(
			'field' => 'presentationname',
			'value' => ( !empty($result['apelido']) ? $result['apelido'] : $entry[0]['cn'][0] )
		);

		$data['info'][] = array(
			'field' => 'jobphone',
			'value' => ( isset( $entry[0]['telephonenumber'][0] ) ? $entry[0]['telephonenumber'][0] : '' )
		);

		$data['info'][] = array(
			'field' => 'area',
			'value' => ( !empty($result['sigla']) ? $result['sigla'] : '' )
		);

		$data['info'][] = array(
			'field' => 'areadescription',
			'value' => ( !empty($result['descricao']) ? $result['descricao'] : '' )
		);

		$data['info'][] = array(
			'field' => 'jobfunction',
			'value' => ( !empty($result['funcao']) ? " / ". $result['funcao'] : '' )
		);

		$data['info'][] = array(
			'field' => 'mail',
			'value' => ( isset($entry[0]['mail'][0]) ? $entry[0]['mail'][0] : '' )
		);

		$data['info'][] = array(
			'field' => 'site',
			'value' => ( !empty($result['sitio']) ? $result['sitio'] : '' )
		);

		$data = array_reduce( $data['info'], function( $carry, $item ) {
			$carry[strtolower( iconv( 'ISO-8859-1', 'ASCII//TRANSLIT', $item['field']) )] = htmlentities( is_array( $item['value'] )? $item['value'][0] : $item['value'] );
			return $carry;
		}, array() );

		return $this->drawSignature( $signature, $data );
	}

	protected function drawSignature( $signature, $data )
	{
		foreach ( $data as $key => $value ) {
			$signature = preg_replace( '/%'.preg_quote( $key ).'%/i', $value, $signature );
			$signature = preg_replace( '/#'.preg_quote( $key ).'#/i', $value, $signature );
		}
		return $signature;
	}

	protected function getCurrentAccount()
	{
		if ( $this->current_account !== false ) return $this->current_account;
		return $this->current_account= (
			isset( $GLOBALS['phpgw_info']['user']['account_id']                  )? $GLOBALS['phpgw_info']['user']['account_id'] :
			isset( $GLOBALS['phpgw']->accounts->data['account_id']               )? $GLOBALS['phpgw']->accounts->data['account_id'] :
			isset( $_SESSION['phpgw_info']['expresso']['user']['account_id']     )? $_SESSION['phpgw_info']['expresso']['user']['account_id'] :
			isset( $_SESSION['phpgw_info']['expressomail']['user']['account_id'] )? $_SESSION['phpgw_info']['expressomail']['user']['account_id'] :
			isset( $_SESSION['phpgw_session']['account_id']                      )? $_SESSION['phpgw_session']['account_id'] :
			false
		);
	}

	function getExtras( $domain, $key )
	{
		$domain = preg_replace( '/.*@/', '', trim( $domain ) );
		while ( !empty( $domain ) ) {
			$this->db->query(
				'SELECT extras '.
				'FROM phpgw_emailadmin_domains '.
				'WHERE domain = \''.$this->db->db_addslashes( $domain ).'\' AND extras like \'%"'.$this->db->db_addslashes( $key ).'"%\''
			);
			while ( $this->db->next_record() ) {
				$extras = unserialize( $this->db->f( 'extras' ) );
				if ( isset( $extras[$key] ) ) return $extras[$key];
			}
			$domain = ( strpos( $domain, '.' ) === false )? '' : trim( preg_replace( '/^[^.]*\./', '', $domain ) );
		}
		return false;
	}

    function getDomains( $domain )
    {
    	if( array_key_exists('operator', $domain) )
			$query = "SELECT * from phpgw_emailadmin_domains WHERE ".$domain['field']." ".$domain['operator']." '".$domain['value']."' ORDER BY domain";
    	else
    		$query = "SELECT * from phpgw_emailadmin_domains WHERE ".$domain['field']." = '".$domain['value']."' ORDER BY domain";

    	$this->db->query($query);

    	$return = array();

    	while( $this->db->next_record() )
    	{
    		$return[] = $this->db->row();
    	}

		return ( count($return ) ) ? $return : false;
    }

	function getProfileList( $limit = null )
	{
		$query 	= "SELECT * FROM phpgw_emailadmin ORDER BY description";
		$return = array();

		if( trim($limit) == null )
		{
			$this->db->query($query, __LINE__, __FILE__ );
		}
		else
		{
			$this->db->query($query, __LINE__, __FILE__, $limit , 10 );
		}

		while( $this->db->next_record() )
		{
			$return[] = $this->db->row();
		}

		return ( count($return ) ) ? $return : false;
	}

	function moveDomain($params)
	{
		$query = "UPDATE phpgw_emailadmin_domains SET profileid = '".$params['newprofileid']."' WHERE domainid = '".$params['domainid']."';";

		return ( $this->db->query($query) != null ) ? true : false ;
	}

	function saveDomains( $params )
	{
		foreach ( (array)$params['extras'] as $key => $value ) if ( is_string( $value ) ) $params['extras'][$key] = utf8_decode( $value );

		if( $params['action'] == 'edit' )
		{
			$query = 'UPDATE phpgw_emailadmin_domains SET'.
				' organization_units = '.( count( (array)$params['ous'] )? "'".$this->db->db_addslashes( serialize( (array)$params['ous'] ) )."'" : 'null' ).
				', extras = '.( count( (array)$params['extras'] )? "'".$this->db->db_addslashes( serialize( (array)$params['extras'] ) )."'" : 'null' ).
			' WHERE domainid='.((int)$params['domainid']);
		}

		if( $params['action'] == 'add' )
		{
			$query = 'INSERT INTO phpgw_emailadmin_domains( profileid, domain, organization_units ) VALUES('.
				(int)$params['profileid'].",".
				"'".$this->db->db_addslashes( $params['domain'] )."',".
				( count( (array)$params['ous'] )? "'".$this->db->db_addslashes( serialize( (array)$params['ous'] ) )."'" : 'null' )."',".
				( count( (array)$params['extras'] )? "'".$this->db->db_addslashes( serialize( (array)$params['extras'] ) )."'" : 'null' ).
				')';
		}

		if( $params['action'] == 'delete' )
		{
			$query = "DELETE FROM phpgw_emailadmin_domains WHERE domainid = '".$params['domainid']."';";
		}

		if( $this->db->query( $query,__LINE__,__FILE__ ) )
			return true;
		else
			return false;
	}

	function saveProfile( $serverConfig )
	{
		// Key to Lower Case;
		$tableCurrent = array();

		foreach( $this->table['fd'] as $key => $value )
		{
			$tableCurrent[strtolower($key)] = $value;
		}

		// Save values;
		foreach( $serverConfig as $key => $value )
		{
			if( $key == 'profileid' )
				continue;

			if( $fields != '' )
			{
				$fields .= ',';
				$values .= ',';
				$query  .= ',';
			}

			switch($tableCurrent[$key]['type'])
			{
				case 'int':
					$value = ( trim($value) != "") ? intval($value) : 0 ;
					break;

				case 'auto':
					$value = intval($value);
					break;

				default :
					$value = $this->db->db_addslashes($value);
					break;
			}

			$fields .= "$key";
			$values .= "'$value'";
			$query  .= "$key='$value'";
		}

		if( trim( $serverConfig['profileid'] ) != "" )
		{
			$query = "UPDATE phpgw_emailadmin SET $query WHERE profileid={$serverConfig['profileid']};";
		}
		else
		{
			$query = "INSERT INTO phpgw_emailadmin ($fields) VALUES ($values) RETURNING profileid";
		}

		return $this->db->query( $query, __LINE__ , __FILE__);
	}
}

?>
