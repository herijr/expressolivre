<!-- BEGIN form -->

<!--JS Imports from phpGW javascript class -->
{scripts_java}

<form action="{action}" name="managers_form" method="post">
 	<div align="center">

 	<input type=hidden name="type" value="{type}">
	<input type=hidden name="hidden_manager_lid" value="{hidden_manager_lid}">
	<input type=hidden name="context">

	<table border="0" width="90%">
		<tr bgcolor="{color_bg1}" align="right">
			<td align="left"><input type="button" value="{lang_back}" onclick="javascript:location.href='index.php?menuaction=expressoAdmin1_2.uimanagers.list_managers'"></td>
			<td><input type="button" value="{lang_save}" onclick="javascript:validade_managers_data('{type}');"></td>
		</tr>
		<tr>
			<td valign="top" colspan="2">
				<table border=0 width=100%>
					<tr bgcolor="{color_bg2}">
						<td colspan="2"><b>{lang_manager}</b></td>
					</tr>				
				    	<tr bgcolor="{color_font1}">
				    		<td width="25%">{lang_search_for_manager}:</td>
						<td>
							<input type="text" id="manager_lid" {input_manager_lid_disabled} name="manager_lid" value="{manager_lid}" size=30 autocomplete="off" onkeyup="javascript:search_manager(this.value)"></input>
							<font color="red"><span id="ea_span_searching_manager">&nbsp;</span></font>
						</td>
					</tr>
					
					<tr bgcolor="{color_font1}" style="display:{display_manager_select}">
						<td width="25%">{lang_found_managers}:</td>
						<td>
							<select id="ea_select_managers" name="ea_select_manager" style="width: 400px" size="10">{ea_select_managers}</select>
						</td>
					</tr>
					<tr bgcolor="{color_font2}">
						<td>{lang_context}:</td>
						<td id="td_input_context">
							<select id="ea_select_contexts">{options_contexts}</select>
							<span style="cursor:pointer" onclick="javascript:add_input_context();">+</span>
							<br>
							<span id="ea_spam_warn" style="color:red">&nbsp;</span>
							{input_context_fields}
						</td>
					</tr>
					<tr bgcolor="{color_bg2}">
						<td colspan="2"><b>{lang_access_control_list}</b>&nbsp;&nbsp;&nbsp;<input type="button" value="{lang_select_all}" onclick="select_all_acls('ea_table_acl');"></td>
					</tr>
					<tr>
						<td colspan="2">
							<!-- BEGIN ACL CONTROL -->
							<table id="ea_table_acl">
								<tr bgcolor="{color_font1}" align='right'>
									<td width="20%">{lang_add_users}:</td>
									<td width="2%"><input type="checkbox" name="acl_add_users" value="1" {acl_add_users}></td>
									<td width="20%">{lang_add_groups}:</td>
									<td width="2%"><input type="checkbox" name="acl_add_groups" value="16" {acl_add_groups}></td>							
									<td width="20%">{lang_add_email_lists}:</td>
									<td width="2%"><input type="checkbox" name="acl_add_maillists" value="256" {acl_add_maillists}></td>
									<td width="20%">{lang_create_organizations}:</td>
									<td width="2%"><input type="checkbox" name="acl_create_sectors" value="4096" {acl_create_sectors}></td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_edit_users}:</td>
									<td><input type="checkbox" name="acl_edit_users" value="2" {acl_edit_users}></td>
									<td>{lang_edit_groups}:</td>
									<td><input type="checkbox" name="acl_edit_groups" value="32" {acl_edit_groups}></td>
									<td>{lang_edit_email_lists}:</td>
									<td><input type="checkbox" name="acl_edit_maillists" value="512" {acl_edit_maillists}></td>
									<td>{lang_edit_organizations}:</td>
									<td><input type="checkbox" name="acl_edit_sectors" value="8192" {acl_edit_sectors}></td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_delete_users}:</td>
									<td><input type="checkbox" name="acl_delete_users" value="4" {acl_delete_users}></td>
									<td>{lang_delete_groups}:</td>
									<td><input type="checkbox" name="acl_delete_groups" value="64" {acl_delete_groups}></td>
									<td>{lang_delete_email_lists}:</td>
									<td><input type="checkbox" name="acl_delete_maillists" value="1024" {acl_delete_maillists}></td>
									<td>{lang_delete_organizations}:</td>
									<td><input type="checkbox" name="acl_delete_sectors" value="16384" {acl_delete_sectors}></td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_rename_users}:</td>
									<td><input type="checkbox" name="acl_rename_users" value="8388608" {acl_rename_users}></td>
									<td>{lang_edit_email_attribute_from_the_groups}:</td>
									<td><input type="checkbox" name="acl_edit_email_groups" value="67108864" {acl_edit_email_groups}></td>
									<td>{lang_edit_SCL_email_lists}:</td>
									<td><input type="checkbox" name="acl_edit_scl_email_lists" value="1073741824" {acl_edit_scl_email_lists}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_manipulate_corporative_information}:</td>
									<td><input type="checkbox" name="acl_manipulate_corporative_information" value="268435456" {acl_manipulate_corporative_information}></td>
									<td>&nbsp;</td><td>&nbsp;</td>
									<td>{lang_add_external_emails}:</td>
									<td><input type="checkbox" name="acl_add_externalEmail" value="34359738368" {acl_add_externalEmail}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_view_user}:</td>
									<td><input type="checkbox" name="acl_view_users" value="33554432" {acl_view_users}></td>
									<td>{lang_add_institutional_accounts}:</td>
									<td><input type="checkbox" name="acl_add_institutional_accounts" value="4294967296" {acl_add_institutional_accounts}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_edit_users_picture}:</td>
									<td><input type="checkbox" name="acl_edit_users_picture" value="536870912" {acl_edit_users_picture}></td>
									<td>{lang_edit_institutional_accounts}:</td>
									<td><input type="checkbox" name="acl_edit_institutional_accounts" value="8589934592" {acl_edit_institutional_accounts}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_edit_users_phonenumber}:</td>
									<td><input type="checkbox" name="acl_edit_users_phonenumber" value="2147483648" {acl_edit_users_phonenumber}></td>
									<td>{lang_remove_institutional_accounts}:</td>
									<td><input type="checkbox" name="acl_remove_institutional_accounts" value="17179869184" {acl_remove_institutional_accounts}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_change_users_password}:</td>
									<td><input type="checkbox" name="acl_change_users_password" value="128" {acl_change_users_password}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_change_users_quote}:</td>
									<td><input type="checkbox" name="acl_change_users_quote" value="262144" {acl_change_users_quote}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td style="{display_samba_suport}">{lang_edit_SAMBA_users_attributes}:</td>
									<td style="{display_samba_suport}"><input type="checkbox" name="acl_edit_sambausers_attributes" value="32768" {acl_edit_sambausers_attributes}></td>
									<td style="{display_samba_suport}">{lang_create_computers}:</td>
									<td style="{display_samba_suport}"><input type="checkbox" name="acl_create_computers" value="1048576" {acl_create_computers}></td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_set_default_users_password}:</td>
									<td><input type="checkbox" name="acl_set_user_default_password" value="524288" {acl_set_user_default_password}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td style="{display_samba_suport}">{lang_edit_SAMBA_domains}:</td>
									<td style="{display_samba_suport}"><input type="checkbox" name="acl_edit_sambadomains" value="16777216" {acl_edit_sambadomains}></td>
									<td style="{display_samba_suport}">{lang_edit_computers}:</td>
									<td style="{display_samba_suport}"><input type="checkbox" name="acl_edit_computers" value="2097152" {acl_edit_computers}></td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_empty_user_inbox}:</td>
									<td><input type="checkbox" name="acl_empty_user_inbox" value="134217728" {acl_empty_user_inbox}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td style="{display_samba_suport}">{lang_delete_computers}:</td>
									<td style="{display_samba_suport}"><input type="checkbox" name="acl_delete_computers" value="4194304" {acl_delete_computers}></td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font2}" align='right'>
									<td>{lang_show_sessions}:</td>
									<td><input type="checkbox" name="acl_view_global_sessions" value="65536" {acl_view_global_sessions}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>{lang_radius_filter}:</td>
									<td><input type="checkbox" name="acl_edit_radius" value="68719476736" {acl_edit_radius}/></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr bgcolor="{color_font1}" align='right'>
									<td>{lang_view_logs}:</td>
									<td><input type="checkbox" name="acl_view_logs" value="131072" {acl_view_logs}></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
							</table>
							<!-- END ACL CONTROL -->
						</td>
					</tr>						
					<tr bgcolor="{color_bg2}">
						<td colspan="4"><b>{lang_applications}</b>&nbsp;&nbsp;&nbsp;<input type="button" value="{lang_select_all}" onclick="select_all_acls('ea_table_app');"></td>
					</tr>
					<tr>
						<td colspan="2">
							<table id="ea_table_app">
								<tr>
									{app_list}
								</tr>
							</table>
						</td>
					</tr>						
				</table>
			</td>
		</tr>
		<tr bgcolor={color_bg1} align="right">
			<td align="left"><input type="button" value="{lang_back}" onclick="javascript:location.href='index.php?menuaction=expressoAdmin1_2.uimanagers.list_managers'"></td>
			<td><input type="button" value="{lang_save}" onclick="javascript:validade_managers_data('{type}');"></td>
		</tr>
	</table>
	</div>
</form>
{error_messages}
<!-- END form -->
