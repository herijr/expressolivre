<?php

include_once('class.ldap_functions.inc.php');

class imap_functions
{
	var $functions			= null;
	var $boemailadmin		= null;
	var $_uid				= false;
	var $_profile			= false;
	var $_imap				= false;
	
	function imap_functions()
	{
		$this->functions	= new functions;
		$this->boemailadmin	= CreateObject('emailadmin.bo');
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
		
		$this->_imap = imap_open('{'.$this->_profile['imapServer'].':'.$this->_profile['imapPort'].'/novalidate-cert}', $this->_profile['imapAdminUsername'], $this->_profile['imapAdminPW'], OP_HALFOPEN);
		
		return $this->profile;
	}
	
	function reset_profile( $uid )
	{
		if ( $uid == $this->_uid ) return $this->profile;
		
		return $this->set_profile( $this->boemailadmin->getProfile('uid', $uid), $uid );
	}
	
	function create($uid, $mailquota = 20)
	{
		$this->reset_profile($uid);
		
		$result = array('status' => true);
		
		if ($this->_profile === false) return array(
			'status'	=> false,
			'msg'		=> lang('Profile not found'),
		);
		
		if (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid))
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = 'Erro na funcao imap_function->create(INBOX): ' . $error[0];
			$result['error'] = $error[0];
			return $result;
		}
		if ( (!empty($this->_profile['imapDefaultSentFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultSentFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = 'Erro na funcao imap_function->create(Enviados): ' . $error[0];
			return $result;
		}
		if ( (!empty($this->_profile['imapDefaultDraftsFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultDraftsFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = 'Erro na funcao imap_function->create(Rascunho): ' . $error[0];
			return $result;
		}
		if ( (!empty($this->_profile['imapDefaultTrashFolder'])) && (!imap_createmailbox($this->_imap, '{'.$this->_profile['imapServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid . $this->_profile['imapDelimiter'] . $this->_profile['imapDefaultTrashFolder'])) )
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = 'Erro na funcao imap_function->create(Lixeira): ' . $error[0];
			return $result;
		}
		if (!imap_set_quota($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid, ($mailquota*1024)))
		{
			$error = imap_errors();
			$result['status'] = false;
			$result['msg'] = 'Erro na funcao imap_function->create(set_quota): ' . $error[0];
			return $result;
		}
		
		$result['status'] = true;
		return $result;
	}
	
	function get_user_info($uid)
	{
		$this->reset_perfil($uid);
		
		$get_quota = @imap_get_quotaroot($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid);
		
		if (count($get_quota) == 0)
		{
			$quota['mailquota'] = '-1';
			$quota['mailquota_used'] = '-1';
		}
		else
		{
			$quota['mailquota'] = ($get_quota['limit'] / 1024);
			$quota['mailquota_used'] = ($get_quota['usage'] / 1024);
		}
		
		return $quota;
	}
	
	function change_user_quota($uid, $quota)
	{
		$this->reset_perfil($uid);
		
		$set_quota = imap_set_quota($this->_imap,"user" . $this->_profile['imapDelimiter'] . $uid, ($quota*1024));
		return true;
	}
	
	function delete_user($uid)
	{
		$this->reset_perfil($uid);
		
		$result['status'] = true;
		
		//Seta acl imap para poder deletar o user.
		// Esta sem tratamento de erro, pois o retorno da funcao deve ter um bug.
		imap_setacl($this->_imap, "user" . $this->_profile['imapDelimiter'] . $uid, $this->_profile['imapAdminUsername'], 'c');
		
		if (!imap_deletemailbox($this->_imap, '{'.$this->_profile['imapServer'].'}' . "user" . $this->_profile['imapDelimiter'] . $uid))
		{
			$result['status'] = false;
			$result['msg'] = "Erro na funcao imap_function->delete_user.\nRetorno do servidor: " . imap_last_error();
		}
		
		return $result;
	}
	
	function rename_mailbox($old_mailbox, $new_mailbox, $profile)
	{
		$this->set_profile( $profile, $new_mailbox );
		
		$result['status'] = true;
		$result_rename = imap_renamemailbox($this->_imap,
			'{'.$this->_profile['imapServer'].':'.$this->_profile['imapPort'].'}user' . $this->_profile['imapDelimiter'] . $old_mailbox,
			'{'.$this->_profile['imapServer'].':'.$this->_profile['imapPort'].'}user' . $this->_profile['imapDelimiter'] . $new_mailbox);
		
		if (!$result_rename)
		{
			$result['status'] = false;
			$result['msg'] = "Erro na funcao imap_function->rename_mailbox.\nRetorno do servidor: " . imap_last_error();
		}
		return $result;
	}
}
