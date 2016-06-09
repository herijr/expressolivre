<?php
/**********************************************************************************\
* Expresso Administração                                                            *
* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)   *
* ----------------------------------------------------------------------------------*
*  This program is free software; you can redistribute it and/or modify it          *
*  under the terms of the GNU General Public License as published by the            *
*  Free Software Foundation; either version 2 of the License, or (at your           *
*  option) any later version.                                                       *
\***********************************************************************************/

class sosectors
{
	var $functions;
	var $ldap_connection;
	var $db_functions;
	var $sectors;
	
	public function sosectors()
	{
		$this->functions    = CreateObject( 'expressoAdmin1_2.functions' );
		$this->db_functions = CreateObject( 'expressoAdmin1_2.db_functions' );
		$this->sectors      = CreateObject( 'phpgwapi.sector_search_ldap' );
		
		if (
			( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_host'] ) ) &&
			( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_root_dn'] ) ) &&
			( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_root_pw'] ) )
		) {
			$this->ldap_connection = $GLOBALS['phpgw']->common->ldapConnect(
				$GLOBALS['phpgw_info']['server']['ldap_master_host'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
				$GLOBALS['phpgw_info']['server']['ldap_master_root_pw']
			);
			
		} else $this->ldap_connection = $GLOBALS['phpgw']->common->ldapConnect();
	}
	
	public function exist_context( $context )
	{
		$result = $this->get_context( $context );
		return isset( $result['count'] ) && $result['count'] > 0;
	}
	
	public function get_context( $context )
	{
		$search = ldap_read( $this->ldap_connection, $context, '(objectclass=*)' );
		return ldap_get_entries( $this->ldap_connection, $search );
	}
	
	public function exist_sector_name( $sector_name, $context )
	{
		$search = ldap_list( $this->ldap_connection, $context, 'ou='.$sector_name );
		$result = ldap_get_entries( $this->ldap_connection, $search );
		return isset( $result['count'] ) && $result['count'] > 0;
	}
	
	public function write_ldap( $dn, $info )
	{
		return ldap_add( $this->ldap_connection, $dn, $info );
	}
	
	public function get_entries_list( $context )
	{
		$result    = array();
		$justthese = array( 'ou', 'cn', 'uid', 'objectclass', 'uidnumber', 'gidnumber', 'sambasid', 'phpgwaccounttype', 'sambadomainname' );
		$search    = ldap_search( $this->ldap_connection, $context, '(!(phpgwaccounttype=u))', $justthese );
		if ( ldap_errno( $this->ldap_connection ) !== 0 )
			$result['error'][] = lang('Fetch entries').': '.ldap_error( $this->ldap_connection );
		
		if ( $search ) {
			$entries   = ldap_get_entries( $this->ldap_connection, $search );
			ldap_free_result( $search );
			if ( isset( $entries['count'] ) ) unset( $entries['count'] );
			foreach ( $entries as $entry ) {
				$type = $this->_get_entry_type( $entry );
				$result[$type][] = $this->_get_entry_vars( $entry, $type );
				
			}
		}
		
		$justthese = array( 'cn', 'uid', 'uidnumber' );
		$search    = ldap_search( $this->ldap_connection, $context, '(phpgwaccounttype=u)', $justthese, null, 100 );
		$limit     = ( ldap_errno( $this->ldap_connection ) === 4 );
		
		if ( ( !$limit ) && ldap_errno( $this->ldap_connection ) !== 0 )
			$result['error'][] = lang('Fetch entries').': '.ldap_error( $this->ldap_connection );
		
		$entries   = array();
		if ( $search ) {
			$entries   = ldap_get_entries( $this->ldap_connection, $search );
			ldap_free_result( $search );
		} else $result['error'][] = lang('Fetch users').': '.ldap_error( $this->ldap_connection );
		
		$type = 'users';
		if ( isset( $entries['count'] ) ) unset( $entries['count'] );
		foreach ( $entries as $entry ) {
			$result[$type][] = $this->_get_entry_vars( $entry, $type );
		}
		$brief = array();
		foreach ( $result as $type => $entries ) {
			if ( $type == 'users' && $limit ) {
				$search = ldap_search( $this->ldap_connection, $context, '(phpgwaccounttype=u)', array( 'dn' ) );
				if ( $search ) {
					$brief[$type] = ldap_count_entries( $this->ldap_connection, $search );
					ldap_free_result( $search );
				} else $brief[$type] = '?';
			} else $brief[$type] = count( $entries );
		}
		$result['brief'] = $brief;
		return $result;
	}
	
	public function delete_sector_ldap_recursively( $dn )
	{
		// Searching for sub entries
		$search = ldap_list( $this->ldap_connection, $dn, 'ObjectClass=organizationalUnit', array('') );
		$info   = ldap_get_entries( $this->ldap_connection, $search );
		
		for ( $i = 0; $i < $info['count']; $i++ ) {
			
			// Deleting recursively sub entries
			if ( !$this->delete_sector_ldap_recursively( $info[$i]['dn'] ) ) return false;
			
			// Write log
			$this->db_functions->write_log( 'Sub organizational unit deleted', $info[$i]['dn'] );
		}
		
		// Remove Ldap entry
		if ( !ldap_delete( $this->ldap_connection, $dn ) ) return false;

		// Remove DB context of managers
		if ( !$this->db_functions->delete_context_managers( $dn ) ) return false;
		
		return true;
	}
	
	public function add_attribute( $dn, $info )
	{
		if ( ldap_mod_add( $this->ldap_connection, $dn, $info ) ) {
			
			ldap_close( $this->ldap_connection );
			return true;
			
		} else {
			
			echo lang( 'Error written in LDAP, function add_attribute' ).ldap_error( $this->ldap_connection );
			ldap_close( $this->ldap_connection );
			return false;
		}
	}
	
	public function remove_attribute( $dn, $info )
	{
		if ( ldap_mod_del( $this->ldap_connection, $dn, $info ) ) {
			
			ldap_close( $this->ldap_connection );
			return true;
			
		} else {
			
			echo lang( 'Error written in LDAP, function remove_attribute' ).ldap_error( $this->ldap_connection );
			ldap_close( $this->ldap_connection );
			return false;
		}
	}
	
	public function create_list( $type, $entries )
	{
		$result = array( 'count' => count( $entries ), 'list' => '' );
		switch ( $type ) {
			case 'smbdomains': $att = 'sambadomainname'; break;
			case 'others': $att = 'dn'; break;
			default: $att = 'cn';
		}
		foreach ( $entries as $entry ) $result['list'] .= $entry[$att].'<br>';
		return $result;
	}
	
	public function check_context( $lid, $context, $exclusive = false )
	{
		$acl = $this->functions->read_acl( $lid );
		if ( $exclusive ) {
			$context = explode( ',', $context );
			array_shift( $context );
			$context = implode( ',', $context );
		}
		foreach ( $acl['contexts'] as $allow )
			if ( preg_match( '/'.preg_quote( $allow, '/' ).'$/', $context ) )
				return true;
		return false;
	}
	
	public function linearizeNodes( $node, $context, $pattern = '', $parentIsLast = false, $lvl = 0, $type = 'line', $long = true )
	{
		switch( $type ) {
			case 'line':  $c = array( '|' => '&#9474;', '+' => '&#9500;', '`' => '&#9584;', '-' => '&#9472;', '>' => '&#9655;' ); break;
			case 'wline': $c = array( '|' => '&#9474;', '+' => '&#9500;', '`' => '&#9492;', '-' => '&#9472;', '>' => '&#62;'   ); break;
			case 'lines': $c = array( '|' => '&#9553;', '+' => '&#9568;', '`' => '&#9562;', '-' => '&#9552;', '>' => '&#9673;' ); break;
			default:      $c = array( '|' => '|', '+' => '|', '`' => '`', '-' => '-', '>' => '>' );
		}
		$c['M'] = $long? $c['-'] : '';
		$c['m'] = $long? '&nbsp;' : '';
		$result = array( array(
			'name' => $node['name'],
			'context' => $context,
			'tree' => '<span class="tree-dir">'.( $lvl > 0? $pattern.($parentIsLast?$c['`']:$c['+']).$c['M'].$c['>'].'&nbsp;' : '').'</span>',
		) );
		if ( isset( $node['childs'] ) && count( $node['childs'] ) ) {
			if ( $lvl > 0 )
				$pattern = $pattern.( $parentIsLast?
					'&nbsp;'.$c['m'].'&nbsp;&nbsp;' :
					$c['|'].$c['m'].'&nbsp;&nbsp;' );
			$lvl++;
			foreach ( $node['childs'] as $key => $sub ) {
				$result = array_merge( $result, $this->linearizeNodes(
					$node['childs'][$key],
					$key.','.$context,
					$pattern,
					key( array_slice( $node['childs'], -1, 1, true ) ) == $key,
					$lvl, $type, $long
				) );
			}
		}
		
		return $result;
	}
	
	public function getContextTree( $sectors_info )
	{
		$tree = array();
		foreach ( $sectors_info as $key => $value) {
			$this->_insertNode( $tree, explode( ',', $key ), preg_replace( '/^[+-]+ /', '', $value ) );
		}
		return $tree;
	}
	
	public function findNode( &$node, $parts )
	{
		$parts = is_string( $parts )? explode( ',', $parts ) : $parts;
		if ( !count( $parts ) ) return false;
		$part = array_pop( $parts );
		if ( !isset( $node['childs'][$part] ) ) return false;
		if ( count( $parts ) ) return $this->findNode( $node['childs'][$part], $parts );
		return $node['childs'][$part];
	}
	
	public function row_action( $action, $type, $context, $title = '' )
	{
		return '<a title="'.$title.'" href="'.$GLOBALS['phpgw']->link('/index.php',Array(
			'menuaction' => 'expressoAdmin1_2.uisectors.'.$action.'_'.$type,
			'context'    => $context
		)).'"> '.lang($action).' </a>';
	}
	
	public function get_controller( $type )
	{
		switch ( $type ) {
			case 'users':      return array( 'name' => 'user' );
			case 'insts':      return array( 'name' => 'ldap_functions', 'delete' => 'delete_institutional_account_data' );
			case 'groups':     return array( 'name' => 'group' );
			case 'lists':      return array( 'name' => 'maillist' );
			case 'computers':  return array( 'name' => 'bocomputers' );
			case 'smbdomains': return array( 'name' => 'uidomains', 'delete' => 'delete_domain' );
		}
		return false;
	}
	
	public function get_tree_list( $context )
	{
		$userAgent = ( isset($_SERVER['HTTP_USER_AGENT']) )? $_SERVER['HTTP_USER_AGENT'] : "";
		$isIE = ( preg_match('/MSIE/i', $userAgent ) ? true : false );
		$type = ( ($isIE ) ? 'wline' : 'line' );
		$tree = $this->linearizeNodes(
			$this->findNode(
				$this->getContextTree(
					$this->functions->get_sectors_list( array( $context ) )
				),
				$context
			), $context, null, null, null, $type
		);
		$result = array( 'count' => count( $tree ), 'list' => '' );
		foreach ( $tree as $entry ) $result['list'] .= $entry['tree'].$entry['name'].'<br>';
		return $result;
	}
	
	private function _insertNode( &$node, $parts, $name )
	{
		if ( !( is_array( $parts ) && count( $parts ) ) ) return array( 'name' => $name );
		$part = array_pop( $parts );
		if ( !isset( $node['childs'][$part] ) ) $node['childs'][$part] = array();
		$node['childs'][$part] = $this->_insertNode( $node['childs'][$part], $parts, $name );
		return $node;
	}
	
	private function _get_entry_type( $entry )
	{
		if ( isset( $entry['phpgwaccounttype'] ) ) {
			if ( $entry['phpgwaccounttype'][0] == 'u' ) return 'users';
			if ( $entry['phpgwaccounttype'][0] == 'g' ) return 'groups';
			if ( $entry['phpgwaccounttype'][0] == 'l' ) return 'lists';
			if ( $entry['phpgwaccounttype'][0] == 'i' ) return 'insts';
		}
		if ( in_array( 'organizationalUnit', $entry['objectclass'] ) ) return 'ous';
		if ( in_array( 'sambaDomain',        $entry['objectclass'] ) ) return 'smbdomains';
		if ( in_array( 'sambaSamAccount',    $entry['objectclass'] ) ) return 'computers';
		return 'others';
	}
	
	private function _get_entry_vars( $entry, $type )
	{
		switch ( $type ) {
			case 'users':      return array( 'cn' => $entry['cn'][0], 'uid' => $entry['uid'][0], 'uidnumber' => $entry['uidnumber'][0] );
			case 'insts':      return array( 'cn' => $entry['cn'][0], 'uid' => $entry['uid'][0] );
			case 'groups':     return array( 'cn' => $entry['cn'][0], 'gidnumber' => $entry['gidnumber'][0] );
			case 'lists':      return array( 'cn' => $entry['cn'][0], 'uidnumber' => $entry['uidnumber'][0] );
			case 'smbdomains': return array( 'sambadomainname' => $entry['sambadomainname'][0], 'sambasid' => $entry['sambasid'][0] );
			case 'computers':  return array( 'cn' => $entry['cn'][0], 'dn' => $entry['dn'] );
			case 'ous':        return array( 'ou' => $entry['ou'][0], 'dn' => $entry['dn'] );
			default: return $entry;
		}
		return false;
	}
}
