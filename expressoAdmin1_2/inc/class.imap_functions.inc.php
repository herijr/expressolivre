<?php
include_once('class.functions.inc.php');
include_once('class.imap.inc.php');
include_once('class.sieve_functions.inc.php');
include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

class imap_functions
{
	var $functions			= null;
	var $sieve_functions	= null;
	var $boemailadmin		= null;
	var $_uid				= false;
	var $_profile			= false;
	var $_imap				= false;
	
	function imap_functions()
	{
		$this->functions		= new functions;
		$this->sieve_functions	= new sieve_functions;
		$this->boemailadmin		= CreateObject('emailadmin.bo');
	}
	
	function set_profile( $profile, $uid )
	{
		$this->_imap = false;
		$this->_profile = $profile;
		$this->_uid = $uid;
		
		if ( !$profile ) return false;
		
		$fields = array(
			'imapDefaultSentFolder'		=> 'sent',
			'imapDefaultDraftsFolder'	=> 'trash',
			'imapDefaultTrashFolder'	=> 'drafts',
			'imapDefaultSpamFolder'		=> 'spam',
		);
		
		foreach ($fields as $key => $value)
			if (!isset($this->_profile[$key]))
				$this->_profile[$key] = str_replace("*","", $this->functions->lang($value));
		
		$this->_imap = imap_open('{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].'/novalidate-cert}', $this->_profile['imapAdminUsername'], $this->_profile['imapAdminPW'], OP_HALFOPEN);
		
		return $this->_profile;
	}
	
	function reset_profile( $uid )
	{
		if ( $uid == $this->_uid ) return $this->_profile;
		
		return $this->set_profile( $this->boemailadmin->getProfile('uid', $uid), $uid );
	}
	
	function create( $uid, $mailquota = false )
	{
		$this->reset_profile($uid);
		
		$result = array('status' => true);

		if ($this->_profile === false) return array(
			'status'	=> false,
			'msg'		=> lang('Profile not found'),
		);
		
		$mailquota = ( $mailquota === false )? $this->_profile['defaultUserQuota'] : $mailquota;
		
		if (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapAdminServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid))
		{
			$error = imap_errors();
			if ($error[0] == 'Mailbox already exists')
			{
				$result['status'] = true;
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('Error on function') . " imap_functions->create(INBOX) ($uid):" . $error[0];
			}
			return $result;
		}

