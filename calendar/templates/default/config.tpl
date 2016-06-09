<!-- BEGIN header -->
<script type="text/javascript">

function disableMinNum(valor){
  if( valor == "True" ){
	document.getElementById("minNum").disabled = true;
	document.getElementById("minNum").value = 0;
  }
  else if (valor == "False"){
	document.getElementById("minNum").disabled = false;
  }
}
</script>
<form name="adminForm" method="POST" action="{action_url}" onSubmit="return minValue()">
<table border="0" align="center">
   <tr bgcolor="{th_bg}">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{title}</b></font></td>
   </tr>
<!-- END header -->

<!-- BEGIN body -->
   <tr bgcolor="{row_on}">
    <td colspan="2">&nbsp;</td>
   </tr>
   <tr bgcolor="{row_off}">
    <td colspan="2"><b>{lang_Calendar_settings}</b></td>
   </tr>
   <tr bgcolor="{row_on}">
    <td>{lang_Do_you_wish_to_autoload_calendar_holidays_files_dynamically?}</td>
    <td>
     <select name="newsettings[auto_load_holidays]">
      <option value=""{selected_auto_load_holidays_False}>{lang_No}</option>
      <option value="True"{selected_auto_load_holidays_True}>{lang_Yes}</option>
     </select>
    </td>
   </tr>
   <tr bgcolor="{row_off}">
    <td>{lang_Location_to_autoload_from}:</td>
    <td>
     <select name="newsettings[holidays_url_path]">
      <option value="localhost"{selected_holidays_url_path_localhost}>localhost</option>
      <option value="http://www.phpgroupware.org/cal"{selected_holidays_url_path_http://www.phpgroupware.org/cal}>www.phpgroupware.org</option>
     </select>
    </td>
   <tr bgcolor="{row_on}">
    <td colspan="2">&nbsp;</td>
   </tr>    
   </tr>
    <tr bgcolor="{row_off}">
    <td colspan="2"><b>{lang_ExpressoMail_Plugin_Calendar}</b></td>
   </tr>
   <tr bgcolor="{row_on}">
    <td>{lang_Select_module_version_expressoMail_(if_available)}</td>
    <td>
     <select name="newsettings[cal_expressoMail]">
      <option value="False"{selected_cal_expressoMail_False}>{lang_dont_use_any_plugin}</option>
      <option value="1.0"{selected_cal_expressoMail_1.0}>Expresso 1.0</option>
      <option value="1.2"{selected_cal_expressoMail_1.2}>Expresso 1.2</option>
     </select>
    </td>
   </tr>
    <tr bgcolor="{row_off}">
    <td>{lang_Select_type_tree_view}</td>
    <td>
     <select name="newsettings[cal_type_tree_participants]">
      <option value="3" {selected_cal_type_tree_participants_3}>{lang_all_levels}</option>
      <option value="2" {selected_cal_type_tree_participants_2}>{lang_first_level_with_recursive_search}</option>
      <option value="1" {selected_cal_type_tree_participants_1}>{lang_all_levels_with_not_recursive_search}</option>      
     </select>
    </td>
   </tr>
   
   <tr bgcolor="{row_on}">
     <td>{lang_Do_you_wish_enable_autosearch?}</td>
     <td>
       <select id="autoSearch" name="newsettings[auto_search]" onchange="disableMinNum(this.value)">
        <option value="False"{selected_auto_search_False}>{lang_No}</option>
        <option value="True"{selected_auto_search_True}>{lang_Yes}</option>
       </select>
     </td>
   </tr>
   
   <tr bgcolor="{row_off}">
       <td>{lang_Minimum_number_of_characters_to_start_the_search_for_participants}</td>
       <td>
          <input type="text" id="minNum" value="{value_min_num_characters}" name="newsettings[min_num_characters]    " size=2 maxlength=2 />
       </td>
   </tr>

   <script> disableMinNum(document.getElementById("autoSearch").value); </script>
<!-- END body -->

<!-- BEGIN footer -->
  <tr bgcolor="{th_bg}">
    <td colspan="2">
&nbsp;
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" name="submit" value="{lang_submit}">
      <input type="submit" name="cancel" value="{lang_cancel}">
     </td>
  </tr>
</table>
</form>
<!-- END footer -->
