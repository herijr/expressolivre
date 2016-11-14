<!-- BEGIN body -->
	<link rel="stylesheet" type="text/css" href="./expressoAdmin1_2/templates/default/shared_accounts.css">
	<script src="../prototype/plugins/jquery/jquery-latest.min.js"></script>
	<div style="display:none" id="{modal_id}">{shared_accounts_modal}</div>

	<div align="center">
		<table border="0" width="90%">
			<tr>
				<td align="left" width="25%">
					<input type="button" value="{lang_create_shared_account}" "{create_share:_account_disabled}" onClick='{onclick_create_shared_account}'>
					<input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'">
				</td>
				<td align="center" "left" width="50%">
					{lang_contexts}: <font color="blue">{context_display}</font>
				</td>
				<td align="right" "left" width="25%">
						{lang_to_search}:
						<input type="text" onKeyUp="javascript:get_shared_accounts_timeOut(this.value)" id="ea_shared_account_search" autocomplete="off" value="{query}">
				</td>
			</tr>
		</table>
	</div>
 
	<div align="center" id="shared_accounts_content">
		<table border="0" width="90%">
			<tr bgcolor="{th_bg}">
				<td width="30%">{lang_full_name}</td>
				<td width="30%">{lang_mail}</td>
				<td width="5%" align="center">{lang_remove}</td>
			</tr>
		</table>
	</div>	

	<div align="center">
		<table border="0" width="90%">
			<tr>
				<td><input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'"></td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
		var countFiles = 1;

		var finderTimeout = "";

		function get_shared_accounts_timeOut(input)
		{
			var head_table = '<table border="0" width="90%"><tr bgcolor="#d3dce3"><td width="30%">'+get_lang("full name")+'</td><td width="30%">'+get_lang("mail")+'</td><td width="5%" align="center">'+get_lang("remove")+'</td></tr></table>';
			
			if( input.length > 4 )
			{
				clearTimeout(finderTimeout);

				finderTimeout = setTimeout(function(){ get_shared_accounts(input); }, 500);
			}
			else
			{
				$('#shared_accounts_content').html( head_table );
			}
		}

		function get_shared_accounts( input )
		{
			cExecute ('$this.ldap_functions.get_shared_accounts&input='+input, function(data)
			{
				if (data.status == 'true')
				{
					var table = '<table border="0" width="90%"><tr bgcolor="#d3dce3"><td width="30%">'+get_lang("full name")+'</td><td width="30%">'+get_lang("mail")+'</td><td width="5%" align="center">'+get_lang("remove")+'</td></tr>'+data.trs+'</table>';
					
					$('#shared_accounts_content').html(table);
				}
				else
				{
					if( data.msg )
					{
						write_msg(data.msg, 'error');
					}
				}
			});
		}
	</script>
<!-- END body -->