<!-- BEGIN head -->
<script language="Javascript">
function openwindow(url)
{ 
	
	var window_features = 	"scrollbars=yes,resizable=yes,location=no,menubar=no," + 
							"personalbar=no,status=no,titlebar=no,toolbar=no," + 
							"screenX=screen.width/3,screenY=screen.height/3,top=screen.height/3,left=screen.width/3,width=" + 
							screen.width/4*3 + ",height=" + screen.height/8*3; 
	
	self.open(url,'', window_features); 
}
</script>
{row}<br />
<!-- END head -->
<!-- BEGIN head_table -->
<table id="calendar_head_table" class="calendar_head_table" border="0" width="100%" cols="{cols}" cellpadding="0" cellspacing="0">
	<tr>
		{header_column}
	</tr>
</table>
<!-- END head_table -->
<!-- BEGIN head_col -->
{str}
<!-- END head_col -->