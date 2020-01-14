<?php
	/**********************************************************************************\
	* Expresso Administra��o                 									      *
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
	require_once( PHPGW_API_INC.'/class.eventws.inc.php' );
	
	class group
	{
		var $ldap_functions;
		var $db_functions;
		var $imap_functions;
		var $functions;
		var $current_config;
		
		function group()
		{
			/*
			if (!ini_get('session.auto_start'))
			{
				session_name('sessionid');
				session_start();
			}
			*/
			
			$this->ldap_functions = new ldap_functions;
			$this->db_functions = new db_functions;
			$this->imap_functions = new imap_functions;
			$this->functions = new functions;
			$this->current_config = $_SESSION['phpgw_info']['expresso']['expressoAdmin'];
		}
		
		function validate_fields($params)
		{
			return $this->ldap_functions->validate_fields_group($params);
		}

		function create( $params )
		{
			// Verifica o acesso do gerente
			if ( !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_ADD_GROUPS ) )
				return array( 'status' => false, 'msg' => lang( 'You do not have access to create new groups' ).'.' );
			
			// Trim all fields
			array_walk_recursive( $params, function( &$str ){ $str = trim( $str ); } );
			
			$result = $this->ldap_functions->validate_fields_group2( array( 'attributes' => serialize( array_merge( $params, array( 'type' => 'create_group' ) ) ) ) );
			if ( !( isset( $result['status'] ) && $result['status'] ) ) return $result;
			
			$cn              = $params['cn'];
			$dn              = 'cn='.$cn.','.$params['context'];
			$isPhpgwAccount  = !isset( $params['grp_of_names'] );
			$isUniqueMembers = isset( $params['grp_of_names_type'] ) && $params['grp_of_names_type'] === 'groupOfUniqueNames';
			$member_attr     = $isPhpgwAccount? 'memberUid' : ( $isUniqueMembers? 'uniqueMember' : 'member' );
			
			// Cria array para incluir no LDAP
			$group_info = array(
				'objectClass' => array(
					'top',
					$isPhpgwAccount? 'posixGroup' : 'groupOf'.( $isUniqueMembers? 'Unique' : '' ).'Names',
				),
				'cn'          => $cn,
				'description' => utf8_encode( $params['description'] ),
			);
			
			if ( isset( $params['members'] ) && is_array( $params['members'] ) && count( $params['members'] ) ) {
				$uidnumber2method         = $isPhpgwAccount? 'uidnumber2uid' : 'uidnumber2dn';
				$group_info[$member_attr] = $members = $members_dn = array();
				foreach ( array_unique( $params['members'] ) as $uidnumber ) {
					$group_info[$member_attr][] = $members[$uidnumber] = $this->ldap_functions->$uidnumber2method( $uidnumber );
					$members_dn[] = $isPhpgwAccount? $this->ldap_functions->uidnumber2dn( $uidnumber ) : $members[$uidnumber];
				}
			} else if ( !$isPhpgwAccount ) $group_info[$member_attr] = null;
			
			if ( $isPhpgwAccount ) {
				
				// Leio o ID a ser usado na criacao do objecto.
				$result = $this->db_functions->get_next_id();
				if ( !( is_numeric( $result['id'] ) && $result['status'] ) )
					return array( 'status' => false, 'msg' => lang( 'Problems getting group ID' ).':'.$result['msg'] );
				
				$group_info['gidNumber'] = $id     = $result['id'];
				$group_info['objectClass'][]       = 'phpgwAccount';
				$group_info['phpgwAccountExpires'] = '-1';
				$group_info['phpgwAccountType']    = 'g';
				$group_info['userPassword']        = '';
				
				if ( isset( $params['email'] ) && $params['email'] != '' ) $group_info['mail']                   = $params['email'];
				if ( isset( $params['phpgwaccountvisible'] )             ) $group_info['phpgwaccountvisible']    = '-1';
				if ( isset( $params['accountrestrictive'] )              ) $group_info['accountrestrictive']     = 'mailListRestriction';
				if ( isset( $params['participantcansendmail'] )          ) $group_info['participantcansendmail'] = 'TRUE';
				
				// Suporte ao SAMBA
				if ( ( $this->current_config['expressoAdmin_samba_support'] == 'true' ) && isset( $params['use_attrs_samba'] ) ) {
					$group_info['objectClass'][]  = 'sambaGroupMapping';
					$group_info['sambaSID']       = $params['sambasid'].'-'.( ( $id * 2 ) + 1001 );
					$group_info['sambaGroupType'] = '2';
				}
				
				// Sending Control Mail
				if ( isset( $params['members_scm'] ) && is_array( $params['members_scm'] ) && count( $params['members_scm'] ) ) {
					$group_info['mailsenderaddress'] = array();
					foreach ( array_unique( $params['members_scm'] ) as $uidnumber )
						$group_info['mailsenderaddress'][] = $this->ldap_functions->uidnumber2mail( $uidnumber );
				}
			}
			
			$result = $this->ldap_functions->ldap_add_entry( $dn, $group_info );
			if ( !( isset( $result['status'] ) && $result['status'] ) ) {
				return array( 'status' => false,
					'msg' => ( $result['error_number'] !== 65 ) ? $result['msg'] :
						lang( 'It was not possible create the group because the LDAP schemas are not update' )."\n".
						lang( 'The administrator must update the directory /etc/ldap/schema/ and re-start LDAP' )."\n".
						lang( 'A updated version of these files can be found here' ).":\n".
						'www.expressolivre.org -> Downloads -> schema.tgz',
				);
			}
			
			if ( $isPhpgwAccount ) {
				
				// Sending control mail log
				if ( isset( $group_info['mailsenderaddress'] ) )
					foreach ( $group_info['mailsenderaddress'] as $mail )
						$this->db_functions->write_log( 'Allowed user to send e-mail to group', $cn.': '.$mail );
				
				// Save personal acl on database
				if ( isset( $params['acl_block_personal_data'] ) && is_array( $params['acl_block_personal_data'] ) && count( $params['acl_block_personal_data'] ) )
					$this->db_functions->save_acl_personal_data( $id, array_reduce( $params['acl_block_personal_data'], function( $a, $b ) { return $a |= intval( $b ); } ), 'add' );
				
				// Chama funcao para incluir os aplicativos ao grupo
				if ( isset( $params['apps'] ) && is_array( $params['apps'] ) && count( $params['apps'] ) )
					$this->db_functions->add_id2apps( $id, $params['apps'] );
				
				// Save group members on database
				if ( isset( $members ) ) {
					foreach ( $members as $uidnumber => $uid ) {
						$this->db_functions->add_user2group( $id, $uidnumber );
						$this->db_functions->write_log( 'Added user to group on group criation', $cn.': '.$uid );
					}
				}
			}
			
			$this->db_functions->write_log( 'Created group', $dn );
			EventWS::getInstance()->send( 'group_created', $dn, $group_info );
			if ( isset( $members_dn ) )
				foreach ( $members_dn as $user_dn )
					EventWS::getInstance()->send( 'user_group_in', $user_dn, array( 'groups' => array( $dn ) ) );
			return array( 'status' => true );
		}

		function save( $new_values )
		{
			// Check manager access
			if ( !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_GROUPS ) )
				return array( 'status' => false, 'msg' => lang( 'You do not have access to edit groups' ).'.' );
			
			$has_change = array();
			
			// Check valid DN
			$dn = $new_values['dn'];
			if ( ( $group_type = $this->get_type( $dn ) ) === false )
				return array( 'status' => false, 'msg' => lang( 'Object not found' ).': '.$dn );
			
			// Trim all fields
			array_walk_recursive( $new_values, function( &$str ){ $str = trim( $str ); } );
			
			$isPhpgwAccount  = $group_type['type'] === 0;
			$member_attr     = $isPhpgwAccount? 'memberUid' : ( $group_type['type'] === 2? 'uniqueMember' : 'member' );
			$old_values      = $isPhpgwAccount? $this->get_info( $group_type['gidnumber'] ) : $this->get_info_groupOfNames( $dn );
			$diff            = array_diff( $new_values, $old_values );
			
			if ( $isPhpgwAccount && isset( $diff['email'] ) && ( !empty( $new_values['email'] ) &&
				ldap_count_entries( $this->ldap_functions->ldap,
					ldap_search( $this->ldap_functions->ldap, $GLOBALS['phpgw_info']['server']['ldap_context'], '(mail='.$new_values['email'].')', array() )
				) > 0
			) ) return array( 'status' => false, 'msg' => lang( 'E-mail already in use' ).'.' );
			
			/**
			 * Rename group and/or move group in directory
			 */
			if ( ( strcasecmp( $old_values['cn'], $new_values['cn'] ) != 0 ) || ( strcasecmp( $old_values['context'], $new_values['context'] ) != 0 ) ) {
				$result = $this->ldap_functions->change_user_context( $dn, 'cn='.$new_values['cn'], $new_values['context'] );
				if ( !( isset( $result['status'] ) && $result['status'] ) )
					return array( 'status' => false, 'msg' => $result['msg'] );
				$old_dn = $dn;
				$dn = 'cn='.$new_values['cn'].','.$new_values['context'];
				$this->db_functions->write_log( 'Renamed group', $old_values['cn'].' -> '.$dn );
				EventWS::getInstance()->send( 'group_renamed', $dn, array( 'old_dn' => $old_dn ) );
			}
			
			//==========================================================================================================
			//= EDIT ATTRIBUTES ========================================================================================
			//==========================================================================================================
			
			$ldap_mod_add = $ldap_mod_del = $ldap_mod_replace = array();
			
			/**
			 * Description changes
			 */
			if ( $new_values['description'] != $old_values['description'] ) {
				$ldap_mod_replace['description'] = utf8_encode( $new_values['description'] );
				$this->db_functions->write_log( 'modified group description', $dn.': '.$old_values['description'].'->'.$new_values['description'] );
			}
			
			/**
			 * Members changes
			 */
			// Normalize members arrays
			$new_members = array_unique( (array)$new_values['members'] );
			$old_members = array_unique( (array)array_map( function( $n ) { return $n['uidnumber']; }, $old_values['memberuid_info'] ) );
			sort( $new_members );
			sort( $old_members );
			if ( $new_members !== $old_members ) {
				$uidnumber2method = $isPhpgwAccount? 'uidnumber2uid' : 'uidnumber2dn';
				
				// Make add member array
				$add_diff = array_diff( $new_members, $old_members );
				if ( count( $add_diff ) ) {
					$add_members = array();
					foreach ( $add_diff as $uidnumber )
						$add_members[$uidnumber] = $this->ldap_functions->$uidnumber2method( $uidnumber );
					// If the member is empty, use mod replace instead mod add
					if ( count( $old_members ) ) $ldap_mod_add[$member_attr] = array_values( $add_members );
					else $ldap_mod_replace[$member_attr] = array_values( $add_members );
				}
				
				// Make remove member array
				$rem_diff = array_diff( $old_members, $new_members );
				if ( count( $rem_diff ) ) {
					$rem_members = array();
					foreach ( $rem_diff as $uidnumber )
						$rem_members[$uidnumber] = $this->ldap_functions->$uidnumber2method( $uidnumber );
					// If the member is empty, use mod replace instead mod del
					if ( count( $new_members ) ) $ldap_mod_del[$member_attr] = array_values( $rem_members );
					else $ldap_mod_replace[$member_attr] = $isPhpgwAccount? array() : null;
				}
			}
			
			if ( $isPhpgwAccount ) {
				
				// Edit email ------------------------------------------------------------------------------------------
				if (
					$new_values['email'] != $old_values['email'] &&
					$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_MOD_GROUPS_EMAIL )
				) $ldap_mod_replace['mail'] = ( empty( $new_values['email'] ) )? array() : $new_values['email'];
				
				// Edit SAMBA ------------------------------------------------------------------------------------------
				if (
					// Check config is enabled
					$this->current_config['expressoAdmin_samba_support'] == 'true' &&
					(
						// Check sambaSID changed
						( $chgSID = ( isset( $new_values['sambasid'] ) && isset( $old_values['sambasid'] ) && $new_values['sambasid'] != $old_values['sambasid'] ) ) ||
						// Check use samba attributes chenged
						( isset( $new_values['use_attrs_samba'] ) !== isset( $old_values['sambasid'] ) )
					)
				) {
					$sambaSID = isset( $new_values['sambasid'] )? $new_values['sambasid'].'-'.( ( 2 * $new_values['gidnumber'] ) + 1001 ) : false;
					// Change SID
					if ( $chgSID ) $ldap_mod_replace['sambaSID'] = $sambaSID;
					else {
						if ( isset( $new_values['use_attrs_samba'] ) ) {
							// Enable SAMBA
							$ldap_mod_add['objectClass'][]  = 'sambaGroupMapping';
							$ldap_mod_add['sambaSID']       = $sambaSID;
							$ldap_mod_add['sambaGroupType'] = '2';
						} else {
							// Disable SAMBA
							$ldap_mod_del['objectClass'][]  = 'sambaGroupMapping';
							$ldap_mod_del['sambaSID']       = array();
							$ldap_mod_del['sambaGroupType'] = array();
						}
					}
				}
				
				$checkboxes = array(
					'phpgwAccountVisible'    => '-1',
					'accountrestrictive'     => 'mailListRestriction',
					'participantcansendmail' => 'TRUE',
				);
				foreach ( $checkboxes as $key => $value )
					if ( isset( $new_values[strtolower( $key )] ) === is_null( $old_values[strtolower( $key )] ) )
						$ldap_mod_replace[$key] = isset( $new_values[strtolower( $key )] )? $value : array();
				
				/**
				 * Members scm changes
				 */
				$new_members_scm = array_unique( isset( $new_values['members_scm'] )? (array)$new_values['members_scm'] : array() );
				$old_members_scm = array_unique( (array)array_map( function( $n ) { return $n['uidnumber']; }, $old_values['memberuid_scm_info'] ) );
				sort( $new_members_scm );
				sort( $old_members_scm );
				if ( $new_members_scm !== $old_members_scm ) {
					
					// Make add member scm array
					$add_diff = array_diff( $new_members_scm, $old_members_scm );
					if ( count( $add_diff ) ) {
						$add_members_scm = array();
						foreach ( $add_diff as $uidnumber )
							$add_members_scm[$uidnumber] = $this->ldap_functions->uidnumber2mail( $uidnumber );
						// If the member is empty, use mod replace instead mod add
						if ( count( $old_members_scm ) ) $ldap_mod_add['mailsenderaddress'] = array_values( $add_members_scm );
						else $ldap_mod_replace['mailsenderaddress'] = array_values( $add_members_scm );
					}
					
					// Make remove member scm array
					$rem_diff = array_diff( $old_members_scm, $new_members_scm );
					if ( count( $rem_diff ) ) {
						$rem_members_scm = array();
						foreach ( $rem_diff as $uidnumber )
							$rem_members_scm[$uidnumber] = $this->ldap_functions->uidnumber2mail( $uidnumber );
						// If the member is empty, use mod replace instead mod del
						if ( count( $new_members_scm ) ) $ldap_mod_del['mailsenderaddress'] = array_values( $rem_members_scm );
						else $ldap_mod_replace['mailsenderaddress'] = array();
					}
				}
			}
			//==========================================================================================================
			//= LDAP COMMIT ============================================================================================
			//==========================================================================================================
			/**
			 * Call LDAP mod add
			 */
			$r_status = true;
			if ( $r_status && count( $ldap_mod_add ) ) {
				$result = $this->ldap_functions->add_user_attributes( $dn, $ldap_mod_add );
				if ( $r_status = ( isset( $result['status'] ) && $result['status'] ) ) {
					
					if ( isset( $ldap_mod_add[$member_attr] ) ) {
						foreach ( $add_members as $uidnumber => $user ) {
							if ( $isPhpgwAccount ) $this->db_functions->add_user2group( $new_values['gidnumber'], $uidnumber );
							$this->db_functions->write_log( 'included user to group', $dn.': '.$user );
							EventWS::getInstance()->send( 'user_group_in', $this->ldap_functions->uidnumber2dn( $uidnumber ), array( 'groups' => array( $dn ) ) );
						}
						unset( $ldap_mod_add[$member_attr] );
					}
					
					if ( isset( $ldap_mod_add['mailsenderaddress'] ) )
						foreach ( $add_members_scm as $uidnumber => $user )
							$this->db_functions->write_log( 'included user scm to group', $dn.': '.$user );
					
					if ( isset( $ldap_mod_add['objectClass'] ) && in_array( 'sambaGroupMapping', $ldap_mod_add['objectClass'] ) )
						$this->db_functions->write_log( 'Added samba attibutes to group', $dn.': '.$new_values['sambasid'] );
					
					if ( count( $ldap_mod_add ) ) $has_change['ldap_add'] = $ldap_mod_add;
				}
			}
			
			/**
			 * Call LDAP mod del
			 */
			if ( $r_status && count( $ldap_mod_del ) ) {
				$result = $this->ldap_functions->remove_user_attributes( $dn, $ldap_mod_del );
				if ( $r_status = ( isset( $result['status'] ) && $result['status'] ) ) {
					
					if ( isset( $ldap_mod_del[$member_attr] ) ) {
						foreach ( $rem_members as $uidnumber => $user ) {
							if ( $isPhpgwAccount ) $this->db_functions->remove_user2group( $new_values['gidnumber'], $uidnumber );
							$this->db_functions->write_log( 'removed user from group', $dn.': '.$user );
							EventWS::getInstance()->send( 'user_group_out', $this->ldap_functions->uidnumber2dn( $uidnumber ), array( 'groups' => array( $dn ) ) );
						}
						unset( $ldap_mod_del[$member_attr] );
					}
					
					if ( isset( $ldap_mod_del['mailsenderaddress'] ) )
						foreach ( $rem_members_scm as $uidnumber => $user )
							$this->db_functions->write_log( 'removed user scm from group', $dn.': '.$user );
					
					if ( isset( $ldap_mod_del['objectClass'] ) && in_array( 'sambaGroupMapping', $ldap_mod_del['objectClass'] ) )
						$this->db_functions->write_log( 'removed group samba attributes', $dn );
					
					if ( count( $ldap_mod_del) ) {
						$has_change['ldap_remove']  = $ldap_mod_del;
						$has_change['old_sambaSID'] = $old_values['sambasid'].'-'.( ( 2 * $old_values['gidnumber'] ) + 1001 );
					}
				}
			}
			
			/**
			 * Call LDAP mod replace
			 */
			if ( $r_status && count( $ldap_mod_replace ) ) {
				$result = $this->ldap_functions->replace_user_attributes( $dn, $ldap_mod_replace );
				$r_status = ( isset( $result['status'] ) && $result['status'] );
				$has_change['ldap_mod_replace'] = $ldap_mod_replace;
			}
			
			//==========================================================================================================
			//==========================================================================================================
			//==========================================================================================================
			
			/**
			 * Check error message
			 */
			if ( !$r_status ) {
				return array( 'status' => false,
					'msg' => ( $result['error_number'] !== 65 ) ? $result['msg'] :
						lang( 'It was not possible create the group because the LDAP schemas are not update' )."\n".
						lang( 'The administrator must update the directory /etc/ldap/schema/ and re-start LDAP' )."\n".
						lang( 'A updated version of these files can be found here' ).":\n".
						'www.expressolivre.org -> Downloads -> schema.tgz',
				);
			}
			
			/**
			 * Log messages from mod replace
			 */
			if ( isset( $ldap_mod_replace[$member_attr] ) ) {
				
				foreach ( $add_members as $user ) {
					if ( $isPhpgwAccount ) $this->db_functions->add_user2group( $new_values['gidnumber'], $uidnumber );
					$this->db_functions->write_log( 'included user to group', $dn.': '.$user );
				}
				
				foreach ( $rem_members as $user ) {
					if ( $isPhpgwAccount ) $this->db_functions->remove_user2group( $new_values['gidnumber'], $uidnumber );
					$this->db_functions->write_log( 'removed user from group', $dn.': '.$user );
				}
			}
			
			if ( $isPhpgwAccount ) {
				
				if ( isset( $ldap_mod_replace['mail'] ) ) {
					if ( empty( $old_values['email'] ) ) $this->db_functions->write_log( 'added attribute mail to group', $dn );
					else if ( empty( $new_values['email'] ) ) $this->db_functions->write_log( 'removed attribute mail from group', $dn );
					else $this->db_functions->write_log( 'modified group email', $dn.': '.$old_values['email'].' -> '.$new_values['email'] );
				}
				
				if ( isset( $ldap_mod_replace['sambaSID'] ) )
					$this->db_functions->write_log( 'modified group samba domain', $dn.': '.$old_values['sambasid'].' -> '.$new_values['sambasid'] );
				
				foreach ( $checkboxes as $key => $value )
					if ( isset( $ldap_mod_replace[$key] ) )
						$this->db_functions->write_log( 'changed attribute '.$key.' to group', $dn.': '.( $ldap_mod_replace[$key] == $value? 'enabled' : 'disabled' ) );
				
				// Change ACL personal fields on database
				$old_acl = isset( $old_values['acl_block_personal_data'] )? intval( $old_values['acl_block_personal_data'] ) : 0;
				$new_acl = array_reduce(
					isset( $new_values['acl_block_personal_data'] )? (array)$new_values['acl_block_personal_data'] : array(),
					function( $i, $v ){ return $i |= $v; }, 0
				);
				if ( $new_acl != $old_acl ) {
					$this->db_functions->save_acl_personal_data( $new_values['gidnumber'], $new_acl, $old_acl? ( $new_acl? '' : 'remove' ) : 'add' ) ;
					$this->db_functions->write_log( 'changed ACL block personal data to group', $dn.': '.$old_acl.' -> '.$new_acl );
				}
				
				// Change applications on database
				$manager_apps = array_keys( $this->db_functions->get_apps( $_SESSION['phpgw_session']['session_lid'] ) );
				$new_values['apps'] = array_intersect( array_keys( isset( $new_values['apps'] )? $new_values['apps'] : array() ), $manager_apps );
				$old_values['apps'] = array_intersect( array_keys( isset( $old_values['apps'] )? $old_values['apps'] : array() ), $manager_apps );
				sort( $new_values['apps'] );
				sort( $old_values['apps'] );
				if ( $new_values['apps'] != $old_values['apps'] ) {
					
					// Add applications
					$add_apps = array_diff( $new_values['apps'], $old_values['apps'] );
					$this->db_functions->add_id2apps( $new_values['gidnumber'], array_flip( $add_apps ) );
					
					// Remove applications
					$rem_apps = array_diff( $old_values['apps'], $new_values['apps'] );
					$this->db_functions->remove_id2apps( $new_values['gidnumber'], array_flip( $rem_apps ) );
				}
				
			}
			if ( count( $has_change ) ) EventWS::getInstance()->send( 'group_changed', $dn, $has_change );
			return array( 'status' => true );
		}

		function get_type( $dn )
		{
			return $this->ldap_functions->get_group_type( $dn );
		}

		function get_info_groupOfNames( $dn )
		{
			$entry = $this->ldap_functions->get_object( $dn );
			$members = isset( $entry[0]['member'] )? $entry[0]['member'] : ( isset( $entry[0]['uniquemember'] )? $entry[0]['uniquemember'] : array() );
			array_walk( $members, function( &$it ){ $it = array_pop( explode( '=', array_shift( explode( ',', $it ) ) ) ); } );
			$result['cn']             = $entry[0]['cn'][0];
			$result['context']        = implode( ',', array_splice( explode( ',', $dn ), 1 ) );
			$result['description']    = utf8_decode( $entry[0]['description'][0] );
			$result['memberuid_info'] = $this->ldap_functions->get_group_members( $members );
			return $result;
		}

		function get_info($gidnumber)
		{
			$group_info = $this->ldap_functions->get_group_info($gidnumber);
			return $group_info;
		}

		function delete($params)
		{
			// Verifica o acesso do gerente
			if (!$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_DEL_GROUPS ))
			{
				$return['status'] = false;
				$return['msg'] = lang('You do not have acces to remove groups') . '.';
				return $return;
			}
			
			$return['status'] = true;
			
			$dn = base64_decode( $params['id'] );
			
			$group_type = $this->get_type( $dn );
			if ( $group_type['type'] === 0 ) {
				
				$info = $this->get_info( $group_type['gidnumber'] );
				
				//LDAP
				$result_ldap = $this->ldap_functions->delete_group( $group_type['gidnumber'] );
				if (!$result_ldap['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result_ldap['msg'];
				}
				
				//DB
				$result_db = $this->db_functions->delete_group( $group_type['gidnumber'] );
				if (!$result_db['status'])
				{
					$return['status'] = false;
					$return['msg'] .= $result_db['msg'];
				}
			} else {
				
				$info = $this->get_info_groupOfNames( $dn );
				
				//LDAP
				$result_ldap = $this->ldap_functions->delete_groupOfNames( $dn );
				if ( !$result_ldap['status'] )
					$return = array( 'status' => false, 'msg' => $result_ldap['msg'] );
			}
			
			if ( $return['status'] == true ) {
				$this->db_functions->write_log( 'deleted group', array_pop( explode( '=', array_shift( explode( ',', $dn ) ) ) ) );
				EventWS::getInstance()->send( 'group_deleted', $dn, $info );
			}
			
			return $return;
		}
		
		function copy( $params )
		{
			// Verifica o acesso do gerente
			if ( !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_ADD_GROUPS ) )
				return array( 'status' => false, 'msg' => lang( 'You do not have access to create new groups' ).'.' );
			
			// Check valid DN
			$dn = base64_decode( $params['gidnumber'] );
			if ( ( $group_type = $this->get_type( $dn ) ) === false )
				return array( 'status' => false, 'msg' => lang( 'Object not found' ).': '.$dn );
			
			$isPhpgwAccount  = $group_type['type'] === 0;
			$old_values      = $isPhpgwAccount? $this->get_info( $group_type['gidnumber'] ) : $this->get_info_groupOfNames( $dn );
			
			$old_cn                  = $old_values['cn'];
			$old_values['cn']        = trim( $params['cn'] );
			$old_values['gidnumber'] = '';
			$old_values['email']     = '';
			$old_values['members']   = array_unique( (array)array_map( function( $n ) { return $n['uidnumber']; }, $old_values['memberuid_info'] ) );
			
			if ( !$isPhpgwAccount ) {
				$old_values['grp_of_names']      = 'on';
				$old_values['grp_of_names_type'] = $group_type['type'] === 1? 'groupOfNames' : 'groupOfUniqueNames';
			}
			
			$result = $this->create( $old_values );
			if ( !$result['status'] ) $this->db_functions->write_log( 'Group copy FAILED. Creation.', 'From: '.$old_cn.' to '.$old_values['cn'].' ('.$result['msg'].')' );
			else $this->db_functions->write_log( 'Finished group copy.', 'From: '.$old_cn.' to '.$old_values['cn'] );
			
			return $result;
		}
	}
