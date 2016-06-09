<!-- BEGIN list -->
<p>
 <table class="collapse" border="0" width="55%" align="center">
  <tr>
   <td><input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'"></td>
  </tr>
  <tr bgcolor="{th_bg}">
   <td>{lang_name}</td>
   <td>{lang_add_sub_sectors}</td>
   <td>{lang_edit}</td>
   <td>{lang_delete}</td>
  </tr>
  {rows}
  <tr>
   <td><input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'"></td>
  </tr>
 </table>
</p>
<!-- END list -->

<!-- BEGIN row -->
  <tr class="collapse" bgcolor="{tr_color}">
   <td style="{td_style}">{sector_name}</td>
   <td width="15%">{add_link}</td>
   <td width="5%">{edit_link}</td>
   <td width="5%">{delete_link}</td>
  </tr>
<!-- END row -->

<!-- BEGIN row_empty -->
  <tr>
   <td colspan="5" align="center">{message}</td>
  </tr>
<!-- END row_empty -->