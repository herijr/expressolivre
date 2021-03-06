<?php
	/**************************************************************************\
	* eGroupWare - Calendar Preferences                                        *
	* http://www.egroupware.org                                                *
	* Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
	*          http://www.radix.net/~cknudsen                                  *
	* Modified by Mark Peters <skeeter@phpgroupware.org>                       *
	* Modified by Ralf Becker <ralfbecker@outdoor-training.de>                 *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	ExecMethod('calendar.bocalendar.check_set_default_prefs');

	$default = array(
		'day'          => lang('Daily'),
		'week'         => lang('Weekly'),
		'month'        => lang('Monthly'),
		'year'         => lang('Yearly'),
		'planner_cat'  => lang('Planner by category'),
		'planner_user' => lang('Planner by user'),
	);
	create_select_box('default calendar view','defaultcalendar',$default,
		'Which of calendar view do you want to see, when you start calendar ?');

	/* Selection of list for home page is different from default calendar,
	   since the decision for the front page is different for the decision
	   for the main calendar page.  But the list could be added to the
	   default list above, if requested.  - shrykedude. */
	$mainpage = array(
		'1' => lang('Yes'),
		'0' => lang('No'),
		'2' => lang('No').' - '.lang('show list of upcoming events'),
	);
	create_select_box('show default view on main screen','mainscreen_showevents',$mainpage,
		'Displays your default calendar view on the startpage (page you get when you enter eGroupWare or click on the homepage icon)?');
	unset($mainpage);
