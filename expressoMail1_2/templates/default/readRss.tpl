<!-- BEGIN bodyRSS -->
<script type="text/javascript" src="phpgwapi/js/x_tools/xtools.js"></script>
<script type="text/javascript" src="{path_expressoMail}/js/news_edit.js"></script>
<script>

	function selectAll() 
	{
		if( arguments.length > 0 )
		{
			var _select = document.getElementById( arguments[0] );

			for( var i = 0 ; i < _select.options.length; i++ )
			{
				_select.options[i].selected = true;
			}
		}
	}
	
</script>

<form method="POST" action="{action_url}">
	<center>
		<div style="width:500px;border:1px solid #000;">
			
			<div style="margin:20px; height:35px;text-align:left;">
				<img src="{path_expressoMail}/templates/default/images/rss.gif"/>
				<label style="font-size:12px;font-weight:bold;"> {lang_RSS_Manager} </label>
			</div>
			
			<div style="margin:20px; text-align:left;">
				<label>{lang_enable_RSS} .:</label>
				<br/>
				<select id="enabledReadRSS" name="newsettings[expressoMail_enabled_read_rss]">
					<option value="false" {selected_expressoMail_enabled_read_rss_false}>{lang_No}</option>
					<option value="true" {selected_expressoMail_enabled_read_rss_true}>{lang_Yes}</option>	            
				</select>
			</div>

			<div style="margin:5px 5px 5px 20px; text-align:left;">
				<label>{lang_enter_Rss} .:</label>
				<br/>
				<input type="text" id="rssEnter" size="45"/>
			</div>

			<div style="margin:5px 5px 5px 20px; text-align:left;">
				<button type="button" onclick="news_edit.subscribe();">{lang_add}</button>
				<button type="button" onclick="news_edit.unsubscribe();">{lang_remove}</button>
			</div>
			
			<div style="margin:15px 5px 5px 20px; text-align:left;">
				<label>{lang_list_rss} .:</label>
				<br/>
				<select id="list_rss" size="10" style="width:320px;" multiple name="newsettings_expressoMail_list_rss[]">{options_list_rss}</select>
			</div>
			
			<div style="margin:20px; text-align:left;">
				<input type="submit" name="save" value="{lang_submit}" onclick="selectAll('list_rss');" />
				<input type="submit" name="cancel" value="{lang_cancel}" />
			</div>
			<input id="lang_can_not_access_this_rss" type="hidden" name="lang_can_not_access_this_rss" value="{lang_can_not_access_this_rss}" />
		</div>
	</center>
</form>

<!-- END bodyRSS -->