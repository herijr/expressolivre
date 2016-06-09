<?php
  /***************************************************************************\
  * eGroupWare - Contacts Center                                              *
  * http://www.egroupware.org                                                 *
  * Written by:                                                               *
  *  - Raphael Derosso Pereira <raphaelpereira@users.sourceforge.net>         *
  *  sponsored by Thyamad - http://www.thyamad.com
  * ------------------------------------------------------------------------- *
  *  This program is free software; you can redistribute it and/or modify it  *
  *  under the terms of the GNU General Public License as published by the    *
  *  Free Software Foundation; either version 2 of the License, or (at your   *
  *  option) any later version.                                               *
  \***************************************************************************/

	/*
	  Set a global flag to indicate this file was found by admin/config.php.
	  config.php will unset it after parsing the form values.
	*/
	$GLOBALS['phpgw_info']['server']['found_validation_hook'] = True;

	/* Check all settings to validate input.  Name must be 'final_validation' */
	function final_validation($value='')
	{
		$error = false;
		
		if($value['cc_global_source0'] == 'ldap')
		{
			if (!$value['cc_ldap_host0'])
			{
				$error[] = '<br>LDAP host must be set!';
			}
			
			if(!$value['cc_ldap_context0'])
			{
				$error[] = '<br>There must be a Context';
			}
			
			if(!$value['cc_ldap_browse_dn0'])
			{
				$error[] = '<br>The Browse Account must be set';
			}	
		}
		
		if ($error)
		{ 
			$GLOBALS['config_error'] = implode("\n", $error);
		}
	}
?>
