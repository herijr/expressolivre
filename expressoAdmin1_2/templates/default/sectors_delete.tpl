<!-- BEGIN list -->
<script src="prototype/plugins/jquery/jquery-latest.min.js"></script>
<form name="form" method="POST" action="{action}" onsubmit="return confirm('{lang_do_you_really_want_delete_this_sector}?');">
 <input type="hidden" name="context" value="{context}">
 <table border="0" width="80%" align="center">
  <tr>
   <td colspan="2" align="right" bgcolor="{color_bg1}">
    <input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'">
   </td>
  </tr>
  <tr>
   <td align="center">
    <font size="5">
     {lang_do_you_really_want_delete_this_sector}?
    </font>
    <font size="5" color="red">
     ({sector})
    <font size="5">
    <br>
   </td>
  </tr>
  <tr>
   <td align="left">
    <font size="4">
     {lang_brief}:</br>
    </font>
    {resume_list}</br>
    <font size="3" color="red">
     {lang_all_users_groups_and_subsectors_from_this_sector_will_be_deleted}!
    </font>
   </td>
  </tr>
  <tr>
   <td align="left">
    <font size="3">
     {lang_sectors} ({sectors_count}):
    </font family="monospace">
    <br>
    <div class="info">{sectors_list}</div>
    <br>
   </td>
  </tr>
  {rows}
  <tr>
   <td align="left" bgcolor="{color_bg1}">
    <input type="submit" name="delete" {delete_disable} value="{lang_delete}">
   </td>
   <td align="right" bgcolor="{color_bg1}">
    {lang_i_really_want_delete_this_sector}! <input type="checkbox" name="confirm_chk">
   </td>
  </tr>	
 </table>
</form>
{error_messages}
{loop_script}
<!-- END list -->

<!-- BEGIN row -->
  <tr>
   <td align="left">
    <font size="3">{lang_section} ({section_count}):</font><br>
    {section_list}<br>
   </td>
  </tr>
<!-- END row -->
