
<!-- BEGIN form_button -->
<nobr>
<script>
	function getUserSelect()
	{
		var _select = document.getElementById('userExportCalendar');
		var _input	= document.getElementById('exportUserId');
		
		for( var i = 0; i < _select.options.length; i++ )
		{
			if( _select.options[i].selected )
			{
				_input.value = _select.options[i].value;
			} 
		}
	}
</script>
<form action="{action_url_button}" method="post" name="{action_text_button}form">
<div style="padding-top:15px; padding-right: 2px">
	{action_extra_field}
	<input id="exportUserId" type="hidden" name="exportUserId" value="" />
	<input id="{button_id}" style="font-size:10px" type="submit" value="{action_text_button}" {action_confirm_button} {onclick_export} >
</div>
	
</form>
</nobr>
<!-- END form_button -->
