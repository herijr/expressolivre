<!-- BEGIN list -->
<p>
  <div align="center">
   <table border="0" width="95%">
    <tr>
	 	<td align="left" width="25%">
	  		<form name="form" method="POST" action="{add_action}">
	  			<input type="submit" value="{lang_add_email_lists}" "{add_email_lists_disabled}">
				<input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'">
	  		</form>
	 	</td>
	 	<td align="center" "left" width="50%">
			{lang_context}: <font color="blue">{context_display}</font>
	 	</td>
     	<td align="right" "left" width="25%">
     		<form method="POST" action="{accounts_url}" name="form_query">
				<table border="0">
					<tr>
     					<td>
							{lang_search}:
						</td>
						<td>
							<input type="text" name="query" autocomplete="off" value="{query}">
						</td>
						<td>
							<img style="cursor:pointer" src="./expressoAdmin1_2/templates/default/images/search.gif" border="0" onclick="javascript:form_query.submit();"> 
						</td>
     				</tr>
		     	</table>
      		</form>
     	</td>
    </tr>
   </table>
  </div>
 
 <div align="center">
  <table border="0" width="95%">
   <tr bgcolor="{th_bg}">
    <td width="20%">{lang_email_lists_logins}</td>
    <td width="40%">{lang_email_lists_names}</td>
    <td width="40%">{lang_email}</td>
    <td width="8%" align="center">{lang_edit}</td>
	<td width="8%" align="center"><span title='Sending Control List'>{lang_scl}<span></td>
	
	
    <td width="8%" align="center">{lang_to_delete}</td>
   </tr>

   {rows}

  </table>
 </div>
<!-- END list -->

<!-- BEGIN row -->
   <tr bgcolor="{tr_color}">
    <td width="20%">{row_uid}</td>
    <td width="30%">{row_name}</td>
    <td width="20%">{row_email}</td>
    <td width="6%" align="center">{edit_link}</td>
	<td width="6%" align="center">{scl_link}</td>
    <td width="7%" align="center">{delete_link}</td>
   </tr>
<!-- END row -->

<!-- BEGIN row_empty -->
   <tr>
    <td colspan="5" align="center"><font color="red"><b>{message}</b></font></td>
   </tr>
	<tr>
		<td><input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'"></td>
	</tr>
<!-- END row_empty -->