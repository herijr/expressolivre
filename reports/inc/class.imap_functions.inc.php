<?php
include_once('class.functions.inc.php');

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
		
		return $this->set_profile( $this->boemailadmin->getProfile('uid', $uid). $uid );
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
			$quota['mailquota'] = round (($get_quota['limit'] / 1024), 2);
			$quota['mailquota_used'] = round (($get_quota['usage'] / 1024), 2);
		}
		return $quota;
	}
	
	function getMembersShareAccount( $uid )
	{
		$this->reset_perfil($uid);
		
		$owner_user_share = imap_getacl($this->_imap, "user" . $this->_profile['imapDelimiter'] . $uid);
		
		//Organiza participantes da conta compartilha em um array, retira apenas os members, 
		$i =0;
		foreach($owner_user_share as $key => $value)
		{
			if ($i != 0)
			{
				$return[$i] = $key;
			}
			$i++;
		}
		
		//Ordena os participantes da conta compartilhada
		sort($return);
		
		return $return;
	}
	
}
