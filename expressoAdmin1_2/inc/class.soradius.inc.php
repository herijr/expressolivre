<?php
class soradius
{
	var $functions;
	var $ldap_connection;
	var $_schemas;
	var $_radius_schema;
	var $_radius_dn;
	var $_config;
	var $_tmp;
	var $_data = array();
	
	function soradius()
	{
		$this->_config = CreateObject('phpgwapi.config','expressoAdmin1_2');
		$this->_config->read_repository();
		
		$this->functions = CreateObject('expressoAdmin1_2.functions');
		if (
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) &&
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) &&
			(!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw']))
		) {
			$this->ldap_connection = $GLOBALS['phpgw']->common->ldapConnect(
				$GLOBALS['phpgw_info']['server']['ldap_master_host'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_pw']
			);
		} else $this->ldap_connection = $GLOBALS['phpgw']->common->ldapConnect();
		$this->_tmp = empty($GLOBALS['phpgw_info']['server']['temp_dir'])? '/tmp' : $GLOBALS['phpgw_info']['server']['temp_dir'];
		$this->_radius_dn = 'dc=radius,'.$GLOBALS['phpgw_info']['server']['ldap_context'];
	}
	
	private function _getShemaDn()
	{
		$entries = ldap_get_entries(
			$this->ldap_connection,
			ldap_read(
				$this->ldap_connection,
				'',
				'objectclass=*',
				array('subschemasubentry')
			)
		);
		return $entries[0]['subschemasubentry'][0];
	}
	
	private function _getObjects()
	{
		$entries = ldap_get_entries(
			$this->ldap_connection,
			ldap_read(
				$this->ldap_connection,
				$this->_getShemaDn(),
				'objectclass=subSchema',
				array('attributetypes', 'objectclasses','ldapsyntaxes')
			)
		);
		return $entries[0];
	}
	
	private function _parseSchema()
	{
		$entries = $this->_getObjects();
		$objs = new stdClass;
		$objs->objectclasses = $this->_parseObject( $entries['objectclasses'] );
		$objs->attributetypes = $this->_parseObject( $entries['attributetypes'] );
		$objs->ldapsyntaxes = $this->_parseObject( $entries['ldapsyntaxes'] );
		return $objs;
	}
	
	private function _parseObject( $entries )
	{
		unset($entries['count']);
		$objs = array();
		foreach ($entries as $schema) {
			$schema = substr(substr($schema,0,strlen($schema)-2),2);
			$schema = str_replace('\\\'', '@@qt@@',$schema);
			preg_match_all('/\'[^\']*\'/', $schema, $matches);
			$matches = $matches[0];
			foreach ($matches as $key => $match) {
				$schema = str_replace($match, '@@str@@', $schema);
				$matches[$key] = str_replace('@@qt@@', '\\\'', str_replace('\'', '', $match));
			}
			$schema = explode(' ', $schema);
			$numericoid = array_shift($schema);
			if ( !isset($objs[$numericoid]) ) $objs[$numericoid] = array();
			while ($token = array_shift($schema)) {
				switch ($token) {
					case 'NAME': case 'DESC': case 'SUP':
					case 'MUST': case 'MAY':
					case 'EQUALITY': case 'ORDERING': case 'SUBSTR': case 'SYNTAX': case 'USAGE': case 'X-ORDERED':
					case 'X-BINARY-TRANSFER-REQUIRED': case 'X-NOT-HUMAN-READABLE':
						if ( !count($schema) ) return null;
						$next = array_shift($schema);
						if ( $next === '@@str@@' ) $next = array_shift($matches);
						if ( $next === '(' ) {
							$cnj = array();
							while ($sub = array_shift($schema)) {
								if ( $sub === '@@str@@' ) $sub = array_shift($matches);
								if ( $sub === ')') break;
								if ( $sub !== '$') $cnj[] = $sub;
							}
							sort($cnj);
							$objs[$numericoid][strtolower($token)] = $cnj;
						} else $objs[$numericoid][strtolower($token)] = $next;
						break;
					case 'OBSOLETE':
					case 'ABSTRACT': case 'STRUCTURAL': case 'AUXILIARY':
					case 'SINGLE-VALUE': case 'COLLECTIVE': case 'NO-USER-MODIFICATION':
						$objs[$numericoid][strtolower($token)] = true;
						break;
					default:
				}
			}
		}
		return $objs;
	}
	
