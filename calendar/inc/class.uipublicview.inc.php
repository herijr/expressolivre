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


	class uipublicview
	{
		var $template;
		var $template_dir;

		var $bo;
		var $cat;

		var $holidays;
		var $holiday_color;
		
		// Variable defined to view public calendars;
		var $publicView = False;
		var $user = False;

		var $debug = False;
//		var $debug = True;

		var $cat_id;
		var $theme;
		var $link_tpl;

		// planner related variables
		var $planner_header;
		var $planner_rows;

		var $planner_group_members;

		var $planner_firstday;
		var $planner_lastday;
		var $planner_days;

		var $planner_end_month;
		var $planner_end_year;
		var $planner_days_in_end_month;

		var $planner_intervals = array(	// conversation hour and interval depending on intervals_per_day
					//                                  1 1 1 1 1 1 1 1 1 1 2 2 2 2
					//              0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3
						'1' => array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0), // 0=0-23h
						'2' => array(0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,1,0,0,0,0,0), // 0=0-12h, 1=12-23h
						'3' => array(0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,2,2,2,2,2,2), // 0=0-12h, 2=12-18h, 3=18-23h
						'4' => array(0,0,0,0,0,0,0,1,1,1,1,1,2,2,2,2,2,2,3,3,3,3,3,3)  // 0=0-7, 7-12h, 3=12-18h, 4=18-23h
					);

		var $public_functions = array(		
			'index' => True,
			'publicView'  => True,
			'get_publicView' => True,
			'view' => True,
			'header' => True,
			'footer' => True,
			
		);

		function uipublicview()
		{ 
			$GLOBALS['phpgw']->nextmatchs = CreateObject('phpgwapi.nextmatchs');

			$this->theme = $GLOBALS['phpgw_info']['theme'];

			$this->bo = CreateObject('calendar.bocalendar',1);
			$this->cat = &$this->bo->cat;
			print_debug('BO Owner',$this->bo->owner);

			$this->template = $GLOBALS['phpgw']->template;
			$this->template_dir = $GLOBALS['phpgw']->common->get_tpl_dir('calendar');
			$this->holiday_color = (substr($this->theme['bg06'],0,1)=='#'?'':'#').$this->theme['bg06'];

			$this->cat_id   = $this->bo->cat_id;

			$this->link_tpl = CreateObject('phpgwapi.Template',$this->template_dir);
			$this->link_tpl->set_unknowns('remove');
			$this->link_tpl->set_file(
				Array(
					'link_picture'	=> 'link_pict.tpl'
				)
			);
			
			$this->link_tpl->set_block('link_picture','link_pict','link_pict');
			$this->link_tpl->set_block('link_picture','pict','pict');
			$this->link_tpl->set_block('link_picture','link_open','link_open');
			$this->link_tpl->set_block('link_picture','link_close','link_close');
			$this->link_tpl->set_block('link_picture','link_text','link_text');

			if($this->bo->use_session)
			{
				// save return-fkt for add, view, ...
				list(,,$fkt) = explode('.',$_GET['menuaction']);
				if ($fkt == 'day' || $fkt == 'week' || $fkt == 'month' || $fkt == 'year' || $fkt == 'planner')
				{
					$this->bo->return_to = $_GET['menuaction'].
						sprintf('&date=%04d%02d%02d',$this->bo->year,$this->bo->month,$this->bo->day);
				}
				$this->bo->save_sessiondata();
			}
			$this->always_app_header = True;
			
			
			print_debug('UI',$this->_debug_sqsof());

			if (!is_object($GLOBALS['phpgw']->html))
			{
				$GLOBALS['phpgw']->html = CreateObject('phpgwapi.html');
			}
			$this->html = &$GLOBALS['phpgw']->html;
		}

		/* Public functions */

		function index($params='')
		{
			if (!$params)
			{
				foreach(array('date','year','month','day') as $field)
				{
					if (isset($_GET[$field]))
					{
						$params[$field] = $_GET[$field];
					}
				}
			}
			$GLOBALS['phpgw']->redirect($this->page('',$params));
		}

		/* Private functions */
		function _debug_sqsof()
		{
			$data = array(
				'filter'     => $this->bo->filter,
				'cat_id'     => $this->bo->cat_id,
				'owner'      => $this->bo->owner,
				'year'       => $this->bo->year,
				'month'      => $this->bo->month,
				'day'        => $this->bo->day,
				'sortby'     => $this->bo->sortby,
				'num_months' => $this->bo->num_months
			);
			Return _debug_array($data,False);
		}

		function output_template_array(&$p,$row,$list,$var)
		{
			if (!isset($var['hidden_vars']))
			{
				$var['hidden_vars'] = '';
			}
			if (!isset($var['tr_color']))
			{
				$var['tr_color'] = $GLOBALS['phpgw']->nextmatchs->alternate_row_color();
			}
			$p->set_var($var);
			$p->parse($row,$list,True);
		}

		function page($_page='',$params='')
		{
			if($_page == '')
			{
				$page_ = explode('.',$this->bo->prefs['calendar']['defaultcalendar']);
				$_page = $page_[0];

				if ($_page=='planner_cat' || $_page=='planner_user')
				{
					$_page = 'planner';
				}
				elseif ($_page=='index' || ($_page != 'day' && $_page != 'week' && $_page != 'month' && $_page != 'year' && $_page != 'planner'))
				{
					$_page = 'month';
					$GLOBALS['phpgw']->preferences->add('calendar','defaultcalendar','month');
					$GLOBALS['phpgw']->preferences->save_repository();
				}
			}
			
			if($GLOBALS['phpgw_info']['flags']['currentapp'] == 'home' ||
				strstr($GLOBALS['phpgw_info']['flags']['currentapp'],'mail'))	// email, felamimail, ...
			{	
				$page_app = 'calendar';
			}
			else
			{	
				$page_app = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			$page_app = 'calendar.uipublicview.publicView';
			
			if (is_array($params))
			{
				$params['menuaction'] = $page_app;
			}
			else
			{
				$params = 'menuaction='.$page_app.$params;
			}
			return $GLOBALS['phpgw']->link('/index.php',$params);
		}

		function header()
		{
			$cols = 8;
			if($this->bo->check_perms(PHPGW_ACL_PRIVATE) == True)
			{
				$cols++;
			}

			$tpl = $GLOBALS['phpgw']->template;
			$tpl->set_unknowns('remove');

			if (!file_exists($file = $this->template_dir.'/header.inc.php'))
			{
				$file = PHPGW_SERVER_ROOT . '/calendar/templates/default/header.inc.php';
			}
			include($file);
			$header = $tpl->fp('out','head');
			unset($tpl);
			echo $header;
		}

		function footer(){
		
		}
		function selectFilter()
		{
			
			$menuaction = $_GET['menuaction'];

			list(,,$method) = explode('.',$menuaction);

			if (@$this->bo->printer_friendly)
			{
				return;
			}

			$p = $GLOBALS['phpgw']->template;
			
			$p->set_file(
				Array(
					'footer'	=> 'footer.tpl',
					'form_button'	=> 'form_button_script.tpl'

				)
			);
			$p->set_block('footer','footer_table','footer_table');
			$p->set_block('footer','footer_row','footer_row');
			$p->set_block('footer','blank_row','blank_row');

			$m = $this->bo->month;
			$y = $this->bo->year;

			$thisdate = date('Ymd',mktime(0,0,0,$m,1,$y));
			$y--;

			$str = '';
			for ($i = 0; $i < 25; $i++)
			{
				$m++;
				if ($m > 12)
				{
					$m = 1;
					$y++;
				}
				$d = mktime(0,0,0,$m,1,$y);
				$d_ymd = date('Ymd',$d);
				$str .= '<option value="'.$d_ymd.'"'.($d_ymd == $thisdate?' selected':'').'>'.lang(date('F', $d)).strftime(' %Y', $d).'</option>'."\n";
			}
			
			$var = Array(
				'action_url'	=> $this->page($method,''),
				'form_name'	=> 'SelectMonth',
				'label'		=> lang('Month'),
				'user'	=> $this->user,
				'form_label'	=> 'date',
				'form_onchange'	=> 'document.SelectMonth.submit()',
				'row'		=> $str,
				'go'		=> lang('Go!')
			);
			
			$this->output_template_array($p,'table_row','footer_row',$var);

		   if($menuaction == 'calendar.uipublicview.week' || $menuaction == 'calendar.uipublicview.publicView')
		   {
				unset($thisdate);
				$thisdate = mktime(0,0,0,$this->bo->month,$this->bo->day,$this->bo->year) - $GLOBALS['phpgw']->datetime->tz_offset;
				$sun = $GLOBALS['phpgw']->datetime->get_weekday_start($this->bo->year,$this->bo->month,$this->bo->day) - $GLOBALS['phpgw']->datetime->tz_offset;

				$str = '';
				for ($i = -7; $i <= 7; $i++)
				{
					$begin = $sun + (7*24*60*60 * $i) + 12*60*60;	// we use midday, that changes in daylight-saveing does not effect us
					$end = $begin + 6*24*60*60;
                    $str .= '<option value="' . $GLOBALS['phpgw']->common->show_date($begin,'Ymd') . '"'.($begin <= $thisdate+12*60*60 && $end >= $thisdate+12*60*60 ? ' selected':'').'>'                    
						. $GLOBALS['phpgw']->common->show_date($begin,$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']) . ' - '
                        . $GLOBALS['phpgw']->common->show_date($end,$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'])
                        . '</option>' . "\n";                        
				}

				$var = Array(
					'action_url'	=> $this->page($method,''),
					'form_name'	=> 'SelectWeek',
					'label'		=> lang('Week'),
					'form_label'	=> 'date',
					'user'	=> $this->user,
					'form_onchange'	=> 'document.SelectWeek.submit()',
					'row'		=> $str,
					'go'		=> lang('Go!')
				);

				$this->output_template_array($p,'table_row','footer_row',$var);
			}

			$str = '';
			for ($i = ($this->bo->year - 3); $i < ($this->bo->year + 3); $i++)
			{
				$str .= '<option value="'.$i.'"'.($i == $this->bo->year?' selected':'').'>'.$i.'</option>'."\n";
			}

			$var = Array(
				'action_url'	=> $this->page($method,''),
				'form_name'	=> 'SelectYear',
				'label'		=> lang('Year'),
				'user'	=> $this->user,
				'form_label'	=> 'year',
				'form_onchange'	=> 'document.SelectYear.submit()',
				'row'		=> $str,
				'go'		=> lang('Go!')
			);
			$this->output_template_array($p,'table_row','footer_row',$var);

			if($menuaction == 'calendar.uipublicview.planner')
			{
				$str = '';
				$date_str = '';

				if(isset($_GET['date']) && $_GET['date'])
				{
					$date_str .= '    <input type="hidden" name="date" value="'.$_GET['date'].'">'."\n";
				}
				$date_str .= '    <input type="hidden" name="month" value="'.$this->bo->month.'">'."\n";
				$date_str .= '    <input type="hidden" name="day" value="'.$this->bo->day.'">'."\n";
				$date_str .= '    <input type="hidden" name="year" value="'.$this->bo->year.'">'."\n";

				for($i=1; $i<=6; $i++)
				{
					$str .= '<option value="'.$i.'"'.($i == $this->bo->num_months?' selected':'').'>'.$i.'</option>'."\n";
				}
				
				$var = Array(
					'action_url'	=> $this->page($method,''),
					'form_name'	=> 'SelectNumberOfMonths',
					'label'		=> lang('Number of Months'),
					'hidden_vars' => $date_str,
					'form_label'	=> 'num_months',
					'form_onchange'	=> 'document.SelectNumberOfMonths.submit()',
					'action_extra_field'	=> $date_str,
					'user'	=> $this->user,
					'row'		=> $str,
					'go'		=> lang('Go!')
				);
				

				$this->output_template_array($p,'table_row','footer_row',$var);
			}
				
			$p->pparse('out','footer_table');
			unset($p);
		}


		function link_to_entry($event,$month,$day,$year)
		{
			$str = '';
			$is_private = !$event['public'] && !$this->bo->check_perms(PHPGW_ACL_READ,$event);
			$viewable = !$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_READ,$event);

			$starttime = $this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$endtime = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$rawdate = mktime(0,0,0,$month,$day,$year);
			$rawdate_offset = $rawdate - $GLOBALS['phpgw']->datetime->tz_offset;
			$nextday = mktime(0,0,0,$month,$day + 1,$year) - $GLOBALS['phpgw']->datetime->tz_offset;
			if ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') && $starttime == $endtime)
			{
				$time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
			}
			elseif ($starttime <= $rawdate_offset && $endtime >= $nextday - 60)
			{
				$time = '[ '.lang('All Day').' ]';
			}
			elseif ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') || $starttime != $endtime)
			{
				if($starttime < $rawdate_offset && $event['recur_type'] == MCAL_RECUR_NONE)
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($rawdate_offset,$this->bo->users_timeformat);
				}
				else
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
				}

				if($endtime >= ($rawdate_offset + 86400))
				{
					$end_time = $GLOBALS['phpgw']->common->show_date(mktime(23,59,59,$month,$day,$year) - $GLOBALS['phpgw']->datetime->tz_offset,$this->bo->users_timeformat);
				}
				else
				{
					$end_time = $GLOBALS['phpgw']->common->show_date($endtime,$this->bo->users_timeformat);
				}
				$time = $start_time.'-'.$end_time;
			}
			else
			{
				$time = '';
			}
			
			$texttitle = $texttime = $textdesc = $textlocation = $textstatus = '';

			
			
			if(!$is_private)
			{
				// split text for better display by templates, also see $texttime $texttitle $textdesc $textlocation	
				$textstatus=$this->bo->display_status($event['users_status']); 
				
			}

			
			$texttime=$time;
			$texttitle=$this->bo->get_short_field($event,$is_private,'title');
			$textdesc=(!$is_private && $event['description'] ? $this->bo->get_short_field($event,$is_private,'description'):'');
			// added $textlocation but this must be activated in the actual pict_link.tpl file of the used template set
			$textlocation=$this->bo->get_short_field($event,$is_private,'location');

			if (!$is_private)
			{
				if($event['priority'] == 3)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','high'),
						'width'	=> 16,
						'height'=> 16,
						'title' => lang('high priority')
					);
				}
				if($event['recur_type'] == MCAL_RECUR_NONE)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','circle'),
						'width'	=> 9,
						'height'=> 9,
						'title' => lang('single event')
					);
				}
				else
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','recur'),
						'width'	=> 12,
						'height'=> 12,
						'title' => lang('recurring event')
					);
				}
			}
			$participants = $this->planner_participants($event['participants']);
			if(count($event['participants']) > 1)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','multi_3'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			else
			{
				$picture[] = Array(
					'pict'	=>  $GLOBALS['phpgw']->common->image('calendar','single'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			if($event['public'] == 0)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','private'),
					'width'	=> 13,
					'height'=> 13,
					'title' => lang('private')
				);
			}
			if(@isset($event['alarm']) && count($event['alarm']) >= 1 && !$is_private)
			{
				// if the alarm is to go off the day before the event
				// the icon does not show up because of 'alarm_today'
				// - TOM
				if($this->bo->alarm_today($event,$rawdate_offset,$starttime))
				{
					$picture[] = Array(
						'pict'  => $GLOBALS['phpgw']->common->image('calendar','alarm'),
						'width' => 13,
						'height'=> 13,
						'title' => lang('alarm')
					);
				}
			}

			$description = $this->bo->get_short_field($event,$is_private,'description');
			for($i=0;$i<count($picture);$i++)
			{
				$var = Array(
					'pic_image' => $picture[$i]['pict'],
					'width'     => $picture[$i]['width'],
					'height'    => $picture[$i]['height'],
					'title'     => $picture[$i]['title']
				);
				$this->output_template_array($this->link_tpl,'picture','pict',$var);
			}
			if ($texttitle)
			{
				$var = Array(
			//		'text' => $text,
					'time'=> $texttime,
					'title'=> $texttitle,
					'users_status'=>$textstatus,
					'desc'=> $textdesc,
					'location'=> $textlocation
				);
				$this->output_template_array($this->link_tpl,'picture','link_text',$var);
			}

			if ($viewable)
			{
				$this->link_tpl->parse('picture','link_close',True);
			}
			$str = $this->link_tpl->fp('out','link_pict');
			$this->link_tpl->set_var('picture','');
			$this->link_tpl->set_var('out','');
//			unset($p);
			return $str;
		}

		function planner_participants($parts)
		{
			static $id2lid;

			$names = '';
			while (list($id,$status) = each($parts))
			{
				$status = substr($this->bo->get_long_status($status),0,1);

				if (!isset($id2lid[$id]))
				{
					$id2lid[$id] = $GLOBALS['phpgw']->common->grab_owner_name($id);
				}
				if (strlen($names))
				{
					$names .= ",\n";
				}
				$names .= $id2lid[$id]." ($status)";
			}
			if($this->debug)
			{
				echo '<!-- Inside participants() : '.$names.' -->'."\n";
			}
			return $names;
		}


		function week_header($month,$year,$display_name = False)
		{
			$this->weekstarttime = $GLOBALS['phpgw']->datetime->get_weekday_start($year,$month,1);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('remove');
			$p->set_file(
				Array (
					'month_header' => 'month_header.tpl'
				)
			);
			$p->set_block('month_header','monthly_header','monthly_header');
			$p->set_block('month_header','column_title','column_title');

			$var = Array(
				'bgcolor'	=> $this->theme['th_bg'],
				'font_color'	=> $this->theme['th_text']
			);
			if($this->bo->printer_friendly && @$this->bo->prefs['calendar']['print_black_white'])
			{
				$var = Array(
					'bgcolor'	=> '',
					'font_color'	=> ''
				);
			}
			$p->set_var($var);

			$p->set_var('col_width','14');
			if($display_name == True)
			{
				$p->set_var('col_title',lang('name'));
				$p->parse('column_header','column_title',True);
				$p->set_var('col_width','12');
			}

			for($i=0;$i<7;$i++)
			{
				$p->set_var('col_title',lang($GLOBALS['phpgw']->datetime->days[$i]));
				$p->parse('column_header','column_title',True);
			}
			return $p->fp('out','monthly_header');
		}

		function display_week($startdate,$weekly,$cellcolor,$display_name = False,$owner=0,$monthstart=0,$monthend=0)
		{	
			if($owner == 0)
			{
				$owner = $GLOBALS['phpgw_info']['user']['account_id'];
			}

			$temp_owner = $this->bo->owner;
			$publicView = $this -> publicView;

			$str = '';
			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'month_header'	=> 'month_header.tpl',
					'month_day'	=> 'month_day.tpl'
				)
			);
			$p->set_block('month_header','monthly_header','monthly_header');
			$p->set_block('month_header','month_column','month_column');
			$p->set_block('month_day','month_daily','month_daily');
			$p->set_block('month_day','day_event','day_event');
			$p->set_block('month_day','event','event');

			$p->set_var('extra','');
			$p->set_var('col_width','14');
			if($display_name)
			{
				$p->set_var('column_data',$GLOBALS['phpgw']->common->grab_owner_name($owner));
				$p->parse('column_header','month_column',True);
				$p->set_var('col_width','12');
			}
			$today = date('Ymd',$GLOBALS['phpgw']->datetime->users_localtime);
			$daily = $this->set_week_array($startdate - $GLOBALS['phpgw']->datetime->tz_offset,$cellcolor,$weekly);
			foreach($daily as $date => $day_params)
			{
				$year  = (int)substr($date,0,4);
				$month = (int)substr($date,4,2);
				$day   = (int)substr($date,6,2);
				$var   = Array(
					'column_data' => '',
					'extra' => ''
				);
				$p->set_var($var);
				if ($weekly || ($date >= $monthstart && $date <= $monthend))
				{
					if ($day_params['new_event'] && !$publicView)
					{
						$new_event_link = ' <a href="'.$this->page('add','&date='.$date).'">'
							. '<img src="'.$GLOBALS['phpgw']->common->image('calendar','new3').'" width="10" height="10" title="'.lang('New Entry').'" border="0" align="center">'
							. '</a>';
						$day_number = '<a href="'.$this->page('day','&date='.$date).'">'.$day.'</a>';
					}
					else
					{
						$new_event_link = '';
						$day_number = $day;
					}

					$var = Array(
						'extra'		=> $day_params['extra'],
						'new_event_link'=> $new_event_link,
						'day_number'	=> $day_number
					);
							$p->set_var($var);

					if(@$day_params['holidays'])
					{
						foreach($day_params['holidays'] as $key => $value)
						{
							$var = Array(
								'day_events' => '<font face="'.$this->theme['font'].'" size="-1">'.$value.'</font><br>'
							);
							$this->output_template_array($p,'daily_events','event',$var);
						}
					}

					if($day_params['appts'])
					{
						$var = Array(
							'week_day_font_size'	=> '2',
							'events'		=> ''
						);
						$p->set_var($var);
						$events = $this->bo->cached_events[$date];
						foreach($events as $event)
						{
							if ($this->bo->rejected_no_show($event))
							{
								continue;	// user does not want to see rejected events
							}
							$p->set_var('day_events',$this->link_to_entry($event,$month,$day,$year));
							$p->parse('events','event',True);
							$p->set_var('day_events','');
						}
					}
					$p->parse('daily_events','day_event',True);
					$p->parse('column_data','month_daily',True);
					$p->set_var('daily_events','');
					$p->set_var('events','');
				}
				$p->parse('column_header','month_column',True);
				$p->set_var('column_data','');
			}
			$this->bo->owner = $temp_owner;
			return $p->fp('out','monthly_header');
		}

		function display_month($month,$year,$showyear,$owner=0)
		{
			if($this->debug)
			{
				echo '<!-- datetime:gmtdate = '.$GLOBALS['phpgw']->datetime->cv_gmtdate.' -->'."\n";
			}

			$this->bo->store_to_cache(
				Array(
					'syear'	=> $year,
					'smonth'=> $month,
					'sday'	=> 1
				)
			);

			$monthstart = (int)(date('Ymd',mktime(0,0,0,$month    ,1,$year)));
			$monthend   = (int)(date('Ymd',mktime(0,0,0,$month + 1,0,$year)));

			$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, 1);

			if($this->debug)
			{
				echo '<!-- display_month:monthstart = '.$monthstart.' -->'."\n";
				echo '<!-- display_month:start = '.date('Ymd H:i:s',$start).' -->'."\n";
			}

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'week' => 'month_day.tpl'
				)
			);
			$p->set_block('week','m_w_table','m_w_table');
			$p->set_block('week','event','event');

			$var = Array(
				'cols'      => 7,
				'day_events'=> $this->week_header($month,$year,False)
			);
			$this->output_template_array($p,'row','event',$var);

			$cellcolor = $this->theme['row_on'];

			for($i = (int)($start + $GLOBALS['phpgw']->datetime->tz_offset);(int)(date('Ymd',$i)) <= $monthend;$i += 604800)
			{
				$cellcolor = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($cellcolor);
				$var = Array(
					'day_events' => $this->display_week($i,False,$cellcolor,False,$owner,$monthstart,$monthend)
				);
				$this->output_template_array($p,'row','event',$var);
			}
			return $p->fp('out','m_w_table');
		}

		function display_weekly($params, $accountId)
		{
			if(!is_array($params))
			{
				$this->index();
			}

			$year = substr($params['date'],0,4);
			$month = substr($params['date'],4,2);
			$day = substr($params['date'],6,2);
			$showyear = $params['showyear'];
			$owners = $params['owners'];
			
			
			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'week'	=> 'month_day.tpl'
				)
			);
			$p->set_block('week','m_w_table','m_w_table');
			$p->set_block('week','event','event');

			$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, $day) + $GLOBALS['phpgw']->datetime->tz_offset;

			$cellcolor = $this->theme['row_off'];

			$true_printer_friendly = $this->bo->printer_friendly;

			if(is_array($owners))
			{
				$display_name = True;
				$counter = count($owners);
				$owners_array = $owners;
				$cols = 8;
			}
			else
			{
				$display_name = False;
				$counter = 1;
				$owners_array[0] = $owners;
				$cols = 7;
			}
			$var = Array(
				'cols'         => $cols,
				'day_events'   => $this->week_header($month,$year,$display_name)
			);
			$this->output_template_array($p,'row','event',$var);

			$tstart = $start - $GLOBALS['phpgw']->datetime->tz_offset;
			$tstop = $tstart + 604800;
			$original_owner = $this->bo->so->owner;

			$this->bo->so->owner = $accountId;
			$this->bo->so->open_box($accountId);
			$this->bo->store_to_cache(
					Array(
						'syear'  => date('Y',$tstart),
						'owner'	=> $original_owner,
						'smonth' => date('m',$tstart),
						'sday'   => date('d',$tstart),
						'eyear'  => date('Y',$tstop),
						'emonth' => date('m',$tstop),
						'eday'   => date('d',$tstop)
					)
				);
				
			$p->set_var('day_events',$this->display_week($start,True,$cellcolor,$display_name,$accountId, True));
				$p->parse('row','event',True);
			
			$this->bo->so->owner = $original_owner;
			$this->bo->printer_friendly = $true_printer_friendly;
			return $p->fp('out','m_w_table');
		}
		
		function set_week_array($startdate,$cellcolor,$weekly)
		{
			for ($j=0,$datetime=$startdate;$j<7;$j++,$datetime += 86400)
			{
				$date = date('Ymd',$datetime + (60 * 60 * 2)); // +2h to be save when switching to and from dst, $datetime is alreay + TZ-Offset
				print_debug('set_week_array : Date ',$date);

				if($events = $this->bo->cached_events[$date])
				{
					print_debug('Date',$date);
					print_debug('Appointments Found',count($events));

					if (!$this->bo->prefs['calendar']['show_rejected'])
					{
						$appts = False;
						foreach($events as $event)	// check for a not-rejected event
						{
							if (!$this->bo->rejected_no_show($event))
							{
								$appts = True;
								break;
							}
						}
					}
					else
					{
						$appts = True;
					}
				}
				else
				{
					$appts = False;
				}

				$holidays = $this->bo->cached_holidays[$date];
				if($weekly)
				{
					$cellcolor = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($cellcolor);
				}

				$day_image = '';
				if($holidays)
				{
					$extra = ' bgcolor="'.$this->bo->holiday_color.'"';
					$class = ($appts?'b':'').'minicalhol';
					if ($date == $this->bo->today)
					{
						$day_image = ' background="'.$GLOBALS['phpgw']->common->image('calendar','mini_day_block').'"';
					}
				}
				elseif ($date != $this->bo->today)
				{
					$extra = ' bgcolor="'.$cellcolor.'"';
					$class = ($appts?'b':'').'minicalendar';
				}
				else
				{
					$extra = ' bgcolor="'.$GLOBALS['phpgw_info']['theme']['cal_today'].'"';
					$class = ($appts?'b':'').'minicalendar';
					$day_image = ' background="'.$GLOBALS['phpgw']->common->image('calendar','mini_day_block').'"';
				}

				if($this->bo->printer_friendly && @$this->bo->prefs['calendar']['print_black_white'])
				{
					$extra = '';
				}

				if(!$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_ADD))
				{
					$new_event = True;
				}
				else
				{
					$new_event = False;
				}
				$holiday_name = Array();
				if($holidays)
				{
					for($k=0;$k<count($holidays);$k++)
					{
						$holiday_name[] = $holidays[$k]['name'];
					}
				}
				$week = '';
				if (!$j || (!$weekly && $j && substr($date,6,2) == '01'))
				{
					$week = lang('week').' '.(int)((date('z',($startdate+(24*3600*4)))+7)/7);
				}
				$daily[$date] = Array(
					'extra'		=> $extra,
					'new_event'	=> $new_event,
					'holidays'	=> $holiday_name,
					'appts'		=> $appts,
					'week'		=> $week,
					'day_image'	=> $day_image,
					'class'		=> $class
				);
			}

			if($this->debug)
			{
				_debug_array($daily);
			}

			return $daily;
		}
		
		function publicView()
		{	
			 						
			$account_name = $_POST['user'] ? 
							$_POST['user'] : 
							($_GET['account_name'] ?
							 $_GET['account_name'] : '');

			$theme_css = $GLOBALS['phpgw_info']['server']['webserver_url'] . '/phpgwapi/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'].'/css/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['theme'].'.css';
		
			echo '<html>'."\n"
				.'<head>'."\n"
				.'<LINK href="'.$theme_css.'" type=text/css rel=StyleSheet>'."\n"
				.'</head><body>'
				.'<div id="divAppboxHeader">'
				.lang('weekly agenda of events')
				.'</div><div id="divAppbox"  align="center" >';
							 
			if(!$account_name){			
				echo '<font color="GREEN" size="+1">'.lang('it types login of the user to have access public agenda').'</font></center></div>';
				return True;
			}
							 
			$accounts = CreateObject('phpgwapi.accounts');
			$accountId = $accounts->name2id($account_name);

			if(!$accountId){			
				echo '<font color="RED" size="+1">'.lang('this user does not exist').'</font></center></div>';
				return True;
			}

			$prefs = CreateObject('phpgwapi.preferences', $accountId);
			$account_prefs = $prefs->read();
			
			$publicView = $account_prefs['calendar']['public_view'];
			
			if($publicView){
				$this -> user = $account_name;
				$this-> selectFilter();				
				echo $this->printer_publicView($this->get_publicView($accountId));
				
			}
			else {
				echo '<font color="RED" size="+1">'.lang('it types login of the user to have access public agenda').'</font></center></div>';
			}

			return True;	
		}

		function get_publicView($accountId)
		{
			// allow users to view public calendars;
			$this -> publicView = True;
			$this->bo->read_holidays();

			$next = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day + 7,$this->bo->year);
			$prev = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day - 7,$this->bo->year);

			$var = Array(
				'week_display'		=>	$this->display_weekly(
					Array(
						'date'		=> sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day),
						'showyear'	=> true, False
					),$accountId
				),
				'print'			=>	$print
			);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_file(
				Array(
					'week_t' => 'publicView.tpl'
				)
			);
			$p->set_var($var);
			return $p->fp('out','week_t');

		}
		
		function printer_publicView($body)
		{				
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			unset($GLOBALS['phpgw_info']['flags']['noappheader']);
			unset($GLOBALS['phpgw_info']['flags']['noappfooter']);
			
			$new_body .= $this->bo->debug_string.$body;
			return $new_body;
		}
		
		
	}
?>
