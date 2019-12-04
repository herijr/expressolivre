<!-- BEGIN body -->

<form class="ajax">
	<div class="fpanel ui-dialog ui-widget ui-widget-content ui-corner-all">
		<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
			<span class="ui-dialog-title">{lang_title}</span>
			<img class="loadgif" src="./phpgwapi/images/ajax-loader.gif">
		</div>
		<div class="ui-dialog-content ui-widget-content">
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_enabled_eventws}:</label>
				<div class="cell input-right ui-corner-right onoffswitch">
					<div class="onoffswitch">
						<input type="checkbox" name="{input_name_enabled}" class="onoffswitch-checkbox" id="enabled_id" {input_value_enabled}>
						<label class="onoffswitch-label" for="enabled_id">
							<span class="onoffswitch-inner" before="{lang_enabled}" after="{lang_disabled}"></span>
							<span class="onoffswitch-switch"></span>
						</label>
					</div>
				</div>
			</div>
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell nowrap">{lang_url}:</label>
				<span class="cell description">{lang_url_desc}</span>
				<div class="cell input-right ui-corner-right">
					<input class="transparent middle" name="{input_name_url}" value="{input_value_url}">
				</div>
			</div>
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_admin}:</label>
				<div class="cell input-right ui-corner-right">
					<input class="transparent" name="{input_name_admin}" value="{input_value_admin}">
				</div>
			</div>
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_passwd}:</label>
				<div class="cell input-right ui-corner-right">
					<input type="password" class="transparent" name="{input_name_passwd}" value="{input_value_passwd}">
				</div>
			</div>
			
			<div class="center-buttons always-on-screen">
				<input type="submit" value="{lang_save}" name="save" class="ui-button ui-widget ui-state-disabled ui-corner-all" disabled="disabled">
				<input type="submit" value="{lang_cancel}" name="cancel" class="ui-button ui-widget ui-state-disabled ui-corner-all" disabled="disabled">
			</div>
		</div>
	</div>
	<div class="hidden">
		<div id="tpl-dialog-save" title="{lang_save}: {lang_title}"></div>
	</div>
</form>
<!-- END body -->
