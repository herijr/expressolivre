<!-- BEGIN form -->
<div align="center">{error_message}</div>
<form method="POST" action="{form_action}">
 <table border="0" align="center" >
<input type="hidden" name="select_lang" value="{select_lang}">
<input type="hidden" name="section" value="{section}">
  {rows}
 </table>
</form>
<script language="Javascript">
// FIX WYSIWYG IFRAME POSITION
setTimeout("setIframeTop()",1);
function setIframeTop()
{
	var iframe = document.getElementsByTagName("IFRAME")[0];
	if(iframe){
		var div = document.createElement("DIV");
		div.style.height = "70px";
		iframe.parentNode.insertBefore( div, iframe);
	}
	else 
		setTimeout("setIframeTop()",1);
}
</script>
<!-- END form -->
 
<!-- BEGIN row -->
  <tr >
   <td>{label}</td>
   <td align="left">{value}</td>
  </tr>
<!-- END row -->
<!-- BEGIN row_2 -->
  <tr >
	<td style="width:580px" colspan="2" align="left">{value}</td>
  </tr>
<!-- END row_2 -->
