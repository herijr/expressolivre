<?php
	/**************************************************************************\
	* ExpressoLivre - preferences                                              *
	* http://www.celepar.pr.gov.br                                             *
	* Written by Joseph Engo <jengo@phpgroupware.org>                          *
	* Modify by João Alfredo Knopik Junior <jakjr@celepar.pr.gov.br>           *
	* 																		   * 
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	$GLOBALS['phpgw_info']['flags'] = array(
		'noheader'   => True,
		'nonavbar'   => True,
		'currentapp' => 'preferences'
	);

	include('../header.inc.php');

	if($_POST['cancel'])
	{
		$GLOBALS['phpgw']->redirect_link('/preferences/index.php');
		$GLOBALS['phpgw']->common->phpgw_exit();
	}

	if(!@is_object($GLOBALS['phpgw']->js))
	{
		$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
	}
	if(!@is_object($GLOBALS['phpgw']->sms)) $GLOBALS['phpgw']->sms = CreateObject('phpgwapi.sms');
	
	$GLOBALS['phpgw']->js->add('src','../prototype/plugins/jquery/jquery-latest.min.js');
	$GLOBALS['phpgw']->js->add('src','../prototype/plugins/expressoAPI/expressoAjax.js');
	$GLOBALS['phpgw']->js->validate_file('jscode','scripts','preferences');#diretorio, arquivo.js, aplicacao

	$GLOBALS['phpgw']->template->set_file(array(
		'form' => 'changepersonaldata.tpl'
	));

	$sms_enabled = $GLOBALS['phpgw']->sms->isEnabled();
	$GLOBALS['phpgw']->template->set_var('sms_enabled', $sms_enabled? 'true' : 'false');
	$GLOBALS['phpgw']->template->set_var('sms_send_number', $sms_enabled? $GLOBALS['phpgw']->sms->getLastPhoneNumberWasSendCode() : '?');
	$GLOBALS['phpgw']->template->set_var('sms_list_checked', $sms_enabled? implode(',',$GLOBALS['phpgw']->sms->getCheckedListPhoneNumbers()) : '');
	
	if( $sms_enabled ) {
		$sms_auth = $GLOBALS['phpgw']->sms->getAuth();
		$GLOBALS['phpgw']->template->set_var('sms_auth_yes', $sms_auth? 'selected="selected"' : '');
		$GLOBALS['phpgw']->template->set_var('sms_auth_no', $sms_auth? '' : 'selected="selected"');
	}

	$GLOBALS['phpgw']->template->set_var('lang_commercial_telephonenumber',lang('%1 telephone number',lang('Commercial')));
	$GLOBALS['phpgw']->template->set_var('lang_birthday',lang('Birthday'));
	$GLOBALS['phpgw']->template->set_var('lang_ps_commercial_telephonenumber',
	lang('Observation') . ': ' . lang('This telephone number will apear in searches for your name, and it will be visible for all ExpressoLivre Users') . '.');
	$GLOBALS['phpgw']->template->set_var('lang_mobile_telephonenumber',lang('%1 telephone number',lang('Mobile')));
	$GLOBALS['phpgw']->template->set_var('lang_homephone_telephonenumber',lang('%1 telephone number',lang('Home')));
	$GLOBALS['phpgw']->template->set_var('lang_code_title',lang('Checking code'));
	$GLOBALS['phpgw']->template->set_var('lang_send_code',lang('send code by sms'));
	$GLOBALS['phpgw']->template->set_var('lang_send_new_code',lang('send new code by sms'));
	$GLOBALS['phpgw']->template->set_var('lang_sms_auth',lang('I consent to receive SMS'));
	$GLOBALS['phpgw']->template->set_var('lang_ins_code',lang('insert received code')); //Inserir o código recebido
	$GLOBALS['phpgw']->template->set_var('lang_confirm_send_new_code',lang('confirm send new checking code?\n\nall received code previously will be invalidated'));
	$GLOBALS['phpgw']->template->set_var('lang_confirm_mobile_autz',lang('confirm deny receiving any sms in this phone?'));
	$GLOBALS['phpgw']->template->set_var('lang_change',lang('Change'));
	$GLOBALS['phpgw']->template->set_var('lang_cancel',lang('Cancel'));
	$GLOBALS['phpgw']->template->set_var('lang_yes',lang('Yes'));
	$GLOBALS['phpgw']->template->set_var('lang_no',lang('No'));
	$GLOBALS['phpgw']->template->set_var( 'lang_mobile_msg', lang( 'Click send code by SMS to validate your cell phone.' ) );
	
	$GLOBALS['phpgw']->template->set_var('form_action',$GLOBALS['phpgw']->link('/preferences/changepersonaldata.php'));
	
	/* Get telephone number from ldap or from post */
	$ldap_conn = $GLOBALS['phpgw']->common->ldapConnect();
	$result = ldap_search($ldap_conn, $GLOBALS['phpgw_info']['server']['ldap_context'], 'uid='.$GLOBALS['phpgw_info']['user']['account_lid'], array('telephonenumber','mobile','homephone','datanascimento'));
	$entrie = ldap_get_entries($ldap_conn, $result);

	/* BEGIN ACL Check for Personal Data Fields.*/
	$disabledTelephoneNumber = false;
	$disabledMobile = false;
	$disabledHomePhone = false;
	$disableBirthday = false;
	if ($GLOBALS['phpgw']->acl->check('blockpersonaldata',1)) {
		$disabledTelephoneNumber = '"disabled=true"';
	}
	if ($GLOBALS['phpgw']->acl->check('blockpersonaldata',2)) {
		$disabledMobile = '"disabled=true"';
	}
	if ($GLOBALS['phpgw']->acl->check('blockpersonaldata',4)) {
		$disabledHomePhone = '"disabled=true"';
	}
	if ($GLOBALS['phpgw']->acl->check('blockpersonaldata',8)) {
		$disableBirthday = '"disabled=true"';
	}
	/* END ACL Check for Personal Data Fields.*/
	
	$GLOBALS['phpgw']->template->set_var('telephonenumber',($_POST['telephonenumber'] ? $_POST['telephonenumber'] : $entrie[0]['telephonenumber'][0]).$disabledTelephoneNumber);
	$GLOBALS['phpgw']->template->set_var('mobile',($_POST['mobile'] ? $_POST['mobile'] : $entrie[0]['mobile'][0]).$disabledMobile);
	$GLOBALS['phpgw']->template->set_var('homephone',($_POST['homephone'] ? $_POST['homephone'] : $entrie[0]['homephone'][0]).$disabledHomePhone);
	$GLOBALS['phpgw']->template->set_var('datanascimento',$_POST['datanascimento'] ? $_POST['datanascimento'] : $entrie[0]['datanascimento'][0] != '' ? $entrie[0]['datanascimento'][0] : '');

	ldap_close($ldap_conn);

	if ($GLOBALS['phpgw_info']['server']['auth_type'] != 'ldap')
	{
		$GLOBALS['phpgw']->template->set_var('sql_message',lang('note: This feature is *exclusive* for ldap repository.'));
	}

	if ($_POST['change'])
	{
		$pattern = '/^\([0-9]{2,3}\)[0-9]{4,5}-[0-9]{4}$/';
		
		$errors = array();
		
		$birth_dte = ( strlen( preg_replace( '/[^0-9]/', '', $_POST['datanascimento']  ) ) <  8 )? '' : $_POST['datanascimento'];
		$com_phone = ( strlen( preg_replace( '/[^0-9]/', '', $_POST['telephonenumber'] ) ) < 10 )? '' : $_POST['telephonenumber'];
		$hom_phone = ( strlen( preg_replace( '/[^0-9]/', '', $_POST['homephone']       ) ) < 10 )? '' : $_POST['homephone'];
		$cel_phone = ( strlen( preg_replace( '/[^0-9]/', '', $_POST['mobile']          ) ) < 10 )? '' : $_POST['mobile'];
		
		if ( ( !empty( $birth_dte ) ) && ( !checkdate( substr( $birth_dte, 3, 2 ), substr( $birth_dte, 0, 2 ), substr( $birth_dte, 6, 4 ) ) ) )
			$errors[] = lang( 'invalid date' );
		
		if ( ( !empty( $com_phone ) ) && ( !preg_match( $pattern, $com_phone ) ) )
			$errors[] = lang( 'Format of %1 telephone number is invalid.', lang( 'Commercial' ) );
		
		if ( ( !empty( $hom_phone ) ) && ( !preg_match( $pattern, $hom_phone ) ) )
			$errors[] = lang( 'Format of %1 telephone number is invalid.', lang( 'Home' ) );
		
		if ( ( !empty( $cel_phone ) ) && ( !preg_match( $pattern, $cel_phone ) ) )
			$errors[] = lang( 'Format of %1 telephone number is invalid.', lang( 'Mobile' ) );
		
		if ( !count( $errors ) ) {
			
			$ldap_mod = array();
			
			if ( ( !$disableBirthday ) && ( $birth_dte != $GLOBALS['phpgw_info']['user']['datanascimento'] ) )
				$ldap_mod['datanascimento'] = empty( $birth_dte )? array() : $birth_dte;
			
			if ( ( !$disabledTelephoneNumber ) && ( $com_phone != $GLOBALS['phpgw_info']['user']['telephonenumber'] ) )
				$ldap_mod['telephonenumber'] = empty( $com_phone )? array() : $com_phone;
			
			if ( ( !$disabledHomePhone ) && ( $hom_phone != $GLOBALS['phpgw_info']['user']['homephone'] ) )
				$ldap_mod['homephone'] = empty( $hom_phone )? array() : $hom_phone;
			
			if ( ( !$disabledMobile ) && ( $cel_phone != $GLOBALS['phpgw_info']['user']['mobile'] ) ) {
				if ( empty( $cel_phone ) ) $ldap_mod['mobile'] = array();
				else if ( ( !$sms_enabled ) || $GLOBALS['phpgw']->sms->isCheckedPhoneNumber( $cel_phone ) ) $ldap_mod['mobile'] = $cel_phone;
			}
			if ( $sms_enabled && ( $_POST['mobile_autz'] === '1' ) != $sms_auth ) {
				$GLOBALS['phpgw']->sms->setAuth( $_POST['mobile_autz'] === '1' );
				$GLOBALS['phpgw']->sms->savePreferences();
			}
			
			if ( count( $ldap_mod ) ) {
				// Use LDAP Replication mode, if available
				if (
					( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_host'] ) ) &&
					( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_root_dn'] ) ) &&
					( !empty( $GLOBALS['phpgw_info']['server']['ldap_master_root_pw'] ) )
				) {
					$ldap_conn = $GLOBALS['phpgw']->common->ldapConnect(
						$GLOBALS['phpgw_info']['server']['ldap_master_host'],
						$GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
						$GLOBALS['phpgw_info']['server']['ldap_master_root_pw']
					);
				} else $ldap_conn = $GLOBALS['phpgw']->common->ldapConnect();
				
				ldap_mod_replace( $ldap_conn, $GLOBALS['phpgw_info']['user']['account_dn'], $ldap_mod );
				
				ldap_close( $ldap_conn );
			}
		} else {
			$GLOBALS['phpgw']->common->phpgw_header();
			echo parse_navbar();
			$GLOBALS['phpgw']->template->set_var( 'messages',$GLOBALS['phpgw']->common->error_list( $errors ) );
			$GLOBALS['phpgw']->template->pfp( 'out', 'form' );
			$GLOBALS['phpgw']->common->phpgw_exit( true );
		}
		$GLOBALS['phpgw']->redirect_link( '/preferences/index.php','cd=18' );
	}
	else
	{
		$GLOBALS['phpgw_info']['flags']['app_header'] = lang('Change your Personal Data');
		$GLOBALS['phpgw']->common->phpgw_header();
		echo parse_navbar();

		$GLOBALS['phpgw']->template->pfp('out','form');
		$GLOBALS['phpgw']->common->phpgw_footer();
	}
?>
