<!-- Begin CSS -->
<style type="text/css">
	#content-left { position:absolute; width:400px; top:5px; left:5px; padding-bottom:20px; }
	#content-left > div { text-align: left;}
	#content-left > div div { padding:1px; }
	#content-left > div div label{ width:140px; line-height:15px; font-size:11px; display:block; float:left; }
	#content-left > div div:nth-child(8) label{ width:113px; }
	#content-left > div div:nth-child(9) label{ width:113px; }
	#content-left > div div input[type=text]{ width:253px; }
	#content-left > div div:nth-child(6) input[type=text]{ width:50px; }
	#content-left > div div:nth-child(7) input[type=text]{ width:50px; }
	#content-left > div div:nth-child(10){ margin-top:10px; text-align: left; padding:1px; }
	#content-left label { width:100px; }
	#content-right { position:absolute; width:360px; top:5px; right:5px; padding-bottom:20px; }
	#content-right > div { text-align: left; }
	#content-right > div div{ padding:2px; }
	#content-right > div div label{ width:135px; line-height:15px; font-size:11px; display:block; float:left; }
	#content-right > div div input{ width:207px; }
	#column-right { background:#DDD; margin:0px; }
	#column-left { background:#DDD; }
	#sep-column-left { margin-left:421px; padding-left:1px; background:white;}
	#sep-column-right { margin-right:375px; padding-right:1px; background:white; }
	#column-central { background:#DDDDDD; height:450px; padding:2px 10px; text-align:right; }
	#lightboxContent { overflow: hidden !important; }
	#lightboxFoot > table { padding:2px; }
</style>
<!-- End CSS -->
<!-- Begin Javascript -->
<script type="text/javascript">

	var finder = new function()
	{
		var _time_out = null;

		this.timeOut = function( element )
		{
			clearTimeout( _time_out );

			$("#ea_span_searching").html( get_lang('searching') + '...' );

			_time_out = setTimeout( function(){ search.user( element ); }, 500 );
		};
	};

	var search = new function()
	{
		this.user = function(element)
		{
			var _find_user = new RegExp("\\b"+ $(element).val() , "i");

			$.fn.optVisible = function( show )
			{
				this.filter( ":not( span > option )").wrap( "<span>" ).parent().hide();

				if( show ){ this.filter( "span > option").unwrap(); }

				return this;
			};

			$("#ea_select_available_users").find("option").each(function()
			{
				$(this).optVisible( true );

				if( !_find_user.test( $(this).text() ) ){ $(this).optVisible( false ); }

			});

			$("#ea_span_searching").html("");
		};

		this.organization = function( input, select )
		{
			var _organization = $.trim( $(input).val().toLowerCase() ); 

			var _expression = new RegExp("\\b"+_organization, "i"); 

			if( _organization !== "" )
			{	
				$(select).find("option").each(function()
				{
					var _optionText = $(this).text();
						_optionText = _optionText.replace( "\+","" );
						_optionText = _optionText.toLowerCase();

					if( _expression.test( _optionText ) )
					{
						$(this).prop( "selected", true );
					}
				});
			}
			else
			{
				$(select).prop("selectedIndex", 0);
			}	
		};
	};
