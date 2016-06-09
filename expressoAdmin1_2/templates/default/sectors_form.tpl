<!-- BEGIN list -->
<p>
  <table border="0" align="center">
  <tr bgcolor="{th_bg}">
   <td>{lang_context}: {context}</td>
  </tr>
 </table>
 
  <form method="POST" action="{action}">
  	<table border="0" align="center">
  		<tr>  
   			<td>
   				{lang_sector_name}:
   			</td>
   			<td>
				<input type="text" {disable} autocomplete="off" name="sector" value={sector}>
   			</td>
 		</tr>
  		<tr>  
   			<td>
   				{lang_do_not_show_this_sector}:
   			</td>
   			<td>
				<input type="checkbox" name="hide_sector" {hide_sector_checked}>
   			</td>
 		</tr>
 		<tr>
   			<td align="left" colspan="2">
     			<input type="submit" name="button_submit" value={lang_save}>
     			<input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'">
     			<input type="hidden" name="context" value="{context}">
   			</td>
 		</tr>
  	</table>
 </form>
 {error_messages}
<!-- END list -->
