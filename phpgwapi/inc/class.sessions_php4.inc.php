<?php
  /**************************************************************************\
  * eGroupWare API - Session management                                      *
  * This file written by Dan Kuykendall <seek3r@phpgroupware.org>            *
  * and Joseph Engo <jengo@phpgroupware.org>                                 *
  * and Ralf Becker <ralfbecker@outdoor-training.de>                         *
  * Copyright (C) 2000, 2001 Dan Kuykendall                                  *
  * -------------------------------------------------------------------------*
  * This library is part of the phpGroupWare API                             *
  * http://www.egroupware.org/api                                            * 
  * ------------------------------------------------------------------------ *
  * This library is free software; you can redistribute it and/or modify it  *
  * under the terms of the GNU Lesser General Public License as published by *
  * the Free Software Foundation; either version 2.1 of the License,         *
  * or any later version.                                                    *
  * This library is distributed in the hope that it will be useful, but      *
  * WITHOUT ANY WARRANTY; without even the implied warranty of               *
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
  * See the GNU Lesser General Public License for more details.              *
  * You should have received a copy of the GNU Lesser General Public License *
  * along with this library; if not, write to the Free Software Foundation,  *
  * Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
  \**************************************************************************/


	class sessions extends sessions_
	{
		
		function sessions()
		{
			$this->sessions_();
			//controls the time out for php4 sessions - skwashd 18-May-2003
			ini_set('session.gc_maxlifetime', $GLOBALS['phpgw_info']['server']['sessions_timeout']);
			session_name('sessionid');
			
		}

		function read_session()
		{
			if (!$this->sessionid)
			{
				return False;
			}
			session_id($this->sessionid);
			session_start();
			return $GLOBALS['phpgw_session'] = $_SESSION['phpgw_session'];
		}

		function set_cookie_params($domain)
		{
			session_set_cookie_params(0,'/',$domain);
		}

		function new_session_id()
		{
			session_start();

			return session_id();
		}

		function register_session($login,$user_ip,$now,$session_flags)
		{
			// session_start() is now called in new_session_id() !!!

			$GLOBALS['phpgw_session']['session_id'] = $this->sessionid;
			$GLOBALS['phpgw_session']['session_lid'] = $login;
			$GLOBALS['phpgw_session']['session_ip'] = $user_ip;
			$GLOBALS['phpgw_session']['session_logintime'] = $now;
			$GLOBALS['phpgw_session']['session_dla'] = $now;
			$GLOBALS['phpgw_session']['session_action'] = $_SERVER['PHP_SELF'];
			$GLOBALS['phpgw_session']['session_flags'] = $session_flags;
			// we need the install-id to differ between serveral installs shareing one tmp-dir
			$GLOBALS['phpgw_session']['session_install_id'] = $GLOBALS['phpgw_info']['server']['install_id'];

			$_SESSION['phpgw_session'] = $GLOBALS['phpgw_session'];
		}

		// This will update the DateLastActive column, so the login does not expire
		function update_dla()
		{
			if (@isset($_GET['menuaction']))
			{
				$action = $_GET['menuaction'];
			}
			else
			{
				$action = $_SERVER['PHP_SELF'];
			}

			// This way XML-RPC users aren't always listed as
			// xmlrpc.php
			if ($this->xmlrpc_method_called)
			{
				$action = $this->xmlrpc_method_called;
			}

			$GLOBALS['phpgw_session']['session_dla'] = time();
			$GLOBALS['phpgw_session']['session_action'] = $action;

			$_SESSION['phpgw_session'] = $GLOBALS['phpgw_session'];

			return True;
		}
		
		function destroy( $sid, $kp3 = null, $id = null )
		{
			if ( ( !$sid ) && !( is_null( $kp3 ) && is_null( $id ) ) ) return false;
			
			if ( is_null( $id ) ) $this->log_access( $this->sessionid );
			else $this->logout_access( $id, $sid );
			
			// Only do the following, if where working with the current user
			if ( $sid === $GLOBALS['phpgw_info']['user']['sessionid'] ) {
				session_unset();
				@session_destroy();
				if ( $GLOBALS['phpgw_info']['server']['usecookies'] ) $this->phpgw_setcookie( session_name() );
				return true;
			}
			switch ( ini_get( 'session.save_handler' ) ) {
				
				case 'files':
					$fname = ini_get( 'session.save_path' ).'/sess_'.$sid;
					if ( is_writable( $fname ) ) unlink( $fname );
					break;
				
				case 'memcached':
					$memcache = new Memcached();
					$url      = parse_url( ini_get( 'session.save_path' ) );
					$prefix   = ini_get( 'memcached.sess_prefix' );
					$memcache->addServer( $url['host'], $url['port'] );
					$memcache->delete( $prefix.'lock.'.$sid );
					$memcache->delete( $prefix.$sid );
					break;
				
				default:
					error_log('destroy session warning: handler '.ini_get('session.save_handler').'not implemented.');
			}
			return true;
		}
		
		/*************************************************************************\
		* Functions for appsession data and session cache                         *
		\*************************************************************************/
		function delete_cache($accountid='')
		{
			$account_id = get_account_id($accountid,$this->account_id);

			$GLOBALS['phpgw_session']['phpgw_app_sessions']['phpgwapi']['phpgw_info_cache'] = '';

			$_SESSION['phpgw_session'] = $GLOBALS['phpgw_session'];
		}

		function appsession($location = 'default', $appname = '', $data = '##NOTHING##')
		{
			if (! $appname)
			{
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}

			/* This allows the user to put '' as the value. */
			if ($data == '##NOTHING##')
			{
				// I added these into seperate steps for easier debugging
				$data = $GLOBALS['phpgw_session']['phpgw_app_sessions'][$appname][$location]['content'];

				/* do not decrypt and return if no data (decrypt returning garbage) */
				if($data)
				{
					$data = $GLOBALS['phpgw']->crypto->decrypt($data);
					//echo "appsession returning: location='$location',app='$appname',data=$data"; _debug_array($data);
					return $data;
				}
			}
			else
			{
				$encrypteddata = $GLOBALS['phpgw']->crypto->encrypt($data);
				$GLOBALS['phpgw_session']['phpgw_app_sessions'][$appname][$location]['content'] = $encrypteddata;
				$_SESSION['phpgw_session'] = $GLOBALS['phpgw_session'];
				return $data;
			}
			return false;
		}
		
		function cache( $location = 'default', $appname = null, $data = null )
		{
			if ( is_null($appname) ) $appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			
			/* This allows the user to put '' as the value. */
			if ( is_null($data) ) {
				return isset($_SESSION['phpgw_session']['phpgw_app_sessions'][$appname][$location]['content'])?
					$_SESSION['phpgw_session']['phpgw_app_sessions'][$appname][$location]['content'] : false;
			} else return $_SESSION['phpgw_session']['phpgw_app_sessions'][$appname][$location]['content'] = $data;
		}
		
		function get_session_info( $sid )
		{
			$result = false;
			switch ( ini_get('session.save_handler') ) {
				
				case 'files':
					
					$fname = ini_get( 'session.save_path' ).'/sess_'.$sid;
					if ( is_readable( $fname ) ) $result = $this->get_valid_session( file_get_contents( $fname ) );
					break;
					
				case 'memcached':
					
					$memcache = new Memcached();
					$url      = parse_url( ini_get( 'session.save_path' ) );
					$prefix   = ini_get( 'memcached.sess_prefix' );
					$memcache->addServer( $url['host'], $url['port'] );
					$result   = $this->get_valid_session( $memcache->get( $prefix.$sid ) );
					break;
					
				default:
					error_log( 'get_session_info warning: handler '.ini_get( 'session.save_handler' ).'not implemented.' );
			
			}
			return $result;
		}

		/*!
		@function list_sessions
		@abstract get list of normal / non-anonymous sessions
		@note The data form the session-files get cached in the app_session phpgwapi/php4_session_cache
		@author ralfbecker
		*/
		function list_sessions( $start, $sort, $order, $all_no_sort = false )
		{
			$changed = false;
			$time = time();
			$maxmatchs = $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
			$restore = $this->cache('currentsessions_session_data');
			$result = ( isset($restore) && (!is_null($restore['timestamp'])) && $restore['timestamp'] > ($time-(60*60)) )?
				$restore :
				array(
					'list' => null,
					'timestamp' => $time,
					'order' => 'none',
					'sort' => 'none',
				);
			if ( is_null($result['list']) ) {
				$list = array();
				switch (ini_get('session.save_handler')) {
					
					case 'files':
						
						$prefix = 'sess_';
						$len = strlen($prefix);
						if ( ! ($dir = @opendir( $path = ini_get('session.save_path') ) ) ) break;
						while ( $file = readdir($dir) ) {
							if ( substr($file,0,$len) != $prefix || !is_readable( $path.'/'.$file ) ) continue;
							$this->append_valid_session( $list , file_get_contents( $path.'/'.$file ) );
						}
						closedir($dir);
						break;
					
					case 'memcached':
						
						$keys = array();
						$memcache = new Memcached();
						$url = parse_url( ini_get( 'session.save_path' ) );
						$memcache->addServer( $url['host'], $url['port'] );
						foreach ( $memcache->getAllKeys() as $key )
							if ( preg_match('/^'.preg_quote(ini_get( 'memcached.sess_prefix' )).'[a-z0-9]{26}$/', $key) )
								$keys[] = $key;
						$memcache->getDelayed( $keys, true );
						while ( $data = $memcache->fetch() ) $this->append_valid_session( $list , $data['value'] );
						break;
						
					default:
						error_log('destroy session warning: handler '.ini_get('session.save_handler').'not implemented.');
				}
				$result['list'] = $list;
				$changed = true;
			}
			
			if (
				(!( is_null($order) && is_null($sort) )) &&
				(strcasecmp($result['order'],$order) || strcasecmp($result['sort'],$sort))
			){
				$result['order'] = $order;
				$result['sort'] = $sort;
				$sort_type = array(
					'session_ip'		=> 'ip',
					'session_logintime'	=> 'reverse',
				);
				$this->sort( $result['list'], $order, $sort, isset($sort_type[$order])? $sort_type[$order] : 'default');
				$changed = true;
			}
			
			// Update cache
			if ( $changed ) $this->cache( 'currentsessions_session_data', null, $result );
			
			if ( $start > 0 ) array_splice( $result['list'], 0, $start );
			array_splice( $result['list'], $maxmatchs );
			return $result['list'];
		}
		
		/*!
		@function total
		@abstract get number of normal / non-anonymous sessions
		@author ralfbecker
		*/
		function total()
		{
			$this->list_sessions( 0, null, null );
			$restore = $this->cache('currentsessions_session_data');
			return count($restore['list']);
		}
		
		function sort( &$array, $field, $reverse, $type = 'default' )
		{
			$func_sort = null;
			$reverse = (strtoupper($reverse) != 'ASC');
			switch ( $type ) {
				case 'ip':
					$func_sort =
						'$_a = explode(".",$a["'.$field.'"]);'.
						'$_b = explode(".",$b["'.$field.'"]);'.
						'$diff = 0;'.
						'for($i=0; $diff == 0 && isset($_a[$i]) && isset($_b[$i]);$i++)'.
							'$diff = ((int)$_a[$i]) - ((int)$_b[$i]);'.
						'return $diff'.($reverse?'*-1':'').';';
					break;
				case 'reverse': $reverse = !$reverse;
				default:
					$func_sort = 'return strcasecmp(trim($a["'.$field.'"]),trim($b["'.$field.'"]))'.($reverse?'*-1':'').';';
			}
			if ( !is_null($func_sort) ) uasort( $array, create_function('$a,$b', $func_sort) );
		}
		
		public function get_valid_session( $session_data ) {
			$data = self::session_unserialize( $session_data );
			if ( ! (
				isset($data['phpgw_session']) &&
				isset($data['phpgw_session']['session_id']) &&
				isset($data['phpgw_session']['session_ip']) &&
				isset($data['phpgw_session']['session_lid']) &&
				isset($data['phpgw_session']['session_dla']) &&
				isset($data['phpgw_session']['session_action']) &&
				isset($data['phpgw_session']['session_logintime'])
			) ) return false;
			$data = $data['phpgw_session'];
			return array(
				'session_id'		=> $data['session_id'],
				'session_ip'		=> $data['session_ip'],
				'session_lid'		=> $data['session_lid'],
				'session_dla'		=> $data['session_dla'],
				'session_action'	=> $data['session_action'],
				'session_logintime'	=> $data['session_logintime'],
			);
		}
		
		public function append_valid_session( &$list, $session_data ) {
			if ( ( $data = $this->get_valid_session( $session_data ) ) === false ) return false;
			$list[$data['session_id']] = $data;
			return true;
		}
		
		public static function session_unserialize( $session_data ) {
			switch (ini_get("session.serialize_handler")) {
				case 'php':
					return self::_session_unserialize_php( $session_data );
					break;
				case 'php_binary':
					return self::_session_unserialize_phpbinary( $session_data );
					break;
				case 'igbinary':
					return igbinary_unserialize( $session_data );
					break;
				default:
					error_log('Unsupported session.serialize_handler');
			}
			return false;
		}
		
		private static function _session_unserialize_php( $session_data ) {
			$return_data = array();
			$offset = 0;
			while ($offset < strlen($session_data)) {
				if (!strstr(substr($session_data, $offset), "|")) {
					error_log("invalid data, remaining: " . substr($session_data, $offset));
					return false;
				}
				$pos = strpos($session_data, "|", $offset);
				$num = $pos - $offset;
				$varname = substr($session_data, $offset, $num);
				$offset += $num + 1;
				$data = unserialize(substr($session_data, $offset));
				$return_data[$varname] = $data;
				$offset += strlen(serialize($data));
			}
			return $return_data;
		}
		
		private static function _session_unserialize_phpbinary( $session_data ) {
			$return_data = array();
			$offset = 0;
			while ($offset < strlen($session_data)) {
				$num = ord($session_data[$offset]);
				$offset += 1;
				$varname = substr($session_data, $offset, $num);
				$offset += $num;
				$data = unserialize(substr($session_data, $offset));
				$return_data[$varname] = $data;
				$offset += strlen(serialize($data));
			}
			return $return_data;
		}
	}
