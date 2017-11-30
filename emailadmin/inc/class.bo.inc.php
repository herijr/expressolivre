<?php
	/***************************************************************************\
	* eGroupWare                                                                *
	* http://www.egroupware.org                                                 *
	* http://www.linux-at-work.de                                               *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/

class bo
{
	var $soemailadmin;
	var $ldap;
	var $ldapLimit;
	
	var $public_functions = array
	(
		'getProfile'			=> True,
		'getProfileList'		=> True
	);

	function bo()
	{
		$this->soemailadmin = CreateObject('emailadmin.so');
		$this->ldap = CreateObject('phpgwapi.common')->ldapConnect();

		//Limit Ldap
		$this->ldapLimit = 50000;
	}
	
	function deleteProfile( $profileID )
	{
		// Delete Domains, if exist
		if( $this->soemailadmin->getDomains( array("field" => "profileid", "value" => $profileID)) )
		{
			$this->soemailadmin->deleteDomains( $profileID );
		}	
		
		// Delete Profile
		$this->soemailadmin->deleteProfile( $profileID );
	}

	function getProfile( $mode = null, $value = null )
	{
		$mode = is_null($mode)? 'uid' : (in_array($mode,array('id','uid','mail'))? $mode : 'uid');
		$value = is_null($value)? (($mode == 'id')? 0 : $GLOBALS['phpgw_info']['user'][(($mode=='mail')?'email':'account_lid')]) : $value;
		
		if ( $mode == 'uid' ) {
			if ( $value == $GLOBALS['phpgw_info']['user']['account_lid'] )
				$value = $GLOBALS['phpgw_info']['user']['email'];
			else {
				$entry = ldap_get_entries(
					$this->ldap,
					ldap_search(
						$this->ldap,
						$GLOBALS['phpgw_info']['server']['ldap_context'],
						"(&(|(phpgwAccountType=u)(phpgwAccountType=l)(phpgwAccountType=s))(uid=".$value."))",
						array("mail")
					)
				);
				$value = $entry[0]['mail'][0];
			}
			$mode = 'mail';
		}
		
		if ( $mode == 'mail' ) $value = preg_replace('/.*@/','',$value);
		return $this->soemailadmin->getProfile( $mode, $value );
	}
    
	function getDefaultUserQuota( $domain = false )
	{
		return $this->soemailadmin->getDefaultUserQuota( $domain );;
	}

	function getUsersLdapByMail( $domain )
	{
		$result = @ldap_get_entries(
					$this->ldap, 
					@ldap_search(
						$this->ldap, 
						$GLOBALS['phpgw_info']['server']['ldap_context'], 
						('(&(phpgwaccounttype=u)(mail=*@'.$domain.'))'),						
						array("uid"), 0 , $this->ldapLimit + 1 ) 
					);

		return ( $result['count'] < $this->ldapLimit ) ? $result : false;
	}

    function getDomains( $domain )
    {
    	return $this->soemailadmin->getDomains( $domain );
    }

	function get_last_insert_id($table, $field)
	{
		return $this->soemailadmin->get_last_insert_id( $table, $field );
	}

	function getProfileList( $limit = null )
	{
		return $this->soemailadmin->getProfileList( $limit );
	}

	function moveDomain($params)
	{
		$domain 	= $this->getDomains( array("field" => "domainid", "value" => $params['domainid']) );
		
		$mailBoxes 	= array();		
		
		if( $domain[0]['profileid'] != $params['newprofileid'] )
		{
			$resultUids = $this->getUsersLdapByMail( $domain[0]['domain'] );
			
			if( !$resultUids )
			{				
				return array( "error" => "Domain with more than ".$this->ldapLimit." users" );
			}
			else
			{	
				if( count($resultUids) > 0 )
				{	
					foreach( $resultUids as $uid )
					{ 
						if( array_key_exists('uid',$uid) )
						{
							$mailBoxes[] = array(
								"profileid_orig"	=> $domain[0]['profileid'],
								"profileid_dest"	=> $params['newprofileid'],
								"uid"				=> $uid['uid'][0],
								"data"				=> serialize(array())
							);
						}
					}
				}

				//Move MailBoxes
				if( $this->soemailadmin->moveDomain( $params ) )
					return array("return" => $this->soemailadmin->addMBoxMigrate( $mailBoxes ) );
				else
					return array("error" => lang("Error moving domain") );
			}
		}
		else
			return array("error" => lang("Error same profile") );
	}

	function saveDomains( $params )
	{
		return $this->soemailadmin->saveDomains( $params );
	}
	function saveProfile($serverConfig)
	{
		return $this->soemailadmin->saveProfile($serverConfig);
	}
}
?>
