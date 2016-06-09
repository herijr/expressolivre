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

class uicurrentsessions
{
	var $template;
	var $bo;
	var $public_functions = array(
		'list_sessions' => true,
		'get_sessions'  => true,
		'kill_all'      => true,
		'kill'          => true
	);
	
	public function uicurrentsessions()
	{
		if ( $GLOBALS['phpgw']->acl->check( 'current_sessions_access', 1, 'admin' ) )
			$GLOBALS['phpgw']->redirect_link( '/index.php' );
		$this->template = CreateObject( 'phpgwapi.Template', PHPGW_APP_TPL );
		$this->bo       = CreateObject( 'admin.bocurrentsessions' );
	}
	
	public function list_sessions()
	{
		$per_page_nums  = array( 20, 50, 100 );
		$page           = isset( $_GET['page'] )? (int)$_GET['page'] : 1;
		$filter         = ( isset( $_GET['filter'] ) && strlen( str_replace( ' ', '', $_GET['filter'] ) ) > 2 )? str_replace( ' ', '', $_GET['filter'] ) : false;
		$order          = ( isset( $_GET['order'] ) && $_GET['order'] == 'session' );
		$itens_per_page = ( isset( $_GET['per_page'] ) && in_array( (int)$_GET['per_page'], $per_page_nums ) )? (int)$_GET['per_page'] : 50;
		
		$can_kill = !$GLOBALS['phpgw']->acl->check( 'current_sessions_access', 8, 'admin' );
		
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang( 'Admin' ).' - '.lang( 'List of current users' );
		
		$js_submit = '$(event.currentTarget).parents(\'form:first\').submit();';
		
		$this->_header();
		
		$this->template->set_file( 'current', 'currentusers.tpl' );
		$this->template->set_block( 'current', 'list', 'list' );
		$this->template->set_block( 'current', 'row', 'row' );
		
		$this->template->set_var( 'lang_page',          lang( 'Page' ) );
		$this->template->set_var( 'lang_of',            lang( 'of' ) );
		$this->template->set_var( 'lang_filter',        lang( 'Filter' ) );
		$this->template->set_var( 'lang_displaying',    lang( 'Displaying' ) );
		$this->template->set_var( 'lang_view',          lang( 'View' ) );
		$this->template->set_var( 'lang_session',       lang( 'Session' ) );
		$this->template->set_var( 'lang_uidnumber',     lang( 'ID Number' ) );
		$this->template->set_var( 'lang_ip',            lang( 'IP' ) );
		$this->template->set_var( 'lang_login',         lang( 'Login Time' ) );
		$this->template->set_var( 'lang_idle',          lang( 'Idle Time' ) );
		$this->template->set_var( 'lang_action',        lang( 'Action' ) );
		$this->template->set_var( 'lang_delete',        lang( 'Terminate' ) );
		$this->template->set_var( 'lang_kill_all',      lang( 'Terminate All Sessions' ) );
		$this->template->set_var( 'lang_kill_all_msg',  lang( 'Please confirm that you want to terminate all sessions.' ) );
		$this->template->set_var( 'lang_cancel',        lang( 'Cancel' ) );
		$this->template->set_var( 'lang_ok',            lang( 'Continue' ) );
		$this->template->set_var( 'lang_refresh',       lang( 'Update' ) );
		$this->template->set_var( 'lang_refresh_msg',   lang( 'Some data has changed, we need to update. Please wait.' ) );
		$this->template->set_var( 'lang_per_page',      lang( 'Per page' ) );
		
		$this->template->set_var( 'link_username',      $order? '<a onclick="$(\'input[name=order]\').val(\'user\'); '.$js_submit.'">'.lang( 'Username' ).'</a>' : lang( 'Username' ) );
		$this->template->set_var( 'link_open_sessions', $order? lang( 'Open Sessions' ) : '<a onclick="$(\'input[name=order]\').val(\'session\'); '.$js_submit.'">'.lang( 'Open Sessions' ).'</a>' );
		
		$per_page_opts = '';
		foreach ( $per_page_nums as $num )
			$per_page_opts .= $num == $itens_per_page? ', '.$num : ', <a onclick="$(\'input[name=per_page]\').val('.$num.'); '.$js_submit.'">'.$num.'</a>';
		$this->template->set_var( 'per_page_opts', substr( $per_page_opts, 2 ) );
		
		$conts          = $this->bo->get_total_itens( $filter );
		$nun_pages      = (int)( ( $conts['num_users'] - 1 ) / $itens_per_page ) + 1;
		$page           = max( 1, min( $page, $nun_pages ) );
		$offset         = ( $itens_per_page * ( $page - 1 ) );
		$records        = $this->bo->list_itens( $filter, $itens_per_page, $offset, $order );
		
		foreach ( $records as $rec ) {
			$this->template->set_var( 'row_username', $rec['username'] );
			$this->template->set_var( 'row_count',    $rec['count'] );
			$this->template->set_var( 'row_view',     lang( 'View' ) );
			
			$this->template->parse('rows','row',True);
		}
		
		$this->template->set_var( 'cur_filter', isset( $_GET['filter'] )? $_GET['filter'] : '' );
		$this->template->set_var( 'cur_page',   $page );
		$this->template->set_var( 'last_page',  $nun_pages );
		$this->template->set_var( 'first_item', count( $records )? $offset + 1 : 0 );
		$this->template->set_var( 'last_item',  count( $records )? $offset + count( $records ) : 0 );
		$this->template->set_var( 'total_u',    $conts['num_users'] );
		$this->template->set_var( 'total_s',    $conts['num_sessions'] );
		$this->template->set_var( 'can_kill',   $can_kill? '' : 'hidden' );
		
		if ( $page == 1 ) {
			$this->template->set_var( 'first_disable'      , 'disabled' );
			$this->template->set_var( 'first_disable_icon' , '-grey' );
		}
		if ( $page == $nun_pages ) {
			$this->template->set_var( 'last_disable'      , 'disabled' );
			$this->template->set_var( 'last_disable_icon' , '-grey' );
		}
		
		$this->template->pfp('out','list');
	}
	
