<?php
	/**********************************************************************************\
	* Expresso Administracao                 									      *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br) *
	* --------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it		  *
	*  under the terms of the GNU General Public License as published by the		  *
	*  Free Software Foundation; either version 2 of the License, or (at your		  *
	*  option) any later version.													  *
	\**********************************************************************************/
	
	
	include_once('class.ldap_functions.inc.php');
	include_once('class.db_functions.inc.php');
	include_once('class.imap_functions.inc.php');
	include_once('class.functions.inc.php');
	include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');
		
	class user
	{
		var $auth;
		var $ldap_functions;
		var $db_functions;
		var $imap_functions;
		var $functions;
		var $current_config;
		
		function user()
		{
			$this->ldap_functions = new ldap_functions;
			$this->db_functions = new db_functions;
			$this->imap_functions = new imap_functions;
			$this->functions = new functions;
			$this->current_config = $_SESSION['phpgw_info']['expresso']['expressoAdmin'];

			defined('PHPGW_API_INC') || define('PHPGW_API_INC','../phpgwapi/inc');
			include_once(PHPGW_API_INC.'/class.auth_egw.inc.php');
			$this->auth = new auth_egw();
		}
		
		function create($params)
		{
			$return['status'] = true;
			foreach ( array( 'givenname', 'sn', 'uid' ) as $key )
				if ( isset( $params[$key] ) )
					$params[$key] = trim( $params[$key] );
			
			// Verifica o acesso do gerente
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_ADD_USERS ))
			{
				$profile = CreateObject('emailadmin.bo')->getProfile('mail', $params['mail']);
				
				// Adiciona a organizacao na frente do uid.
				if ($this->current_config['expressoAdmin_prefix_org'] == 'true')
				{
					$context_dn = ldap_explode_dn(strtolower($GLOBALS['phpgw_info']['server']['ldap_context']), 1);
				
					$explode_dn = ldap_explode_dn(strtolower($params['context']), 1);
					$explode_dn = array_reverse($explode_dn);
					//$params['uid'] = $explode_dn[3] . '-' . $params['uid'];
					$params['uid'] = $explode_dn[$context_dn['count']] . '-' . $params['uid'];
				}
			
				// Leio o ID a ser usado na criacao do objecto. Esta funcao ja incrementa o ID no BD.
				$next_id = $this->db_functions->get_next_id();
				
				if ((!is_numeric($next_id['id'])) || (!$next_id['status']))
				{
					$return['status'] = false;
					$return['msg'] = $this->functions->lang('problems getting user id') . ".\n" . $next_id['msg'];
					return $return;
				}
				else
				{
					$id = $next_id['id'];
				}
			
				// Cria array para incluir no LDAP
				$dn = 'uid=' . $params['uid'] . ',' . $params['context'];		
			
				$user_info = array();
				$user_info['cn']						= $params['givenname'] . ' ' . $params['sn'];
				$user_info['gidNumber']					= $params['gidnumber'];
				$user_info['givenName']					= $params['givenname'];
				$user_info['homeDirectory']             = $params['sambahomedirectory'];
				$user_info['loginShell']                = $params['loginshell'];
				$user_info['mail']						= $params['mail'];
				$user_info['objectClass'][]				= 'posixAccount';
				$user_info['objectClass'][]				= 'inetOrgPerson';
				$user_info['objectClass'][]				= 'shadowAccount';
				$user_info['objectClass'][]				= 'qmailuser';
				$user_info['objectClass'][]				= 'phpgwAccount';
				$user_info['objectClass'][]				= 'top';
				$user_info['objectClass'][]				= 'person';
				$user_info['objectClass'][]				= 'organizationalPerson';
				$user_info['phpgwAccountExpires']		= '-1';
				$user_info['phpgwAccountType']			= 'u';
				$user_info['sn']						= $params['sn'];
				$user_info['uid']						= $params['uid'];
				$user_info['uidnumber']					= $id;
				//$user_info['userPassword']			= '{md5}' . base64_encode(pack("H*",md5($params['password1'])));
				$user_info['userPassword']				= $this->auth->encrypt_password($params['password1']);
				$user_info['phpgwLastPasswdChange']     = isset( $params['passwd_expired'] )? 0 : time();
				
				// Gerenciar senhas RFC2617
				if ($this->current_config['expressoAdmin_userPasswordRFC2617'] == 'true')
				{
					$realm		= $this->current_config['expressoAdmin_realm_userPasswordRFC2617'];
					$uid		= $user_info['uid'];
					$password	= $params['password1'];
					$user_info['userPasswordRFC2617'] = $realm . ':      ' . md5("$uid:$realm:$password");
				}
				
				if ($params['phpgwaccountstatus'] == '1')
					$user_info['phpgwAccountStatus'] = 'A';
			
				if ($params['departmentnumber'] != '')
					$user_info['departmentnumber']	= $params['departmentnumber'];
			
				if ($params['telephonenumber'] != '')
					$user_info['telephoneNumber']	= $params['telephonenumber'];
				
				//Ocultar da pesquisa e do catalogo
				if ($params['phpgwaccountvisible'])
					$user_info['phpgwAccountVisible'] = '-1';
				
				if ($profile)
				{
					if ($params['accountstatus'] == 1)
						$user_info['accountStatus'] = 'active';
					
					// Cria user_info no caso de ter alias e forwarding email.
					if( isset($params['mailalternateaddress']) ){
						foreach( $params['mailalternateaddress'] as $index => $mailalternateaddress)
						{
							if ($mailalternateaddress != '')
								$user_info['mailAlternateAddress'][] = $mailalternateaddress;
						}
					}
					
					if( isset($params['mailforwardingaddress']) ){
						foreach ($params['mailforwardingaddress'] as $index => $mailforwardingaddress)
						{
							if ($mailforwardingaddress != '')
								$user_info['mailForwardingAddress'][] = $mailforwardingaddress;
						}
					}
					
					if ($params['deliverymode'])
						$user_info['deliveryMode'] = 'forwardOnly';
					
				}

				// Suporte ao SAMBA
				if (($this->current_config['expressoAdmin_samba_support'] == 'true') && ($params['use_attrs_samba'] == 'on'))
				{
					
					// Qualquer um que crie um usuario, deve ter permissao para adicionar a senha samba.
					// Verifica o acesso do gerente aos atributos samba
					//if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES ))
					//{
						//Verifica se o binario para criar as senhas do samba exite.
						if (!is_file('/home/expressolivre/mkntpwd'))
						{
							$return['status'] = false;
							$return['msg'] .= 
									$this->functions->lang("the binary file /home/expressolivre/mkntpwd does not exist") . ".\\n" .
									$this->functions->lang("it is needed to create samba passwords") . ".\\n" . 
									$this->functions->lang("alert your administrator about this") . ".";
						}
						else
						{
							$user_info['objectClass'][]        = 'sambaSamAccount';
							$user_info['sambaSID']             = $params['sambadomain'] . '-' . ((2 * $id)+1000);
							$user_info['sambaPrimaryGroupSID'] = $params['sambadomain'] . '-' . ((2 * $user_info['gidNumber'])+1001);
							$user_info['sambaAcctFlags']       = $params['sambaacctflags'];
							$user_info['sambaLogonScript']     = $params['sambalogonscript'];
							$user_info['sambaLMPassword']      = exec('/home/expressolivre/mkntpwd -L "'.$params['password1'] . '"');
							$user_info['sambaNTPassword']      = exec('/home/expressolivre/mkntpwd -N "'.$params['password1'] . '"');
							$user_info['sambaPasswordHistory'] = '0000000000000000000000000000000000000000000000000000000000000000';
							$user_info['sambaPwdCanChange']    = strtotime("now");
							$user_info['sambaPwdLastSet']      = strtotime("now");
							$user_info['sambaPwdMustChange']   = '2147483647';
						}
					//}
				}
				
				// Verifica o acesso do gerente aos atributos corporativos
				if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_CORPORATIVE ))
				{
					//retira caracteres que nao sao numeros.
					$params['corporative_information_cpf'] = preg_replace("/[^0-9]/", "", $params['corporative_information_cpf']);
					//description
					$params['corporative_information_description'] = utf8_encode($params['corporative_information_description']);
					foreach ($params as $atribute=>$value)
					{
						$pos = strstr($atribute, 'corporative_information_');
						if ($pos !== false || $atribute == 'birthdate' )
						{
							if ($params[$atribute])
							{
								$ldap_atribute = str_replace("corporative_information_", "", $atribute);
								$user_info[$ldap_atribute] = $params[$atribute];
							}
						}
					}
				}

				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// RADIUS
				$radius = $this->functions->check_acl( $_SESSION[ 'phpgw_session' ][ 'session_lid' ], ACL_Managers::ACL_MOD_USERS_RADIUS );
				$radius_conf = $this->ldap_functions->getRadiusConf();
				if ( $radius && $radius_conf->enabled )
				{
					$radius_add = array();
					foreach( $params['radius_option_selected'] as $radiuskey )
						if (
							array_key_exists( $radiuskey, $radius_conf->profiles ) && !(
								isset($radius_conf->profiles[$radiuskey]['radiusGroupName']) &&
								!$this->functions->isMembership($radius_conf->profiles[$radiuskey]['radiusGroupName'])
							)
						)
							$radius_add[] = $radiuskey;
					
					if ( count($radius_add) ) {
						$user_info['objectClass'][] = $radius_conf->profileClass;
						$user_info[$radius_conf->groupname_attribute] = $radius_add;
					}
				}
				
				$result = $this->ldap_functions->ldap_add_entry($dn, $user_info);
				if ( !( isset( $result['status'] ) && $result['status'] ) )
				{
					$return['status'] = false;
					$return['msg'] .= $result['msg'];

					return $return;
				}
				
				// Chama funcao para salvar foto no OpenLDAP.
				if ( $_FILES['photo']['name'] != '' && $this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_PICTURE ) )
				{
					$size_conf = $this->current_config['expressoAdmin_photo_length'] == '' ? 10240 : $this->current_config['expressoAdmin_photo_length'];
					
					if( $_FILES['photo']['size'] > $size_conf )
					{
						$return['status'] = false;
						$return['msg']   .= $this->functions->lang('User photo could not be saved because is bigger than').' '.( $size_conf / 1024 ).' kb.';
					} else {
						$result = $this->ldap_functions->ldap_save_photo( $dn, $_FILES['photo']['tmp_name'] );
						if ( !$result['status'] ) {
							$return['status'] = false;
							$return['msg']   .= $result['msg'];
						}
					}
				}

				// API - Chama funcao para salvar foto no OpenLDAP.
				if( isset( $params['accountPhoto']) )
				{
					if ( $params['accountPhoto']['name'] != '' && $this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_PICTURE ) )
	  				{
	  					$size_conf = $this->current_config['expressoAdmin_photo_length'] == '' ? 10240 : $this->current_config['expressoAdmin_photo_length'];
		  
	  					if( $params['accountPhoto']['size'] > $size_conf )
	  					{
	  						$return['status'] = false;
	  						$return['msg'] .= $this->functions->lang('User photo could not be saved because is bigger than').' '.( $size_conf / 1024 ).' kb.';
	  					} else {
	  						$result = $this->ldap_functions->ldap_save_photo( $dn, array( $params['accountPhoto']['source'] ) );
	  						if ( !$result['status'] ) {
	  							$return['status'] = false;
	  							$return['msg'] .= $result['msg'];
	  						}
	  					}
	  				}
				}

				//GROUPS
				if ($params['groups'])
				{
					foreach ($params['groups'] as $gidnumber)
					{
						$result = $this->ldap_functions->add_user2group($gidnumber, $user_info['uid']);
						if (!$result['status'])
						{
							$return['status'] = false;
							$return['msg'] .= $result['msg'];
						}
						$result = $this->db_functions->add_user2group($gidnumber, $id);
						if (!$result['status'])
						{
							$return['status'] = false;
							$return['msg'] .= $result['msg'];
						}
					}
				}
			
				// Inclusao do Mail do usuario nas listas de email selecionadas.
				if ($params['maillists'])
				{
					foreach($params['maillists'] as $uid)
	            	{
						$result = $this->ldap_functions->add_user2maillist($uid, $user_info['mail']);
						if (!$result['status'])
						{
							$return['status'] = false;
							$return['msg'] .= $result['msg'];
						}
	            	}
				}
                       
				// APPS
				if (count($params['apps']))
				{
					$result = $this->db_functions->add_id2apps($id, $params['apps']);
					if (!$result['status'])
					{
						$return['status'] = false;
						$return['msg'] .= $result['msg'];
					}
				}

				// Chama funcao para incluir no pgsql as preferencia de alterar senha.
				if ($params['changepassword'])
				{
					$result = $this->db_functions->add_pref_changepassword($id);
					if (!$result['status'])
					{
						$return['status'] = false;
						$return['msg'] .= $result['msg'];
					}
				}
				
				if ($profile)
				{
					// Strength profile in imap, ldap replication delay may cause an error profile not found
					$this->imap_functions->set_profile($profile, $params['uid']);
					
					// Chama funcao para criar mailbox do usuario, no imap-cyrus.
					$result = $this->imap_functions->create_user_inbox($params, false);
					if (!$result['status'])
					{
						$return['status'] = false;
						$return['msg'] .= $result['msg'];
					}
				}
				$this->db_functions->write_log("created user",$dn);
				
				require_once( PHPGW_API_INC . '/class.eventws.inc.php' );
				EventWS::getInstance()->send( 'user_created', $dn, array( 'passwd' => $params['password1'] ) );
				
				if ( is_string( $result ) ) {
					$return['status'] = true;
					$return['msg']   .= $this->functions->lang( $result );
				}
				////////////////////////////////////////////////////////////////////////////////////////////////////////
			}

			return $return;
		}
		
		function save($new_values)
		{
			$create_user_inbox = false;
			$mbox_migrate = false;
			$has_change = array();
			
			$return = array('status' => true);
			$return['msg'] = '';
			
			foreach ( array( 'givenname', 'sn', 'uid' ) as $key )
				if ( isset( $new_values[$key] ) )
					$new_values[$key] = trim( $new_values[$key] );
			
			if ( !($old_values = $this->get_user_info($new_values['uidnumber']) ) ) {
				$return['status'] = false;
				$return['msg'] = $this->functions->lang('You do not have access to edit user informations') . '.';
				return $return;
			}
			
			$dn = 'uid=' . $old_values['uid'] . ',' . strtolower($old_values['context']);

			//retira caracteres que nao sao numeros.
			$new_values['corporative_information_cpf'] = preg_replace("/[^0-9]/", "", $new_values['corporative_information_cpf']);

			$diff = array_diff($new_values, $old_values);
			
			$manager_account_lid = $_SESSION['phpgw_session']['session_lid'];
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) &&
				(!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PASSWORD )) &&
				(!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )) &&
				(!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_CORPORATIVE )) &&
				(!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PHONE_NUMBER ))
				)
			{
				$return['status'] = false;
				$return['msg'] = $this->functions->lang('You do not have access to edit user informations') . '.';
				return $return;
			}

			// Check manager access
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS ))
			{
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Change user organization
				if (isset($diff['context']) && $diff['context'])
				{
					if (strcasecmp($old_values['context'], $new_values['context']) != 0)
					{
						$newrdn = 'uid=' . $old_values['uid'];
						$newparent = $new_values['context'];
						$result =  $this->ldap_functions->change_user_context($dn, $newrdn, $newparent);
						if (!$result['status'])
						{
							$return['status'] = false;
							$return['msg'] .= $result['msg'];
						}
						else
						{
							$has_change['old_dn'] = $dn;
							$dn = $newrdn . ',' . $newparent;
							$this->db_functions->write_log('modified user context', $dn . ': ' . $old_values['uid'] . '->' . $new_values['context']);
						}
					}
				}
			
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// REPLACE some attributes
				if (isset($diff['givenname']) && $diff['givenname'])
				{
					$ldap_mod_replace['givenname'] = $new_values['givenname'];
					$ldap_mod_replace['cn'] = $new_values['givenname'] . ' ' . $new_values['sn'];
					$this->db_functions->write_log("modified first name", "$dn: " . $old_values['givenname'] . "->" . $new_values['givenname']);
				}
				if (isset($diff['sn']) && $diff['sn'])
				{
					$ldap_mod_replace['sn'] = $new_values['sn'];
					$ldap_mod_replace['cn'] = $new_values['givenname'] . ' ' . $new_values['sn'];
					$this->db_functions->write_log("modified last name", "$dn: " . $old_values['sn'] . "->" . $new_values['sn']);
				}
				if (isset($diff['sambahomedirectory']) && $diff['sambahomedirectory'])
				{
					$ldap_mod_replace['homedirectory'] = $new_values['sambahomedirectory'];
					$this->db_functions->write_log("modified user homedirectory",$dn);
				}
				if (isset($diff['loginshell']) && $diff['loginshell'])
				{
					if ( isset( $old_values['loginshell'] ) ) $ldap_mod_replace['loginshell'] = $new_values['loginshell'];
					else $ldap_add['loginshell'] = $new_values['loginshell'];
					$this->db_functions->write_log("modified user loginshell",$dn);
				}
				
				if (isset($diff['mail']) && $diff['mail'])
				{
					$emailadminbo = CreateObject('emailadmin.bo');
					$newProfile = $emailadminbo->getProfile('mail', $new_values['mail']);
					if ( $newProfile === false ) {
						
						// Remove mx parameter from ldap
						$fields = array('accountstatus','mailalternateaddress','mailforwardingaddress','deliverymode','mailquota');
						foreach ($fields as $value) if(isset($new_values[$fields])) unset($new_values[$fields]);
						$diff = array_diff($new_values, $old_values);
						
						// Try delete old profile mailbox
						$result = $this->imap_functions->delete_user($old_values['uid']);
						$this->db_functions->write_log(($result['status']?'deleted':'error delete')." user data from IMAP", $old_values['uid']);
						
					} else {
						
						$oldProfile = $emailadminbo->getProfile('mail', $old_values['mail']);
						
						if ( !$oldProfile ) {
							
							$create_user_inbox = true;
							
						} else if ( $newProfile['profileID'] != $oldProfile['profileID'] ) {
							
							$result = $this->db_functions->addMBoxMigrate( array(array(
								"profileid_orig"	=> $oldProfile['profileID'],
								"profileid_dest"	=> $newProfile['profileID'],
								"uid"				=> $old_values['uid'],
								"data"				=> serialize(array(
									"mailquota"			=> $new_values['mailquota'],
									"mailquota_used"	=> $new_values['mailquota_used'],
								)),
							)));
							
							if (!$result) {
								$return['status'] = false;
								$return['msg'] .= $this->functions->lang('Asynchronous service unavailable');
								return $return;
							}
						}
					}
					
					$ldap_mod_replace['mail'] = $new_values['mail'];
					$this->ldap_functions->replace_user2maillists($new_values['mail'], $old_values['mail']);
					$this->ldap_functions->replace_mail_from_institutional_account($new_values['mail'], $old_values['mail']);
					$this->db_functions->write_log("modified user email", "$dn: " . $old_values['mail'] . "->" . $new_values['mail']);
				}
			}
			
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) || 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_PASSWORD )) )
			{
				if (isset($diff['password1']) && $diff['password1'])
				{
					//$ldap_mod_replace['userPassword'] = '{md5}' . base64_encode(pack("H*",md5($new_values['password1'])));
					$ldap_mod_replace['userPassword'] = $this->auth->encrypt_password($new_values['password1']);
					
					// Suporte ao SAMBA
					if (($this->current_config['expressoAdmin_samba_support'] == 'true') && ($new_values['userSamba']) && ($new_values['use_attrs_samba'] == 'on'))
					{
						$ldap_mod_replace['sambaLMPassword'] = exec('/home/expressolivre/mkntpwd -L "'.$new_values['password1'] . '"');
						$ldap_mod_replace['sambaNTPassword'] = exec('/home/expressolivre/mkntpwd -N "'.$new_values['password1'] . '"');
					}
					
					// Gerenciar senhas RFC2617
					if ($this->current_config['expressoAdmin_userPasswordRFC2617'] == 'true')
					{
						$realm		= $this->current_config['expressoAdmin_realm_userPasswordRFC2617'];
						$uid		= $new_values['uid'];
						$password	= $new_values['password1'];
						$passUserRFC2617 = $realm . ':      ' . md5("$uid:$realm:$password");
						
						if ($old_values['userPasswordRFC2617'] != '')
							$ldap_mod_replace['userPasswordRFC2617'] = $passUserRFC2617;
						else
							$ldap_add['userPasswordRFC2617'] = $passUserRFC2617;
					}
					
					$ldap_mod_replace['phpgwlastpasswdchange'] = time();
					$this->db_functions->write_log("modified user password",$dn);
					
					$GLOBALS['hook_values']['uid']        = $old_values['uid'];
					$GLOBALS['hook_values']['account_id'] = $old_values['uidnumber'];
					$GLOBALS['hook_values']['new_passwd'] = $new_values['password1'];
					$GLOBALS['phpgw']->hooks->process('changepassword');
					$has_change['passwd'] = $new_values['password1'];
				}
				
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Add Expiration
				if ( isset( $new_values['passwd_expired'] ) ) {
					
					//Force permission to change password
					$new_values['changepassword'] = 1;
					
					if ( $old_values['passwd_expired'] > 0 ) {
						$ldap_mod_replace['phpgwlastpasswdchange'] = 0;
						$this->db_functions->write_log( 'expired user password', $dn );
					}
					
				} else if ( $old_values['passwd_expired'] === 0 ) {
					$ldap_mod_replace['phpgwlastpasswdchange'] = time();
					$this->db_functions->write_log( 'removed expiry from user password', $dn );
				}
			}
			
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) || 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_PHONE_NUMBER )) )
			{
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// TELEPHONE

				if ((isset($diff['telephonenumber']) && $diff['telephonenumber']) && ($old_values['telephonenumber'] != ''))
				{
					$ldap_mod_replace['telephonenumber'] = $new_values['telephonenumber'];
					$this->db_functions->write_log('modified user telephonenumber', $dn . ': ' . $old_values['telephonenumber'] . '->' . $new_values['telephonenumber']);
				}
				if (($old_values['telephonenumber'] == '') && ($new_values['telephonenumber'] != ''))
				{
					$ldap_add['telephonenumber'] = $new_values['telephonenumber'];
					$this->db_functions->write_log("added user phone",$dn);
				}
				if (($old_values['telephonenumber'] != '') && ($new_values['telephonenumber'] == ''))
				{
					$ldap_remove['telephonenumber'] = array();
					$this->db_functions->write_log("removed user phone",$dn);
				}

				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// SIP

				/*
				if (($diff['sipnumber']) && ($old_values['sipnumber'] != ''))
				{
					$ldap_mod_replace['sipnumber'] = $new_values['sipnumber'];
					$this->db_functions->write_log('modified user SIP', $dn . ': ' . $old_values['sipnumber'] . '->' . $new_values['sipnumber']);
				}
				if (($old_values['sipnumber'] == '') && ($new_values['sipnumber'] != ''))
				{
					$ldap_add['sipnumber'] = $new_values['sipnumber'];
					$this->db_functions->write_log("added user SIP",$dn);
				}
				if (($old_values['sipnumber'] != '') && ($new_values['sipnumber'] == ''))
				{
					$ldap_remove['sipnumber'] = array();
					$this->db_functions->write_log("removed user SIP",$dn);
				}
				*/

			}
			
			// REPLACE, ADD & REMOVE COPORATIVEs ATRIBUTES
			// Verifica o acesso do gerente aos atributos corporativos
			
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) || 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_CORPORATIVE )) )
			{

				// CENTRAL DE SEGURANCA
				// Verifica se o campo e protegido 
				$fields_protected = array();

				$fields_protected = explode( ",", $new_values['protected_fields'] );

				foreach ( $new_values as $atribute => $value )
				{
					if( in_array( $atribute , $fields_protected ) )
					{
						$this->db_functions->setProtectedField( $new_values['uidnumber'], $atribute, utf8_encode($new_values[$atribute]) );						
					}

					$pos = strstr($atribute, 'corporative_information_');
					if ($pos !== false || $atribute == 'birthdate' )
					{
						$ldap_atribute = trim(str_replace("corporative_information_", "", $atribute));

						// REPLACE CORPORATIVE ATTRIBUTES
						if( isset($diff[$atribute]) && $diff[$atribute] && ($old_values[$atribute] != '') )
						{
							$is_replace = false;

							if( $this->db_functions->getProtectedFields( $new_values['uidnumber'], $atribute ) )
							{
								if( in_array( $atribute , $fields_protected ) )
								{
									$is_replace = true;
								}
								else
								{
									$is_replace = false;
								}
							}
							else
							{
								$is_replace = true;
							}

							if( $is_replace )
							{
								$ldap_mod_replace[$ldap_atribute] = utf8_encode($new_values[$atribute]);
								$this->db_functions->write_log('modified user attribute', $dn . ': ' . $ldap_atribute . ': ' . $old_values[$atribute] . '->' . $new_values[$atribute]);
							}
						}
						//ADD CORPORATIVE ATTRIBUTES
						elseif (($old_values[$atribute] == '') && ($new_values[$atribute] != ''))
						{
							$ldap_add[$ldap_atribute] = utf8_encode($new_values[$atribute]);
							$this->db_functions->write_log('added user attribute', $dn . ': ' . $ldap_atribute . ': ' . $old_values[$atribute] . '->' . $new_values[$atribute]);
						}
						//REMOVE CORPORATIVE ATTRIBUTES
						elseif (($old_values[$atribute] != '') && ($new_values[$atribute] == ''))
						{
							if( !$this->db_functions->getProtectedFields( $new_values['uidnumber'], $atribute ) )
							{
								$ldap_remove[$ldap_atribute] = array();
								$this->db_functions->write_log('removed user attribute', $dn . ': ' . $ldap_atribute . ': ' . $old_values[$atribute] . '->' . $new_values[$atribute]);
							}
						}
					}
				}
			}
			
			//Suporte ao SAMBA
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) || 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )) )
			{
				
				if (isset($diff['gidnumber']) && $diff['gidnumber'])
				{
					$ldap_mod_replace['gidnumber'] = $new_values['gidnumber'];
					$this->db_functions->write_log('modified user primary group', $dn . ': ' . $old_values['gidnumber'] . '->' . $new_values['gidnumber']);
				}
				
				if (($this->current_config['expressoAdmin_samba_support'] == 'true'))
				{
					if ( ($new_values['userSamba']) && $new_values['use_attrs_samba'] == 'on' )
					{
						if ($diff['gidnumber'])
						{
							$ldap_mod_replace['sambaPrimaryGroupSID']	= $this->current_config['expressoAdmin_sambaSID'] . '-' . ((2 * $new_values['gidnumber'])+1001);
							$this->db_functions->write_log('modified user sambaPrimaryGroupSID', $dn);
						}
						
						if ($diff['sambaacctflags'])
						{
							$ldap_mod_replace['sambaacctflags'] = $new_values['sambaacctflags'];
							$this->db_functions->write_log("modified user sambaacctflags",$dn);
						}
						if ($diff['sambalogonscript'])
						{
							$ldap_mod_replace['sambalogonscript'] = $new_values['sambalogonscript'];
							$this->db_functions->write_log("modified user sambalogonscript",$dn);
						}
						if ($diff['sambadomain'])
						{
							$ldap_mod_replace['sambaSID']				= $diff['sambadomain'] . '-' . ((2 * $old_values['uidnumber'])+1000);
							$ldap_mod_replace['sambaPrimaryGroupSID']	= $diff['sambadomain'] . '-' . ((2 * $old_values['gidnumber'])+1001);
							$this->db_functions->write_log('modified user samba domain', $dn . ': ' . $old_values['sambadomain'] . '->' . $new_values['sambadomain']);
						}
					}
					if (isset($diff['sambahomedirectory']) && $diff['sambahomedirectory'])
					{
						$ldap_mod_replace['homedirectory'] = $new_values['sambahomedirectory'];
						$this->db_functions->write_log("modified user homedirectory",$dn);
					}
					if (isset($diff['loginshell']) && $diff['loginshell'])
					{
						$ldap_mod_replace['loginshell'] = $new_values['loginshell'];
						$this->db_functions->write_log("modified user loginshell",$dn);
					}
				}
			}
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// ADD or REMOVE some attributes
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// PHOTO
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_PICTURE ))
			{
				$size_conf = $this->current_config['expressoAdmin_photo_length'] == '' ? 10240 : $this->current_config['expressoAdmin_photo_length'];
        
				if (isset($new_values['delete_photo']) && $new_values['delete_photo'])
				{
					if( $this->ldap_functions->ldap_remove_photo($dn) ){
						$this->db_functions->write_log( "removed user photo", $dn );
						$has_change['photo_removed'] = true;
					}
				}
				elseif($_FILES['photo']['name'] != '')
				{
					if( $_FILES['photo']['size'] > $size_conf )
					{
						$return['status'] = false;
						$return['msg']   .= $this->functions->lang('User photo could not be saved because is bigger than').' '.( $size_conf / 1024 ).' kb.';
					}
					else
					{
						if ($new_values['photo_exist'])
						{
							$photo_exist = true;
							$this->db_functions->write_log("mofified user photo",$dn);
						}
						else
						{
							$photo_exist = false;
							$this->db_functions->write_log("added user photo",$dn);
						}
						$this->ldap_functions->ldap_save_photo($dn, $_FILES['photo']['tmp_name'], $new_values['photo_exist']);
						$has_change['photo_added'] = true;
					}
				}
				else if( isset($new_values['accountPhoto']) )	
				{
					if( $new_values['accountPhoto']['size'] > $size_conf )
					{
						$return['status'] = false;
						$return['msg']   .= $this->functions->lang('User photo could not be saved because is bigger than').' '.( $size_conf / 1024 ).' kb.';
					}
					else
					{
						if( $new_values['accountPhoto']['photo_exist'] )
						{
							$photo_exist = true;
							$this->db_functions->write_log("mofified user photo",$dn);
						}
						else
						{
							$photo_exist = false;
							$this->db_functions->write_log("added user photo",$dn);
						}
						$this->ldap_functions->ldap_save_photo( $dn, array( $new_values['accountPhoto']['source'] ), $photo_exist );
						$has_change['photo_added'] = true;
					}
				}
			}
			
			// Verifica o acesso para adicionar ou remover tais atributos
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS ))
			{
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// PREF_CHANGEPASSWORD
				if ( isset($new_values['changepassword']) ) {
					if( ( !isset($old_values['changepassword']) ) || trim($old_values['changepassword']) == "" ){				
						$this->db_functions->add_pref_changepassword($new_values['uidnumber']);
						$this->db_functions->write_log("turn on changepassword",$dn);
					}
				}
				else
				{
					if( isset($old_values['changepassword']) && trim($old_values['changepassword']) != "" ){	
						$this->db_functions->remove_pref_changepassword($new_values['uidnumber']);
						$this->db_functions->write_log("turn off changepassword",$dn);
					}
				}

				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// ACCOUNT STATUS
				if ( isset($new_values['phpgwaccountstatus']) ) {
					if( ( !isset($old_values['phpgwaccountstatus']) ) || trim($old_values['phpgwaccountstatus']) == "" ){				
						$ldap_add['phpgwaccountstatus'] = 'A';
						$this->db_functions->write_log("turn on user account",$dn);
					}
				}
				else
				{
					if( isset($old_values['phpgwaccountstatus']) && trim($old_values['phpgwaccountstatus']) != "" ){	
						$ldap_remove['phpgwaccountstatus'] = array();						
						$this->db_functions->write_log("turn off user account",$dn);
					}
				}

				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// ACCOUNT VISIBLE
				if ( isset($new_values['phpgwaccountvisible']) ) {
					if( ( !isset($old_values['phpgwaccountvisible']) ) || trim($old_values['phpgwaccountvisible']) == "" ){				
						$ldap_add['phpgwaccountvisible'] = '-1';
						$this->db_functions->write_log("turn on phpgwaccountvisible",$dn);
					}
				}
				else
				{
					if( isset($old_values['phpgwaccountvisible']) && trim($old_values['phpgwaccountvisible']) != "" ){	
						$ldap_remove['phpgwaccountvisible'] = array();
						$this->db_functions->write_log("turn off phpgwaccountvisible",$dn);
					}
				}

				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Mail Account STATUS
				if ( isset($new_values['accountstatus']) ) {
					if( ( !isset($old_values['accountstatus']) ) || trim($old_values['accountstatus']) == "" ){				
						$ldap_add['accountstatus'] = 'active';
						$this->db_functions->write_log("turn on user account email",$dn);
					}
				}
				else
				{
					if( isset($old_values['accountstatus']) && trim($old_values['accountstatus']) != "" ){	
						$ldap_remove['accountstatus'] = array();
						$this->db_functions->write_log("turn off user account email",$dn);
					}
				}				
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// MAILALTERNATEADDRESS
				if (!(isset($new_values['mailalternateaddress']) && is_array($new_values['mailalternateaddress'])))
					$new_values['mailalternateaddress'] = array();
				if (!(isset($old_values['mailalternateaddress']) && is_array($old_values['mailalternateaddress'])))
					$old_values['mailalternateaddress'] = array();
				
				$add_mailalternateaddress = array_diff($new_values['mailalternateaddress'], $old_values['mailalternateaddress']);
				$remove_mailalternateaddress = array_diff($old_values['mailalternateaddress'], $new_values['mailalternateaddress']);
				foreach ($add_mailalternateaddress as $index=>$mailalternateaddress)
				{
					if ($mailalternateaddress != '')
					{
						$ldap_add['mailalternateaddress'][] = $mailalternateaddress;
						$this->db_functions->write_log("added mailalternateaddress","$dn: $mailalternateaddress");
					}
				}
				foreach ($remove_mailalternateaddress as $index=>$mailalternateaddress)
				{
					if ($mailalternateaddress != '')
					{
						if ($index !== 'count')
						{
							$ldap_remove['mailalternateaddress'][] = $mailalternateaddress;
							$this->db_functions->write_log("removed mailalternateaddress","$dn: $mailalternateaddress");
						}
					}
				}
				
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// MAILFORWARDINGADDRESS
				if (!(isset($new_values['mailforwardingaddress']) && is_array($new_values['mailforwardingaddress'])))
					$new_values['mailforwardingaddress'] = array();
				if (!(isset($old_values['mailforwardingaddress']) && is_array($old_values['mailforwardingaddress'])))
					$old_values['mailforwardingaddress'] = array();
				
				$add_mailforwardingaddress = array_diff($new_values['mailforwardingaddress'], $old_values['mailforwardingaddress']);
				$remove_mailforwardingaddress = array_diff($old_values['mailforwardingaddress'], $new_values['mailforwardingaddress']);
				foreach ($add_mailforwardingaddress as $index=>$mailforwardingaddress)
				{
					if ($mailforwardingaddress != '')
					{
						$ldap_add['mailforwardingaddress'][] = $mailforwardingaddress;
						$this->db_functions->write_log("added mailforwardingaddress","$dn: $mailforwardingaddress");
					}
				}
				foreach ($remove_mailforwardingaddress as $index=>$mailforwardingaddress)
				{
					if ($mailforwardingaddress != '')
					{
						if ($index !== 'count')
						{
							$ldap_remove['mailforwardingaddress'][] = $mailforwardingaddress;
							$this->db_functions->write_log("removed mailforwardingaddress","$dn: $mailforwardingaddress");
						}
					}
				}
				
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Delivery Mode
				if ( isset($new_values['deliverymode']) ) {
					if( ( !isset($old_values['deliverymode']) ) || trim($old_values['deliverymode']) == "" ){				
						$ldap_add['deliverymode'] = 'forwardOnly';
						$this->db_functions->write_log("added forwardOnly", $dn);
					}
				}
				else
				{
					if( isset($old_values['deliverymode']) && trim($old_values['deliverymode']) != "" ){	
						$ldap_remove['deliverymode'] = array();
						$this->db_functions->write_log("removed forwardOnly", $dn);
					}
				}
			}
			
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) && 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )) )
			{
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// REMOVE ATTRS OF SAMBA
				if (($this->current_config['expressoAdmin_samba_support'] == 'true') && ($new_values['userSamba']) && ($new_values['use_attrs_samba'] != 'on'))
				{
					$ldap_remove['objectclass'] 			= 'sambaSamAccount';	
					$ldap_remove['sambaSID']				= array();
					$ldap_remove['sambaPrimaryGroupSID']	= array();
					$ldap_remove['sambaAcctFlags']			= array();
					$ldap_remove['sambaLogonScript']		= array();
					$ldap_remove['sambaLMPassword']			= array();
					$ldap_remove['sambaNTPassword']			= array();
					$ldap_remove['sambaPasswordHistory']	= array();
					$ldap_remove['sambaPwdCanChange']		= array();
					$ldap_remove['sambaPwdLastSet']			= array();
					$ldap_remove['sambaPwdMustChange']		= array();
					$has_change['old_sambaSID']             = $old_values['sambasid'].'-'.( ( 2 * $old_values['uidnumber'] ) + 1000 );
					$this->db_functions->write_log("removed user samba attributes", $dn);
				}
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// ADD ATTRS OF SAMBA
				if (($this->current_config['expressoAdmin_samba_support'] == 'true') && (!$new_values['userSamba']) && (isset($new_values['use_attrs_samba']) && $new_values['use_attrs_samba'] == 'on'))
				{
					if (!is_file('/home/expressolivre/mkntpwd'))
					{
						$return['status'] = false;
						$return['msg'] .= $this->functions->lang("The file /home/expressolivre/mkntpwd does not exist") . ".\n";
						$return['msg'] .= $this->functions->lang("It is necessery to create samba passwords") . ".\n";
						$return['msg'] .= $this->functions->lang("Inform your system administrator about this") . ".\n";
					}
					else
					{
						$ldap_add['objectClass'][] 			= 'sambaSamAccount';
						$ldap_add['sambaSID']				= $new_values['sambadomain'] . '-' . ((2 * $new_values['uidnumber'])+1000);
						$ldap_add['sambaPrimaryGroupSID']	= $new_values['sambadomain'] . '-' . ((2 * $new_values['gidnumber'])+1001);
						$ldap_add['sambaAcctFlags']			= $new_values['sambaacctflags'];
						$ldap_add['sambaLogonScript']		= $new_values['sambalogonscript'];
						$ldap_add['sambaLMPassword']		= exec('/home/expressolivre/mkntpwd -L '.'senha');
						$ldap_add['sambaNTPassword']		= exec('/home/expressolivre/mkntpwd -N '.'senha');
						$ldap_add['sambaPasswordHistory']	= '0000000000000000000000000000000000000000000000000000000000000000';
						$ldap_add['sambaPwdCanChange']		= strtotime("now");
						$ldap_add['sambaPwdLastSet']		= strtotime("now");
						$ldap_add['sambaPwdMustChange']	= '2147483647';
						$this->db_functions->write_log("added user samba attribute", $dn);
					}
				}
			}

			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// RADIUS
			$radius = $this->functions->check_acl( $_SESSION[ 'phpgw_session' ][ 'session_lid' ], ACL_Managers::ACL_MOD_USERS_RADIUS );
			$radius_conf = $this->ldap_functions->getRadiusConf();
			if ( $radius  && $radius_conf->enabled )
			{
				foreach ( $radius_conf->profiles as $radiuskey => $value )
				{
					if ( isset($value['radiusGroupName']) && isset( $new_values['radius_option_selected'] ) && !$this->functions->isMembership($value['radiusGroupName']) )
					{
						$key = array_search( $radiuskey, $new_values['radius_option_selected'] );
						if ( in_array( $radiuskey, $old_values[$radius_conf->groupname_attribute] ) )
						{
							if ( $key === false ) $new_values['radius_option_selected'][] = $radiuskey;
						} else {
							if ( $key !== false ) unset($new_values['radius_option_selected'][$key]);
						}
					}
				}
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// ADD / MOD
				if ( ! empty( $new_values['radius_option_selected'] ) )
				{
					foreach( $new_values['radius_option_selected'] as $radiuskey )
					{
						if ( array_key_exists( $radiuskey, $radius_conf->profiles ) )
						{
							if ( ! isset( $old_values[$radius_conf->profileClass] ) )
							{
								if ( !( is_array($ldap_add[ 'objectClass' ]) && array_search($radius_conf->profileClass, $ldap_add[ 'objectClass' ]) !== false ) )
									$ldap_add[ 'objectClass' ][ ] = $radius_conf->profileClass;
								
								$ldap_add = array_merge_recursive( $ldap_add, array( $radius_conf->groupname_attribute => $radiuskey ) );
								
								$this->db_functions->write_log( "radius attributes was added", $dn );
							}
							else if( !in_array( $radiuskey, $old_values[$radius_conf->groupname_attribute] ) )
							{
								$ldap_mod_replace = array_merge_recursive( $ldap_mod_replace, array( $radius_conf->groupname_attribute => $radiuskey ) );
								
								$this->db_functions->write_log( "radius attributes was modified", $dn );
							}
						}
					}
				}
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// REMOVE
				if ( empty( $new_values['radius_option_selected'] ) && array_key_exists( $radius_conf->profileClass, $old_values ) )
				{
					$ldap_remove[ $radius_conf->groupname_attribute ] = array();
					$ldap_remove[ 'objectclass' ] = $radius_conf->profileClass;
					
					$this->db_functions->write_log( "radius' attributes was removed", $dn);
				}
			}
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// GROUPS
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_GROUPS )) 
			{
				// If the manager does not have the suficient access, the new_values.uid is empty. 
				if (empty($new_values['uid']))
					$user_uid = $old_values['uid'];
				else
					$user_uid = $new_values['uid'];
				
				if (!(isset($new_values['groups']) && is_array($new_values['groups'])))
					$new_values['groups'] = array();
				if (!(isset($old_values['groups']) && is_array($old_values['groups'])))
					$old_values['groups'] = array();
			
				$add_groups = array_diff($new_values['groups'], $old_values['groups']);
				$remove_groups = array_diff($old_values['groups'], $new_values['groups']);
			
				if (count($add_groups)>0)
				{
					$has_change['user_group_in'] = array();
					foreach($add_groups as $gidnumber)
					{
						$this->db_functions->add_user2group($gidnumber, $new_values['uidnumber']);
						$add_user2group_result = $this->ldap_functions->add_user2group($gidnumber, $user_uid);
						if ($add_user2group_result['status']) {
							$this->db_functions->write_log("included user to group", "user_uid:$user_uid -> group_dn:" . $add_user2group_result['group_dn']);
							$has_change['user_group_in'][] = $add_user2group_result['group_dn'];
						} else
							$this->db_functions->write_log("Fail to include user to group", $add_user2group_result['msg']);
					}
					if ( !count( $has_change['user_group_in'] ) ) unset( $has_change['user_group_in'] );
				}
				
				if (count($remove_groups)>0)
				{
					$has_change['user_group_out'] = array();
					foreach($remove_groups as $gidnumber)
					{
						foreach($old_values['groups_info'] as $group)
						{
							if (($group['gidnumber'] == $gidnumber) && ($group['group_disabled'] == 'false'))
							{
								$this->db_functions->remove_user2group($gidnumber, $new_values['uidnumber']);
								$remove_user2group_result = $this->ldap_functions->remove_user2group($gidnumber, $user_uid);
								if ($remove_user2group_result['status']) {
									$this->db_functions->write_log("removed user from group", "user_uid:$user_uid -> group_dn:" . $remove_user2group_result['group_dn']);
									$has_change['user_group_out'][] = $remove_user2group_result['group_dn'];
								} else
									$this->db_functions->write_log("Fail to remove user from group", $remove_user2group_result['msg']);
							}
						}
					}
					if ( !count( $has_change['user_group_out'] ) ) unset( $has_change['user_group_out'] );
				}
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// LDAP_MOD_REPLACE
			if (isset($ldap_mod_replace) && count($ldap_mod_replace))
			{
				$result = $this->ldap_functions->replace_user_attributes($dn, $ldap_mod_replace);
				$has_change['ldap_mod_replace'] = $ldap_mod_replace;
				if (!$result['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result['msg'];
				}
			}
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// LDAP_MOD_ADD
			if (isset($ldap_add) && count($ldap_add))
			{
				$has_change['ldap_add'] = $ldap_add;
				$result = $this->ldap_functions->add_user_attributes($dn, $ldap_add);
				if (!$result['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result['msg'];
				}
			}
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// LDAP_MOD_REMOVE			
			if (isset($ldap_remove) && count($ldap_remove))
			{
				$has_change['ldap_remove'] = $ldap_remove;
				$result = $this->ldap_functions->remove_user_attributes($dn, $ldap_remove);
				if (!$result['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result['msg'];
				}
			}
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Create new inbox
			if ($create_user_inbox) {
				$this->imap_functions->set_profile( $newProfile, $old_values['uid'] );
				$result = $this->imap_functions->create_user_inbox($new_values);
				if (!$result['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result['msg'];
				}
			}
			
			if ( ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) && 
			     ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS_QUOTA )) &&
			     (!$mbox_migrate) )
			{
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// MAILQUOTA
				if ( ($new_values['mailquota'] != $old_values['mailquota']) && (is_numeric($new_values['mailquota'])) )
				{
					$result = $this->imap_functions->change_user_quota($new_values['uid'], $new_values['mailquota']);
					
					if ($result['status'])
					{
						$this->db_functions->write_log("modified user email quota" , $dn . ':' . $old_values['mailquota'] . '->' . $new_values['mailquota']);
					}
					else
					{
						$return['status'] = false;
						$return['msg'] .= $result['msg'];
					}
				}
			}
			
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_USERS )) 
			{
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// MAILLISTS
				if (!(isset($new_values['maillists']) && is_array($new_values['maillists'])))
					$new_values['maillists'] = array();
				if (!(isset($old_values['maillists']) && is_array($old_values['maillists'])))
					$old_values['maillists'] = array();

				$add_maillists = array_diff($new_values['maillists'], $old_values['maillists']);
				$remove_maillists = array_diff($old_values['maillists'], $new_values['maillists']);
				
				if (count($add_maillists)>0)
				{
					foreach($add_maillists as $uid)
					{
						$this->ldap_functions->add_user2maillist($uid, $new_values['mail']);
						$this->db_functions->write_log("included user to maillist","$uid: $dn");
					}
				}

				if (count($remove_maillists)>0)
				{
					foreach($remove_maillists as $uid)
					{
						$this->ldap_functions->remove_user2maillist($uid, $new_values['mail']);
						$this->db_functions->write_log("removed user from maillist","$dn: $uid");
					}
				}
			
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// APPS
				$new_values2 = array();
				$old_values2 = array();
				if (count($new_values['apps'])>0)
				{
					foreach ($new_values['apps'] as $app=>$tmp)
					{
						$new_values2[] = $app;
					}
				}
				if (count($old_values['apps'])>0)
				{
					foreach ($old_values['apps'] as $app=>$tmp)
					{
						$old_values2[] = $app;
					}
				}
				$add_apps    = array_flip(array_diff($new_values2, $old_values2));
				$remove_apps = array_flip(array_diff($old_values2, $new_values2));

				if (count($add_apps)>0)
				{
					$this->db_functions->add_id2apps($new_values['uidnumber'], $add_apps);

					foreach ($add_apps as $app => $index)
						$this->db_functions->write_log("added application to user","$dn: $app");
				}
				if (count($remove_apps)>0)
				{
					//Verifica se o gerente tem acesso a aplica��o antes de remove-la do usuario.
					$manager_apps = $this->db_functions->get_apps($_SESSION['phpgw_session']['session_lid']);
					
					foreach ($remove_apps as $app => $app_index)
					{
						if ($manager_apps[$app] == 'run')
							$remove_apps2[$app] = $app_index;
					}
					$this->db_functions->remove_id2apps($new_values['uidnumber'], $remove_apps2);
					
					if ( is_array( $remove_apps2 ) ) foreach ($remove_apps2 as $app => $access)
						$this->db_functions->write_log("removed application to user","$dn: $app");
				}
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			}
			
			if ( count( $has_change ) ) {
				require_once( PHPGW_API_INC . '/class.eventws.inc.php' );
				EventWS::getInstance()->send( 'user_changed', $dn, $has_change );
			}
			
			if ( is_string( $result ) ) {
				$return['status'] = false;
				$return['msg']   .= $this->functions->lang( $result );
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////
			return $return;
		}		
		
		function get_user_info($uidnumber)
		{
			if (!$user_info_ldap = $this->ldap_functions->get_user_info($uidnumber))
				return false;
			if ( $this->functions->denied( $user_info_ldap['context'] ) )
				return false;
			$user_info_db1 = $this->db_functions->get_user_info($uidnumber);
			$user_info_db2 = $this->ldap_functions->gidnumbers2cn($user_info_db1['groups']);
			$user_info_imap = $this->imap_functions->get_user_info($user_info_ldap['uid']);
			$user_info = array_merge($user_info_ldap, $user_info_db1, $user_info_db2, $user_info_imap);
			return $user_info;
		}
		
		function set_user_default_password($params)
		{
			$return['status'] = 1;
			$uid = $params['uid'];
			//$defaultUserPassword = '{md5}'.base64_encode(pack("H*",md5($this->current_config['expressoAdmin_defaultUserPassword'])));
			$passwd = isset($this->current_config['expressoAdmin_defaultUserPassword'])? $this->current_config['expressoAdmin_defaultUserPassword'] : '';
			$defaultUserPassword = $this->auth->encrypt_password($passwd);
			
			if (!$this->db_functions->default_user_password_is_set($uid))
			{
				$userPassword = $this->ldap_functions->set_user_password($uid, $defaultUserPassword);
				$this->db_functions->set_user_password($uid, $userPassword);
				$this->db_functions->write_log("inserted default password",$uid);
			}
			else
			{
				$return['status'] = 0;
				$return['msg'] = $this->functions->lang('default password already registered') . '!';
			}
			
			return $return;
		}

		function return_user_password($params)
		{
			$return['status'] = 1;
			$uid = $params['uid'];
			
			if ($this->db_functions->default_user_password_is_set($uid))
			{
				$userPassword = $this->db_functions->get_user_password($uid);
				$this->ldap_functions->set_user_password($uid, $userPassword);
			}
			else
			{
				$return['status'] = 0;
				$return['msg'] = $this->functions->lang('default password not registered') . '!';
			}
			
			$this->db_functions->write_log("returned user password",$uid);
			
			return $return;
		}
		
		function delete($params)
		{
			$return['status'] = true;

			// Verifica se a caixa postal esta em migracao
			$migrateMB = $this->db_functions->getMBoxMigrate( false , $params['uid'] );

			if( count($migrateMB) && strtolower($migrateMB['uid']) == strtolower($params['uid']) )
			{
				$return['status'] = false;
				$return['msg'] .= $this->functions->lang('The mailbox is being migrated wait') . '.';
				return $return;
			}

			$this->db_functions->write_log('delete user: start', $params['uid']);
			
			// Verifica o acesso do gerente
			if ($this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_DEL_USERS ))
			{
				$uidnumber = $params['uidnumber'];
				if (!$user_info = $this->get_user_info($uidnumber))
				{
					$this->db_functions->write_log('delete user: error getting users info', $user_info['uid']);
					$return['status'] = false;
					$return['msg'] = $this->functions->lang('error getting users info');
					return $return; 
				}

				//LDAP
				$result_ldap = $this->ldap_functions->delete_user($user_info);
				if (!$result_ldap['status'])
				{
					$return['status'] = false;
					$return['msg'] = 'user.delete(ldap): ' . $result_ldap['msg'];
					return $return;
				}
				else
				{
					$this->db_functions->write_log("deleted users data from ldap", $user_info['uid']);
					
					//DB
					$result_db = $this->db_functions->delete_user($user_info);
					if (!$result_db['status'])
					{
						$return['status'] = false;
						$return['msg'] .= 'user.delete(db): ' . $result_db['msg'];
					}
					else
					{
						$this->db_functions->write_log("deleted users data from DB", $user_info['uid']);
					}
					
					//IMAP
					$result_imap = $this->imap_functions->delete_user($user_info['uid']);
					if (!$result_imap['status'])
					{
						$return['status'] = false;
						$return['msg'] .= $result_imap['msg'];
					}
					else
					{
						$this->db_functions->write_log("deleted users data from IMAP", $user_info['uid']);
					}
					
					require_once( PHPGW_API_INC . '/class.eventws.inc.php' );
					$dn = 'uid='.$user_info['uid'].','.$user_info['context'];
					
					EventWS::getInstance()->send( 'user_deleted', $dn, array_merge( $user_info, array( 'groups' => $user_info['groups_ldap_dn'] ) ) );
				}
			}
			else
			{
				$this->db_functions->write_log('delete user: manager does not have access', $params['uidnumber']);
			}
			
			$this->db_functions->write_log('delete user: end', $user_info['uid']);
			return $return;
		}

		function rename( $params )
		{
			$uid = $params['uid'];
			$new_uid = $params['new_uid'];
			
			// Verifica se a caixa postal esta em migracao
			$migrateMB = $this->db_functions->getMBoxMigrate( false , $uid );
			
			if( count($migrateMB) && strtolower($migrateMB['uid']) == strtolower($uid) )
				return array( 'status' => false, 'msg' => $this->functions->lang('The mailbox is being migrated wait').'.' );
			
			// Verifica acesso do gerente (OU) ao tentar renomear um usuario.
			if ( !$this->ldap_functions->check_access_to_renamed($uid) )
				return array( 'status' => false, 'msg' => $this->functions->lang('You do not have access to delete user').'.' );
			
			// Check if the new_uid is in use.
			if ( !$this->ldap_functions->check_rename_new_uid($new_uid) )
				return array( 'status' => false, 'msg' => $this->functions->lang('New login already in use').'.' );
			
			// Verifica o acesso do gerente
			if ( !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_REN_USERS ))
				return array( 'status' => false, 'msg' => $this->functions->lang('Permission denied').'.' );
			
			//$defaultUserPassword = $this->auth->encrypt_password($this->current_config['expressoAdmin_defaultUserPassword']);
			//$defaultUserPassword_plain = $this->current_config['expressoAdmin_defaultUserPassword'];
			
			// Get profile before rename on LDAP, solve replication problems
			$profile = CreateObject('emailadmin.bo')->getProfile('uid', $uid);
			
			// Rename uid on ldap
			$result = $this->ldap_functions->rename_uid( $uid, $new_uid );
			if ( !$result['status'] ) return array( 'status' => false, 'msg' => $this->functions->lang('Error rename user in LDAP').'.' );
			
			// Rename mailbox on imap
			if ( $profile ) {
				
				$result = $this->imap_functions->rename_mailbox( $uid, $new_uid, $profile );
				if ( !$result['status'] ) {
					
					// Revert ldap uid
					$this->ldap_functions->rename_uid( $new_uid, $uid );
					
					return array( 'status' => false, 'msg' => $this->functions->lang('Error renaming user mailboxes').".\n".$result['msg'] );
				}
			}
			
			// In this point, not revert ldap or mailbox 
			$this->db_functions->write_log("renamed user", "$uid -> $new_uid");
			
			require_once( PHPGW_API_INC . '/class.eventws.inc.php' );
			EventWS::getInstance()->send( 'user_renamed', 'cn='.$new_uid.','.$result['context'], array( 'old_dn' => 'cn='.$uid.','.$result['context']) );
			
			// Rename script on sieve
			if ( $profile && $profile['imapEnableSieve'] === 'yes' ) {
				include_once('sieve-php.lib.php');
				
				$sieve = new sieve(
					$profile['imapSieveServer'],
					$profile['imapSievePort'],
					$new_uid,
					$profile['imapAdminPW'],
					$profile['imapAdminUsername'],
					'PLAIN'
				);
				
				if ( !$sieve->sieve_login() )
					return array( 'status' => true, 'msg' => $this->functions->lang("Can not login sieve").'.' );
				
				$sieve->sieve_listscripts();
				
				if ( !$sieve->error ) {
					$myactivescript = $sieve->response["ACTIVE"];
					$sieve->sieve_getscript( $myactivescript );
					
					$script = '';
					if ( !empty($sieve->response) ) foreach ( $sieve->response as $line ) $script .= $line;
					
					if ( !empty($script) ) {
						
						if ( !$sieve->sieve_sendscript( $new_uid, $script) )
							return array( 'status' => true, 'msg' => $this->functions->lang('Problem saving sieve script').'.' );
						
						if ( !$sieve->sieve_setactivescript( $new_uid ) )
							return array( 'status' => true, 'msg' => $this->functions->lang('Problem activating sieve script').'.' );
						
						if ( !$sieve->sieve_deletescript( $myactivescript ) )
							return array( 'status' => true, 'msg' => $this->functions->lang('Problem deleting old script').'.' );
						
					}
				}
				$sieve->sieve_logout();
			}
			
			return array( 'status' => true );
		}
		
		function write_log_from_ajax($params)
		{
			$this->db_functions->write_log($params['_action'],'',$params['userinfo'],'','');
			return true;
		}
		
		function mbox_migrate($params)
		{
			$async = CreateObject('phpgwapi.asyncservice');
			$id = (int)$params['id'];
			
			// Get retry execute job case
			$is_retry = isset($params['asyncservice_retry']);
			$retry = $is_retry? (int)$params['asyncservice_retry'] : 0;
			$async->log('is_retry: '.($is_retry?'T':'F').' retry: '.$retry);
			
			// Give up after 3 attempts
			if ( $retry > 3 ) {
				$this->db_functions->setMBoxMigrateStatus( $id, 'error' );
				return false;
			}
			
			// Get migrate settings
			$setts = $this->db_functions->getMBoxMigrate( $id );
			if (!$setts) return false;
			
			// Check if exists another instance for this user with running or error
			if ( ( (!$is_retry) && $setts['status'] != '0' ) || $setts['previous_status'] !== '' ) return false;
			
			// Unserialize data field
			if ( isset($setts['data']) ) $setts['data'] = unserialize($setts['data']);
			
			// Get quota from settings
			$quota = isset($setts['data']['mailquota'])? $setts['data']['mailquota']*1024 : null;
			
			// Update migration status
			$this->db_functions->setMBoxMigrateStatus( $id, 'exec' );
			
			// Search both profiles
			$emailadminbo = CreateObject('emailadmin.bo');
			$profile_orig = $emailadminbo->getProfile('id', $setts['profileid_orig']);
			$profile_dest = $emailadminbo->getProfile('id', $setts['profileid_dest']);
			
			// Execute migration function
			$result = $this->imap_functions->move_mailbox($setts['uid'], $profile_orig, $profile_dest, $quota, $is_retry );
			
			// Update migration status
			$this->db_functions->setMBoxMigrateStatus( $id, $result? 'success' : 'error' );
			
		}
		
	}
?>
