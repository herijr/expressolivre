<!-- BEGIN body -->

<form class="ajax">
	<div class="fpanel ui-dialog ui-widget ui-widget-content ui-corner-all">
		<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
			<span class="ui-dialog-title">{lang_title}</span>
			<img class="loadgif" src="./phpgwapi/images/ajax-loader.gif">
		</div>
		<div class="ui-dialog-content ui-widget-content">
			
			<div class="hline bmargin ui-state-default ui-corner-all">
				<label class="cell">{lang_enabled_ad}:</label>
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
			
			<div class="ui-dialog ui-widget ui-widget-content ui-corner-all">
				<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
					<span id="ui-id-1" class="ui-dialog-title">{lang_ad_ou_list}</span>
					<img class="loadgif" src="./prototype/plugins/messenger/images/ajax-loader.gif">
				</div>
				<div id="dialog" class="ui-dialog-content ui-widget-content">
					
					<div class="right-buttons">
						<input type="button" value="{lang_add}" id="addOUListButton" class="ui-button ui-widget ui-state-default ui-corner-all">
					</div>
					
					<div id="oulist">
						
						<div class="hline bmargin ui-state-default ui-corner-all">
							<label class="cell">{lang_ou_list_name}:</label>
							<label class="cell input-right">{lang_ou_list_dn}:</label>
						</div>
						
					</div>
					
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
		
		<div id="tpl-input-list" class="hline bmargin ui-state-default ui-corner-all">
			<div class="cell input-left ui-corner-left">
				<input type="text" class="transparent">
			</div>
			<div class="cell iconbar"></div>
			<div class="cell input-right ui-corner-right">
				<input type="text" class="transparent">
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
function addOUList( key, value ) {
	if ( value == undefined ) { value = key = ''; }
	var tplInput = $('#tpl-input-list').clone(true).attr('id',null);
	$(tplInput).find('.input-left').find('input').attr('name','a_AD_ou_list_key[]').attr('value',key);
	$(tplInput).find('.input-right').find('input').attr('name','a_AD_ou_list_val[]').attr('value',value);
	$(tplInput).find('.iconbar').append( $('<button></button>').button({ icons: { primary: 'ui-icon-trash' } }).on('click',function(){
		$(this).parents('.hline').remove();
	}));
	$('#oulist').append(tplInput);
};
$( document ).ready(function(){
	var ou_list = {js_oulist};
	for( var k in ou_list ) if( ou_list.hasOwnProperty(k) ) addOUList( k, ou_list[k] );
	$('#addOUListButton').on('click',addOUList);
});
</script>

<!-- END body -->
