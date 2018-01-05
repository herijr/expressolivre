<?php
	/***************************************************************************\
	* EGroupWare - EMailAdmin                                                   *
	* http://www.egroupware.org                                                 *
	* Written by : Lars Kneschke [lkneschke@egroupware.org]                     *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/

class ui
{
	var $boemailadmin;
	var $template;

	var $public_functions = array
	(
		'addDomains'			=> True,
		'addProfile'			=> True,
		'deleteProfile'			=> True,
		'deleteDomains'			=> True,
		'editProfile'			=> True,
		'getProfiles'			=> True,
		'listConfigurations'	=> True,			
		'listDomains'			=> True,		
		'listProfiles'			=> True,
		'moveDomain'			=> True,
		'saveProfile'			=> True
	);
	
	function ui()
	{
		$this->template			= CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
		$this->boemailadmin		= CreateObject('emailadmin.bo');
	}

	function addDomains()
	{
		if ( $_POST && isset( $_POST['domainid'] ) ) {
			
			$params = array(
				'domainid'  => trim( $_POST['domainid'] ),
				'ous'       => isset( $_POST['ous'] )? (array)$_POST['ous'] : array(),
				'extras'    => isset( $_POST['extras'] )? (array)$_POST['extras'] : array(),
				'action'    => 'edit',
			);
			
			$this->boemailadmin->saveDomains( $params );
			
			echo json_encode( array("return" => "edit_domain_ok") );
			
		} else if( $_POST && ( isset($_POST['domain']) && isset($_POST['profileid']))) {
			
			$params = array(
				'domain'    => strtolower(trim($_POST['domain'])),
				'profileid' => trim($_POST['profileid']),
				'ous'       => isset( $_POST['ous'] )? (array)$_POST['ous'] : array(),
				'extras'    => isset( $_POST['extras'] )? (array)$_POST['extras'] : array(),
				'action'    => 'add'
			);
			
			if(preg_match('/([a-zA-Z0-9\-_]+\.)?[a-zA-Z0-9\-_]+\.[a-zA-Z]{2,5}/',$params['domain'])) 
			{
				if( !$this->boemailadmin->getDomains( array( "field" => "domain", "value" => $params['domain'] ) ) )
				{	
					if( $params['profileid'] != "" )
					{	
						$this->boemailadmin->saveDomains( $params );

						if( !array_key_exists('return', $_POST) )
						{		
							$domains = $this->boemailadmin->getDomains( array( "field" => "profileid", "value" => $params['profileid'] ) );

							echo json_encode($domains);
						}
						else
							echo json_encode( array("return" => "add_domain_ok") );
					}
					else
					{
						if( !isset($_SESSION['table_emailadmin_domain']) )
						{	
							$_SESSION['table_emailadmin_domain'][] = array( "domain" => $params['domain'], "profileid" => "");

							echo json_encode( $_SESSION['table_emailadmin_domain'] );
						}
						else
						{
							$is_search = false;

							foreach($_SESSION['table_emailadmin_domain'] as $key => $value )
							{
								if( trim( $_POST['domain'] ) === $value['domain'] )
								{	
									$is_search = true;
								}
							}

							if( !$is_search )
							{
								$_SESSION['table_emailadmin_domain'][] = array( "domain" => $params['domain'], "profileid" => "" );

								echo json_encode( $_SESSION['table_emailadmin_domain'] );
							}
							else
							{
								echo json_encode( array( "error" => "add_domain_registered") );
							}
						}
					}
				}
				else
				{
					echo json_encode( array( "error" => "add_domain_registered") );
				}
			}
			else
			{
			    echo json_encode( array("error" => "domain_invalid") );
			}
		}
	}

	function deleteDomains()
	{
		if( $_POST )
		{
			if( isset( $_POST['domainid'] ) )
			{	
				$params = array(
					'domainid' 	=> $_POST['domainid'],
					'action'	=> 'delete'
				);

				$this->boemailadmin->saveDomains( $params );

				if( array_key_exists('return', $_POST ) )
				{	
					$domains = $this->boemailadmin->getDomains( array( "field"=> "profileid" , "value" => $_POST['profileid'] ) );

					echo json_encode($domains);
				}
				else
					echo json_encode( array( "result" => "true" ) );
			}
			elseif( isset( $_POST['domain'] ) )
			{
				$searchKey = '';

				foreach( $_SESSION['table_emailadmin_domain'] as $key => $value )
				{	
					if( trim( $_POST['domain']) === $value['domain'] )
					{	
						$searchKey = $key;
					}
				}

				unset( $_SESSION['table_emailadmin_domain'][$searchKey] );

				echo json_encode( $_SESSION['table_emailadmin_domain'] );
			}
		}
	}

	function addProfile()
	{
		// Header Page
		$GLOBALS['phpgw']->common->phpgw_header();
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang("Add new profile Server Express");
		echo parse_navbar();

		// Get Form Profile;
		$this->formProfile();

		$this->template->set_var(array(
			"action_url" 		=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.saveProfile'),
			"lang_save"			=> lang("add"),
			"link_back_page"	=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listProfiles'),
		));

		// Clear;
		if( isset($_SESSION['table_emailadmin_domain']) )
		{
			unset($_SESSION['table_emailadmin_domain'] );
		}	

		$this->template->parse("out","main");
		
		print $this->template->get('out','main');
	}

	function deleteProfile()
	{
		$this->boemailadmin->deleteProfile($_GET['profileid']);
		
		$this->listProfiles();
	}

	function editProfile()
	{
		// Get ProfileID
		$profileID = intval($_GET['profileid']);
		
		// Get Profiles
		$profileData = $this->boemailadmin->getProfile( 'id', $profileID );

		if( count($profileData) )
		{	
			// Header Page
			$GLOBALS['phpgw']->common->phpgw_header();
			$GLOBALS['phpgw_info']['flags']['app_header'] = lang('edit') . " - " . $profileData['description'];
			echo parse_navbar();
		
			// Get Form Profile;
			$this->formProfile();

			$this->template->set_var( array(
				"action_url" 		=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.saveProfile'),
				"link_back_page"	=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listProfiles'),
				"lang_save"			=> lang("save"),									
			));

			//Check Boxes
			$checkBoxes = array( 'imapenablecyrusadmin', 'imapenablesieve', 'imaptlsauthentication', 'imaptlsencryption',
				'smtpauth', 'smtpldapusedefault', 'imapoldcclient','imapcreatespamfolder');

			//Select Boxes
			$selectBoxes = array( 'imapdelimiter' );

			foreach($profileData as $key => $value)
			{
				if( array_search( strtolower($key), $checkBoxes) !== FALSE )
				{
					if( $value === 'yes' )
						$this->template->set_var('selected_'.strtolower($key),'checked="1"');
				}
				else if( array_search( strtolower($key), $selectBoxes ) !== FALSE )
				{
					$optionValue = 'selected_'.strtolower($key).'_dot';

					if( $value === "/" ){ $optionValue = 'selected_'.strtolower($key).'_slash'; }
											
					$this->template->set_var( $optionValue ,'selected="1"');
				}	
				else
				{
					$this->template->set_var('value_'.strtolower($key),$value);
				}
			}

			// Get Domains
			$domains = $this->boemailadmin->getDomains( array( "field" => "profileid", "value" => $profileID ) );

			$value_domains = "";

			foreach( $domains as $key => $value )
			{
				$value_domains .= '<tr>';
				$value_domains .= "<td>{$domains[$key]['domain']}</td>";
				$value_domains .= '<td class="td_size" menu_action="delete" domainid="'.$domains[$key]['domainid'].'">'.lang('delete').'</td>';
				$value_domains .= '</tr>';
			}

			$this->template->set_var( 'value_domains', $value_domains );

			$this->template->parse("out","main");

			print $this->template->get('out','main');
		}
	}
	
	function formProfile()
	{
		$this->template->set_file(array("body" => "profile.tpl"));
		$this->template->set_block('body','main');
		$this->template->set_var(array(
			"lang_Configuration_SMTP"		=> lang("Configuration for the SMTP service"),				
			"lang_Configuration_Cyrus_IMAP"		=> lang("Configuration for the service Cyrus IMAP"),
			"lang_cyrus_imap_administration"	=> lang('Cyrus IMAP server administration'),
			"lang_add_domain"				=> lang("Add domain"),
			"lang_admin_username" 			=> lang('admin username'),
			"lang_admin_password" 			=> lang('admin password'),
			"lang_back"						=> lang("back"),
			"lang_create_spam_folder"		=> lang('create spam folder'),				
			"lang_cyrus_imap_server" 		=> lang('cyrus imap server'),				
			"lang_cyrus_user_post_spam" 	=> lang('cyrus user post spam'),
			"lang_confirm_domain"			=> lang('Do you really want to delete this Domain'),		
			"lang_delete"					=> lang("Delete"),
			"lang_delimiter_imap"			=> lang("Delimiter of the IMAP server folders: (points to the logins, use / as delimiter.)"),	
			"lang_default_folders"			=> lang('Default Folders'),
			"lang_domain"					=> lang("Domain"),
			"lang_domains_assigned"			=> lang("Domains assigned"),
			"lang_domain_name"				=> lang("Domain name"),
			"lang_drafts_folder" 			=> lang('Drafts Folder'),
			"lang_edit"						=> lang("Edit"),
			"lang_erro_add_domain"			=> lang("Domain is already registered"),	
			"lang_enable_cyrus_imap_administration" => lang('enable Cyrus IMAP server administration'),							
			"lang_enable_sieve" 			=> lang('enable Sieve'),				
			"lang_imap_server_hostname_or_IP_address" => lang('IMAP server hostname or ip address'),
			"lang_imap_server_logintype"	=> lang("imap server logintype"),
			"lang_imap_server_port"			=> lang("imap server port"),
			"lang_invalid_domain"			=> lang('Invalid domain'),	
			"lang_LDAP_Settings"			=> lang('ldap settings'),
			"lang_LDAP_server_admin_dn" 	=> lang('LDAP server admin DN'),
			"lang_LDAP_server_admin_pw" 	=> lang('LDAP server admin password'),								
			"lang_LDAP_server_base_dn" 		=> lang('LDAP server accounts DN'),				
			"lang_LDAP_server_hostname_or_IP_address"	=> lang('LDAP server hostname or ip address'),
			"lang_sent_folder" 				=> lang('Sent Folder'),				
			"lang_sieve_server_hostname_or_ip_address" => lang('Sieve server hostname or ip address'),
			"lang_sieve_server_port" 		=> lang('Sieve server port'),
			"lang_sieve_settings" 			=> lang('Sieve settings'),
			"lang_imap_server"				=> lang("IMAP server"),
			"lang_spam_settings" 			=> lang('spam settings'),				
			"lang_SMTP_server_hostname_or_IP_address" => lang("SMTP-Server hostname or IP address"),
			"lang_postfix_ldap" 			=> lang("postfix with ldap"),
			"lang_pre_2001_c_client" 		=> lang('IMAP C-Client Version < 2001'),			
			"lang_profile_name"				=> lang("Profile name"),
			"lang_profile_name_blank"		=> lang("The PROFILE NAME is blank"),
			"lang_SMTP_Standard"			=> lang("SMTP Server Standard"),
			"lang_stmp_server_port" 		=> lang("smtp server port"),			
			"lang_standard"					=> lang("standard"),
			"lang_spam_folder" 				=> lang('Spam Folder'),
			"lang_trash_folder"				=> lang('Trash Folder'),								
			"lang_use_tls_authentication"	=> lang('use tls authentication'),
			"lang_use_tls_encryption" 		=> lang('use tls encryption'),
			"lang_Use_SMTP_auth" 			=> lang('Use SMTP auth'),
			"lang_use_LDAP_defaults" 		=> lang('use LDAP defaults'),
			"lang_user_defined_accounts"	=> lang("users can define their own emailaccounts"),
			"lang_vmailmgr" 				=> lang('Virtual MAIL ManaGeR'),				
			"lang_global" 					=> lang("Global"),
		));
	}

	function getProfiles()
	{
		$profileList = $this->boemailadmin->getProfileList();
		
		if( is_array( $profileList ) )
		{
			$return = array();
			
			foreach( $profileList as $key => $value )
			{
				$return['profiles'][] = array( "profileid" => $value['profileid'], "description" => $value['description'] );
			}
			
			$s = CreateObject('phpgwapi.sector_search_ldap');
			$return['organization_units'] = $s->get_organizations( $GLOBALS['phpgw_info']['server']['ldap_context'], '', false, true, false, true );
			
			echo json_encode( $return );
		}
		else
			echo json_encode( array( "error" => "no server mx" ) );
	}

	function listDomains()
	{
		// Header Page
		$GLOBALS['phpgw']->common->phpgw_header();
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Admin') .' - ' . lang('domain list');		
		echo parse_navbar();

		$this->template->set_file(array("body" => "listDomains.tpl"));
		
		$countDomains = "";

		if( $_POST['button_search_domain'] && $_POST['input_search_domain'])
		{	
			$search		= '%'.strtolower(trim($_POST['input_search_domain'])).'%';
			
			$domains	= $this->boemailadmin->getDomains( array("field" => "domain", "value" => $search, "operator" => "LIKE") );

			if( is_array( $domains ) )
			{
				foreach( $domains as $domain )
				{
				
					$profileData = $this->boemailadmin->getProfile( 'id', $domain['profileid'] );
					$ous = '';
					foreach ( (array)unserialize( $domain['organization_units'] ) as $ou ) if ( $ou ) $ous .= '<p>'.$ou.'</p>';
					$extras = '';
					foreach ( (array)unserialize( $domain['extras'] ) as $key => $val ) if ( $key )
						$extras.= '<p>'.lang( $key ).' = <q name="'.$key.'">'.$val.'</q></p>';

					$rowsTable .= '<tr data-id="'.$domain['domainid'].'">';
					$rowsTable .= '<td class="nowrap" name="description">'.$profileData['description'].'</td>';
					$rowsTable .= '<td class="nowrap" name="domain">'.$domain['domain'].'</td>';
					$rowsTable .= '<td menu_action="delete" domainid="'.$domain['domainid'].'" title="'.lang('delete').'">'.lang('delete').'</td>';
					$rowsTable .= '<td menu_action="move" domainid="'.$domain['domainid'].'" title="'.lang('move').'">'.lang('move').'</td>';
					$rowsTable .= '<td menu_action="edit" name="ous" title="'.lang('edit').'">'.( empty( $ous )?'-':$ous ).'</td>';
					$rowsTable .= '<td menu_action="edit" name="extras" title="'.lang('edit').'">'.( empty( $extras)?'-':$extras).'</td>';
					$rowsTable .= '</tr>';
				}

				$countDomains = count($domains);
			}
		}

		$this->template->set_var( array( 
			"action_url"			=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listDomains'),
			"countDomains"			=> $countDomains,
			"lang_add"				=> lang("add"),
			"lang_remove"				=> lang("remove"),
			"lang_added_domain"		=> lang("added domain"),
			"lang_edited_domain"        => lang("edited domain"),
			"lang_label_profile"        => lang("profile"),
			"lang_label_domain"         => lang("domain"),
			"lang_label_ous"            => lang("organizations"),
			"lang_admin_add_domain"		=> lang("Administrator - Add domain"),
			"lang_admin_move_domain"	=> lang("Administrator - Move domain"),
			"lang_back_page" 		=> lang("back"),
			"lang_close"			=> lang("close"),
			"lang_confirm_domain" 	=> lang("Do you really want to delete this Domain"),
			"lang_confirm_move_domain" => lang("Do you really want to move this Domain"),
			"lang_msg_move_domain"	=> lang("This operation will move all mailboxes in the domain"),
			"lang_delete"			=> lang("delete"),
			"lang_domain"			=> lang("domain"),
			"lang_domain_success"	=> lang("domain switched profile successfully"),		
			"lang_domains_found"	=> lang("domains found"),
			"lang_erro_add_domain"	=> lang("Domain is already registered"),		
			"lang_enter_domain"		=> lang("enter a domain"),
			"lang_move"				=> lang("move"),
			"lang_move_domain"		=> lang("move domain"),
			"lang_ou_list"			=> lang("organizations"),
			"lang_extras"			=> lang("Other settings"),
			"lang_key"				=> lang("key"),
			"lang_value"			=> lang("value"),
			"lang_save"				=> lang("save"),
			"lang_search"			=> lang("search"),
			"lang_search_domain"	=> lang("search domain"),
			"lang_profile"			=> lang("profile"),
			"link_back_page" 		=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listConfigurations'),
			"rowsTable"				=> $rowsTable,
			'extras_vars_keys_json' => preg_replace( '/^{/', '{ ', json_encode( array( 'defaultUserQuota' => utf8_encode( lang( 'defaultUserQuota' ) ) ) ) ),
		));	

		$this->template->set_block("body","main");

		$this->template->parse("out","main");
		
		print $this->template->get('out','main');
	}

	function listConfigurations()
	{
		// Header Page
		$GLOBALS['phpgw']->common->phpgw_header();
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Admin') .' - ' . lang('Site Configuration');
		echo parse_navbar();
		
		$this->template->set_file(array("body" => "listConfigurations.tpl"));
		$this->template->set_var( array( 
			"lang_back"				=> lang("back"),
			"lang_manage_domains"	=> lang("Manage Domains"),
			"lang_manage_profile"	=> lang("Manage Profile"),
			"lang_profile_server" 	=> lang("Profile Server"),
			"lang_server_mx"		=> lang("Server MX"),
			"link_back_page"		=> $GLOBALS['phpgw']->link('/admin/index.php'),				
			"link_profile_server"	=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listProfiles'),
			"link_server_mx"		=> $GLOBALS['phpgw']->link('/index.php','menuaction=emailadmin.ui.listDomains'),
		));

		$this->template->set_block("body","main");
		
		$this->template->parse("out","main");
		
		print $this->template->get('out','main');
	}

	function listProfiles()
	{
		// Header Page
		$GLOBALS['phpgw']->common->phpgw_header();
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Admin') .' - ' . lang('profile list');
		echo parse_navbar();
		
		$this->template->set_file(array("body" => "listProfiles.tpl"));
		$this->template->set_block('body','main');
		
		// Limits	
		$limit 		= 0;
		$previous	= 0;
		$next		= 0;

		if( isset($_GET['action']) )
		{
			if( $_GET['action'] == 'next' )
			{	
				$next = ( (int)($_GET['next']) + 5 );
				$limit = $next;
			}
			
			if( $_GET['action'] == 'previous' )
			{	
				$previuos = ( $_GET['previous'] == 0 ) ? 0 : (int)($_GET['previous'] - 5 );
				$limit = $previous;
			}
		}

		$profileList = $this->boemailadmin->getProfileList( $limit );

		// Create the data array
		if( $profileList )
		{
			foreach( $profileList as $key => $value )
			{
				$linkEdit 	= "menuaction=emailadmin.ui.editProfile&profileid=".$profileList[$key]['profileid'];
				$linkRemove	= "menuaction=emailadmin.ui.deleteProfile&profileid=".$profileList[$key]['profileid'];
				
				$rowsTable .= "<tr>";
				$rowsTable .= '<td>'.$profileList[$key]['description'].'</a></td>';
				$rowsTable .= '<td>'.$profileList[$key]['smtpserver'].'</a></td>';
				$rowsTable .= '<td>'.$profileList[$key]['imapserver'].'</a></td>';
				$rowsTable .= '<td class="td_list_profiles"><a href="'.$GLOBALS['phpgw']->link('/index.php',$linkEdit).'">'.lang('Edit').'</a></td>';
				$rowsTable .= '<td class="td_list_profiles"><a href="'.$GLOBALS['phpgw']->link('/index.php',$linkRemove).'" onClick="return confirm(\''.lang('Do you really want to delete this Profile').'\')">'.lang('delete').'</a></td>';
				$rowsTable .= "</tr>";
			}
		}

		// Create the table html code
		$this->template->set_var('rowsTable', $rowsTable );
		
		$this->template->set_var( array(
			'count_profileList'	=> ( ( $profileList ) ? count($profileList) : 0 ),
			'lang_add_profile'	=> lang('add profile'),
			'lang_back_page'	=> lang("Back"),
			'lang_Configuration_IMAP'	=> lang("Configuration for the service IMAP"),
			'lang_Configuration_SMTP'	=> lang("Configuration for the SMTP service"),
			'lang_description'	=> lang("description"),
			'lang_edit'			=> lang("edit"),
			'lang_previous'		=> lang("previous"),
			'lang_next'			=> lang("next"),
			'lang_remove'		=> lang("delete"),
			'link_add_new'		=> $GLOBALS['phpgw']->link('/index.php', 'menuaction=emailadmin.ui.addProfile'),
			'link_back_page'	=> $GLOBALS['phpgw']->link('/index.php', 'menuaction=emailadmin.ui.listConfigurations'),
			'link_previous'		=> $GLOBALS['phpgw']->link('/index.php', 'menuaction=emailadmin.ui.listProfiles&previous='.$previous.'&next='.$next.'&action=previous'),
			'link_next'			=> $GLOBALS['phpgw']->link('/index.php', 'menuaction=emailadmin.ui.listProfiles&previous='.$previous.'&next='.$next.'&action=next')
		));
		
		$this->template->parse("out","main");
		
		print $this->template->get('out','main');
	}
	
	function moveDomain()
	{
		if( $_POST && ( $_POST['domainid'] && $_POST['newprofileid'] ) ) 
		{
			$params = array( 'domainid' => $_POST['domainid'], 'newprofileid' => $_POST['newprofileid'] );

			$result = $this->boemailadmin->moveDomain( $params );

			echo json_encode( $result );
		}
	}

	function saveProfile()
	{
		// Save add/edit profile
		if( $_POST )
		{	
			$profile = $_POST;

			$profileid = $this->boemailadmin->saveProfile($profile);

			if( $profileid->fields[0] )
			{
				// Add domains if exist;
				if( isset( $_SESSION['table_emailadmin_domain'] ) && $profile['profileid'] == "" )
				{
					$params = array(
						'profileid'	=> trim($profileid->fields[0]),
						'action'	=> 'add'
					);

					foreach( $_SESSION['table_emailadmin_domain'] as $value)
					{
						$params['domains'][] = $value['domain'];
					}

					if( $this->boemailadmin->saveDomains($params) )
					{
						unset( $_SESSION['table_emailadmin_domain'] );
					}
				}
			}

			$this->listProfiles();
		}
	}
}

?>
