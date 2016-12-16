<?php
	/***********************************************************************************\
	* Expresso Administração															*
	* by Valmir André de Sena (valmirse@gmail.com, valmir.sena@ati.pe.gov.br
	* ----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			*
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.														*
	\***********************************************************************************/

include_once('class.functions.inc.php');
include_once('class.ldap_functions.inc.php');
include_once('class.imap.inc.php');
include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

class shared_accounts
{
	var $functions;
	var $ldap_functions;
	var $boemailadmin 	= null;
	var $imap			= null;

	function __construct()
	{			
		$this->ldap_functions 	= new ldap_functions;			
		$this->functions 		= new functions;
		$this->boemailadmin		= CreateObject('emailadmin.bo');
	}

	private function get_imap( $_profile, $_user)
	{
		if( $this->imap == null )
		{
			$this->imap = new imap( $_profile, $_user );
		}

		return $this->imap;
	}

	private function specialCharacters($param)
	{
		if( is_array($param) )
		{
			foreach( $param as $key => $val )
			{ 
				if( is_object($val) ){ continue; } 

				$param[$key] = $this->specialCharacters( $val );
			}

			return $param;
		}
		else
		{
		    $array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );

		    $array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );

		    $param = str_replace( $array1, $array2, $param);

   			$param = trim(preg_replace("/(~|\\*|#|--|;|\\\\)/","", $param ));

   			$param = mb_convert_encoding($param, "UTF-8", mb_detect_encoding($param, "UTF-8, ISO-8859-15, ISO-8859-1", true));