/*
	$summary = array(
		'no'     => lang('Never'),
		'daily'  => lang('Daily'),
		'weekly' => lang('Weekly')
	);
	create_select_box('Receive summary of appointments','summary',$summary,
		'Do you want to receive a regulary summary of your appointsments via email?<br>The summary is sent to your standard email-address on the morning of that day or on Monday for weekly summarys.<br>It is only sent when you have any appointments on that day or week.');
*/
	$updates = array(
		'no'             => lang('Never'),
		'add_cancel'     => lang('on invitation / cancelation only'),
		'time_change_4h' => lang('on time change of more than 4 hours too'),
		'time_change'    => lang('on any time change too'),
		'modifications'  => lang('on all modification, but responses'),
		'responses'      => lang('on participant responses too')
	);
	create_select_box('Receive email updates','receive_updates',$updates,
		"Do you want to be notified about new or changed appointments? You be notified about changes you make yourself.<br>You can limit the notifications to certain changes only. Each item includes all the notification listed above it. All modifications include changes of title, description, participants, but no participant responses. If the owner of an event requested any notifcations, he will always get the participant responses like acceptions and rejections too.");

	$update_formats = array(
		'none'     => lang('None'),
		'extended' => lang('Extended'),
		'ical'     => lang('iCal / rfc2445')
	);
	create_select_box('Format of event updates','update_format',$update_formats,
		'Extended updates always include the complete event-details. iCal\'s can be imported by certain other calendar-applications.');

	$event_details = array(
		'to-fullname' => lang('Fullname of person to notify'),
		'to-firstname'=> lang('Firstname of person to notify'),
		'to-lastname' => lang('Lastname of person to notify'),
		'title'       => lang('Title of the event'),
		'description' => lang('Description'),
		'startdate'   => lang('Start Date/Time'),
		'enddate'     => lang('End Date/Time'),
		'olddate'     => lang('Old Startdate'),
		'category'    => lang('Category'),
		'location'    => lang('Location'),
		'priority'    => lang('Priority'),
		'participant'=> lang('Participants'),
		'owner'       => lang('Owner'),
		'repetition'  => lang('Repetitiondetails (or empty)'),
		'action'      => lang('Action that caused the notify: Added, Canceled, Accepted, Rejected, ...'),
		'link'        => lang('Link to view the event'),
	);
	create_notify('Notification messages for added events ','notifyAdded',5,50,
		'This message is sent to every participant of events you own, who has requested notifcations about new events.<br>You can use certain variables which get substituted with the data of the event. The first line is the subject of the email.',
		'',$event_details);
	create_notify('Notification messages for canceled events ','notifyCanceled',5,50,
		'This message is sent for canceled or deleted events.','',$event_details,False);
	create_notify('Notification messages for modified events ','notifyModified',5,50,
		'This message is sent for modified or moved events.','',$event_details,False);
	create_notify('Notification messages for your responses ','notifyResponse',5,50,
		'This message is sent when you accept, tentative accept or reject an event.',
		'',$event_details,False);
	create_notify('Notification messages for your alarms','notifyAlarm',5,50,
		'This message is sent when you set an Alarm for a certain event. Include all information you might need.',
		'',$event_details,False);

	create_check_box('Show invitations you rejected','show_rejected',
		'Should invitations you rejected still be shown in your calendar ?<br>You can only accept them later (eg. when your scheduling conflict is removed), if they are still shown in your calendar!');

	create_check_box('Display status of events','display_status',
		'Should the status of the event-participants (accept, reject, ...) be shown in brakets after each participants name ?');
        create_check_box('Hide title and description of event when conflicts','hide_event_conflict',
		                'When user invite other that time conflicts this option hide title and description even if the event is public');

	$weekdaystarts = array(
		'Monday'   => lang('Monday'),
		'Sunday'   => lang('Sunday'),
		'Saturday' => lang('Saturday')
	);
	create_select_box('weekday starts on','weekdaystarts',$weekdaystarts,
		'This day is shown as first day in the week or month view.');

	for ($i=0; $i < 24; ++$i)
	{
		$options[$i] = $GLOBALS['phpgw']->common->formattime($i,'00');
	}
	create_select_box('work day starts on','workdaystarts',$options,
		'This defines the start of your dayview. Events before this time, are shown above the dayview.<br>This time is also used as a default starttime for new events.');
	create_select_box('work day ends on','workdayends',$options,
		'This defines the end of your dayview. Events after this time, are shown below the dayview.');
	$intervals = array(
		5	=> '5',
		10	=> '10',
		15	=> '15',
		20	=> '20',
		30	=> '30',
		45	=> '45',
		60	=> '60'
	);
	create_select_box('Intervals in day view','interval',$intervals,
		'Defines the size in minutes of the lines in the day view.');
	create_input_box('default appointment length (in minutes)','defaultlength',
		'Default length of newly created events. The length is in minutes, eg. 60 for 1 hour.','',3);

	$groups = $GLOBALS['phpgw']->accounts->membership($GLOBALS['phpgw_info']['user']['account_id']);
	$options = array(-1 => lang('none'));
	if (is_array($groups))
	{
		foreach($groups as $group)
		{
			$options[$group['account_id']] = $GLOBALS['phpgw']->common->grab_owner_name($group['account_id']);
		}
	}
	create_select_box('Preselected group for entering the planner','planner_start_with_group',$options,
		'This group that is preselected when you enter the planner. You can change it in the planner anytime you want.');

	$planner_intervals = array(
		1	=> '1',
		2	=> '2',
		3	=> '3',
		4	=> '4',
	);
	create_select_box('Intervals per day in planner view','planner_intervals_per_day',
		$planner_intervals,'Specifies the the number of intervals shown in the planner view.');

	$defaultfilter = array(
		'all'     => lang('all'),
		'private' => lang('private only'),
//		'public'  => lang('global public only'),
//		'group'   => lang('group public only'),
//		'private+public' => lang('private and global public'),
//		'private+group'  => lang('private and group public'),
//		'public+group'   => lang('global public and group public')
	);
	create_select_box('Default calendar filter','defaultfilter',$defaultfilter,
		'Which events do you want to see when you enter the calendar.');

	create_check_box('Set new events to private','default_private',
		'Should new events created as private by default ?');

	create_check_box('Print the mini calendars','display_minicals',
		'Should the mini calendars by printed / displayed in the printer friendly views ?');

	create_check_box('Print calendars in black & white','print_black_white',
		'Should the printer friendly view be in black & white or in color (as in normal view)?');

	$freebusy_url = $GLOBALS['phpgw_info']['server']['webserver_url'].'/calendar/freebusy.php?user='.$GLOBALS['phpgw_info']['user']['account_lid'].'&password='.$GLOBALS['phpgw_info']['user']['preferences']['calendar']['freebusy_pw'];
	if ($freebusy_url[0] == '/')
	{
		$freebusy_url = ($_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$freebusy_url;
	}
	$freebusy_help = lang('Should not loged in persons be able to see your freebusy information? You can set an extra password, different from your normal password, to protect this informations. The freebusy information is in iCal format and only include the times when you are busy. It does not include the event-name, description or locations. The URL to your freebusy information is %1.','<a href="'.$freebusy_url.'" target="_blank">'.$freebusy_url.'</a>');
	create_check_box('Make freebusy information availible to not loged in persons?','freebusy',
		$freebusy_help,'',False);
	create_input_box('Password for not loged in users to your freebusy information?','freebusy_pw',
		'If you dont set a password here, the information is availible to everyone, who knows the URL!!!');
	create_check_box('Display status of events','display_status',
		'Should the status of the event-participants (accept, reject, ...) be shown in brakets after each participants name ?');
	create_check_box('Leave my calendar public','public_view',
		'This option allow everybody to view your calendar in readmode.');
	create_select_box('Default view of your calendar','title_view',array(
			'0' => lang('Title and Description'),
			'1' => lang('Only Title')
		),'This option enable or disable the meeting description view your calendar in readmode.');