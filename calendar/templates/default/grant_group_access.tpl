<div align="center" style="width:100%">
<input type="hidden" id="txt_loading" value="{lang_Loading}">
<input type="hidden" id="txt_searching" value="{lang_Searching}">
<input type="hidden" id="txt_users" value="{lang_Users}">
<input type="hidden" id="txt_groups" value="{lang_Groups}">
<input type="hidden" id="txt_confirm" value="{lang_confirm}">
<input type="hidden" id="txt_success" value="{lang_success}">
<input type="hidden" id="txt_exist" value="{lang_exist}">
<input type="hidden" id="txt_nouser" value="{lang_nouser}">
<input type="hidden" id="txt_nogroup" value="{lang_nogroup}">
<input type="hidden" id="txt_nopermissiontype" value="{lang_nopermissiontype}">
<input type="hidden" id="txt_typemoreletters" value="{lang_typemoreletters}">
<input type="hidden" id="template_set" value="{template_set}">
<table width="80%" border="0">
<tr>
<td align='center' valign="bottom"><br><b>{lang_User_to_grant_access}</b><br>
	<font color="red"><span id="cal_span_searching1">&nbsp;</span></font>
	<br>{lang_Search_for}: <input type="text" id="cal_input_searchUser1" name="cal_input_searchUser1" value="" size=30 autocomplete="off" onkeyup="javascript:search_object(this,'cal_span_searching1','user','u')"/>
	<br><br>
	<select id="user" style="width: 300px" size="13"></select>
</td>
<td width="20px" valign="middle" align="left"><br><br><br><br>
<button type="button" onClick="javascript:add_user();"><img src="{template_set}/images/add.png" style="vertical-align: middle;" >{lang_Add}</button>
<br><br><b>{lang_access_type}</b><br>
<input type="checkbox" id="right_L" value="L"/>{lang_read}<br>
<input type="checkbox" id="right_A" value="A"/>{lang_add}<br>
<input type="checkbox" id="right_E" value="E"/>{lang_edit}<br>
<input type="checkbox" id="right_R" value="R"/>{lang_delete}<br>
<input type="checkbox" id="right_P" value="P"/>{lang_private}
</td>
<td align='center' valign="bottom"><br><b>{lang_Group_to_share_calendar}</b><br>
	<font color="red"><span id="cal_span_searching2">&nbsp;</span></font>
	<br>{lang_Search_for}:	<input type="text" id="cal_input_searchUser2" name="cal_input_searchUser2" value="" size=30 autocomplete="off" onkeyup="javascript:search_object(this,'cal_span_searching2','group','g')"/><br><br>
	<select id="group" style="width: 300px" size="13"></select>
</td>
</tr>
	<tr height="20px"></tr>
	<tr bgcolor="{tr_color}" style="font-size:0.8em">
		<th align="left" width="25%" nowrap>&nbsp;{lang_granted_user}</th>
		<th align="center" width="1%">{lang_access_type}</th>
		<th align="left" width="40%" nowrap>&nbsp;{lang_shared_group}</th>
		<th width="1%">&nbsp;</th>
	</tr>
<tbody id='tbody_list' >
<!-- BEGIN list -->
	<tr id='{select}' bgcolor="{tr_color_header}">
		<td>
			<b>&nbsp;&nbsp;{granted_user}</b>
		</td>
		<td align="center">
			{access_type}
		</td>		
		<td>
			&nbsp;&nbsp;{shared_group}
		</td>
		<td align="center">
			<button  title="{lang_Remove}" type="button" onClick="javascript:remove_user('{select}');"><img alt="{lang_Remove}" src="{template_set}/images/delete.png" style="vertical-align: middle;"/></button>
		</td>
	</tr>
<!-- END list -->
</tbody>
</table>
<br><br>
</div>
{scripts}