<?php
	/***********************************************************************************\
	* Expresso Administraï¿½ï¿½o															*
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	*
	* ----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			*
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.														*
	\***********************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

	class uiaccounts
	{
		var $public_functions = array
		(
			'list_users'				=> True,
			'add_users'					=> True,
			'edit_user'					=> True,
			'view_user'					=> True,
			'show_photo'				=> True,
			'show_access_log'			=> True,
			'css'						=> True
		);

		var $nextmatchs;
		var $user;
		var $functions;
		var $current_config;
		var $ldap_functions;
		var $db_functions;
		var $session;

		function uiaccounts()
		{
			$this->user			= CreateObject('expressoAdmin1_2.user');
			$this->nextmatchs	= CreateObject('phpgwapi.nextmatchs');
			$this->functions	= CreateObject('expressoAdmin1_2.functions');
			$this->ldap_functions = CreateObject('expressoAdmin1_2.ldap_functions');
			$this->db_functions = CreateObject('expressoAdmin1_2.db_functions');
			$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c->read_repository();
			$this->current_config = $c->config_data;
			
			if(!@is_object($GLOBALS['phpgw']->js))
			{
				$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			}
			
			$GLOBALS['phpgw']->js->validate_file('jscode','connector','expressoAdmin1_2');#diretorio, arquivo.js, aplicacao
			$GLOBALS['phpgw']->js->validate_file('jscode','expressoadmin','expressoAdmin1_2');
			$GLOBALS['phpgw']->js->validate_file('jscode','tabs','expressoAdmin1_2');
			$GLOBALS['phpgw']->js->validate_file('jscode','users','expressoAdmin1_2');
		}

		function list_users()
		{
			$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$acl = $this->functions->read_acl($account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $acl['contexts'];
			foreach ($acl['contexts_display'] as $index=>$tmp_context)
			{
				$context_display .= '<br>'.$tmp_context;
			}
			// Verifica se o administrador tem acesso.
			if (!$this->functions->check_acl( $account_lid, ACL_Managers::GRP_VIEW_USERS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
			}

			if(isset($_POST['query']))
			{
				// limit query to limit characters
				if(preg_match('/^[a-z_0-9_-].+$/i',$_POST['query'])) 
				{
					$GLOBALS['query'] = $_POST['query'];
				}
			}
			
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('User accounts');
			$GLOBALS['phpgw']->common->phpgw_header();

			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(Array('accounts' => 'accounts.tpl'));
			$p->set_block('accounts','body');
			$p->set_block('accounts','row');
			$p->set_block('accounts','row_empty');

			$var = Array(
				'bg_color'					=> $GLOBALS['phpgw_info']['theme']['bg_color'],
				'th_bg'						=> $GLOBALS['phpgw_info']['theme']['th_bg'],
				'accounts_url'				=> $GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uiaccounts.list_users'),
				'back_url'					=> $GLOBALS['phpgw']->link('/expressoAdmin1_2/index.php'),
				'add_action'				=> $GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uiaccounts.add_users'),
				'create_user_disabled'		=> $this->functions->check_acl( $account_lid, ACL_Managers::ACL_ADD_USERS ) ? '' : 'disabled',
				'context'					=> $raw_context,
				'context_display'			=> $context_display,
			);
			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'body'));

			$p->set_var('query', $GLOBALS['query']);
			
			//Admin make a search
			if ($GLOBALS['query'] != '')
			{
				$account_info = $this->functions->get_list('accounts', $GLOBALS['query'], $contexts);
			}
			
			if (!count($account_info) && $GLOBALS['query'] != '')
			{
				$p->set_var('message',lang('No matches found'));
				$p->parse('rows','row_empty',True);
			}
			else if (count($account_info))
			{  // Can edit, delete or rename users ??
				$can_edit = $this->functions->check_acl( $account_lid,
					ACL_Managers::ACL_MOD_USERS,
					ACL_Managers::ACL_MOD_USERS_PASSWORD,
					ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES,
					ACL_Managers::ACL_MOD_USERS_QUOTA,
					ACL_Managers::ACL_MOD_USERS_CORPORATIVE,
					ACL_Managers::ACL_MOD_USERS_PHONE_NUMBER
				);
				$can_view   = $this->functions->check_acl( $account_lid, ACL_Managers::ACL_VW_USERS );
				$can_delete = $this->functions->check_acl( $account_lid, ACL_Managers::ACL_DEL_USERS );
				$can_rename = $this->functions->check_acl( $account_lid, ACL_Managers::ACL_REN_USERS );

				while (list($null,$account) = each($account_info))
				{
					$this->nextmatchs->template_alternate_row_color($p);

					$var = array(
						'row_loginid'	=> $account['account_lid'],
						'row_cn'		=> $account['account_cn'],
						'row_mail'		=> (!$account['account_mail']?'<font color=red>Sem E-mail</font>':$account['account_mail'])
					);
	
					$migrateMB = $this->db_functions->getMBoxMigrate( false , $account['account_lid'] );
					$isMigrate = false;

					if( count($migrateMB) && strtolower($migrateMB['uid']) == strtolower($account['account_lid']))
					{	

						$status 	= $migrateMB['status'];
						$statusMB 	= '';
						$isMigrate 	= true;
						$prevStatus = explode( ",", $migrateMB['previous_status'] );

						if( in_array("-1", $prevStatus) )
						{
							$status = "-1";
						}
						else 
						{
							if( $migrateMB['status'] == "-1" ) $status = $migrateMB['status'];
						}

						switch( $status )
						{
					 		case '-1':
					 			$statusMB = lang("ERROR in the copy of the mailbox");
					 			break;

					 		case '0' :
					 			$statusMB = lang("Awaiting execution");
					 			break;
					 		case '1':
					 			$statusMB = lang("Running");
					 			break;
						}
						
						$var['row_mail'] = $account['account_mail'] . " - <label style='color:red;font-weight:bold;'>".lang('Migration')." : ".$statusMB."</label>";
					}
						
					$p->set_var($var);

					// Edit
					if ($can_edit)
						$p->set_var('row_edit',$this->row_action('edit','user',$account['account_id']));
					elseif ($can_view)
						$p->set_var('row_edit',$this->row_action('view','user',$account['account_id']));
					else
						$p->set_var('row_edit','&nbsp;');

					// Rename
					if ($can_rename)
					{
						if( !$isMigrate )
							$p->set_var('row_rename',"<a href='#' onClick='javascript:rename_user(\"".$account['account_lid']."\",\"".$account['account_id']."\");'>".lang('to rename')."</a>");
						else
							$p->set_var('row_rename', "<font style='color:red;font-weight:bold;'>".lang('Disabled')."</font>" );
					}
					else
						$p->set_var('row_rename','&nbsp;');

					// Delete	
					if ($can_delete)
					{
						if( !$isMigrate )
							$p->set_var('row_delete',"<a href='#' onClick='javascript:delete_user(\"".$account['account_lid']."\",\"".$account['account_id']."\");'>".lang('to delete')."</a>");
						else
							$p->set_var('row_delete', "<font style='color:red;font-weight:bold;'>".lang('Disabled')."</font>" );
					}
					else
						$p->set_var('row_delete','&nbsp;');

					$p->parse('rows','row',True);
				}
			}
			
			$p->pfp('out','body');
		}

		function add_users()
		{
			$radius_conf = $this->ldap_functions->getRadiusConf();
			$GLOBALS['phpgw']->js->validate_file('jscode','users','expressoAdmin1_2');

			$GLOBALS['phpgw']->js->set_onload('get_available_groups(document.forms[0].context.value);');
			$GLOBALS['phpgw']->js->set_onload('get_available_maillists(document.forms[0].context.value);');
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
				$GLOBALS['phpgw']->js->set_onload('get_available_sambadomains(document.forms[0].context.value, \'create_user\');');
			
			if ($radius_conf->enabled)
			{
				$GLOBALS['phpgw']->js->validate_file("jscode","radius","expressoAdmin1_2");
			}

			$manager_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$acl = $this->functions->read_acl($manager_lid);
			
			$manager_contexts = $acl['contexts'];
			
			// Verifica se tem acesso a este modulo
			if (!$this->functions->check_acl( $manager_lid, ACL_Managers::ACL_ADD_USERS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
			}
				
			// Imprime nav_bar
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Create User');
			$GLOBALS['phpgw']->common->phpgw_header();
			
			// Seta template
			$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			$t = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$t->set_file(array("body" => "accounts_form.tpl"));
			$t->set_block('body','main');

			// Pega combo das organizações e seleciona, caso seja um post, o setor que o usuario selecionou.
			foreach ($manager_contexts as $index=>$context)
			{
				$combo_manager_org .= $this->functions->get_organizations($context);
			}
			//$combo_all_orgs = $this->functions->get_organizations($GLOBALS['phpgw_info']['server']['ldap_context'], '', true, true, true);
			
			// Chama funcao para criar lista de aplicativos disponiveis.
			$applications_list = $this->functions->make_list_app($manager_lid);

			// Cria combo de dominio samba
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
			{
				$a_sambadomains = $this->db_functions->get_sambadomains_list();
				$sambadomainname_options = '<option value=""></option>';
				if (count($a_sambadomains))
				{
					foreach ($a_sambadomains as $a_sambadomain)
					{
						// So mostra os sambaDomainName do contexto do manager
						if ($this->ldap_functions->exist_sambadomains($manager_contexts, $a_sambadomain['samba_domain_name']))
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "'>" . $a_sambadomain['samba_domain_name'] . "</option>";
					}
				}
			}

			// Cria combo radius
			if ($radius_conf->enabled)
			{
				$radius_options = '';
				foreach ( $radius_conf->profiles as $key => $value ) {
					$disabled_radopt = ( isset($value['radiusGroupName']) && !$this->functions->isMembership($value['radiusGroupName']) )? ' disabled="disabled"' : '';
					$radius_options .= '<option value="'.$key.'"'.$disabled_radopt.'>'.$value['description'][0].'</option>';
				}
			}
			
			// Valores default.
			$var = Array( 
				'row_on'				=> "#DDDDDD",
				'row_off'				=> "#EEEEEE",
				'color_bg1'				=> "#E8F0F0",
				//'manager_context'		=> $manager_context,
				'type'					=> 'create_user',
				'back_url'				=> $GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uiaccounts.list_users'),
				'disabled_access_button'=> 'disabled',
				'display_access_log_button'	=> 'none',
				'display_access_log_button'	=> 'none',
				'display_empty_user_inbox'	=>'none',
				'display_quota_used'		=> 'none',
				
				// First ABA
				'display_spam_uid'				=> 'display:none',
				
				'sectors'						=> $combo_manager_org,
				'combo_organizations'			=> $combo_manager_org,
				//'combo_all_orgs'				=> $combo_all_orgs,
				'passwd_expired_checked'		=> 'CHECKED',
				'disabled_passwd_expired'		=> 'disabled',
				'changepassword_checked'		=> 'CHECKED',
				'phpgwaccountstatus_checked'	=> 'CHECKED',
				'photo_bin'						=> $GLOBALS['phpgw_info']['server']['webserver_url'].'/expressoAdmin1_2/templates/default/images/photo_celepar.png',
				'display_picture'				=> $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_USERS_PICTURE ) ? '' : 'none', 
				'display_tr_default_password'	=> 'none',
				'minimumSizeLogin'				=> $this->current_config['expressoAdmin_minimumSizeLogin'],
				'defaultDomain'					=> $this->current_config['expressoAdmin_defaultDomain'],
				'concatenateDomain'				=> $this->current_config['expressoAdmin_concatenateDomain'],
				'display_password_generator_button'=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? '' : 'none',
				'readonly_password'				=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? 'readonly' : '',
				'password_input_type'			=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? 'text' : 'password',
				
				'ldap_context'					=> ldap_dn2ufn($GLOBALS['phpgw_info']['server']['ldap_context']),
				
				// Corporative Information
				'display_corporative_information' => $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_USERS_CORPORATIVE ) ? '' : 'none',
				
				// MIGRATE MAILBOX
				'isMigrateMB'	=> 0,

				//MAIL
				'accountstatus_checked'			=> 'CHECKED',
				'mailquota'				=> $this->current_config['expressoAdmin_defaultUserQuota'],
				'changequote_disabled'			=> $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_USERS_QUOTA ) ? '' : 'readonly',
				'imapDelimiter'				=> $_SESSION['phpgw_info']['expresso']['email_server']['imapDelimiter'],
				'input_mailalternateaddress_fields' 	=> '<input type="text" name="mailalternateaddress[]" id="mailalternateaddress" autocomplete="off" value="{mailalternateaddress}" {disabled} size=50>',
				'input_mailforwardingaddress_fields'	=> '<input type="text" name="mailforwardingaddress[]" id="mailforwardingaddress" autocomplete="off" value="{mailforwardingaddress}" {disabled} size=50>',
				'display_create_user_inbox'		=> 'none',
				
				'apps'								=> $applications_list,
				
				//SAMBA ABA
				'use_attrs_samba_checked'		=> 'CHECKED',
				'sambadomainname_options'		=> $sambadomainname_options,
				'sambalogonscript'			=> $this->current_config['expressoAdmin_defaultLogonScript'] != '' ? $this->current_config['expressoAdmin_defaultLogonScript'] : '',
				'use_suggestion_in_logon_script'	=> $this->current_config['expressoAdmin_defaultLogonScript'] == '' ? 'true' : 'false',
				'sambahomedirectory'             => isset( $this->current_config[ 'homedirectory' ] ) ? $this->current_config[ 'homedirectory' ] : '/dev/null',
				'loginshell'                     => isset( $this->current_config[ 'loginshell' ] ) ? $this->current_config[ 'loginshell' ] : '/bin/bash',

				// RADIUS
				'radius_options' => $radius_options
			);

			// Should we show SAMBA tab SAMBA ??
			if ( ($this->current_config['expressoAdmin_samba_support'] == 'true') && ($this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )) )
				$t->set_var('display_samba_suport', '');
			else
				$t->set_var('display_samba_suport', 'none');
			
			// Is Radius enabled and has the manager privileges to it?
			if ( $radius_conf->enabled && ($this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_USERS_RADIUS )) )
				$t->set_var('display_radius_suport', '');
			else
				$t->set_var('display_radius_suport', 'none');
			
			require_once( PHPGW_API_INC . '/class.activedirectory.inc.php' );
			if ( ActiveDirectory::getInstance()->enabled && $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_SET_USERS_ACTIVE_DIRECTORY ) ) {
				$dlist = '<select name="ad_ou"><option value="">'.lang( 'default' ).'</option>';
				foreach ( ActiveDirectory::getInstance()->ou_list as $key => $value )
					$dlist .= '<option value="'.$value.'">'.$key.'</option>';
				$dlist .= '</select>';
				$t->set_var( array(
					'ad_status'                  => 0,
					'lang_active_directory_info' => lang( 'create the account in the oraganization unit ' ),
					'active_directory_info'      => $dlist,
					'display_ad_suport'          => '',
				) );
			} else
				$t->set_var( 'display_ad_suport', 'none' );
			
			$t->set_var($var);
			$t->set_var($this->functions->make_dinamic_lang($t, 'main'));
			$t->pfp('out','main');
		}
		
		function view_user()
		{
			ExecMethod('expressoAdmin1_2.uiaccounts.edit_user');
			return;
		}
		
		function edit_user()
		{
			$manager_account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$acl = $this->functions->read_acl($manager_account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $acl['contexts'];		
			$alert_warning = '';
			$radius_conf = $this->ldap_functions->getRadiusConf();
			
			// Verifica se tem acesso a este modulo
			$disabled = 'disabled';
			$disabled_password = 'disabled';
			$disabled_samba = 'disabled';
			$disabled_edit_photo = 'disabled';
			$disabled_phonenumber = 'disabled';
			$disabled_group = 'disabled';
			
			$display_picture = 'none';
			if ( !$this->functions->check_acl( $manager_account_lid,
				ACL_Managers::ACL_VW_USERS,
				ACL_Managers::ACL_MOD_USERS,
				ACL_Managers::ACL_MOD_USERS_PASSWORD,
				ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES,
				ACL_Managers::ACL_MOD_USERS_CORPORATIVE,
				ACL_Managers::ACL_MOD_USERS_PHONE_NUMBER
			) ) $GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
			// SOMENTE ALTERAÇÃO DE SENHA
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PASSWORD )))
			{
				$disabled = 'disabled';
				$disabled_password = '';
			}
			// SOMENTE ALTERAÇÃO DOS ATRIBUTOS SAMBA
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )))
			{
				$disabled = 'disabled';
				$disabled_samba = '';
			}
			// SOMENTE ALTERAÇÃO DE TELEFONE
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PHONE_NUMBER )))
			{
				$disabled = 'disabled';
				$disabled_phonenumber = '';
			}
			// SOMENTE GRUPOS
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_GROUPS )))
			{
				$disabled = 'disabled';
				$disabled_group = '';
			}
			// TOTAIS MENOS O SAMBA
			if (($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && (!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )))
			{
				$disabled = '';
				$disabled_password = '';
				$disabled_samba = 'disabled';
				$disabled_phonenumber = '';
				$disabled_group = '';
			}
			// TOTAIS
			elseif ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS ))
			{
				$disabled = '';
				$disabled_password = '';
				$disabled_samba = '';
				$disabled_phonenumber = '';
				$disabled_group = '';
			}
			
			if (!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_QUOTA ))
				$disabled_quote = 'readonly';
			
			if ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PICTURE ))
			{
				$disabled_edit_photo = '';
				$display_picture = '';
			}
			// GET all infomations about the user.
			$user_info = $this->user->get_user_info($_GET['account_id']);

			// Formata o CPF
			if ($user_info['corporative_information_cpf'] != '')
			{
				if (strlen($user_info['corporative_information_cpf']) < 11)
				{
					while (strlen($user_info['corporative_information_cpf']) < 11)
					{
						$user_info['corporative_information_cpf'] = '0' . $user_info['corporative_information_cpf'];
					}
				} 
				if (strlen($user_info['corporative_information_cpf']) == 11)
				{
					// Compatível com o php4.
					//$cpf_tmp = str_split($user_info['corporative_information_cpf'], 3);
					$cpf_tmp[0] = $user_info['corporative_information_cpf'][0] . $user_info['corporative_information_cpf'][1] . $user_info['corporative_information_cpf'][2]; 
					$cpf_tmp[1] = $user_info['corporative_information_cpf'][3] . $user_info['corporative_information_cpf'][4] . $user_info['corporative_information_cpf'][5];
					$cpf_tmp[2] = $user_info['corporative_information_cpf'][6] . $user_info['corporative_information_cpf'][7] . $user_info['corporative_information_cpf'][8];
					$cpf_tmp[3] = $user_info['corporative_information_cpf'][9] . $user_info['corporative_information_cpf'][10];
					$user_info['corporative_information_cpf'] = $cpf_tmp[0] . '.' . $cpf_tmp[1] . '.' . $cpf_tmp[2] . '-' . $cpf_tmp[3];
				}
			}
			// JavaScript
			$GLOBALS['phpgw']->js->validate_file("jscode","users","expressoAdmin1_2");
			$GLOBALS['phpgw']->js->set_onload("get_available_groups(document.forms[0].context.value);");
			$GLOBALS['phpgw']->js->set_onload("get_available_maillists(document.forms[0].context.value);");
			$GLOBALS['phpgw']->js->set_onload("use_samba_attrs('".$user_info['sambaUser']."');");
			
			if ( $radius_conf->enabled )
			{
				$GLOBALS['phpgw']->js->validate_file("jscode","radius","expressoAdmin1_2");
			}

			// Seta header.
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);

			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Edit User');
			$GLOBALS['phpgw']->common->phpgw_header();

			// Seta templates.
			$t = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$t->set_file(array("body" => "accounts_form.tpl"));
			$t->set_block('body','main');
							
			foreach ($contexts as $index=>$context)
				$combo_manager_org .= $this->functions->get_organizations($context, $user_info['context']);
			//$combo_all_orgs = $this->functions->get_organizations($GLOBALS['phpgw_info']['server']['ldap_context'], $user_info['context'], true, true, true);			

			// GROUPS.
			if (count($user_info['groups_info']) > 0)
			{
				foreach ($user_info['groups_info'] as $group)
				{
					$array_groups[$group['gidnumber']] = $group['cn'];
				}
				natcasesort($array_groups);
				foreach ($array_groups as $gidnumber=>$cn)
				{
					// O memberUid do usuário está somente no Banco, então adicionamos o memberUid no Ldap.
					if (is_null($user_info['groups_ldap'][$gidnumber]))
					{
						$this->db_functions->remove_user2group($gidnumber, $_GET['account_id']);

						if ($alert_warning == '')
							$alert_warning = lang("the expressoadmin corrected the following inconsistencies") . ":\\n";
						$alert_warning .= lang("user removed from group because the group was not found") . ":\\n$cn - gidnumber: $gidnumber.";
					}
					else
						$ea_select_user_groups_options .= "<option value=" . $gidnumber . ">" . $cn . "</option>";
					
					if ($gidnumber == $user_info['gidnumber'])
					{
						$ea_combo_primary_user_group_options .= "<option value=" . $gidnumber . " selected>" . $cn . "</option>";
					}
					else
					{
						$ea_combo_primary_user_group_options .= "<option value=" . $gidnumber . ">" . $cn . "</option>";
					}
				}
				
				// O memberUid do usuário está somente no Ldap.
				$groups_db = array_flip($user_info['groups']);
				foreach ($user_info['groups_ldap'] as $gidnumber=>$cn)
				{
					if (is_null($groups_db[$gidnumber]))
					{
						/*
						$this->ldap_functions->remove_user2group($gidnumber, $user_info['uid']);
						if ($alert_warning == '')
							$alert_warning = "O expressoAdmin corrigiu as seguintes inconsistências:\\n";
						$alert_warning .= "Removido atributo memberUid do usuário do grupo $cn.\\n";
						*/
						$ea_select_user_groups_options .= "<option value=" . $gidnumber . ">" . $cn . " [".lang('only on ldap')."]</option>";
					}
				}
			}
			
			// MAILLISTS
			if (count($user_info['maillists_info']) > 0)
			{
				foreach ($user_info['maillists_info'] as $maillist)
				{
					$array_maillist[$maillist['uid']] = $maillist['uid'] . "  (" . $maillist['mail'] . ") ";
				}
				natcasesort($array_maillist);
				foreach ($array_maillist as $uid=>$option)
				{
					$ea_select_user_maillists_options .= "<option value=" . $uid . ">" . $option . "</option>";
				}
			}
			
			// APPS.
			if ($disabled == 'disabled')
				$apps = $this->functions->make_list_app($manager_account_lid, $user_info['apps'], 'disabled');
			else
				$apps = $this->functions->make_list_app($manager_account_lid, $user_info['apps']);
			
			//PHOTO
			if ($user_info['photo_exist'])
			{
				$photo_bin = "./index.php?menuaction=expressoAdmin1_2.uiaccounts.show_photo&uidNumber=".$_GET['account_id'];
			}
			else
			{
				$photo_bin = $GLOBALS['phpgw_info']['server']['webserver_url'] . '/expressoAdmin1_2/templates/default/images/photo_celepar.png';
				$disabled_delete_photo = 'disabled';
			}

			// Cria combo de dominios do samba
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
			{
				$a_sambadomains = $this->db_functions->get_sambadomains_list();
				$sambadomainname_options = '<option value=""></option>';
				if (count($a_sambadomains))
				{
					foreach ($a_sambadomains as $a_sambadomain)
					{
						if ($a_sambadomain['samba_domain_sid'] == $user_info['sambasid'])
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "' SELECTED>" . $a_sambadomain['samba_domain_name'] . "</option>";
						else
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "'>" . $a_sambadomain['samba_domain_name'] . "</option>";
					}
				}
			}
			
			// Cria combo radius
			if ($radius_conf->enabled)
			{
				$radius_options = '';
				$user_radius_options = '';
				foreach ( $radius_conf->profiles as $key => $value )
				{
					$disabled_radopt = ( isset($value['radiusGroupName']) && !$this->functions->isMembership($value['radiusGroupName']) )? ' disabled="disabled"' : '';
					$opt = '<option value="'.$key.'"'.$disabled_radopt.'>'.$value['description'][0].'</option>';
					if ( isset($user_info[$radius_conf->groupname_attribute]) && in_array( $key, $user_info[$radius_conf->groupname_attribute] ))
						$user_radius_options .= $opt;
					else
						$radius_options .= $opt;
				}
			}
			
			require_once( PHPGW_API_INC . '/class.activedirectory.inc.php' );
			if (
				$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_SET_USERS_ACTIVE_DIRECTORY ) &&
				( $ad = ActiveDirectory::getInstance() ) &&
				$ad->enabled
			) {
				$ad_status = 0;
				if ( $info = $ad->info( $user_info['uid'] ) ) {
					$ad_status = $info->Enabled? 1 : 2;
					$label = lang( 'Active Directory information' );
					$msg = '<table width="100%">';
					foreach ( array(
						'Enabled'            => 'Status',
						'Logon'              => 'Username',
						'Name'               => 'Name',
						'Department'         => 'Department',
						'OU'                 => 'Organizational Unit',
						'LastUpdateExpresso' => 'Last update',
						'pwdLastSet'         => 'Last password update',
					) as $key => $label ) {
						$msg .= '<tr bgcolor="#'.(($c = !$c)?'DDDDDD':'EEEEEE').'"><td>'.lang( $label ).':</td><td>'.( $key === 'Enabled'? lang( $info->{$key}? 'active': 'inactive' ) : $info->{$key} ).'</td></tr>';
					}
					$msg .= '<tr bgcolor="#'.(($c = !$c)?'DDDDDD':'EEEEEE').'"><td>'.lang( 'AD Servers information' ).':</td><td>';
					$msg_tmp1 = '';
					foreach ( $info->DomainsInfo->UserDomainInfo as $obj ) {
						$msg_tmp2 = '';
						foreach ( array( 'LastLogon', 'LogonCount', 'LastLogonFail', 'badPwdCount' ) as $key ) {
							if ( isset( $obj->{$key} ) )
								$msg_tmp2 .= '<tr><td>'.$key.':</td><td>'.$obj->{$key}.'</td></tr>';
						}
						if ( $msg_tmp2 !== '' ) $msg_tmp1 .= '<tr bgcolor="#'.(($c = !$c)?'DDDDDD':'EEEEEE').'"><td>'.$obj->DnsHostName.':</td><td><table width="100%">'.$msg_tmp2.'</table></td></tr>';
					}
					$msg .= ( $msg_tmp1 === '' ) ? lang( 'none' ): '<table width="100%">'.$msg_tmp1.'</table>';
					$msg .= '</td></tr></table>';
				} else {
					$label = lang( 'create the account in the oraganization unit ' );
					$msg   = '<select name="ad_ou"><option value="">'.lang( 'default' ).'</option>';
					foreach ( $ad->ou_list as $key => $value )
						$msg .= '<option value="'.$value.'">'.$key.'</option>';
					$msg .= '</select>';
					if ( !preg_match( '/User not found/', $ad->getError() ) ) $msg .= '<br>'.$ad->getError();
				}
				
				$t->set_var( array(
					'ad_status'                  => $ad_status,
					'lang_active_directory_info' => $label,
					'active_directory_info'      => $msg,
					'ad_enabled_checked'         => ($ad_status === 1)? 'checked' : '',
					'display_ad_suport'          => '',
				) );
			} else
				$t->set_var( 'display_ad_suport', 'none' );
			
			// Mail Alternate & Forwarding
			if (is_array($user_info['mailalternateaddress']))
			{
				for ($i = 0; $i < $user_info['mailalternateaddress']['count']; $i++)
				{
					if ($i > 0)
						$input_mailalternateaddress_fields .= '<br>';
					$input_mailalternateaddress_fields .= '<input type="text" name="mailalternateaddress[]" id="mailalternateaddress" autocomplete="off" value="'.$user_info['mailalternateaddress'][$i].'" {disabled} size=50>';
				}
			}
			else
			{
				$input_mailalternateaddress_fields = '<input type="text" name="mailalternateaddress[]" id="mailalternateaddress" autocomplete="off" value="" {disabled} size=50>';
			}

			if (is_array($user_info['mailforwardingaddress']))
			{
				for ($i = 0; $i < $user_info['mailforwardingaddress']['count']; $i++)
				{
					if ($i > 0)
						$input_mailforwardingaddress_fields .= '<br>';
					$input_mailforwardingaddress_fields .= '<input type="text" name="mailforwardingaddress[]" id="mailforwardingaddress" autocomplete="off" value="'.$user_info['mailforwardingaddress'][$i].'" {disabled} size=50>';
				}
			}
			else
			{
				$input_mailforwardingaddress_fields = '<input type="text" name="mailforwardingaddress[]" id="mailforwardingaddress" autocomplete="off" value="" {disabled} size=50>';
			}

			if ($alert_warning != '')
				$alert_warning = "alert('". $alert_warning ."')";
			
			if ( $user_info['passwd_expired'] > 0 && $GLOBALS['phpgw_info']['server']['max_pwd_age'] && $GLOBALS['phpgw_info']['server']['max_pwd_age'] > 0 ) {
				
				$expired_time = $user_info['passwd_expired'] + $GLOBALS['phpgw_info']['server']['max_pwd_age'] * 24 * 60 * 60;
				$printable_date = date('d/m/Y - H:i', $expired_time);
				$password_expiration_message = ( ( $expired_time - time() > 0 )? lang('password will expire on') : lang('password expired on') ) . ' ' . $printable_date;
			}

			// Verifica se a caixa postal esta em migracao
			$migrateMB 		= $this->db_functions->getMBoxMigrate( false , $user_info['uid'] );
			$isMigrateMB	= 0;

			if( count($migrateMB) && strtolower($migrateMB['uid']) == strtolower($user_info['uid']) )
			{
				$isMigrateMB = 1;
			}
			
			$has_profile = isset($user_info['mailbox_profile_id']);
			
			$var = Array(
				'uidnumber'					=> $_GET['account_id'],
				'type'						=> 'edit_user',
				'photo_exist'				=> $user_info['photo_exist'],
				'departmentnumber'			=> $user_info['departmentnumber'],
				'user_context'				=> $user_info['context'],
				
				'row_on'					=> "#DDDDDD",
				'row_off'					=> "#EEEEEE",
				'color_bg1'					=> "#E8F0F0",
				'action'					=> $GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uiaccounts.validate_user_data_edit'),
				'back_url'					=> './index.php?menuaction=expressoAdmin1_2.uiaccounts.list_users',
				'disabled'					=> $disabled,
				'disabled_password'			=> $disabled_password,
				'disabled_samba'			=> $disabled_samba,
				'changequote_disabled'		=> $disabled_quote,
				'disable_phonenumber'		=> $disabled_phonenumber,
				'disable_group'				=> $disabled_group,
				'display_password_generator_button'=> $this->current_config['expressoAdmin_usePasswordGenerator'] != 'false' ? '' : 'none',
				'display_password_generator_button'=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? '' : 'none',
				'readonly_password'				=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? 'readonly' : '',
				'password_input_type'		=> $this->current_config['expressoAdmin_usePasswordGenerator'] == 'true' ? 'text' : 'password',
				'defaultDomain'					=> $this->current_config['expressoAdmin_defaultDomain'],
				'ldap_context'					=> ldap_dn2ufn($GLOBALS['phpgw_info']['server']['ldap_context']),
				
				// Display ABAS
				'display_corporative_information'=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_CORPORATIVE ) ? '' : 'none',
				'display_applications'		=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::GRP_DISPLAY_APPLICATIONS ) ? '' : 'none',
				'display_emaillists'		=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::GRP_DISPLAY_EMAIL_LISTS ) ? '' : 'none',
				'display_groups'			=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::GRP_DISPLAY_GROUPS ) ? '' : 'none',
				'display_emailconfig'		=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::GRP_DISPLAY_EMAIL_CONFIG ) ? '' : 'none',
				
				// First ABA
				'alert_warning'					=> "$alert_warning",
				'display_input_account_lid'		=> 'display:none',
				'sectors'						=> $combo_manager_org,
				'combo_organizations'			=> $combo_manager_org,
				//'combo_all_orgs'				=> $combo_all_orgs,
				'uid'							=> $user_info['uid'],
				'givenname'						=> $user_info['givenname'],
				'sn'							=> $user_info['sn'],
				'telephonenumber'				=> $user_info['telephonenumber'],
				'photo_bin'						=> $photo_bin,
				'disabled_edit_photo'			=> $disabled_edit_photo,
				'display_picture'				=> $display_picture,
				'display_tr_default_password'	=> $this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_SET_USERS_DEFAULT_PASSWORD ) ? '' : 'none',
				
				'passwd_expired_checked'		=> $user_info['passwd_expired'] === 0 ? 'CHECKED' : '',
				'password_expiration_message'	=> $password_expiration_message,
				
				'changepassword_checked'		=> $user_info['changepassword'] == '1' ? 'CHECKED' : '',
				'phpgwaccountstatus_checked'	=> $user_info['phpgwaccountstatus'] == 'A' ? 'CHECKED' : '',
				'phpgwaccountvisible_checked'	=> $user_info['phpgwaccountvisible'] == '-1' ? 'CHECKED' : '',

				// Corporative Information
				'corporative_information_employeenumber' => $user_info['corporative_information_employeenumber'],
				'corporative_information_birthdate'		=> $user_info['birthdate'],
				'corporative_information_cpf'			=> $user_info['corporative_information_cpf'],
				'corporative_information_rg'			=> $user_info['corporative_information_rg'],
				'corporative_information_rguf'			=> $user_info['corporative_information_rguf'],
				'corporative_information_description'	=> $user_info['corporative_information_description'],
				
				// MIGRATE MAILBOX
				'isMigrateMB'					=> $isMigrateMB,
				'disabled_is_migrate'			=> ( ( $isMigrateMB ) ? "readonly='readonly'" : ''),
				'disabled_button_is_migrate'	=> ( ( $isMigrateMB ) ? "alert('".lang('The mailbox is being migrated wait')."')" : "javascript:empty_inbox(uid.value);"),
				'display_msg_migrate_mailbox'	=> ( ( $isMigrateMB ) ? "block" : "none" ),		
				'migrate_mailbox'				=> lang("The mailbox is being migrated wait"),
				'fields_mail_bocked'			=> lang("Fields blocked"),
				'profile_msg_lost_share'		=> $user_info['hasLostShare']? lang( 'There are shares that will be lost when moving the mailbox between servers' ) : '',

				//MAIL
				'mail'									=> $user_info['mail'],
				'profile_descr'							=> $user_info['mailbox_profile_descr'],
				'profile_id'							=> $has_profile? $user_info['mailbox_profile_id'] : '',
				'accountstatus_checked'					=> $user_info['accountstatus'] == 'active' || !$has_profile ? 'CHECKED' : '',
				'input_mailalternateaddress_fields'		=> $input_mailalternateaddress_fields,
				'input_mailforwardingaddress_fields'	=> $input_mailforwardingaddress_fields,
				'deliverymode_checked'					=> $user_info['deliverymode'] == 'forwardOnly' ? 'CHECKED' : '',
				
				'display_quota'				=> isset($user_info['mailbox_error']) ? 'none' : '',
				'mailquota'					=> $user_info['mailquota'] == '-1' ? '' : $user_info['mailquota'],
				
				'display_quota_used'		=> isset($user_info['mailbox_error']) ? 'none' : '',
				'mailquota_used'			=> $user_info['mailquota_used'] == '-1' ? lang('without quota') : $user_info['mailquota_used'],
				
				'display_empty_user_inbox'	=> $has_profile && !isset($user_info['mailbox_error']) ? '' : 'none',
				'display_create_user_inbox'	=> $has_profile && $user_info['mailbox_error'] == 'quotaNotFound' ? '' : 'none',
				'display_fail_user_inbox'	=> $user_info['mailbox_error'] == 'couldntOpenStream' ? '' : 'none',
				
				//Third ABA
				'ea_select_user_groups_options'	=> $ea_select_user_groups_options,
				'ea_combo_primary_user_group_options'	=> $ea_combo_primary_user_group_options,
				
				//Fourd ABA
				'ea_select_user_maillists_options'  => $ea_select_user_maillists_options,
								
				//Five ABA
				'apps'	=> $apps,

				//SAMBA ABA
				'userSamba'                      => $user_info[ 'sambaUser' ],
				'sambalogonscript'               => $user_info[ 'sambalogonscript' ],
				'sambahomedirectory'             => $user_info[ 'homedirectory' ],
				'loginshell'                     => $user_info[ 'loginshell' ],
				'sambadomainname_options'        => $sambadomainname_options,
				'use_attrs_samba_checked'        => ( $user_info[ 'sambaUser' ] ) ? 'CHECKED' : '',
				'active_user_selected'           => ( $user_info[ 'sambaaccflags' ] == '[U          ]' ) ? 'selected' : '',
				'desactive_user_selected'        => ( $user_info[ 'sambaaccflags' ] == '[DU         ]' ) ? 'selected' : '',
				'defaultLogonScript'             => $this->current_config[ 'expressoAdmin_defaultLogonScript'],
				'use_suggestion_in_logon_script' => $this->current_config[ 'expressoAdmin_defaultLogonScript'] == '' ? 'true' : 'false',

				// RADIUS
				'radius_options' => $radius_options,
				'user_radius_options' => $user_radius_options
			);

			$t->set_var($var);
			$t->set_var($this->functions->make_dinamic_lang($t, 'main'));
			
			// Should we show SAMBA tab SAMBA ??
			if ( ($this->current_config['expressoAdmin_samba_support'] == 'true') && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_SAMBA_ATTRIBUTES )) )
				$t->set_var('display_samba_suport', '');
			else
				$t->set_var('display_samba_suport', 'none');
			
			// Is Radius enabled and has the manager privileges to it?
			if ( $radius_conf->enabled && ($this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_RADIUS )) )
				$t->set_var('display_radius_suport', '');
			else
				$t->set_var('display_radius_suport', 'none');
			
			$t->pfp('out','body');			
		}
		
		function row_action($action,$type,$account_id)
		{
			return '<a href="'.$GLOBALS['phpgw']->link('/index.php',Array(
				'menuaction' => 'expressoAdmin1_2.uiaccounts.'.$action.'_'.$type,
				'account_id' => $account_id
			)).'"> '.lang($action).' </a>';
		}

		function css()
		{
			$appCSS = 
			'th.activetab
			{
				color:#000000;
				background-color:#D3DCE3;
				border-top-width : 1px;
				border-top-style : solid;
				border-top-color : Black;
				border-left-width : 1px;
				border-left-style : solid;
				border-left-color : Black;
				border-right-width : 1px;
				border-right-style : solid;
				border-right-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;
			}
			
			th.inactivetab
			{
				color:#000000;
				background-color:#E8F0F0;
				border-bottom-width : 1px;
				border-bottom-style : solid;
				border-bottom-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;				
			}
			
			.td_left {border-left:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			.td_right {border-right:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			
			div.activetab{ display:inline; }
			div.inactivetab{ display:none; }';
			
			return $appCSS;
		}

		function show_photo()
		{
			if( isset( $_GET['uidNumber']) )
			{
				$photo = $this->get_photo( $_GET['uidNumber'] ); 

				if( trim($photo) !== "" && $photo )
				{	
					header( "Content-Type: image/jpeg" );

					$image = imagecreatefromstring( $photo );

					$smallPhoto = imagecreatetruecolor ( 80, 106 );

					imagecopyresampled( $smallPhoto, $image, 0, 0, 0, 0, 80 , 106 , imagesx($image), imagesy($image) );

					imagejpeg( $smallPhoto ); 
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		
		function get_photo($uidNumber)
		{
			$ldap_conn	= $GLOBALS['phpgw']->common->ldapConnect();
			$filter		= "(&(phpgwAccountType=u)(uidNumber=".$uidNumber."))";
			$justthese	= array("jpegphoto");
			$search 	= ldap_search($ldap_conn, $GLOBALS['phpgw_info']['server']['ldap_context'], $filter, $justthese);
			$entry 		= ldap_first_entry($ldap_conn, $search);
			$jpeg_data 	= ldap_get_values_len($ldap_conn, $entry, "jpegphoto");
			
			return ( isset( $jpeg_data[0] ) ? $jpeg_data[0] : false );
		}
		
		function show_access_log()
		{	
			$account_id = $_GET['account_id']; 
			
			$manager_account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$tmp = $this->functions->read_acl($manager_account_lid);
			$manager_context = $tmp[0]['context'];
			
			// Verifica se tem acesso a este modulo
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && (!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PASSWORD )))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
			}

			// Seta header.
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);

			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Access Log');
			$GLOBALS['phpgw']->common->phpgw_header();

			// Seta templates.
			$t = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$t->set_file(array("body" => "accesslog.tpl"));
			$t->set_block('body','main');
			$t->set_block('body','row','row');

			// GET access log from the user.
			$GLOBALS['phpgw']->db->limit_query("select loginid,ip,li,lo,account_id,sessionid from phpgw_access_log WHERE account_id=".$account_id." order by li desc",$start,__LINE__,__FILE__);
			while ($GLOBALS['phpgw']->db->next_record())
			{
				$records[] = array(
					'loginid'    => $GLOBALS['phpgw']->db->f('loginid'),
					'ip'         => $GLOBALS['phpgw']->db->f('ip'),
					'li'         => $GLOBALS['phpgw']->db->f('li'),
					'lo'         => $GLOBALS['phpgw']->db->f('lo'),
					'account_id' => $GLOBALS['phpgw']->db->f('account_id'),
					'sessionid'  => $GLOBALS['phpgw']->db->f('sessionid')
				);
			}

			// Seta as vcariaveis
			while (is_array($records) && list(,$record) = each($records))
			{
				$var = array(
					'row_loginid' => $record['loginid'],
					'row_ip'      => $record['ip'],
					'row_li'      => date("d/m/Y - H:i:s", $record['li']),
					'row_lo'      => $record['lo'] == 0 ? 0 : date("d/m/Y - H:i:s", $record['lo'])
				);
				$t->set_var($var);
				$t->fp('rows','row',True);
			}

			$var = Array(
				'th_bg'			=> $GLOBALS['phpgw_info']['theme']['th_bg'],
				'back_url'		=> "./index.php?menuaction=expressoAdmin1_2.uiaccounts.edit_user&account_id=$account_id",
			);
			$t->set_var($var);
			$t->set_var($this->functions->make_dinamic_lang($t, 'body'));
			$t->pfp('out','body');
		}
	}
?>
