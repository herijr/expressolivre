<!-- BEGIN manageheader -->

<script language="JavaScript" type="text/javascript">
<!--
	{js_default_db_ports}
	function setDefaultDBPort(selectBox,portField)
	{
		if(selectBox.selectedIndex != -1 && selectBox.options[selectBox.selectedIndex].value)
		{
			portField.value = default_db_ports[selectBox.options[selectBox.selectedIndex].value];
		}
		return false;
	}

	function ocultar(zdiv)
	{
		var xdiv = document.getElementById(zdiv);
		if(xdiv.id == "certificado") {
			xdiv.style.display='none';
			document.getElementById('cert_0').checked = true;
		}
		if( xdiv.id == "certificado" || xdiv.id == "criptografia") {
			var xdiv = document.getElementById('criptografia');
			document.getElementById('cripto_0').checked = true;
		}
		if(xdiv.id == "certificado" || xdiv.id == "criptografia" ) {
			var xdiv = document.getElementById('criptografiax');
			document.getElementById('maxcerttxt').value = 0;
		}
		if(xdiv.id == "badlogin") {
			document.getElementById('badlogintxt').value='0';
		}
		xdiv.style.display='none';	
	}

	function exibir(zdiv)
	{
		var xdiv = document.getElementById(zdiv);
		if(xdiv.id == "cripto_options") {
			document.getElementById('maxcerttxt').value = '10';
		}
		if(xdiv.id == "badlogin") {
			document.getElementById('badlogintxt').value= '3';
		}
		xdiv.style.display='';
	}

	function getEvent(e)
	// Retorna um dicionario com o objeto evento e o codigo da tecla pressionada
	{
		var d
		var keycode
		var evento
		if (window.event)
			d = { e: window.event, keycode: window.event.keyCode }
		else {
			if (e)
				d = { e: e, keycode: e.which }
			else
				return null
		}
		return d
	}

	function soNumero(myfield, e)
	// Permite a digitacao de apenas numeros em campos de formularios
	// Utilizacao: <input type="text" onkeypress="return soNumero(this, event);">
	{

		var d = getEvent(e);
		var e = d['e'];
		var keycode = d['keycode'];
		if (e == null) return true;
		// Tecla de funcao (Ctrl, Alt), deixa passar
		if (e.ctrlKey || e.metaKey || keycode < 32)
			return true;
		else
			return (keycode > 47 && keycode < 58); // false se tecla nao for numerica
	}

//-->
</script>

