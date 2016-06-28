<!-- BEGIN list -->
<script src="prototype/plugins/jquery/jquery-latest.min.js"></script>
<script>
	function refresh_grp_of_names() {
		if ( $('input[name=grp_of_names]')[0].checked ) {
			$('form').addClass('grp_of_names');
			$('#grp_of_names_type').show();
		} else {
			$('form').removeClass('grp_of_names')
			$('#grp_of_names_type').hide();
		}
	}
</script>
<style>
	.hide, form.grp_of_names table.grp_of_names th, form.grp_of_names div.grp_of_names {
		display: none;
	}
	form.grp_of_names table.grp_of_names th:first-child {
		display: block !important;
	}
	div.align-middle input, div.align-middle spam {
		vertical-align: middle;
	}
</style>

<center>
<form action="{form_action}" method="POST" name="app_form" class="{class_form}">
<input type="hidden" name="defaultDomain"       value="{defaultDomain}">
<input type="hidden" name="dn"                  value="{dn}">
<input type="hidden" name="gidnumber"           value="{gidnumber}">
<input type="hidden" name="ldap_context"        value="{ldap_context}">
<input type="hidden" name="manager_context"     value="{manager_context}">
<br>
<table width="90%" border="0" cellspacing="0" cellpading="0" class="grp_of_names">
	<tr>
		<th id="tab1" class="activetab" onclick="javascript:tab.display(1);">
			<a href="#" tabindex="0" accesskey="1" onfocus="tab.display(1);" onclick="tab.display(1); return(false);">
				{lang_general_information}
			</a>
		</th>
		<th id="tab2" class="activetab" onclick="javascript:tab.display(2);">
			<a href="#" tabindex="0" accesskey="2" onfocus="tab.display(2);" onclick="tab.display(2); return(false);">
				{lang_aplication_permission}
			</a>
		</th>
		<th id="tab3" class="activetab" onclick="javascript:tab.display(3);">
			<a href="#" tabindex="0" accesskey="3" onfocus="tab.display(3);" onclick="tab.display(3); return(false);">
				{lang_sending_control_mail}
			</a>
		</th>
		<th id="tab4" class="activetab" onclick="javascript:tab.display(4);">
			<a href="#" tabindex="0" accesskey="4" onfocus="tab.display(4);" onclick="tab.display(4); return(false);">
				{lang_block_personal_data_edit}
			</a>
		</th>
	</tr>
</table>
<br>

<div id="tabcontent1" class="inactivetab">
	<table border="0" width=75% cellspacing="4">
		<tr>
			<td width="40%" bgcolor="#DDDDDD">
				{lang_search_organization}:<br>
				<input type="text" id="organization_search" autocomplete="off" size=20 onKeyUp="javascript:search_organization(event, this.value, 'ea_combo_org_info');" onBlur="javascript:sinc_combos_org(context.value, ea_check_allUsers.checked); get_available_sambadomains(context.value, '{type}')">
				<br>
				{lang_group_organization}:<br>
				<select id="ea_combo_org_info" name="context" onchange="javascript:sinc_combos_org(this.value, ea_check_allUsers.checked); get_available_sambadomains(this.value, '{type}')">{combo_manager_org}</select><br>
							
				{lang_group_name}: <font color="blue">Ex: grupo-celepar-rh</font><br>
				<input name="cn" size="35" value="{cn}" autocomplete="off" onblur="javascript:groupEmailSuggestion('{concatenateDomain}')"><br>

				<div class="align-middle {class_div_radio}">
					<spam>{lang_external_group}? </spam>
					<input type="checkbox" name="grp_of_names" onChange="refresh_grp_of_names()" {grp_of_names_value}>
					<div id="grp_of_names_type" class="align-middle" style="display:none;">
						<input type="radio" name="grp_of_names_type" value="groupOfNames" {grp_of_names_type_1_value}><spam>{lang_groupOfNames}</spam>
						<input type="radio" name="grp_of_names_type" value="groupOfUniqueNames" {grp_of_names_type_2_value}><spam>{lang_groupOfUniqueNames}</spam>
					</div>
				</div>
				<div class="grp_of_names">
					{lang_email}:<br>
					<input name="email" size="60" value="{email}" {disable_email_groups} autocomplete="off"><br>
				</div>
				{lang_description}:<br>
				<input name="description" size="60" value="{description}" autocomplete="off"><br>
							
				<div id="ea_div_display_samba_options" style={display_samba_options}>
					<table border="0">
						<tr bgcolor={row_on}>
							<td>{lang_use_samba_attributes}:</td>
							<td>						
								<input type="checkbox" {use_attrs_samba_checked} name="use_attrs_samba" onChange="javascript:use_samba_attrs(this.checked)">
							</td>
						</tr>
						<tr>
							<td>{lang_domain}:</td>
							<td>
								<select {disabled_samba} name="sambasid" id="ea_combo_sambadomains">
									{sambadomainname_options}
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="grp_of_names align-middle">
					<spam>{lang_do_not_show_this_group}? </spam>
					<input type="checkbox" {phpgwaccountvisible_checked} name="phpgwaccountvisible">
				</div>

				<b>{lang_group_users} (<font color=red>{user_count}</font>):</b>
				<button type="button" onClick="javascript:popup_group_info();">{lang_text}</button>
				<br>
							
				<select id="ea_select_usersInGroup" name="members[]" style="width: 400px" multiple size="13">{ea_select_usersInGroup}</select>
			</td>
						
			<td width="20%" valign="middle" align="center" bgcolor="#DDDDDD">
				<br><br><br><br><br><br>
				<button type="button" onClick="javascript:add_user2group();"><img src="expressoAdmin1_2/templates/default/images/add.png" style="vertical-align: middle;">&nbsp;{lang_add_user}</button>
				<br><br>
				<button type="button" onClick="javascript:remove_user2group();"><img src="expressoAdmin1_2/templates/default/images/rem.png" style="vertical-align: middle;">&nbsp;{lang_remove_user}</button>
			</td>
						
			<td width="40%" valign="bottom" bgcolor="#DDDDDD">
				{lang_search_organization}:<br>
				<input type="text" id="organization_search" autocomplete="off" size=20 onKeyUp="javascript:search_organization(event, this.value, 'ea_combo_org_groups');" onBlur="javascript:get_available_users(org_context.value, ea_check_allUsers.checked);">
				<br>						
						
				{lang_organizations}:<br>
				<select name="org_context" id="ea_combo_org_groups" onchange="javascript:get_available_users(this.value, ea_check_allUsers.checked);">{combo_all_orgs}</select>
							
				<br>
				<div class="align-middle">
					<input type="checkbox" name="ea_check_allUsers" id="ea_check_allUsers" onclick="javascript:get_available_users(document.forms[0].org_context[0].value, this.checked);">
					<spam>{lang_show_users_from_all_sub-organizations}.</spam>
				</div>
				<br><br>
							
				{lang_search_user}:<br>
				<input id="ea_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:optionFinderTimeout(this)"><br>
							
				<font color="red"><span id="ea_span_searching">&nbsp;</span></font>
				<br>
				<b>{lang_users}:</b><br>
				<select id="ea_select_available_users" style="width: 400px" multiple size="13"></select>
			</td>
		</tr>
	</table>
