<!-- BEGIN main -->

<!--CSS -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/emailadmin.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/profile.css" />

<!-- The code for Global Tab -->
<input type="hidden" name="lang_confirm_domain" value="{lang_confirm_domain}">
<input type="hidden" name="lang_profile_name_blank" value="{lang_profile_name_blank}">

<div id="tabs">
	<ul>
		<li><a href="#globals">{lang_global}</a></li>
		<li><a href="#smtp">{lang_Configuration_SMTP}</a></li>
		<li><a href="#pop3_imap">{lang_Configuration_Cyrus_IMAP}</a></li>
	</ul>
	
	<form action="{action_url}" name="mailsettings" method="POST">
		<input type="hidden" name="profileid" value="{value_profileid}">
		<div id="globals">
			<fieldset>
				<div style="font-weight:bold;">
					<label style="width:110px;">{lang_profile_name}</label>
					<input type="text" name="description" value="{value_description}" size="30">
				</div>
			</fieldset>

			<div id="acc_domains">
				<h3>{lang_domains_assigned}</h3>
				<div>
					<fieldset class="domains">
						<legend>{lang_domains_assigned}</legend>
						<div>
							<label>{lang_domain_name}</label>
							<input type="text">
							<a>{lang_add_domain}</a>
						</div>
						<table id="table_domains" class="table_domains">
							<tr>
								<th>{lang_domain}</th>
								<th class="th_size">{lang_delete}</th>
							</tr>
							{value_domains}	
						</table>
					</fieldset>
				</div>
			</div>
			<span id="msg_erro_add_domain" style="margin-top:10px;display:none;color:red;font-weight:bold;">{lang_erro_add_domain}</span>	
			<span id="msg_erro_invalid_domain" style="margin-top:10px;display:none;color:red;font-weight:bold;">{lang_invalid_domain}</span>
		</div>
		
		<div id="smtp">
			<div id="acc_smtp">
				<!-- Conf server default smtp -->
				<h3>{lang_SMTP_Standard}</h3>
				<div>
					<fieldset>
						<legend>{lang_SMTP_Standard}</legend>
						<div>
							<label>{lang_SMTP_server_hostname_or_IP_address}</label>
							<input type="text" size="40" name="smtpserver" value="{value_smtpserver}">
						</div>
						<div>
							<label>{lang_stmp_server_port}</label>
							<input type="text" size="5" maxlength="5" name="smtpport" value="{value_smtpport}">
						</div>
						<div>
							<label>{lang_Use_SMTP_auth}</label>
							<input type="checkbox" name="smtpauth" {selected_smtpauth} value="yes"> 
						</div>
					</fieldset>
				</div>

				<!-- Conf settings ldap -->
				<h3>{lang_LDAP_Settings}</h3>
				<div>
					<fieldset>
						<legend>{lang_LDAP_Settings}</legend>
						<div>
							<label>{lang_use_LDAP_defaults}</label>
							<input type="checkbox" name="smtpldapusedefault" {selected_smtpldapusedefault} value="yes">
						</div>

						<div>
							<label>{lang_LDAP_server_hostname_or_IP_address}</label>
							<input type="text" size="40" maxlength="80" name="smtpldapserver" value="{value_smtpldapserver}">
						</div>

						<div>
							<label>{lang_LDAP_server_admin_dn}</label>
							<input type="text" size="40" maxlength="200" name="smtpldapadmindn" value="{value_smtpldapadmindn}">
						</div>

						<div>
							<label>{lang_LDAP_server_admin_pw}</label>
							<input type="password" size="40" maxlength="30" name="smtpldapadminpw" value="{value_smtpldapadminpw}">
						</div>

						<div>
							<label>{lang_LDAP_server_base_dn}</label>
							<input type="text" size="40" maxlength="200" name="smtpldapbasedn" value="{value_smtpldapbasedn}">
						</div>
					</fieldeset>
				</div>
			</div>
		</div>
		
		<div id="pop3_imap">
			<div id="acc_pop3_imap">
				<!-- Conf server cyrus imap -->
				<h3>{lang_cyrus_imap_server}</h3>
				<div>
					<fieldset>
						<legend>{lang_cyrus_imap_server}</legend>
						<div>
							<label>{lang_imap_server_hostname_or_IP_address}</label>
							<input type="text" size="40" maxlength="80" name="imapserver" value="{value_imapserver}">
						</div>

						<div>
							<label>{lang_imap_server_port}</label>
							<input type="text" size="5" maxlength="5" name="imapport" value="{value_imapport}">
						</div>

						<div>
							<label>{lang_delimiter_imap}</label>
							<select name="imapdelimiter">
								<option value="." {selected_imapdelimiter_dot}>&nbsp;.&nbsp;</option>
								<option value="/" {selected_imapdelimiter_slash}>&nbsp;/&nbsp;</option>
							</select>
						</div>

						<div>
							<label>{lang_use_encryption}</label>						
							<select name="imapencryption">
								<option value="no" {selected_imapencryption_no}>&nbsp;{lang_no}&nbsp;</option>
								<option value="ssl" {selected_imapencryption_ssl}>&nbsp;SSL&nbsp;</option>
								<option value="tls" {selected_imapencryption_tls}>&nbsp;TLS&nbsp;</option>
								<option value="notls" {selected_imapencryption_notls}>&nbsp;NOTLS&nbsp;</option>
							</select>
						</div>

						<div>
							<label>{lang_validate_cert}</label>												
							<input type="checkbox" name="imapvalidatecert" {selected_imapvalidatecert} value="yes">
						</div>

						<div>
							<label>{lang_pre_2001_c_client}</label>
							<input type="checkbox" name="imapoldcclient" {selected_imapoldcclient} value="yes">
						</div>

					</fieldset>

					<br>

					<fieldset>
						<legend>{lang_cyrus_imap_administration}</legend>

						<div>
							<label>{lang_enable_cyrus_imap_administration}</label>
							<input type="checkbox" name="imapenablecyrusadmin" {selected_imapenablecyrusadmin} value="yes">
						</div>

						<div>
							<label>{lang_admin_username}</label>
							<input type="text" size="40" maxlength="40" name="imapadminusername" value="{value_imapadminusername}">
						</div>

						<div>
							<label>{lang_admin_password}</label>
							<input type="password" size="40" maxlength="40" name="imapadminpw" value="{value_imapadminpw}">
						</div>

						<div>
							<label>{lang_admin_imap_server_hostname_or_ip_address}</label>
							<input type="text" size="40" name="imapAdminServer" value="{value_imapadminserver}">
						</div>
						
						<div>
							<label>{lang_admin_imap_server_port}</label>
							<input type="text" size="5" name="imapAdminPort" value="{value_imapadminport}">
						</div>

					</fieldset>

					<br>

					<fieldset>

						<legend>{lang_sieve_settings}</legend>

						<div>
							<label>{lang_enable_sieve}</label>
							<input type="checkbox" name="imapenablesieve" {selected_imapenablesieve} value="yes">
						</div>

						<div>
							<label>{lang_sieve_server_hostname_or_ip_address}</label>
							<input type="text" size="40" maxlength="80" name="imapsieveserver" value="{value_imapsieveserver}">
						</div>

						<div>
							<label>{lang_sieve_server_port}</label>
							<input type="text" size="5" maxlength="5" name="imapsieveport" value="{value_imapsieveport}">
						</div>

					</fieldset>

					<br>

					<fieldset>
						<legend>{lang_spam_settings}</legend>

						<div>
							<label>{lang_create_spam_folder}</label>
							<input type="checkbox" name="imapcreatespamfolder" {selected_imapcreatespamfolder} value="yes">
						</div>

						<div>
							<label>{lang_cyrus_user_post_spam}</label>
							<input type="text" size="40" maxlength="80" name="imapcyrususerpostspam" value="{value_imapcyrususerpostspam}">
						</div>

					</fieldset>

					<br>

					<fieldset>
						<legend>{lang_default_folders}</legend>

						<div>
							<label>{lang_trash_folder}</label>
							<input type="text" size="40" name="imapdefaulttrashfolder" value="{value_imapdefaulttrashfolder}">
						</div>

						<div>
							<label>{lang_sent_folder}</label>
							<input type="text" size="40" name="imapdefaultsentfolder" value="{value_imapdefaultsentfolder}">
						</div>

						<div>
							<label>{lang_drafts_folder}</label>
							<input type="text" size="40" name="imapdefaultdraftsfolder" value="{value_imapdefaultdraftsfolder}">
						</div>

						<div>
							<label>{lang_spam_folder}</label>
							<input type="text" size="40" name="imapdefaultspamfolder" value="{value_imapdefaultspamfolder}">
						</div>
					</fieldset>
				</div>
			</div>
		</div>

	</form>

</div>

<div class="buttons">
	<button id="button_back">{lang_back}</button>
	<button id="button_save">{lang_save}</button>
</div>

<!-- JavaScript -->
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript" src="./emailadmin/js/profile.js"></script>
<script type="text/javascript">
	$("#button_back").button().click(function(){
		window.location = '{link_back_page}';
	});
</script>

<!-- END main -->