			return $param;
		}
	} 

	public function save( $_data )
	{
		if( isset($_data['data']) )
		{
			$params = array();
			
			$params = $this->specialCharacters( $_data['data'] );
			
			$params['uid'] = $this->get_shared_mail2uid( $params );

			$_result = array( 'status' => false );

			$_profile = $this->boemailadmin->getProfile('mail', $params['mail'] );

			if( isset($params['anchor']) && trim($params['anchor']) !== "" )
			{
				$params['old_uid'] 	= $this->get_shared_dn2uid($params['anchor']);

				if( is_array($_profile) && count($_profile) > 0 )
				{
					$_result = $this->ldap_functions->save_shared_accounts($params);
					
					$_imap = $this->get_imap( $_profile, $params['uid'] );

					$_mailQuota = intval( isset($params['mailquota']) ? $params['mailquota'] * 1024 : 10 * 1024 );

					$_result['status'] &= $_imap->set_quota( intval( $_mailQuota) );

					if( count($params['owners_acl']) > 0 )
					{	
				    	if( isset($params['owners_acl']) && count($params['owners_acl']) > 0 )
				    	{
				    		$_result['status'] &= $this->setACLMailbox( $_imap, $params, "SAVE" );
				    	}
				    }
				    else
				    {
				    	$_result['status'] &= $this->setACLMailbox( $_imap, array( "owners_acl" => "", "uid" => $params['uid'] ), "SAVE" );
				    }
			    }
			    else
			    {
			    	$_result = array( "msg" => lang('Profile not found') );
			    }
			}
			else
			{
				if( is_array($_profile) && count($_profile) > 0 )
				{	
					$_result = $this->ldap_functions->create_shared_accounts($params);

					$_imap = $this->get_imap( $_profile, $params['uid'] );

					$_result['status'] &= $_imap->create_inbox();

					$_mailQuota = intval( isset($params['mailquota']) ? $params['mailquota'] * 1024 : 10 * 1024 );

					$_result['status'] &= $_imap->set_quota( $_mailQuota );

			    	$_result['status'] &= $_imap->create_mailbox( 'INBOX' . $_profile['imapDelimiter'] . $_profile['imapDefaultDraftsFolder'] );
			    	
			    	$_result['status'] &= $_imap->create_mailbox( 'INBOX' . $_profile['imapDelimiter'] . $_profile['imapDefaultTrashFolder'] );
			    	
			    	$_result['status'] &= $_imap->create_mailbox( 'INBOX' . $_profile['imapDelimiter'] . $_profile['imapDefaultSentFolder'] );

			    	if( isset($params['owners_acl']) && count($params['owners_acl']) > 0 )
			    	{
			    		$_result['status'] &= $this->setACLMailbox( $_imap, $params['owners_acl'], "CREATE" );
			    	}
			    }
			    else
			    {
			    	$_result = array( "msg" => lang('Profile not found') );
			    }
			}
		}

		return $_result;
	}

	private function setACLMailbox( $imap, $params, $type )
	{
		$_result = true;

		$owners_acl = ( isset( $params['owners_acl'] ) ? $params['owners_acl'] : $params );

		if( $type == "CREATE" )
		{
			foreach( $owners_acl as $value )
			{
				$_result &= $imap->set_acl( 'INBOX', array( trim($value['user']) => trim($value['acl']) ) );
			}
		}
		else
		{
			$_acls_old	= $imap->get_acl( 'INBOX' );

			if( is_array($owners_acl) && count($owners_acl) > 0 )
			{
				unset( $_acls_old[ $params['uid'] ] );

				$_acls_new = array();

				foreach( $owners_acl as $value )
				{
					$_acls_new[$value['user'] ] = $value['acl'];
				}

				$_acls_remove = array_diff_assoc( $_acls_old, $_acls_new );

				/* Remove ACLs */
				foreach( $_acls_remove as $owner => $acl )
				{
					$_result &= $imap->set_acl( 'INBOX', array( trim($owner) => '' ) );
				}

				/* Add ACLs */
				foreach( $_acls_new as $owner => $acl )
				{
					$_result &= $imap->set_acl( 'INBOX', array( trim($owner) => trim($acl) ) );
				}
			}
			else
			{
				$_acl_owner = $_acls_old[ $params['uid'] ];

				/* Remove ACLs */
				foreach( $_acls_old as $owner => $acl )
				{
					$_result &= $imap->set_acl( 'INBOX', array( trim($owner) => '' ) );
				}

				/* Add ACL only owner*/
				$_result &= $imap->set_acl( 'INBOX', array( $params['uid'] => $_acl_owner ) );
			}
		}

		return $_result;
	}

	public function get($params)
	{
	    if (!$this->functions->check_acl($_SESSION['phpgw_info']['expresso']['user']['account_lid'], ACL_Managers::GRP_VIEW_INSTITUTIONAL_ACCOUNTS))
	    {
	            $return['status'] = false;
	            $return['msg'] = $this->functions->lang('You do not have right to list institutional accounts') . ".";
	            return $return;
	    }

	    $input = $params['input'];
	    $justthese = array("cn", "mail", "uid");
	    $trs = array();

	    foreach ($this->manager_contexts as $idx=>$context)
	    {
			$shared_accounts = ldap_search($this->ldap, $context, ("(&(phpgwAccountType=s)(|(mail=$input*)(cn=*$input*)))"), $justthese);
			$entries = ldap_get_entries($this->ldap, $shared_accounts);

			for ($i=0; $i<$entries['count']; $i++)
			{
				$tr = "<tr class='normal' onMouseOver=this.className='selected' onMouseOut=this.className='normal'><td onClick=sharedAccounts.edit('".$entries[$i]['uid'][0]."')>" . $entries[$i]['cn'][0] . "</td><td onClick=sharedAccounts.edit('".$entries[$i]['uid'][0]."')>" . $entries[$i]['mail'][0] . "</td><td align='center' onClick=sharedAccounts.remove('".$entries[$i]['uid'][0]."')><img HEIGHT='16' WIDTH='16' src=./expressoAdmin1_2/templates/default/images/delete.png></td></tr>";
				$trs[$tr] = $entries[$i]['cn'][0];
			}
	    }

	    $trs_string = "";

	    if( count($trs) > 0 )
	    {
			natcasesort($trs);

			foreach ($trs as $tr=>$cn){ $trs_string .= $tr; }
	    }

	    $return['status'] = 'true';
	    $return['trs'] = $trs_string;
	    return $return;
	}

	public function get_data( $params )
	{
		$_profile 		= $this->boemailadmin->getProfile('uid', $params['uid'] );
		$_accountLdap 	= $this->ldap_functions->get_shared_account_data( $params );
		$_imap 			= $this->get_imap( $_profile, $params['uid'] );
		$_acl 			= $_imap->get_acl( 'INBOX' );

		$_msgInfo = $_imap->get_info();

		$_inboxSize = (string)(round ((($_msgInfo->Size)/(1024*1024)), 2));

        $result = array(
			'user_context'		=> $_accountLdap['user_context'],
			'status'			=> $_accountLdap['status'],
			'accountStatus'		=> $_accountLdap['accountStatus'],
			'phpgwAccountVisible' 	=> $_accountLdap['phpgwAccountVisible'], 
			'cn'				=> $_accountLdap['cn'],
			'mail'				=> $_accountLdap['mail'],
			'description'		=> $_accountLdap['description'],
        	'owners_options' 	=> '',
        	'owners'         	=> array(),
        	'owners_acl'     	=> array(),
        	'mailquota'      	=> ( $_imap->get_quota() / 1024 ),
        	'display_empty_inbox' 			=> $this->functions->check_acl($_SESSION['phpgw_session']['session_lid'],ACL_Managers::ACL_SET_SHARED_ACCOUNTS_ACL_EMPTY) ? 'visible' : 'hidden',
		    'allow_edit_shared_account_acl' => $this->functions->check_acl($_SESSION['phpgw_session']['session_lid'],ACL_Managers::ACL_MOD_SHARED_ACCOUNTS_ACL),
		    'mailquota_used' 				=> $_inboxSize
        );

        foreach( $_acl as $key => $value )
        {
        	if( trim($key) !== trim($params['uid']) )
        	{	
	            $result['owners_options'] 	.= '<option value="'. $key .'">' . $this->ldap_functions->uid2cn($key) . '</option>';
	            $result['owners'][] 		= $key;
	            $result['owners_acl'][] 	= $value;
	        }
        }
        
        return $result;
	}

    public function delete($params)
    {
    	$_profile 	= $this->boemailadmin->getProfile('uid', $params['uid'] );

    	if( $_profile )
    	{	
	    	$_imap 		= $this->get_imap( $_profile, $params['uid'] );

	        $_result 	= $this->ldap_functions->delete_shared_account_data($params);

	        if( $_result['status'] ){ $_result['status'] &= $_imap->delete_inbox(); }
	        
	        return $_result;
	    }
	    else
	    {
	    	return array( "status" => false, "msg" => lang("Profile not found") );
	    }
    }

    //Get the shared uid from mail
    private function get_shared_mail2uid($params){
            list($uid) = explode("@",$params['mail']);
            if( preg_match("/^(ou|dc)=(\w+),.*/", $params['context'], $match) ){
                $uid = $uid."_".$match[2];
            }
            return $uid;
    }
    private function get_shared_dn2uid($dn){
            $uid = "";
            if( preg_match("/^uid=(\w+),.*/", $dn, $match) ){
                $uid = $match[1];
            }
            return $uid;
    }
    public function empty_inbox( $params )
    {
        $_uid = $this->get_shared_dn2uid( $params['uid'] );

        $_profile = $this->boemailadmin->getProfile('uid', $_uid );

        $_result = array( 'status' => false );
		
		// Verifica o acesso do gerente
        if( !( $this->functions->check_acl($_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_SET_USERS_EMPTY_INBOX ) ||
                $this->functions->check_acl($_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_SET_SHARED_ACCOUNTS_ACL_EMPTY ) ) )
		{
			$_result['msg'] = $this->functions->lang('You do not have access to clean an inbox');
		}
		else
		{
			try
			{
				$_imap = $this->get_imap( $_profile, $_uid );

				$_imap->empty_inbox();
			}
			catch( Exception $e )
			{
                $_result['msg'] = $e->getMessage();
			}

			$_result = array( 'status' => true, 'uid' => $_uid );
		}

		return $_result;
    }
}