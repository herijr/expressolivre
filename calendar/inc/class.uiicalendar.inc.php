<?php
  /**************************************************************************\
  * eGroupWare - Calendar                                                    *
  * http://www.egroupware.org                                                *
  * Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
  *          http://www.radix.net/~cknudsen                                  *
  * Modified by Mark Peters <skeeter@phpgroupware.org>                       *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	class uiicalendar
	{
		var $bo;
		var $template;

		var $public_functions = array(
			'test'		=> True,
			'import'		=> True
		);

		function uiicalendar()
		{
			$this->bo = CreateObject('calendar.boicalendar');
			$this->template = $GLOBALS['phpgw']->template;
			$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Calendar - [iv]Cal Importer');
		}

		function print_test($val,$title,$x_pre='')
		{
//			echo 'VAL = '._debug_array($val,False)."<br>\n";
			if(is_array($val))
			{
				@reset($val);
				while(list($key,$value) = each($val))
				{
					if(is_array($key))
					{
						$this->print_test($key,$title,$x_pre);
					}
					elseif(is_array($value))
					{
						$this->print_test($value,$title,$x_pre);
					}
					else
					{
						if($x_pre && $key == 'name')
						{
							$x_key = $x_pre.$value;
							list($key,$value) = each($val);
							$key=$x_key;
						}
						if($this->bo->parameter[$key]['type'] == 'function')
						{
							$function = $this->bo->parameter[$key]['function'];
							$v_value = $this->bo->$function($value);
						}
						else
						{
							$v_value = $value;
						}
						echo $title.' ('.$key.') = '.$v_value."<br>\n";
					}
				}
			}
			elseif($val != '')
			{
				echo $title.' = '.$val."<br>\n";
			}
		}
		
		function get_error_message($error_number) {
			switch ($error_number) {
				case 1:
					return lang('event already exists');
			}
		}

		function import()
		{
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['nonappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['nonappfooter'] = True;
			$GLOBALS['phpgw']->common->phpgw_header();

			if(!@is_dir($GLOBALS['phpgw_info']['server']['temp_dir']))
			{
				mkdir($GLOBALS['phpgw_info']['server']['temp_dir'],0700);
			}

			echo '<body bgcolor="' . $GLOBALS['phpgw_info']['theme']['bg_color'] . '">';

			if ($GLOBALS['HTTP_GET_VARS']['action'] == 'GetFile')
			{
				echo '<b><center>' . lang('You must select a [iv]Cal. (*.[iv]cs)') . '</b></center><br><br>';
			}

 			$this->template->set_file(array('vcalimport' => 'vcal_import.tpl'));
			$this->template->set_block('vcalimport','page_block');
			$this->template->set_block('vcalimport','error_block');
			
			if($GLOBALS['HTTP_GET_VARS']['error_number']) {
				$this->template->set_var('error_message',$this->get_error_message( $GLOBALS['HTTP_GET_VARS']['error_number'] ) );
				$this->template->parse('error_box','error_block',true);
			}
			
			$var = Array(
				'vcal_header'	=> '<p>',
				'ical_lang'		=> lang('(i/v)Cal'),
				'action_url'	=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.boicalendar.import'),
				'lang_access'	=> lang('Access'),
				'lang_groups'	=> lang('Which groups'),
				'access_option'=> $access_option,
				'group_option'	=> $group_option,
				'load_vcal'	=> lang('Load [iv]Cal')
			);
			$this->template->set_var($var);
			$this->template->pfp('out', 'page_block');
			// $this->template->pparse('out','page_block');
		}
	}
?>
