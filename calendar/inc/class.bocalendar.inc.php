<?php
  /**************************************************************************\
  * eGroupWare - Calendar                                                    *
  * http://www.eGroupWare.org                                                *
  * Maintained and further developed by RalfBecker@outdoor-training.de       *
  * Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
  *          http://www.radix.net/~cknudsen                                  *
  * Originaly modified by Mark Peters <skeeter@phpgroupware.org>             *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	class bocalendar
	{
		var $public_functions = Array(
			'read_entry'      => True,
			'delete_entry'    => True,
			'delete_calendar' => True,
			'change_owner'    => True,
			'update'          => True,
			'check_set_default_prefs' => True,
			'store_to_cache'  => True,
			'export_event'    => True,
			'send_alarm'      => True,
			'reinstate'       => True
		);

		var $soap_functions = Array(
			'read_entry' => Array(
				'in' => Array(
					'int'
				),
				'out' => Array(
					'SOAPStruct'
				)
			),
			'delete_entry' => Array(
				'in' => Array(
					'int'
				),
				'out' => Array(
					'int'
				)
			),
			'delete_calendar' => Array(
				'in' => Array(
					'int'
				),
				'out' => Array(
					'int'
				)
			),
			'change_owner' => Array(
				'in' => Array(
					'array'
				),
				'out' => Array(
					'int'
				)
			),
			'update' => Array(
				'in' => Array(
					'array',
					'array',
					'array',
					'array',
					'array'
				),
				'out' => Array(
					'array'
				)
			),
			'store_to_cache'	=> Array(
				'in' => Array(
					'struct'
				),
				'out' => Array(
					'SOAPStruct'
				)
			),
			'store_to_cache'	=> Array(
				'in' => Array(
					'array'
				),
				'out' => Array(
					'string'
				)
			),
			'categories' => array(
				'in'  => array('bool'),
				'out' => array('array')
			),
		);

                var $wdays;

                var $debug = False;
//		var $debug = True;

		var $so;
		var $so1;
		var $ex_participants;
		var $cached_events;
		var $repeating_events;
		var $day;
		var $month;
		var $year;
		var $prefs;

		var $owner;
		var $holiday_color;
		var $printer_friendly = False;

		var $cached_holidays;

		var $g_owner = 0;

		var $filter;
		var $cat_id;
		var $users_timeformat;

		var $modified;
		var $deleted;
		var $added;

		var $is_group = False;

		var $soap = False;

		var $use_session = False;

		var $today;
		var $debug_string;

		var $sortby;
		var $num_months;
		var $xmlrpc = False;	// not called via xmlrpc
       	var $async = false;

		function bocalendar($session=0)
		{

			if(!is_object($GLOBALS['phpgw']->datetime))
			{
				$GLOBALS['phpgw']->datetime = createobject('phpgwapi.date_time');
			}

			$this->cat = CreateObject('phpgwapi.categories');
			$this->grants = $GLOBALS['phpgw']->acl->get_grants('calendar');
			@reset($this->grants);
			if(DEBUG_APP)
			{
				if(floor(phpversion()) >= 4)
				{
					$this->debug_string = '';
					ob_start();
				}

				foreach($this->grants as $grantor => $rights)
				{
					print_debug('Grantor',$grantor);
					print_debug('Rights',$rights);
				}
			}

			print_debug('Read use_session',$session);

			if($session)
			{
				$this->read_sessiondata();
				$this->use_session = True;
			}
			print_debug('BO Filter',$this->filter);
			print_debug('Owner',$this->owner);

			if ($GLOBALS['argv']) {
			  $this->async = true;
			  $this->load_lang();
			}

			$this->prefs['calendar']    = $GLOBALS['phpgw_info']['user']['preferences']['calendar'];
			$this->check_set_default_prefs();

			$owner = get_var('owner',array('GET','POST'),$GLOBALS['owner']);

			preg_match('/menuaction=([a-zA-Z.]+)/',$_SERVER['HTTP_REFERER'],$regs);
			$from = $regs[1];
			if ((substr($_SERVER['PHP_SELF'],-8) == 'home.php' && substr($this->prefs['calendar']['defaultcalendar'],0,7) == 'planner'
				|| $_GET['menuaction'] == 'calendar.uicalendar.planner' &&
				$from  != 'calendar.uicalendar.planner' && !$this->save_owner)
				&& (int)$this->prefs['calendar']['planner_start_with_group'] > 0)
			{
				// entering planner for the first time ==> saving owner in save_owner, setting owner to default
				//
				$this->save_owner = $this->owner;
				$owner = 'g_'.$this->prefs['calendar']['planner_start_with_group'];
			}
			elseif ($_GET['menuaction'] != 'calendar.uicalendar.planner' &&
				$this->save_owner)
			{
				// leaving planner with an unchanged user/owner ==> setting owner back to save_owner
				//
				$owner = (int)(isset($_GET['owner']) ? $_GET['owner'] : $this->save_owner);
				unset($this->save_owner);
			}
			elseif (!empty($owner) && $owner != $this->owner && $from == 'calendar.uicalendar.planner')
			{
				// user/owner changed within planner ==> forgetting save_owner
				//
				unset($this->save_owner);
			}

			if(isset($owner) && $owner!='' && substr($owner,0,2) == 'g_')
			{
				$this->set_owner_to_group(substr($owner,2));
			}
			elseif(isset($owner) && $owner!='')
			{
				$this->owner = (int)$owner;
			}
			elseif(!@isset($this->owner) || !@$this->owner)
			{
				$this->owner = (int)$GLOBALS['phpgw_info']['user']['account_id'];
			}
			elseif(isset($this->owner) && $GLOBALS['phpgw']->accounts->get_type($this->owner) == 'g')
			{
				$this->set_owner_to_group((int)$this->owner);
			}

			$this->prefs['common']    = $GLOBALS['phpgw_info']['user']['preferences']['common'];

			if ($this->prefs['common']['timeformat'] == '12')
			{
				$this->users_timeformat = 'h:ia';
			}
			else
			{
				$this->users_timeformat = 'H:i';
			}
			$this->holiday_color = (substr($GLOBALS['phpgw_info']['theme']['bg07'],0,1)=='#'?'':'#').$GLOBALS['phpgw_info']['theme']['bg07'];

			$friendly = (isset($_GET['friendly'])?$_GET['friendly']:'');
			$friendly = ($friendly=='' && isset($_POST['friendly'])?$_POST['friendly']:$friendly);

			$this->printer_friendly = ((int)$friendly == 1?True:False);

			if(isset($_POST['filter'])) { $this->filter = $_POST['filter']; }
			if(isset($_POST['sortby'])) { $this->sortby = $_POST['sortby']; }
			if(isset($_POST['cat_id'])) { $this->cat_id = $_POST['cat_id']; }

			if(!isset($this->filter))
			{
				$this->filter = ' '.$this->prefs['calendar']['defaultfilter'].' ';
			}

			if(!isset($this->sortby))
			{
				$this->sortby = $this->prefs['calendar']['defaultcalendar'] == 'planner_user' ? 'user' : 'category';
			}

			if($GLOBALS['phpgw']->accounts->get_type($this->owner)=='g')
			{
				$this->filter = ' all ';
			}

			$this->so = CreateObject('calendar.socalendar',
				Array(
					'owner'		=> $this->owner,
					'filter'	=> $this->filter,
					'category'	=> $this->cat_id,
					'g_owner'	=> $this->g_owner
				)
			);
			$this->rpt_day = array(	// need to be after creation of socalendar
				MCAL_M_SUNDAY    => 'Sunday',
				MCAL_M_MONDAY    => 'Monday',
				MCAL_M_TUESDAY   => 'Tuesday',
				MCAL_M_WEDNESDAY => 'Wednesday',
				MCAL_M_THURSDAY  => 'Thursday',
				MCAL_M_FRIDAY    => 'Friday',
				MCAL_M_SATURDAY  => 'Saturday'
			);
			if($this->bo->prefs['calendar']['weekdaystarts'] != 'Sunday')
			{
				$mcals = array_keys($this->rpt_day);
				$days  = array_values($this->rpt_day);
				$this->rpt_day = array();
				list($n) = $found = array_keys($days,$this->prefs['calendar']['weekdaystarts']);
				for ($i = 0; $i < 7; ++$i,++$n)
				{
					$this->rpt_day[$mcals[$n % 7]] = $days[$n % 7];
				}
			}
			$this->rpt_type = Array(
				MCAL_RECUR_NONE		=> 'None',
				MCAL_RECUR_DAILY	=> 'Daily',
				MCAL_RECUR_WEEKLY	=> 'Weekly',
				MCAL_RECUR_MONTHLY_WDAY	=> 'Monthly (by day)',
				MCAL_RECUR_MONTHLY_MDAY	=> 'Monthly (by date)',
				MCAL_RECUR_YEARLY	=> 'Yearly'
			);

			$localtime = $GLOBALS['phpgw']->datetime->users_localtime;

			$date = (isset($GLOBALS['date'])?$GLOBALS['date']:'');
			$date = (isset($_GET['date'])?$_GET['date']:$date);
			$date = ($date=='' && isset($_POST['date'])?$_POST['date']:$date);

			$year = (isset($_GET['year'])?$_GET['year']:'');
			$year = ($year=='' && isset($_POST['year'])?$_POST['year']:$year);

			$month = (isset($_GET['month'])?$_GET['month']:'');
			$month = ($month=='' && isset($_POST['month'])?$_POST['month']:$month);

			$day = (isset($_GET['day'])?$_GET['day']:'');
			$day = ($day=='' && isset($_POST['day'])?$_POST['day']:'');

			$num_months = (isset($_GET['num_months'])?$_GET['num_months']:'');
			$num_months = ($num_months=='' && isset($_POST['num_months'])?$_POST['num_months']:$num_months);

			if(isset($date) && $date!='')
			{
				$this->year  = (int)(substr($date,0,4));
				$this->month = (int)(substr($date,4,2));
				$this->day   = (int)(substr($date,6,2));
			}
			else
			{
				if(isset($year) && $year!='')
				{
					$this->year = $year;
				}
				else
				{
					$this->year = date('Y',$localtime);
				}
				if(isset($month) && $month!='')
				{
					$this->month = $month;
				}
				else
				{
					$this->month = date('m',$localtime);
				}
				if(isset($day) && $day!='')
				{
					$this->day = $day;
				}
				else
				{
					$this->day = date('d',$localtime);
				}
			}

			if(isset($num_months) && $num_months!='')
			{
				$this->num_months = $num_months;
			}
			elseif($this->num_months == 0)
			{
				$this->num_months = 1;
			}

			$this->today = date('Ymd',$GLOBALS['phpgw']->datetime->users_localtime);

			if(!@is_object($GLOBALS['phpgw']->sms)) $GLOBALS['phpgw']->sms = CreateObject('phpgwapi.sms');

			if(DEBUG_APP)
			{
				print_debug('BO Filter','('.$this->filter.')');
				print_debug('Owner',$this->owner);
				print_debug('Today',$this->today);
				if(floor(phpversion()) >= 4)
				{
					$this->debug_string .= ob_get_contents();
					ob_end_clean();
				}
			}
			$this->xmlrpc = is_object($GLOBALS['server']) && $GLOBALS['server']->last_method;
                        $this->wdays = Array(MCAL_M_SUNDAY,MCAL_M_MONDAY,MCAL_M_TUESDAY,MCAL_M_WEDNESDAY,
                            MCAL_M_THURSDAY,MCAL_M_FRIDAY,MCAL_M_SATURDAY);
		}

		function delegar($idDelegador,$idDelegado,$idEvento) {

			$this->so->cal->stream->lock(array("phpgw_cal_user"));
			
			if(substr($idDelegado,-1) == 'U') {
			    $idDelegado = substr($idDelegado,0,-1);
			}

			$sql = "SELECT * FROM phpgw_cal_user where cal_id=$idEvento and cal_login=$idDelegado";

			if($idDelegado != '')
			{
				$this->so->cal->stream->query($sql,__LINE__,__FILE__);
				if($this->so->cal->stream->next_record())
				{
					throw new Exception("The selected User is already included in this event");
				}else{
					$sql = "update phpgw_cal_user set cal_login = $idDelegado,cal_status='U' where cal_id=$idEvento 
						and cal_login=$idDelegador";

					$this->so->cal->stream->query($sql,__LINE__,__FILE__,0,-1,true);
				
					$event = $this->so->read_entry($idEvento);

					$this->send_update(MSG_ADDED,array($idDelegado => 'U'),$event,$event,false,'delegar');
				}
			}else {
				throw new Exception('999');
			}

			$this->so->cal->stream->unlock();
		}

	  function load_lang() {
	    if(!$_SESSION['phpgw_info']['calendar']['langAlarm']) 
	      {
		$array_keys = array();
		$fn = '../../calendar/setup/phpgw_alarm_'.$GLOBALS['phpgw_info']['user']['preferences']['common']['lang'].'.lang';			
		echo $fn;
		if (file_exists($fn)){
		  $fp = fopen($fn,'r');
		  while ($data = fgets($fp,16000))	{
		    list($message_id,$app_name,$null,$content) = explode("\t",substr($data,0,-1));			
		    $_SESSION['phpgw_info']['calendar']['langAlarm'][$message_id] =  $content;
		  }
		  fclose($fp);
		}
	      }
	  }
		function list_methods($_type='xmlrpc')
		{
			/*
			  This handles introspection or discovery by the logged in client,
			  in which case the input might be an array.  The server always calls
			  this function to fill the server dispatch map using a string.
			*/
			if (is_array($_type))
			{
				$_type = $_type['type'];
			}
			switch($_type)
			{
				case 'xmlrpc':
					$xml_functions = array(
						'list_methods' => array(
							'function'  => 'list_methods',
							'signature' => array(array(xmlrpcStruct,xmlrpcString)),
							'docstring' => lang('Read this list of methods.')
						),
						'read' => array(
							'function'  => 'read_entry',
							'signature' => array(array(xmlrpcStruct,xmlrpcInt)),
							'docstring' => lang('Read a single entry by passing the id and fieldlist.')
						),
						'read_entry' => array(	// deprecated, use read
							'function'  => 'read_entry',
							'signature' => array(array(xmlrpcStruct,xmlrpcInt)),
							'docstring' => lang('Read a single entry by passing the id and fieldlist.')
						),
						'write' => array(
							'function'  => 'update',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('Add or update a single entry by passing the fields.')
						),
						'add_entry' => array(	// deprecated, use write
							'function'  => 'update',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('Add a single entry by passing the fields.')
						),
						'update_entry' => array(	// deprecated, use write
							'function'  => 'update',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('Update a single entry by passing the fields.')
						),
						'delete' => array(
							'function'  => 'delete_entry',
							'signature' => array(array(xmlrpcInt,xmlrpcInt)),
							'docstring' => lang('Delete a single entry by passing the id.')
						),
						'delete_entry' => array(	// deprecated, use delete
							'function'  => 'delete_entry',
							'signature' => array(array(xmlrpcInt,xmlrpcInt)),
							'docstring' => lang('Delete a single entry by passing the id.')
						),
						'delete_calendar' => array(
							'function'  => 'delete_calendar',
							'signature' => array(array(xmlrpcInt,xmlrpcInt)),
							'docstring' => lang('Delete an entire users calendar.')
						),
						'change_owner' => array(
							'function'  => 'change_owner',
							'signature' => array(array(xmlrpcInt,xmlrpcStruct)),
							'docstring' => lang('Change all events for $params[\'old_owner\'] to $params[\'new_owner\'].')
						),
						'search' => array(
							'function'  => 'store_to_cache',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('Read a list of entries.')
						),
						'store_to_cache' => array(	// deprecated, use search
							'function'  => 'store_to_cache',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('Read a list of entries.')
						),
						'export_event' => array(
							'function'  => 'export_event',
							'signature' => array(array(xmlrpcString,xmlrpcStruct)),
							'docstring' => lang('Export a list of entries in iCal format.')
						),
						'categories' => array(
							'function'  => 'categories',
							'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
							'docstring' => lang('List all categories.')
						),
					);
					return $xml_functions;
					break;
				case 'soap':
					return $this->soap_functions;
					break;
				default:
					return array();
					break;
			}
		}

		function set_owner_to_group($owner)
		{
			print_debug('calendar::bocalendar::set_owner_to_group:owner',$owner);
			$this->owner = (int)$owner;
			$this->is_group = True;
			$this->g_owner = Array();
			$members = $GLOBALS['phpgw']->accounts->member($owner);
			if (is_array($members))
			{
				foreach($members as $user)
				{
					// use only members which gave the user a read-grant
					if ($this->check_perms(PHPGW_ACL_READ,0,$user['account_id']))
					{
						$this->g_owner[] = $user['account_id'];
					}
				}
			}
			//echo "<p>".function_backtrace().": set_owner_to_group($owner) = ".print_r($this->g_owner,True)."</p>\n";
		}

		function member_of_group($owner=0)
		{
			$owner = ($owner==0?$GLOBALS['phpgw_info']['user']['account_id']:$owner);
			$group_owners = $GLOBALS['phpgw']->accounts->membership();
			while($group_owners && list($index,$group_info) = each($group_owners))
			{
				if($this->owner == $group_info['account_id'])
				{
					return True;
				}
			}
			return False;
		}

		function save_sessiondata($data='')
		{
			if ($this->use_session)
			{
				if (!is_array($data))
				{
					$data = array(
						'filter'     => $this->filter,
						'cat_id'     => $this->cat_id,
						'owner'      => $this->owner,
						'save_owner' => $this->save_owner,
						'year'       => $this->year,
						'month'      => $this->month,
						'day'        => $this->day,
						'date'       => $this->date,
						'sortby'     => $this->sortby,
						'num_months' => $this->num_months,
						'return_to'  => $this->return_to
					);
				}
				if($this->debug)
				{
					if(floor(phpversion()) >= 4)
					{
						ob_start();
					}
					echo '<!-- '."\n".'Save:'."\n"._debug_array($data,False)."\n".' -->'."\n";
					if(floor(phpversion()) >= 4)
					{
						$this->debug_string .= ob_get_contents();
						ob_end_clean();
					}
				}
				$GLOBALS['phpgw']->session->appsession('session_data','calendar',$data);
			}
		}

		function read_sessiondata()
		{
			$data = $GLOBALS['phpgw']->session->appsession('session_data','calendar');
			print_debug('Read',_debug_array($data,False));

			$this->filter = $data['filter'];
			$this->cat_id = $data['cat_id'];
			$this->sortby = $data['sortby'];
			$this->owner  = (int)$data['owner'];
			$this->save_owner = (int)$data['save_owner'];
			$this->year   = (int)$data['year'];
			$this->month  = (int)$data['month'];
			$this->day    = (int)$data['day'];
			$this->num_months = (int)$data['num_months'];
			$this->return_to = $data['return_to'];
		}

		function read_entry($id,$ignore_acl=False)
		{
			if (is_array($id) && count($id) == 1)
			{
				list(,$id) = each($id);
			}
			if($ignore_acl || $this->check_perms(PHPGW_ACL_READ,$id))
			{
				$event = $this->so->read_entry($id);
				if(!isset($event['participants'][$this->owner]) && $this->user_is_a_member($event,$this->owner))
				{
					$this->so->add_attribute('participants','U',(int)$this->owner);
					$this->so->add_entry($event);
					$event = $this->get_cached_event();
				}
				return $this->xmlrpc ? $this->xmlrpc_prepare($event) : $event;
			}
			if ($this->xmlrpc)
			{
				$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['no_access'],$GLOBALS['xmlrpcstr']['no_access']);
			}
			return False;
		}

		function delete_single($param)
		{
			if($this->check_perms(PHPGW_ACL_DELETE,(int)$param['id']))
			{
				$temp_event = $this->get_cached_event();
				$event = $this->read_entry((int)$param['id']);
//				if($this->owner == $event['owner'])
//				{
				$exception_time = mktime($event['start']['hour'],$event['start']['min'],0,$param['month'],$param['day'],$param['year']) - $GLOBALS['phpgw']->datetime->tz_offset;
				$event['recur_exception'][] = (int)$exception_time;
				$this->so->cal->event = $event;
//				print_debug('exception time',$event['recur_exception'][count($event['recur_exception']) -1]);
//				print_debug('count event exceptions',count($event['recur_exception']));
				$this->so->add_entry($event);
				$cd = 16;

				$this->so->cal->event = $temp_event;
				unset($temp_event);
			}
			else
			{
				$cd = 60;
			}
//			}
			return $cd;
		}

		function delete_entry($id)
		{
			if (is_array($id) && count($id) == 1)
			{
				list(,$id) = each($id);
			}
			if($this->check_perms(PHPGW_ACL_DELETE,$id))
			{
				$this->so->delete_entry($id);

				if ($this->xmlrpc)
				{
					$this->so->expunge($id);
				}
				return $this->xmlrpc ? True : 16;
			}
			if ($this->xmlrpc)
			{
				$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['no_access'],$GLOBALS['xmlrpcstr']['no_access']);
			}
			return 60;
		}

		function reinstate($params='')
		{
			if($this->check_perms(PHPGW_ACL_EDIT,$params['cal_id']) && isset($params['reinstate_index']))
			{
				$event = $this->so->read_entry($params['cal_id']);
				@reset($params['reinstate_index']);
				print_debug('Count of reinstate_index',count($params['reinstate_index']));
				if(count($params['reinstate_index']) > 1)
				{
					while(list($key,$value) = each($params['reinstate_index']))
					{
						print_debug('reinstate_index ['.$key.']',(int)$value);
						print_debug('exception time',$event['recur_exception'][(int)$value]);
						unset($event['recur_exception'][(int)$value]);
						print_debug('count event exceptions',count($event['recur_exception']));
					}
				}
				else
				{
					print_debug('reinstate_index[0]',(int)$params['reinstate_index'][0]);
					print_debug('exception time',$event['recur_exception'][(int)$params['reinstate_index'][0]]);
					unset($event['recur_exception'][(int)$params['reinstate_index'][0]]);
					print_debug('count event exceptions',count($event['recur_exception']));
				}
				$this->so->cal->event = $event;
				$this->so->add_entry($event);
				return 42;
			}
			else
			{
				return 43;
			}
		}

		function delete_calendar($owner)
		{
			if($GLOBALS['phpgw_info']['user']['apps']['admin'])
			{
				$this->so->delete_calendar($owner);
			}
		}

		function change_owner($params='')
		{
			if($GLOBALS['phpgw_info']['server']['calendar_type'] == 'sql')
			{
				if(is_array($params))
				{
					$this->so->change_owner($params['old_owner'],$params['new_owner']);
				}
			}
		}

		function expunge()
		{
			reset($this->so->cal->deleted_events);
			while(list($i,$event_id) = each($this->so->cal->deleted_events))
			{
				$event = $this->so->read_entry($event_id);
				if(!$this->ex_participants)
					$this->ex_participants = html_entity_decode($event['ex_participants']);			
				if($this->check_perms(PHPGW_ACL_DELETE,$event))
				{
					$this->send_update(MSG_DELETED,$event['participants'],$event,false,false,'expunge');
				}
				else
				{
					unset($this->so->cal->deleted_events[$i]);
				}
			}
			$this->so->expunge();
		}

		function search_keywords($keywords)
		{
			$type = $GLOBALS['phpgw']->accounts->get_type($this->owner);

			if($type == 'g')
			{
				$members = $GLOBALS['phpgw']->acl->get_ids_for_location($this->owner, 1, 'phpgw_group');
			}
			else
			{
				$members = array_keys($this->grants);

				if (!in_array($this->owner,$members))
				{
					$members[] = $this->owner;
				}
			}
			foreach($members as $n => $uid)
			{
				if (!($this->grants[$uid] & PHPGW_ACL_READ))
				{
					unset($members[$n]);
				}
			}
			return $this->so->list_events_keyword($keywords,$members);
		}

                function getNextDow($start){
                    if ($start == 6)
                    {
                        return 0;
                    }
                    else
                    {
                        return $start + 1;
                    }
                }

                // TODO: Levar em consideracao no calculo das ocorrencias o parametro
                // $event['recur_interval']
                function expand_repetition($event){
                    $return = Array();

                    $event['recur_enddate']['hour'] = 24;
                    $enddate = $this->maketime($event['recur_enddate']);

                    $owner = $event['owner'];
                    $participants = $event['participants'];

            //        $new_event = Array();
            //        $new_event['owner'] = $owner;
            //        $new_event['participants'] = $participants;
            //        $new_event['start'] = $event['start'];
            //        $new_event['end'] = $event['end'];
            //
            //        $return[] = $new_event;

                    switch($event['recur_type']) {
                        case MCAL_RECUR_DAILY:
                            // add 1 day = 86400 sec
                            $starttime = $this->maketime($event['start']);
                            $endtime = $this->maketime($event['end']);

                            $offset = 86400;
                            for (; $starttime <= $enddate;
                                $starttime = $starttime+$offset,
                                $endtime = $endtime+$offset)
                            {
                                $new_event = Array();
                                $new_event['owner'] = $owner;
                                $new_event['participants'] = $participants;
                                $new_event['start'] = $starttime;
                                $new_event['end'] = $endtime;
                                $return[] = $new_event;
                            }

                            break;
                        case MCAL_RECUR_WEEKLY:

                            $setDow = (int)date('w',$this->maketime($event['start']));
                            $dowOffsets = Array();
                            $firstDow;
                            $firstDowOffset = 0;

                            // Initializing do
                            $mask = $this->wdays[$setDow];
                            $firstDayMismatch = False;
                            if (((int)$event['recur_data'] & $mask) == 0){
                                $firstDayMismatch = True;
                            }

                            $dow = $setDow;

                            for ($nextDow = $setDow; array_sum($dowOffsets) < 7;)
                            {

                                $nextDow = $this->getNextDow($nextDow);
                                $mask = $this->wdays[$nextDow];

                                if (((int)$event['recur_data'] & $mask) != 0)
                                {
                                    if ($firstDayMismatch and $firstDowOffset == 0)
                                    {
                                        if ($dow < $nextDow)
                                        {
                                            $firstDowOffset = $nextDow - $dow;
                                        }
                                        else if ($dow > $nextDow)
                                        {
                                            $firstDowOffset = (7 - $dow) + $nextDow;
                                        }
                                    }
                                    else
                                    {
                                        if ($dow < $nextDow)
                                        {
                                            $dowOffsets[] = $nextDow - $dow;
                                        }
                                        else if ($dow > $nextDow)
                                        {
                                            $dowOffsets[] = (7 - $dow) + $nextDow;
                                        }
                                        else
                                        {
                                            $dowOffsets[] = 7;
                                        }

                                    }

                                    $dow = $nextDow;
                                    if (!isset($firstDow))
                                    {
                                        $firstDow = $dow;
                                    }

                                }

            //                    $nextDow = $this->getNextDow($nextDow);
            //                    $mask = $this->wdays[$nextDow];

                            }

                            if ($firstDayMismatch)
                            {
                                if ($dow < $firstDow)
                                {
                                    $dowOffsets[] = $firstDow - $dow;
                                }
                                else if ($dow > $firstDow)
                                {
                                    $dowOffsets[] = (7 - $dow) + $firstDow;
                                }
                            }

                            // Gera os eventos
                            $starttime = $this->maketime($event['start']);
                            $endtime = $this->maketime($event['end']);
                            $offset = 86400;

                            $new_event = Array();
                            $new_event['owner'] = $owner;
                            $new_event['participants'] = $participants;
                            $new_event['start'] = $starttime;
                            $new_event['end'] = $endtime;
                            $return[] = $new_event;

                            if ($firstDayMismatch && $firstDowOffset != 0)
                            {
                                $multi = $firstDowOffset;

                                $new_event = Array();
                                $new_event['owner'] = $owner;
                                $new_event['participants'] = $participants;
                                $new_event['start'] = $starttime += $offset*$multi;
                                $new_event['end'] = $endtime += $offset*$multi;
                                $return[] = $new_event;
                            }

                            for ($i = 0, $multi = $dowOffsets[$i],
                                $starttime += $offset*$multi,
                                $endtime += $offset*$multi;
                                $starttime <= $enddate;
                                $i++,
                                $i = ($i < count($dowOffsets))?$i:0,
                                $multi = $dowOffsets[$i],
                                $starttime += $offset*$multi,
                                $endtime += $offset*$multi)
                            {
                                //error_log('loop infinito?');
                                $new_event = Array();
                                $new_event['owner'] = $owner;
                                $new_event['participants'] = $participants;
                                $new_event['start'] = $starttime; // + $offset*$multi;
                                $new_event['end'] = $endtime; // + $offset*$multi;
                                $return[] = $new_event;
                            }


                            break;
                        case MCAL_RECUR_MONTHLY_WDAY:
                            // Not working!!!
                            break;
                        case MCAL_RECUR_MONTHLY_MDAY:
                        case MCAL_RECUR_YEARLY:

                            for ($key = ($event['recur_type'] == MCAL_RECUR_YEARLY)?'year':'month',
                                $starttime = $event['start'], $endtime = $event['end'];
                                $this->maketime($starttime) <= $enddate;
                                $starttime['year'] = ($key == 'month' && $starttime['month'] == 12)?$starttime['year']+1:$starttime['year'],
                                $endtime['year'] = ($key == 'month' && $endtime['month'] == 12)?$endtime['year']+1:$endtime['year'],
                                $starttime[$key] = ($key == 'year')?$starttime[$key]+1:($key == 'month' && $starttime[$key] < 12)?$starttime[$key]+1:1,
                                $endtime[$key] = ($key == 'year')?$endtime[$key]+1:($key == 'month' && $endtime[$key] < 12)?$endtime[$key]+1:1)
                            {
                                $new_event = Array();
                                $new_event['owner'] = $owner;
                                $new_event['participants'] = $participants;
                                $new_event['start'] = $this->maketime($starttime);
                                $new_event['end'] = $this->maketime($endtime);
                                $return[] = $new_event;
                            }

                            break;
            //            default:
            //                return Array();

                    }

                    return $return;
                }

		function update( $params = '' )
		{
			if( isset($_REQUEST['back_calendar']) && isset($_REQUEST['Cancelarform']) )
			{
				$menuAction = explode("menuaction=", $_REQUEST['Cancelarform'] );
				$GLOBALS['phpgw']->redirect_link('/index.php','menuaction='.$menuAction[1]);
			}
			
			if(!is_object($GLOBALS['phpgw']->datetime))
			{
				$GLOBALS['phpgw']->datetime = createobject('phpgwapi.date_time');
			}

			$l_cal = (@isset($params['cal']) && $params['cal']?$params['cal']:$_POST['cal']);
			$l_participants = (@$params['participants']?$params['participants']:$_POST['participants']);
			
			$this->ex_participants = (@$params['ex_participants']?$params['ex_participants']:$_POST['ex_participants']);
			$this->ex_participants = implode(", ", array_map( "trim", explode(",", $this->ex_participants )));
			
			$l_categories = (@$params['categories']?$params['categories']:$_POST['categories']);
			$l_start = (@isset($params['start']) && $params['start']?$params['start']:$_POST['start']);
			$l_end = (@isset($params['end']) && $params['end']?$params['end']:$_POST['end']);
			$l_recur_enddate = (@isset($params['recur_enddate']) && $params['recur_enddate']?$params['recur_enddate']:$_POST['recur_enddate']);
			$from_mobile = ( isset( $params['from_mobile'] ) && $params['from_mobile'] ) || ( isset( $_POST['from_mobile'] ) && $_POST['from_mobile'] );
			
			$ui_return             = $from_mobile? "mobile.ui_mobilecalendar.edit" : "calendar.uicalendar.edit";
			$ui_index              = $from_mobile? "mobile.ui_mobilecalendar.index" : "calendar.uicalendar.index";
			$ui_overlap            = $from_mobile? "mobile.ui_mobilecalendar.overlap" : "calendar.uicalendar.overlap";
			$send_to_ui            = !( isset( $params['sendToUi'] ) && $params['sendToUi'] == '0' );
			$force_overlapping     = ( isset( $params['forceOverlapEvents'] ) && $params['forceOverlapEvents'] == '1' );
			
			print_debug('ID',$l_cal['id']);

			// don't wrap to the next day for no time
			if ($l_end['hour'] == 24 && $l_end['min'] == 0)
			{
				$l_end['hour'] = 23;
				$l_end['min'] = 59;
			}

			if(isset($_GET['readsess']))
			{
				$event = $this->restore_from_appsession();
				$event['title'] = stripslashes($event['title']);
				$event['description'] = stripslashes($event['description']);
				$event['ex_participants'] = stripslashes($event['ex_participants']);
				$datetime_check = $this->validate_update($event);
				if($datetime_check)
				{
					ExecMethod($ui_return,
						Array(
							'cd'		=> $datetime_check,
							'readsess'	=> 1
						)
					);
					if(!$from_mobile)
						$GLOBALS['phpgw']->common->phpgw_exit(True);
					else
						return;
				}
				$overlapping_events = False;
			}
			else
			{
				if((!$l_cal['id'] && !$this->check_perms(PHPGW_ACL_ADD)) ||
				   ($l_cal['id'] && !$this->check_perms(PHPGW_ACL_EDIT,$l_cal['id'])))
				{
					if ($this->xmlrpc)
					{
						$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['no_access'],$GLOBALS['xmlrpcstr']['no_access']);
					}
					if (!$send_to_ui)
					{
						return array(($l_cal['id']?1:2) => 'permission denied');
					}
					ExecMethod($ui_index);
					if(!$from_mobile)
						$GLOBALS['phpgw']->common->phpgw_exit();
					else
						return;
				}

				print_debug('Prior to fix_update_time()');
				$this->fix_update_time($l_start);
				$this->fix_update_time($l_end);
				if(!isset($l_cal['ex_participants']))
				{
					$l_cal['ex_participants'] = $this->ex_participants;
				}

				if(!isset($l_categories))
				{
					$l_categories = 0;
				}

				$is_public = ($l_cal['type'] != 'private' && $l_cal['type'] != 'privateHiddenFields');
				$this->so->event_init();
				$this->add_attribute('uid',$l_cal['uid']);
				$this->add_attribute('type',$l_cal['type']);
				if($l_cal['ex_participants']) {
					$this->add_attribute('ex_participants',$l_cal['ex_participants']);
				}
				if(count($l_categories) >= 2)
				{
					$this->so->set_category(implode(',',$l_categories));
				}
				else
				{
					$this->so->set_category(strval($l_categories[0]));
				}
				$this->so->set_title($l_cal['title']);
				$this->so->set_description($l_cal['description']);
				$this->so->set_ex_participants($l_cal['ex_participants']);
				$this->so->set_start($l_start['year'],$l_start['month'],$l_start['mday'],$l_start['hour'],$l_start['min'],0);
				$this->so->set_end($l_end['year'],$l_end['month'],$l_end['mday'],$l_end['hour'],$l_end['min'],0);
				$this->so->set_class($is_public);
				$this->so->add_attribute('reference',(@isset($l_cal['reference']) && $l_cal['reference']?$l_cal['reference']:0));
				$this->so->add_attribute('location',(@isset($l_cal['location']) && $l_cal['location']?$l_cal['location']:''));
				$this->so->add_attribute('repeat',(@isset($l_cal['recur_type']) && $l_cal['recur_type']?$l_cal['recur_type']:''));
				$this->so->add_attribute('alarmd',(@isset($l_cal['alarmdays'])?$l_cal['alarmdays']:0));
				$this->so->add_attribute('alarmh',(@isset($l_cal['alarmhours'])?$l_cal['alarmhours']:0));
				$this->so->add_attribute('alarmm',(@isset($l_cal['alarmminutes'])?$l_cal['alarmminutes']:0));
				
				$this->so->add_attribute('sms_check_owner',(isset($params['sms_check_owner'])? (bool)$params['sms_check_owner'] : isset($_POST['sms_check_owner'])));
				$this->so->add_attribute('sms_check_parts',(isset($params['sms_check_parts'])? (bool)$params['sms_check_parts'] : isset($_POST['sms_check_parts'])));
				
				if($l_cal['id'])
				{
					$this->so->add_attribute('id',$l_cal['id']);
				}

				if($l_cal['rpt_use_end'] != 'y')
				{
					$l_recur_enddate['year'] = 0;
					$l_recur_enddate['month'] = 0;
					$l_recur_enddate['mday'] = 0;
				}
				elseif (isset($l_recur_enddate['str']))
				{
					$l_recur_enddate = $this->jscal->input2date($l_recur_enddate['str'],False,'mday');
				}

				switch((int)$l_cal['recur_type'])
				{
					case MCAL_RECUR_NONE:
						$this->so->set_recur_none();

						$repetido = (int)$l_cal['recur_type']; // recebe o tipo de repeticao;
						$this->so->add_attribute('recur_type',(@isset($repetido) && $repetido?$repetido:0));
						$this->so->add_attribute('rpt_end_use',(@isset($l_cal['rpt_use_end']) && $l_cal['rpt_use_end']?$l_cal['rpt_use_end']:''));

						break;
					case MCAL_RECUR_DAILY:
						$this->so->set_recur_daily((int)$l_recur_enddate['year'],(int)$l_recur_enddate['month'],(int)$l_recur_enddate['mday'],(int)$l_cal['recur_interval']);

						$repetido = (int)$l_cal['recur_type']; // recebe o tipo de repeticao;
						$this->so->add_attribute('recur_type',(@isset($repetido) && $repetido?$repetido:0));

						$tmp_init = explode("/",$_POST['start']['str']); // recebe a data inicial da repeticao;
						$init_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_init[1]),$tmp_init[0],$tmp_init[2]); // transforma a data inicial + hora + minuto em UNIX timestamp;

						if($l_cal['rpt_use_end'] == 'y') // verifica se foi indicada data final da repeticao, se sim:
						{
							$this->so->add_attribute('rpt_end_use',(@isset($l_cal['rpt_use_end']) && $l_cal['rpt_use_end']?$l_cal['rpt_use_end']:''));
							
							$tmp_end = explode("/",$_POST['recur_enddate']['str']); // recebe data final da repeticao;
							$end_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_end[1]),$tmp_end[0],$tmp_end[2]); // transforma a data inicial + hora e minuto de inicio da repeticao em UNIX timestamp;

						}else { // se nao existe data final da repeticao (agendamento infinito):

							$end_rept = 0; // variavel recebe zero e nao sera adicionado nenhum alarme para o agendamento;
						}
						break;
					case MCAL_RECUR_WEEKLY:

						$repetido = (int)$l_cal['recur_type']; // recebe o tipo de repeticao;
						$this->so->add_attribute('recur_type',(@isset($repetido) && $repetido?$repetido:0));

						$tmp_init = explode("/",$_POST['start']['str']); // recebe a data inicial da repeticao;
						$init_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_init[1]),$tmp_init[0],$tmp_init[2]); // transforma a data inicial + hora + minuto em UNIX timestamp;

						if($l_cal['rpt_use_end'] == 'y') // verifica se foi indicada data final da repeticao, se sim:
						{

							$this->so->add_attribute('rpt_end_use',(@isset($l_cal['rpt_use_end']) && $l_cal['rpt_use_end']?$l_cal['rpt_use_end']:''));
							$tmp_end = explode("/",$_POST['recur_enddate']['str']); // recebe data final da repeticao;
							$end_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_end[1]),$tmp_end[0],$tmp_end[2]); // transforma a data inicial + hora e minuto de inicio da repeticao em UNIX timestamp;

						}else { // se nao existe data final da repeticao (agendamento infinito):

							$end_rept = 0; // variavel recebe zero e nao sera adicionado nenhum alarme para o agendamento;
						}

						foreach(array('rpt_sun','rpt_mon','rpt_tue','rpt_wed','rpt_thu','rpt_fri','rpt_sat') as $rpt_day)
						{
							$l_cal['recur_data'] += (int)$l_cal[$rpt_day];
						}
						if (is_array($l_cal['rpt_day']))
						{
							foreach ($l_cal['rpt_day'] as $mask)
							{
								$l_cal['recur_data'] |= (int)$mask;
								$rpt_wdays = $l_cal['recur_data']; // recebe os dias da semana (somatorio) para repetir o alarme;
							}
						}
						$this->so->add_attribute('rpt_wdays',(@isset($rpt_wdays) && $rpt_wdays?$rpt_wdays:''));
						$this->so->set_recur_weekly((int)$l_recur_enddate['year'],(int)$l_recur_enddate['month'],(int)$l_recur_enddate['mday'],(int)$l_cal['recur_interval'],$l_cal['recur_data']);
						break;
					case MCAL_RECUR_MONTHLY_WDAY:
						$var_wday = true;
					case MCAL_RECUR_MONTHLY_MDAY:
						if ($var_wday)
						{
							$this->so->set_recur_monthly_wday((int)$l_recur_enddate['year'],(int)$l_recur_enddate['month'],(int)$l_recur_enddate['mday'],(int)$l_cal['recur_interval']);
						}
						else
						{
							$this->so->set_recur_monthly_mday((int)$l_recur_enddate['year'],(int)$l_recur_enddate['month'],(int)$l_recur_enddate['mday'],(int)$l_cal['recur_interval']);
						}
						
						$repetido = (int)$l_cal['recur_type']; // recebe o tipo de repeticao;
						$this->so->add_attribute('recur_type',(@isset($repetido) && $repetido?$repetido:0));

						$tmp_init = explode("/",$_POST['start']['str']); // recebe a data inicial da repeticao;
						$init_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_init[1]),$tmp_init[0],$tmp_init[2]); // transforma a data inicial + hora + minuto em UNIX timestamp;

						if($l_cal['rpt_use_end'] == 'y') // verifica se foi indicada data final da repeticao, se sim:
						{
							$this->so->add_attribute('rpt_end_use',(@isset($l_cal['rpt_use_end']) && $l_cal['rpt_use_end']?$l_cal['rpt_use_end']:''));
							$tmp_end = explode("/",$_POST['recur_enddate']['str']); // recebe data final da repeticao;
							$end_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_end[1]),$tmp_end[0],$tmp_end[2]); // transforma a data inicial + hora e minuto de inicio da repeticao em UNIX timestamp;

						}else { // se nao existe data final da repeticao (agendamento infinito):

							$end_rept = 0; // variavel recebe zero e nao sera adicionado nenhum alarme para o agendamento;
						}
						break;
					case MCAL_RECUR_YEARLY:
						$this->so->set_recur_yearly((int)$l_recur_enddate['year'],(int)$l_recur_enddate['month'],(int)$l_recur_enddate['mday'],(int)$l_cal['recur_interval']);

						$repetido = (int)$l_cal['recur_type']; // recebe o tipo de repeticao;
						$this->so->add_attribute('recur_type',(@isset($repetido) && $repetido?$repetido:0));

						$tmp_init = explode("/",$_POST['start']['str']); // recebe a data inicial da repeticao;
						$init_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_init[1]),$tmp_init[0],$tmp_init[2]); // transforma a data inicial + hora + minuto em UNIX timestamp;

						if($l_cal['rpt_use_end'] == 'y') // verifica se foi indicada data final da repeticao, se sim:
						{
							$this->so->add_attribute('rpt_end_use',(@isset($l_cal['rpt_use_end']) && $l_cal['rpt_use_end']?$l_cal['rpt_use_end']:''));
							$tmp_end = explode("/",$_POST['recur_enddate']['str']); // recebe data final da repeticao;
							$end_rept = mktime($_POST['start']['hour'],$_POST['start']['min'],0,intval($tmp_end[1]),$tmp_end[0],$tmp_end[2]); // transforma a data inicial + hora e minuto de inicio da repeticao em UNIX timestamp;

						}else { // se nao existe data final da repeticao (agendamento infinito):

							$end_rept = 0; // variavel recebe zero e nao sera adicionado nenhum alarme para o agendamento;
						}
						break;
				}

				if($l_participants)
				{
					$parts = $l_participants;
					$minparts = min($l_participants);
					$part = Array();
					for($i=0;$i<count($parts);$i++)
					{
						if (($accept_type = substr($parts[$i],-1,1)) == '0' || (int)$accept_type > 0)
						{
							$accept_type = 'U';
						}
						$acct_type = $GLOBALS['phpgw']->accounts->get_type((int)$parts[$i]);
						if($acct_type == 'u')
						{
							$part[(int)$parts[$i]] = $accept_type;
						}
						elseif($acct_type == 'g')
						{
							$part[(int)$parts[$i]] = $accept_type;
							$groups[] = $parts[$i];
							/* This pulls ALL users of a group and makes them as participants to the event */
							/* I would like to turn this back into a group thing. */
							$acct = CreateObject('phpgwapi.accounts',(int)$parts[$i]);
							$members = $acct->member((int)$parts[$i]);
							unset($acct);
							if($members == False)
							{
								continue;
							}
							while($member = each($members))
							{
								$part[$member[1]['account_id']] = $accept_type;
							}
						}
					}
				}
				else
				{
					$part = False;
				}

				if($part)
				{
					@reset($part);
					while(list($key,$accept_type) = each($part))
					{
						$this->so->add_attribute('participants',$accept_type,(int)$key);
					}
				}

				if($groups)
				{
					@reset($groups);
					$this->so->add_attribute('groups',(int)$group_id);
				}

				$event = $this->get_cached_event();
				if(!is_int($minparts))
				{
					$minparts = $this->owner;
				}
				if(!@isset($event['participants'][$l_cal['owner']]))
				{
					$this->so->add_attribute('owner',$minparts);
				}
				else
				{
					$this->so->add_attribute('owner',$l_cal['owner']);
				}
				$this->so->add_attribute('priority',$l_cal['priority']);

				foreach($l_cal as $name => $value)
				{
					if ($name[0] == '#')	// Custom field
					{
						$this->so->add_attribute($name,stripslashes($value));
					}
				}
				if (isset($_POST['preserved']) && is_array($preserved = unserialize(stripslashes($_POST['preserved']))))
				{
					foreach($preserved as $name => $value)
					{
						switch($name)
						{
							case 'owner':
								$this->so->add_attribute('participants',$value,$l_cal['owner']);
								break;
							default:
								$this->so->add_attribute($name,str_replace(array('&amp;','&quot;','&lt;','&gt;'),array('&','"','<','>'),$value));
						}
					}
				}
				$event = $this->get_cached_event();

				if ($l_cal['alarmdays'] > 0 || $l_cal['alarmhours'] > 0 ||
						$l_cal['alarmminutes'] > 0)
				{
					$offset = ($l_cal['alarmdays'] * 24 * 3600) +
						($l_cal['alarmhours'] * 3600) + ($l_cal['alarmminutes'] * 60);

					$time = $this->maketime($event['start']) - $offset  +  $GLOBALS['phpgw']->datetime->tz_offset;

					$event['alarm'][] = Array(
						'time'    => $time,
						'offset'  => $offset,
						'owner'   => $this->owner,
						'enabled' => 1,
						'repeat'  => $repetido, // para repetir alarme;
						'init_rept' => $init_rept, // inicio repeticao;
						'end_rept' => $end_rept, // fim repeticao;
						'rpt_wdays' => $rpt_wdays // dias repeticao da semana;
					);
				}

				if( array_key_exists('webconf', $l_cal) )
				{
					$event['webconf'] = true;
				}

				$this->store_to_appsession($event);
				$datetime_check = $this->validate_update($event);

				print_debug('bo->validated_update() returnval',$datetime_check);
				if($datetime_check)
				{
					if (!$send_to_ui)
					{
						return array($datetime_check => 'invalid input data');
					}
					ExecMethod($ui_return,
						Array(
							'cd'		=> $datetime_check,
							'readsess'	=> 1
						)
					);
					if(!$from_mobile)
						$GLOBALS['phpgw']->common->phpgw_exit(True);
					else
						return;
				}

				if($event['id'])
				{
					$event_ids[] = $event['id'];
				}
				if($event['reference'])
				{
					$event_ids[] = $event['reference'];
				}

				if( isset($repetido) and $repetido > 0 )
				{
                    $events = $this->expand_repetition($event);
                    
                    $overlapping_events = False;
                    
                    foreach($events as $evt)
                    {
                        $overlap = $this->overlap(
                            $evt['start'],
                            $evt['end'],
                            $evt['participants'],
                            $evt['owner'],
                            $event_ids);
                        
                        if( $overlap )
                        {
                            if (!$overlapping_events)
                            {
                                $overlapping_events = Array();
                            }
                            array_push($overlapping_events, $overlap);
                        }
                    }
                }
                else
                {
                    $overlapping_events = $this->overlap(
                        $this->maketime($event['start']),
                        $this->maketime($event['end']),
                        $event['participants'],
                        $event['owner'],
                        $event_ids
                    );
                }
			}
			
			if( !$this->ex_participants ){ $this->ex_participants = $event['ex_participants']; }			

			if  ( ($force_overlapping == false) && ($overlapping_events) ) 
			{
				if($send_to_ui)
				{
					$event['ex_participants'] = $this->ex_participants;
					unset($GLOBALS['phpgw_info']['flags']['noheader']);
					unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
					ExecMethod($ui_overlap,
						array(
							'o_events'		=> $overlapping_events,
							'this_event'	=> $event
						)
					);
					
					if(!$from_mobile)
					{
						$GLOBALS['phpgw']->common->phpgw_exit(True);
					}
					else
					{
						return;
					}
				}
				else
				{
					return $overlapping_events;
				}
			}
			else
			{
				if( !$event['id'] )
				{
					$this->so->add_entry($event);
					
					$get_cached_event = $this->get_cached_event();

					// WebConf ?
					$async = null;

					if( isset($l_cal['webconf']) )
					{
						$textWebConf = "<div style='border:1px dashed #cecece;padding:5px;'>".
										"<h3 style='font-weight:bold;color:red;text-align:center;'>Aten&ccedil;&atilde;o</h3>".
										"<p>Este evento foi marcado com a op&ccedil;&atilde;o para utilizar o sistema de CONFER&Ecirc;NCIA pela WEB. ".
											"Voc&ecirc; receber&aacute; um email minutos antes do evento contendo um link para acessar esta confer&ecirc;ncia".
										"</p>".
										"</div>";
						
						if(!is_object($GLOBALS['phpgw']->asyncservice)){ $GLOBALS['phpgw']->asyncservice = CreateObject('phpgwapi.asyncservice'); }
						
						$async = &$GLOBALS['phpgw']->asyncservice;

						$time = intval(mktime($get_cached_event['start']['hour'],
											$get_cached_event['start']['min'],
											$get_cached_event['start']['sec'],
											$get_cached_event['start']['month'],
											$get_cached_event['start']['mday'],
											$get_cached_event['start']['year']
										));
						
						$now = intval( time() );

						$timeEvent = ( ( $time - 600 ) < $now ) ? $time : ( $time - 600 );

						$async->set_timer( $timeEvent,
											'webconf:'.$get_cached_event['id'].':0' ,
											'calendar.webconference.createMeeting',
											$get_cached_event
										);

						$get_cached_event['webconf'] = $textWebConf;
					}
					
					$this->send_update(MSG_ADDED,$event['participants'],'',$get_cached_event,false,'update');

					print_debug('New Event ID',$event['id']);
				}
				else
				{
					print_debug('Updating Event ID',$event['id']);
					$new_event = $event;
					$old_event = $this->read_entry($event['id']);
					// if old event has alarm and the start-time changed => update them
					//echo "<p>checking ".count($old_event['alarm'])." alarms of event #$event[id] start moved from ".print_r($old_event['start'],True)." to ".print_r($event['start'],True)."</p>\n";
					if ($old_event['alarm'] &&
						$this->maketime($old_event['start']) != $this->maketime($event['start']))
					{
						$this->so->delete_alarms($old_event['id']);
						foreach($old_event['alarm'] as $id => $alarm)
						{
							$alarm['time'] = $this->maketime($event['start']) - $alarm['offset'];
							$event['alarm'][] = $alarm;
						}
						//echo "updated alarms<pre>".print_r($event['alarm'],True)."</pre>\n";
					}
					$this->so->cal->event = $event;
					$this->so->add_entry($event);
					$this->prepare_recipients($new_event,$old_event);
				}

				$date = sprintf("%04d%02d%02d",$event['start']['year'],$event['start']['month'],$event['start']['mday']);
				if($send_to_ui)
				{
					$this->read_sessiondata();
					if ($this->return_to)
					{
						$GLOBALS['phpgw']->redirect_link('/index.php','menuaction='.$this->return_to);
						$GLOBALS['phpgw']->common->phpgw_exit();
					}
					Execmethod($ui_index);
				}
				else
				{
					return (int)$event['id'];
				}
			}
			return True;
		}

		/* Private functions */
		function read_holidays($year=0)
		{
			if(!$year)
			{
				$year = $this->year;
			}
			$holiday = CreateObject('calendar.boholiday');
			$holiday->prepare_read_holidays($year,$this->owner);
			$this->cached_holidays = $holiday->read_holiday();
			unset($holiday);
		}

		function user_is_a_member($event,$user)
		{
			@reset($event['participants']);
			$uim = False;
			$security_equals = $GLOBALS['phpgw']->accounts->membership($user);
			while(!$uim && $event['participants'] && $security_equals && list($participant,$status) = each($event['participants']))
			{
				if($GLOBALS['phpgw']->accounts->get_type($participant) == 'g')
				{
					@reset($security_equals);
					while(list($key,$group_info) = each($security_equals))
					{
						if($group_info['account_id'] == $participant)
						{
							return True;
							$uim = True;
						}
					}
				}
			}
			return $uim;
		}

		function maketime($time)
		{
			return mktime(intval($time['hour']),intval($time['min']),intval($time['sec']),intval($time['month']),intval($time['mday']),intval($time['year']));
		}

		/*!
		@function time2array
		@abstract returns a date-array suitable for the start- or endtime of an event from a timestamp
		@syntax time2array($time,$alarm=0)
		@param $time the timestamp for the values of the array
		@param $alarm (optional) alarm field of the array, defaults to 0
		@author ralfbecker
		*/
		function time2array($time,$alarm = 0)
		{
			return array(
				'year'  => (int)(date('Y',$time)),
				'month' => (int)(date('m',$time)),
				'mday'  => (int)(date('d',$time)),
				'hour'  => (int)(date('H',$time)),
				'min'   => (int)(date('i',$time)),
				'sec'   => (int)(date('s',$time)),
				'alarm' => (int)($alarm)
			);
		}

		/*!
		@function set_recur_date
		@abstract set the start- and enddates of a recuring event for a recur-date
		@syntax set_recur_date(&$event,$date)
		@param $event the event which fields to set (has to be the original event for start-/end-times)
		@param $date  the recuring date in form 'Ymd', eg. 20030226
		@author ralfbecker
		*/
		function set_recur_date(&$event,$date)
		{
			$org_start = $this->maketime($event['start']);
			$org_end   = $this->maketime($event['end']);
			$start = mktime($event['start']['hour'],$event['start']['min'],0,substr($date,4,2),substr($date,6,2),substr($date,0,4));
			$end   = $org_end + $start - $org_start;
			$event['start'] = $this->time2array($start);
			$event['end']   = $this->time2array($end);
		}

		function fix_update_time(&$time_param)
		{
			if (isset($time_param['str']))
			{
				if (!is_object($this->jscal))
				{
					$this->jscal = CreateObject('phpgwapi.jscalendar');
				}
				$time_param += $this->jscal->input2date($time_param['str'],False,'mday');
				unset($time_param['str']);
			}
			if ($this->prefs['common']['timeformat'] == '12')
			{
				if ($time_param['ampm'] == 'pm')
				{
					if ($time_param['hour'] <> 12)
					{
						$time_param['hour'] += 12;
					}
				}
				elseif ($time_param['ampm'] == 'am')
				{
					if ($time_param['hour'] == 12)
					{
						$time_param['hour'] -= 12;
					}
				}

				if($time_param['hour'] > 24)
				{
					$time_param['hour'] -= 12;
				}
			}
		}

		function validate_update($event)
		{
			$error = 0;

			// do a little form verifying
			if (!count($event['participants']))
			{
				$error = 43;
			}
			elseif ($event['type'] != 'hourAppointment' && $event['title'] == '')
			{
				$error = 40;
			}
			elseif ($event['repeat'] != 0)
			{
				if( $event['rpt_end_use'] != 'y')
				{
					$error = 46;
				}
				
				if ($event['repeat'] == 2)
				{
	    				if($event['rpt_wdays'] == '') {
	        				$error = 44;
	            			}
					if( $event['rpt_end_use'] != 'y')
					{
						$error = 46;
					}

	        		if (  ( isset($event['alarmd'])  && ($event['alarmd'] != 0) ) || ( isset($event['alarmh'])  && ($event['alarmh'] != 0) ) || ( isset($event['alarmm'])  && ($event['alarmm'] != 0) ) )
	        		{
	                	if( isset($event['recur_type']) && ($event['recur_type'] != 0) )
	        			{
	                		if( $event['rpt_end_use'] != 'y')
	            			{
	                			$error = 45;
	    		    		}
	        			}
	        		}
				}

		  		if (  ( isset($event['alarmd'])  && ($event['alarmd'] != 0) ) || ( isset($event['alarmh'])  && ($event['alarmh'] != 0) ) || ( isset($event['alarmm'])  && ($event['alarmm'] != 0) ) )
	            {
	            	if( isset($event['recur_type']) && ($event['recur_type'] != 0) )
	            	{
	            		if( $event['rpt_end_use'] != 'y')
	            		{
	            			$error = 45;
	            		}
	            	}
	            }
			}
			elseif (($GLOBALS['phpgw']->datetime->time_valid($event['start']['hour'],$event['start']['min'],0) == False) || ($GLOBALS['phpgw']->datetime->time_valid($event['end']['hour'],$event['end']['min'],0) == False))
			{
				$error = 41;
			}
			elseif (($GLOBALS['phpgw']->datetime->date_valid($event['start']['year'],$event['start']['month'],$event['start']['mday']) == False) || ($GLOBALS['phpgw']->datetime->date_valid($event['end']['year'],$event['end']['month'],$event['end']['mday']) == False) || ($GLOBALS['phpgw']->datetime->date_compare($event['start']['year'],$event['start']['month'],$event['start']['mday'],$event['end']['year'],$event['end']['month'],$event['end']['mday']) == 1))
			{
				$error = 42;
			}
			elseif ($GLOBALS['phpgw']->datetime->date_compare($event['start']['year'],$event['start']['month'],$event['start']['mday'],$event['end']['year'],$event['end']['month'],$event['end']['mday']) == 0)
			{
				if ($GLOBALS['phpgw']->datetime->time_compare($event['start']['hour'],$event['start']['min'],0,$event['end']['hour'],$event['end']['min'],0) == 1)
				{
					$error = 47;
				}
			}
			return $error;
		}

		/*!
		@function participants_not_rejected($participants,$event)
		@abstract checks if any of the $particpants participates in $event and has not rejected it
		*/
		function participants_not_rejected($participants,$event)
		{
			//echo "participants_not_rejected()<br>participants =<pre>"; print_r($participants); echo "</pre><br>event[participants]=<pre>"; print_r($event['participants']); echo "</pre>\n";
			foreach($participants as $uid => $status)
			{
				//echo "testing event[participants][uid=$uid] = '".$event['participants'][$uid]."'<br>\n";
				if (isset($event['participants'][$uid]) && $event['participants'][$uid] != 'R' &&
					$status != 'R')
				{
					return True;	// found not rejected participant in event
				}
			}
			return False;
		}
		
		function participants_unanswered($participants,$event)
		{
			foreach($participants as $uid => $status)
			{				
				if (isset($event['participants'][$uid]) && $event['participants'][$uid] == 'U' && $status == 'U')
				{
					return False;
				}
			}
			return True;
		}

		function overlap($starttime,$endtime,$participants,$owner=0,$id=0,$restore_cache=False)
		{
			if($restore_cache)
			{
				$temp_cache_events = $this->cached_events;
			}

			$temp_start = (int)(date('Ymd',$starttime));
			$temp_start_time = (int)(date('Hi',$starttime));
			$temp_end = (int)(date('Ymd',$endtime));
			$temp_end_time = (int)(date('Hi',$endtime));
			
			$users = array();
			
			if( count($participants) )
			{
				while(list($user,$status) = each($participants))
				{
					$users[] = $user;
				}
			}
			else
			{
				$users[] = $this->owner;
			}

			$possible_conflicts = $this->store_to_cache(
				array(
					'smonth'	=> substr(strval($temp_start),4,2),
					'sday'		=> substr(strval($temp_start),6,2),
					'syear'		=> substr(strval($temp_start),0,4),
					'emonth'	=> substr(strval($temp_end),4,2),
					'eday'		=> substr(strval($temp_end),6,2),
					'eyear'		=> substr(strval($temp_end),0,4),
					'owner'		=> $users
				)
			);

			if($possible_conflicts[$temp_start] || $possible_conflicts[$temp_end])
			{
				if($temp_start == $temp_end)
				{
					@reset($possible_conflicts[$temp_start]);

					while(list($key,$event) = each($possible_conflicts[$temp_start]))
					{
						$found = False;
						if($id)
						{
							@reset($id);
							while(list($key,$event_id) = each($id))
							{
								if($event['id'] == $event_id)
								{
									$found = True;
								}
							}
						}
						
						if( !$found )
						{
							$temp_event_start = sprintf("%d%02d",$event['start']['hour'],$event['start']['min']);
							$temp_event_end = sprintf("%d%02d",$event['end']['hour'],$event['end']['min']);
							if(($temp_start_time <= $temp_event_start &&
								$temp_end_time > $temp_event_start &&
								$temp_end_time <= $temp_event_end ||
								$temp_start_time >= $temp_event_start &&
								$temp_start_time < $temp_event_end &&
								$temp_end_time >= $temp_event_end ||
								$temp_start_time <= $temp_event_start &&
								$temp_end_time >= $temp_event_end ||
								$temp_start_time >= $temp_event_start &&
								$temp_end_time <= $temp_event_end)) 
							{
								if( $this->participants_not_rejected($participants,$event) )
									$retval[] = $event['id'];

								if( $this->participants_unanswered($participants,$event) )
									$retval[] = $event['id'];		

								$retval = array_unique( $retval );
							}
						}
					}
				}
			}
			else
			{
				$retval = False;
			}

			if($restore_cache)
			{
				$this->cached_events = $temp_cache_events;
			}

			return $retval;
		}

		/*!
		@function check_perms( )
		@syntax check_perms($needed,$event=0,$other=0)
		@abstract Checks if the current user has the necessary ACL rights
		@author ralfbecker
		@discussion The check is performed on an event or general on the cal of an other user
		@param $needed necessary ACL right: PHPGW_ACL_{READ|EDIT|DELETE}
		@param $event event as array or the event-id or 0 for general check
		@param $other uid to check (if event==0) or 0 to check against $this->owner
		@note Participating in an event is considered as haveing read-access on that event, \
			even if you have no general read-grant from that user.
		*/
		function check_perms($needed,$event=0,$other=0)
		{
			$event_in = $event;
			
			if (is_int($event) && $event == 0)
			{
				$owner = $other > 0 ? $other : $this->owner;
			}
			else
			{
				if (!is_array($event))
				{
					$event = $this->so->read_entry((int) $event);
				}
				if (!is_array($event))
				{
					if ($this->xmlrpc)
					{
						$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['not_exist'],$GLOBALS['xmlrpcstr']['not_exist']);
					}
					return False;
				}
				$owner = $event['owner'];
				$private = $event['public'] == False || $event['public'] == 0;
			}
			$user = $GLOBALS['phpgw_info']['user']['account_id'];
			$grants = $this->grants[$owner];

			if (is_array($event) && $needed == PHPGW_ACL_READ)
			{
				// Check if the $user is one of the participants or has a read-grant from one of them
				//
				foreach($event['participants'] as $uid => $accept)
				{
					if ($this->grants[$uid] & PHPGW_ACL_READ || $uid == $user)
					{
						$grants |= PHPGW_ACL_READ;
						// if the $user is one of the participants read-grant private (restricted) too.
						if($uid == $user || ($this->grants[$uid] & PHPGW_ACL_PRIVATE)) {
							$grants |= PHPGW_ACL_PRIVATE;
						}
						//break;
					}
				}
			}

			if ($GLOBALS['phpgw']->accounts->get_type($owner) == 'g' && $needed == PHPGW_ACL_ADD)
			{
				$access = False;	// a group can't be the owner of an event
			}
			else
			{
				$access = $user == $owner || $grants & $needed && (!$private || $grants & PHPGW_ACL_PRIVATE);
			}
			//echo "<p>".function_backtrace()." check_perms($needed,$event_id,$other) for user $user and needed_acl $needed: event='$event[title]': owner=$owner, private=$private, grants=$grants ==> access=$access</p>\n";

			return $access;
		}


		function display_status($user_status)
		{
			if(@$this->prefs['calendar']['display_status'] && $user_status)
			{
				$user_status = substr($this->get_long_status($user_status),0,1);

				return ' ('.$user_status.')';
			}
			else
			{
				return '';
			}
		}

		function get_long_status($status_short)
		{
			switch ($status_short)
			{
				case 'A':
					$status = lang('Accepted');
					break;
				case 'R':
					$status = lang('Rejected');
					break;
				case 'T':
					$status = lang('Tentative');
					break;
				case 'U':
					$status = lang('No Response');
					break;
			}
			return $status;
		}

		function is_private($event,$owner)
		{
			if($owner == 0)
			{
				$owner = $this->owner;
			}
			if ($owner == $GLOBALS['phpgw_info']['user']['account_id'] || ($event['public']==1) || ($this->check_perms(PHPGW_ACL_PRIVATE,$event) && $event['public']==0) || $event['owner'] == $GLOBALS['phpgw_info']['user']['account_id'])
			{
				return False;
			}
			elseif($event['public'] == 0)
			{
				return True;
			}
			elseif($event['public'] == 2)
			{
				$is_private = True;
				$groups = $GLOBALS['phpgw']->accounts->membership($owner);
				while (list($key,$group) = each($groups))
				{
					if (strpos(' '.implode(',',$event['groups']).' ',$group['account_id']))
					{
						return False;
					}
				}
			}
			else
			{
				return False;
			}

			return $is_private;
		}

		function get_short_field($event,$is_private=True,$field='')
		{
			if($is_private)
			{
				return lang('private');
			}

// cut off too long titles
			elseif(strlen($event[$field]) > 19 && !$this->printer_friendly && $field=="title")
//			elseif(strlen($event[$field]) > 19 && $this->printer_friendly)
			{
// we dont use currently 160304
//				return substr($event[$field], 0 , 19) . '&nbsp;...';
				return $event[$field];
			}
			else
			{
				return $event[$field];
			}
		}

		function long_date($first,$last=0)
		{
			if (!is_array($first))
			{
				$first = $this->time2array($raw = $first);
				$first['raw'] = $raw;
				$first['day'] = $first['mday'];
			}
			if ($last && !is_array($last))
			{
				$last = $this->time2array($raw = $last);
				$last['raw'] = $raw;
				$last['day'] = $last['mday'];
			}
			$datefmt = $this->prefs['common']['dateformat'];

			$month_before_day = strtolower($datefmt[0]) == 'm' ||
				strtolower($datefmt[2]) == 'm' && $datefmt[4] == 'd';

			for ($i = 0; $i < 5; $i += 2)
			{
				switch($datefmt[$i])
				{
					case 'd':
						$range .= $first['day'] . ($datefmt[1] == '.' ? '.' : '');
						if ($first['month'] != $last['month'] || $first['year'] != $last['year'])
						{
							if (!$month_before_day)
							{
								$range .= ' '.lang(strftime('%B',$first['raw']));
							}
							if ($first['year'] != $last['year'] && $datefmt[0] != 'Y')
							{
								$range .= ($datefmt[0] != 'd' ? ', ' : ' ') . $first['year'];
							}
							if (!$last)
							{
								return $range;
							}
							$range .= ' - ';

							if ($first['year'] != $last['year'] && $datefmt[0] == 'Y')
							{
								$range .= $last['year'] . ', ';
							}

							if ($month_before_day)
							{
								$range .= lang(strftime('%B',$last['raw']));
							}
						}
						else
						{
							$range .= ' - ';
						}
						$range .= ' ' . $last['day'] . ($datefmt[1] == '.' ? '.' : '');
						break;
					case 'm':
					case 'M':
						$range .= ' '.lang(strftime('%B',$month_before_day ? $first['raw'] : $last['raw'])) . ' ';
						break;
					case 'Y':
						$range .= ($datefmt[0] == 'm' ? ', ' : ' ') . ($datefmt[0] == 'Y' ? $first['year'].($datefmt[2] == 'd' ? ', ' : ' ') : $last['year'].' ');
						break;
				}
			}
			return $range;
		}

		function get_week_label()
		{
			$first = $GLOBALS['phpgw']->datetime->gmtdate($GLOBALS['phpgw']->datetime->get_weekday_start($this->year, $this->month, $this->day));
			$last = $GLOBALS['phpgw']->datetime->gmtdate($first['raw'] + 518400);

			return ($this->long_date($first,$last));
		}

		function normalizeminutes(&$minutes)
		{
			$hour = 0;
			$min = (int)$minutes;
			if($min >= 60)
			{
				$hour += $min / 60;
				$min %= 60;
			}
			settype($minutes,'integer');
			$minutes = $min;
			return $hour;
		}

		function splittime($time,$follow_24_rule=True)
		{
			$temp = array('hour','minute','second','ampm');
			$time = strrev($time);
			$second = (int)(strrev(substr($time,0,2)));
			$minute = (int)(strrev(substr($time,2,2)));
			$hour   = (int)(strrev(substr($time,4)));
			$hour += $this->normalizeminutes($minute);
			$temp['second'] = $second;
			$temp['minute'] = $minute;
			$temp['hour']   = $hour;
			$temp['ampm']   = '  ';
			if($follow_24_rule == True)
			{
				if ($this->prefs['common']['timeformat'] == '24')
				{
					return $temp;
				}

				$temp['ampm'] = 'am';

				if ((int)$temp['hour'] > 12)
				{
					$temp['hour'] = (int)((int)$temp['hour'] - 12);
					$temp['ampm'] = 'pm';
				}
				elseif ((int)$temp['hour'] == 12)
				{
					$temp['ampm'] = 'pm';
				}
			}
			return $temp;
		}

		function get_exception_array($exception_str='')
		{
			$exception = Array();
			if(strpos(' '.$exception_str,','))
			{
				$exceptions = explode(',',$exception_str);
				for($exception_count=0;$exception_count<count($exceptions);$exception_count++)
				{
					$exception[] = (int)$exceptions[$exception_count];
				}
			}
			elseif($exception_str != '')
			{
				$exception[] = (int)$exception_str;
			}
			return $exception;
		}

		function build_time_for_display($fixed_time)
		{
			$time = $this->splittime($fixed_time);
			$str = $time['hour'].':'.((int)$time['minute']<=9?'0':'').$time['minute'];

			if ($this->prefs['common']['timeformat'] == '12')
			{
				$str .= ' ' . $time['ampm'];
			}

			return $str;
		}

		function sort_event($event,$date)
		{
			$inserted = False;
			if(isset($event['recur_exception']))
			{
				$event_time = mktime($event['start']['hour'],$event['start']['min'],0,(int)(substr($date,4,2)),(int)(substr($date,6,2)),(int)(substr($date,0,4))) - $GLOBALS['phpgw']->datetime->tz_offset;
				while($inserted == False && list($key,$exception_time) = each($event['recur_exception']))
				{
					if($this->debug)
					{
						echo '<!-- checking exception datetime '.$exception_time.' to event datetime '.$event_time.' -->'."\n";
					}
					if($exception_time == $event_time)
					{
						$inserted = True;
					}
				}
			}
			if($this->cached_events[$date] && $inserted == False)
			{

				if($this->debug)
				{
					echo '<!-- Cached Events found for '.$date.' -->'."\n";
				}
				$year = substr($date,0,4);
				$month = substr($date,4,2);
				$day = substr($date,6,2);

				if($this->debug)
				{
					echo '<!-- Date : '.$date.' Count : '.count($this->cached_events[$date]).' -->'."\n";
				}

				for($i=0;$i<count($this->cached_events[$date]);$i++)
				{
					if($this->cached_events[$date][$i]['id'] == $event['id'] || $this->cached_events[$date][$i]['reference'] == $event['id'])
					{
						if($this->debug)
						{
							echo '<!-- Item already inserted! -->'."\n";
						}
						$inserted = True;
						break;
					}
					/* This puts all spanning events across multiple days up at the top. */
					if($this->cached_events[$date][$i]['recur_type'] == MCAL_RECUR_NONE)
					{
						if($this->cached_events[$date][$i]['start']['mday'] != $day && $this->cached_events[$date][$i]['end']['mday'] >= $day)
						{
							continue;
						}
					}
					if(date('Hi',mktime($event['start']['hour'],$event['start']['min'],$event['start']['sec'],$month,$day,$year)) < date('Hi',mktime($this->cached_events[$date][$i]['start']['hour'],$this->cached_events[$date][$i]['start']['min'],$this->cached_events[$date][$i]['start']['sec'],$month,$day,$year)))
					{
						for($j=count($this->cached_events[$date]);$j>=$i;$j--)
						{
							$this->cached_events[$date][$j] = $this->cached_events[$date][$j-1];
						}
						if($this->debug)
						{
							echo '<!-- Adding event ID: '.$event['id'].' to cached_events -->'."\n";
						}
						$inserted = True;
						$this->cached_events[$date][$i] = $event;
						break;
					}
				}
			}
			if(!$inserted)
			{
				if($this->debug)
				{
					echo '<!-- Adding event ID: '.$event['id'].' to cached_events -->'."\n";
				}
				$this->cached_events[$date][] = $event;
			}
		}

		function check_repeating_events($datetime)
		{
			@reset($this->repeating_events);
			$search_date_full = date('Ymd',$datetime);
			$search_date_year = date('Y',$datetime);
			$search_date_month = date('m',$datetime);
			$search_date_day = date('d',$datetime);
			$search_date_dow = date('w',$datetime);
			$search_beg_day = mktime(12,0,0,$search_date_month,$search_date_day,$search_date_year);
			if($this->debug)
			{
				echo '<!-- Search Date Full = '.$search_date_full.' -->'."\n";
			}
			$repeated = $this->repeating_events;
			$r_events = count($repeated);
			for ($i=0;$i<$r_events;$i++)
			{
				$rep_events = $this->repeating_events[$i];
				$id = $rep_events['id'];
				$event_beg_day = mktime(0,0,0,$rep_events['start']['month'],$rep_events['start']['mday'],$rep_events['start']['year']);
				if($rep_events['recur_enddate']['month'] != 0 && $rep_events['recur_enddate']['mday'] != 0 && $rep_events['recur_enddate']['year'] != 0)
				{
					$event_recur_time = $this->maketime($rep_events['recur_enddate']);
				}
				else
				{
					$event_recur_time = mktime(0,0,0,1,1,2030);
				}
				$end_recur_date = date('Ymd',$event_recur_time);
				$full_event_date = date('Ymd',$event_beg_day);

				if($this->debug)
				{
					echo '<!-- check_repeating_events - Processing ID - '.$id.' -->'."\n";
					echo '<!-- check_repeating_events - Recurring End Date - '.$end_recur_date.' -->'."\n";
				}

				// only repeat after the beginning, and if there is an rpt_end before the end date
				if (($search_date_full > $end_recur_date) || ($search_date_full < $full_event_date))
				{
					continue;
				}

				if ($search_date_full == $full_event_date)
				{
					$this->sort_event($rep_events,$search_date_full);
					continue;
				}
				else
				{
					$freq = $rep_events['recur_interval'];
					$freq = $freq ? $freq : 1;
					$type = $rep_events['recur_type'];
					switch($type)
					{
						case MCAL_RECUR_DAILY:
							if($this->debug)
							{
								echo '<!-- check_repeating_events - MCAL_RECUR_DAILY - '.$id.' -->'."\n";
							}
							
							if ($freq == 1 && $rep_events['recur_enddate']['month'] != 0 && $rep_events['recur_enddate']['mday'] != 0 && $rep_events['recur_enddate']['year'] != 0 && $search_date_full <= $end_recur_date)
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							elseif (floor(($search_beg_day - $event_beg_day)/86400) % ($freq ? $freq : 1))
							{
								continue;
							}
							else
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							break;
						case MCAL_RECUR_WEEKLY:
							if (floor(($search_beg_day - $event_beg_day)/604800)  % ($freq ? $freq : 1))
							{
								continue;
							}
							$check = 0;
							switch($search_date_dow)
							{
								case 0:
									$check = MCAL_M_SUNDAY;
									break;
								case 1:
									$check = MCAL_M_MONDAY;
									break;
								case 2:
									$check = MCAL_M_TUESDAY;
									break;
								case 3:
									$check = MCAL_M_WEDNESDAY;
									break;
								case 4:
									$check = MCAL_M_THURSDAY;
									break;
								case 5:
									$check = MCAL_M_FRIDAY;
									break;
								case 6:
									$check = MCAL_M_SATURDAY;
									break;
							}
							if ($rep_events['recur_data'] & $check)
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							break;
						case MCAL_RECUR_MONTHLY_WDAY:
							if ((($search_date_year - $rep_events['start']['year']) * 12 + $search_date_month - $rep_events['start']['month']) % $freq)
							{
								continue;
							}

							if (($GLOBALS['phpgw']->datetime->day_of_week($rep_events['start']['year'],$rep_events['start']['month'],$rep_events['start']['mday']) == $GLOBALS['phpgw']->datetime->day_of_week($search_date_year,$search_date_month,$search_date_day)) &&
								(ceil($rep_events['start']['mday']/7) == ceil($search_date_day/7)))
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							break;
						case MCAL_RECUR_MONTHLY_MDAY:
							if ((($search_date_year - $rep_events['start']['year']) * 12 + $search_date_month - $rep_events['start']['month'])  % ($freq ? $freq : 1))
							{
								continue;
							}
							if ($search_date_day == $rep_events['start']['mday'])
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							break;
						case MCAL_RECUR_YEARLY:
							if (($search_date_year - $rep_events['start']['year']) % ($freq ? $freq : 1))
							{
								continue;
							}
							if (date('dm',$datetime) == date('dm',$event_beg_day))
							{
								$this->sort_event($rep_events,$search_date_full);
							}
							break;
					}
				}
			}	// end for loop
		}	// end function

		function store_to_cache($params)
		{
			if(!is_array($params))
			{
				return False;
			}
			if (isset($params['start']) && ($datearr = $GLOBALS['server']->iso86012date($params['start'])))
			{
				$syear = $datearr['year'];
				$smonth = $datearr['month'];
				$sday = $datearr['mday'];
				$this->xmlrpc = True;
			}
			else
			{
				$syear = $params['syear'];
				$smonth = $params['smonth'];
				$sday = $params['sday'];
			}
			if (isset($params['end']) && ($datearr = $GLOBALS['server']->iso86012date($params['end'])))
			{
				$eyear = $datearr['year'];
				$emonth = $datearr['month'];
				$eday = $datearr['mday'];
				$this->xmlrpc = True;
			}
			else
			{
				$eyear = (isset($params['eyear'])?$params['eyear']:0);
				$emonth = (isset($params['emonth'])?$params['emonth']:0);
				$eday = (isset($params['eday'])?$params['eday']:0);
			}
			if (!isset($params['owner']) && @$this->xmlrpc)
			{
				$owner_id = $GLOBALS['phpgw_info']['user']['user_id'];
			}
			else
			{
				$owner_id = (isset($params['owner'])?$params['owner']:0);
				if($owner_id==0 && $this->is_group)
				{
					unset($owner_id);
					$owner_id = $this->g_owner;
					if($this->debug)
					{
						echo '<!-- owner_id in ('.implode(',',$owner_id).') -->'."\n";
					}
				}
			}
			if(!$eyear && !$emonth && !$eday)
			{
				$edate = mktime(23,59,59,$smonth + 1,$sday + 1,$syear);
				$eyear = date('Y',$edate);
				$emonth = date('m',$edate);
				$eday = date('d',$edate);
			}
			else
			{
				if(!$eyear)
				{
					$eyear = $syear;
				}
				//Tratamento do valor final (mes) da pesquisa de eventos feita em $this->so->list_events.
				//Se $emonth nao tem valor, recebe o valor de $smonth (que recebe $params['smonth']) e soma 1.
				//O valor $params['emonth'] indica o mes final para a pesquisa de eventos, e passou a ser
				//informado na a impressao de eventos mensais. Mudancas feitas em class.uicalendar.inc.php, 
				//function display_month_print();
				if(!$emonth)
				{
					$emonth = $smonth + 1;
					if($emonth > 12)
					{
						$emonth = 1;
						$eyear++;
					}
				}
				if(!$eday)
				{
					$eday = $sday + 1;
				}
				$edate = mktime(23,59,59,$emonth,$eday,$eyear);
			}
			//echo "<p>bocalendar::store_to_cache(".print_r($params,True).") syear=$syear, smonth=$smonth, sday=$sday, eyear=$eyear, emonth=$emonth, eday=$eday, xmlrpc='$param[xmlrpc]'</p>\n";
			if($this->debug)
			{
				echo '<!-- Start Date : '.sprintf("%04d%02d%02d",$syear,$smonth,$sday).' -->'."\n";
				echo '<!-- End   Date : '.sprintf("%04d%02d%02d",$eyear,$emonth,$eday).' -->'."\n";
			}
			//A variavel $month_print recebe o parametro 'saux' com o mes de inicio da pesquisa de eventos por
			//$this->so->list_events. O valor do mes final da pesquisa e tratado no codigo acima;
			//$month_ini = $params['saux'];

			if($owner_id)
			{
				$cached_event_ids = $this->so->list_events($syear,$smonth,$sday,$eyear,$emonth,$eday,$owner_id);
				$cached_event_ids_repeating = $this->so->list_repeated_events($syear,$smonth,$sday,$eyear,$emonth,$eday,$owner_id);
			}
			else
			{
				$cached_event_ids = $this->so->list_events($syear,$smonth,$sday,$eyear,$emonth,$eday);
				$cached_event_ids_repeating = $this->so->list_repeated_events($syear,$smonth,$sday,$eyear,$emonth,$eday);
			}

			$c_cached_ids = count($cached_event_ids);
			$c_cached_ids_repeating = count($cached_event_ids_repeating);

			if($this->debug)
			{
				echo '<!-- events cached : '.$c_cached_ids.' : for : '.sprintf("%04d%02d%02d",$syear,$smonth,$sday).' -->'."\n";
				echo '<!-- repeating events cached : '.$c_cached_ids_repeating.' : for : '.sprintf("%04d%02d%02d",$syear,$smonth,$sday).' -->'."\n";
			}

			$this->cached_events = Array();

			if($c_cached_ids == 0 && $c_cached_ids_repeating == 0)
			{
				return;
			}

			$cache_start = (int)(sprintf("%04d%02d%02d",$syear,$smonth,$sday));
			$cached_event=$this->get_cached_event();
			if($c_cached_ids)
			{
				for($i=0;$i<$c_cached_ids;$i++)
				{
					$event = $this->so->read_entry($cached_event_ids[$i]);
					if ($event['recur_type'])
					{
						continue;	// fetch recuring events only in 2. loop
					}
					$startdate = (int)(date('Ymd',$this->maketime($event['start'])));
					$enddate = (int)(date('Ymd',$this->maketime($event['end'])));
					$this->cached_events[$startdate][] = $event;
					if($startdate != $enddate)
					{
						$start['year'] = (int)(substr($startdate,0,4));
						$start['month'] = (int)(substr($startdate,4,2));
						$start['mday'] = (int)(substr($startdate,6,2));
						for($j=$startdate,$k=0;$j<=$enddate;$k++,$j=(int)(date('Ymd',mktime(0,0,0,$start['month'],$start['mday'] + $k,$start['year']))))
						{
							$c_evt_day = count($this->cached_events[$j]) - 1;
							if($c_evt_day < 0)
							{
								$c_evt_day = 0;
							}
							if($this->debug)
							{
								echo '<!-- Date: '.$j.' Count : '.$c_evt_day.' -->'."\n";
							}
							if($this->cached_events[$j][$c_evt_day]['id'] != $event['id'])
							{
								if($this->debug)
								{
									echo '<!-- Adding Event for Date: '.$j.' -->'."\n";
								}
								$this->cached_events[$j][] = $event;
							}
							if ($j >= $cache_start && (@$params['no_doubles'] || @$this->xmlrpc))
							{
								break;	// add event only once on it's startdate
							}
						}
					}
				}
			}

			$this->repeating_events = Array();
			if($c_cached_ids_repeating)
			{
				for($i=0;$i<$c_cached_ids_repeating;$i++)
				{
					$this->repeating_events[$i] = $this->so->read_entry($cached_event_ids_repeating[$i]);
					if($this->debug)
					{
						echo '<!-- Cached Events ID: '.$cached_event_ids_repeating[$i].' ('.sprintf("%04d%02d%02d",$this->repeating_events[$i]['start']['year'],$this->repeating_events[$i]['start']['month'],$this->repeating_events[$i]['start']['mday']).') -->'."\n";
					}
				}
				for($date=mktime(0,0,0,$smonth,$sday,$syear);$date<=$edate;$date += 86400)
				{
					if($this->debug)
					{
						$search_date = date('Ymd',$date);
						echo '<!-- Calling check_repeating_events('.$search_date.') -->'."\n";
					}
					$this->check_repeating_events($date);
					if($this->debug)
					{
						echo '<!-- Total events found matching '.$search_date.' = '.count($this->cached_events[$search_date]).' -->'."\n";
						for($i=0;$i<count($this->cached_events[$search_date]);$i++)
						{
							echo '<!-- Date: '.$search_date.' ['.$i.'] = '.$this->cached_events[$search_date][$i]['id'].' -->'."\n";
						}
					}
				}
			}
			$retval = Array();
			for($j=date('Ymd',mktime(0,0,0,$smonth,$sday,$syear)),$k=0;$j<=date('Ymd',mktime(0,0,0,$emonth,$eday,$eyear));$k++,$j=date('Ymd',mktime(0,0,0,$smonth,$sday + $k,$syear)))
			{
				if(is_array($this->cached_events[$j]))
				{
					if ($this->xmlrpc)
					{
						foreach($this->cached_events[$j] as $event)
						{
							$retval[] = $this->xmlrpc_prepare($event);
						}
					}
					else
					{
						$retval[$j] = $this->cached_events[$j];
					}
				}
			}
			//echo "store_to_cache(".print_r($params,True).")=<pre>".print_r($retval,True)."</pre>\n";
			$this->so->cal->event = $cached_event;
			return $retval;
		}

		function xmlrpc_prepare(&$event)
		{
			$event['rights'] = $this->grants[$event['owner']];

			foreach(array('start','end','modtime','recur_enddate') as $name)
			{
				if (isset($event[$name]))
				{
					$event[$name] = $GLOBALS['server']->date2iso8601($event[$name]);
				}
			}
			if (is_array($event['recur_exception']))
			{
				foreach($event['recur_exception'] as $key => $timestamp)
				{
					$event['recur_exception'][$key] = $GLOBALS['server']->date2iso8601($timestamp);
				}
			}
			static $user_cache = array();

			if (!is_object($GLOBALS['phpgw']->perferences))
			{
				$GLOBALS['phpgw']->perferences = CreateObject('phpgwapi.preferences');
			}
			foreach($event['participants'] as $user_id => $status)
			{
				if (!isset($user_cache[$user_id]))
				{
					$user_cache[$user_id] = array(
						'name'   => $GLOBALS['phpgw']->common->grab_owner_name($user_id),
						'email'  => $GLOBALS['phpgw']->perferences->email_address($user_id)
					);
				}
				$event['participants'][$user_id] = $user_cache[$user_id] + array(
					'status' => $status,
				);
			}
			if (is_array($event['alarm']))
			{
				foreach($event['alarm'] as $id => $alarm)
				{
					$event['alarm'][$id]['time'] = $GLOBALS['server']->date2iso8601($alarm['time']);
					if ($alarm['owner'] != $GLOBALS['phpgw_info']['user']['account_id'])
					{
						unset($event['alarm'][$id]);
					}
				}
			}
			$event['category'] = $GLOBALS['server']->cats2xmlrpc(explode(',',$event['category']));

			// using access={public|privat} in all modules via xmlrpc
			$event['access'] = $event['public'] ? 'public' : 'privat';
			unset($event['public']);

			return $event;
		}

		/* Begin Appsession Data */
		function store_to_appsession($event)
		{
			$GLOBALS['phpgw']->session->appsession('entry','calendar',$event);
		}

		function restore_from_appsession()
		{
			$this->event_init();
			$event = $GLOBALS['phpgw']->session->appsession('entry','calendar');
			$this->so->cal->event = $event;
			return $event;
		}
		/* End Appsession Data */

		/* Begin of SO functions */
		function get_cached_event()
		{
			return $this->so->get_cached_event();
		}

		function add_attribute($var,$value,$index='**(**')
		{
			$this->so->add_attribute($var,$value,$index);
		}

		function event_init()
		{
			$this->so->event_init();
		}

		function set_start($year,$month,$day=0,$hour=0,$min=0,$sec=0)
		{
			$this->so->set_start($year,$month,$day,$hour,$min,$sec);
		}

		function set_end($year,$month,$day=0,$hour=0,$min=0,$sec=0)
		{
			$this->so->set_end($year,$month,$day,$hour,$min,$sec);
		}

		function set_title($title='')
		{
			$this->so->set_title($title);
		}

		function set_description($description='')
		{
			$this->so->set_description($description);
		}
		function set_ex_participants($ex_participants='')
		{
			$this->so->set_ex_participants($ex_participants);
		}

		function set_class($class)
		{
			$this->so->set_class($class);
		}

		function set_category($category='')
		{
			$this->so->set_category($category);
		}

		function set_alarm($alarm)
		{
			$this->so->set_alarm($alarm);
		}

		function set_recur_none()
		{
			$this->so->set_recur_none();
		}

		function set_recur_daily($year,$month,$day,$interval)
		{
			$this->so->set_recur_daily($year,$month,$day,$interval);
		}

		function set_recur_weekly($year,$month,$day,$interval,$weekdays)
		{
			$this->so->set_recur_weekly($year,$month,$day,$interval,$weekdays);
		}

		function set_recur_monthly_mday($year,$month,$day,$interval)
		{
			$this->so->set_recur_monthly_mday($year,$month,$day,$interval);
		}

		function set_recur_monthly_wday($year,$month,$day,$interval)
		{
			$this->so->set_recur_monthly_wday($year,$month,$day,$interval);
		}

		function set_recur_yearly($year,$month,$day,$interval)
		{
			$this->so->set_recur_yearly($year,$month,$day,$interval);
		}
		/* End of SO functions */

		function prepare_matrix($interval,$increment,$part,$fulldate)
		{
			for($h=0;$h<24;$h++)
			{
				for($m=0;$m<$interval;$m++)
				{
					$index = (($h * 10000) + (($m * $increment) * 100));
					$time_slice[$index]['marker'] = '&nbsp';
					$time_slice[$index]['description'] = '';
				}
			}
			foreach($this->cached_events[$fulldate] as $event)
			{
				if ($event['participants'][$part] == 'R')
				{
					continue;	// dont show rejected invitations, as they are free time
				}
				$eventstart = $GLOBALS['phpgw']->datetime->localdates($this->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset);
				$eventend = $GLOBALS['phpgw']->datetime->localdates($this->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset);
				$start = ($eventstart['hour'] * 10000) + ($eventstart['minute'] * 100);
				$starttemp = $this->splittime("$start",False);
				$subminute = 0;
				for($m=0;$m<$interval;$m++)
				{
					$minutes = $increment * $m;
					if((int)$starttemp['minute'] > $minutes && (int)$starttemp['minute'] < ($minutes + $increment))
					{
						$subminute = ($starttemp['minute'] - $minutes) * 100;
					}
				}
				$start -= $subminute;
				$end =  ($eventend['hour'] * 10000) + ($eventend['minute'] * 100);
				$endtemp = $this->splittime("$end",False);
				$addminute = 0;
				for($m=0;$m<$interval;$m++)
				{
					$minutes = ($increment * $m);
					if($endtemp['minute'] < ($minutes + $increment) && $endtemp['minute'] > $minutes)
					{
						$addminute = ($minutes + $increment - $endtemp['minute']) * 100;
					}
				}
				$end += $addminute;
				$starttemp = $this->splittime("$start",False);
				$endtemp = $this->splittime("$end",False);

				for($h=$starttemp['hour'];$h<=$endtemp['hour'];$h++)
				{
					$startminute = 0;
					$endminute = $interval;
					$hour = $h * 10000;
					if($h == (int)$starttemp['hour'])
					{
						$startminute = ($starttemp['minute'] / $increment);
					}
					if($h == (int)$endtemp['hour'])
					{
						$endminute = ($endtemp['minute'] / $increment);
					}
					$private = $this->is_private($event,$part);
					$time_display = $GLOBALS['phpgw']->common->show_date($eventstart['raw'],$this->users_timeformat).'-'.$GLOBALS['phpgw']->common->show_date($eventend['raw'],$this->users_timeformat);
					$time_description = '('.$time_display.') '.$this->get_short_field($event,$private,'title').$this->display_status($event['participants'][$part]);
					for($m=$startminute;$m<$endminute;$m++)
					{
						$index = ($hour + (($m * $increment) * 100));
						$time_slice[$index]['marker'] = '-';
						$time_slice[$index]['description'] = $time_description;
						$time_slice[$index]['id'] = $event['id'];
					}
				}
			}
			return $time_slice;
		}

		/*!
		@function set_status
		@abstract set the participant response $status for event $cal_id and notifies the owner of the event
		*/
		function set_status($cal_id,$status)
		{
			$status2msg = array(
				REJECTED  => MSG_REJECTED,
				TENTATIVE => MSG_TENTATIVE,
				ACCEPTED  => MSG_ACCEPTED
			);
			if (!isset($status2msg[$status]))
			{
				return False;
			}
			$event = $this->so->read_entry($cal_id);
			$account_id = $GLOBALS['phpgw_info']['user']['account_id'];
			if(($status2msg[$status] == "5" && $event['participants'][$account_id] == "A") ||
			 ($status2msg[$status] == "3" && $event['participants'][$account_id] == "R")) {
				return True;
			}
			$this->so->set_status($cal_id,$status);
			$event = $this->so->read_entry($cal_id);
			$this->send_update($status2msg[$status],$event['participants'],$event,false,false,'set_status');

		}

		/*!
		@function update_requested
		@abstract checks if $userid has requested (in $part_prefs) updates for $msg_type
		@syntax update_requested($userid,$part_prefs,$msg_type,$old_event,$new_event)
		@param $userid numerical user-id
		@param $part_prefs preferces of the user $userid
		@param $msg_type type of the notification: MSG_ADDED, MSG_MODIFIED, MSG_ACCEPTED, ...
		@param $old_event Event before the change
		@param $new_event Event after the change
		@returns 0 = no update requested, > 0 update requested
		*/
		function update_requested($userid,$part_prefs,$msg_type,$old_event,$new_event)
		{
			if ($msg_type == MSG_ALARM)
			{
				return True;	// always True for now
			}
			$want_update = 0;

			// the following switch fall-through all cases, as each included the following too
			//
			$msg_is_response = $msg_type == MSG_REJECTED || $msg_type == MSG_ACCEPTED || $msg_type == MSG_TENTATIVE;

			switch($ru = $part_prefs['calendar']['receive_updates'])
			{
				case 'responses':
					if ($msg_is_response)
					{
						++$want_update;
					}
				case 'modifications':
					if ($msg_type == MSG_MODIFIED)
					{
						++$want_update;
					}
				case 'time_change_4h':
				case 'time_change':
					$diff = max(abs($this->maketime($old_event['start'])-$this->maketime($new_event['start'])),
						abs($this->maketime($old_event['end'])-$this->maketime($new_event['end'])));
					$check = $ru == 'time_change_4h' ? 4 * 60 * 60 - 1 : 0;
					if ($msg_type == MSG_MODIFIED && $diff > $check)
					{
						++$want_update;
					}
				case 'add_cancel':
					if ($old_event['owner'] == $userid && $msg_is_response ||
						$msg_type == MSG_DELETED || $msg_type == MSG_ADDED)
					{
						++$want_update;
					}
					break;
				case 'no':
					break;
			}
			//echo "<p>bocalendar::update_requested(user=$userid,pref=".$part_prefs['calendar']['receive_updates'] .",msg_type=$msg_type,".($old_event?$old_event['title']:'False').",".($old_event?$old_event['title']:'False').") = $want_update</p>\n";
			return $want_update > 0;
		}

		function create_vcard($event_arrays)
		{
            if(!is_array($event_arrays))
				return null;

			$tmpattach="BEGIN:VCALENDAR\n"
			."PRODID:-//Expresso Livre//Calendar//EN\n"
			."VERSION:1.0\n";
            
            foreach ($event_arrays as $event)
            {
				// It translates int to string
				if (!is_object($event) && !is_array($event) || !array_key_exists  ('end', $event))
					$event = $event_arrays;

				//set to GMT
				//Aqui estou pegando o horario do evento e setando para GMT de acordo com o fuso horario estabelecido nas configuracoes do expresso.
				$start_time = mktime($event['start']['hour'],$event['start']['min'],$event['start']['sec'],$event['start']['month'],$event['start']['mday'],$event['start']['year']) - $GLOBALS['phpgw']->datetime->tz_offset;
				$end_time = mktime($event['end']['hour'],$event['end']['min'],$event['end']['sec'],$event['end']['month'],$event['end']['mday'],$event['end']['year']) - $GLOBALS['phpgw']->datetime->tz_offset;
				
				$start_time = date("Ymd\THis", $start_time);
				$end_time = date("Ymd\THis", $end_time);
	
				// Necessario espacos apos quebra-de-linha, na descricao, caso contrario 
				// ocorrera erro ao importar o agendamento no Outlook (erro lunar).
				$description = str_replace("\n","\n ", $event['description']);
				
				$tmpattach.="BEGIN:VEVENT\r\n"
				."DTSTART:".$start_time."\r\n"
				."DTEND:".$end_time."\r\n"
				."LAST-MODIFIED:".date("Ymd\THis")."\r\n"
				."UID:Expresso-".$event['id'].$event['uid']."\r\n";

				// Participants Externs
				$ex_participants = trim($event['ex_participants']);

				if( substr( $ex_participants, -1 ) === ',' )
				{
					$ex_participants = substr( $ex_participants, 0, ( strlen($ex_participants) - 1 ) );
				}

 				$ex_participants = explode( "," , $ex_participants );
 				
 				$tmp = "";
				
				foreach( $ex_participants as $key => $participants )
				{
					
					$begin  = strpos( htmlentities($participants), "&lt;");
					
					$mail 	= $participants;

					if( $begin !== false )
					{
						$mail = substr( htmlentities($mail), ($begin + 4 ) );
						$mail = substr( $mail, 0 , strlen($mail)-4 );
					}

					$tmp = ( $key == 0 ) ? "" : "\r\n";

					$tmp .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=".$mail.";X-NUM-GUESTS=0:mailto:".$mail;
					
					$tmpattach .= wordwrap( $tmp , 75, "\r\n"." ", true );
				}

				$tmpattach .= "\r\nDESCRIPTION:".$description."\r\n"
				."SUMMARY:".$event['title']."\r\n"
				."LOCATION:".$event['location']."\r\n"
				."END:VEVENT\r\n";
			}
			
			$tmpattach.="END:VCALENDAR\r\n";
			
			return trim($tmpattach);
		}

		/*!
		@function send_update
		@abstract sends update-messages to certain participants of an event
		@syntax send_update($msg_type,$to_notify,$old_event,$new_event=False)
		@param $msg_type type of the notification: MSG_ADDED, MSG_MODIFIED, MSG_ACCEPTED, ...
		@param $to_notify array with numerical user-ids as keys (!) (value is not used)
		@param $old_event Event before the change
		@param $new_event Event after the change
		*/
		function send_update($msg_type,$to_notify,$old_event,$new_event=False,$user=False, $caller = 'none')
		{			
			if (!is_array($to_notify))
			{
				$to_notify = array();
			}
			$owner = $old_event ? $old_event['owner'] : $new_event['owner'];
			if ($owner && !isset($to_notify[$owner]) && $msg_type != MSG_ALARM)
			{
				$to_notify[$owner] = 'owner';	// always include the event-owner40
			}
			$version = $GLOBALS['phpgw_info']['apps']['calendar']['version'];

			$GLOBALS['phpgw_info']['user']['preferences'] = $GLOBALS['phpgw']->preferences->create_email_preferences();
			$sender = $GLOBALS['phpgw_info']['user']['email'];

			$temp_tz_offset = $this->prefs['common']['tz_offset'];
			$temp_timeformat = $this->prefs['common']['timeformat'];
			$temp_dateformat = $this->prefs['common']['dateformat'];

			$tz_offset = ((60 * 60) * (int)$temp_tz_offset);

			if($old_event != False)
			{
				$t_old_start_time = $this->maketime($old_event['start']);
				if($t_old_start_time < (time() - 86400))
				{
					return False;
				}
			}

			$temp_user = $GLOBALS['phpgw_info']['user'];

			if (!$user)
			{
				$user = $this->owner;
			}
			$GLOBALS['phpgw_info']['user']['preferences'] = $GLOBALS['phpgw']->preferences->create_email_preferences($user);

			$event = $msg_type == MSG_ADDED || $msg_type == MSG_MODIFIED ? $new_event : $old_event;
			if($old_event != False)
			{
				$old_starttime = $t_old_start_time - $GLOBALS['phpgw']->datetime->tz_offset;
			}
			$starttime = $this->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$endtime   = $this->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;

			switch($msg_type)
			{
				case MSG_DELETED:
					$action = lang('Canceled');
					$msg = 'Canceled';
					$msgtype = '"calendar";';
					$method = 'cancel';
					$typesend = 1;
					break;
				case MSG_MODIFIED:
					$action = lang('Modified');
					$msg = 'Modified';
					$msgtype = '"calendar"; Version="'.$version.'"; Id="'.$new_event['id'].'"';
					$method = 'request';
					$typesend = 2;
					break;
				case MSG_ADDED:
					$action = lang('Added');
					$msg = 'Added';
					$msgtype = '"calendar"; Version="'.$version.'"; Id="'.$new_event['id'].'"';
					$method = 'request';
					$typesend = 3;
					break;
				case MSG_REJECTED:
					$action = lang('Rejected');
					$msg = 'Response';
					$msgtype = '"calendar";';
					$method = 'reply';
					$typesend = 4;
					break;
				case MSG_TENTATIVE:
					$action = lang('Tentative');
					$msg = 'Response';
					$msgtype = '"calendar";';
					$method = 'reply';
					$typesend = 5;
					break;
				case MSG_ACCEPTED:
					$action = lang('Accepted');
					$msg = 'Response';
					$msgtype = '"calendar";';
					$method = 'reply';
					$typesend = 6;
					break;
				case MSG_ALARM:
					$action = lang('Alarm');
					$msg = 'Alarm';
					$msgtype = '"calendar";';
					$method = 'publish';	// duno if thats right
					$typesend = 7;
					break;
				default:
					$method = 'publish';
					$typesend = 8;
			}
			$notify_msg = $this->prefs['calendar']['notify'.$msg];
			if (empty($notify_msg))
			{
				$notify_msg = $this->prefs['calendar']['notifyAdded'];	// use a default
			}
			$details = array(			// event-details for the notify-msg
				'id'          => $msg_type == MSG_ADDED ? $new_event['id'] : $old_event['id'],
				'action'      => $action,
			);
			$event_arr = $this->event2array($event);
			foreach($event_arr as $key => $val)
			{
				$details[$key] = $val['data'];
			}
			
			$details['participants'] = implode("\n",$details['participants']);

			$details['link'] = $GLOBALS['phpgw_info']['server']['webserver_url'].'/index.php?menuaction=calendar.uicalendar.view&cal_id='.$event['id'];
			// if url is only a path, try guessing the rest ;-)
			if ($GLOBALS['phpgw_info']['server']['webserver_url'][0] == '/')
			{
				$details['link'] = ($GLOBALS['phpgw_info']['server']['enforce_ssl'] ? 'https://' : 'http://').
					($GLOBALS['phpgw_info']['server']['hostname'] ? $GLOBALS['phpgw_info']['server']['hostname'] : 'localhost').
					$details['link'];
			}
			
			//Seta o email usando phpmailer
			define('PHPGW_INCLUDE_ROOT','../');	
			define('PHPGW_API_INC','../phpgwapi/inc');	
			include_once(PHPGW_API_INC.'/class.phpmailer.inc.php');
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$emailadmin = CreateObject('emailadmin.bo')->getProfile();
		
			$mail->Host = $emailadmin['smtpServer'];
			$mail->Port = $emailadmin['smtpPort'];
			$mail->From = $GLOBALS['phpgw']->preferences->values['email'];
			$mail->FromName = $GLOBALS['phpgw_info']['user']['fullname'];
			$mail->IsHTML(true);

			$sms_data = array('to' => array());

			// Aqui e enviado o email
			foreach($to_notify as $userid => $statusid) 
			{
			 	$mail->ClearAttachments();
			 	
				$userid = (int)$userid;

				if ($statusid == 'R' || $GLOBALS['phpgw']->accounts->get_type($userid) == 'g')
				{
					continue;	// dont notify rejected participants
				}

				$sms_data['to'][$userid] = true;

				if($userid != $GLOBALS['phpgw_info']['user']['account_id'] ||  $msg_type == MSG_ALARM)
				{
					print_debug('Msg Type',$msg_type);
					print_debug('UserID',$userid);

					$preferences = CreateObject('phpgwapi.preferences',$userid);
					$part_prefs = $preferences->read_repository();

					if (!$this->update_requested($userid,$part_prefs,$msg_type,$old_event,$new_event))
					{
						continue;
					}
					$GLOBALS['phpgw']->accounts->get_account_name($userid,$lid,$details['to-firstname'],$details['to-lastname']);
					$details['to-fullname'] = $GLOBALS['phpgw']->common->display_fullname('',$details['to-firstname'],$details['to-lastname']);

					$to = $preferences->email_address($userid);
					
					if (empty($to) || $to[0] == '@' || $to[0] == '$')	// we have no valid email-address
					{
						//echo "<p>bocalendar::send_update: Empty email adress for user '".$details['to-fullname']."' ==> ignored !!!</p>\n";
						continue;
					}
					print_debug('Email being sent to',$to);

					$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset'] = $part_prefs['common']['tz_offset'];
					$GLOBALS['phpgw_info']['user']['preferences']['common']['timeformat'] = $part_prefs['common']['timeformat'];
					$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'] = $part_prefs['common']['dateformat'];

					$GLOBALS['phpgw']->datetime->tz_offset = ((60 * 60) * (int)$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset']);

					if($old_starttime)
					{
						$details['olddate'] = $GLOBALS['phpgw']->common->show_date($old_starttime);
					}
					$details['startdate'] = $GLOBALS['phpgw']->common->show_date($starttime);
					$details['enddate']   = $GLOBALS['phpgw']->common->show_date($endtime);
					
					
					list($subject,$body1) = explode("\n",$GLOBALS['phpgw']->preferences->parse_notify($notify_msg,$details),2);
					
					switch($part_prefs['calendar']['update_format'])
 					{
						case  'extended':
							//$body .= "\n\n".lang('Event Details follow').":\n";
							$body = '';
							$body .= "<br>".lang('Event Details follow')." :: ";
							foreach($event_arr as $key => $val)
							{
								// titulo
								if($key =='title')
								{
									$var1 = $val['field'];
									$vardata1 = $details[$key];
								}
								//descricao
								if($key =='description')
								{
									$var2 = $val['field'];
									$vardata2 = $details[$key];
								}
								//dt inicio
								if($key =='startdate')
								{
									switch(trim($part_prefs['common']['dateformat']))
									{
										
										case ($part_prefs['common']['dateformat'] === "m/d/Y" || $part_prefs['common']['dateformat'] === "m-d-Y" || $part_prefs['common']['dateformat'] === "m.d.Y"):
											$var3 = $val['field'];
											$vardata3 = $details[$key];
											$newmounth3 = substr($vardata3,0,2);
											$newday3 = substr($vardata3,3,2);
											$newyear3 = substr($vardata3,6,4);
											$newall3 =$newyear3.$newmounth3.$newday3;
											break;
											
										case	($part_prefs['common']['dateformat'] === "Y/d/m" || $part_prefs['common']['dateformat'] === "Y-d-m" || $part_prefs['common']['dateformat'] === "Y.d.m"):

											$var3 = $val['field'];
											$vardata3 = $details[$key];
											$newyear3 = substr($vardata3,0,4);
											$newday3 = substr($vardata3,5,2);
											$newmounth3 = substr($vardata3,8,2);
											$newall3 =$newyear3.$newmounth3.$newday3;
											break;

										case ($part_prefs['common']['dateformat'] === "Y/m/d" || $part_prefs['common']['dateformat'] === "Y-m-d" || $part_prefs['common']['dateformat'] === "Y.m.d"):

											$var3 = $val['field'];
											$vardata3 = $details[$key];
											$newyear3 = substr($vardata3,0,4);
											$newmounth3 = substr($vardata3,5,2);
											$newday3 = substr($vardata3,8,2);
											$newall3 =$newyear3.$newmounth3.$newday3;
											break;

										case ($part_prefs['common']['dateformat'] === "d/m/Y" || $part_prefs['common']['dateformat'] === "d-m-Y" || $part_prefs['common']['dateformat'] === "d.m.Y" || $part_prefs['common']['dateformat'] === "d-M-Y"):
										
											$var3 = $val['field'];
											$vardata3 = $details[$key];
											$newday3 = substr($vardata3,0,2);
											$newmounth3 = substr($vardata3,3,2);
											$newyear3 = substr($vardata3,6,4);
											$newall3 =$newyear3.$newmounth3.$newday3;
											break;
									
									}
									
								}
								//dt final
								if($key =='enddate')
								{
									$var4 = $val['field'];
									$vardata4 = $details[$key];
								}
								//localizacao
								if($key =='location')
								{
									$var8 = $val['field'];
									$vardata8 = $details[$key];
								}
								//participantes
								if($key =='participants')
								{
									$var5 = $val['field'];
									foreach($val['data'] as $NewKey => $NewVal)
									{
										//Research inside of ldap ( Pesquisa dentro do ldap )
										$newvalue = $this->so->search_uidNumber($to);
										foreach($newvalue as $tmp)
										{
											$tmpmail = $tmp['mail'][0];
											$tmpuid = $tmp['uidnumber'][0];
											if( trim($tmpmail) == trim($to) & trim($tmpuid) == trim($NewKey))
											{
													if( ( $typesend == 3 ) || ( $typesend == 2 ) )
													{
														$question = "";
														
														if( $typesend == 2 )
															$question = lang("Do you wish to change your participation in this event?");

														$lang1 = lang("To See Commitment");
														$varbuttom = "<form action=".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uicalendar.view&cal_id=$event[id]&date=$newall3' method='POST'>
																		  		  <input type='submit' value='$lang1'>
																		  		   </form>";
														$lang2 = lang("To accept");
														$varbuttom1 ="<input type='submit' value='$lang2' onClick='javascript:window.open(\"".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uicalendar.set_action&cal_id=$event[id]&action=3&response=1&user_id=$userid\",\"frontpage\",\"height=200,width=450,statusbar=no,toolbar=no,scrollbars=no,menubar=no,left=300,top=200\")'>";

														$lang3 = lang("To reject");
														$varbuttom2 ="<input type='submit' value='$lang3' onClick='javascript:window.open(\"".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uicalendar.set_action&cal_id=$event[id]&action=0&response=0\",\"frontpage\",\"height=100,width=400,statusbar=no,toolbar=no,scrollbars=no,menubar=no,left=300,top=200\")'>";
														
														$lang4 = lang("Alarm");
														$varbuttom3 = "<form action=".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uialarm.manager method='POST'>
																		  		  <input type='submit' value='$lang4'>
																		  		  <input type='hidden' name='cal_id' value=$event[id]>
																		  		   </form>";
																				   
														$lang5 = lang("Delegate");
														$varbuttom4 = "<form action='".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uicalendar.screen_delegate_event&id_event=$event[id]&date=$newall3&delegator=$tmpuid' method='POST'>
																		  		  <input type='submit' value='$lang5'>
																		  		   </form>";
														
														$lang6 = lang("Tentative");
														$varbuttom5 ="<input type='submit' value='$lang6' onClick='javascript:window.open(\"".$GLOBALS['phpgw_info']['server']['webserver_url']."/index.php?menuaction=calendar.uicalendar.set_action&cal_id=$event[id]&action=2&response=1&user_id=$userid\",\"frontpage\",\"height=200,width=450,statusbar=no,toolbar=no,scrollbars=no,menubar=no,left=300,top=200\")'>";														
													}
													else
													{
															$varbuttom  = "";
															$varbuttom1 = "";
															$varbuttom2 = "";
															$varbuttom3 = "";
															$varbuttom4 = "";
															$varbuttom5 = "";
													}
											}
											// It only mounts variavel with the name of the participants of the list ( Monta a variavel somente com o nome dos participantes da lista)
											if($typesend == 3)
											{
												list($tmpvardata5,$tmp2vardata5) = explode("(",$NewVal);
												$vardata5 = $tmpvardata5."<br>";
											}
											else
											{
												$vardata5 = $NewVal."<br>";
											}
										
										}
										$vardata6 .= $vardata5;
										unset($vardata5);
									}
								}		
							}
							//To mount the message as text/html (Para montar a mensagem como text/html - /phpgwapi/inc/class.send.inc.php )
							$content_type = "text/html";
							//It mounts the body of the message (Monta o corpo da mensagem)
							
							// A constante PHPGW_APP_TPL nao existe para envio de alarmes (cront, asyncservice).
							define ("PHPGW_APP_TPL",PHPGW_API_INC . "/../../calendar/templates/".$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set']."");
							
							$body = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
							$body->set_file(Array('calendar' => 'body_email.tpl'));
							$body->set_block('calendar','list');
							$var = Array(
								'script'			=> $script,
								'subject'			=> $body1,
								'var1'				=> $var1,
								'vardata1'			=> $vardata1,
								'var2'				=> $var2,
								'vardata2'			=> $vardata2,
								'var3'				=> $var3,
								'vardata3'			=> $vardata3,
								'var4'				=> $var4,
								'vardata4'			=> $vardata4,
								'var5'				=> $var5,
								'vardata6'			=> $vardata6,
								'var8'				=> $var8,
								'vardata8'			=> $vardata8,
								'question'			=> $question,															
								'varbuttom'			=> $varbuttom,
								'varbuttom1'		=> $varbuttom1,
								'varbuttom2'		=> $varbuttom2,
								'varbuttom3'		=> $varbuttom3,
								'varbuttom4'		=> $varbuttom4,
								'varbuttom5'		=> $varbuttom5,
								'msgWebConf'		=> (isset($new_event['webconf']) ? $new_event['webconf'] : "")
							);
							$body->set_var($var);
							$tmpbody = $body->fp('out','list');
														
							break;

						case 'ical':
							$content_type = "calendar; method=$method; name=calendar.ics";
/*							if ($body != '')
							{
								$boundary = '----Message-Boundary';
								$body .= "\n\n\n$boundary\nContent-type: text/$content_type\n".
									"Content-Disposition: inline\nContent-transfer-encoding: 7BIT\n\n";
								$content_type = '';
							}
*/
							$body = ExecMethod('calendar.boicalendar.export',array(
								'l_event_id'  => $event['id'],
								'method'      => $method,
								'chunk_split' => False
							));
							break;
					}
					$mail->AddAddress($to);
					$mail->Body = $tmpbody;
					$mail->From = $sender;
					$mail->FromName = $GLOBALS['phpgw_info']['user']['fullname'];
					$mail->Sender = $mail->From;
					$mail->SenderName = $mail->FromName;
					$mail->Subject = $subject;
					unset($vardata5);
					unset($vardata6);
				}
			}

			// SMS message behavior
			if ($GLOBALS['phpgw']->sms->isEnabled ()) {
				switch ($msg) {
					
					case 'Added':
					case 'Modified':
					case 'Canceled':
						
						// Remove all participants, except owner
						if (isset($event['sms_check_parts']) && (!$event['sms_check_parts']))
							$sms_data['to'] = array( $owner => true );
						
						// Remove owner in owner events on cases:
						// Not be selected on the checkbox
						// Edit event dispatch three callers, (prep_add, prep_mod and prep_del), only prep_mod is valid to owner
						// In participant list
						if ( ((!(isset($event['sms_check_owner']) && $event['sms_check_owner']))|| $caller=='prep_add' || $caller=='prep_del') && isset($sms_data['to'][$owner]) )
							unset($sms_data['to'][$owner]);
						
					case 'Alarm':
						
						// Test one or more receiver
						// Test whether the start time of the event was not exceeded
						if (count($sms_data['to']) && ( time() < $this->maketime($event['start']) )) {
							
							// Message template
							$sms_data['message'] = lang('calendar event').': $$action$$ - $$title$$ - $$startdate$$ - $$location$$';
							foreach ($details as $key => $value) if (is_string($value)) $sms_data['message'] = str_replace('$$'.$key.'$$', $value, $sms_data['message']);
							
							try {
								
								// Send message for each receiver
								$GLOBALS['phpgw']->sms->send_message(array_keys($sms_data['to']), utf8_encode($sms_data['message']));
								
							} catch (Exception $e) {}
							
						}
						break;
					default: break;
				}
			}

			//Inicializa variavel de retorno.
			$returncode=true;			
			if(count($mail->to)) {				
				// Envia aviso a todos os participantes.
				if(!$mail->Send()) {				
					$returncode = false;
					$errorInfo['participants'] = $mail->ErrorInfo;
				}
				else
					$returncode =  true;
			}
			
			if(count($to_notify) && $this->ex_participants){
				$mail->ClearAllRecipients();
				$var = explode(",",trim($this->ex_participants));
				$to = array();
				if(!$subject) {
					$details['startdate'] = $GLOBALS['phpgw']->common->show_date($starttime);
					$details['enddate']   = $GLOBALS['phpgw']->common->show_date($endtime);
					list($subject,$body1) = explode("\n",$GLOBALS['phpgw']->preferences->parse_notify($notify_msg,$details),2);
				}
				
				foreach($var as $index => $ex_participant){
					$ex_participant = trim($ex_participant);
					$ex_participant = preg_replace('#"(.*)" <(.*)\@(.*)\.(.*)>#','\\2@\\3.\\4',$ex_participant);
						if($ex_participant)
							$to[] = $ex_participant;
				}		
				
				foreach($to as $i => $to_array)
					$mail->AddAddress($to_array);
				
				$_body = explode("<hr size='1' width='100%'>",$tmpbody);
				$tmpbody = $_body[0] ? $_body[0] : $subject ;
				$tmpbody.= "<br><b>".lang("external participants").":: </b> ".htmlentities($this->ex_participants);
				$tmpbody.= "<br>".lang("Summary").": ".$this->so->cal->event[title]."<br>";
				$tmpbody.= "<br>".lang("Start time").": ".$GLOBALS['phpgw']->common->show_date($starttime)."<br>".lang("End date").": ".$GLOBALS['phpgw']->common->show_date($endtime)."<br>";
				$tmpbody.= "<br><br><hr size='1' width='100%'><font color='red'>"
				.lang("This is an automatically generated message. To confirm your attendance, please reply to the sender of the message.")."<br></font><br>";
				
				if ($GLOBALS['bocalendar']->so->cal->event[start][month] >= 10)
					$event_month=$GLOBALS['bocalendar']->so->cal->event[start][month];
				else
					$event_month="0".$GLOBALS['bocalendar']->so->cal->event[start][month];
				//attach extern vcalendar/icalendar (ics)			
				// define('context','$GLOBALS.bocalendar.so.cal.event');
				$tmpattach = $this->create_vcard(array($GLOBALS['bocalendar']->so->cal->event));
				if($tmpattach && $msg_type == MSG_ADDED ){					
					/*
					$tmpbody .="<a href='../index.php?menuaction=calendar.uicalendar.add&date="
					.$GLOBALS['bocalendar']->so->cal->event[start][year]
				.$event_month
				.$GLOBALS['bocalendar']->so->cal->event[start][mday]
					."&hour=".$GLOBALS['bocalendar']->so->cal->event[start][hour]
					."&minute=".$GLOBALS['bocalendar']->so->cal->event[start][min]
					."&title=".$GLOBALS['bocalendar']->so->cal->event['title']
					."&description=".$GLOBALS['bocalendar']->so->cal->event['description']
					."&location=".$GLOBALS['bocalendar']->so->cal->event['location']."'>"
					."<h2>".lang("Add to my expresso")."</h2>";
					*/
					$tempdir = $GLOBALS['phpgw_info']['server']['temp_dir'] . SEP;
					srand((double)microtime()*1000000);
					$random_number = rand(100000000,999999999);
					$newfilename = md5(time() . getenv("REMOTE_ADDR") . $random_number );
					$filename = $tempdir . $newfilename;
					$attach_fd = fopen($filename,"w+");
					fwrite($attach_fd,$tmpattach);
					$mail->AddAttachment($filename, "extern.ics", "base64", "text/plain"); // "application/octet-stream"
					fclose($attach_fd);
				}
				$mail->From = $sender;
				$mail->FromName = $GLOBALS['phpgw_info']['user']['fullname'];
				$mail->Sender = $mail->From;
				$mail->SenderName = $mail->FromName;
				$mail->Subject = lang("Notice of appointment of external event");
				$mail->Body = $tmpbody;
																		
				if(!$mail->Send()) 
				{
					$returncode=false;
					$errorInfo['ex_participants'] = $mail->ErrorInfo;
				}
				else
				{
					$returncode=true;
				}
			}


			if((is_int($this->user) && $this->user != $temp_user['account_id']) ||
				(is_string($this->user) && $this->user != $temp_user['account_lid']))
			{
				$GLOBALS['phpgw_info']['user'] = $temp_user;
			}	

			$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset'] = $temp_tz_offset;
			$GLOBALS['phpgw']->datetime->tz_offset = ((60 * 60) * $temp_tz_offset);
			$GLOBALS['phpgw_info']['user']['preferences']['common']['timeformat'] = $temp_timeformat;
			$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'] = $temp_dateformat;
			
			// Notifica por email o criador do compromisso, com as possiveis falhas.						
			if($errorInfo) {
				$tmpbody = "<font color='red'>".lang("The following commitment had problems for DELIVERING the NOTIFICATION messages.").".</font><br>";
				$tmpbody.= "<br>".lang("Summary").": ".$this->so->cal->event[title]."<br>";
				$tmpbody.= "<br>".lang("Start time").": ".$GLOBALS['phpgw']->common->show_date($starttime)."<br>".lang("End date").": ".$GLOBALS['phpgw']->common->show_date($endtime)."<br>";				
				$tmpbody.= "<br><u>".lang("Failed to delivery")."</u><br>";
				$failed = false;				
				if(strstr($errorInfo['participants'],"recipients_failed")){
					$failed	= explode("recipients_failed",$errorInfo['participants']);
					$tmpbody.= lang("to").": ".$failed[1];
				}
				if(strstr($errorInfo['ex_participants'],"recipients_failed")){
					$failed = explode("recipients_failed",$errorInfo['ex_participants']);
					$tmpbody.= lang("to").": ".$failed[1];
				}			
				if(!$failed) {
					$tmpbody.= lang("Description").":<br>";
					$tmpbody.= $errorInfo['participants'];
					$tmpbody.= "<br>".$errorInfo['ex_participants'];
				}
				$tmpbody.= "<br>".lang("Subject").": ".$subject;
				// Reinicializa o objeto, devido ao erro anterior na entrega.
				
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->Host = $emailadmin['smtpServer'];
				$mail->Port = $emailadmin['smtpPort'];
				$mail->From = $sender;
				$mail->FromName = $GLOBALS['phpgw_info']['user']['fullname'];
				$mail->Sender = $mail->From;
				$mail->SenderName = $mail->FromName;
				$mail->IsHTML(True);
				$mail->Subject = lang("calendar event")." - ".lang("email notification");							
				$mail->Body = $tmpbody;					
				$mail->AddAddress($sender);
				if(!$mail->Send())			
					$returncode = false;
				else
					$returncode =  true;
			}
			return $returncode;
		}
		
		function send_alarm($alarm)
		{
			//echo "<p>bocalendar::send_alarm("; print_r($alarm); echo ")</p>\n";
			
			$GLOBALS['phpgw_info']['user']['account_id'] = $this->owner = $alarm['owner'];

			if (!$alarm['enabled'] || !$alarm['owner'] || !$alarm['cal_id'] || !($event = $this->so->read_entry($alarm['cal_id'])))
			{
				return False;	// event not found
			}
			if ($alarm['all'])
			{
				$to_notify = $event['participants'];
			}
			elseif ($this->check_perms(PHPGW_ACL_READ,$event))	// checks agains $this->owner set to $alarm[owner]
			{
				$to_notify[$alarm['owner']] = 'A';
			}
			else
			{
				return False;	// no rights
			}
			return $this->send_update(MSG_ALARM,$to_notify,$event,False,$alarm['owner'],'send_alarm');
		}

		function get_alarms($event_id)
		{
			return $this->so->get_alarm($event_id);
		}

		function alarm_today($event,$today,$starttime)
		{
			$found = False;
			@reset($event['alarm']);
			$starttime_hi = $GLOBALS['phpgw']->common->show_date($starttime,'Hi');
			$t_appt['month'] =$GLOBALS['phpgw']->common->show_date($today,'m');
			$t_appt['mday'] = $GLOBALS['phpgw']->common->show_date($today,'d');
			$t_appt['year'] = $GLOBALS['phpgw']->common->show_date($today,'Y');
			$t_appt['hour'] = $GLOBALS['phpgw']->common->show_date($starttime,'H');
			$t_appt['min']  = $GLOBALS['phpgw']->common->show_date($starttime,'i');
			$t_appt['sec']  = 0;
			$t_time = $this->maketime($t_appt) - $GLOBALS['phpgw']->datetime->tz_offset;
			$y_time = $t_time - 86400;
			$tt_time = $t_time + 86399;
			print_debug('T_TIME',$t_time.' : '.$GLOBALS['phpgw']->common->show_date($t_time));
			print_debug('Y_TIME',$y_time.' : '.$GLOBALS['phpgw']->common->show_date($y_time));
			print_debug('TT_TIME',$tt_time.' : '.$GLOBALS['phpgw']->common->show_date($tt_time));
			while(list($key,$alarm) = each($event['alarm']))
			{
				if($alarm['enabled'])
				{
					print_debug('TIME',$alarm['time'].' : '.$GLOBALS['phpgw']->common->show_date($alarm['time']).' ('.$event['id'].')');
					if($event['recur_type'] != MCAL_RECUR_NONE)   /* Recurring Event */
					{
						print_debug('Recurring Event');
						if($alarm['time'] > $y_time && $GLOBALS['phpgw']->common->show_date($alarm['time'],'Hi') < $starttime_hi && $alarm['time'] < $t_time)
						{
							$found = True;
						}
					}
					elseif($alarm['time'] > $y_time && $alarm['time'] < $t_time)
					{
						$found = True;
					}
				}
			}
			print_debug('Found',$found);
			return $found;
		}

		function prepare_recipients(&$new_event,$old_event)
		{
						
			// Find modified and deleted users.....
			while(list($old_userid,$old_status) = each($old_event['participants']))
			{
				if(isset($new_event['participants'][$old_userid]))
				{
					print_debug('Modifying event for user',$old_userid);
					$this->modified[(int)$old_userid] = $new_status;
				}
				else
				{
					print_debug('Deleting user from the event',$old_userid);
					$this->deleted[(int)$old_userid] = $old_status;
				}
			}
			// Find new users.....
			while(list($new_userid,$new_status) = each($new_event['participants']))
			{
				if(!isset($old_event['participants'][$new_userid]))
				{
					print_debug('Adding event for user',$new_userid);
					$this->added[$new_userid] = 'U';
					$new_event['participants'][$new_userid] = 'U';
				}
			}
			
			if(count($this->added) > 0 || count($this->modified) > 0 || count($this->deleted) > 0)
			{
				if(count($this->added) > 0)
				{
					$this->ex_participants = '';
					$this->send_update(MSG_ADDED,$this->added,'',$new_event,false,'prep_add');
				}
				if(count($this->modified) > 0)
				{
					$this->send_update(MSG_MODIFIED,$this->modified,$old_event,$new_event,false,'prep_mod');
				}
				if(count($this->deleted) > 0)
				{
					$this->ex_participants = '';
					$this->send_update(MSG_DELETED,$this->deleted,$old_event,false,false,'prep_del');
				}
			}
		}

		function remove_doubles_in_cache($firstday,$lastday)
		{
			$already_moved = Array();
			for($v=$firstday;$v<=$lastday;$v++)
			{
				if (!$this->cached_events[$v])
				{
					continue;
				}
				$cached = $this->cached_events[$v];
				$this->cached_events[$v] = array();
				while (list($g,$event) = each($cached))
				{
					$end = date('Ymd',$this->maketime($event['end']));
					print_debug('EVENT',_debug_array($event,False));
					print_debug('start',$start);
					print_debug('v',$v);

					if (!isset($already_moved[$event['id']]) || $event['recur_type'] && $v > $end)
					{
						$this->cached_events[$v][] = $event;
						$already_moved[$event['id']] = 1;
						print_debug('Event moved');
					}
				}
			}
		}

		function get_dirty_entries($lastmod=-1)
		{
			$events = false;
			$event_ids = $this->so->cal->list_dirty_events($lastmod);
			if(is_array($event_ids))
			{
				foreach($event_ids as $key => $id)
				{
					$events[$id] = $this->so->cal->fetch_event($id);
				}
			}
			unset($event_ids);

			$rep_event_ids = $this->so->cal->list_dirty_events($lastmod,$true);
			if(is_array($rep_event_ids))
			{
				foreach($rep_event_ids as $key => $id)
				{
					$events[$id] = $this->so->cal->fetch_event($id);
				}
			}
			unset($rep_event_ids);

			return $events;
		}

		function _debug_array($data)
		{
			echo '<br>UI:';
			_debug_array($data);
		}

		/*!
		@function rejected_no_show
		@abstract checks if event is rejected from user and he's not the owner and dont want rejected
		@param $event to check
		@returns True if event should not be shown
		*/
		function rejected_no_show($event)
		{
			$ret = !$this->prefs['calendar']['show_rejected'] &&
				$event['owner'] != $this->owner &&
				$event['participants'][$this->owner] == 'R';
			//echo "<p>rejected_no_show($event[title])='$ret': user=$this->owner, event-owner=$event[owner], status='".$event['participants'][$this->owner]."', show_rejected='".$this->prefs['calendar']['show_rejected']."'</p>\n";
			return $ret;
		}

		/* This is called only by list_cals().  It was moved here to remove fatal error in php5 beta4 */
		function list_cals_add($id,&$users,&$groups)
		{
			$name = $GLOBALS['phpgw']->common->grab_owner_name($id);
			if (($type = $GLOBALS['phpgw']->accounts->get_type($id)) == 'g')
			{
				$arr = &$groups;
			}
			else
			{
				$arr = &$users;
			}
			$arr[$name] = Array(
				'grantor' => $id,
				'value'   => ($type == 'g' ? 'g_' : '') . $id,
				'name'    => $name
			);
		}

		/*!
		@function list_cals
		@abstract generate list of user- / group-calendars for the selectbox in the header
		@returns alphabeticaly sorted array with groups first and then users
		*/
		function list_cals()
		{
			$users = $groups = array();
			foreach($this->grants as $id => $rights)
			{
				$this->list_cals_add($id,$users,$groups);
			}
			
			//by JakJr, melhora de performance na abertura da agenda
			/*if ($memberships = $GLOBALS['phpgw']->accounts->membership($GLOBALS['phpgw_info']['user']['account_id']))
			{
				foreach($memberships as $group_info)
				{
					$this->list_cals_add($group_info['account_id'],$users,$groups);

					if ($account_perms = $GLOBALS['phpgw']->acl->get_ids_for_location($group_info['account_id'],PHPGW_ACL_READ,'calendar'))
					{
						foreach($account_perms as $id)
						{
							$this->list_cals_add($id,$users,$groups);
						}
					}
				}
			}*/
			uksort($users,'strnatcasecmp');
			uksort($groups,'strnatcasecmp');

			return $users + $groups;	// users first and then groups, both alphabeticaly
		}

	  function translate($key,$vars=false, $not_found='*' )
	  {
    	    if ($this->async)
	      return $GLOBALS['phpgw']->translation->translate_async($key, $vars);
	    return lang($key, $vars);
	  }

		/*!
		@function event2array
		@abstract create array with name, translated name and readable content of each attributes of an event
		@syntax event2array($event,$sep='<br>')
		@param $event event to use
		@returns array of attributes with fieldname as key and array with the 'field'=translated name \
			'data' = readable content (for participants this is an array !)
		*/
		function event2array($event)
		{
			
			
		  $var['title'] = Array(
				'field'		=> $this->translate('Title'),
				'data'		=> $event['title']
			);

			// Some browser add a \n when its entered in the database. Not a big deal
			// this will be printed even though its not needed.
			$var['description'] = Array(
				'field'	=> $this->translate('Description'),
				'data'	=> $event['description']
			);

			$var['ex_participants'] = Array(
				'field'	=> $this->translate('External Participants'),
				'data'	=> $event['ex_participants']
			);
			
			$cats = Array();
			$this->cat->categories($this->bo->owner,'calendar');
			if(strpos($event['category'],','))
			{
				$cats = explode(',',$event['category']);
			}
			else
			{
				$cats[] = $event['category'];
			}
			foreach($cats as $cat_id)
			{
				list($cat) = $this->cat->return_single($cat_id);
				$cat_string[] = $cat['name'];
			}
			$var['category'] = Array(
				'field'	=> $this->translate('Category'),
				'data'	=> implode(', ',$cat_string)
			);

			$var['location'] = Array(
				'field'	=> $this->translate('Location'),
				'data'	=> $event['location']
			);

			$var['startdate'] = Array(
				'field'	=> $this->translate('Start Date/Time'),
				'data'	=> $GLOBALS['phpgw']->common->show_date($this->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset),
			);

			$var['enddate'] = Array(
				'field'	=> $this->translate('End Date/Time'),
				'data'	=> $GLOBALS['phpgw']->common->show_date($this->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset)
			);

			$pri = Array(
				1	=> lang('Low'),
				2	=> lang('Normal'),
				3	=> lang('High')
			);
			$var['priority'] = Array(
				'field'	=> lang('Priority'),
				'data'	=> $pri[$event['priority']]
			);

			$var['owner'] = Array(
				'field'	=> lang('Created By'),
				'data'	=> $GLOBALS['phpgw']->common->grab_owner_name($event['owner'])
			);

			$var['updated'] = Array(
				'field'	=> lang('Updated'),
				'data'	=> $GLOBALS['phpgw']->common->show_date($this->maketime($event['modtime']) - $GLOBALS['phpgw']->datetime->tz_offset)
			);

			$var['access'] = Array(
				'field'	=> lang('Access'),
				'data'	=> $event['public'] ? lang('Public') : lang('Private')
			);

			if(@isset($event['groups'][0]))
			{
				$cal_grps = '';
				for($i=0;$i<count($event['groups']);$i++)
				{
					if($GLOBALS['phpgw']->accounts->exists($event['groups'][$i]))
					{
						$cal_grps .= ($i>0?'<br>':'').$GLOBALS['phpgw']->accounts->id2name($event['groups'][$i]);
					}
				}

				$var['groups'] = Array(
					'field'	=> lang('Groups'),
					'data'	=> $cal_grps
				);
			}

			$participants = array();
			foreach($event['participants'] as $user => $short_status)
			{
				if($GLOBALS['phpgw']->accounts->exists($user))
				{
					$participants[$user] = $GLOBALS['phpgw']->common->grab_owner_name($user).' ('.$this->get_long_status($short_status).')';
				}
			}
			$var['participants'] = Array(
				'field'	=> $this->translate('Participants'),
				'data'	=> $participants
			);

			// Repeated Events
			if($event['recur_type'] != MCAL_RECUR_NONE)
			{
				$str = lang($this->rpt_type[$event['recur_type']]);

				$str_extra = array();
				if ($event['recur_enddate']['mday'] != 0 && $event['recur_enddate']['month'] != 0 && $event['recur_enddate']['year'] != 0)
				{
					$recur_end = $this->maketime($event['recur_enddate']);
					if($recur_end != 0)
					{
						$recur_end -= $GLOBALS['phpgw']->datetime->tz_offset;
						$str_extra[] = lang('ends').': '.lang($GLOBALS['phpgw']->common->show_date($recur_end,'l')).', '.$this->long_date($recur_end).' ';
					}
				}
				// only weekly uses the recur-data (days) !!!
				if($event['recur_type'] == MCAL_RECUR_WEEKLY)
				{
					$repeat_days = array();
					foreach ($this->rpt_day as $mcal_mask => $dayname)
					{
						if ($event['recur_data'] & $mcal_mask)
						{
							$repeat_days[] = lang($dayname);
						}
					}
					if(count($repeat_days))
					{
						$str_extra[] = lang('days repeated').': '.implode(', ',$repeat_days);
					}
				}
				if($event['recur_interval'] != 0)
				{
					$str_extra[] = lang('Interval').': '.$event['recur_interval'];
				}

				if(count($str_extra))
				{
					$str .= ' ('.implode(', ',$str_extra).')';
				}

				$var['recure_type'] = Array(
					'field'	=> lang('Repetition'),
					'data'	=> $str,
				);
			}

			if (!isset($this->fields))
			{
				$this->custom_fields = CreateObject('calendar.bocustom_fields');
				$this->fields = &$this->custom_fields->fields;
				$this->stock_fields = &$this->custom_fields->stock_fields;
			}
			foreach($this->fields as $field => $data)
			{
				if (!$data['disabled'])
				{
					if (isset($var[$field]))
					{
						$sorted[$field] = $var[$field];
					}
					elseif (!isset($this->stock_fields[$field]) && strlen($event[$field]))	// Custom field
					{
						$lang = lang($name = substr($field,1));
						$sorted[$field] = array(
							'field' => $lang == $name.'*' ? $name : $lang,
							'data'  => $event[$field]
						);
					}
				}
				unset($var[$field]);
			}
			foreach($var as $name => $v)
			{
				$sorted[$name] = $v;

			}
			return $sorted;
		}

		/*!
		@function check_set_default_prefs
		@abstract sets the default prefs, if they are not already set (on a per pref. basis)
		@note It sets a flag in the app-session-data to be called only once per session
		*/
		function check_set_default_prefs()
		{
			if (($set = $GLOBALS['phpgw']->session->appsession('default_prefs_set','calendar')))
			{
				return;
			}
			$GLOBALS['phpgw']->session->appsession('default_prefs_set','calendar','set');

			$default_prefs = $GLOBALS['phpgw']->preferences->default['calendar'];

			$subject = $this->translate('Calendar Event') . ' - $$action$$: $$startdate$$ $$title$$'."\n";
			$defaults = array(
				'defaultcalendar' => 'week',
				'mainscreen_showevents' => '0',
				'summary'         => 'no',
				'receive_updates' => 'no',
				'update_format'   => 'extended',	// leave it to extended for now, as iCal kills the message-body
				'notifyAdded'     => $subject . $this->translate ('You have a meeting scheduled for %1',array('$$startdate$$')),
				'notifyCanceled'  => $subject . $this->translate ('Your meeting scheduled for %1 has been canceled',array('$$startdate$$')),
				'notifyModified'  => $subject . $this->translate ('Your meeting that had been scheduled for %1 has been rescheduled to %2',array('$$olddate$$','$$startdate$$')),
				'notifyResponse'  => $subject . $this->translate ('On %1 %2 %3 your meeting request for %4', array('$$date$$','$$fullname$$','$$action$$','$$startdate$$')),
				'notifyAlarm'     => $this->translate('Alarm for %1 at %2 in %3',array('$$title$$','$$startdate$$','$$location$$')) . "\n" . $this->translate('Here is your requested alarm.'),
				'show_rejected'   => '0',
				'hide_event_conflict'   => '0',
				'display_status'  => '1',
				'weekdaystarts'   => 'Monday',
				'workdaystarts'   => '9',
				'workdayends'     => '17',
				'interval'        => '30',
				'defaultlength'   => '60',
				'planner_start_with_group' => $GLOBALS['phpgw']->accounts->name2id('Default'),
				'planner_intervals_per_day'=> '4',
				'defaultfilter'   => 'all',
				'default_private' => '0',
				'display_minicals'=> '1',
				'print_black_white'=>'0'
			);
			foreach($defaults as $var => $default)
			{
				if (!isset($default_prefs[$var]) || $default_prefs[$var] == '')
				{
					$GLOBALS['phpgw']->preferences->add('calendar',$var,$default,'default');
					$need_save = True;
				}
			}
			if ($need_save)
			{
				$prefs = $GLOBALS['phpgw']->preferences->save_repository(False,'default');
				$this->prefs['calendar'] = $prefs['calendar'];
			}
			if ($this->prefs['calendar']['send_updates'] && !isset($this->prefs['calendar']['receive_updates']))
			{
				$this->prefs['calendar']['receive_updates'] = $this->prefs['calendar']['send_updates'];
				$GLOBALS['phpgw']->preferences->add('calendar','receive_updates',$this->prefs['calendar']['send_updates']);
				$GLOBALS['phpgw']->preferences->delete('calendar','send_updates');
				$prefs = $GLOBALS['phpgw']->preferences->save_repository();
			}
		}

		// return array with all infolog categories (for xmlrpc)
		function categories($complete = False)
		{
			return $GLOBALS['server']->categories($complete);
		}
	}
?>