<table border="0" width="90%" cellspacing="0" cellpadding="0" align="center">
<tbody>
	<tr><td colspan="2">
		<fieldset><legend>{lang_analysis}</legend>
			<table>
				<tr><td colspan="2"></td></tr>
					{detected}
				</table>
		</fieldset>
	</tr>	

	<tr><td>&nbsp;</td></tr>

	<tr><td colspan="2">
		<fieldset><legend>{lang_settings}</legend>
			<table>
				<form name="domain_settings" action="manageheader.php" method="post">
					<input type="hidden" name="setting[write_config]" value="true">
				<tr>
					<td colspan="2"><b>{lang_serverroot}</b>
						<br><input type="text" name="setting[server_root]" size="80" value="{server_root}">
					</td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_includeroot}</b><br><input type="text" name="setting[include_root]" size="80" value="{include_root}"></td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_adminuser}</b><br><input type="text" name="setting[HEADER_ADMIN_USER]" size="30" value="{header_admin_user}"></td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_adminpass}</b><br><input type="password" name="setting[HEADER_ADMIN_PASSWORD]" size="30" value="{header_admin_password}"><input type="hidden" name="setting[HEADER_ADMIN_PASS]" value="{header_admin_pass}"></td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_setup_acl}</b><br><input type="text" name="setting[setup_acl]" size="30" value="{setup_acl}"></td>
				</tr>
				<tr>
					<td><b>{lang_persist}</b><br>
						<select type="checkbox" name="setting[db_persistent]">
							<option value="True"{db_persistent_yes}>{lang_Yes}</option>
							<option value="False"{db_persistent_no}>{lang_No}</option>
						</select>
					</td>
					<td>{lang_persistdescr}</td>
				</tr>
				<tr>
					<td><b>{lang_sesstype}</b><br>
						<select name="setting[sessions_type]">
							{session_options}
						</select>
					</td>
					<td>{lang_sesstypedescr}</td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_suggestion}</b></td>
				</tr>
				<tr>
					<td colspan="2"><INPUT size="100" name="setting[sugestoes_email_to]" value="{sugestoes_email_to}"></td>
				</tr>
				<tr>
					<td colspan="2"><b>{lang_domainname}</b></td>
				</tr>
				<tr>
					<td colspan="2"><INPUT size="50" name="setting[domain_name]" value="{domain_name}"></td>
				</tr>
				<tr>
					<td><b>{lang_usetokenlogin}</b><br>
						<select type="checkbox" name="setting[use_token_login]">
							<option value="1"{use_token_login_yes}>{lang_Yes}</option>
							<option value="0"{use_token_login_no}>{lang_No}</option>
						</select>
					</td>
					<td>{lang_usetokenlogindescr}</td>
				</tr>
			</table>
		</fieldset>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<fieldset><legend>Mcrypt</legend>
				<table>
					<tr>
						<td>
							<b>{lang_enablemcrypt}</b><br>
							<select name="setting[enable_mcrypt]">
								<option value="True"{mcrypt_enabled_yes}>{lang_Yes}</option>
								<option value="False"{mcrypt_enabled_no}>{lang_No}</option>
							</select>
						</td>
						<td>{lang_mcrypt_warning}</td>
					</tr>
					<tr>
						<td><b>{lang_mcryptversion}</b><br><input type="text" name="setting[mcrypt_version]" value="{mcrypt}"></td>
						<td>{lang_mcryptversiondescr}</td>
					</tr>
					<tr>
						<td><b>{lang_mcryptiv}</b><br><input type="text" name="setting[mcrypt_iv]" value="{mcrypt_iv}" size="30"></td>
						<td>{lang_mcryptivdescr}</td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>

	<tr><td colspan="2">&nbsp;</td></tr>

	<tr>
		<td colspan="2">
			<fieldset><legend>{lang_domains}</legend>
				<table>
					{domains}{comment_l}
					<tr class="th">
						<td colspan="2"><input type="submit" name="adddomain" value="{lang_adddomain}"></td>
					</tr>{comment_r}
				</table>
			</fieldset>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<fieldset><legend>HTTPS</legend>
				<table>
					<tr>
						<td colspan="2"><b>{lang_usehttps}</b></td>
					</tr>
  				<tr><td colspan="2">
	  				<font color='red'>Obs.: {lang_httpsdescr}</font><br>
						<INPUT type="radio"{use_https_0} name="setting[use_https]" value="0" onclick="javascript:ocultar('certificado')">{lang_nohttps}<BR>
						<INPUT type="radio"{use_https_1} name="setting[use_https]" value="1" onclick="javascript:exibir('certificado')" >{lang_loginhttps}<BR>
						<INPUT type="radio"{use_https_2} name="setting[use_https]" value="2" onclick="javascript:exibir('certificado')" >{lang_sitewidehttps}<BR>	
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div id="certificado" {div_cert}>
								<table>
									<tr><td colspan="2"><b>{lang_usecertificate}</b></td></tr>
									<tr><td colspan="2">
										<font color='red'>Obs.: {lang_certdescr}</font><br>
										<INPUT id="cert_0" type="radio" {certificado_0} name="setting[certificado]" onclick="javascript:ocultar('criptografiax');javascript:ocultar('linegap')" value="0" >{lang_notusecert}<BR>
										<INPUT id="cert_1" type="radio" {certificado_1} name="setting[certificado]" onclick="javascript:exibir('criptografiax');javascript:exibir('linegap')" value="1">{lang_usecert}<BR>
										</td></tr>
								</table>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>

	<tr id="linegap" style="display:none"><td>&nbsp;</td></tr>

	<tr>
		<td colspan="2"><div id="criptografiax" {div_criptox} >
			<fieldset><legend>{lang_cryptosig}</legend>
				<table>
					<tr>
						<td colspan="2">
							<b>{lang_enablesig}</b>
	 						<br><font color='red'>Obs.: {lang_sigdescr}</font>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<INPUT id='cripto_0' onclick="javascript:ocultar('cripto_options')" type="radio" {use_assinar_criptografar_0} name="setting[use_assinar_criptografar]" value="0"  />{lang_dontenable}<BR>
							<div id="criptografia" ><INPUT id='cripto_1' onclick="javascript:exibir('cripto_options')" type="radio" {use_assinar_criptografar_1} name="setting[use_assinar_criptografar]" value="1" />{lang_doenable}</div><BR>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div id="cripto_options" {cripto_options}>
								<table>
									<tr>
										<td colspan="2">
											<b>{lang_maxrecipientes}<br><INPUT type="text" maxlength="2" size="3" name="setting[num_max_certs_to_cipher]" id="maxcerttxt" value="{num_max_certs_to_cipher}" onkeypress="return soNumero(this, event);">
										</td>
									</tr>
								</table>		
  						</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</div>
		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td colspan="2">
			<fieldset><legend> Controle da senha</legend>
				<table>
					<tr>
						<td nowrap>
							<b>{lang_passldapatrib}</b>
						</td>
					</tr>
					<tr>
						<td>
							<INPUT type="text" maxlength="50" size="40" name="setting[atributoexpiracao]" id="atributoexpiracaotxt" value="{atributoexpiracao}" >
						</td>
					</tr>
					<tr>
						<td nowrap>
							<b>{lang_ldapuserclass}</b>
						</td>
					</tr>
					<tr>
						<td>
							<INPUT type="text" maxlength="50" size="40" name="setting[atributousuarios]" id="atributousuarios" value="{atributousuarios}" >
						</td>
					</tr>
				</table>
			</fieldset>
		</td></tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td colspan="2">
			<fieldset><legend>{lang_antitheft}</legend>
			<table>
				<tr>
					<td colspan="2">
						<b>{lang_usecaptcha}</b>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<INPUT type="radio" {captcha_0} name="setting[captcha]" value="0" onclick="javascript:ocultar('badlogin')">{lang_notusecaptcha}<BR>
						<INPUT type="radio" {captcha_1} name="setting[captcha]" value="1" onclick="javascript:exibir('badlogin')" >Usar Anti-Robo.<BR>
					</td>
				</tr>		
				<tr>
					<td colspan="2">
						<div id="badlogin" {div_badlogin}>
							<table>
								<tr>
									<td colspan="2">
										<b>{lang_triesbeforecaptcha}</b>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<INPUT type="text" maxlength="2" size="3" name="setting[num_badlogin]" id="badlogintxt" value="{num_badlogin}" onkeypress="return soNumero(this, event);">
									</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
			</table>
		</fieldset>
		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td colspan="2">{errors}</td>
	</tr>
	{formend}
	<tr>
		<td colspan="3">
			<form action="index.php" method="post">
				<br>{lang_finaldescr}<br><br>
				<input type="hidden" name="FormLogout"  value="header">
				<input type="hidden" name="ConfigLogin" value="Login">
				<input type="hidden" name="FormUser"    value="{FormUser}">
				<input type="hidden" name="FormPW"      value="{FormPW}">
				<input type="hidden" name="FormDomain"  value="{FormDomain}">
				<input type="submit" name="junk"        value="{lang_continue}">
			</form>
		</td>
	</tr>
	<tr class="banner">
		<td colspan="3">&nbsp;</td>
	</tr>
