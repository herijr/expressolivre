<?php

// Default number of letters = 3
if (!$GLOBALS['phpgw_info']['server']['num_letters_userpass']) {
	$GLOBALS['phpgw_info']['server']['num_letters_userpass'] = 3;
}

// Default number of special letters = 0
if (!$GLOBALS['phpgw_info']['server']['num_special_letters_userpass']) {
	$GLOBALS['phpgw_info']['server']['num_special_letters_userpass'] = 0;
}

// Default number of uppercase letters = 1
if (!$GLOBALS['phpgw_info']['server']['num_uppercase_letters']) {
	$GLOBALS['phpgw_info']['server']['num_uppercase_letters'] = 1;
}

// Password : validate fields;
$errors = array();

if (!$GLOBALS['phpgw']->auth->authenticate($GLOBALS['phpgw_info']['user']['account_lid'], $a_passwd)) {
	$errors[] = lang('Your actual password is wrong');
} else if ($n_passwd != $n_passwd_2) {
	$errors[] = lang('The two passwords are not the same');
} else if ($a_passwd == $n_passwd) {
	$errors[] = lang('Your old password and your new password are the same. Choose a different new password');
} else if (!$n_passwd) {
	$errors[] = lang('You must enter a password');
} else if (strlen($n_passwd) < $GLOBALS['phpgw_info']['server']['num_letters_userpass']) {
	$errors[] = lang('Your password must contain %1 or more letters', $GLOBALS['phpgw_info']['server']['num_letters_userpass']);
}

if (is_array($errors) && count($errors) > 0 ) {
	$GLOBALS['phpgw']->common->phpgw_header();
	echo parse_navbar();
	$GLOBALS['phpgw']->template->set_var('messages', "<span style='font-weight:bold;'>Erro(s) :</span> " . implode("<br>", $errors ) );
	$GLOBALS['phpgw']->template->pfp('out', 'form');
	$GLOBALS['phpgw']->common->phpgw_exit(True);
}


// Password : validate rules expresso admin;
$errors = array();

// Tests uppercase, digits and characteres
preg_match_all('/[[:upper:]]/', $n_passwd, $matchesUpperCase);

preg_match_all('/[[:digit:]]/', $n_passwd, $matchesDigit);

preg_match_all('/[^[:alnum:]]/', $n_passwd, $matchesChars);

//Upper Case
$upperCase = (is_array($matchesUpperCase[0]) && isset($matchesUpperCase[0]) ? count($matchesUpperCase[0]) : 0);
if ($upperCase < $GLOBALS['phpgw_info']['server']['num_uppercase_letters']) {
	$errors[] = lang('Your password must contain at least %1 uppercase letters', $GLOBALS['phpgw_info']['server']['num_uppercase_letters']);
}

//Number or characters special
$specialChars = 0;
$specialChars += (is_array($matchesDigit) && isset($matchesDigit[0]) ? count($matchesDigit[0]) : 0);
$specialChars += (is_array($matchesChars) && isset($matchesChars[0]) ? count($matchesChars[0]) : 0);

if ($specialChars < $GLOBALS['phpgw_info']['server']['num_special_letters_userpass']) {
	$errors[] = lang('Your password must contain at least %1 special characters', $GLOBALS['phpgw_info']['server']['num_special_letters_userpass']);
}

if (is_array($errors) && count($errors) > 0 ) {
	$GLOBALS['phpgw']->common->phpgw_header();
	echo parse_navbar();
	$GLOBALS['phpgw']->template->set_var('messages', "<span style='font-weight:bold;'>Erro(s) :</span><br>" . implode("<br>", $errors ) );
	$GLOBALS['phpgw']->template->pfp('out', 'form');
	$GLOBALS['phpgw']->common->phpgw_exit(True);
}

$o_passwd = $GLOBALS['phpgw_info']['user']['passwd'];
$passwd_changed = $GLOBALS['phpgw']->auth->change_password($o_passwd, $n_passwd);
if (!$passwd_changed) {
	$errors[] = lang('Failed to change password') . ". " . lang('Please contact your administrator') . '.';
	$GLOBALS['phpgw']->common->phpgw_header();
	echo parse_navbar();
	$GLOBALS['phpgw']->template->set_var('messages', $GLOBALS['phpgw']->common->error_list($errors));
	$GLOBALS['phpgw']->template->pfp('out', 'form');
	$GLOBALS['phpgw']->common->phpgw_exit(True);
} else {
	$GLOBALS['phpgw_info']['user']['passwd'] = $passwd_changed;
	$_SESSION['phpgw_info']['expresso']['user']['account_lid'] = $GLOBALS['phpgw_info']['user']['account_lid'];
	include(dirname(__FILE__) . '/../../../expressoAdmin1_2/inc/class.db_functions.inc.php');
	$db_functions = new db_functions();
	$db_functions->write_log('modified user password', 'User change its own password in preferences');

			require_once( PHPGW_API_INC.'/class.eventws.inc.php' );
			EventWS::getInstance()->send( 'user_passwd_changed', $GLOBALS['phpgw_info']['user']['account_dn'], array( 'passwd' => $n_passwd ) );

	$GLOBALS['hook_values']['uid']        = $GLOBALS['phpgw_info']['user']['account_lid'];
	$GLOBALS['hook_values']['account_id'] = $GLOBALS['phpgw_info']['user']['account_id'];
	$GLOBALS['hook_values']['old_passwd'] = $o_passwd;
	$GLOBALS['hook_values']['new_passwd'] = $n_passwd;
	$GLOBALS['phpgw']->hooks->process('changepassword');

	if ($GLOBALS['phpgw_info']['server']['certificado']) {
		// Vai criptografar senha com o certificado digital(para uso no login com certificado) ....
		$RR = @grava_senha_criptografada_com_certificado_no_ldap($GLOBALS['phpgw_info']['user']['account_id'], $n_passwd);
	}

	if ((!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) && (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) && (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw']))
	) {
		sleep(5);
	}
	if ($GLOBALS['phpgw_info']['server']['use_https'] == 1)
		Header('Location: http://' . $_SERVER['HTTP_HOST'] . $GLOBALS['phpgw_info']['server']['webserver_url'] . '/preferences/index.php');
	else
		$GLOBALS['phpgw']->redirect_link('/preferences/index.php', 'cd=18');
}
