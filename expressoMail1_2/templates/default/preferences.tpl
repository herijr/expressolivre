<script src="js/main.js"></script>
<center>
<script language="JavaScript" type="text/javascript">
<!--
  function exibir_ocultar(situacao)
  {
     var xtr1 = document.getElementById('tr01');
     var xtr2 = document.getElementById('tr02');
     var xc1 = document.getElementById('c01');
     var xc2 = document.getElementById('c02');
     if(situacao==true)
       {
    xtr1.style.display='';
    xtr2.style.display='';
    xc1.checked=false;
    xc2.checked=false;
       }
      else
       {
    xtr1.style.display='none';
    xtr2.style.display='none';
    xc1.checked=false;
    xc2.checked=false;
       }
  }
 -->
</script> 
<table width="65%" border="0" cellspacing="2" cellpadding="2">

<form  method="POST" action="{save_action}" onSubmit="selectValues()">
<input type="hidden" name="try_saved" value="false">
    <tr bgcolor="{th_bg}">
        <td colspan="2" align="center">{lang_config_expressoMail}</td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_max_emails_per_page}</td>
        <td align="center">
			<select name="max_emails_per_page">
				<option {option_25_selected} value="25">25</option>
				<option {option_50_selected} value="50">50</option>
				<option {option_75_selected} value="75">75</option>
				<option {option_100_selected} value="100">100</option>
			</select>
        </td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td>{lang_save_deleted_msg}</td>
        <td align="center">
        	<input type="checkbox" name="save_deleted_msg" value=1 {checked_save_deleted_msg}>
        </td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_delete_trash_messages_after_n_days}</td>
        <td align="center">
			<select name="delete_trash_messages_after_n_days">
				<option {delete_trash_messages_option_0_selected} value="0">{lang_none}</option>
				<option {delete_trash_messages_option_1_selected} value="1">{one_day}</option>
				<option {delete_trash_messages_option_2_selected} value="2">{two_days}</option>
				<option {delete_trash_messages_option_3_selected} value="3">{three_days}</option>
				<option {delete_trash_messages_option_4_selected} value="4">{four_days}</option>
				<option {delete_trash_messages_option_5_selected} value="5">{five_days}</option>
			</select>
        </td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_delete_spam_messages_after_n_days}</td>
        <td align="center">
			<select name="delete_spam_messages_after_n_days">
				<option {delete_spam_messages_option_0_selected} value="0">{lang_none}</option>
				<option {delete_spam_messages_option_1_selected} value="1">{one_day}</option>
				<option {delete_spam_messages_option_2_selected} value="2">{two_days}</option>
				<option {delete_spam_messages_option_3_selected} value="3">{three_days}</option>
				<option {delete_spam_messages_option_4_selected} value="4">{four_days}</option>
				<option {delete_spam_messages_option_5_selected} value="5">{five_days}</option>
			</select>
        </td>
    </tr>
    {open_comment_local_messages_config}
	<tr bgcolor="{tr_color2}">
        <td>{lang_Would_you_like_to_use_local_messages_?}</td>
        <td align="center">
        	<select name="use_local_messages" id="use_local_messages" onchange="disable_field(document.getElementById('keep_archived_messages'),'document.getElementById(\'use_local_messages\').value==\'0\'')">
				<option {use_local_messages_option_No_selected} value="0">{lang_No}</option>
				<option {use_local_messages_option_Yes_selected} value="1">{lang_Yes}</option>
			</select>
        </td>
    </tr>
	<tr bgcolor="{tr_color1}">
        <td>{lang_Would_you_like_to_keep_archived_messages_?}</td>
        <td align="center">
        	<select name="keep_archived_messages" id="keep_archived_messages">				
				<option {keep_archived_messages_option_No_selected} value="0">{lang_No}</option>
				<option {keep_archived_messages_option_Yes_selected} value="1">{lang_Yes}</option>
			</select>
        </td>
    </tr>
    {close_comment_local_messages_config}
    <tr bgcolor="{tr_color2}">
        <td>{lang_delete_and_show_previous_message}</td>
        <td align="center">
        	<input type="checkbox" name="delete_and_show_previous_message" value=1 {checked_delete_and_show_previous_message}>
        </td>
    </tr>
	<tr bgcolor="{tr_color1}">
        <td>{lang_alert_new_msg}</td>
        <td align="center">
        	<input type="checkbox" name="alert_new_msg" value=1 {checked_alert_new_msg}>
        </td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td>{lang_hook_home}</td>
        <td align="center">
        	<input type="checkbox" name="mainscreen_showmail" value=1 {checked_mainscreen_showmail}>
        </td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_save_in_folder}</td>
        <td align="center">
        	<select name="save_in_folder">{value_save_in_folder}</select>
        </td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td>{lang_hide_menu}</td>
        <td align="center">
			<input type="checkbox" name="check_menu" value=1 {checked_menu}>
        </td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_line_height}</td>
        <td align="center">
			<select name="line_height">
				<option {line_height_option_20_selected} value="20">{normal}</option>
				<option {line_height_option_30_selected} value="30">{medium}</option>
				<option {line_height_option_40_selected} value="40">{big}</option>
			</select>
        </td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td>{lang_font_size}</td>
        <td align="center">
			<select name="font_size">
				<option {font_size_option_10_selected} value="10">{small}</option>
				<option {font_size_option_11_selected} value="11">{normal}</option>
				<option {font_size_option_15_selected} value="15">{big}</option>
			</select>
        </td>
    </tr>
    <tr bgcolor="{tr_color1}">
        <td>{lang_use_dynamic_contacts}</td>
        <td align="center">
			<input type="checkbox" name="use_dynamic_contacts" value=1 {checked_dynamic_contacts}>
        </td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td>
			{lang_use_shortcuts}
		</td>
		<td align="center">
			<input type="checkbox" name="use_shortcuts" value=1 {checked_shortcuts}>
		</td>
    </tr>

    <tr bgcolor="{tr_color1}">
        <td>
			{lang_auto_save_draft}
		</td>
		<td align="center">
			<input type="checkbox" name="auto_save_draft" value=1 {checked_auto_save_draft}>
		</td>
    </tr>
    <tr bgcolor="{th_bg}">
        <td colspan="2" align="center">{lang_config_signature}</td>
    </tr>
    <tr bgcolor="{tr_color2}"  {display_digital_cripto}>
        <td>
			{lang_use_signature_digital_cripto}
		</td>
		<td align="center">
			<input type="checkbox" name="use_signature_digital_cripto" value=1 {checked_use_signature_digital_cripto} onClick="javascript:exibir_ocultar(this.checked)">
		</td>
    </tr>

    <tr  bgcolor="{tr_color1}" id="tr01" {display_digital}>
	<td>
			{lang_use_signature_digital}
		</td>
		<td  align="center" >
			 <input id="c01" type="checkbox" name="use_signature_digital" value=1 {checked_use_signature_digital}>
		</td>
    </tr>

    <tr bgcolor="{tr_color2}" id="tr02" {display_cripto} >
	<td>
			{lang_use_signature_cripto}
		</td>
		<td  align="center">
			 <input id="c02" type="checkbox" name="use_signature_cripto" value=1 {checked_use_signature_cripto}>
		</td>
    </tr>

     <tr bgcolor="{tr_color1}">
        <td>
			{lang_use_signature}
		</td>
		<td align="center">
			<input type="checkbox" name="use_signature" value=1 {checked_use_signature}>
		</td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td valign="center">
			{lang_type_signature}
        </td>
        <td align="center">
			<select id="type_signature" name="type_signature" onChange="javascript:changeTypeSignature(this.value)">
				<option {type_signature_option_text_selected} value="text">{simple_text}</option>
				<option {type_signature_option_html_selected} value="html">{html_text}</option>
			</select>
		</td>
    </tr>
    <tr bgcolor="{tr_color2}">
        <td colspan='2' valign="center" id="td_html_signature" style="{type_signature_td_html}">
			{rtf_signature}
        </td>
    </tr>
     <tr bgcolor="{tr_color2}">
        <td colspan='2' align="center" id="td_text_signature"  style="{type_signature_td_text}">
			<textarea name='text_signature' rows='4' cols='70'>{text_signature}</textarea>
        </td>
    </tr>
    <tr bgcolor="{th_bg}">
    	<td colspan="2" >
    		<table width="100%" border="0" cellspacing="2" cellpadding="2">
    			<tr>
        			<td align="left">
        				<input type="button" name="cancel" value="{lang_cancel}" onClick="javascript:document.location.href = '../expressoMail1_2/index.php'">
        			</td>
        			<td align="right">
        				<input type="submit" name="submit" value="{lang_save}" onClick="try_saved.value='true';">
        			</td>
        		</tr>
        </td>
    </tr>

</table>
</center>
<script language="JavaScript" type="text/javascript">
	if(document.getElementById('keep_archived_messages'))
		disable_field(document.getElementById('keep_archived_messages'),'document.getElementById(\'use_local_messages\').value==\'0\'')
	var form = 	document.forms[0];

	function selectValues(){
		var signature = eval("form."+form.type_signature.value+"_signature");
		signature.name = "signature";
		return true;
	} 	

	function changeTypeSignature(value){
		var html_signature = FCKeditorAPI.GetInstance("html_signature");

  		if(value == 'text'){
			form.text_signature.value = html_signature.GetHTML();
  	  		document.getElementById("td_text_signature").style.display = '';
	  	  	document.getElementById("td_html_signature").style.display = 'none';
  		}
	  	else if(value == 'html'){
  		  	html_signature.SetHTML(form.text_signature.value);
  		  	document.getElementById("td_text_signature").style.display = 'none';
  	  		document.getElementById("td_html_signature").style.display  = '';
	  	}
	  	
	}
</script>  
