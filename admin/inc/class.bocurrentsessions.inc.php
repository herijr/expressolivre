<?php
/**************************************************************************\
* eGroupWare - Administration                                              *
* http://www.egroupware.org                                                *
*  This file written by Joseph Engo <jengo@phpgroupware.org>               *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

class bocurrentsessions
{
	var $db;
	var $public_functions = array();
	
	public function bocurrentsessions()
	{
		$this->db = $GLOBALS['phpgw']->db;
	}
	
	public function get_total_itens( $filter = false )
	{
		$this->db->query( '
			SELECT count(*) AS num_users, sum( count ) AS num_sessions
			FROM (
				SELECT loginid, count(loginid) AS count
				FROM phpgw_access_log
				WHERE lo = 0
				'.( $filter? ' AND loginid LIKE \'%'.pg_escape_string( $filter ).'%\'': '' ).'
				GROUP BY loginid
				ORDER BY loginid
			) AS c'
		);
		return $this->db->next_record()? $this->db->row() : array( 'num_users' => 0 , 'num_sessions' => 0 );
	}
	
	public function list_itens( $filter, $limit, $offset, $order_by_sessions = false )
	{
		$this->db->query( '
			SELECT loginid, count(loginid) AS count
			FROM phpgw_access_log
			WHERE lo = 0
			'.( $filter? ' AND loginid LIKE \'%'.pg_escape_string( $filter ).'%\'': '' ).'
			GROUP BY loginid
			ORDER BY '.( $order_by_sessions? 'count DESC, ' : '' ).'loginid
			LIMIT '.(int)$limit.'
			OFFSET '.(int)$offset
		);
		$records = array();
		while ( $this->db->next_record() ) $records[] = array( 'username' => $this->db->f( 'loginid' ), 'count' => $this->db->f( 'count' ) );
		return $records;
	}
	
	public function get_itens( $filter, $limit = 100, $except_current = false )
	{
		$this->db->query( '
			SELECT loginid AS id, trim(sessionid) AS sid
			FROM phpgw_access_log
			WHERE lo = 0 '.
				( $filter? ' AND loginid LIKE \'%'.pg_escape_string( $filter ).'%\' ': '' ).
				( $except_current? ' AND trim(sessionid) != \''.pg_escape_string( $GLOBALS['phpgw_info']['user']['sessionid'] ).'\' ': '' ).
			'LIMIT '.(int)$limit
		);
		$records = array();
		while ( $this->db->next_record() ) $records[] = $this->db->row();
		return $records;
	}
	
	public function get_itens_by_id( $id )
	{
		$this->db->query( '
			SELECT trim(sessionid) AS session, ip, li AS login, account_id AS uidnumber
			FROM phpgw_access_log
			WHERE lo = 0
			AND loginid = \''.pg_escape_string( $id ).'\' ORDER BY li'
		);
		$records = array();
		while ( $this->db->next_record() ) $records[] = $this->db->row();
		return $records;
	}
	
	public function find_sid( $id, $sid )
	{
		$this->db->query( '
			SELECT trim(sessionid) AS session
			FROM phpgw_access_log
			WHERE lo = 0
				AND loginid = \''.pg_escape_string( $id ).'\'
				AND sessionid LIKE \''.pg_escape_string( $sid ).'%\''
		);
		return ( $this->db->next_record() )? $this->db->f( 'session' ) : false;
	}
	
	public function get_sessions( $id )
	{
		$can_kill        = !$GLOBALS['phpgw']->acl->check( 'current_sessions_access', 8, 'admin' );
		$can_view_ip     = !$GLOBALS['phpgw']->acl->check( 'current_sessions_access', 4, 'admin' );
		$can_view_action = !$GLOBALS['phpgw']->acl->check( 'current_sessions_access', 2, 'admin' );
		
		$id = trim( $id );
		
		$records = $this->get_itens_by_id( $id );
		if ( !count( $records ) ) return array( 'error' => lang( 'sessions not found' ) );
		
		foreach ( $records as $key => $rec ) {
			$data = $GLOBALS['phpgw']->session->get_session_info( $rec['session'] );
			if (
				is_array( $data ) &&
				trim( $rec['session'] ) === trim( $data['session_id'] ) &&
				$id                     === $data['session_lid'] &&
				$rec['ip']              === $data['session_ip'] &&
				( abs( $rec['login'] - $data['session_logintime'] ) < 10 )
			) {
				$records[$key]['action' ] = $data['session_action'];
				$records[$key]['idle'   ] = $data['session_dla'];
				$records[$key]['isvalid'] = true;
			} else $records[$key]['isvalid'] = false;
		}
		
		$ldap = CreateObject( 'admin.ldap_functions' );
		if ( !$ldap->check_user( $id, $records[0]['uidnumber'] ) ) {
			$GLOBALS['phpgw']->session->logout_access( $id );
			foreach ( $records as $key => $rec )
				if ( $rec['isvalid'] )
					$GLOBALS['phpgw']->session->destroy( $rec['session'], null, $id );
			return array( 'error' => lang( 'user not found' ) );
		}
		
		foreach ( $records as $key => $rec ) {
			if ( !$can_view_ip ) unset( $records[$key]['ip'] );
			if ( !$can_view_action ) unset( $records[$key]['action'] );
			$records[$key]['kill']    = $can_kill;
			$records[$key]['session'] = substr( $rec['session'], 0, 8 );
			$records[$key]['login']   = date( 'd/m/Y H:i:s', $rec['login'] );
			if ( isset( $rec['idle'] ) ) {
				$diff = date_diff( date_create( '@'.$rec['idle'] ), date_create() );
				$records[$key]['idle'] = $diff->format( '%a %h:%i:%s' );
			}
			if ( !$rec['isvalid'] ) $GLOBALS['phpgw']->session->logout_access( $id, $rec['session'] );
		}
		
		return $records;
	}
	
	public function kill_session( $id, $sid )
	{
		$data = $GLOBALS['phpgw']->session->get_session_info( $sid );
		
		if ( !( is_array( $data ) && $sid === $data['session_id'] && $id === $data['session_lid'] ) ) {
			$GLOBALS['phpgw']->session->logout_access( $id, $sid );
			return false;
		}
		
		$GLOBALS['phpgw']->session->destroy( $sid, null, $id );
		
		return true;
	}
}
