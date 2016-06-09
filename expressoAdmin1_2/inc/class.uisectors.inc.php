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

class uisectors
{
	var $public_functions = array
	(
		'list_sectors'                  => true,
		'add_sector'                    => true,
		'edit_sector'                   => true,
		'delete_sector'                 => true,
		'css'                           => true,
	);
	
	var $bo;
	var $nextmatchs;
	var $functions;
	
	function uisectors()
	{
		$this->bo = CreateObject('expressoAdmin1_2.bosectors');
		$this->so = $this->bo->so;
		$this->functions = $this->bo->functions;
		$this->nextmatchs = createobject('phpgwapi.nextmatchs');
	}
	
	public function list_sectors()
	{
		$manager_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
		
		// Verifica se o administrador tem acesso.
		if ( !$this->functions->check_acl( $manager_lid,'list_sectors' ) ) {
			$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
		}
		
		unset($GLOBALS['phpgw_info']['flags']['noheader']);
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Sectors');
		$GLOBALS['phpgw']->common->phpgw_header();
		
		$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
		$p->set_file(array('sectors' => 'sectors.tpl'));
		$p->set_block('sectors','list','list');
		$p->set_block('sectors','row','row');
		$p->set_block('sectors','row_empty','row_empty');
		
		$acl = $this->functions->read_acl( $manager_lid );
		asort( $acl['contexts_display'] );
		$tree = $this->so->getContextTree( $this->functions->get_sectors_list( $acl['contexts'] ) );
		
		$p->set_var( array(
			'th_bg'     => $GLOBALS['phpgw_info']['theme']['th_bg'],
			'back_url'  => $GLOBALS['phpgw']->link('/expressoAdmin1_2/index.php'),
		) );
		$p->set_var($this->functions->make_dinamic_lang($p, 'list'));
		
		if ( !count( $tree ) ) {
			
			$p->set_var( 'message', lang( 'No matches found' ) );
			$p->parse( 'rows','row_empty', true );
			
		} else {
			
			$can_edit   = $this->functions->check_acl( $manager_lid,'edit_sectors' );
			$can_delete = $this->functions->check_acl( $manager_lid,'delete_sectors' );
			
			foreach( $acl['contexts_display'] as $key => $context_name ) {
				$context = $acl['contexts'][$key];
				$p->set_var( array(
					'tr_color'     => $GLOBALS['phpgw_info']['theme']['th_bg'],
					'td_style'     => 'color: blue; padding: 3px;',
					'sector_name'  => $context_name,
					'add_link'     => $this->so->row_action( 'add', 'sector', $context, $context_name ),
					'edit_link'    => '&nbsp;',
					'delete_link'  => '&nbsp;',
				) );
				$p->fp('rows','row',True);

				$userAgent = ( isset($_SERVER['HTTP_USER_AGENT']) )? $_SERVER['HTTP_USER_AGENT'] : "";
				$isIE = ( preg_match('/MSIE/i', $userAgent ) ? true : false );
				$type = ( ($isIE ) ? 'wline' : 'line' );
				
				$sub = $this->so->linearizeNodes( $this->so->findNode( $tree, $context ), $context, null, null, null, $type );
				array_shift ( $sub );
				
				foreach ( $sub as $entry ) {
					$denied = $this->so->sectors->denied( $entry['context'] );
					$tr_color = $this->nextmatchs->alternate_row_color( $tr_color );
					$p->set_var( array(
						'tr_color'     => $tr_color,
						'sector_name'  => $entry['tree'].$entry['name'],
						'td_style'     => '',
						'add_link'     => $this->so->row_action( 'add', 'sector', $entry['context'], $entry['name'] ),
						'edit_link'    => $can_edit? $this->so->row_action( 'edit', 'sector', $entry['context'], $entry['name'] ) : '',
						'delete_link'  => ( $can_delete && !$denied )? $this->so->row_action( 'delete', 'sector', $entry['context'], $entry['name'] ) : '',
					) );
					$p->fp('rows','row',true);
				}
				if ( count($sub)%2 != 0 ) $tr_color = $this->nextmatchs->alternate_row_color( $tr_color );
				
			}
		}
		
		$p->parse('rows','row_empty',True);
		$p->pfp('out','list');
	}
	
