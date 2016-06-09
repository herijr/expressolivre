<?php
/************************************************************************************\
* Expresso Administração                                                             *
* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)    *
* -----------------------------------------------------------------------------------*
*  This program is free software; you can redistribute it and/or modify it           *
*  under the terms of the GNU General Public License as published by the             *
*  Free Software Foundation; either version 2 of the License, or (at your            *
*  option) any later version.                                                        *
\************************************************************************************/

class bosectors
{
	var $so;
	var $functions;
	var $db_functions;
	
	function bosectors()
	{
		$this->so           = CreateObject( 'expressoAdmin1_2.sosectors' );
		$this->functions    = $this->so->functions;
		$this->db_functions = $this->so->db_functions;
	}
	
	function create_sector( $sector, $context, $hide = false )
	{
		// New dn
		$dn = 'ou='.$sector.','.$context;
		
		$sector_info = array(
			'ou' => $sector,
			'objectClass' => array( 'top', 'organizationalUnit' )
		);
		
		if ( $GLOBALS['phpgw_info']['server']['system_name'] != '' )
			$sector_info['phpgwSystem'] = strtolower( $GLOBALS['phpgw_info']['server']['system_name'] );
		
		if ( $hide ) {
			$sector_info['objectClass'][]       = 'phpgwAccount';
			$sector_info['phpgwaccountvisible'] = '-1';
		}
		
		// Save new sector in ldap 
		if ( !$this->so->write_ldap( $dn, $sector_info ) ) return false;
		
		// Write log on success
		$this->db_functions->write_log( 'created sector', $dn );
		
		return true;
	}
	
	function edit_sector( $context, $hide )
	{
		$info     = $this->so->get_context( $context );
		$has_sys  = ($GLOBALS['phpgw_info']['server']['system_name'] != '') && isset( $info[0]['phpgwSystem'] );
		$has_acc  = isset( $info[0]['objectclass'] ) && in_array( 'phpgwAccount', $info[0]['objectclass'] );
		$entry    = array();
		
		if ( $hide ) {
			
			if ( !$has_acc ) $entry['objectClass'][] = 'phpgwAccount';
			$entry['phpgwaccountvisible'] = '-1';
			if ( !$this->so->add_attribute( $info[0]['dn'], $entry ) ) return false;
			$this->db_functions->write_log( 'Added non-visible flag', $info[0]['dn'] );
			
		} else {
			
			$entry['phpgwaccountvisible'] = array();
			if ( $has_acc && !$has_sys ) $entry['objectClass'] = 'phpgwAccount';
			if ( !$this->so->remove_attribute( $info[0]['dn'], $entry ) ) return false;
			$this->db_functions->write_log( 'Removed non-visible flag', $info[0]['dn'] );
			
		}
		
		return true;
	}
	
	function delete_sector( $context, $list )
	{
		foreach ( array( 'users', 'insts', 'groups', 'lists', 'computers', 'smbdomains' ) as $type ) {
			
			if ( !isset( $list[$type] ) ) continue;
			
			if ( !( $ctl_params = $this->so->get_controller( $type ) ) )
				return array( 'status' => false, 'msg' => lang( 'Invalid controller name' ) );
			
			$controller = CreateObject( 'expressoAdmin1_2.'.$ctl_params['name'] );
			$func = isset( $ctl_params['delete'] )? $ctl_params['delete'] : 'delete';
			
			if ( is_null( $controller ) )
				return array( 'status' => false, 'msg' => lang( 'Invalid controller' ) );
			
			foreach ( $list[$type] as $params ) {
				
				$del_result = $controller->{$func}( $params );
				if ( $del_result['status'] === false ) return $del_result;
			}
			
			if ( $list['brief'][$type] > count( $list[$type] ) )
				return array( 'status' => false, 'partial' => true );
		}
		
		// Delete all organizational units tree
		if ( !$this->so->delete_sector_ldap_recursively( $context ) )
			return array( 'status' => false, 'msg' => lang( 'Error in OpenLDAP recording.' ) );
		
		// Write log on success
		$this->db_functions->write_log( 'Organizational unit deleted', $context );
		
		return array( 'status' => true );
	}
}
