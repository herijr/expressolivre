<!-- BEGIN body -->
<style>
	form.ajax input.ui-button, form.ajax div.add-field, .ui-dialog .ui-dialog-buttonpane .ui-button							{ min-width: 120px; }
	form.ajax div.ui-dialog, div.ui-dialog div.ui-dialog																	{ margin: 0 auto; position: relative; }
	form.ajax div.fpanel, div.ui-dialog div.fpanel																			{ width: 60%; min-width: 700px; }
	form.ajax div.center-buttons, div.ui-dialog div.center-buttons															{ display: table; margin: 10px auto 0; }
	form.ajax div.right-buttons, div.ui-dialog div.right-buttons															{ display: table; margin: 5px -2px 5px auto; }
	form.ajax div.left-buttons, div.ui-dialog div.left-buttons																{ display: table; margin: 5px auto 5px -2px; }
	form.ajax .hidden, div.ui-dialog .hidden																				{ display: none; }
	form.ajax .invisible, div.ui-dialog .invisible																			{ opacity: 0; }
	form.ajax .bmargin, div.ui-dialog .bmargin																				{ margin-bottom: 8px; }
	form.ajax .hline, div.ui-dialog .hline																					{ display: table; width: 100%; height: 30px; }
	form.ajax label, div.ui-dialog label																					{ vertical-align: middle; padding-left: 12px; }
	form.ajax .cell, div.ui-dialog .cell																					{ display: table-cell; }
	form.ajax .clear, div.ui-dialog .clear																					{ clear: both; }
	form.ajax div.iconbar, div.ui-dialog div.iconbar																		{ vertical-align: middle; display: none;}
	form.ajax div.iconbar button, div.ui-dialog div.iconbar button															{ width: 30px; height:30px; float: right; margin-right: 0px; }
	form.ajax div.hline:hover div.iconbar, div.ui-dialog div.hline:hover div.iconbar										{ display: table-cell; }
	form.ajax .transparent, div.ui-dialog .transparent																		{ color: #2E6E9E; background-color: rgba(255, 255, 255, 0); border-color: rgba(255, 255, 255, 0); -webkit-appearance: none; }
	form.ajax .input-right, div.ui-dialog .input-right																		{ background-color: rgba(255, 255, 255, 1); border: 0px solid #C5DBEC; border-left-width: 1px; width: 50%; }
	form.ajax select.transparent, div.ui-dialog select.transparent															{ padding: 6px; width: 100%; cursor: pointer; }
	form.ajax input.transparent, div.ui-dialog input.transparent															{ padding: 7px; width: 100%; }
	form.ajax .input-box																									{ position: relative; }
	form.ajax .input-box button																								{ position: absolute; width: 30px; height:30px; top: 0px; right: -1px; display: none; }
	form.ajax div.hline:hover .input-box.show button																		{ display: inherit; }
	form.ajax div.hline:hover .input-box.show select																		{ padding-right: 36px; }
	div.ui-dialog .loadgif																									{ position: absolute; right: 8px; }
</style>
<form class="ajax">
	<div class="fpanel ui-dialog ui-widget ui-widget-content ui-corner-all">
		<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
			<span id="ui-id-1" class="ui-dialog-title">{lang_title}</span>
		</div>
		<div id="dialog" class="ui-dialog-content ui-widget-content">
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_radius_enabled}:</label>
				<div class="cell input-right ui-corner-right">
					<select name="radius_enabled" class="transparent">
						<option value="0"{value_radius_enabled_false}>{lang_no}</option>
						<option value="1"{value_radius_enabled_true}>{lang_yes}</option>
					</select>
				</div>
			</div>
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_groupname_attribute}:</label>
				<div class="cell input-right ui-corner-right">
					<select name="groupname_attribute" class="transparent" >{select_grpname_attr_opts}</select>
				</div>
			</div>
			
			<div class="ui-dialog ui-widget ui-widget-content ui-corner-all">
				<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
					<span id="ui-id-1" class="ui-dialog-title">{lang_radius_profiles}</span>
					<img class="loadgif" src="./prototype/plugins/messenger/images/ajax-loader.gif">
				</div>
				<div id="dialog" class="ui-dialog-content ui-widget-content">
					
					<div class="right-buttons">
						<input type="button" value="{lang_add}" name="addProfileButton" class="ui-button ui-widget ui-state-disabled ui-corner-all" disabled="disabled">
					</div>
					
					<div id="radius-groups"></div>
					
				</div>
			</div>
			
			<div class="center-buttons">
				<input type="submit" value="{lang_save}" name="save" class="ui-button ui-widget ui-state-disabled ui-corner-all" disabled="disabled">
				<input type="submit" value="{lang_cancel}" name="cancel" class="ui-button ui-widget ui-state-disabled ui-corner-all" disabled="disabled">
			</div>
		</div>
	</div>
	<div class="hidden">
	
		<div id="tpl-dialog-save" title="{lang_save}: {lang_radius_profiles}">
		</div>
		
		<div id="tpl-dialog-add" title="{lang_add}: {lang_radius_profiles}">
			
			<div class="err-msg invisible hline bmargin ui-state-highlight ui-corner-all">
				<label class="cell"></label>
			</div>
			
			<div id="input-profile-name" class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_profile_name}</label>
				<div class="cell input-right ui-corner-right">
					<input class="transparent" type="text">
				</div>
			</div>
			
			<div id="input-profile-desc" class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_description}</label>
				<div class="cell input-right ui-corner-right">
					<input class="transparent" type="text">
				</div>
			</div>
		</div>
		
		<div id="tpl-add-field">
			<div class="right-buttons">
				<div class="add-field ui-state-default ui-corner-all">
					<select class="transparent">
						<option value="">{lang_add_field}</option>
					</select>
				</div>
			</div>
			<div class="input-grp"></div>
			<div class="left-buttons">
				<input type="button" value="{lang_rem}" name="remProfileButton" class="ui-button ui-widget ui-state-default ui-corner-all">
			</div>
		</div>
		
		<div id="tpl-input" class="hline bmargin ui-state-default ui-corner-all">
			<label class="cell"></label>
			<div class="cell iconbar"></div>
			<div class="cell input-right ui-corner-right"></div>
		</div>
		
		<div id="tpl-select" class="hline bmargin ui-state-default ui-corner-all">
			<label class="cell"></label>
			<div class="cell iconbar"></div>
			<div class="cell input-right ui-corner-right"></div>
		</div>
		
		<div id="tpl-input-box" class="input-box">
			<button class=""></button>
		</div>
		
	</div>
</form>
<!-- END body -->
