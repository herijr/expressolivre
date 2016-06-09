<script type="text/javascript" src="phpgwapi/js/wz_dragdrop/wz_dragdrop.js"></script>
<script type="text/javascript" src="phpgwapi/js/dJSWin/dJSWin.js"></script>
<script src='{url_template}/../../js/edit_exmail.js' type='text/javascript'></script>
<script src='{module_name}/inc/load_lang.php' type='text/javascript'></script>
<script src='{module_name}/js/connector.js' type='text/javascript'></script>
<script type='text/javascript'>var DEFAULT_URL = '{module_name}/controller.php?action=';</script> 
<font color="#FF0000">{error_msg}</font>
<form action="index.php">
<input type="hidden" id="txt_loading" value="{lang_loading}">
<input type="hidden" id="txt_searching" value="{lang_searching}">
<input type="hidden" id="txt_users" value="{lang_users}">
<input type="hidden" id="txt_groups" value="{lang_groups}">

<input type="hidden" name="menuaction" value="calendar.uicalendar.delegate_event">
<input type="hidden" name="event" value="{id_event}">
<input type="hidden" name="delegator" value="{delegator}">
<input type="hidden" name="date" value="{date}">
<table>
	<tr>
		<td>
			{lang_ou}
		</td>
		<td>
			<select name="org_context" id="combo_org" onchange="javascript:get_available_only_users('{module_name}',this.value,'{recursive}');">{options}</select>
		</td>
	</tr>
	<tr>
		<td colspan='2'><font color="red"><span id="cal_span_searching">&nbsp;</span></font></td>
	</tr>
	<tr>
		<td>
			{lang_search_for}
		</td>
		<td>
			<input value="" id="cal_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:optionFinderTimeout(this,0)"><br>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			{lang_avaliable_users}<br>
			<select name="delegated" id="user_list_in" style="width: 300px" size="13"></select>'
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<input type="submit" value="{Delegate}">
		</td>
	</tr>
	
</table>
</form>
<script type='text/javascript'>
	setTimeout("get_available_only_users('{module_name}',document.getElementById('combo_org').value,'{recursive}');",1000);
</script>