</div>

<div id="tabcontent2" class="inactivetab">
	<table width="75%" border="0" cellspacing="4">
		<tr>
        	<td colspan="3">
        		<table width="100%" border="0" cols="6">
					{apps}
				</table>
			</td>
		</tr>
	</table>
</div>

<div id="tabcontent3" class="inactivetab">
	<table width="75%" border="0" cellspacing="4">
		<tr>
			<td width="40%" valign="bottom" bgcolor="#DDDDDD">

				{lang_apply_send_control_list_to_this_list}?<input type="checkbox" {accountRestrictive_checked} name="accountrestrictive">
				<br><br>
				{lang_participants_from_the_list_can_send_email_to_this_list}? <input type="checkbox" {participantCanSendMail_checked} name="participantcansendmail"><br>
				<br>
				<b>{lang_users_who_can_send_email_to_this_list}:</b><br>
				<select id="ea_select_users_scm" name="members_scm[]" style="width:400px; height:200px" multiple size="13">{ea_select_users_scm}</select>

			</td>
						
			<td width="20%" valign="middle" align="center" bgcolor="#DDDDDD">
				<br><br><br><br><br><br>
				<button type="button" onClick="javascript:add_user2scm();"><img src="expressoAdmin1_2/templates/default/images/add.png" style="vertical-align: middle;" >&nbsp;{lang_add_user}</button>
				<br><br>
				<button type="button" onClick="javascript:remove_user2scm();"><img src="expressoAdmin1_2/templates/default/images/rem.png" style="vertical-align: middle;" >&nbsp;{lang_remove_user}</button>
			</td>
			
			<td width="40%" valign="bottom" bgcolor="#DDDDDD">
				{lang_organizations}:<br>
				<select name="org_context" id="ea_combo_org_groups" onchange="javascript:get_available_users(this.value, ea_check_allUsers.checked);">{combo_all_orgs}</select>			
					
				<br>
				<input type="checkbox" name="ea_check_allUsers" id="ea_check_allUsers" onclick="javascript:get_available_users(org_context.value, this.checked);">
				{lang_show_users_from_all_sub-organizations}.
				<br><br>
							
				{lang_search_user}:<br>
				<input id="ea_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:optionFinderTimeout(this)"><br>
						
				<font color="red"><span id="ea_span_searching">&nbsp;</span></font>
				<br>
				<b>{lang_users}:</b><br>
				<select id="ea_select_available_users_scm" style="width:400px; height:200px" multiple size="13"></select>
			</td>
		</tr>
	</table>
</div>

<div id="tabcontent4" class="inactivetab">
<table width="75%" border="0" cellspacing="4">
		<tr>
        	<td colspan="3">
        		<table width="70%" border="0" cols="6">
					{personal_data_fields}
				</table>
			</td>
		</tr>
	</table>
</div>


<br><br>
<table width="90%" border="0" cellspacing="0" cellpading="1">
	<tr>
		<td width="90%" align="left"  class="td_left" bgcolor="{color_bg1}">
			<input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'">
		</td>
		<td width="10%" align="right" class="td_right" bgcolor="{color_bg1}">
			<input type="button" value="{lang_save}" onClick="javascript:validate_fields('{type}','{restrictionsOnGroup}');">
		</td>
	</tr>
</table>

<script type="text/javascript">
	
	var tab = new Tabs(4,'activetab','inactivetab','tab','tabcontent','','','tabpage');
	tab.display(1);
	
</script>
</form>
</center>

<!-- END list -->
