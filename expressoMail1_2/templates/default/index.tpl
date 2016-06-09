<!-- BEGIN list -->
<input type="hidden" value="{txt_loading}" id="txt_loading">
<input type="hidden" value="{txt_clear_trash}" id="txt_clear_trash">
<input type="hidden" value="{upload_max_filesize}" id="upload_max_filesize">
<input type="hidden" value="{msg_folder}" id="msg_folder">
<input type="hidden" value="{msg_number}" id="msg_number">
<input type="hidden" value="{user_email}" id="user_email">
<input type="hidden" value="{user_organization}" id="user_organization">
<input type="hidden" value="{cyrus_delimiter}" id="cyrus_delimiter">
<table id="main_table" width="100%" cellspacing="0" cellpadding="0" border="0" style="display:none">
<tbody>
<tr>
	<td id="folderscol" width="170px" height="100%" valign="top"> 
		<table id="folders_tbl" width="170px" border="0" cellspacing="0" cellpadding="0"> 
			<tbody>
				<tr>
					<td class='content-menu'>
						<table border="0" cellspacing="0" cellpadding="0" style="width:100%"> 
							<tbody>
								<tr>
									<td>
										<input type="text" id="em_message_search" size="16" maxlength="22">
										<a href="#"></a>
										<a href="#"></a>
									</td>
								</tr>
								<tr height="24">
									<td class='content-menu-td' onclick='javascript:new_message("new","null");' onmouseover='javascript:set_menu_bg(this);' onmouseout='javascript:unset_menu_bg(this);'>
										<div class='em_div_sidebox_menu'>
											<img src='./templates/default/images/menu/createmail.gif' />
											<span class="em_sidebox_menu">{new_message}</span>
										</div>
									</td>
								</tr>
								<tr height="24">
									<td class='content-menu-td' id='em_refresh_button' onclick='javascript:refresh();' onmouseover='javascript:set_menu_bg(this);' onmouseout='javascript:unset_menu_bg(this);'>
										<div class='em_div_sidebox_menu'>
											<img src='./templates/default/images/menu/checkmail.gif' />
												<span class="em_sidebox_menu">{refresh}</span>
										</div>
									</td>
								</tr>
								<tr height="24">
									<td id="link_tools" class='content-menu-td' onmouseover='javascript:set_menu_bg(Element("link_tools"));' onmouseout='javascript:unset_menu_bg(this);'>
										<div class='em_div_sidebox_menu'>
											<img height='16px' src='./templates/default/images/menu/tools.gif' />
												<span class="em_sidebox_menu">{tools} ...</span>
										</div>
									</td>
								</tr>								
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td height="2px"></td>
				</tr>						
				<tr>
					<td class="image-menu" valign="top" style="padding:0px">
						<div id="content_folders" class="menu-degrade" style="width:210px;height:100%;overflow:auto"></div>
					</td>
				</tr>
				<tr>
					<td>
						<div id="content_messenger" style="width:210px;"></div>
					</td>
				</tr>
			</tbody>
		</table>
	</td>
	<td class="whiteSpace">&nbsp;</td>			
	<td width="*" valign="top" align="left">
		<div id="exmail_main_body" class="messagescol">
			<table id="border_table" width="auto" height="26" cellspacing="0" cellpadding="0" border="0">
				<tbody id="border_tbody">
					<tr id="border_tr">
						<td nowrap class="menu" onClick="alternate_border(0);resizeWindow();"  id="border_id_0">
							&nbsp;{lang_inbox}&nbsp;<font face="Verdana" size="1" color="#505050">[
							<span id="new_m">0</span> / 
							<span id="tot_m">0</span>]
							</font>
						</td>
						<td nowrap id="border-left" class="button-border"><div></div></td>
						<td nowrap id="border-right" class="button-border"><div></div></td>
						<td nowrap id="border_blank" class="last_menu" width="100%">&nbsp;</td>
					</tr>
				</tbody>
			</table>
			<div id="content_id_0" class="conteudo"></div>
			<div id="footer_menu">
				<table style="border-top:0px solid black" id="footer_box" cellpadding=0 cellspacing=0 border=0 width="100%" height="10px">
					<tbody>
						<tr id="table_message"></tr>
						<tr>
							<td id="messenger-conversation-bar-container" colspan="2"></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</td>
</tr>
</tbody>
</table>
<div id='forms_queue'></div>
<div style="display:none" id="send_queue">
	<table width="100%" height="100%">
		<tr>
			<td background="js/modal/images/fundo_exp.png" valign="top" align="center">
				<font color="#006699"><b><div id="text_send_queue"></div></b></font>
			</td>
		</tr>
	</table>
</div>

<div id="expressoMOTD" style="display:none"></div>
<div id="search_div" align="center" style="white-space:nowrap"></div>
<div id="window_InfoQuota" style="display:none"></div>
<div id="div_sel_messages" style="display:none"></div>
<div id="import_window" style="display:none"></div>
<div id="expressoFolders" style="display:none;overflow:hidden"></div>
<script type="text/javascript">

	var parent = $("#em_message_search").parent();

	parent.find('input').on('keypress', function(e){

		if( e.keyCode == 13)
		{
			performQuickSearch( parent.find('input').val() );
		}
	})

	// a href emails
	parent.find("a").first().first().html("<img align='top' src='templates/default/images/search.gif'>");
	parent.find("a").first().bind("click", function(){ search_emails( parent.find("input").val() ); });
	parent.find("a").first().attr("title", "{lang_Open_Search_Window}");
	parent.find("a").first().attr("alt", "{lang_Open_Search_Window}");

	// a href users;
	parent.find("a").first().next().html("<img align='top' src='templates/default/images/users.gif'>");
	parent.find("a").first().next().bind("click",function(){ emQuickSearch( parent.find("input").val(), 'null', 'null'); });
	parent.find("a").first().next().attr("title", "{lang_search_user}");
	parent.find("a").first().next().attr("alt", "{lang_search_user}");

	parent.find("a").children('a').each(function()
	{
		$(this).css({'padding':'1 8px','width':'16px','height':'16px'});
	});
	
</script>

<!-- END list -->