	public function get_sessions()
	{
		$id = isset( $_GET['id'] )? $_GET['id'] : '';
		if ( $id === '' ) $this->_setResponse( array( 'error' => lang( 'parameter not found' ) ), 404 );
		
		$records = $this->bo->get_sessions( $id );
		
		$this->_setResponse( $records, isset( $records['error'] )? 404 : null );
	}
	
	public function kill()
	{
		return $this->_kill_session(
			isset( $_GET['id'] )? $_GET['id'] : '',
			isset( $_GET['session'] )? $_GET['session'] : ''
		);
	}
	
	public function kill_all()
	{
		if ( $GLOBALS['phpgw']->acl->check( 'current_sessions_access', 8, 'admin' ) )
			$this->_setResponse( array( 'error' => lang( 'permission denied' ) ), 404 );
		
		$filter = ( isset( $_GET['filter'] ) && strlen( str_replace( ' ', '', $_GET['filter'] ) ) > 2 )? str_replace( ' ', '', $_GET['filter'] ) : false;
		
		$records = $this->bo->get_itens( $filter, 100, true );
		
		foreach ( $records as $key => $rec ) $this->_kill_session( $rec['id'], $rec['sid'], true );
		
		$this->_setResponse( array( 'status' => (int)count( $records ) ) );
	}
	
	private function _header()
	{
		if ( !( isset( $GLOBALS['phpgw']->js ) && is_object( $GLOBALS['phpgw']->js ) ) ) $GLOBALS['phpgw']->js = CreateObject( 'phpgwapi.javascript' );
		$GLOBALS['phpgw']->js->validate_file( 'jscode', 'openwindow', 'admin' );
		$GLOBALS['phpgw']->common->phpgw_header();
		echo parse_navbar();
	}
	
	private function _kill_session( $id, $sid_part, $batch = false )
	{
		if ( $GLOBALS['phpgw']->acl->check( 'current_sessions_access', 8, 'admin' ) )
			$this->_setResponse( array( 'error' => lang( 'permission denied' ) ), 404 );
		
		if ( $id === '' ) $this->_setResponse( array( 'error' => lang( 'parameter not found' ) ), 404 );
		if ( strlen( $sid_part ) < 8 ) $this->_setResponse( array( 'error' => lang( 'parameter not found' ) ), 404 );
		
		$sid = $this->bo->find_sid( $id, $sid_part );
		if ( $sid === false ) $this->_setResponse( array( 'error' => lang( 'sessions not found' ) ), 404 );
		
		if ( !$this->bo->kill_session( $id, $sid ) ) if ( !$batch ) $this->_setResponse( array( 'error' => lang( 'error log out' ) ), 404 );
		
		if ( !$batch ) $this->_setResponse( array( 'status' => true ) );
	}
	
	private function _setResponse( $data, $code = null )
	{
		if ( !is_null($code) ) header( ':', true, (int)$code );
		header( 'Content-Type: application/json' );
		echo json_encode( (array)$data );
		exit;
	}
}