		if ( (!empty($this->_profile['imapDefaultSentFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapAdminServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultSentFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " imap_functions->create(".$this->_profile['imapDefaultSentFolder']."):" . $error[0];
			return $result;
		}

		if ( (!empty($this->_profile['imapDefaultDraftsFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapAdminServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultDraftsFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " imap_functions->create(".$this->_profile['imapDefaultDraftsFolder']."):" . $error[0];
			return $result;
		}

		if ( (!empty($this->_profile['imapDefaultTrashFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapAdminServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultTrashFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " imap_functions->create(".$this->_profile['imapDefaultTrashFolder']."):" . $error[0];
			return $result;
		}

		if (!imap_set_quota($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid, ($mailquota*1024))) 
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Error on function') . " imap_functions->create(imap_set_quota):" . $error[0];
			return $result;
		}
		
		try {
			$imap = new imap( $this->_profile, $uid );
			$imap->clean_shared();
		} catch( Exception $e) {}
		
		$result['status'] = true;

		return $result;
	}
	
	function get_user_info($uid)
	{
		$this->reset_profile($uid);
		
		$result = array();
		$result['mailbox_profile_descr'] = lang('Profile not found');
		$result['mailquota']             = isset( $this->_profile['defaultUserQuota'] )? $this->_profile['defaultUserQuota']: $this->boemailadmin->getDefaultUserQuota();
		$result['mailquota_used']        = '0.0';
		
		if ($this->_profile === false) return $result;
		
		$result['mailbox_profile_descr'] = $this->_profile['description'];
		$result['mailbox_profile_id'] = $this->_profile['profileID'];
		try {
			$imap = new imap( $this->_profile, $uid );
			$result['hasLostShare'] = ( count( $imap->get_acl( 'INBOX' ) ) > 1 ) || count( $imap->get_shared( 'INBOX' ) );
		} catch( Exception $e) {}
		
		if ($this->_imap === false)
			return array_merge($result,array('mailbox_error' => 'couldntOpenStream'));
		
		// return -1 if user without mailbox quota 
		$get_quota = @imap_get_quotaroot($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid);
		
		if ($get_quota === false)
			return array_merge($result,array('mailbox_error' => 'quotaNotFound'));
		
		if (is_array($get_quota))
		{
			if (is_null($get_quota['limit']))
			{
				$result['mailquota'] = '-1';
				$result['mailquota_used'] = '-1';
			}
			else
			{
				$result['mailquota'] = round (($get_quota['limit'] / 1024), 2);
				$result['mailquota_used'] = round (($get_quota['usage'] / 1024), 2);
			}
		}
		return $result;
	}
	
	function change_user_quota($uid, $quota)
	{
		$this->reset_profile($uid);
		
		$result = array('status' => true);
		
		if ($this->_profile === false) return $result;
		
		if (!imap_set_quota($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid, ($quota*1024)) )
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('it was not possible to change users mailbox quota') . ".\n";
			$result['msg'] .= $this->functions->lang('Server returns') . ': ' . imap_last_error();
		}
		
		return $result;
	}
	
	function delete_user($uid)
	{
		$this->reset_profile($uid);
		
		$result = array('status' => true);
		
		if ($this->_profile === false) return $result;
		
		if ( imap_last_error() === 'Mailbox does not exist' ) return $result;
		
		//Seta acl imap para poder deletar o user.
		// Esta sem tratamento de erro, pois o retorno da funcao deve ter um bug.
		imap_setacl($this->_imap, "user" . $this->_profile['imapDelimiter'] . $uid, $this->_profile['imapAdminUsername'], 'c');
		
		try {
			$imap = new imap( $this->_profile, $uid );
			$shared = $imap->get_shared();
		} catch( Exception $e) {
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Server returns') . ': ' . $e->getMessage();
			return $result;
		}
		
		if (!imap_deletemailbox($this->_imap, '{'.$this->_profile['imapAdminServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid))
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('it was not possible to delete users mailbox') . ".\n";
			$result['msg'] .= $this->functions->lang('Server returns') . ': ' . imap_last_error();
		}
		
		try {
			$imap->clean_shared( $shared );
		} catch( Exception $e) {}
		
		return $result;
	}
	
	function rename_mailbox($old_mailbox, $new_mailbox, $profile)
	{
		$this->set_profile( $profile, $new_mailbox );
		
		$result = array('status' => true);

		$get_quota = @imap_get_quotaroot($this->_imap, 'user' . $this->_profile['imapDelimiter'] . $old_mailbox);
		
		if ($get_quota === false)
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang("Mailbox do not exist. Process aborted.\n") . $this->functions->lang('Server returns') . ': ' . imap_last_error();
			return $result;
		}
			
		if (is_array($get_quota))
		{
			if (is_null($get_quota['limit']))
			{
				$def_quota = $this->_profile['defaultUserQuota'] * 1024;
				if (! @imap_set_quota($this->_imap, 'user' . $this->_profile['imapDelimiter'] . $old_mailbox, $def_quota ) )
				{
					$result['status'] = false;
					$result['msg'] = $this->functions->lang("User without quota. Error setting user quota. Process aborted.\n") . $this->functions->lang('Server returns') . ': ' . imap_last_error();
					return $result;
				}
				
				$get_quota['STORAGE']['limit'] = $def_quota;
				$get_quota['STORAGE']['usage'] = 0;
			}
		}

		$limit = $get_quota['STORAGE']['limit'];
		$usage = $get_quota['STORAGE']['usage'];
		
		if ($usage >= $limit)
		{
			if (! @imap_set_quota($this->_imap, 'user' . $this->_profile['imapDelimiter'] . $old_mailbox, (int)($usage+10240)) )
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang("Error increasing user quota. Process aborted.\n") . $this->functions->lang('Server returns') . ': ' . imap_last_error();
				return $result;
			}
		}
		
		try {
			$imap = new imap( $this->_profile, $old_mailbox );
			$shared = $imap->get_shared();
		} catch( Exception $e) {
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Server returns') . ': ' . $e->getMessage();
			return $result;
		}
		
		if (! @imap_renamemailbox($this->_imap,
						'{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].'}user' . $this->_profile['imapDelimiter'] . $old_mailbox,
						'{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].'}user' . $this->_profile['imapDelimiter'] . $new_mailbox) )
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('Server returns') . ': ' . imap_last_error();

		}


		if ($usage >= $limit)
		{
			if (! @imap_set_quota($this->_imap, 'user' . $this->_profile['imapDelimiter'] . $new_mailbox, (int)($limit)) )
			{
				$result['status'] = false;
				$result['msg'] .= $this->functions->lang("Error returning user quota.\n") . $this->functions->lang('Server returns') . ': ' . imap_last_error();
				
				@imap_renamemailbox($this->_imap,
					'{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].'}user' . $this->_profile['imapDelimiter'] . $new_mailbox,
					'{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].'}user' . $this->_profile['imapDelimiter'] . $old_mailbox);
			}
		}
		
		foreach ( $shared as $user_share => $params ) {
			
			try {
				$imap = new imap( $this->_profile, $user_share );
				
				if ( isset( $params[0] ) )
					$imap->rename_acl( array( 'user', $user_share ), $old_mailbox, $new_mailbox );
				
				if ( isset( $params['mbox'] ) )
					foreach ( $params['mbox'] as $path => $acl )
						$imap->rename_acl( array( 'user', $user_share, $path ), $old_mailbox, $new_mailbox );
				
			} catch( Exception $e) {}
			
		}
		
		return $result;
	}
	
	function empty_inbox($params)
	{
		$this->reset_profile($params['uid']);
		
		// Verifica o acesso do gerente
		if (!$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_SET_USERS_EMPTY_INBOX ))
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('You do not have access to clean an user inbox');
			return $result;
		}
		
		if ($this->_profile['imapTLSEncryption'] == 'yes')
		{
			$imap_options = '/tls/novalidate-cert';
		}
		else
		{
			$imap_options = '/notls/novalidate-cert';
		}

		$result['status'] = true;
		$uid = $params['uid'];
		
		$return_setacl = imap_setacl($this->_imap, "user" . $this->_profile['imapDelimiter'] . $uid, $this->_profile['imapAdminUsername'], 'lrswipcda');
		
		if ($return_setacl)
		{
			$mbox_stream = imap_open('{'.$this->_profile['imapAdminServer'].':'.$this->_profile['imapAdminPort'].$imap_options .'}user'. $this->_profile['imapDelimiter'] . $uid, $this->_profile['imapAdminUsername'], $this->_profile['imapAdminPW']);
			
			$check = imap_mailboxmsginfo($mbox_stream);
			$inbox_size = (string)(round ((($check->Size)/(1024*1024)), 2));
			
			$return_imap_delete = imap_delete($mbox_stream,'1:*');
			imap_close($mbox_stream, CL_EXPUNGE);
			
			imap_setacl ($this->_imap, "user" . $this->_profile['imapDelimiter'] . $uid, $this->_profile['imapAdminUsername'], '');
			
			if ($return_imap_delete)
			{
				$result['inbox_size'] = $inbox_size;
				
				$get_user_quota = @imap_get_quotaroot($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid);
				$result['mailquota_used'] = (string)(round(($get_user_quota['usage']/1024), 2));
			}
			else
			{
				$result['status'] = false;
				$result['msg'] = $this->functions->lang('It was not possible clean the users inbox') . ".\n" . $this->functions->lang('Server returns') . ': ' . imap_last_error();
			}
		}
		else
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('It was not possible to modify the users acl') . ".\n" . $this->functions->lang('Server returns') . ': ' . imap_last_error();
		}
		return $result;
	}
	
	function create_user_inbox($params, $isEdit = true)
	{
		$result = array('status' => true);
		
		if ($isEdit && !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS ))
		{
			$result['status'] = false;
			$result['msg'] = $this->functions->lang('You do not have access to create an user inbox');
			return $result;
		}
		
		$quota = ( isset($params['mailquota']) && $this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_QUOTA ) )?
			$params['mailquota'] : $this->_profile['defaultUserQuota'];
		
