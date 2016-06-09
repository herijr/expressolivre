<?php
/**************************************************************************\
* phpGroupWare API - Timed Asynchron Services for eGroupWare               *
* Written by Ralf Becker <RalfBecker@outdoor-training.de>                  *
* Class for creating cron-job like timed calls of eGroupWare methods       *
* -------------------------------------------------------------------------*
* This library is part of the eGroupWare API                               *
* http://www.eGroupWare.org                                                *
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/
defined('ASYNC_LOG') or define('ASYNC_LOG', stristr(PHP_OS, 'WIN')? 'C:\\async.log' : '/tmp/async.log' );

/*!
@class asyncservice
@author Ralf Becker
@copyright GPL - GNU General Public License
@abstract The class implements a general eGW service to execute callbacks at a given time.
@discussion see http://www.egroupware.org/wiki/TimedAsyncServices
*/

class asyncservice
{
	var $public_functions = array(
		'set_timer' => True,
		'check_run' => True,
		'cancel_timer' => True,
		'read'      => True,
		'install'   => True,
		'installed' => True,
		'last_check_run' => True
	);
	var $php = '';
	var $crontab = '';
	var $db;
	var $db_table = 'phpgw_async';
	var $debug = 0;
	
	/**
	 * Constructor of the class asyncservice
	 */
	function asyncservice()
	{
		$this->db = $GLOBALS['phpgw']->db;
		
		$this->cronline = PHPGW_SERVER_ROOT . '/phpgwapi/cron/asyncservices.php '.$GLOBALS['phpgw_info']['user']['domain'];
		
		$this->only_fallback = substr(php_uname(), 0, 7) == "Windows";	// atm cron-jobs dont work on win
	}

	/*!
	@function set_timer
	@abstract calculates the next run of the timer and puts that with the rest of the data in the db for later execution.
	@syntax set_timer($times,$id,$method,$data,$account_id=False)
	@param $times unix timestamp or array('min','hour','dow','day','month','year') with execution time. 
		Repeated events are possible to shedule by setting the array only partly, eg. 
		array('day' => 1) for first day in each month 0am or array('min' => '* /5', 'hour' => '9-17') 
		for every 5mins in the time from 9am to 5pm.
	@param $id unique id to cancel the request later, if necessary. Should be in a form like 
		eg. '<app><id>X' where id is the internal id of app and X might indicate the action.
	@param $method Method to be called via ExecMethod($method,$data). $method has the form 
		'<app>.<class>.<public function>'.
	@param $data This data is passed back when the method is called. It might simply be an 
		integer id, but it can also be a complete array.
	@param $account_id account_id, under which the methode should be called or False for the actual user
	@result False if $id already exists, else True	
	*/
	/**
	 *  Add job
	 *  
	 * @param string $id
	 * @param array $times
	 * @param string $method
	 * @param array $data
	 * @param integer $account_id
	 * @return boolean
	 */
	public function add( $id, $times, $method, $data, $account_id = false, $priority = 0)
	{
		// Check params
		if ( empty($id) || empty($method) || $this->read($id) || !($next = $this->_calc_next_run( $times )) ) return false;
		
		// Write new job
		return $this->write( array(
			'id'			=> $id,
			'next'			=> $next,
			'times'			=> $times,
			'method'		=> $method,
			'data'			=> $data,
			'account_id'	=> ($account_id === false)? $GLOBALS['phpgw_info']['user']['account_id'] : $account_id,
			'priority'		=> $priority,
		));
	}
	
	/**
	 * Remove job
	 * 
	 * @param string $id
	 */
	public function remove( $id )
	{
		return $this->_delete( $id );
	}
	
	/**
	 * Get last check data
	 *
	 * @return <boolean, array('start', 'end', 'run_by')>
	 */
	public function get_last_check_run()
	{
		$id = '##last-check-run##';
		$last = $this->read( $id );
		return ( $last && isset( $last[$id] ) )? $last[$id]['data'] : false;
	}
	
