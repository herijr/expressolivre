<input type="hidden" id="{modal_id}_title" value="{lang_creation_of_shared_accounts}">
<input type="hidden" id="{modal_id}_height" value="503">
<input type="hidden" id="{modal_id}_width" value="930">
<input type="hidden" id="{modal_id}_close_action" value="sharedAccounts.close()">
<input type="hidden" id="{modal_id}_create_action" value="sharedAccounts.save()">
<input type="hidden" id="{modal_id}_save_action" value="sharedAccounts.save()">
<input type="hidden" id="{modal_id}_onload_action" value="">
<form enctype="multipart/form-data" name="shared_accounts_form" method="post">
<input type="hidden" id="anchor" name="anchor">
<input type="hidden" id="owners_acls" name="owners_acl" value="">
<table border="0" cellspacing="4">
	<tr>
		<td width="35%" bgcolor="#DDDDDD">
			<p style="line-height: 220%">{lang_search_organization}:
			<input type="text" id="organization_search" autocomplete="off" size=20 onKeyUp="javascript:searchOrganization(this.value, 'ea_combo_org');"><br>
			
			{lang_organization}:
			<select id="ea_combo_org" name="context">{manager_organizations}</select><br>
							
			{lang_full_name}: 
			<input id="cn" name="cn" size="36" autocomplete="off"><br>
						
			{lang_mail}: 
			<input id="mail" name="mail" size="45" autocomplete="off"><br>

			{lang_description}:
			<input id="description" name="description" size="42" autocomplete="off"><br>
            
            {lang_Email_quota_in_MB}:
            <input type="text" id="mailquota" name="mailquota" autocomplete="off" value="{mailquota}" {changequote_disabled} {disabled} size=16>
            <br>
            <span id='quota_used_field' name='quota_used_field' style="display:{display_quota_used}">{lang_quota_used_in_mb}:
            	<input type="text" name="mailquota_used" id="mailquota_used" value="{mailquota_used}" disabled size=10>
            	<br>
            </span>
            <span id='display_empty_inbox' name='display_empty_inbox' style="display:none">
            	<input type='button' {disabled} {disabled_empty_inbox} value='{lang_empty_inbox}' onclick="javascript:sharedAccounts.emptyMailBox(anchor.value);">
            	<br>
            </span>
			{lang_is_account_active}: <input type="checkbox" id="accountStatus" name="accountStatus" checked><br>

			{lang_omit_account_from_the_catalog}: <input type="checkbox" id="phpgwAccountVisible" name="phpgwAccountVisible"></p>
							
			<b>{lang_owners}:</b>
			<br>
			<select style="width:350px; height:160px" id="ea_select_owners" onchange="sharedAccounts.getAcl(this.value);" name="owners[]" multiple size="13"></select>
            <span style="position:absolute;">
            	<table>
            		<tbody>
            			<tr>
            				<td colspan="2">
            					<b>Direitos de acesso:</b>
            				</td>
            			</tr>
            			<tr>
            				<td>Leitura:</td>
            				<td>
            					<input id="em_input_readAcl" onclick="javascript:sharedAccounts.setAcl(this);" type="checkbox">
            					<img title="Outros usuarios poder&atilde;o LER suas mensagens." src="./expressoAdmin1_2/templates/default/images/ajuda.jpg">
            				</td>
            			</tr>
            			<tr>
            				<td>Exclusao:</td>
            				<td>
            					<input id="em_input_deleteAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
            					<img title="Outros usu&aacute;rios poder&atilde;o APAGAR/MOVER suas mensagens." src="./expressoAdmin1_2/templates/default/images/ajuda.jpg">
            				</td>
            			</tr>
            			<tr>
            				<td>Criacao:</td>
            				<td>
            					<input id="em_input_writeAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
            					<img title="Outros usu&aacute;rios poder&atilde;o CRIAR/ADICIONAR novas mensagens." src="./expressoAdmin1_2/templates/default/images/ajuda.jpg">
            				</td>
            			</tr>
            			<tr>
            				<td>Enviar:</td>
            				<td>
            					<input id="em_input_sendAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
            					<img title="Outros usuarios poder&atilde;o ENVIAR mensagens como sendo voc&ecirc;." src="./expressoAdmin1_2/templates/default/images/ajuda.jpg">
            				</td>
            			</tr>
            		</tbody>
            	</table>
            </span>
		</td>
						
		<td width="15%" valign="middle" align="center" bgcolor="#DDDDDD">
			<button id="bt_add_user" type="button" onClick="javascript:sharedAccounts.addOwner();">
				<img src="expressoAdmin1_2/templates/default/images/add.png" style="vertical-align: middle;">&nbsp;{lang_add_owner}
			</button>
			<br>
			<br>
			<button id="bt_remove_user" type="button" onClick="javascript:sharedAccounts.delOwner();">
				<img src="expressoAdmin1_2/templates/default/images/rem.png" style="vertical-align: middle;">&nbsp;{lang_remove_owner}
			</button>
		</td>
						
		<td width="25%" valign="bottom" bgcolor="#DDDDDD">
			{lang_search_organization}:<br>
			<input type="text" id="organization_search" autocomplete="off" size=20 onKeyUp="javascript:searchOrganization(this.value, 'ea_combo_org_available_users');" onBlur="javascript:sharedAccounts.getAvailableUsers(org_context.value);">
			<br>
							
			{lang_organizations}:<br>
			<select name="org_context" id="ea_combo_org_available_users" onchange="javascript:sharedAccounts.getAvailableUsers(this.value);">{all_organizations}</select>
			<br>
			<br><br>
			{lang_search_user}:<br>
			<input id="ea_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:finderTimeout(this)"><br>
							
			<font color="red"><span id="ea_span_searching">&nbsp;</span></font>
			<br>
			<b>{lang_users}:</b><br>
			<select id="ea_select_available_users" style="width:350px; height:160px" multiple size="13"></select>
		</td>
	</tr>
</table>
</form>
<script type="text/javascript">

	var timeOut = "";

	function finderTimeout( element )
	{
		clearTimeout(timeOut);
		
		$("#ea_span_searching").html( get_lang('searching') + '...' );
		
		timeOut = setTimeout(function(){ finder( element );  }, 500 );
	}

	function finder( element )
	{
		var _find_user = new RegExp("\\b"+ $(element).val() , "i");

		$.fn.optVisible = function( show )
		{
			this.filter( ":not( span > option )").wrap( "<span>" ).parent().hide();

			if( show ){ this.filter( "span > option").unwrap(); }

			return this;
		};

		$("#ea_select_available_users").find("option").each(function()
		{
			$(this).optVisible( true );

			if( !_find_user.test( $(this).text() ) ){ $(this).optVisible( false ); }

		});

		$("#ea_span_searching").html("");
	}

	function searchOrganization( key, element )
	{
		var _find_org 	= new RegExp("\\b"+key, "i");

		if( $.trim(element) === "ea_combo_org" )
		{
			$("#ea_combo_org option").each(function(){
				if( _find_org.test( $(this).text) ){ $(this).prop( "selected", true ); }
			});
		}
		else
		{
			$("#ea_combo_org_available_users option").each(function(){
				if( _find_org.test( $(this).text) ){ $(this).prop( "selected", true ); }
			});
		}
	}

</script>