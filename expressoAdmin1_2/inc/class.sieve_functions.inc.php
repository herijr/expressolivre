<?php
include_once('class.functions.inc.php');
include_once('sieve-php.lib.php');

class sieve_functions
{
	var $functions = null;
	
	function sieve_functions()
	{
		$this->functions	= new functions;
	}
	
	function _get_connection($uid, $profile)
	{
		$sieve = new sieve(
			$profile['imapSieveServer'],
			$profile['imapSievePort'],
			$uid,
			$profile['imapAdminPW'],
			$profile['imapAdminUsername'],
			'PLAIN'
		);
		return $sieve->sieve_login()? $sieve : false;
	}
	
	function move_scripts($uid, $old_profile, $new_profile)
	{
		$old_sieve = $this->_get_connection($uid, $old_profile);
		if ( !$old_sieve ) return array('status' => false, 'msg' => $this->functions->lang('Can not login sieve'));
		
		$old_sieve->sieve_listscripts();
		
		if ( isset($old_sieve->error) ) {
			if ( $old_sieve->error == 20 ) return array('status' => true);
			else return array('status' => false, 'msg' => $this->functions->lang($old_sieve->error_raw));
		}
		$scriptname = $old_sieve->response["ACTIVE"];
		
		$old_sieve->sieve_getscript( $scriptname );
		$script = trim(implode("",is_array($old_sieve->response)? $old_sieve->response : array()))."\n";
		
		$new_sieve = $this->_get_connection($uid, $new_profile);
		if ( !$new_sieve ) return array('status' => false, 'msg' => $this->functions->lang('Can not login sieve'));
		
		if( !$new_sieve->sieve_sendscript( $scriptname, $script ) )
			return array('status' => false, 'msg' => $this->functions->lang('Problem saving sieve script'));
		
		if( !$new_sieve->sieve_setactivescript( $scriptname ) )
			return array('status' => false, 'msg' => $this->functions->lang('Problem activating sieve script'));
		
		$old_sieve->sieve_logout();
		$new_sieve->sieve_logout();
		return array('status' => true);
	}
}
