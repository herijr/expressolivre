<!-- BEGIN list -->
<link rel="stylesheet" type="text/css" href="prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css"/>
<link rel="stylesheet" type="text/css" href="admin/templates/default/css/currentsessions.css"/>
<script src="prototype/plugins/jquery/jquery-latest.min.js"></script>
<script src="prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script src="admin/js/jscode/currentsessions.js"></script>
<form id="pagination" method="GET" action="/index.php" data-page="{cur_page}">
	<input type="hidden" name="menuaction" value="admin.uicurrentsessions.list_sessions"/>
	<input type="hidden" name="order" value=""/>
	<div class="mdiv">
		<table class="mtable wmax" border="0">
			<thead>
				<tr>
					<td class="wmax">{link_username}</td>
					<td>{link_open_sessions}</td>
					<td>{lang_view}</td>
				</tr>
			</thead>
			<tbody>{rows}</tbody>
		</table>
		<table class="pagination" border="0">
			<tr>
				<td class="icon {first_disable}" onclick="submit_pagination( this, 1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/first{first_disable_icon}.png">
				</td>
				<td class="icon icpad {first_disable}" onclick="submit_pagination( this, $('#pagination').data('page')-1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/left{first_disable_icon}.png">
				</td>
				<td><div class="sep"></div></td>
				<td>{lang_page}</td>
				<td><input class="cpage" name="page" type="text" value="{cur_page}"/></td>
				<td> {lang_of} {last_page}</td>
				<td><div class="sep"></div></td>
				<td class="icon icpad {last_disable}" onclick="submit_pagination( this, $('#pagination').data('page')+1 );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/right{last_disable_icon}.png">
				</td>
				<td class="icon {last_disable}" onclick="submit_pagination( this, {last_page} );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/last{last_disable_icon}.png">
				</td>
				<td><div class="sep"></div></td>
				<td>{lang_per_page}: {per_page_opts}<input class="hidden" name="per_page" type="hidden" value="{per_page}"/></td>
				<td><div class="sep"></div></td>
				<td>{lang_filter}</td>
				<td><input class="cfilter" name="filter" type="text" value="{cur_filter}" onkeydown="if ( ( event.which || event.keyCode ) == 13 ) $('#pagination').submit();"/></td>
				<td><div class="sep"></div></td>
				<td class="icon"><img border="0" title="" src="/phpgwapi/templates/default/images/view.png" onclick="$('#pagination').submit();"></td>
				<td class="wmax"></td>
				<td class="{can_kill}"><div class="sep"></div></td>
				<td class="{can_kill}">{lang_kill_all}</td>
				<td class="{can_kill} icon" onclick="kill_all( {total_s}, '{cur_filter}' );">
					<img border="0" title="" src="/phpgwapi/templates/default/images/delete.png">
				</td>
				<td><div class="sep"></div></td>
				<td>{lang_displaying} {first_item} - {last_item} {lang_of} {total_u}</td>
			</tr>
		</table>
		<div id="view-sessions-dialog" class="hidden" title="">
			<div class="err-msg invisible hline bmargin ui-state-highlight ui-corner-all">
				<label class="cell"></label>
			</div>
			<table class="mtable wmax" border="0">
				<thead>
					<tr>
						<td>{lang_session}</td>
						<td>{lang_uidnumber}</td>
						<td>{lang_ip}</td>
						<td>{lang_login}</td>
						<td>{lang_idle}</td>
						<td class="wmax force-wrap">{lang_action}</td>
						<td>{lang_delete}</td>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div id="dialog-confirm" class="hidden" title="{lang_kill_all}" data-button-cancel="{lang_cancel}" data-button-ok="{lang_ok}">
			<p><span class="ui-icon ui-icon-alert ui-dialog-icon-pos"></span>{lang_kill_all_msg}
				(<span id="kill-cont">0</span>/<span>{total_s}</span>)
			</p>
			<span id="kill-err-msg" class="ui-state-error-text"></span>
		</div>
		<div id="dialog-refresh" class="hidden" title="{lang_refresh}">
			<p></span>{lang_refresh_msg}</p>
		</div>
	</div>
</form>
<!-- END list -->

<!-- BEGIN row -->
<tr>
	<td>{row_username}</td>
	<td>{row_count}</td>
	<td class="icon" onclick="open_sessions('{row_username}')">
		<img border="0" title="" src="/phpgwapi/templates/default/images/edit.png">
	</td>
</tr>
<!-- END row -->
