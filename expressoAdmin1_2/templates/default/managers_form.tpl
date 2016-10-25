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
							{template_control_list}
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
