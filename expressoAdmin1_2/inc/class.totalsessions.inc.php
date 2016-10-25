<?php
	/*************************************************************************************\
	* Expresso Administração                 						           			 *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	 *
	* -----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			 *
	*  under the terms of the GNU General Public License as published by the			 *
	*  Free Software Foundation; either version 2 of the License, or (at your			 *
	*  option) any later version.														 *
	\*************************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');
	
	class totalsessions
	{
		var $functions;
		var $template;
		var $bo;
		var $public_functions = array( 'show_total_sessions' => True );
		
		function totalsessions()
		{
			$this->functions = createobject('expressoAdmin1_2.functions');
			$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$tmp = $this->functions->read_acl($account_lid);
			$manager_context = $tmp[0]['context'];
			// Verifica se o administrador tem acesso.
			if (!$this->functions->check_acl( $account_lid, ACL_Managers::ACL_VW_GLOBAL_SESSIONS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
			}
			
			$this->template = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
		}
		
		function show_total_sessions()
		{
			$sum = 0;
			switch ( ini_get('session.save_handler') ) {
				
				case 'user':
					include_once(PHPGW_API_INC.'/class.dbsession.php');
					$dbsession = new dbsession();
					$sum = $dbsession->get_users_online();
					break;
				
				case 'files':
					$bo    = CreateObject( 'admin.bocurrentsessions' );
					$conts = $bo->get_total_itens();
					$sum   = $conts['num_sessions'];
					
					$this->template->set_var( 'description', '<label>'.lang('Session path').'</label>: <strong>'.ini_get('session.save_path').'</strong>' );
					break;
				
				case 'memcached':
					$bo       = CreateObject( 'admin.bocurrentsessions' );
					$conts    = $bo->get_total_itens();
					$memcache = new Memcached();
					$url = parse_url( ini_get( 'session.save_path' ) );
					$memcache->addServer( $url['host'], $url['port'] );
					$stats    = current( $memcache->getStats() );
					$sum      = $stats['sessions'] = $conts['num_sessions'];
					
					$this->template->set_var( 'description', $this->memcached_info( $stats ) );
					break;
				
				default:
					error_log('destroy session warning: handler '.ini_get('session.save_handler').'not implemented.');
			}
			
			$GLOBALS['phpgw_info']['flags']['app_header'] = 'ExpressoAdmin - '.lang('Total Sessions');
			$GLOBALS['phpgw']->common->phpgw_header();
			
			echo parse_navbar();
			
			$this->template->set_file('template','totalsessions.tpl');
			$this->template->set_block('template','list','list');
			$this->template->set_var($this->functions->make_dinamic_lang($this->template, 'list'));
			$this->template->set_var('lang_total', lang("Total sessions on server"));
			$this->template->set_var('total', $sum);
			$this->template->set_var('back_url', $GLOBALS['phpgw']->link('/expressoAdmin1_2/index.php'));
			$this->template->pfp('out','list');
		}
		
		function memcached_info( $stats ) {
			
			$fields = array(
				'version'					=> 'Memcache Server version',
				'pid'						=> 'Process id of this server process',
				'uptime_usr'				=> 'Runtime server (days h:m:s)',
				'rusage_user_seconds'		=> 'Accumulated user time for this process (seconds)',
				'rusage_system_seconds'		=> 'Accumulated system time for this process (seconds)',
				'curr_connections'			=> 'Number of open connections',
				'connection_structures'		=> 'Number of connection structures allocated by the server',
				'total_connections'			=> 'Total number of connections opened since the server started running',
				'cmd_get'					=> 'Cumulative number of retrieval requests',
				'cmd_set'					=> 'Cumulative number of storage requests',
				'total_items'				=> 'Total number of items stored by this server ever since it started',
				'sessions'					=> 'Number of session keys allocated',
				'get_hits_usr'				=> 'Number of keys that have been requested and found present',
				'get_misses_usr'			=> 'Number of items that have been requested and not found',
				'bytes_read'				=> 'Total number of bytes read by this server from network',
				'bytes_written'				=> 'Total number of bytes sent by this server to network',
				'limit_maxbytes'			=> 'Number of bytes this server is allowed to use for storage',
				'evictions'					=> 'Number of valid items removed from cache to free memory for new items',
			);
			
			// Calc percent hit x miss
			$percCacheHit = round( ((real)$stats['get_hits']) / ((real)$stats['cmd_get']) * 100, 3 );
			$stats['get_hits_usr'] = $stats['get_hits'].' ('.$percCacheHit.'%)';
			$stats['get_misses_usr'] = $stats['get_misses'].' ('.(100-$percCacheHit).'%)';
			$stats['uptime_usr'] = floor($stats['uptime']/(60*60*24)).' '.date('H:i:s', $stats['uptime']);
			
			// byte to MB
			foreach ( array( 'bytes_read', 'bytes_written', 'limit_maxbytes' ) as $key )
				$stats[$key] = (round((real)$stats[$key]/(1024*1024),2)).'MB';
			
			$html = '<table>';
			foreach ( $fields as $key => $field )
				$html .= '<tr><td><label>'.lang($field).':</label></td><td><strong>'.$stats[$key].'</strong></td></tr>';
			$html .= '</table>';
			
			return $html;
		}
	}