	/**
	 * Get next run timestamp
	 *
	 * @param array $times
	 * @return <boolean, integer>
	 */
	public function get_next_run( $times )
	{
		return $this->_calc_next_run( $times );
	}
	
	/**
	 * @abstract Checks if there are any jobs ready to run (timer expired) and executes them
	 * 
	 * @param string $run_by
	 * @return <boolean, number>
	 */
	public function check_run( $run_by = '' )
	{
		// Update last check run date
		$this->_last_check_run( 'start', $run_by );
		
		// Check others proccess running
		$this->_check_running();
		
		// Count jobs
		$count_exec = 0;
		
		// Allocate next job
		while ( $job = $this->_get_next_job() ) {
			$this->log('exec job: '.$job['id']);
			
			try {
				// Set user data
				$this->_set_globals( $job['account_id'], $job['method'] );
				
				// Execute job method
				ExecMethod( $job['method'], $job['data'] );
				
				$count_exec++;
				
			} catch(Exception $e) {
				
				if ( !empty($GLOBALS['phpgw_info']['server']['sugestoes_email_to']) ) {
					mail(
						$GLOBALS['phpgw_info']['server']['sugestoes_email_to'],
						'Expresso asyncservices warning',
						'Throw exception on async services class:'."\n".$e->getMessage()."\n".print_r($job,true),
						'From: asyncservices'
					);
				}
			}
			
			$this->log('finalize_job job: '.$job['id']);
			
			// Deallocate job
			$this->_finalize_job( $job['id'] );
		}
		
		// Update last check run date
		$this->_last_check_run( 'end', $run_by );
		
		return $count_exec? $count_exec : false;
	}
	
	/**
	 * Update last check run date
	 * 
	 * @param string $set <'start'|'end'>
	 * @param string $run_by
	 * @return boolean
	 */
	public function log( $msg )
	{
		if ( !defined('ASYNC_LOG') ) return false;
		$fp = fopen( ASYNC_LOG,'a+' );
		fwrite( $fp, date('Y/m/d H:i:s ').(isset($_GET['domain'])? $_GET['domain'] : 'default').':'.getmypid().': '.$msg."\n" );
		fclose( $fp );
		return true;
	}
	
	/**
	 * Update last check run date
	 * 
	 * @param string $set <'start'|'end'>
	 * @param string $run_by
	 * @return boolean
	 */
	private function _last_check_run( $set, $run_by )
	{
		$last = current($this->read('##last-check-run##'));
		$last['data']['run_by'] = $run_by;
		$last['data'][$set] = time();
		$this->write( $last, true );
		return true;
	}
	
	/**
	 * Check others proccess running
	 * 
	 * @return boolean
	 */
	private function _check_running( )
	{
		// Initialize counter report
		$report = new stdClass;
		$report->elder = 0;
		$report->stopped = 0;
		
		// Find running jobs
		$this->db->query('SELECT * FROM '.$this->db_table.' WHERE pid IS NOT NULL');
		$jobs = array();
		while ($this->db->next_record())
			$jobs[] = $this->db->row();
		
		// Test each job
		foreach ($jobs as $job) {
			$ps = $this->_pidinfo( $job['pid'] );
			
			// Check if exists and if command name is php
			if ($ps && preg_match('/^php[45]?$/',$ps['COMMAND'])) {
				
				// Report job running by enlapsed time
				$mm = $this->_time_to_minutes( $ps['ELAPSED'] );
				if ($mm >= 60 && $mm < 65 ) $report->elder++;
				
			} else {
				
				$job['times'] = unserialize($job['times']);
				$job['data'] = unserialize($job['data']);
				
				// Retry job again
				$job['data']['asyncservice_retry'] = (int)(isset($job['data']['asyncservice_retry'])? $job['data']['asyncservice_retry']+1 : 1);
				if ( (int)($job['data']['asyncservice_retry']) < 5 ) $this->write( $job , true);
				else {
					
					// Abort job stopped unexpectedly
					$this->_delete( $job['id'] );
					$report->stopped++;
				}
			}
		}
		
		if (($report->elder || $report->stopped) && ( !empty($GLOBALS['phpgw_info']['server']['sugestoes_email_to']) )) {
			mail(
				$GLOBALS['phpgw_info']['server']['sugestoes_email_to'],
				'Expresso asyncservices warning',
				'Problem detected on async services class:'."\n".print_r($report,true),
				'From: asyncservices'
			);
		}
		return true;
	}
	
