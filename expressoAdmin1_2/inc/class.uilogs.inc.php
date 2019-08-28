<?php
/************************************************************************************\
* Expresso Administração                 										     *
* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	 *
* -----------------------------------------------------------------------------------*
*  This program is free software; you can redistribute it and/or modify it		 	 *
*  under the terms of the GNU General Public License as published by the			 *
*  Free Software Foundation; either version 2 of the License, or (at your			 *
*  option) any later version.														 *
\************************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

class uilogs
{
	var $public_functions = array(
		'list_logs' => true,
		'view_log'  => true
	);
	
	var $functions;
	
	function uilogs()
	{
		$this->functions = createobject( 'expressoAdmin1_2.functions' );
	}
	
	function list_logs()
	{
		if ( !$this->functions->check_acl( $GLOBALS['phpgw']->accounts->data['account_lid'], ACL_Managers::ACL_VW_LOGS ) )
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/expressoAdmin1_2/inc/access_denied.php' ) );
		
		unset( $GLOBALS['phpgw_info']['flags']['noheader'] );
		unset( $GLOBALS['phpgw_info']['flags']['nonavbar'] );
		
		if ( !( isset( $GLOBALS['phpgw']->js ) && is_object( $GLOBALS['phpgw']->js ) ) ) $GLOBALS['phpgw']->js = CreateObject( 'phpgwapi.javascript' );
		
		$GLOBALS['phpgw']->css->validate_file( 'expressoAdmin1_2/templates/default/css/custom.css' );
		$GLOBALS['phpgw']->js->add( 'file', './prototype/plugins/jquery/jquery-latest.min.js', 'utf-8' );
		$GLOBALS['phpgw']->js->add( 'file', './prototype/plugins/jquery/jquery-migrate.min.js', 'utf-8' );		
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang( 'Logs' );
		$GLOBALS['phpgw']->common->phpgw_header();
		
		$p = CreateObject( 'phpgwapi.Template', PHPGW_APP_TPL );
		$p->set_file( array( 'logs' => 'logs.tpl' ) );
		$p->set_block( 'logs', 'list', 'list' );
		$p->set_block( 'logs', 'row', 'row' );
		
		$where = array();
		if (
			( $_POST['query_manager_lid'] != '' ) ||
			( $_POST['query_action'     ] != '' ) ||
			( $_POST['query_date'       ] != '' ) ||
			( $_POST['query_hour'       ] != '' ) ||
			( $_POST['query_other'      ] != '' )
		) {
			if ( $_POST['query_manager_lid'] != '' ) $where[] = 'manager LIKE \'%'.$_POST['query_manager_lid'].'%\'';
			if ( $_POST['query_action'     ] != '' ) $where[] = 'action LIKE \'%'.$_POST['query_action'].'%\'';
			if ( $_POST['query_other'      ] != '' ) {
				$str = $_POST['query_other'];
				$where[] = 'userinfo LIKE \'%'.$str.'%\' OR action LIKE \'%'.$str.'%\' OR manager LIKE \'%'.$str.'%\'';
			}
			if ( $_POST['query_date'] != '' ) {
				$hr = $_POST['query_hour'] != '';
				$where[] = 'date > TO_TIMESTAMP(\''.$_POST['query_date'].$_POST['query_hour'].'\',\'DD/MM/YYYY'.( $hr? 'HH24:MI' :'' ).'\')';
				$where[] = 'date < TO_TIMESTAMP(\''.$_POST['query_date'].$_POST['query_hour'].'\',\'DD/MM/YYYY'.( $hr? 'HH24:MI' :'' ).'\') + INTERVAL \'1 '.( $hr? 'minute' : 'day' ).'\'';
			}
		}
		$per_page_nums  = array( 20, 50, 100 );
		$itens_per_page = ( isset( $_POST['per_page'] ) && in_array( (int)$_POST['per_page'], $per_page_nums ) )? (int)$_POST['per_page'] : 20;
		$cont           = $this->_get_total_itens( $where );
		$nun_pages      = (int)( ( $cont - 1 ) / $itens_per_page ) + 1;
		$page           = max( 1, min( ( isset( $_POST['page'] )? (int)$_POST['page'] : 1 ), $nun_pages ) );
		$offset         = ( $itens_per_page * ( $page - 1 ) );
		$logs           = $this->_list_itens( $where, $itens_per_page, $offset );
		
		$p->set_var( array(
			'back_url'           => $GLOBALS['phpgw']->link( '/expressoAdmin1_2/index.php' ),
			'search_action'      => $GLOBALS['phpgw']->link( '/index.php','menuaction=expressoAdmin1_2.uilogs.list_logs' ),
			'query_manager_lid'  => $_POST['query_manager_lid'],
			'query_action'       => $_POST['query_action'],
			'query_date'         => $_POST['query_date'],
			'query_hour'         => $_POST['query_hour'],
			'query_other'        => $_POST['query_other'],
			'cur_page'           => $page,
			'per_page'           => $itens_per_page,
			'last_page'          => $nun_pages,
			'first_item'         => count( $logs )? $offset + 1 : 0,
			'last_item'          => count( $logs )? $offset + count( $logs ) : 0,
			'total'              => $cont,
			'first_disable'      => ( $page == 1 )? 'disabled' : '',
			'first_disable_icon' => ( $page == 1 )? '-grey' : '',
			'last_disable'       => ( $page == $nun_pages )? 'disabled' : '',
			'last_disable_icon'  => ( $page == $nun_pages )? '-grey' : '',
		) );
		$p->set_var( $this->functions->make_dinamic_lang( $p, 'list' ) );
		
		$per_page_opts = '';
		foreach ( $per_page_nums as $num )
			$per_page_opts .= $num == $itens_per_page? ', '.$num : ', <a onclick="$(\'input[name=per_page]\').val('.$num.'); $(event.currentTarget).parents(\'form:first\').submit();">'.$num.'</a>';
		$p->set_var( 'per_page_opts', substr( $per_page_opts, 2 ) );
		
		foreach ( $logs as $log ) {
			$a_date    = explode( " ", $log['date'] );
			$a_day     = explode( "-", $a_date[0] );
			$a_day_tmp = array_reverse( $a_day );
			$a_day     = join( $a_day_tmp, "/" );
			$a_hour    = explode( ".", $a_date[1] );
			$p->set_var( array(
				'row_date'        => $a_day.'  '.$a_hour[0],
				'row_manager_lid' => $log['manager'],
				'row_action'      => lang( $log['action'] ),
				'row_about'       => $log['userinfo']
			) );
			$p->set_var( 'row_view', $this->_row_action( 'view', 'log', $log['date'] ) );
			$p->parse( 'rows', 'row', true );
		}
		
		$p->pfp( 'out', 'list' );
	}
	
	private function _row_action( $action, $type, $date )
	{
		return '<a href="'.$GLOBALS['phpgw']->link( '/index.php', array(
			'menuaction' => 'expressoAdmin1_2.uilogs.'.$action.'_'.$type,
			'date'       => $date,
		) ).'"> '.lang( $action ).' </a>';
	}
	
	private function _get_total_itens( $where ) {
		if ( count( (array)$where ) == 0 ) return 0;
		$GLOBALS['phpgw']->db->query( '
			SELECT count(*) AS count
			FROM phpgw_expressoadmin_log
			WHERE '.implode( ' AND ', $where )
		);
		return $GLOBALS['phpgw']->db->next_record()? $GLOBALS['phpgw']->db->f( 'count' ) : 0;
	}
	
	private function _list_itens( $where, $limit, $offset ) {
		if ( count( (array)$where ) == 0 ) return array();
		$GLOBALS['phpgw']->db->query( '
			SELECT manager, date, userinfo, action
			FROM phpgw_expressoadmin_log
			WHERE '.implode( ' AND ', $where ).'
			ORDER BY date DESC
			LIMIT '.(int)$limit.'
			OFFSET '.(int)$offset
		);
		$records = array();
		while ( $GLOBALS['phpgw']->db->next_record() ) $records[] = $GLOBALS['phpgw']->db->row();
		return $records;
	}
}