	private function _getSchema($objectClass)
	{
		if ( is_null($this->_schemas) ) $this->_schemas = $this->_parseSchema();
		foreach ( $this->_schemas->objectclasses as $schema )
			if ( in_array($objectClass, (array)$schema['name']) )
				return $schema;
		
		return false;
	}
	
	public function getRadiusSchema()
	{
		if ( is_null($this->_radius_schema) ) {
			$this->_radius_schema = $this->_getSchema('radiusprofile');
			$attrs = array_merge( (array)$this->_radius_schema['must'], (array)$this->_radius_schema['may'] );
			$this->_radius_schema['attr_dict'] = array();
			foreach ( $this->_schemas->attributetypes as $value ) {
				$name = $value['name'];
				if ( in_array( $name, $attrs ) ) {
					unset($value['name']);
					$value['syntax'] = $this->_schemas->ldapsyntaxes[$value['syntax']]['desc'];
					$this->_radius_schema['attr_dict'][$name] = $value;
				}
			}
		}
		return $this->_radius_schema;
	}
	
	private function _getEnabled()
	{
		return ( isset($this->_config->config_data['expressoAdmin_radius_support']) && $this->_config->config_data['expressoAdmin_radius_support'] === 'true' );
	}
	
	private function _setEnabled( $value )
	{
		$value = (bool)$value;
		if ( $this->_getEnabled() === $value ) return 'skipped';
		$this->_config->config_data['expressoAdmin_radius_support'] = ( $value? 'true' : 'false' );
		return 'changed';
	}
	
	private function _getGroupAttr()
	{
		return isset($this->_config->config_data['expressoAdmin_radius_attr'])? $this->_config->config_data['expressoAdmin_radius_attr'] : 'radiusGroupName';
	}
	
	private function _setGroupAttr( $value )
	{
		$value = $value;
		if ( $this->_getGroupAttr() === $value ) return 'skipped';
		$schema = $this->getRadiusSchema();
		if ( !in_array($value, $schema['may']) ) return lang('Invalid value').': "'.lang('Group Name Field').'"';
		if ( is_file($this->_tmp.'/radius_groupname_attribute.ldif')) return lang('Another operation is using this feature, try later');
		$this->_data['groupname_attribute'] = array( 'old' => $this->_getGroupAttr(), 'new' => $value );
		$this->_config->config_data['expressoAdmin_radius_attr'] = $value;
		return 'changed';
	}
	
	private function _createDC()
	{
		return ldap_add(
			$this->ldap_connection,
			$this->_radius_dn,
			array(
				'objectClass' => array(
					'organization',
					'dcObject',
					'top',
				),
				'o' => 'radius',
			)
		);
	}
	
	private function _getProfiles()
	{
		$entries = ldap_get_entries(
			$this->ldap_connection,
			@ldap_search(
				$this->ldap_connection,
				$this->_radius_dn,
				'(cn=*)'
			)
		);
		
		if ( is_null($entries) ) {
			if ( $this->_createDC() ) return $this->_getProfiles();
			else return false;
		}
		
		$result = array();
		$schema = $this->getRadiusSchema();
		for ( $i = 0; $i < $entries['count']; $i++ ) {
			$result[$entries[$i]['cn'][0]] = array();
			for ( $j = 0; $j < $entries[$i]['count']; $j++ ) {
				if ( !in_array( $entries[$i][$j], array('cn','objectclass') ) ) {
					$key = array_search(strtolower($entries[$i][$j]),array_map('strtolower',$schema['may']));
					unset( $entries[$i][$entries[$i][$j]]['count'] );
					$result[$entries[$i]['cn'][0]][($key===false)?$entries[$i][$j]:$schema['may'][$key]] = $entries[$i][$entries[$i][$j]];
				}
			}
		}
		ksort($result);
		return $result;
	}
	