	/**
	 * Report a snapshot of the current processes
	 * 
	 * @param integer $pid
	 * @return array('STAT','ELAPSED','COMMAND')
	 */
	private function _pidinfo( $pid )
	{
		exec('ps -o stat,etime,comm '.(int)$pid, $ps, $result);
		if ($result > 0) return false;
		foreach ($ps as $key => $val) $ps[$key] = explode(' ',preg_replace('/ +/',' ',trim($val)));
		return array_combine($ps[0],$ps[1]);
	}
	
	/**
	 * Convert string elapsed time, in the form [[DD-]hh:]mm:ss, to minutes
	 * 
	 * @param string $time
	 * @return integer
	 */
	private function _time_to_minutes( $time )
	{
		$idxs = array(0,1,60,1440);
		$sum = 0;
		foreach (array_reverse(explode(':',strtr($time,'-',':'))) as $key => $value)
			$sum += ((int)$value) * ($idxs[$key]);
		return (int)$sum;
	}
	
	/**
	 * @abstract Get next job to execute
	 * 
	 * @return boolean
	 */
	private function _get_next_job()
	{
		$tz_offset = (int)(60*60*$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset']);
		$time = time() - $tz_offset;
		
		$sql = 'UPDATE '.$this->db_table.' '.
			'SET pid = '.getmypid().' '.
			'WHERE id = ( '.
				'SELECT id '.
				'FROM '.$this->db_table.' '.
				'WHERE next <= '.$time.' '.
				'AND method != \'none\' '.
				'AND priority < COALESCE(( '.
					'SELECT priority '.
					'FROM '.$this->db_table.' '.
					'WHERE pid IS NOT NULL '.
					'ORDER BY priority '.
					'LIMIT 1 '.
				'), 100) '.
				'ORDER BY priority, next '.
				'LIMIT 1 '.
			') '.
			'RETURNING *';
		
		$this->db->query( $sql );
		
		$job = $this->db->next_record()? $this->db->row() : false;
		if ($job) {
			$job['times'] = ((int)(unserialize($job['times']))) + $tz_offset;
			$job['data'] = unserialize($job['data']);
		}
		return $job;
	}
	
	/**
	 * Set current job session with user data
	 * 
	 * @param integer $account_id
	 * @param string $method
	 */
	private function _set_globals( $account_id, $method )
	{
		if ( $GLOBALS['phpgw_info']['user']['account_id'] != $account_id ) {
			
			$domain	= $GLOBALS['phpgw_info']['user']['domain'];
			$lang	= $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'];
			unset($GLOBALS['phpgw_info']['user']);
			
			if ($GLOBALS['phpgw']->session->account_id = $account_id) {
				
				$GLOBALS['phpgw']->session->account_lid = $GLOBALS['phpgw']->accounts->id2name( $account_id );
				$GLOBALS['phpgw']->session->account_domain = $domain;
				$GLOBALS['phpgw']->session->read_repositories(false,false);
				$GLOBALS['phpgw_info']['user']  = $GLOBALS['phpgw']->session->user;
				
				if ( $lang != $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'] ) {
					
					unset($GLOBALS['lang']);
					$GLOBALS['phpgw']->translation->add_app('common');
				}
			
			} else $GLOBALS['phpgw_info']['user']['domain'] = $domain;
		}
		
		list($app) = explode('.',$method);
		$GLOBALS['phpgw']->translation->add_app($app);
	}
	
	/**
	 * Free proccess for this current job
	 * 
	 * @param string $id
	 * @return boolean
	 */
	private function _finalize_job( $id )
	{
		$jobs = $this->read( $id );
		
		if ( ! ( $jobs && isset($jobs[$id])) ) return false;
		
		$job = $jobs[$id];
		$job['next'] = $this->_calc_next_run($job['times']);
		
		if ( $job['next'] ) $this->write( $job, true );
		else $this->_delete( $job['id'] );
		
		return true;
	}
	
	/*!
	 @function next_run
	@abstract calculates the next execution time for $times
	@syntax next_run($times)
	@param $times unix timestamp or array('year'=>$year,'month'=>$month,'dow'=>$dow,'day'=>$day,'hour'=>$hour,'min'=>$min)
	with execution time. Repeated execution is possible to shedule by setting the array only partly,
	eg. array('day' => 1) for first day in each month 0am or array('min' => '/5', 'hour' => '9-17')
	for every 5mins in the time from 9am to 5pm. All not set units before the smallest one set,
	are taken into account as every possible value, all after as the smallest possible value.
	@param $debug if True some debug-messages about syntax-errors in $times are echoed
	@result a unix timestamp of the next execution time or False if no more executions
	*/
	private function _calc_next_run( $times )
	{
		// Mark current time
		$now = time();
		
		// Case TIMESTAMP: check if $times is unix timestamp
		// if it's not expired return it, else false
		if ( !is_array( $times ) )
			return ((int)$times > (int)$now)? (int)$times : false;
		
		// If an array is given, we have to enumerate the possible times first
		$units		= array( 'year' => 'Y',          'month' => 'm', 'day' => 'd', 'dow' => 'w', 'hour' => 'H', 'min' => 'i' );
		$max_unit	= array( 'year' => date('Y')+10, 'month' => 12,  'day' => 31,  'dow' => 6,   'hour' => 23,  'min' => 59  );
		$min_unit	= array( 'year' => date('Y'),    'month' => 1,   'day' => 1,   'dow' => 0,   'hour' => 0,   'min' => 0   );
	
		// get the number of the first and last pattern set in $times,
		// as empty patterns get enumerated before the the last pattern and
		// get set to the minimum after
		$n = $first_set = $last_set = 0;
		foreach ($units as $u => $date_pattern) {
			++$n;
			if ( isset($times[$u]) ) {
				$last_set = $n;
				if ( !$first_set ) $first_set = $n;
			}
		}
		
		// now we go through all units and enumerate all patterns and not set patterns
		// (as descript above), enumerations are arrays with unit-values as keys
		$n = 0;
		foreach($units as $u => $date_pattern) {
			++$n;
			if ( isset($times[$u]) ) {
				$time = explode(',',$times[$u]);
				$times[$u] = array();
				foreach ($time as $t) {
					if (strstr($t,'-') !== False && strstr($t,'/') === False) {
						list($min,$max) = $arr = explode('-',$t);
						if (count($arr) != 2 || !is_numeric($min) || !is_numeric($max) || $min > $max) {
							return False;
						}
						for ($i = (int)$min; $i <= $max; ++$i) {
							$times[$u][$i] = True;
						}
					} else {
						if ($t == '*') $t = '*/1';
						list($one,$inc) = $arr = explode('/',$t);
						if (!(is_numeric($one) && count($arr) == 1 || count($arr) == 2 && is_numeric($inc))) {
							return False;
						}
						if (count($arr) == 1) {
							$times[$u][(int)$one] = True;
						} else {
							list($min,$max) = $arr = explode('-',$one);
							if (empty($one) || $one == '*') {
								$min = $min_unit[$u];
								$max = $max_unit[$u];
							} else if (count($arr) != 2 || $min > $max) {
								return False;
							}
							for ($i = $min; $i <= $max; $i += $inc) {
								$times[$u][$i] = True;
							}
						}
					}
				}
			} else if ($n < $last_set || $u == 'dow') {
				// before last value set (or dow) => empty gets enumerated
				for ($i = $min_unit[$u]; $i <= $max_unit[$u]; ++$i) {
					$times[$u][$i] = True;
				}
			} else {
				// => after last value set => empty is min-value
				$times[$u][$min_unit[$u]] = True;
			}
		}
		
		// now we have the times enumerated, lets find the first not expired one
		$found = array();
		while (!isset($found['min'])) {
			$future = False;
			foreach ($units as $u => $date_pattern) {
				$unit_now = $u != 'dow' ?
					(int)date($date_pattern) :
					(int)date($date_pattern,mktime(12,0,0,$found['month'],$found['day'],$found['year']));
				
				if (isset($found[$u])) {
					$future = $future || $found[$u] > $unit_now;
					continue; // already set
				}
				foreach ($times[$u] as $unit_value => $nul) {
					switch ($u) {
						case 'dow':
							$valid = $unit_value == $unit_now;
							break;
						case 'min':
							$valid = $future || $unit_value > $unit_now;
							break;
						default:
							$valid = $future || $unit_value >= $unit_now;
							break;
					}
					
					// valid and not over
					if ($valid && ($u != $next || $unit_value > $over)) {
						$found[$u] = $unit_value;
						$future = $future || $unit_value > $unit_now;
						break;
					}
				}
				// we have to try the next one, if it exists
				if (!isset($found[$u])) {
					$next = array_keys($units);
					if (!isset($next[count($found)-1])) {
						return False;
					}
					$next = $next[count($found)-1];
					$over = $found[$next];
					unset($found[$next]);
					break;
				}
			}
		}
		
		return mktime($found['hour'],$found['min'],0,$found['month'],$found['day'],$found['year']);
	}
	/*!
	@function read
	@abstract reads all matching db-rows / jobs
	@syntax reay($id=0)
	@param $id =0 reads all expired rows / jobs ready to run\
		!= 0 reads all rows/jobs matching $id (sql-wildcards '%' and '_' can be used)
	@result db-rows / jobs as array or False if no matches
	*/
	function read( $id = 0 )
	{
		$tz_offset = (int)(60*60*$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset']);
		$time = time() - $tz_offset;
		$id = $this->db->db_addslashes($id);
		
		$sql = 'SELECT * FROM '.$this->db_table.' ';
		if ( !$id ) {
			
			$sql .= 'WHERE next <= '.$time.' AND id != \'##last-check-run##\'';
			
		} else if (strpos($id,'%') !== false || strpos($id,'_') !== false) {
			
			$sql .= 'WHERE id LIKE \''.$id.'\' AND id != \'##last-check-run##\'';
			
		} else {
			
			$sql .= 'WHERE id = \''.$id.'\'';
		}
	
		$this->db->query( $sql, __LINE__, __FILE__ );
		
		$jobs = array();
		while ($this->db->next_record()) {
			
			$jobs[$this->db->f('id')] = array(
				'id'			=> $this->db->f('id'),
				'next'			=> $this->db->f('next'),
				'times'			=> ((int)(unserialize($this->db->f('times')))) + $tz_offset,
				'method'		=> $this->db->f('method'),
				'data'			=> unserialize($this->db->f('data')),
				'account_id'	=> $this->db->f('account_id')
			);
		}
		
		return count($jobs)? $jobs : false;
	}
	
	/**
	 * Write a job / db-row to the db
	 * 
	 * @param array $job		db-row as array
	 * @param boolean $exists	if true, we do an update, else we check if update or insert necesary
	 * @return boolean
	 */
	function write( $job, $exists = false )
	{
		// Check and format fields
		$job['id']			= '\''.$job['id'].'\'';
		$job['next']		= (int)$job['next'];
		$job['times']		= '\''.$this->db->db_addslashes(serialize($job['times'])).'\'';
		$job['method']		= '\''.$job['method'].'\'';
		$job['data']		= '\''.$this->db->db_addslashes(serialize($job['data'])).'\'';
		$job['account_id']	= (int)$job['account_id'];
		$job['priority']	= (int)(isset($job['priority'])? $job['priority'] : 0);
		$job['pid']			= 'NULL';
		
		// Check if update or insert necesary
		if ( !$exists ) $exists = (boolean) $this->read( trim( $job['id'], '\'' ) );
		
		// Prepares the array for the update
		if ( $exists ) {
			array_walk( $job, create_function('&$v,$k', '$v = $k.\' = \'.$v;') );
			$id = current( array_splice( $job, array_search( 'id', $job ), 1 ) );
		}
		
		$sql = $exists?
			( 'UPDATE '.$this->db_table.' SET '.implode( ', ', $job ).' WHERE '.$id ) :
			( 'INSERT INTO '.$this->db_table.'('.implode( ', ', array_keys( $job ) ).') VALUES ('.implode( ', ', $job ).')' );
		
		$this->db->query( $sql, __LINE__, __FILE__ );
		
		return (boolean)$this->db->affected_rows();
	}
	
	/**
	 * Delete db-row / job with id
	 * 
	 * @param string $id
	 * @return boolean
	 */
	private function _delete( $id )
	{
		// Event calendar
		$this->db->query('SELECT * FROM '.$this->db_table.' WHERE id = \''.$id.'\'' , __LINE__, __FILE__ );

		if( $this->db->num_rows() )
		{
			$this->db->query('DELETE FROM '.$this->db_table.' WHERE id = \''.$id.'\'' , __LINE__, __FILE__ );
		}
		
		// Event webconf
		$webConf 	= explode(":", $id );
		$webConfId	= "webconf:".$webConf[1].":0";

		$this->db->query('SELECT * FROM '.$this->db_table.' WHERE id = \''.$webConfId.'\'' , __LINE__, __FILE__ );
		
		if( $this->db->num_rows() )
		{
			$this->db->query('DELETE FROM '.$this->db_table.' WHERE id = \''.$webConfId.'\'' , __LINE__, __FILE__ );
		}

		return (boolean)$this->db->affected_rows();
	}

	function find_binarys()
	{
		static $run = False;
		if ($run)
		{
			return;
		}
		$run = True;

		if (substr(php_uname(), 0, 7) == "Windows") 
		{
			// ToDo: find php-cgi on windows
		}
		else
		{
			$binarys = array(
				'php'  => '/usr/bin/php',
				'php4' => '/usr/bin/php4',		// this is for debian
				'crontab' => '/usr/bin/crontab'
			);
			foreach ($binarys as $name => $path)
			{
				$this->$name = $path;	// a reasonable default for *nix

				if (!($Ok = @is_executable($this->$name)))
				{
					if (file_exists($this->$name))
					{
						echo '<p>'.lang('%1 is not executable by the webserver !!!',$this->$name)."</p>\n";
						$perms = fileperms($this->$name);
						if (!($perms & 0x0001) && ($perms & 0x0008))	// only executable by group
						{
							$group = posix_getgrgid(filegroup($this->$name));
							$webserver = posix_getpwuid(posix_getuid ());
							echo '<p>'.lang("You need to add the webserver user '%1' to the group '%2'.",$webserver['name'],$group['name'])."</p>\n";							}
					}
					if ($fd = popen('/bin/sh -c "type -p '.$name.'"','r'))
					{
						$this->$name = fgets($fd,256);
						@pclose($fd);
					}
					if ($pos = strpos($this->$name,"\n"))
					{
						$this->$name = substr($this->$name,0,$pos);
					}
				}
				if (!$Ok && !@is_executable($this->$name))
				{
					$this->$name = $name;	// hopefully its in the path
				}
				//echo "<p>$name = '".$this->$name."'</p>\n";
			}
			if ($this->php4[0] == '/')	// we found a php4 binary
			{
				$this->php = $this->php4;
			}
		}
	
	}
	
	/**
	 * @function installed
	 * @abstract checks if phpgwapi/cron/asyncservices.php is installed as cron-job
	 * @syntax installed()
	 * @result the times asyncservices are run (normaly 'min'=>'* /5') or False if not installed or 0 if crontab not found
	 * @note Not implemented for Windows at the moment, always returns 0
	 */
	function installed()
	{
		if ($this->only_fallback) {
			return 0;
		}
		$this->find_binarys();
		
		// Nao eh executavel porque esta fora do openbase_dir
		/*
		if (!is_executable($this->crontab))
		{
			//echo "<p>Error: $this->crontab not found !!!</p>";
			return 0;
		}
		*/
		
		$times = False;
		$this->other_cronlines = array();
		if (($crontab = popen('/bin/sh -c "'.$this->crontab.' -l" 2>&1','r')) !== False)
		{
			while ($line = fgets($crontab,256))
			{
				if ($this->debug) echo 'line '.++$n.": $line<br>\n";
				$parts = explode(' ',$line,6);
				
				// Foi customizado para a Celepar.
				//if ($line[0] == '#' || count($parts) < 6 || ($parts[5][0] != '/' && substr($parts[5],0,3) != 'php')) 
				if ($line[0] == '#' || count($parts) < 6)
				{
					// ignore comments
					if ($line[0] != '#')
					{
						$times['error'] .= $line;
					}
				} 
				elseif (strstr($line,$this->cronline) !== False)
				{
					$cron_units = array('min','hour','day','month','dow');
					foreach($cron_units as $n => $u)
					{
						$times[$u] = $parts[$n];
					}
					$times['cronline'] = $line;
				}
				else
				{
					$this->other_cronlines[] = $line;
				}
			}
			@pclose($crontab);
		}
		return $times;
	}
	
	/**
	 * @function insall
	 * @abstract installs /phpgwapi/cron/asyncservices.php as cron-job
	 * @syntax install($times)
	 * @param $times array with keys 'min','hour','day','month','dow', not set is equal to '*'.
	 * 	False means de-install our own crontab line
	 * @result the times asyncservices are run, False if they are not installed,
	 * 	0 if crontab not found and ' ' if crontab is deinstalled
	 * @note Not implemented for Windows at the moment, always returns 0
	 */
	function install( $times )
	{
		if ($this->only_fallback && $times !== False) {
			return 0;
		}
		$this->installed();	// find other installed cronlines

		if (($crontab = popen('/bin/sh -c "'.$this->crontab.' -" 2>&1','w')) !== False)
		{
			if (is_array($this->other_cronlines))
			{
				foreach ($this->other_cronlines as $cronline)
				{
					fwrite($crontab,$cronline);		// preserv the other lines on install
				}
			}
			if ($times !== False)
			{
				$cron_units = array('min','hour','day','month','dow');
				$cronline = '';
				foreach($cron_units as $cu)
				{
					$cronline .= (isset($times[$cu]) ? $times[$cu] : '*') . ' ';
				}
				 					 
				//$cronline .= $this->php.' -q '.$this->cronline."\n";
				$php_version = preg_match("/5./",phpversion()) ? "php5" : "php4";
				$cronline .= "cd /var/www/expresso/phpgwapi/cron/; $php_version -c /etc/$php_version/apache2/php.ini -q /var/www/expresso/phpgwapi/cron/asyncservices.php default\n";
				//echo "<p>Installing: '$cronline'</p>\n";
				fwrite($crontab,$cronline);
			}
			@pclose($crontab);
		}
		return $times !== False ? $this->installed() : ' ';
	}
	
	public function cancel_timer( $id ) { return $this->remove( $id ); }
	public function last_check_run() { return $this->get_last_check_run(); }
	public function next_run( $times, $debug = false ) { return $this->get_next_run( $times ); }
	public function set_timer( $times, $id, $method, $data, $account_id = false) { return $this->add( $id, $times, $method, $data, $account_id ); }
}
