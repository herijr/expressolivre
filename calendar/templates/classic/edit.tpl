<!-- BEGIN edit_entry -->
<style type="text/css">
div#tipDiv {
  position:absolute; visibility:hidden; left:0; top:0; z-index:10000;
  background-color:#EFEFEF; border:1px solid #337;
  width:220px; padding:3px;
  color:#000; font-size:11px; line-height:1.2;
  cursor: default;
}
</style>
<script language="JavaScript">
	self.name="first_Window";
	function accounts_popup()
	{
		Window1=window.open('{accounts_link}',"Search","width=800,height=600,toolbar=no,scrollbars=yes,resizable=yes");
	}

	function show_disponibility() {
		participants = "";
		combo = document.getElementById('user_list');
		if(combo.length==0) {
			alert('Selecione os participantes');
			return;
		}

		for (i=0;i<combo.length;i++) {
			participants+=combo[i].value+",";
		}
		url = 'index.php?menuaction=calendar.uicalendar.disponibility&participants='+participants+'&date='+document.getElementById('start[str]').value;

		//alert(url);
		document.getElementById('frame_disponibility').src = url;
		document.getElementById('disponibility').style.display='';
		//window.open(url);
	}
</script>
<center>
<font color="#000000" face="{font}">

<form action="{action_url}" method="post" name="app_form">
{common_hidden}
<table id="editFormTable" border="0" width="90%"   class="prefTable">
 <tr>
  <td colspan="2">
   <center><font size="+1"><b id="formStatus">{errormsg}</b></font></center>
  </td>
 </tr>
{row}
 <tr>
  <td>
  <table><tr valign="top">
  <td>
  <div style="padding-top:15px; padding-right: 2px">
  	<script language="JavaScript">
		var alert_field = '{alert_msg}';
	</script>
  	<input id="submit_button" style="font-size:10px" type="submit" value="{submit_button}" onClick="return submitValues(alert_field);"></div></form>
  </td>
  <td>{cancel_button}</td>
  </tr></table>
  </td>
  <td align="right">{delete_button}</td>
 </tr>
</table>
</font>
</center>
<!-- END edit_entry -->
<!-- BEGIN list -->
 <tr bgcolor="{tr_color}">
  <td valign="top" width="25%">&nbsp;<b>{field}:</b></td>
  <td valign="top" width="75%">{data}</td>
 </tr>
<!-- END list -->
<!-- BEGIN hr -->
 <tr bgcolor="{tr_color}">
  <td colspan="2">
   {hr_text}
  </td>
 </tr>
<!-- END hr -->