</table>
</body>
</html>
<!-- END manageheader -->

<!-- BEGIN domain -->
	<tr class="th">
		<td>{lang_domain}:</td>&nbsp;<td><input name="domains[{db_domain}]" value="{db_domain}">&nbsp;&nbsp;<input type="checkbox" name="deletedomain[{db_domain}]">&nbsp;<font color="0000ff">{lang_delete}</font></td>
	</tr>
	<tr>
		<td><b>{lang_dbtype}</b><br>
			<select name="setting_{db_domain}[db_type]" onchange="setDefaultDBPort(this,this.form['setting_{db_domain}[db_port]']);">
				{dbtype_options}
			</select>
		</td>
		<td>{lang_whichdb}</td>
	</tr>
	<tr>
		<td><b>{lang_dbhost}</b><br><input type="text" name="setting_{db_domain}[db_host]" value="{db_host}"></td><td>{lang_dbhostdescr}</td>
	</tr>
	<tr>
		<td><b>{lang_dbport}</b><br><input type="text" name="setting_{db_domain}[db_port]" value="{db_port}"></td><td>{lang_dbportdescr}</td>
	</tr>
	<tr>
		<td><b>{lang_dbname}</b><br><input type="text" name="setting_{db_domain}[db_name]" value="{db_name}"></td><td>{lang_dbnamedescr}</td>
	</tr>
	<tr>
		<td><b>{lang_dbuser}</b><br><input type="text" name="setting_{db_domain}[db_user]" value="{db_user}"></td><td>{lang_dbuserdescr}</td>
	</tr>
	<tr>
		<td><b>{lang_dbpass}</b><br><input type="password" name="setting_{db_domain}[db_pass]" value="{db_pass}"></td><td>{lang_dbpassdescr}</td>
	</tr>
	<tr>
		<td><b>{lang_configuser}</b><br><input type="text" name="setting_{db_domain}[config_user]" value="{config_user}"></td>
	</tr>
	<tr>
		<td><b>{lang_configpass}</b><br><input type="password" name="setting_{db_domain}[config_pass]" value="{config_pass}"><input type="hidden" name="setting_{db_domain}[config_password]" value="{config_password}"></td>
		<td>{lang_passforconfig}</td>
	</tr>
<!-- END domain -->
