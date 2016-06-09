#!/usr/bin/php -q
<?php
/**************************************************************************\
* eGroupWare API - Timed Asynchron Services for eGroupWare                 *
* Written by Ralf Becker <RalfBecker@outdoor-training.de>                  *
* Class for creating cron-job like timed calls of eGroupWare methods       *
* -------------------------------------------------------------------------*
* This library is part of the eGroupWare API                               *
* http://www.egroupware.org/                                               *
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

// Remove the comment from one of the following lines to enable loging
defined('ASYNC_LOG') or define('ASYNC_LOG', stristr(PHP_OS, 'WIN')? 'C:\\async.log' : '/tmp/async.log' );

function async_log( $msg ) {
	if ( !defined('ASYNC_LOG') ) return false;
	$fp = fopen( ASYNC_LOG,'a+' );
	fwrite( $fp, date('Y/m/d H:i:s ').$_GET['domain'].':'.getmypid().': '.$msg."\n" );
	fclose( $fp );
	return true;
}

function async_shutdown() {
	if ( !is_null( $e = error_get_last() ) && ( $e['type'] & (E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING) ))
		async_log($e['type'].') '.$e['file'].':'.$e['line'].': '.$e['message']);
}

register_shutdown_function('async_shutdown');

$_GET['domain'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'default';

async_log( "asyncservice started" );

// Need to be adapted if this script is moved somewhere else
$path_to_egroupware = realpath(dirname(__FILE__).'/../..');

// 'noapi' => true : this stops header.inc.php to include phpgwapi/inc/function.inc.php
$GLOBALS['phpgw_info']['flags'] = array(
	'currentapp'	=> 'login',
	'noapi'			=> true,
);

if ( !is_readable( $path_to_egroupware.'/header.inc.php' ) ) {
	async_log( "asyncservice.php: Could not find '$path_to_egroupware/header.inc.php', exiting !!!" );
	exit(1);
}

include($path_to_egroupware.'/header.inc.php');
unset($GLOBALS['phpgw_info']['flags']['noapi']);

$db_type = $GLOBALS['phpgw_domain'][$_GET['domain']]['db_type'];
if ( !isset($GLOBALS['phpgw_domain'][$_GET['domain']]) || empty($db_type) ) {
	async_log( "asyncservice.php: Domain '$_GET[domain]' is not configured or renamed, exiting !!!" );
	exit(1);
}

// Some constanst for pre php4.3
defined('PHP_SHLIB_SUFFIX') or define('PHP_SHLIB_SUFFIX',strtoupper(substr(PHP_OS, 0,3)) == 'WIN' ? 'dll' : 'so');
defined('PHP_SHLIB_PREFIX') or define('PHP_SHLIB_PREFIX',PHP_SHLIB_SUFFIX == 'dll' ? 'php_' : '');

$db_extension = PHP_SHLIB_PREFIX.$db_type.'.'.PHP_SHLIB_SUFFIX;
if ( ( !extension_loaded( $db_type ) ) && !( version_compare( PHP_VERSION, '5.3.0', '<' ) && dl( $db_extension ) ) ) {
	async_log( "asyncservice.php: Extension '$db_type' is not loaded and can't be loaded via dl('$db_extension') !!!" );
}

// No php4-sessions availible for cgi
$GLOBALS['phpgw_info']['server']['sessions_type'] = 'db';

include(PHPGW_API_INC.'/functions.inc.php');
$preferences = createobject('phpgwapi.preferences');
$preferences->read();

$num = ExecMethod('phpgwapi.asyncservice.check_run','crontab');
async_log( $num ? "$num job(s) executed\n" : 'Nothing to execute'."\n" );

$GLOBALS['phpgw']->common->phpgw_exit();