	function add_sector()
	{
		$error       = false;
		$is_post     = ( $_SERVER['REQUEST_METHOD'] === 'POST' );
		$manager_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
		$context     = $is_post?
			( isset($_POST['context'])? $_POST['context'] : '' ) :
			( isset($_GET['context'])? $_GET['context'] : '' );
		
		// Check access to create and context permission
		if ( !(
			$this->functions->check_acl( $manager_lid, 'create_sectors' ) &&
			$this->so->check_context( $manager_lid, $context )
		) ) {
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/expressoAdmin1_2/inc/access_denied.php' ) );
		}
		
		$sector = isset($_POST['sector'])? $_POST['sector'] : '';
		$hide   = isset($_POST['hide_sector']);
		
		if ( $is_post ) {
			
			// Check empty sector name
			if ( ( !$error ) && $sector == '' )
				$error = lang( 'Sector name is empty' );
			
			// Check if the context exists
			if ( ( !$error ) && ( !$this->so->exist_context( $context ) ) )
				$error = lang( 'Context not found' );
			
			// Check if there is already a sector in context
			if ( ( !$error ) && $this->so->exist_sector_name( $sector, $context ) )
				$error = lang( 'Sector name already exist' );
			
			// Insert new sector
			if ( ( !$error ) && ( !$this->bo->create_sector( $sector, $context, $hide ) ) )
				$error = lang( 'Error in OpenLDAP recording' );
			
			// Redirect on success
			if ( !$error ) $GLOBALS['phpgw']->redirect(
				$GLOBALS['phpgw']->link( '/index.php','menuaction=expressoAdmin1_2.uisectors.list_sectors' )
			);
		}
		
		// Init render
		unset($GLOBALS['phpgw_info']['flags']['noheader']);
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Create Sector');
		$GLOBALS['phpgw']->common->phpgw_header();
		
		// Set the template
		$p = CreateObject( 'phpgwapi.Template', PHPGW_APP_TPL );
		$p->set_file( array( 'create_sector' => 'sectors_form.tpl' ) );
		$p->set_block( 'create_sector', 'list', 'list' );
		
		$p->set_var( array(
			'th_bg'               => $GLOBALS['phpgw_info']['theme']['th_bg'],
			'context'             => $context,
			'sector'              => $sector,
			'hide_sector_checked' => $hide ? 'checked' : '',
			'error_messages'      => $error? '<script type="text/javascript">alert(\''.str_replace(PHP_EOL, '\\n',$error).'\')</script>' : '',
			'back_url'            => $GLOBALS['phpgw']->link( '/index.php',
				'menuaction=expressoAdmin1_2.uisectors.list_sectors'
			),
			'action'              => $GLOBALS['phpgw']->link( '/index.php', array(
				'menuaction' => 'expressoAdmin1_2.uisectors.add_sector',
				'context'    => $context
			)),
		) );
		
		$p->set_var( $this->functions->make_dinamic_lang( $p, 'list' ) );
		$p->pfp( 'out', 'create_sector' );
	}
	