</script>
<!-- End Javascript -->
<form enctype="multipart/form-data" name="shared_accounts_form" method="post">
	<input type="hidden" id="{modal_id}_title" value="{lang_creation_of_shared_accounts}">
	<input type="hidden" id="{modal_id}_height" value="503">
	<input type="hidden" id="{modal_id}_width" value="930">
	<input type="hidden" id="{modal_id}_close_action" value="sharedAccounts.close()">
	<input type="hidden" id="{modal_id}_create_action" value="sharedAccounts.save()">
	<input type="hidden" id="{modal_id}_save_action" value="sharedAccounts.save()">
	<input type="hidden" id="{modal_id}_onload_action" value="">
	<input type="hidden" id="anchor" name="anchor">
	<input type="hidden" id="owners_acls" name="owners_acl" value="">
	<div id="content-left"> 
		<h4>{lang_Shared_account}</h4>
		<div>
			<div>
				<label>{lang_search_organization}:</label>
				<input type="text" id="organization_search" autocomplete="off" onkeyup="javascript:search.organization( this , $('#ea_combo_org') );">
			</div>
			
			<div>
				<label>{lang_organization}:</label>
				<select id="ea_combo_org" name="context">{manager_organizations}</select>
			</div>
				
			<div>			
				<label>{lang_full_name}:</label>
				<input type="text" id="cn" name="cn" autocomplete="off">
			</div>
				
			<div>		
				<label>{lang_mail}:</label>
				<input type="text" id="mail" name="mail" autocomplete="off">
			</div>

			<div>
				<label>{lang_description}:</label>
				<input type="text" id="description" name="description" autocomplete="off">
			</div>
	        
	        <div>
	            <label>{lang_Email_quota_in_MB}:</label>
	            <input type="text" id="mailquota" name="mailquota" autocomplete="off" value="{mailquota}" {changequote_disabled} {disabled}>
	            <span id='display_empty_inbox' name='display_empty_inbox' style="visibility:hidden;">
	            	<input type='button' {disabled} {disabled_empty_inbox} value='{lang_empty_inbox}' onclick="javascript:sharedAccounts.emptyMailBox(anchor.value);">
	            </span>
			</div>

	        <div>
	            <span id='quota_used_field' name='quota_used_field' style="visibility:{display_quota_used}">{lang_quota_used_in_mb}:
	            	<input type="text" name="mailquota_used" id="mailquota_used" value="{mailquota_used}" disabled>
	            </span>
	        </div>

	        <div>
	        	<div>
					<label>{lang_is_account_active}:</label>
				</div>
				
				<input type="checkbox" id="accountStatus" name="accountStatus" checked>
			</div>

			<div>
				<label>{lang_omit_account_from_the_catalog}:</label>
				<br>
				<input type="checkbox" id="phpgwAccountVisible" name="phpgwAccountVisible">
			</div>
				
			<div>			
				<label style="font-weight:bold;">{lang_owners}:</label>
				<select style="width:350px; height:165px;" id="ea_select_owners" onchange="sharedAccounts.getAcl(this.value);" name="owners[]" multiple size="13">
				</select>
			</div>
		</div>
	</div>
	<div id="column-left">
		<div id="sep-column-left">
			<div id="column-right">
				<div id="content-right"> 
					<h4>{lang_Organizations_and_users}</h4>
					<div>
						<div>
							<label>{lang_search_organization}:</label>
							<input type="text" id="organization_search" autocomplete="off" onkeyup="javascript:search.organization( this, $('#ea_combo_org_available_users'));" onblur="javascript:sharedAccounts.getAvailableUsers(org_context.value);">
						</div>
										
						<div>
							<label>{lang_organizations}:</label>
							<select name="org_context" id="ea_combo_org_available_users" onchange="javascript:sharedAccounts.getAvailableUsers(this.value);">{all_organizations}</select>
						</div>

						<div>
							<label>{lang_search_user}:</label>
							<input id="ea_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:finder.timeOut(this);">
						</div>
										
						<div>
							<font color="red"><span id="ea_span_searching">&nbsp;</span></font>
							<br>
							<br>
							<label style="font-weight:bold;">{lang_users}:</label>	
							<select id="ea_select_available_users" style="width:350px; height:270px" multiple size="13"></select>
						</div>
					</div>
				</div>
				<div id="sep-column-right">
					<div id="column-central">
						<div style="margin-top:200px;">
							<button id="bt_add_user" type="button" onClick="javascript:sharedAccounts.addOwner();">
								<img src="expressoAdmin1_2/templates/default/images/add.png" style="vertical-align: middle;">&nbsp;{lang_add_owner}
							</button>
						</div>

						<div style="margin-top:30px;">
							<button id="bt_remove_user" type="button" onClick="javascript:sharedAccounts.delOwner();">
								<img src="expressoAdmin1_2/templates/default/images/rem.png" style="vertical-align: middle;">&nbsp;{lang_remove_owner}
							</button>
						</div>

						<div style="margin-top:30px;">
							<div>
		        				<label>{lang_Reading}:</label>
		       					<input id="em_input_readAcl" onclick="javascript:sharedAccounts.setAcl(this);" type="checkbox">
			        			<img title="{lang_The_owner_can_read_the_messages}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
	        				</div>

	        				<div>
		        				<label>{lang_Exclusion}:</label>
		       					<input id="em_input_deleteAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
		       					<img title="{lang_The_owner_can_delete_or_move_the_message}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
		       				</div>

		       				<div>
		        				<label>{lang_Creation}:</label>
		       					<input id="em_input_writeAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
		       					<img title="{lang_The_owner_can_create_or_add_new_message}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
		       				</div>
	        				
	        				<div>
		        				<label>{lang_To_send}:</label>
		       					<input id="em_input_sendAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
		       					<img title="{lang_The_owner_can_send_message}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
		       				</div>

	        				<div>
		        				<label>{lang_save}:</label>
		       					<input id="em_input_saveAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
		       					<img title="{lang_other_users_will_save_the_sent_messages_as_you_in_this_mailbox}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
		       				</div>

	        				<div>
		        				<label>{lang_folder}:</label>
		       					<input id="em_input_folderAcl" onclick="sharedAccounts.setAcl(this);" type="checkbox">
		       					<img title="{lang_allow_create_or_delete_folders_on_this_mailbox}" src="./expressoAdmin1_2/templates/default/images/ajuda.png">
		       				</div>

		       			</div>
					</div>
				</div>
			</div>
		</div>  
	</div>
</form>