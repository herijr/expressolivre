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


	class socalendar
	{
//		var $debug = True;
		var $debug = False;
		var $cal;
		var $db;
		var $owner;
		var $g_owner;
		var $is_group = False;
		var $datetime;
		var $filter;
		var $cat_id;
		
		public function __construct( $param )
		{
			$this->db = $GLOBALS['phpgw']->db;
			if(!is_object($GLOBALS['phpgw']->datetime))
			{
				$GLOBALS['phpgw']->datetime = createobject('phpgwapi.date_time');
			}

			$this->owner = (!isset($param['owner']) || $param['owner'] == 0?$GLOBALS['phpgw_info']['user']['account_id']:$param['owner']);
			$this->filter = (isset($param['filter']) && $param['filter'] != ''?$param['filter']:$this->filter);
			$this->cat_id = (isset($param['category']) && $param['category'] != ''?$param['category']:$this->cat_id);
			if(isset($param['g_owner']) && is_array($param['g_owner']))
			{
				$this->is_group = True;
				$this->g_owner = $param['g_owner'];
			}
			if($this->debug)
			{
				echo '<!-- SO Filter : '.$this->filter.' -->'."\n";
				echo '<!-- SO cat_id : '.$this->cat_id.' -->'."\n";
			}
			$this->cal = CreateObject('calendar.socalendar_');
			$this->open_box($this->owner);
		}
		
		// It returns uidNumber and cn ( Retorna o uidNumber e o cn )
		function search_uidNumber($mail)
		{
			$connection = $GLOBALS['phpgw']->common->ldapConnect();
			$justthese = array("uidNumber","cn","mail");
			$search = ldap_search($connection, $GLOBALS['phpgw_info']['server']['ldap_context'], "mail=" . $mail, $justthese);
			$result = ldap_get_entries($connection, $search);
			ldap_close($connection);
			return $result;
		}

		function open_box($owner)
		{
			$this->cal->open('INBOX',(int)$owner);
		}

		function maketime($time)
		{
			return mktime($time['hour'],$time['min'],$time['sec'],$time['month'],$time['mday'],$time['year']);
		}

		function read_entry($id)
		{
			return $this->cal->fetch_event($id);
		}

		function list_events($startYear,$startMonth,$startDay,$endYear=0,$endMonth=0,$endDay=0,$owner_id=0)
		{
			$extra = '';
			$extra .= (strpos($this->filter,'private')?'AND phpgw_cal.is_public=0 ':'');
			//$extra .= ($this->cat_id?"AND phpgw_cal.category like '%".$this->cat_id."%' ":'');
			if ($this->cat_id)
			{
				if (!is_object($GLOBALS['phpgw']->categories))
				{
					$GLOBALS['phpgw']->categories = CreateObject('phpgwapi.categories');
				}
				$cats = $GLOBALS['phpgw']->categories->return_all_children($this->cat_id);
				$extra .= "AND (phpgw_cal.category".(count($cats) > 1 ? " IN ('".implode("','",$cats)."')" : '=\''.(int)$this->cat_id."'");
				foreach($cats as $cat)
				{
					$extra .= " OR phpgw_cal.category LIKE '$cat,%' OR phpgw_cal.category LIKE '%,$cat,%' OR phpgw_cal.category LIKE '%,$cat'";
				}
				$extra .= ') ';
			}
			if($owner_id)
			{
				return $this->cal->list_events($startYear,$startMonth,$startDay,$endYear,$endMonth,$endDay,$extra,$GLOBALS['phpgw']->datetime->tz_offset,$owner_id);
			}
			else
			{
				return $this->cal->list_events($startYear,$startMonth,$startDay,$endYear,$endMonth,$endDay,$extra,$GLOBALS['phpgw']->datetime->tz_offset);
			}
		}

		function get_event_ids( $from = false, $join = false, $where = false, $order = false )
		{
			return $this->cal->get_event_ids( $from, $join, $where, $order );
		}

		function list_repeated_events( $syear, $smonth, $sday,$eyear, $emonth, $eday, $owner_id = 0 )
		{
			if ( !$owner_id ) $owner_id = $this->is_group ? $this->g_owner : $this->owner;

			if ( $GLOBALS['phpgw_info']['server']['calendar_type'] != 'sql' || !count( $owner_id ) ) return array();

			$starttime = mktime( 0, 0, 0, $smonth, $sday, $syear ) - $GLOBALS['phpgw']->datetime->tz_offset;

			return $this->get_event_ids(
				array( 'phpgw_cal_user.cal_login' => $owner_id ),
				array( 'repeats' => true ),
				array(
					'category' => $this->cat_id,
					'private'  => ( strpos( $this->filter, 'private' ) !== false ),
					'( phpgw_cal_repeats.recur_enddate >= '.$starttime.' OR phpgw_cal_repeats.recur_enddate = 0 )',
				),
				array( 'phpgw_cal.datetime ASC', 'phpgw_cal.edatetime ASC', 'phpgw_cal.priority ASC' )
			);
		}

		function list_events_keyword( $keywords, $members = '' )
		{
			return $this->get_event_ids(
				array( 'phpgw_cal_user.cal_login' => $members?: (int)$this->owner ),
				array( 'extra' => true ),
				array(
					'( phpgw_cal_user.cal_login = '.(int)$this->owner.' OR phpgw_cal.is_public = 1 )',
					'category' => $this->cat_id,
					'keywords' => $keywords,
					'private'  => ( strpos( $this->filter, 'private' ) !== false ),
				),
				array( 'phpgw_cal.datetime DESC', 'phpgw_cal.edatetime DESC', 'phpgw_cal.priority ASC' )
			);
		}

		function find_uid( $uid )
		{
			if ( !( $found = $this->get_event_ids( array( 'phpgw_cal.uid ' => $uid ) ) ) )
				$found = $this->get_event_ids( array( 'phpgw_cal.uid ' => $uid ), array( 'repeats' => true ) );
			return is_array( $found )? $found[0] : false;
		}

		function read_from_store($startYear,$startMonth,$startDay,$endYear='',$endMonth='',$endDay='')
		{
			$events = $this->list_events($startYear,$startMonth,$startDay,$endYear,$endMonth,$endDay);
			$events_cached = Array();
			for($i=0;$i<count($events);$i++)
			{
				$events_cached[] = $this->read_entry($events[$i]);
			}
			return $events_cached;
		}

		function add_entry(&$event)
		{
			return $this->cal->save_event($event);
		}

		function save_alarm($cal_id,$alarm,$id=0)
		{
			return $this->cal->save_alarm($cal_id,$alarm,$id);
		}

		function delete_alarm($id)
		{
			return $this->cal->delete_alarm($id);
		}

		function delete_alarms($cal_id)
		{
			return $this->cal->delete_alarms($cal_id);
		}

		function delete_entry($id)
		{
			return $this->cal->delete_event($id);
		}

		function expunge()
		{
			$this->cal->expunge();
		}

		function delete_calendar($owner)
		{
			$this->cal->delete_calendar($owner);
		}

		function change_owner($account_id,$new_owner)
		{
			if($GLOBALS['phpgw_info']['server']['calendar_type'] == 'sql')
			{
				$db2 = $this->cal->stream;
				$this->cal->stream->query('SELECT cal_id FROM phpgw_cal_user WHERE cal_login='.$account_id,__LINE__,__FILE__);
				while($this->cal->stream->next_record())
				{
					$id = $this->cal->stream->f('cal_id');
					$db2->query('SELECT count(*) FROM phpgw_cal_user WHERE cal_id='.$id.' AND cal_login='.$new_owner,__LINE__,__FILE__);
					$db2->next_record();
					if($db2->f(0) == 0)
					{
						$db2->query('UPDATE phpgw_cal_user SET cal_login='.$new_owner.' WHERE cal_id='.$id.' AND cal_login='.$account_id,__LINE__,__FILE__);
					}
					else
					{
						$db2->query('DELETE FROM phpgw_cal_user WHERE cal_id='.$id.' AND cal_login='.$account_id,__LINE__,__FILE__);
					}
				}
				$this->cal->stream->query('UPDATE phpgw_cal SET owner='.$new_owner.' WHERE owner='.$account_id,__LINE__,__FILE__);
			}
		}

		function set_status($id,$status)
		{
			$this->cal->set_status($id,$this->owner,$status);
		}

		function get_alarm($cal_id)
		{
			if (!method_exists($this->cal,'get_alarm'))
			{
				return False;
			}
			return $this->cal->get_alarm($cal_id);
		}

		function read_alarm($id)
		{
			if (!method_exists($this->cal,'read_alarm'))
			{
				return False;
			}
			return $this->cal->read_alarm($id);
		}

		function read_alarms($cal_id)
		{
			if (!method_exists($this->cal,'read_alarms'))
			{
				return False;
			}
			return $this->cal->read_alarms($cal_id);
		}

		function find_recur_exceptions($event_id)
		{
			if($GLOBALS['phpgw_info']['server']['calendar_type'] == 'sql')
			{
				$arr = Array();
				$this->cal->query('SELECT datetime FROM phpgw_cal WHERE reference='.$event_id,__LINE__,__FILE__);
				if($this->cal->num_rows())
				{
					while($this->cal->next_record())
					{
						$arr[] = (int)$this->cal->f('datetime');
					}
				}
				if(count($arr) == 0)
				{
					return False;
				}
				else
				{
					return $arr;
				}
			}
			else
			{
				return False;
			}
		}

		/* Begin mcal equiv functions */
		function get_cached_event()
		{
			return $this->cal->event;
		}
		
		function add_attribute($var,$value,$element='**(**')
		{
			$this->cal->add_attribute($var,$value,$element);
		}

		function event_init()
		{
			$this->cal->event_init();
		}

		function set_date($element,$year,$month,$day=0,$hour=0,$min=0,$sec=0)
		{
			$this->cal->set_date($element,$year,$month,$day,$hour,$min,$sec);
		}

		function set_start($year,$month,$day=0,$hour=0,$min=0,$sec=0)
		{
			$this->cal->set_start($year,$month,$day,$hour,$min,$sec);
		}

		function set_end($year,$month,$day=0,$hour=0,$min=0,$sec=0)
		{
			$this->cal->set_end($year,$month,$day,$hour,$min,$sec);
		}

		function set_title($title='')
		{
			$this->cal->set_title($title);
		}

		function set_description($description='')
		{
			$this->cal->set_description($description);
		}
		function set_ex_participants($ex_participants='')
		{
			$this->cal->set_ex_participants($ex_participants);
		}

		function set_class($class)
		{
			$this->cal->set_class($class);
		}

		function set_category($category='')
		{
			$this->cal->set_category($category);
		}

		function set_alarm($alarm)
		{
			$this->cal->set_alarm($alarm);
		}

		function set_recur_none()
		{
			$this->cal->set_recur_none();
		}

		function set_recur_daily($year,$month,$day,$interval)
		{
			$this->cal->set_recur_daily($year,$month,$day,$interval);
		}

		function set_recur_weekly($year,$month,$day,$interval,$weekdays)
		{
			$this->cal->set_recur_weekly($year,$month,$day,$interval,$weekdays);
		}

		function set_recur_monthly_mday($year,$month,$day,$interval)
		{
			$this->cal->set_recur_monthly_mday($year,$month,$day,$interval);
		}

		function set_recur_monthly_wday($year,$month,$day,$interval)
		{
			$this->cal->set_recur_monthly_wday($year,$month,$day,$interval);
		}

		function set_recur_yearly($year,$month,$day,$interval)
		{
			$this->cal->set_recur_yearly($year,$month,$day,$interval);
		}
		
		/* End mcal equiv functions */
	}
?>