	private function _addProfile( $cn, $data, $test = false )
	{
		if ( $test ) {
			if ( strlen($cn) > 32 || (!preg_match('/^[a-zA-Z]([0-9a-zA-Z\-_])*$/', $cn)) ) return lang('Invalid value').': "'.$cn.'"';
			return $this->_modProfile( $cn, array( 'old' => array(), 'new' => (array)$data), true );
		} else {
			foreach ( $data as $key => $value ) foreach ( $value as $i => $val ) $data[$key][$i] = trim($val);
			$data['objectClass'] = array( 'radiusObjectProfile', 'radiusprofile', 'top' );
			ldap_add( $this->ldap_connection, 'cn='.$cn.','.$this->_radius_dn, $data );
		}
		return 'changed';
	}
	
	private function _modProfile( $cn, $data, $test = false )
	{
		// Divides the keys for call type ldap: add, modify and remove.
		$rem = array();
		$mod = array();
		foreach ( $data['old'] as $key => $value ) {
			if ( isset($data['new'][$key]) ) {
				
				// Test whether there was any change in values
				if ( array_diff($data['new'][$key],$value) || array_diff($value,$data['new'][$key]) ) $mod[$key] = $data['new'][$key];
				unset($data['new'][$key]);
				
			} else $rem[$key] = array();
		}
		$add = (array)$data['new'];
		
		if ( $test ) {
			// Checks if you have no action, skip
			if ( (count($add) + count($mod) + count($rem)) < 1 ) return 'skipped';
			
			// Blocks the description field for remove
			if ( isset($rem['description']) ) return $cn.': '.lang('Description field required');
			
			// Load schema
			$schema = $this->getRadiusSchema();
			
			// Check the fields that will be added in ldap
			foreach ( array_merge( $add, $mod ) as $key => $value ) {
				
				// Check that the key belongs to the schema of radius
				if ( $key !== 'description' && ( !in_array($key, $schema['may']) ) ) return $cn.': '.lang('Invalid value').': "'.$key.'"';
				
				// Checks attribute schema
				if ( isset($schema['attr_dict'][$key]) ) {
					
					// Check if array is allow
					if ( count($value) > 1 && isset($schema['attr_dict'][$key]['single-value']) ) return $cn.': '.lang('Field is single value').': "'.$key.'"';
					
					// Check case ignore attribute
					if ( isset($schema['attr_dict'][$key]['equality']) && $schema['attr_dict'][$key]['equality'] == 'caseIgnoreIA5Match' ) {
						foreach ( $value as $i => $val ) $value[$i] = strtolower($val);
					}
				}
				
				// Checks for empty fields
				foreach ( $value as $val ) if ( strlen(trim($val)) < 1 ) return $cn.': '.$key.': '.lang('Empty value');
				
				// Check for repeated values.
				if ( count($value) != count(array_unique($value)) ) return $cn.': '.$key.': '.lang('Repeated values');
				
			}
		} else {
			$dn = 'cn='.$cn.','.$this->_radius_dn;
			foreach ( $add as $key => $value ) foreach ( $value as $i => $val ) $add[$key][$i] = trim($val);
			foreach ( $mod as $key => $value ) foreach ( $value as $i => $val ) $mod[$key][$i] = trim($val);
			if ( count($add) ) ldap_mod_add( $this->ldap_connection, $dn, $add );
			if ( count($rem) ) ldap_mod_del( $this->ldap_connection, $dn, $rem );
			if ( count($mod) ) ldap_mod_replace( $this->ldap_connection, $dn, $mod );
		}
		return 'changed';
	}
	
	private function _remProfile( $cn, $data, $test = false )
	{
		if ( $test ) {
			$entries = ldap_get_entries(
				$this->ldap_connection,
				@ldap_search( $this->ldap_connection, $GLOBALS['phpgw_info']['server']['ldap_context'], '('.$this->_getGroupAttr().'='.$cn.')', array('dn'), 0, 1 )
			);
			if ( $entries['count'] > 0 ) return $cn.': '.lang('Profile in use');
		} else {
			ldap_delete( $this->ldap_connection, 'cn='.$cn.','.$this->_radius_dn );
		}
		return 'changed';
	}
	
	public function getRadiusConf()
	{
		$conf = new stdClass();
		$conf->enabled = $this->_getEnabled();
		$conf->profileClass = 'radiusprofile';
		$conf->groupname_attribute = $this->_getGroupAttr();
		$conf->profiles = $this->_getProfiles();
		return $conf;
	}
	