	function edit_sector()
	{
		$error       = false;
		$is_post     = ( $_SERVER['REQUEST_METHOD'] === 'POST' );
		$manager_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
		$context     = $is_post?
			( isset($_POST['context'])? $_POST['context'] : '' ) :
			( isset($_GET['context'])? $_GET['context'] : '' );
		
		// Check access to edit and context permission
		if ( !(
			$this->functions->check_acl( $manager_lid, 'edit_sectors' ) &&
			$this->so->check_context( $manager_lid, $context, true )
		) ) {
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/expressoAdmin1_2/inc/access_denied.php' ) );
		}
		
		// Check if the context exists
		$info = $this->so->get_context( $context );
		if ( !( isset($info['count']) && $info['count'] > 0 ) )
			$error = lang( 'Context not found' );
		
		$sector   = $info[0]['ou'][0];
		$old_hide = ( isset($info[0]['phpgwaccountvisible'][0]) && $info[0]['phpgwaccountvisible'][0] == '-1' );
		$hide     = $is_post? isset($_POST['hide_sector']) : $old_hide;
		
		if ( ( !$error ) && $is_post ) {
			
			// Update sector
			if ( ( !$error ) && ( $hide != $old_hide ) && ( !$this->bo->edit_sector( $context, $hide ) ) )
				$error = lang( 'Error in OpenLDAP recording' );
			
			// Redirect on success
			if ( !$error ) $GLOBALS['phpgw']->redirect(
				$GLOBALS['phpgw']->link( '/index.php','menuaction=expressoAdmin1_2.uisectors.list_sectors' )
			);
		}
		
		// Init render
		unset($GLOBALS['phpgw_info']['flags']['noheader']);
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Edit Sector');
		$GLOBALS['phpgw']->common->phpgw_header();
		
		// Set the template
		$p = CreateObject( 'phpgwapi.Template', PHPGW_APP_TPL );
		$p->set_file( array( 'edit_sector' => 'sectors_form.tpl' ) );
		$p->set_block( 'edit_sector', 'list', 'list' );
		
		$p->set_var( array(
			'th_bg'               => $GLOBALS['phpgw_info']['theme']['th_bg'],
			'context'             => $context,
			'disable'             => 'disabled',
			'sector'              => $sector,
			'hide_sector_checked' => $hide ? 'checked' : '',
			'error_messages'      => $error? '<script type="text/javascript">alert(\''.str_replace(PHP_EOL, '\\n',$error).'\')</script>' : '',
			'action'              => $GLOBALS['phpgw']->link( '/index.php', array(
				'menuaction' => 'expressoAdmin1_2.uisectors.edit_sector',
				'context'    => $context
			)),
			'back_url'            => $GLOBALS['phpgw']->link( '/index.php',
				'menuaction=expressoAdmin1_2.uisectors.list_sectors'
			),
		) );
		
		$p->set_var( $this->functions->make_dinamic_lang( $p, 'list' ) );
		$p->pfp( 'out', 'edit_sector' );
	}
	
