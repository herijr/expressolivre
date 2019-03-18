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


	if (@$GLOBALS['phpgw_info']['flags']['included_classes']['socalendar_'])
	{
		return;
	}

	$GLOBALS['phpgw_info']['flags']['included_classes']['socalendar_'] = True;

	class socalendar_ extends socalendar__
	{
		var $deleted_events = Array();

		var $cal_event;
		var $today = Array('raw','day','month','year','full','dow','dm','bd');


		public function __construct()
		{
			parent::__construct();

			if (!is_object($GLOBALS['phpgw']->asyncservice))
			{
				$GLOBALS['phpgw']->asyncservice = CreateObject('phpgwapi.asyncservice');
			}
			$this->async = &$GLOBALS['phpgw']->asyncservice;
		}

		function open($calendar='',$user='',$passwd='',$options='')
		{
			if($user=='')
			{
	//			settype($user,'integer');
				$this->user = $GLOBALS['phpgw_info']['user']['account_id'];
			}
			elseif(is_int($user))
			{
				$this->user = $user;
			}
			elseif(is_string($user))
			{
				$this->user = $GLOBALS['phpgw']->accounts->name2id($user);
			}

			$this->stream = $GLOBALS['phpgw']->db;
			return $this->stream;
		}

		function popen($calendar='',$user='',$passwd='',$options='')
		{
			return $this->open($calendar,$user,$passwd,$options);
		}

		function reopen($calendar,$options='')
		{
			return $this->stream;
		}

		function close($options='')
		{
			return True;
		}

		function create_calendar($calendar='')
		{
			return $calendar;
		}

		function rename_calendar($old_name='',$new_name='')
		{
			return $new_name;
		}

		function delete_calendar($calendar='')
		{
			$this->stream->query('SELECT cal_id FROM phpgw_cal WHERE owner='.(int)$calendar,__LINE__,__FILE__);
			if($this->stream->num_rows())
			{
				while($this->stream->next_record())
				{
					$this->delete_event((int)$this->stream->f('cal_id'));
				}
				$this->expunge();
			}
			$this->stream->lock(array('phpgw_cal_user'));
			$this->stream->query('DELETE FROM phpgw_cal_user WHERE cal_login='.(int)$calendar,__LINE__,__FILE__);
			$this->stream->unlock();

			return $calendar;
		}

		/*!
		@function read_alarms
		@abstract read the alarms of a calendar-event specified by $cal_id
		@returns array of alarms with alarm-id as key
		@note the alarm-id is a string of 'cal:'.$cal_id.':'.$alarm_nr, it is used as the job-id too
		*/
		function read_alarms($cal_id)
		{
			$alarms = array();

			if( $jobs = $this->async->read('cal:'.(int)$cal_id.':%') )
			{
				foreach($jobs as $id => $job)
				{
					$alarm         = $job['data'];	// text, enabled
					$alarm['id']   = $id;
					$alarm['time'] = $job['next'];

					$alarms[$id] = $alarm;
				}
			}
			else if($jobs = $this->async->read('webconf:'.(int)$cal_id.':%'))
			{
				foreach($jobs as $id => $job)
				{
					$alarm         = $job['data'];	// text, enabled
					$alarm['id']   = $id;
					$alarm['time'] = $job['next'];

					$alarms[$id] = $alarm;
				}
			}
			
			return $alarms;
		}

		/*!
		@function read_alarm
		@abstract read a single alarm specified by it's $id
		@returns array with data of the alarm
		@note the alarm-id is a string of 'cal:'.$cal_id.':'.$alarm_nr, it is used as the job-id too
		*/
		function read_alarm($id)
		{
			if (!($jobs = $this->async->read($id)))
			{
				return False;
			}
			list($id,$job) = each($jobs);
			$alarm         = $job['data'];	// text, enabled
			$alarm['id']   = $id;
			$alarm['time'] = $job['next'];

			//echo "<p>read_alarm('$id')="; print_r($alarm); echo "</p>\n";
			return $alarm;
		}

/* Funcao save_alarm modificada para gerar alarmes repetidos, caso seja marcado um evento igualmente repetido. A funcao recebe qual o tipo
de repeticao escolhido pelo usuario (diario, semanal, mensal, anual) e insere alarmes nas respectivas repeticoes do evento. */
		/*!
		@function save_alarm
		@abstract saves a new or updated alarm
		@syntax save_alarm($cal_id,$alarm,$id=False)
		@param $cal_id Id of the calendar-entry
		@param $alarm array with fields: text, owner, enabled, ..
		@returns the id of the alarm
		*/
		function save_alarm($cal_id,$alarm)
		{

			if (!($id = $alarm['id']))
			{
				$alarm['time'] -= $GLOBALS['phpgw']->datetime->tz_offset;	// time should be stored in server timezone
				$alarms = $this->read_alarms($cal_id);	// find a free alarm#

				if($alarm['repeat'] == 1) // repeticao do tipo "Diariamente";
				{
					$n = 0;

					while(@isset($alarms[$id]));
					{

						$init_alarm = $alarm['init_rept'];
						$end_alarm = $alarm['end_rept'];

						if($end_alarm != 0)
						{
							while($init_alarm <= $end_alarm)
							{
								$id = 'cal:'.(int)$cal_id.':'.$n;
								$n++;
								
								$alarm['cal_id'] = $cal_id;		// we need the back-reference

								unset($alarm['repeat']);
								unset($alarm['init_rept']);
								unset($alarm['end_rept']);
								unset($alarm['rpt_wdays']);

								if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
								{
									return False;
								}

								$alarm['time'] += 86400;
								$init_alarm += 86400;
							}
						}
					}

				}elseif($alarm['repeat'] == 2) { // repeticao do tipo "Semanalmente";

					$n = 0;

					$init_alarm = $data_atual = $alarm['init_rept'];
					$end_alarm = $alarm['end_rept'];

					$rpt_alarm_wdays = $alarm['rpt_wdays'];

					$divisor = 64;
					$quociente = 0;
					$resto = 0;

					$dia_semana = date("w",$init_alarm);

					switch($dia_semana)
					{
						case 0:
							$dia = array(
									0 => 'domingo',
									1 => 'segunda',
									2 => 'terca',
									3 => 'quarta',
									4 => 'quinta',
									5 => 'sexta',
									6 => 'sabado'
							);
							break;
						case 1:
							$dia = array(
									0 => 'segunda',
									1 => 'terca',
									2 => 'quarta',
									3 => 'quinta',
									4 => 'sexta',
									5 => 'sabado',
									6 => 'domingo'
							);
							break;
						case 2:
							$dia = array(
									0 => 'terca',
									1 => 'quarta',
									2 => 'quinta',
									3 => 'sexta',
									4 => 'sabado',
									5 => 'domingo',
									6 => 'segunda'
							);
							break;
						case 3:
							$dia = array(
									0 => 'quarta',
									1 => 'quinta',
									2 => 'sexta',
									3 => 'sabado',
									4 => 'domingo',
									5 => 'segunda',
									6 => 'terca'
							);
							break;
						case 4:
							$dia = array(
									0 => 'quinta',
									1 => 'sexta',
									2 => 'sabado',
									3 => 'domingo',
									4 => 'segunda',
									5 => 'terca',
									6 => 'quarta'
							);
							break;
						case 5:
							$dia = array(
									0 => 'sexta',
									1 => 'sabado',
									2 => 'domingo',
									3 => 'segunda',
									4 => 'terca',
									5 => 'quarta',
									6 => 'quinta'
							);
							break;
						case 6:
							$dia = array(
									0 => 'sabado',
									1 => 'domingo',
									2 => 'segunda',
									3 => 'terca',
									4 => 'quarta',
									5 => 'quinta',
									6 => 'sexta'
							);
							break;
					}


					$dias_semana = array(
								64 => 'sabado',
								32 => 'sexta',
								16 => 'quinta',
								8 => 'quarta',
								4 => 'terca',
								2 => 'segunda',
								1 => 'domingo'
							);

					$result = array(); 
					do
					{

						$resto = ($rpt_alarm_wdays % $divisor);
						$quociente = floor($rpt_alarm_wdays / $divisor);

						if($quociente == 1)
						{
							$result[] = $dias_semana[$divisor];

							$divisor = $divisor / 2;
							$rpt_alarm_wdays = $resto;

						}else {

							while($rpt_alarm_wdays < $divisor)
							{
								$divisor = $divisor / 2;
							}

							$resto = ($rpt_alarm_wdays % $divisor);
							$quociente = floor($rpt_alarm_wdays / $divisor);

							if($quociente == 1)
							{

								$result[] = $dias_semana[$divisor];

								$divisor = $divisor / 2;
								$rpt_alarm_wdays = $resto;
							}
						}

					}
					while($resto != 0);

					krsort($result);

					$week_num = 0;
					$y = 0;

					while(@isset($alarms[$id]));
					{

						if($end_alarm != 0)
						{

							while($data_atual <= $end_alarm)
							{

								foreach($dia as $index => $value)
								{
									if(in_array($value,$result))
									{

										$nova_data = $init_alarm + (86400 * ($index + (7 * $y)));

										if($nova_data == $init_alarm){

											continue;
										}

										$id = 'cal:'.(int)$cal_id.':'.$n;
										$n++;

										$alarm['cal_id'] = $cal_id;		// we need the back-reference

										unset($alarm['repeat']);
										unset($alarm['init_rept']);
										unset($alarm['end_rept']);
										unset($alarm['rpt_wdays']);
										
										$data_atual = $nova_data;

										if($data_atual > $end_alarm){
											if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
											{
												return False;
											}
											break;
										}

										if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
										{
											return False;
										}
											$alarm['time'] = $nova_data - $alarm['offset'] - $GLOBALS['phpgw']->datetime->tz_offset; 
									}
								}
								$y++;
							}
						}
					}

				}elseif($alarm['repeat'] == 3) { // repeticao do tipo "Mensalmente (por data)";

					$n = 0;

					$init_alarm = $alarm['init_rept'];
					$end_alarm = $alarm['end_rept'];
					
					while(@isset($alarms[$id]));
					{

						if($end_alarm != 0)
						{
							while($init_alarm <= $end_alarm)
							{

								$next_month = date("n",$init_alarm) + 1;
								$next_alarm = mktime(date("G",$alarm['time']),date("i",$alarm['time']),date("s",$alarm['time']),$next_month,date("j",$alarm['time']),date("Y",$alarm['time']));

								$id = 'cal:'.(int)$cal_id.':'.$n;
								$n++;

								$alarm['cal_id'] = $cal_id;		// we need the back-reference

								unset($alarm['repeat']);
								unset($alarm['init_rept']);
								unset($alarm['end_rept']);
								unset($alarm['rpt_wdays']);

								if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
								{
									return False;
								}

								$alarm['time'] = $next_alarm;
								$init_alarm = $next_alarm;

							}
						}
					}

				}elseif($alarm['repeat'] == 5) { // repeticao do tipo "Anualmente";

					$n = 0;

					$init_alarm = $alarm['init_rept'];
					$end_alarm = $alarm['end_rept'];
					
					while(@isset($alarms[$id]));
					{


						if($end_alarm != 0)
						{
							while($init_alarm < $end_alarm)
							{

								$next_year = date("Y",$init_alarm) + 1;
								$next_alarm = mktime(date("G",$alarm['time']),date("i",$alarm['time']),date("s",$alarm['time']),date("n",$alarm['time']),date("j",$alarm['time']),$next_year);

								$id = 'cal:'.(int)$cal_id.':'.$n;
								$n++;

								$alarm['cal_id'] = $cal_id;		// we need the back-reference

								unset($alarm['repeat']);
								unset($alarm['init_rept']);
								unset($alarm['end_rept']);
								unset($alarm['rpt_wdays']);

								if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
								{
									return False;
								}

								$alarm['time'] = $next_alarm;
								$init_alarm = $next_alarm;
							}
						}
					}

				}else {
					$alarm['time'] -= $GLOBALS['phpgw']->datetime->tz_offset;	// time should be stored in server timezone
					$n = count($alarms);
					while(@isset($alarms[$id]));
					{

						$id = 'cal:'.(int)$cal_id.':'.$n;

						unset($alarm['repeat']);
						unset($alarm['init_rept']);
						unset($alarm['end_rept']);
						unset($alarm['rpt_wdays']);

						$alarm['cal_id'] = $cal_id;		// we need the back-reference
						if (!$this->async->set_timer($alarm['time'],$id,'calendar.bocalendar.send_alarm',$alarm))
						{
							return False;
						}

						++$n;
					}
				}

			}
			else
			{
				$this->async->cancel_timer($id);
			}
			$alarm['time'] -= $GLOBALS['phpgw']->datetime->tz_offset;	// time should be stored in server timezone
			return $id;
		}

		/*!
		@function delete_alarms($cal_id)
		@abstract delete all alarms of a calendar-entry
		@returns the number of alarms deleted
		*/
		function delete_alarms($cal_id)
		{
			$alarms = $this->read_alarms($cal_id);

			foreach($alarms as $id => $alarm)
			{
				$this->async->cancel_timer($id);
			}
			return count($alarms);
		}

		/*!
		@function delete_alarm($id)
		@abstract delete one alarms identified by its id
		@returns the number of alarms deleted
		*/
		function delete_alarm($id)
		{
			return $this->async->cancel_timer($id);
		}

		function fetch_event($event_id,$options='')
		{
			if(!isset($this->stream))
			{
				return False;
			}

			$event_id = (int)$event_id;

			$this->stream->lock(array('phpgw_cal','phpgw_cal_user','phpgw_cal_repeats','phpgw_cal_extra'/* OLD-ALARM,'phpgw_cal_alarm'*/));

			$this->stream->query('SELECT * FROM phpgw_cal WHERE cal_id='.$event_id,__LINE__,__FILE__);

			if($this->stream->num_rows() > 0)
			{
				$this->event_init();

				$this->stream->next_record();
				// Load the calendar event data from the db into $event structure
				// Use http://www.php.net/manual/en/function.mcal-fetch-event.php as the reference
				$this->add_attribute('owner',(int)$this->stream->f('owner'));
				$this->add_attribute('id',(int)$this->stream->f('cal_id'));
				$this->add_attribute('type',$this->stream->f('cal_type'));
				$this->set_class((int)$this->stream->f('is_public'));
				$this->set_category($this->stream->f('category'));
				$this->set_title(stripslashes($GLOBALS['phpgw']->strip_html($this->stream->f('title'))));
				$this->set_description(stripslashes($GLOBALS['phpgw']->strip_html($this->stream->f('description'))));
				$this->set_ex_participants(stripslashes($GLOBALS['phpgw']->strip_html($this->stream->f('ex_participants'))));
				$this->add_attribute('uid',$GLOBALS['phpgw']->strip_html($this->stream->f('uid')));
				$this->add_attribute('location',stripslashes($GLOBALS['phpgw']->strip_html($this->stream->f('location'))));
				$this->add_attribute('reference',(int)$this->stream->f('reference'));

				// This is the preferred method once everything is normalized...
				//$this->event->alarm = (int)$this->stream->f('alarm');
				// But until then, do it this way...
				//Legacy Support (New)

				$datetime = $GLOBALS['phpgw']->datetime->localdates($this->stream->f('datetime'));
				$this->set_start($datetime['year'],$datetime['month'],$datetime['day'],$datetime['hour'],$datetime['minute'],$datetime['second']);

				$datetime = $GLOBALS['phpgw']->datetime->localdates($this->stream->f('mdatetime'));
				$this->set_date('modtime',$datetime['year'],$datetime['month'],$datetime['day'],$datetime['hour'],$datetime['minute'],$datetime['second']);

				$datetime = $GLOBALS['phpgw']->datetime->localdates($this->stream->f('edatetime'));
				$this->set_end($datetime['year'],$datetime['month'],$datetime['day'],$datetime['hour'],$datetime['minute'],$datetime['second']);

			//Legacy Support
				$this->add_attribute('priority',(int)$this->stream->f('priority'));
				if($this->stream->f('cal_group') || $this->stream->f('groups') != 'NULL')
				{
					$groups = explode(',',$this->stream->f('groups'));
					for($j=1;$j<count($groups) - 1;$j++)
					{
						$this->add_attribute('groups',$groups[$j],$j-1);
					}
				}

				$this->stream->query('SELECT * FROM phpgw_cal_repeats WHERE cal_id='.$event_id,__LINE__,__FILE__);
				if($this->stream->num_rows())
				{
					$this->stream->next_record();

					$this->add_attribute('recur_type',(int)$this->stream->f('recur_type'));
					$this->add_attribute('recur_interval',(int)$this->stream->f('recur_interval'));
					$enddate = $this->stream->f('recur_enddate');
					if($enddate != 0 && $enddate != Null)
					{
						$datetime = $GLOBALS['phpgw']->datetime->localdates($enddate);
						$this->add_attribute('recur_enddate',$datetime['year'],'year');
						$this->add_attribute('recur_enddate',$datetime['month'],'month');
						$this->add_attribute('recur_enddate',$datetime['day'],'mday');
						$this->add_attribute('recur_enddate',$datetime['hour'],'hour');
						$this->add_attribute('recur_enddate',$datetime['minute'],'min');
						$this->add_attribute('recur_enddate',$datetime['second'],'sec');
					}
					else
					{
						$this->add_attribute('recur_enddate',0,'year');
						$this->add_attribute('recur_enddate',0,'month');
						$this->add_attribute('recur_enddate',0,'mday');
						$this->add_attribute('recur_enddate',0,'hour');
						$this->add_attribute('recur_enddate',0,'min');
						$this->add_attribute('recur_enddate',0,'sec');
					}
					$this->add_attribute('recur_enddate',0,'alarm');
					if($this->debug)
					{
						echo 'Event ID#'.$this->event['id'].' : Enddate = '.$enddate."<br>\n";
					}
					$this->add_attribute('recur_data',$this->stream->f('recur_data'));

					$exception_list = $this->stream->f('recur_exception');
					$exceptions = Array();
					if(strpos(' '.$exception_list,','))
					{
						$exceptions = explode(',',$exception_list);
					}
					elseif($exception_list != '')
					{
						$exceptions[]= $exception_list;
					}
					$this->add_attribute('recur_exception',$exceptions);
				}

			//Legacy Support
				$this->stream->query('SELECT * FROM phpgw_cal_user WHERE cal_id='.$event_id,__LINE__,__FILE__);
				if($this->stream->num_rows())
				{
					while($this->stream->next_record())
					{
						if((int)$this->stream->f('cal_login') == (int)$this->user)
						{
							$this->add_attribute('users_status',$this->stream->f('cal_status'));
						}
						$this->add_attribute('participants',$this->stream->f('cal_status'),(int)$this->stream->f('cal_login'));
					}
				}

			// Custom fields
				$this->stream->query('SELECT * FROM phpgw_cal_extra WHERE cal_id='.$event_id,__LINE__,__FILE__);
				if($this->stream->num_rows())
				{
					while($this->stream->next_record())
					{
						$this->add_attribute('#'.$this->stream->f('cal_extra_name'),$this->stream->f('cal_extra_value'));
					}
				}

	/* OLD-ALARM
				if($this->event['reference'])
				{
					// What is event['reference']???
					$alarm_cal_id = $event_id.','.$this->event['reference'];
				}
				else
				{
					$alarm_cal_id = $event_id;
				}

				//echo '<!-- cal_id='.$alarm_cal_id.' -->'."\n";
				//$this->stream->query('SELECT * FROM phpgw_cal_alarm WHERE cal_id in ('.$alarm_cal_id.') AND cal_owner='.$this->user,__LINE__,__FILE__);
				$this->stream->query('SELECT * FROM phpgw_cal_alarm WHERE cal_id='.$event_id.' AND cal_owner='.$this->user,__LINE__,__FILE__);
				if($this->stream->num_rows())
				{
					while($this->stream->next_record())
					{
						$this->event['alarm'][] = Array(
							'id'		=> (int)$this->stream->f('alarm_id'),
							'time'	=> (int)$this->stream->f('cal_time'),
							'text'	=> $this->stream->f('cal_text'),
							'enabled'	=> (int)$this->stream->f('alarm_enabled')
						);
					}
				}
	*/
			}
			else
			{
				$this->event = False;
			}

			$this->stream->unlock();

			if ($this->event)
			{
				$this->event['alarm'] = $this->read_alarms($event_id);

				if($this->event['reference'])
				{
					$this->event['alarm'] += $this->read_alarms($event_id);
				}
			}
			return $this->event;
		}

		function append_event()
		{
			$this->save_event($this->event);
			$this->send_update(MSG_ADDED,$this->event->participants,'',$this->event);
			return $this->event['id'];
		}

		function store_event()
		{
			return $this->save_event($this->event);
		}

		function delete_event($event_id)
		{
			$this->deleted_events[] = $event_id;
		}

		function snooze($event_id)
		{
		//Turn off an alarm for an event
		//Returns true.
		}

		function list_alarms($begin_year='',$begin_month='',$begin_day='',$end_year='',$end_month='',$end_day='')
		{
		//Return a list of events that has an alarm triggered at the given datetime
		//Returns an array of event ID's
		}

		// The function definition doesn't look correct...
		// Need more information for this function
		function next_recurrence($weekstart,$next)
		{
	//		return next_recurrence (int stream, int weekstart, array next);
		}

		function expunge()
		{
			if(count($this->deleted_events) <= 0)
			{
				return 1;
			}
			$this_event = $this->event;
			$locks = Array(
				'phpgw_cal',
				'phpgw_cal_user',
				'phpgw_cal_repeats',
				'phpgw_cal_extra'
	// OLD-ALARM			'phpgw_cal_alarm'
			);
			$this->stream->lock($locks);
			foreach($this->deleted_events as $cal_id)
			{
				foreach ($locks as $table)
				{
					$this->stream->query('DELETE FROM '.$table.' WHERE cal_id='.$cal_id,__LINE__,__FILE__);
				}
			}
			$this->stream->unlock();

			foreach($this->deleted_events as $cal_id)
			{
				$this->delete_alarms($cal_id);
			}
			$this->deleted_events = array();

			$this->event = $this_event;
			return 1;
		}

		function list_events( $startYear, $startMonth, $startDay, $endYear = 0, $endMonth = 0, $endDay = 0, $extra = '', $tz_offset = 0, $owner_id = 0 )
		{
			if ( !isset( $this->stream ) ) return false;

			$from = ( is_array( $owner_id ) && count( $owner_id ) )?
			array( 'phpgw_cal_user.cal_login' => $owner_id ) :
			array( 'phpgw_cal.owner' => $this->user, 'phpgw_cal_user.cal_login' => $this->user );

			$ini = mktime( 0, 0, 0, $startMonth, $startDay, $startYear ) - $tz_offset;
			$end = ( $endYear != 0 && $endMonth != 0 && $endDay != 0 )? ( mktime( 23, 59, 59, (int)$endMonth, (int)$endDay, (int)$endYear ) - $tz_offset ) : ( mktime( 23, 59, 59, (int)$endMonth, (int)$endDay, (int)($endYear)+1 ) - $tz_offset );
			$eyr = ( $end + 31536000 );

			$interval  = '( '.
				'( ( phpgw_cal.datetime  BETWEEN '.$ini.' AND '.$end.' ) OR  ( phpgw_cal.edatetime BETWEEN '.$ini.' AND '.$end.' ) ) OR '.
				'( ( phpgw_cal.datetime  BETWEEN 0        AND '.$ini.' ) AND ( phpgw_cal.edatetime BETWEEN '.$ini.' AND '.$eyr.' ) ) OR '.
				'( ( phpgw_cal.edatetime BETWEEN '.$end.' AND '.$eyr.' ) AND ( phpgw_cal.datetime  BETWEEN 0 AND '.$end    .' ) ) )'.
				$extra;

			return $this->get_event_ids(
				array(
					'phpgw_cal.owner' => $this->user,
					'phpgw_cal_user.cal_login' => $this->user,
				),
				false,
				array( $interval ),
				array( 'phpgw_cal.datetime ASC', 'phpgw_cal.edatetime ASC', 'phpgw_cal.priority ASC' )
			);
		}

		function list_dirty_events( $lastmod = -1, $repeats = false )
		{
			if ( !isset( $this->stream ) ) return false;

			$lastmod = (int)$lastmod;

			return $this->get_event_ids(
				array( 'phpgw_cal_user.cal_login' => $this->user ),
				array( 'repeats' => !!$repeats ),
				array( ( $lastmod > 0 )? 'mdatetime = '.$lastmod : false ),
				array( 'phpgw_cal.cal_id ASC' )
			);
		}
		/***************** Local functions for SQL based Calendar *****************/

		function get_event_ids( $from = false, $join = false, $where = false, $order = false )
		{
			$sql = $this->qry_implode( ' ', array(
				'SELECT DISTINCT phpgw_cal.cal_id, phpgw_cal.datetime,phpgw_cal.edatetime,phpgw_cal.priority',
				$this->parse_from( $from, $join, $where ),
				$this->parse_join( $join ),
				$this->parse_where( $where ),
				$this->parse_order( $order ),
			), false, ' ' );

			if ( $this->debug ) echo 'FULL SQL : '.$sql.'<br>'.PHP_EOL;

			$this->stream->query( $sql, __LINE__, __FILE__ );

			$retval = Array();
			if ( $this->stream->num_rows() == 0 ) {
				if ( $this->debug ) echo 'No records found!<br>'.PHP_EOL;
				return $retval;
			}

			while ( $this->stream->next_record() ) $retval[] = (int)$this->stream->f( 'cal_id' );

			if ( $this->debug ) echo 'Records found!<br>'.PHP_EOL;

			return $retval;
		}

		protected function parse_from( $from, &$join, &$where )
		{
			$map = array_reduce( array_keys( (array)$from ), function( $c, $i ){ $c[preg_filter( '/\..*/', '', $i)] = $i; return $c; }, array() );
			switch ( count( $map ) ) {
				case 0: return 'FROM phpgw_cal'; break;
				case 1:
					$tb = key( $map );
					$on = $map[$tb].( is_array( $from[$map[$tb]] )? ' IN ( '.$this->qry_quote( $from[$map[$tb]] ).' )' : ' = '.$this->qry_quote( $from[$map[$tb]] ) );
					if ( $tb === 'phpgw_cal' ) {
						if ( !is_array( $where ) ) $where = (array)$where;
						array_unshift( $where, $on );
						return 'FROM phpgw_cal'; 
					}
					if ( !is_array( $join ) ) $join = (array)$join;
					array_unshift( $join, 'JOIN '.$tb.' ON '.$tb.'.cal_id = phpgw_cal.cal_id AND '.$on );
					return 'FROM phpgw_cal'; 
					break;
				default:
					$suq_qry = array();
					foreach ( $map as $tb => $key ) $suq_qry[] = 'SELECT DISTINCT '.$tb.'.cal_id FROM '.$tb.' WHERE '.$map[$tb].( is_array( $from[$map[$tb]] )? ' IN ( '.$this->qry_quote( $from[$map[$tb]] ).' )' : ' = '.$this->qry_quote( $from[$map[$tb]] ) );
					return 'FROM phpgw_cal JOIN ( SELECT DISTINCT cal_id FROM ( '.implode( ' UNION ALL ', $suq_qry ).' ) AS sub1 ) AS sub2 ON sub2.cal_id = phpgw_cal.cal_id';
			}
			return false;
		}

		protected function parse_join( $join )
		{
			$join = $this->qry_filter( $join );

			if ( isset( $join['repeats'] ) ) $join['repeats'] = 'LEFT JOIN phpgw_cal_repeats ON phpgw_cal_repeats.cal_id = phpgw_cal.cal_id';
			if ( isset( $join['extra'] ) ) $join['extra']  = 'LEFT JOIN phpgw_cal_extra ON phpgw_cal_extra.cal_id = phpgw_cal.cal_id';

			return $this->qry_implode( ' ', $join );
		}

		protected function parse_where( $where )
		{
			$where = $this->qry_filter( $where );

			if ( isset( $where['private']  ) ) $where['private']  = 'phpgw_cal.is_public = 0';
			if ( isset( $where['category'] ) ) $where['category'] = ( ((int)$where['category']) <= 0 )? false : 'string_to_array( phpgw_cal.category, \',\' ) @> ARRAY[ '.$this->qry_quote( $where['category'] ).' ]';
			if ( isset( $where['keywords'] ) ) $where['keywords'] = '( '.$this->qry_implode( ' OR ', array_reduce( explode( ' ', $where['keywords'] ), function( $c, $i ){ foreach ( array( 'phpgw_cal.title', 'phpgw_cal.description', 'phpgw_cal.location', 'phpgw_cal_extra.cal_extra_value' ) as $v ) $c[] = 'UPPER( '.$v.' ) LIKE UPPER( \'%'.addslashes( addcslashes( $i, '_%' ) ).'%\')'; return $c; }, array() ) ).' )';

			return $this->qry_implode( false, $where, 'WHERE' );
		}

		protected function parse_order( $order )
		{
			return $this->qry_implode( ', ', $this->qry_filter( $order ), 'ORDER BY' );
		}

		protected function qry_filter( $param )
		{
			return array_filter( (array)$param, function( $v ) {
				return !(
					$v === false ||
					is_null( $v ) ||
					( is_array( $v ) && count( $v ) === 0 ) ||
					( is_string( $v ) && $v === '' )
				);
			} );
		}

		protected function qry_implode( $glue, $arr, $prefix = false )
		{
			if ( is_null($glue) || $glue === false ) { $glue = ' '.( $arr['glue']?: 'AND' ).' '; unset( $arr['glue'] ); }
			return ( count( $arr ) === 0 )? false : ( ($prefix !== false)? $prefix.' ': '' ).implode( $glue, $this->qry_filter( $arr ) );
		}

		protected function qry_quote( $param )
		{
			if ( is_string( $param ) ) return '\''.$param.'\'';
			if ( is_int( $param    ) ) return $param;
			return implode( ', ', array_reduce( $param, function( $c, $i ){ $c[] = $this->qry_quote( $i ); return $c; }, array() ) );
		}

		function save_event( &$event )
		{
			$locks = Array(
				'phpgw_cal',
				'phpgw_cal_user',
				'phpgw_cal_repeats',
				'phpgw_cal_extra'
			);

			$this->stream->lock($locks);

			if($event['id'] == 0)
			{
				if(!$event['uid'])
				{
					if ($GLOBALS['phpgw_info']['server']['hostname'] != '')
					{
						$id_suffix = $GLOBALS['phpgw_info']['server']['hostname'];
					}
					else
					{
						$id_suffix = $GLOBALS['phpgw']->common->randomstring(3).'local';
					}
					$parts = Array(
						0 => 'title',
						1 => 'description',
						2 => 'ex_participants'
					);
					@reset($parts);
					while(list($key,$field) = each($parts))
					{
						$part[$key] = substr($GLOBALS['phpgw']->crypto->encrypt($event[$field]),0,20);
						if(!$GLOBALS['phpgw']->crypto->enabled)
						{
							$part[$key] = bin2hex(unserialize($part[$key]));
						}
					}
					$event['uid'] = $part[0].'-'.$part[1].'@'.$id_suffix;
				}
				$this->stream->query('INSERT INTO phpgw_cal(uid,title,owner,priority,is_public,category) '
					. "values('".$event['uid']."','".pg_escape_string($event['title'])
					. "',".(int)$event['owner'].','.(int)$event['priority'].','.(int)$event['public'].",'"
					. $event['category']."')",__LINE__,__FILE__);
				$event['id'] = $this->stream->get_last_insert_id('phpgw_cal','cal_id');
				$last_status = true;
			}

			$date = $this->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$enddate = $this->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$today = time() - $GLOBALS['phpgw']->datetime->tz_offset;

			if( $event['type'] == 'hourAppointment'){ $type = 'H'; }
			else if($event['type'] == 'privateHiddenFields'){ $type = 'P'; }
			else { $type = 'E'; }

			$sql = 'UPDATE phpgw_cal SET '
            		. 'owner='.(int)$event['owner'].', '
            		. 'datetime='.(int)$date.', '
            		. 'mdatetime='.(int)$today.', '
            		. 'edatetime='.(int)$enddate.', '
            		. 'priority='.(int)$event['priority'].', '
            		. "category='".$this->stream->db_addslashes($event['category'])."', "
            		. "cal_type='".$this->stream->db_addslashes($type)."', "
            		. 'is_public='.(int)$event['public'].', '
            		. "title='".pg_escape_string($event['title'])."', "
            		. "description='".pg_escape_string($event['description'])."', "
            		. "ex_participants='".$this->stream->db_addslashes($event['ex_participants'])."', "
            		. "location='".pg_escape_string($event['location'])."', "
            		. ($event['groups']?"groups='".(count($event['groups'])>1?implode(',',$event['groups']):','.$event['groups'][0].',')."', ":'')
            		. 'reference='.(int)$event['reference'].' '
            		. ',last_status = '.($last_status ? "'N'" : "'U'").',last_update = '.time()."000". ' '
            		. 'WHERE cal_id='.(int)$event['id'];

			$this->stream->query($sql,__LINE__,__FILE__);

			$this->stream->query('DELETE FROM phpgw_cal_user WHERE cal_id='.(int)$event['id'],__LINE__,__FILE__);

			@reset($event['participants']);
			while (list($key,$value) = @each($event['participants']))
			{
				if((int)$key == $event['owner'])
				{
					$value = 'A';
				}
				$this->stream->query('INSERT INTO phpgw_cal_user(cal_id,cal_login,cal_status) '
					. 'VALUES('.(int)$event['id'].','.(int)$key.",'".$this->stream->db_addslashes($value)."')",__LINE__,__FILE__);
			}

			if($event['recur_type'] != MCAL_RECUR_NONE)
			{
				if($event['recur_enddate']['month'] != 0 && $event['recur_enddate']['mday'] != 0 && $event['recur_enddate']['year'] != 0)
				{
					$end = $this->maketime($event['recur_enddate']) - $GLOBALS['phpgw']->datetime->tz_offset;
				}
				else
				{
					$end = 0;
				}

				$this->stream->query('SELECT count(cal_id) FROM phpgw_cal_repeats WHERE cal_id='.(int)$event['id'],__LINE__,__FILE__);
				$this->stream->next_record();
				$num_rows = $this->stream->f(0);
				if($num_rows == 0)
				{
					$this->stream->query('INSERT INTO phpgw_cal_repeats(cal_id,recur_type,recur_enddate,recur_data,recur_interval) '
						.'VALUES('.(int)$event['id'].','.$event['recur_type'].','.(int)$end.','.$event['recur_data'].','.$event['recur_interval'].')',__LINE__,__FILE__);
				}
				else
				{
					$this->stream->query('UPDATE phpgw_cal_repeats '
						. 'SET recur_type='.$event['recur_type'].', '
						. 'recur_enddate='.(int)$end.', '
						. 'recur_data='.$event['recur_data'].', '
						. 'recur_interval='.$event['recur_interval'].', '
						. "recur_exception='".(count($event['recur_exception'])>1?implode(',',$event['recur_exception']):(count($event['recur_exception'])==1?$event['recur_exception'][0]:''))."' "
						. 'WHERE cal_id='.$event['id'],__LINE__,__FILE__);
				}

			}
			else
			{
				$this->stream->query('DELETE FROM phpgw_cal_repeats WHERE cal_id='.(int)$event['id'],__LINE__,__FILE__);
			}
			// Custom fields
			$this->stream->query('DELETE FROM phpgw_cal_extra WHERE cal_id='.(int)$event['id'],__LINE__,__FILE__);

			foreach($event as $name => $value)
			{
				if ($name[0] == '#' && strlen($value))
				{
					$this->stream->query('INSERT INTO phpgw_cal_extra (cal_id,cal_extra_name,cal_extra_value) '
					. 'VALUES('.(int)$event['id'].",'".addslashes(substr($name,1))."','".addslashes($value)."')",__LINE__,__FILE__);
				}
			}
	/*
			$alarmcount = count($event['alarm']);
			if ($alarmcount > 1)
			{
				// this should never happen, $event['alarm'] should only be set
				// if creating a new event and uicalendar only sets up 1 alarm
				// the user must use "Alarm Management" to create/establish multiple
				// alarms or to edit/change an alarm
				echo '<!-- how did this happen, too many alarms -->'."\n";
				$this->stream->unlock();
				return True;
			}

			if ($alarmcount == 1)
			{

				list($key,$alarm) = @each($event['alarm']);

				$this->stream->query('INSERT INTO phpgw_cal_alarm(cal_id,cal_owner,cal_time,cal_text,alarm_enabled) VALUES('.$event['id'].','.$event['owner'].','.$alarm['time'].",'".$alarm['text']."',".$alarm['enabled'].')',__LINE__,__FILE__);
				$this->stream->query('SELECT LAST_INSERT_ID()');
				$this->stream->next_record();
				$alarm['id'] = $this->stream->f(0);
			}
	*/
			print_debug('Event Saved: ID #',$event['id']);

			$this->stream->unlock();

			if (is_array($event['alarm']))
			{
				foreach ($event['alarm'] as $alarm)	// this are all new alarms
				{
					$this->save_alarm($event['id'],$alarm);
				}
			}
			$GLOBALS['phpgw_info']['cal_new_event_id'] = $event['id'];
			$this->event = $event;
			return True;
		}

		function get_alarm($cal_id)
		{
	/* OLD-ALARM
			$this->stream->query('SELECT cal_time, cal_text FROM phpgw_cal_alarm WHERE cal_id='.$id.' AND cal_owner='.$this->user,__LINE__,__FILE__);
			if($this->stream->num_rows())
			{
				while($this->stream->next_record())
				{
					$alarm[$this->stream->f('cal_time')] = $this->stream->f('cal_text');
				}
				@reset($alarm);
				return $alarm;
			}
			else
			{
				return False;
			}
	*/
			$alarms = $this->read_alarms($cal_id);
			$ret = False;

			foreach($alarms as $alarm)
			{
				if ($alarm['owner'] == $this->user || !$alarm['owner'])
				{
					$ret[$alarm['time']] = $alarm['text'];
				}
			}
			return $ret;
		}

		function set_status($id,$owner,$status)
		{
			$status_code_short = Array(
				REJECTED =>	'R',
				NO_RESPONSE	=> 'U',
				TENTATIVE	=>	'T',
				ACCEPTED	=>	'A'
			);

			$this->stream->query("UPDATE phpgw_cal_user SET cal_status='".$status_code_short[$status]."' WHERE cal_id=".$id." AND cal_login=".$owner,__LINE__,__FILE__);
	/* OLD-ALARM
			if ($status == 'R')
			{
				$this->stream->query('UPDATE phpgw_cal_alarm set alarm_enabled=0 where cal_id='.$id.' and cal_owner='.$owner,__LINE__,__FILE__);
			}
	*/
			return True;
		}

	// End of ICal style support.......

		function group_search($owner=0)
		{
			$owner = ($owner==$GLOBALS['phpgw_info']['user']['account_id']?0:$owner);
			$groups = substr($GLOBALS['phpgw']->common->sql_search('phpgw_cal.groups',(int)$owner),4);
			if (!$groups)
			{
				return '';
			}
			else
			{
				return "(phpgw_cal.is_public=2 AND (". $groups .')) ';
			}
		}

		function splittime_($time)
		{
			$temp = array('hour','minute','second','ampm');
			$time = strrev($time);
			$second = (int)strrev(substr($time,0,2));
			$minute = (int)strrev(substr($time,2,2));
			$hour   = (int)strrev(substr($time,4));
			$temp['second'] = (int)$second;
			$temp['minute'] = (int)$minute;
			$temp['hour']   = (int)$hour;
			$temp['ampm']   = '  ';

			return $temp;
		}

		function date_to_epoch($d)
		{
			return $this->localdates(mktime(0,0,0,(int)(substr($d,4,2)),(int)(substr($d,6,2)),(int)(substr($d,0,4))));
		}

	/* OLD-ALARM
		function add_alarm($eventid,$alarm,$owner)
		{
			$this->stream->query('INSERT INTO phpgw_cal_alarm(cal_id,cal_owner,cal_time,cal_text,alarm_enabled) VALUES('.$eventid.','.$owner.','.$alarm['time'].",'".$alarm['text']."',1)",__LINE__,__FILE__);
			$this->stream->query('SELECT LAST_INSERT_ID()');
			$this->stream->next_record();
			return($this->stream->f(0));
		}
		function delete_alarm($alarmid)
		{
			$this->stream->query('DELETE FROM phpgw_cal_alarm WHERE alarm_id='.$alarmid,__LINE__,__FILE__);
		}
	*/
	}
