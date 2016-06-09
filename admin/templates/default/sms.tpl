<!-- BEGIN sms_page -->
<form method="POST" action="{action_url}">
<table align="center" width="60%" cellspacing="2" style="border: 1px solid #000000;">
	<tr class="th">
		<td colspan="2">&nbsp;<b>{lang_title}</b></td>
	</tr>
	<tr class="row_off">
		<td>{lang_sms_enabled}:</td>
		<td>
			<select name="sms_enabled">
				<option value="0"{value_sms_enabled_false}>{lang_no}</option>
				<option value="1"{value_sms_enabled_true}>{lang_yes}</option>
			</select>
	</tr>
	<tr class="row_on">
		<td>{lang_sms_wsdl}:</td>
		<td><input name="sms_wsdl" value="{value_sms_wsdl}" size="80"></td>
	</tr>
	<tr class="row_off">
		<td>{lang_sms_user}:</td>
		<td><input name="sms_user" value="{value_sms_user}"></td>
	</tr>
	<tr class="row_on">
		<td>{lang_sms_passwd}</td>
		<td><input type="password" name="sms_passwd"></td>
	</tr>
	<tr class="th">
		<td colspan="2">&nbsp;<b>{lang_groups_ldap}</b></td>
	</tr>
	<tr class="row_on">
		<td colspan="2">
			{lang_organizations} :
			&nbsp;
			<select id="admin_organizations_ldap" name="organizations" onchange="javascript:ldap.search(this,'$this.bosms.getGroupsLdap');">
				{ous_ldap}
			</select>
			<span id="admin_span_loading" style="color:red;visibility:hidden;">&nbsp;{lang_load}</span>
		</td>
	</tr>
	<tr class="row_on">
		<td colspan="2">
		<table align="center" cellspacing="0">
			<tr>
				<td class="row_off">	
					{lang_groups_ldap} :
					<br/>
					<select id="groups_ldap" size="10" style="width: 300px" multiple></select>
				</td>
				<td class="row_off">
					<input type="button" value="Adicionar" onclick="javascript:ldap.add('groups_sms');" />
					<br/>
					<br/>
					<input type="button" value="Remover" onclick="javascript:ldap.remove('groups_sms');" />
				</td>
				<td class="row_off">
					{lang_groups_selected} :
					<br/>
					<select id="groups_sms" size="10" style="width: 300px" multiple name="sms_groups[]">{groups_sms}</select>
				</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr class="th">
		<td colspan="2">&nbsp;<b>{lang_credentials}</b></td>
	</tr>
	<tr class="row_on">
		<td colspan="2">
			<style>
				#grps_sortable { list-style: none; margin: 0; padding: 0; width: 100%; cursor: row-resize;}
				#grps_sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; height: 18px; }
				#grps_sortable li span { position: absolute; margin-left: -1.3em; }
				#grps_sortable li div { float: right; margin: 1px 3px 3px 26px; }
				#grps_sortable li .grp_title { float: left; margin: 3px 3px; }
				#grps_hidden { display: none; }
			</style>
			<ul id="grps_sortable">{grps_sortable}</ul>
			<ul id="grps_hidden">{grp_default}</ul>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
		  <input type="submit" name="save" value="{lang_save}" onclick="javascript:ldap.select_('groups_sms');">
		  <input type="submit" name="cancel" value="{lang_cancel}">
		  <br>
		</td>
	</tr>
</table>
</form>
<!-- END sms_page -->