	function delete_sector()
	{
		$error       = false;
		$loop        = false;
		$is_post     = ( $_SERVER['REQUEST_METHOD'] === 'POST' );
		$manager_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
		$context     = $is_post?
			( isset($_POST['context'])? $_POST['context'] : '' ) :
			( isset($_GET['context'])? $_GET['context'] : '' );
		
		// Check access to delete and context permission
		if ( !(
			$this->functions->check_acl( $manager_lid, 'delete_sectors' ) &&
			$this->so->check_context( $manager_lid, $context, true ) &&
			!$this->so->sectors->denied( $context )
		) ) {
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/expressoAdmin1_2/inc/access_denied.php' ) );
		}
		
		// Check if the context exists
		$info      = $this->so->get_context( $context );
		if ( !( isset($info['count']) && $info['count'] > 0 ) )
			$error = lang( 'Context not found' );
		$sector    = $info[0]['ou'][0];
		
		// Get all entries below the sector
		$entrylist = $this->so->get_entries_list( $context );
		if ( isset( $entrylist['error'] ) ) $error = implode( ',', $entrylist['error'] );
		
		// Get organizations tree
		$tree = $this->so->get_tree_list( $context );
		
		if ( ( !$error ) && $is_post && isset( $_POST['delete'] ) && isset( $_POST['confirm_chk'] ) ) {
			
			// Others entries with unknown type shoud not be deleted
			if ( ( !$error ) && ( isset( $entrylist['brief']['others'] ) && count( $entrylist['brief']['others'] ) > 0 ) )
				$error = lang( 'Unknown entries below the sector' );
			
			// Check if list ous is same number of sector tree
			if ( ( !$error ) && ( isset( $entrylist['brief']['ous'] ) && $entrylist['brief']['ous'] !== $tree['count'] ) )
				$error = lang( 'Error sector count invalid' );
			
			// Delete sector
			if ( !$error ) {
				$result = $this->bo->delete_sector( $context , $entrylist );
				if ( isset( $result['status'] ) && $result['status'] === true ) {
					$GLOBALS['phpgw']->redirect(
						$GLOBALS['phpgw']->link( '/index.php','menuaction=expressoAdmin1_2.uisectors.list_sectors' )
					);
				} else if ( isset( $result['partial'] ) ) {
					$loop = true;
					$entrylist['brief']['users'] -= count($entrylist['users']);
				} else {
					$error     = isset( $result['msg'] )? $result['msg'] : lang( 'Error in OpenLDAP recording' );
					$entrylist = $this->so->get_entries_list( $context );
					$tree      = $this->so->get_tree_list( $context );
					if ( isset( $entrylist['error'] ) ) $error = implode( ',', $entrylist['error'] );
				}
			}
		}
		
		// Init render
		unset($GLOBALS['phpgw_info']['flags']['noheader']);
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Delete Sectors');
		$GLOBALS['phpgw']->common->phpgw_header();
		
		$title = array(
			'error'      => lang( 'Error' ),
			'ous'        => lang( 'organizations' ),
			'others'     => lang( 'Others' ),
			'smbdomains' => lang( 'Samba Domains' ),
			'computers'  => lang( 'computers' ),
			'lists'      => lang( 'email lists' ),
			'groups'     => lang( 'user groups' ),
			'insts'      => lang( 'institutional_accounts' ),
			'users'      => lang( 'user accounts' ),
		);
		$brief = '<table>';
		foreach ( $entrylist['brief'] as $key => $value ) $brief .= '<tr><td>'.$title[$key].':</td><td>'.$value.'</td></tr>';
		$brief .= '</table>';
		
		// Set the template
		$p = CreateObject( 'phpgwapi.Template', PHPGW_APP_TPL );
		$p->set_file( array( 'delete_sector' => 'sectors_delete.tpl' ) );
		$p->set_block( 'delete_sector', 'list' );
		$p->set_block( 'delete_sector', 'row' );
		
		$p->set_var( array(
			'color_bg1'      => "#E8F0F0",
			'context'        => $context,
			'sector'         => $sector,
			'lang_confirm'   => 'confirm message',
			'delete_disable'  => $error? 'disabled="disabled"' : '',
			'error_messages' => $error? '<script type="text/javascript">alert(\''.str_replace(PHP_EOL, '\\n',$error).'\')</script>' : '',
			'loop_script'    => $loop? '<script type="text/javascript">$(document).ready(function(){'.PHP_EOL.
				'$(\'input[name=confirm_chk]\').prop(\'checked\',true);'.PHP_EOL.
				'$(\'form[name=form]\').attr(\'onsubmit\',null);'.PHP_EOL.
				'$(\'input[name=delete]\').click();'.PHP_EOL.
				'});</script>' : '',
			'action'         => $GLOBALS['phpgw']->link( '/index.php', array(
				'menuaction' => 'expressoAdmin1_2.uisectors.delete_sector',
				'context'    => $context
			)),
			'back_url'       => $GLOBALS['phpgw']->link( '/index.php',
				'menuaction=expressoAdmin1_2.uisectors.list_sectors'
			),
			'lang_sectors'   => $title['ous'],
			'sectors_list'   => $tree['list'],
			'sectors_count'  => $tree['count'],
			'lang_brief'     => lang ( 'Brief' ),
			'resume_list'    => $brief,
		) );
		
		$p->set_var( $this->functions->make_dinamic_lang( $p, 'list' ) );
		if ( !$loop ) {
			foreach ( array( 'others', 'users', 'insts', 'groups', 'lists', 'computers', 'smbdomains' ) as $type ) {
				if ( !isset($entrylist[$type]) ) continue;
				$list = $this->so->create_list( $type, $entrylist[$type] );
				$p->set_var( array(
					'lang_section'  => $title[$type],
					'section_list'  => $list['list'],
					'section_count' => $list['count'].($entrylist['brief'][$type] > $list['count']? '+' : '' ),
				) );
				$p->fp( 'rows', 'row', true );
			}
		}
		$p->set_var( 'delete_sector', '{list}' );
		$p->pfp( 'out', 'delete_sector' );
	}
	
	public function css()
	{
		$appCSS = '';
		return $appCSS;
	}
}
