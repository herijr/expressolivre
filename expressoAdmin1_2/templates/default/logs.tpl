<!-- BEGIN list -->
<script type="text/javascript">
function submit_form( obj, value ) {
	if ( $(obj).hasClass('disabled') ) return false;
	$('input[name=page]').val( value );
	$(obj).parents('form:first').submit();
}
</script>
<form method="POST" action="{accounts_url}" data-page="{cur_page}">
	<input class="hidden" type="submit">
	<div class="mdiv">
		<div align="center">
			<table border="0">
				<tr>
					<td class="wmin"><input type="button" value="{lang_back}" onClick="document.location.href='{back_url}'"></td>
				</tr>
				<tr>
					<td>{lang_manager}:</td>
					<td><input type="text" name="query_manager_lid" autocomplete="off" value="{query_manager_lid}"></td>
				</tr>
				<tr>
					<td>{lang_action}:</td>
					<td><input type="text" name="query_action" autocomplete="off" value="{query_action}"></td>
				</tr>
				<tr>
					<td>{lang_date}:</td>
					<td><input type="text" name="query_date" autocomplete="off" value="{query_date}"> (dd/mm/aaaa)</td>
				</tr>
				<tr>
					<td>{lang_hour}:</td>
					<td><input type="text" name="query_hour" autocomplete="off" value="{query_hour}"> (hh:mm)</td>
				</tr>
				<tr>
					<td>{lang_other}:</td>
					<td><input type="text" name="query_other" autocomplete="off" value="{query_other}"></td>
				</tr>
			</table>
		</div>
		<table class="mtable" border="0">
			<thead>
				<tr>
					<td width="10%">{lang_date}</td>
					<td width="10%">{lang_manager}</td>
					<td>{lang_action}</td>
					<td>{lang_about}</td>
				</tr>
			</thead>
			{rows}
		</table>
		<table class="pagination" border="0">
			<tr>
				<td class="icon {first_disable}" onclick="submit_form( this, 1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/first{first_disable_icon}.png">
				</td>
				<td class="icon icpad {first_disable}" onclick="submit_form( this, $(event.currentTarget).parents('form:first').data('page')-1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/left{first_disable_icon}.png">
				</td>
				<td><div class="sep"></div></td>
				<td>{lang_page}</td>
				<td><input class="cpage" name="page" type="text" value="{cur_page}"/></td>
				<td> {lang_of} {last_page}</td>
				<td><div class="sep"></div></td>
				<td class="icon icpad {last_disable}" onclick="submit_form( this, $(event.currentTarget).parents('form:first').data('page')+1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/right{last_disable_icon}.png">
				</td>
				<td class="icon {last_disable}" onclick="submit_form( this, {last_page} );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/last{last_disable_icon}.png">
				</td>
				<td><div class="sep"></div></td>
				<td>{lang_per_page}: {per_page_opts}<input class="hidden" name="per_page" type="hidden" value="{per_page}"/></td>
				<td><div class="sep"></div></td>
				<td class="icon"><img border="0" title="" src="/phpgwapi/templates/default/images/view.png" onclick="$(event.currentTarget).parents('form:first').submit();"></td>
				<td class="wmax"></td>
				<td><div class="sep"></div></td>
				<td>{lang_displaying} {first_item} - {last_item} {lang_of} {total}</td>
			</tr>
		</table>
	</div>
</form>
	
<!-- END list -->
<!-- BEGIN row -->
<tr>
	<td>{row_date}</td>
	<td>{row_manager_lid}</td>
	<td>{row_action}</td>
	<td class="force-wrap">{row_about}</td>
</tr>
<!-- END row -->