		$result = $this->create($params['uid'], $quota);
		$result['quota'] = $quota;
		return $result;
	}
	
	function get_delimiter($params)
	{
		$mode = isset($params['mail'])? 'mail' : (isset($params['id'])? 'id' : (isset($params['uid'])? 'uid' : null));
		$profile = $this->boemailadmin->getProfile($mode, is_null($mode)? null : $params[$mode]);
		return (string)(isset($profile['imapDelimiter'])? $profile['imapDelimiter'] : '');
	}
	
	function get_profile_info($params)
	{
		$email_bo = CreateObject('emailadmin.bo');
		$profile  = $email_bo->getProfile('mail', $params['mail']);
		if (!$profile) return false;
		return array(
			'profile_id'       => $profile['profileID'],
			'profile_descr'    => $profile['description'],
			'profile_delim'    => $profile['imapDelimiter'],
			'defaultUserQuota' => $profile['defaultUserQuota'],
		);
	}
	
	function move_mailbox($uid, $old_profile, $new_profile, $quota = null, $force_delete = false)
	{
		$status = false;
		$tag = 'move_mailbox: ';
		try {
			$async = CreateObject('phpgwapi.asyncservice');
			$async->log($tag.'init: '.$uid.' from: '.$old_profile['imapServer'].' to: '.$new_profile['imapServer']);
			
			$old_imap = new imap( $old_profile, $uid );
			if (!$old_imap->has_inbox()) throw new Exception('Inbox not exists in origin');
			
			$new_imap = new imap( $new_profile, $uid );
			
			$quota = is_null($quota)? $old_imap->get_quota() : $quota;
			$async->log($tag.'quota: '.$quota);
			
			// If the inbox already exists in the destination, choose delete or throw an exception
			if ( $new_imap->has_inbox() ) {
				if ( $force_delete ) $new_imap->delete_inbox();
				else throw new Exception('Inbox already exists in destiny');
			}
			
			foreach ($old_imap->list_folders() as $mailbox) {
				
				// Create new mailbox(folder) on destiny
				$async->log($tag.'create: '.implode('/',$mailbox));
				$new_imap->create_mailbox( $mailbox );
				
				// Set current mailbox in origin and destiny
				$async->log($tag.'sync folder in origin and destiny');
				if ( !($old_imap->set_mailbox($mailbox) && $new_imap->set_mailbox($mailbox)) )
					throw new Exception('Cannot open mailbox');
				
				// Copy mail one by one
				$num = $old_imap->get_num_msgs();
				$async->log($tag.'mail: '.$num.' copying messages');
				for ($i = 1; $i <= $num; $i++) {
					$stream = fopen('php://memory', "rw+");
					$opts = $old_imap->get_mail($i, $stream);
					$new_imap->put_mail($stream, $opts->flags, $opts->date );
					fclose($stream);
				}
			}
			
			if ( $quota !== false ){
				$async->log($tag.'set quota: '.$quota);
				$new_imap->set_quota( $quota );
			}
			
			$async->log($tag.'sieve: '.$uid);
			$result = $this->sieve_functions->move_scripts($uid, $old_profile, $new_profile);
			if ( !$result['status'] ) throw new Exception($result['msg']);
			
			try {
				$async->log($tag.'delete: '.$uid);
				$old_imap->delete_inbox();
			} catch(Exception $e) {
				$async->log($tag.'delete: error: '.$e->getMessage());
			}
			
			$status = true;
			
		} catch(Exception $e) {
			$async->log($tag.'exception: '.$e->getMessage());
		}
		
		$async->log($tag.'done: '.$uid.' ('.($status?'T':'F').')');
		return $status;
	}
}