	public function setRadiusConf( $params )
	{
		$error = array();
		$status = array();
		$ldap = array('rem' => array(), 'add' => array(), 'mod' => array() );
		$commit = false;
		
		if ( isset($params['radius_enabled']) ) $status['radius_enabled'] = $this->_setEnabled( $params['radius_enabled'] === '1' );
		if ( isset($params['groupname_attribute']) ) {
			$groupname_attribute = $this->_getGroupAttr();
			$status['groupname_attribute'] = $this->_setGroupAttr( $params['groupname_attribute'] );
		}
		
		foreach ( $this->_getProfiles() as $key => $value ) {
			if ( isset($params['profiles'][$key]) ) {
				$ldap['mod'][$key] = array( 'old' => (array)$value, 'new' => (array)$params['profiles'][$key] );
				$status['profile_'.$key] = $this->_modProfile( $key, $ldap['mod'][$key], true );
				if ( $status['profile_'.$key] === 'skipped' ) unset($ldap['mod'][$key]);
				unset($params['profiles'][$key]);
			} else {
				$ldap['rem'][$key] = array();
				$status['profile_'.$key] = $this->_remProfile( $key, $ldap['rem'][$key], true );
			}
		}
		foreach ( $params['profiles'] as $key => $value ) {
			$ldap['add'][$key] = (array)$value;
			$status['profile_'.$key] = $this->_addProfile( $key, $ldap['add'][$key], true );
		}
		
		foreach ( $status as $key => $value ) {
			switch ($value) {
				case 'skipped': break;
				case 'changed': $commit = true; break;
				default: $error[] = utf8_encode($value);
			}
		}
		if ( count($error) ) return $error;
		if ( $commit ) {
			
			if ( $status['groupname_attribute'] == 'changed' ) $this->_listLdapGroupAttr();
			
			foreach ( $ldap as $func => $value )
				foreach ( $value as $key => $data )
					$this->{'_'.$func.'Profile'}( $key, $data );
			
			$this->_config->save_repository();
		}
		return true;
	}
	
	public function getTopGroupsLdap()
	{
		$entries = ldap_get_entries(
			$this->ldap_connection,
			@ldap_list(
				$this->ldap_connection,
				$GLOBALS['phpgw_info']['server']['ldap_context'],
				'(phpgwAccountType=g)',
				array('cn','gidnumber')
			)
		);
		$result = array();
		for ($i=0; $i<$entries['count']; $i++) $result[$entries[$i]['gidnumber'][0]] = $entries[$i]['cn'][0];
		asort($result);
		return $result;
	}
	
	private function _listLdapGroupAttr()
	{
		$old = $this->_data['groupname_attribute']['old'];
		$new = $this->_data['groupname_attribute']['new'];
		$fname = $this->_tmp.'/radius_groupname_attribute.ldif';
		$srv = 
			'-H \''.$GLOBALS['phpgw_info']['server']['ldap_host'].'\' '.
			'-D \''.$GLOBALS['phpgw_info']['server']['ldap_root_dn'].'\' '.
			'-w \''.$GLOBALS['phpgw_info']['server']['ldap_root_pw'].'\' ';
		$cmd = 'touch '.$fname.' && '.
			'ldapsearch -LLLx -o nettimeout=none -s sub -a always '.$srv.' -b \''.$GLOBALS['phpgw_info']['server']['ldap_context'].'\' '.
			'\'(&('.$old.'=*)(!('.$old.'=\00)))\' \'dn\' \''.$old.'\' | '.
			'perl -p0e \'s/\n //g\' | '.
			'sed \'/^dn: /achangetype: modify\' | '.
			'sed \'s#^'.$old.': \(.*\)\$#add: '.$new.'\n'.$new.': \1\n-\ndelete: '.$old.'\n'.$old.': \1\n-#g\' > '.$fname.' && '.
			'ldapmodify -x -o nettimeout=none '.$srv.' -f '.$fname.' && '.
			'gzip '.$fname.' && '.
			'mv '.$fname.'.gz '.$fname.'.\$(date +%Y%m%d%H%M).complete.gz';
		exec('nohup sh -c "'.$cmd.'" > /dev/null 2>&1 &');
	}
}
?>
