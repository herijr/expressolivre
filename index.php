<?php
	/**************************************************************************\
	* phpGroupWare                                                             *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	if( file_exists('mobile.php') ){ include 'mobile.php'; }
	
	$current_url = substr($_SERVER["SCRIPT_NAME"], 0, strpos($_SERVER["SCRIPT_NAME"],'index.php'));

	$phpgw_info = array();
	if(!file_exists('header.inc.php'))
	{
		Header('Location: '.$current_url.'setup/index.php');
		exit;
	}

	$GLOBALS['sessionid'] = isset($_GET['sessionid']) ? $_GET['sessionid'] : @$_COOKIE['sessionid'];
	if(!$GLOBALS['sessionid'])
	{
		Header('Location: '.$current_url.'login.php'.
		(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ?
		'?phpgw_forward='.urlencode('/index.php?'.$_SERVER['QUERY_STRING']):''));
		exit;
	}

	/*
		This is the menuaction driver for the multi-layered design
	*/
	if(isset($_GET['menuaction']))
	{
		list($app,$class,$method) = explode('.',@$_GET['menuaction']);
		if(! $app || ! $class || ! $method)
		{
			$invalid_data = True;
		}
	}
	else
	{
	//$phpgw->log->message('W-BadmenuactionVariable, menuaction missing or corrupt: %1',$menuaction);
	//$phpgw->log->commit();

		$app = 'home';
		$invalid_data = True;
	}

	if($app == 'phpgwapi')
	{
		$app = 'home';
		$api_requested = True;
	}

	$GLOBALS['phpgw_info']['flags'] = array(
		'noheader'   => True,
		'nonavbar'   => True,
		'currentapp' => $app
	);
	include('./header.inc.php');

	if (($GLOBALS['phpgw_info']['server']['use_https'] == 2) && ($_SERVER['HTTPS'] != 'on'))
	{
		Header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		exit;
	}

	if($app == 'home' && !$api_requested)
	{
		if( $_GET['dont_redirect_if_moble'] == 1 )
			Header('Location: ' . $GLOBALS['phpgw']->link('/home.php?dont_redirect_if_moble=1'));
		else
			Header('Location: ' . $GLOBALS['phpgw']->link('/home.php'));
	}

	if($api_requested)
	{
		$app = 'phpgwapi';
	}
	$_SESSION['phpgw_info']['server'] = $GLOBALS['phpgw_info']['server'];

	$GLOBALS[$class] = CreateObject(sprintf('%s.%s',$app,$class));
	if((is_array($GLOBALS[$class]->public_functions) && $GLOBALS[$class]->public_functions[$method]) && ! $invalid_data)
	{
		execmethod($_GET['menuaction']);
		unset($app);
		unset($class);
		unset($method);
		unset($invalid_data);
		unset($api_requested);
	}
	else
	{
		if(!$app || !$class || !$method)
		{
			if(@is_object($GLOBALS['phpgw']->log))
			{
				if($menuaction)
                {			
					$GLOBALS['phpgw']->log->message(array(
						'text' => "W-BadmenuactionVariable, menuaction missing or corrupt: $menuaction",
						'p1'   => $menuaction,
						'line' => __LINE__,
						'file' => __FILE__
					));
                }
			}
		}

		if(!is_array($GLOBALS[$class]->public_functions) || ! $$GLOBALS[$class]->public_functions[$method] && $method)
		{
			if(@is_object($GLOBALS['phpgw']->log))
			{				
			 	if($menuaction)
                {			
					$GLOBALS['phpgw']->log->message(array(
						'text' => "W-BadmenuactionVariable, attempted to access private method: $method",
						'p1'   => $method,
						'line' => __LINE__,
						'file' => __FILE__
					));
                }
			}
		}
		if(@is_object($GLOBALS['phpgw']->log))
		{
			$GLOBALS['phpgw']->log->commit();
		}

		Header('Location: ' . $GLOBALS['phpgw']->link('/home.php'));

	}
?>
