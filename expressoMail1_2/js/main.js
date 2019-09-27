// Tempo do auto refresh (em milisegundos)
var time_refresh = 300000;
// tempo do auto save (em milisegundos)
// 20000 = 20 segundos
var autosave_time = 20000;

function clean_folder( type, manual, days ) {
	
	if ( !( type === 'trash' || type === 'spam' ) ) return false;
	
	if ( ( manual === undefined || manual === true ) && !confirm( get_lang( 'Do you really want to empty your '+type+' folder?' ) ) ) return false;
	
	var params = {};
	params['action'] = '$this.imap_functions.clean_folder';
	params['type'] = type;
	if ( days !== undefined ) params['days'] = parseInt( days );
	
	$.ajax({
		'type'    : 'POST',
		'url'     :'./controller.php',
		'dataType': 'json',
		'data'    : params,
		'params'  : params
	}).done(function( data ) {
		if ( data == undefined ) write_msg( get_lang( 'An unknown error occurred. The operation could not be completed.' ) );
		else {
			if ( data.status ) {
				if ( data.folder == get_current_folder() ) {
					$('#chk_box_select_all_messages').prop('checked', false);
					$('#tot_m').html(0);
					$('#new_m').html(0);
					$('#tbody_box').html('');
				}
				refresh();
			}
			if ( data.message ) write_msg( data.message );
		}
	}).fail(function() {
		write_msg( get_lang( 'An unknown error occurred. The operation could not be completed.' ) );
	});
	return true;
}

function init(){
	if (!is_ie)
		Element('tableDivAppbox').width = '100%';

	var save_contacts = function(data){
		contacts = data;
		if (preferences.use_local_messages == 1 && window.google && google.gears)
			if (expresso_local_messages.is_offline_installed())
				expresso_local_messages.capt_url('controller.php?action=$this.db_functions.get_dropdown_contacts_to_cache');

	}
	var save_preferences = function(data){


		preferences = data;
		current_folder="INBOX";
		
		if( (window.google && google.gears) && !google.gears.factory.getPermission())
		    preferences.use_local_messages=0;

		if ((preferences.use_local_messages==1) && (!window.google || !google.gears)) {
		    temp = confirm(get_lang("To use local messages you have to install google gears. Would you like to be redirected to gears installation page?"));
		    if (temp) {
			    location.href = "http://gears.google.com/?action=install&message="+
			    "Para utilizar o recurso de mensagens locais, instale o google gears&return=" + document.location.href;
		    }
		    else {
			    preferences.use_local_messages=0;
		    }
		}

		Ajax( '$this.imap_functions.get_range_msgs2', {
			'folder'          : 'INBOX',
			'msg_range_begin' : '1',
			'msg_range_end'   : preferences.max_email_per_page,
			'sort_box_type'   : 'SORTARRIVAL',
			'search_box_type' : 'ALL',
			'sort_box_reverse': '1'
		}, function( data ) { draw_box( data, 'INBOX', true ); } );
		Ajax( '$this.imap_functions.get_folders_list', { 'onload': true }, update_menu );
		Ajax( '$this.db_functions.get_dropdown_contacts', undefined, save_contacts ); //Save contacts needs preferences.

		if(preferences.hide_folders == "1")
		{
			$('#divAppboxHeader').html(title_app_menu);

			//Quando a esta marcada a opcao ocultar pastas( preferences.hide_folder ), o titulo ExpressoMail e apagado;
			$("#main_title").html('');
		}
		
		if( preferences.outoffice && preferences.outoffice == "1" )
			write_msg(get_lang("Attention, you are in out of office mode."), true);

		ConstructMenuTools();
	
		// Insere a applet de criptografia
		if (preferences.use_signature_digital_cripto == '1'){
			loadApplet();
		}
		// Fim da insercao da applet

		// Inicia Messenger
		setTimeout( function(){ init_messenger(); }, 1000 );
	}

	// Versao
	Element('divAppboxHeader').innerHTML = title_app;

	// Get cyrus delimiter
	cyrus_delimiter = Element('cyrus_delimiter').value;

	Ajax( '$this.functions.get_preferences', undefined, save_preferences );
	Ajax( 'phpgwapi.browser.isMobile', undefined, function( data ) {
		mobile_device = ( ( data.constructor == Boolean ) ? data : ( data === 'true' ) );
	} );
	setTimeout('auto_refresh()', time_refresh);
}

function init_offline(){
        current_folder = 'local_Inbox';
	if (account_id != null) {
		if (!is_ie)
			Element('tableDivAppbox').width = '100%';
		else
			connector.createXMLHTTP();
		Element('divStatusBar').innerHTML = '<table height="16px" border=0 width=100% cellspacing=0 cellpadding=2>' +
		'<tr>' +
		'<td style="padding-left:17px" width=33% id="content_quota" align=left></td>' +
		'<td width=33% height=16px align=center nowrap><font face=Verdana, Arial, Helvetica, sans-serif color=#000066 size=2>' +
		'<b>ExpressoMail Offline</b><font size=1><b> - Versao 1.0</b></font></td>' +
		'<td width=33% id="div_menu_c3" align=right></td>' +
		'</tr></table>';

		ConstructMenuTools();

		draw_tree_folders();

		proxy_mensagens.messages_list('local_Inbox', 1, preferences.max_email_per_page, 'SORTARRIVAL', null, 1,1,1, function handler(data){
			draw_box(data, 'local_Inbox');
		})

		// Get cyrus delimiter
	cyrus_delimiter = Element('cyrus_delimiter').value;

	cExecute ("$this.db_functions.get_dropdown_contacts_to_cache", function(data) {contacts = data;});
	//cExecute ("$this.functions.get_preferences", save_preferences);
	}
}

function init_messenger()
{
	 // Function Remove Plugin
	 var remove_plugin_im = function()
	 {
		// Remove tr/td/div
		$("#content_messenger").parent().parent().remove();
	
		// Remove Input
		$("input[name=expresso_messenger_enabled]").remove();

		// Div bar
		$("#messenger-conversation-bar-container").parent().remove();

		// Resize Window
		resizeWindow();
	 };

	 if( $("input[name=expresso_messenger_enabled]").length > 0 )
	 {	
		if( $("input[name=expresso_messenger_enabled]").attr("value") === "true" )
		{
			if( parseInt($.browser.version) > 7 )
			{	
				$.ajax({
					"type"		: "POST",
					"url"		: "../prototype/plugins/messenger/auth_messenger.php",
					"dataType"	: "json",
					"success" 	: function(data)
					{
						if( !data['error'] )
						{
							if( $.trim(data['dt__b']) != "" )
							{	
								// Append divs
								$( '#content_messenger' )
								.append(
									$('<div>')
									.attr( { 'id': '_plugin' } )
									.css( { 'width': '210px', 'display': 'none' } )
								)
								.append(
									$('<div>')
									.attr( { 'id': '_menu' } )
									.css( { 'cursor': 'pointer', 'text-align': 'center' } )
									.append(
										$( '<img>' )
										.attr( {
											'src':   'templates/default/images/chat-icon-disabled.png',
											'title': get_lang( 'Expresso Messenger disabled' ),
											'alt':   get_lang( 'Expresso Messenger disabled' )
										} )
									)
								);
								
								// Init messenger method
								if ( preferences.messenger_auto_start == '1' ) initMessenger( { 'currentTarget': $( '#content_messenger' ), 'data': data } );
								else $( '#content_messenger' ).on( 'click', data, initMessenger );
								
								// Resize Window
								resizeWindow();
							}
							else
							{
								// Error Load Plugin Jabber;
								write_msg( get_lang("ERROR: The IM service, inform the administrator") );
								
								// Remove Plugin;
								remove_plugin_im();
							}
						}
						else
						{
							// Remove Plugin
							remove_plugin_im();
						}
					}
				});
			}
			else
			{
				// Msg update browser
				write_msg( get_lang("Your browser is not compatible to use the Express Messenger") );

				// Remove Plugin
				remove_plugin_im();
			}
		}//
	}
	else
	{
		// Remove Plugin
		remove_plugin_im();
	}
}

/**
 * Carrega a applet java no objeto search_div
 * @author Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 */

function loadApplet(){

	var search_div = Element('search_div');
	var applet = null;
	if (navigator.userAgent.match('MSIE')){
		applet = document.createElement('<object style="display:yes;width:0;height:0;vertical-align:bottom;" id="cert_applet" ' +
			'classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93"></object>');

		var parameters = {
			type:'application/x-java-applet;version=1.5',
			code:'ExpressoSmimeApplet',
			codebase:'/security/',
			mayscript:'true',
			token: token_param,
			locale: locale,
			archive:'ExpressoCertMail.jar,' +
				'ExpressoCert.jar,' +
				'bcmail-jdk15-142.jar,' +
				'mail.jar,' +
				'activation.jar,' +
				'bcprov-jdk15-142.jar,' +
				'commons-codec-1.3.jar,' +
				'commons-httpclient-3.1.jar,' +
				'commons-logging-1.1.1.jar'
			//debug:'true'
		}

		if (parameters != 'undefined' && parameters != null){
			for (var parameter in parameters) {
				var param = document.createElement("PARAM");
				param.setAttribute("name",parameter);
				param.setAttribute("value",parameters[parameter]);
				applet.appendChild(param);
			}
		}
	}
	else
	{
		applet = document.createElement('embed');
		applet.innerHTML = '<embed style="display:yes;width:0;height:0;vertical-align:bottom;" id="cert_applet" code="ExpressoSmimeApplet.class" ' +
			'codebase="/security/" locale="'+locale+'"'+
			'archive="ExpressoCertMail.jar,ExpressoCert.jar,bcmail-jdk15-142.jar,mail.jar,activation.jar,bcprov-jdk15-142.jar,commons-codec-1.3.jar,commons-httpclient-3.1.jar,commons-logging-1.1.1.jar" ' +
			'token="' + token_param + '" ' +
			'type="application/x-java-applet;version=1.5" mayscript > ' +
			//'type="application/x-java-applet;version=1.5" debug="true" mayscript > ' +
			'<noembed> ' +
			'No Java Support. ' +
			'</noembed> ' +
			'</embed> ';
	}
	
	if( applet != null )
	{
		applet.style.top	= "-100px";
		applet.style.left	= "-100px";
		window.document.body.insertBefore( applet, document.body.lastChild );
	}
	
}

function disable_field(field,condition) {
	var comando = "if ("+condition+") { document.getElementById('"+field.id+"').disabled=true;} else { document.getElementById('"+field.id+"').disabled=false; }";
	eval(comando);
}
/*
	funcao que remove todos os anexos...
*/
function remove_all_attachments(folder,msg_num) {

	if (confirm(get_lang("delete all attachments confirmation"))){
		Ajax( "$this.imap_functions.remove_attachments", { 'folder' : folder , 'msg_num' : msg_num }, function(data) {
			if(!data.status) {
				alert(data.msg);
			} else {
				msg_to_delete = Element(msg_num);
				change_tr_properties(msg_to_delete, data.msg_no);
				msg_to_delete.childNodes[1].innerHTML = "";
				write_msg(get_lang("Attachments removed"));
				delete_border(msg_num+'_r','false'); //close email tab
			}
		});
	}
}

function watch_changes_in_msg( id )
{
	if ( document.getElementById('border_id_'+id) )
	{
		var $root = $('form[name=form_message_'+id+']');
		$('#save_message_options_'+id).attr('disabled','disabled');
		var keypress_handler = function() {
			$root.find('iframe').contents().off('keypress.save_changed');
			$root.find('textarea,input,select').off('keypress.save_changed change.save_changed');
			$root.find('a,img').off('click.save_changed');
			$('#save_message_options_'+id).attr('disabled',null);
		};
		$root.find('iframe').contents().off('keypress.save_changed').on('keypress.save_changed',keypress_handler);
		$root.find('textarea,input,select').off('keypress.save_changed change.save_changed').on('keypress.save_changed change.save_changed',keypress_handler);
		$root.find('a,img').off('click.save_changed').on('click.save_changed',keypress_handler);
	}
}

function show_msg(msg_info){
	
	if ( !( msg_info instanceof Object ) && !msg_info.msg_number )
		return;

	if( !verify_session(msg_info))
		return;

	if( msg_info.status_get_msg_info == 'false' )
	{
		write_msg(get_lang("Problems reading your message")+ ".");
		return;
	}

	var handler_sendNotification = function(data){
		if (data)
			write_msg(get_lang("A read confirmation was sent."));
		else
			write_msg(get_lang("Error in SMTP sending read confirmation."));
	}

	if(msg_info.source)
	{
		// Abrindo um e-mail criptografado
		// Verifica se existe o objeto applet
		if (!Element('cert_applet')){
			// se nao existir, mostra mensagem de erro.
			write_msg(get_lang('The preference "%1" isn\'t enabled.', get_lang('Enable digitally sign/cipher the message?')));
		} else {
			// se existir prepara os dados para serem enviados e chama a
			// operacao na applet

			connector.showProgressBar();

		   // if ((msg_info.DispositionNotificationTo) && ((msg_info.Unseen == 'U') || (msg_info.Recent == 'N'))){
			/*	var confNotification = confirm(get_lang("The sender waits your notification of reading. Do you want to confirm this?"), "");
				if (confNotification)*/
			//		cExecute ("$this.imap_functions.send_notification&notificationto="+msg_info.DispositionNotificationTo+"&subject="+url_encode(msg_info.subject), handler_sendNotification);
		   // }

			Element('cert_applet').doButtonClickAction('decript',
														msg_info.msg_number,
														msg_info.source,
														msg_info.msg_folder); // Passa os dados para a applet
		}
		return;

	}


	if (msg_info.status_get_msg_info == 'false')
	{
		write_msg(get_lang("Problems reading your message")+ ".");
		return;
	}

	if (msg_info.status == 'false'){
		eval(msg_info.command_to_exec);
	} else {
		
		var ID = msg_info.original_ID ? msg_info.original_ID : msg_info.msg_number;
		var id_msg_read = ID+"_r";

		if (preferences.use_shortcuts == '1'){ shortcutExpresso.selectMsg( ID , false ); }
		// Call function to draw message
		// If needed, delete old border
		if (openTab.type[currentTab] == 2 || openTab.type[currentTab] == 3){ delete_border(currentTab,'false'); }

		if(Element("border_id_" + id_msg_read)) {
			alternate_border(id_msg_read);
			resizeWindow(); 
		} else {

			var border_id = create_border(msg_info.subject, id_msg_read, 2 , msg_info.msg_folder );
		
			if(border_id) {
				
				draw_message(msg_info,border_id);
				var unseen_sort = document.getElementById('span_flag_UNSEEN').getAttribute('onclick');
				unseen_sort = unseen_sort.toString();
				if ( !(unseen_sort.indexOf("'UNSEEN' == 'UNSEEN'") < 0) )
				{
					var sort_type = sort_box_type;
					sort_box_type = null;
					sort_box('UNSEEN', sort_type);
				}
			} else {
				return;
			}
		}

		var domains = "";
		if ((msg_info.DispositionNotificationTo) && (!msg_is_read(ID) || (msg_info.Recent == 'N')))
		{
			var sendReadNotification = false;
			
			if(preferences.notification_domains != undefined && preferences.notification_domains != "")
			{
				domains = preferences.notification_domains.split(',');

				for (var i = 0; i < domains.length; i++) {
					if (msg_info.DispositionNotificationTo.match(domains[i]+">"))
					{
						sendReadNotification = true;
						break;
					}
				}
			}

			if ( !sendReadNotification ) {

				var emailReplace = msg_info.DispositionNotificationTo;
				
				sendReadNotification = confirm( get_lang("The sender:\n%1\nwaits your notification of reading. Do you want to confirm this?", emailReplace.replace(/\+(.*?)\@/g, "@") ), "");
			}
			
			if ( sendReadNotification ){

				Ajax( '$this.imap_functions.send_notification', {
					'notificationto' : msg_info.DispositionNotificationTo,
					'date' : msg_info.udate,
					'subject' : msg_info.subject,
					'toaddress2' : msg_info.toaddress2
				}, handler_sendNotification );
			}

		}
		//Change msg class to read.
		if (!msg_is_read(ID))
		{
			set_msg_as_read(ID, true);
			if (msg_info.cacheHit || (!proxy_mensagens.is_local_folder(get_current_folder()) && msg_info.original_ID))
			{
				set_message_flag(ID, "seen"); // avoid caducous (lazy) data
			}
		}
	}
}

function auto_refresh(){
	refresh(preferences.alert_new_msg);
	setTimeout('auto_refresh()', time_refresh);
}

function refresh(alert_new_msg){
	var handler_refresh = function(data){
		if(!verify_session(data))
			return;
			
		if ( parseInt( alert_new_msg ) && data && data.new_msgs && data.new_msgs.sum && data.new_msgs.sum > 0 )
			open_alert_new_msg( data.new_msgs );
		
		var box = Element("tbody_box");
		if (box.childNodes.length == 0)
		{
			var tr_info = document.createElement("TR");
			var td_info = document.createElement("TD");
			td_info.setAttribute("colspan", "10");
			td_info.setAttribute("background", "#FFF");
			td_info.setAttribute("id","msg_info");
			td_info.id = "msg_info";
			td_info.align = "center";
			td_info.style.padding = "25px";
	   		td_info.style.fontWeight = "bold";
			td_info.style.fontSize = "11pt";
			td_info.innerHTML = get_lang("This mail box is empty");
			tr_info.appendChild(td_info);
			box.appendChild(td_info);
		}

		if( data.length > 0 )
		{
			var msg_info = document.getElementById('msg_info');
			if (msg_info != null)
			{
				var msg_tr = msg_info.parentNode;
				msg_tr.removeChild(msg_info);
			}

			table_element = Element("table_box");
			var box = Element("tbody_box");
			table_element.appendChild(box);

			if (data.msgs_to_delete.length > 0)
				for (var i=0; i<data.msgs_to_delete.length; i++){
					if ( (data.msgs_to_delete[i] != undefined) && (data.msgs_to_delete[i] != "")){
						removeAll(data.msgs_to_delete[i]);
					}
				}

			if (data[0].msg_folder != current_folder) // Bad request
				return false;

			var total_messages_element = Element('tot_m');

			for( var i=0; i<data.length; i++ )
			{
				var existent = document.getElementById(data[i].msg_number);
				
				if ( !existent )
				{
					var new_msg = this.make_tr_message(data[i], current_folder, data.offsetToGMT);

					if( data[i].next_msg_number != undefined && data[i].next_msg_number != null )
						box.insertBefore(new_msg, Element(data[i].next_msg_number));
					else if (data[i].Recent == 'N')
						box.insertBefore(new_msg,box.firstChild);
					else 
						box.appendChild(new_msg);

					total_messages_element.innerHTML = parseInt( total_messages_element.innerHTML ) + 1;
				}
			}

			$(box).children('tr').each(function()
			{
				$(this).off("oncontextmenu");
				$(this).off("mousedown");

				$(this).on("oncontextmenu", function(e){ return false; });
				$(this).on("mousedown", function(e)
				{ 
		    		if (typeof e.preventDefault != 'undefined')
						e.preventDefault();
					else
						e.onselectstart = new Function("return false;");

				    _dragArea.makeDraggedMsg( $(this), e );
				});
			});

			build_quota(data['quota']);
		}
		// Update Box BgColor
		var box = Element("tbody_box");
		if(box.childNodes.length > 1){
			updateBoxBgColor(box.childNodes);
		}

		if ( preferences.use_shortcuts == '1' ) {
			
			// Test if focus has lost
			if ( $('.selected_shortcut_msg').length == 0 )
			{
				// Get last index of focus and select message
				var idx = Math.min(Shortcut.focus_index(),$('#tbody_box').children().length);
				if ( idx > 0 ){ 
					shortcutExpresso.selectMsg( $('#tbody_box tr:nth-child('+idx+')').attr('id'), false );
				};
			}
		}
		connector.purgeCache();
		update_menu();
	}

	msg_range_end = (current_page*preferences.max_email_per_page);
	msg_range_begin = (msg_range_end-(preferences.max_email_per_page)+1);


	//Get element tBody.
	main = Element("tbody_box");
	if(!main)
		return;

	// Get all TR (messages) in tBody.
	main_list = main.childNodes;
	var tmp = '';
	var string_msgs_in_main = '';

	var len = main_list.length;
	for (var j=0; j < len; j++)
		tmp += main_list[j].id + ',';

	string_msgs_in_main = tmp.substring(0,(tmp.length-1));
	if(!expresso_offline)
		Ajax( '$this.imap_functions.refresh', {
			'folder'          : current_folder,
			'msgs_existent'   : string_msgs_in_main,
			'msg_range_begin' : msg_range_begin,
			'msg_range_end'   : msg_range_end,
			'sort_box_type'   : sort_box_type,
			'search_box_type' : search_box_type,
			'sort_box_reverse': sort_box_reverse
		}, handler_refresh );
}

function delete_msgs(folder, msgs_number, border_ID, show_success_msg,archive)
{
	if (arguments.length < 4){
		show_success_msg = true;
	}

	if (folder == 'null')
		folder = current_folder;

	if(openTab.type[currentTab] == 1)
		return move_search_msgs('content_id_'+currentTab,'INBOX'+cyrus_delimiter+trashfolder,trashfolder);

	if(currentTab.toString().indexOf("_r") != -1){
			msgs_number = currentTab.toString().substr(0,currentTab.toString().indexOf("_r"));
	}

	if (!archive && (parseInt(preferences.save_deleted_msg)) && (folder!='INBOX'+cyrus_delimiter+trashfolder)){
		move_msgs2(folder, msgs_number, border_ID, 'INBOX'+cyrus_delimiter+trashfolder,trashfolder,show_success_msg );
		return;
	}

	var handler_delete_msgs = function(data){

		if( data.error && $.trim(data.error) !== "" ) {
			if( ( new RegExp("Permission denied") ).test( data.error ) ){ 
				alert( get_lang("You don't have permission for this operation!") ); 
			} else {
				alert( get_lang('Error moving message.') + " :\n " + data.error );
			}
			
			return false;
		}

		$("#chk_box_select_all_messages").attr('checked', false );

		if ( preferences.use_shortcuts == '1') { shortcutExpresso.selectMsg( false, 'up' ); }

		if (show_success_msg) {
			if (data.msgs_number.length == 1){
				write_msg(get_lang("The message was deleted."));
			} else { 
				write_msg(get_lang("The messages were deleted."));
			}
		}

		if (openTab.type[currentTab] > 1){
			var msg_to_delete = Element(msgs_number);
			if (parseInt(preferences.delete_and_show_previous_message) && msg_to_delete) {
				if (msg_to_delete.previousSibling){
					var previous_msg = msg_to_delete.previousSibling.id;
					Ajax( '$this.imap_functions.get_info_msg', { 'msg_number': previous_msg, 'msg_folder': current_folder }, show_msg );
				} else {
					delete_border(currentTab,'false');
				}
			} else {
				delete_border(currentTab,'false');
			}
		}

		if( $.isArray(data.msgs_number) ){
			$.each( data.msgs_number, function(key, value){
				removeAll( $("#" + value).attr('id') );
			});
		}

		$("#tot_m").html( parseInt($("#tot_m").html()) - data.msgs_number.length );
		
		refresh();
	}

	if(msgs_number === 'selected'){
		msgs_number = get_selected_messages();
	}
	
	if( msgs_number.length > 0 || parseInt(msgs_number) > 0 ){
		Ajax( "$this.imap_functions.delete_msgs", {
			'folder': folder,
			'msgs_number' : msgs_number,
			'border_ID' : border_ID,
			'sort_box_type' : sort_box_type,
			'search_box_type' : search_box_type,
			'sort_box_reverse' : sort_box_reverse
		}, handler_delete_msgs );
	}else{
		write_msg(get_lang('No selected message.'));
	}
}

function move_search_msgs(border_id, new_folder, new_folder_name)
{
	var selected_messages = '';
	
	var main_list = Element("tbody_box_" + currentTab.substr(7)).childNodes;
	
	for (j = 0; j < main_list.length; j++) {
		var check_box = main_list[j].firstChild.firstChild;
		if (check_box && check_box.checked) {
			if (proxy_mensagens.is_local_folder(main_list[j].getAttribute('name'))) {
				alert(get_lang("You cant manipulate local messages on search"));
				return;
			}
			selected_messages += main_list[j].id + ',';
		}
	}
	
	selected_messages = selected_messages.substring(0, (selected_messages.length - 1));

	if (selected_messages) {
		var selected_param = "";
		if (selected_messages.indexOf(',') != -1) {
			selected_msg_array = selected_messages.split(",");
			for (i = 0; i < selected_msg_array.length; i++) {
				var tr = Element(selected_msg_array[i]);
				if (tr.getAttribute('name') == new_folder) {
					write_msg(get_lang('At least one message have the same origin'));
					return false;
				}
				trfolder = (tr.getAttribute('name') == null ? get_current_folder() : tr.getAttribute('name'));
				selected_param += ',' + trfolder + ';' + tr.id.replace(/_[a-zA-Z0-9]+/, "");
			}
		} else {
			var tr = Element(selected_messages);
			if (tr.getAttribute('name') == new_folder) {
				write_msg(get_lang('The origin folder and the destination folder are the same.'));
				return false;
			}
			trfolder = (tr.getAttribute('name') == null ? get_current_folder() : tr.getAttribute('name'));
			selected_param = trfolder + ';' + tr.id.replace(/_[a-zA-Z0-9]+/, "");
		}

		var params = {};

		if (!new_folder && parseInt(preferences.save_deleted_msg)) {
			new_folder = 'INBOX' + cyrus_delimiter + trashfolder;
			new_folder_name = trashfolder;
			params.delete = 'true';
		}

		params.selected_messages = url_encode(selected_param);

		if (new_folder) {
			params.new_folder = url_encode(new_folder);
			params.new_folder_name = url_encode(new_folder_name);

		}

		Ajax('this.imap_functions.move_search_messages', params, function (data) {
			if (!data || !data.msgs_number)
				return;
			else if (data.deleted) {
				if (data.msgs_number.length == 1)
					write_msg(get_lang("The message was deleted."));
				else
					write_msg(get_lang("The messages were deleted."));
			}
			else {
				if (data.msgs_number.length == 1)
					write_msg(get_lang("The message was moved to folder ") + lang_folder(data.new_folder_name));
				else
					write_msg(get_lang("The messages were moved to folder ") + lang_folder(data.new_folder_name));
			}

			selected_messages = selected_messages.split(",");
			for (i = 0; i < selected_messages.length; i++) {
				removeAll(selected_messages[i]);
			}
			// Update Box BgColor
			var box = Element("tbody_box_" + currentTab.substr(7)).childNodes;
			if (main_list.length > 1) {
				updateBoxBgColor(box);
			}
			connector.purgeCache();
		});
	} else {
		write_msg(get_lang('No selected message.'));
	}
}

function move_msgs2(folder, msgs_number, border_ID, new_folder, new_folder_name, show_success_msg) {
	if ((!folder) || (folder == 'null')) {
		folder = Element("input_folder_" + msgs_number + "_r") ? Element("input_folder_" + msgs_number + "_r").value : (openTab.imapBox[currentTab] ? openTab.imapBox[currentTab] : get_current_folder());
	}

	if (msgs_number == 'selected' && openTab.type[currentTab] == 1) {
		return move_search_msgs('content_id_' + currentTab, new_folder, new_folder_name);
	}

	var handler_move_msgs = function(data) {

		if (data && eval(data.status) == false ) {

			if( data.error ) {
				if( ( new RegExp("Permission denied") ).test( data.error ) ){ 
					alert( get_lang("You don't have permission for this operation!") ); 
				} else {
					alert( get_lang('Error moving message.') + " :\n " + data.error );
				}
			} 
			
			return false;
		}

		mail_msg = (Element("divScrollMain_" + numBox)) ? Element("divScrollMain_" + numBox).firstChild.firstChild : Element("divScrollMain_0").firstChild.firstChild;

		if (typeof (data.msgs_number) == 'string') data.msgs_number = data.msgs_number.split(',');
		write_msg(get_lang('The message' + ((data.msgs_number.length == 1) ? ' was' : 's were') + ' moved to folder ') + lang_folder(data.new_folder_name));

		if (openTab.type[currentTab] > 1) {
			msg_to_delete = Element(data.msgs_number);

			if (parseInt(preferences.delete_and_show_previous_message) && msg_to_delete) {
				if (msg_to_delete.previousSibling) {
					var previous_msg = msg_to_delete.previousSibling.id;
 					Ajax( '$this.imap_functions.get_info_msg', { 'msg_number': previous_msg, 'msg_folder': folder }, show_msg );
				}
				else {
					delete_border(data.border_ID, 'false');
				}
			}
			else {
				delete_border(data.border_ID, 'false');
			}

			if (msg_to_delete != null) {
				msg_to_delete.parentNode.removeChild(msg_to_delete);
				//mail_msg.removeChild(msg_to_delete);
			}

			if (data.border_ID.toString().indexOf("_r") > -1) {
				var _msgSearch = data.border_ID.toString();
				_msgSearch = _msgSearch.substr(0, _msgSearch.indexOf("_r"));

				if (Element(_msgSearch) != null) {
					Element(_msgSearch).parentNode.removeChild(Element(_msgSearch));
				}
			}

			// Update Box BgColor
			if (Element("tbody_box") != null) {
				var box = Element("tbody_box");

				if (box.childNodes.length > 0) {
					updateBoxBgColor(box.childNodes);
				}
			}
		} else {
			Element('chk_box_select_all_messages').checked = false;

			if (!mail_msg) mail_msg = Element('tbody_box');

			var msg_to_delete;

			if (typeof (msgs_number) == 'string') all_search_msg = msgs_number.split(',');
			else if (typeof (msgs_number) == 'number') all_search_msg = msgs_number;

			for (var i = 0; i <= all_search_msg.length; i++) {
				msg_to_delete = Element(folder + ';' + all_search_msg[i]);
				if (msg_to_delete)
					msg_to_delete.parentNode.removeChild(msg_to_delete);
			}

			// Store index of focus message
			if (preferences.use_shortcuts == '1') Shortcut.focus_index($('.selected_shortcut_msg').prevAll().length + 1);

			// Remove messages rows
			$.each(data.msgs_number, function (i, id) { $('tr#' + id).remove(); });

			if (data.border_ID.indexOf('r') != -1) {
				if (parseInt(preferences.delete_and_show_previous_message) && folder == get_current_folder()) {
					delete_border(data.border_ID, 'false');
					show_msg(data.previous_msg);
				}
				else
					delete_border(data.border_ID, 'false');
			}
			if (folder == get_current_folder()){
				Element('tot_m').innerHTML = parseInt(Element('tot_m').innerHTML) - data.msgs_number.length;
			}

			refresh();
		}

	}// END VAR HANDLER_MOVE_MSG

	if (msgs_number == 'selected') {
		msgs_number = get_selected_messages();
	}

	if (currentTab.toString().indexOf("_r") != -1) {
		msgs_number = currentTab.toString().substr(0, currentTab.toString().indexOf("_r"));
		border_ID = currentTab.toString();
	}

	if (msgs_number.toString().indexOf("_s") != -1) {
		folder = Element(msgs_number).getAttribute("name");
		msgs_number = msgs_number.toString().substr(0, msgs_number.toString().indexOf("_s"));
	}

	if (folder == new_folder) {
		write_msg(get_lang('The origin folder and the destination folder are the same.'));
		return;
	}

	if (parseInt(msgs_number) > 0 || msgs_number.length > 0){
		Ajax( '$this.imap_functions.move_messages', {
			'folder'           : folder,
			'msgs_number'      : msgs_number,
			'border_ID'        : border_ID,
			'sort_box_type'    : sort_box_type,
			'search_box_type'  : search_box_type,
			'sort_box_reverse' : sort_box_reverse,
			'reuse_border'     : border_ID,
			'new_folder'       : new_folder,
			'new_folder_name'  : new_folder_name,
			'get_previous_msg' : preferences.delete_and_show_previous_message
		}, handler_move_msgs );
	} else {
		write_msg(get_lang('No selected message.'));
	}
}


function move_msgs(folder, msgs_number, border_ID, new_folder, new_folder_name) {
	move_msgs2(folder, msgs_number, border_ID, new_folder, new_folder_name,true);
}

function get_selected_messages()
{
	try{
		main = document.getElementById("divScrollMain_"+numBox).firstChild.firstChild;
	}catch(e){
	};

	if (! main)
		main = Element("tbody_box");

	// Get all TR (messages) in tBody.
	main_list = main.childNodes;

	var selected_messages = '';
	var selected_messages_by_shortcuts = '';
	var j = 0;
	for (j; j<(main_list.length); j++)
	{

		if ( (!isNaN(parseInt(numBox))) && (numBox == 0)) {
			check_box = Element("check_box_message_" + main_list[j].id);
		}else {
			id_mensagem = main_list[j].id.split('_');
			check_box = Element("search_" + numBox + "_check_box_message_" + id_mensagem[0]);
		}

		if ((check_box) && (check_box.checked)) {

			var numericTest = /^[0-9]+$/;
			if (numericTest.test(main_list[j].id))
				selected_messages += main_list[j].id + ',';
			else
				selected_messages += id_mensagem[0] + ',';
		
		}
	
		if (preferences.use_shortcuts == '1')
		{
			if ( exist_className(Element(main_list[j].id), 'selected_shortcut_msg') )
			{
				selected_messages_by_shortcuts += main_list[j].id + ',';
			}
		}
		
	}
	selected_messages = selected_messages.substring(0,(selected_messages.length-1));

	if (preferences.use_shortcuts == '1')
	{
		selected_messages_by_shortcuts = selected_messages_by_shortcuts.substring(0,(selected_messages_by_shortcuts.length-1));

		var array_selected_messages_by_shortcuts = selected_messages_by_shortcuts.split(",");
		var array_selected_messages = selected_messages.split(",");

		if ((array_selected_messages.length <= 1) && (array_selected_messages_by_shortcuts.length > 1))
		{
			return selected_messages_by_shortcuts;
		}
	}

	if (selected_messages == '')
		return false;
	else
		return selected_messages;
}

function replaceAll(string, token, newtoken) {
	while (string.indexOf(token) != -1) {
 		string = string.replace(token, newtoken);
	}
	return string;
}

function new_message_to(email) {
	var new_border_ID = new_message('new','null');
	document.getElementById("to_" + new_border_ID).value=email;
}

function new_message(type, border_ID)
{
	var new_border_ID = draw_new_message(parseInt(border_ID));
	if(typeof(openTab.type[new_border_ID]) != "undefined") {
		if(tabTypes[type] == openTab.type[new_border_ID]) {
			return new_border_ID;
		} else {
			var a_types = { 6 : get_lang("Forward"),7 : get_lang("Reply"), 
					8 : get_lang("Reply to all with history"),
					9 : get_lang("Reply without history"),
					10: get_lang("Reply to all without history")};

			if(!confirm(get_lang("Your message to %1 has not been saved or sent. "+
						"To %2 will be necessary open it again. Discard your message?",
						a_types[openTab.type[new_border_ID]].toUpperCase(), 
						a_types[tabTypes[type]].toUpperCase()))){
				return new_border_ID;
			} else {
				delete_border(currentTab);
				new_border_ID = draw_new_message(parseInt(border_ID));
			}
		}
	}
	if (new_border_ID == false)
	{
		setTimeout('new_message(\''+type+'\',\''+border_ID+'\');',500);
		return false;
	}
	openTab.type[new_border_ID] = tabTypes[type];
	
	// Salva a pasta da mensagem respondida ou encaminhada:
	var folder_message = Element("input_folder_"+border_ID);
	if(folder_message) {
		var input_current_folder = document.createElement('input');
		input_current_folder.id = "new_input_folder_"+border_ID;
		input_current_folder.name = "input_folder";
		input_current_folder.type = "hidden";
		input_current_folder.value = folder_message.value;
		Element("content_id_" + new_border_ID).appendChild(input_current_folder);
	}//Fim.
	var data = [];
	if (Element("from_" + border_ID)){
			if (document.getElementById("reply_to_" + border_ID)){
				data.to = document.getElementById("reply_to_values_" + border_ID).value;
				data.to = data.to.replace(/&lt;/gi,"<");
				data.to = data.to.replace(/&gt;/gi,">");
		}
		else {
			if (document.getElementById("from_values_" + border_ID))
			{
				data.to = document.getElementById("from_values_" + border_ID).value;
				data.to = data.to.replace(/&lt;/gi,"<");
				data.to = data.to.replace(/&gt;/gi,">");
			}
		}

		if (document.getElementById("to_values_" + border_ID)){
			data.to_all = document.getElementById("to_values_" + border_ID).value;
			data.to_all = data.to_all.replace(/\n/gi," ");
			data.to_all = data.to_all.replace(/&lt;/gi,"<");
			data.to_all = data.to_all.replace(/&gt;/gi,">");
			var _array_to_all = data.to_all.split(",");
		}
	}

	if (document.getElementById("cc_" + border_ID)){
		data.cc = document.getElementById("cc_values_" + border_ID).value;
		data.cc = data.cc.replace(/&lt;/gi,"<");
		data.cc = data.cc.replace(/&gt;/gi,">");
	}
	if (document.getElementById("cco_" + border_ID)){
		data.cco = document.getElementById("cco_values_" + border_ID).value;
		data.cco = data.cco.replace(/&lt;/gi,"<");
		data.cco = data.cco.replace(/&gt;/gi,">");
	}
	if (document.getElementById("subject_" + border_ID))
		data.subject = document.getElementById("subject_" + border_ID).innerHTML;

	if (document.getElementById("body_" + border_ID)) {
		data.body = $('#content_id_'+border_ID).data('body');
		data.type = $('#content_id_'+border_ID).data('type');

		data.body = ( ( $.trim(data.type) === "plain" ) ? "<pre>"+data.body+"</pre>" : data.body );
	}

	if (Element('date_' + border_ID)){
		data.date = Element('date_' + border_ID).innerHTML;
	}

	if (Element('date_day_' + border_ID)){
		data.date_day = Element('date_day_' + border_ID).value;
	}
	if (Element('date_hour_' + border_ID)){
		data.date_hour = Element('date_hour_' + border_ID).value;
	}
	if(typeof(preferences.signature) == 'undefined')
		preferences.signature = "";

	if(type!="new" && type!="edit")
		data.is_local_message = (document.getElementById("is_local_"+border_ID).value=="1")?true:false;

	var title        = '';
	var mail_content = '';
	var target_signature = 'append';
	switch ( type ) {
	
		case "reply_with_history":
			target_signature = 'prepend';
			mail_content = make_body_reply( data.body, data.to, data.date_day, data.date_hour );
		case "reply_without_history":
			title = 'Re: '+data.subject;
			$('#subject_'+new_border_ID).val(title);
			$('#to_'+new_border_ID).val(data.to);
			$('#content_id_'+new_border_ID).append(
				$('<input>').attr({'id':'msg_reply_from_'+new_border_ID,'type':'hidden'}).val($('#msg_number_'+border_ID).val())
			);
			break;

		case "reply_to_all_with_history":
			target_signature = 'prepend';
			mail_content = make_body_reply( data.body, data.to, data.date_day, data.date_hour );
		case "reply_to_all_without_history":
			// delete user email from to_all array.
			data.to_all = new Array();
			var j = 0;
			for(i = 0; i < _array_to_all.length; i++) {
				if(_array_to_all[i].lastIndexOf(Element("user_email").value) == "-1"){
					data.to_all[j++] = _array_to_all[i];
				}
			}
			if ( data.to_all != get_lang("undisclosed-recipient") ) data.to_all = data.to_all.join(",");
			else data.to_all = '';

			title = 'Re: '+data.subject;
			$('#subject_'+new_border_ID).val(title);
			$('#to_'+new_border_ID).val(data.to+', '+data.to_all);
			if ( data.cc ) {
				$('#cc_'+new_border_ID).val(data.cc);
				Element("tr_cc_" + new_border_ID).style.display='';
				Element("a_cc_link_" + new_border_ID).style.display='none';
				Element('space_link_' + new_border_ID).style.display='none';
			}
			$('#content_id_'+new_border_ID).append(
				$('<input>').attr({ 'id': 'msg_reply_from_'+new_border_ID, 'type': 'hidden' }).val($('#msg_number_'+border_ID).val())
			);
			break;

		case "forward":
			msg_forward_from = document.createElement('input');
			msg_forward_from.id = "msg_forward_from_" + new_border_ID;
			msg_forward_from.type = "hidden";
			msg_forward_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_forward_from);
			title = "Fw: " + data.subject;
			document.getElementById("subject_" + new_border_ID).value = "Fw: " + data.subject;
			target_signature = 'prepend';
			mail_content = make_forward_body( data.body, data.to, data.date, data.subject, data.to_all, data.cc );
			break;
		case "new":
			mail_content = '<br>';
			title = get_lang("New Message");
			if(Element('msg_number').value) {
				var _to = Element('msg_number').value;
				var reEmail = /^[A-Za-z\d_-]+(\.[A-Za-z\d_-]+)*@(([A-Za-z\d][A-Za-z\d-]{0,61}[A-Za-z\d]\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\])$/;
				if(!reEmail.test(_to))
				{
					if( contacts.length > 0 )
					{
						var array_contacts = contacts.split(',');
						for( i = 0; i < array_contacts.length; i++)
						{
							var _group = array_contacts[i].split(";");

							if( _group[1].indexOf(_to) > -1 && _group[1] === _to )
							{
								_to = '"'+_group[0]+'" <'+_group[1]+'>';
							}
						}
					}
				}
				Element("to_" + new_border_ID).value = _to +',';
				Element('msg_number').value = '';
			}
			break;
		case "edit":
			openTab.imapBox[new_border_ID] = folder_message.value;
			openTab.toPreserve[new_border_ID] = true;
			openTab.imapUid[new_border_ID] = parseInt(border_ID.substr(0,border_ID.indexOf("_")));
			document.getElementById('font_border_id_'+new_border_ID).innerHTML = data.subject;
			title = "Edicao: "+data.subject;
			
			data.to = Element("to_values_" + border_ID).value;
			if( data.to != get_lang("without destination") ) {
				data.to = data.to.replace(/&lt;/gi,"<");
				data.to = data.to.replace(/&gt;/gi,">");
			} else {
				data.to = "";
			}

			Element("to_" + new_border_ID).value = data.to;
			if (data.cc){
				Element("cc_" + new_border_ID).value = data.cc;
				Element("space_link_" + new_border_ID).style.display = 'none';
				Element("a_cc_link_" + new_border_ID).style.display = 'none';
				Element("tr_cc_"+ new_border_ID).style.display = '';
			}
			if (data.cco){
				Element("cco_" + new_border_ID).value = data.cco;
				Element("space_link_" + new_border_ID).style.display = 'none';
				Element("a_cco_link_" + new_border_ID).style.display = 'none';
				Element("tr_cco_"+ new_border_ID).style.display = '';
			}
			Element("subject_" + new_border_ID).value = data.subject;
			
			if( Element("disposition_notification_" + border_ID) )
				Element("return_receipt_" + new_border_ID).checked = true;

			var element_important_message = Element("important_message_" + new_border_ID);
			if(element_important_message) {
				if(Element("is_important_" + border_ID).value == "1") element_important_message.checked = true;
			}

			mail_content = data.body;
			$('#textplain_rt_checkbox_'+new_border_ID).get(0).checked = ( data.type == 'plain' );
		default:
	}

	// Write main editor frame
	var body = $('iframe#body_'+new_border_ID)[0];
	var doc = body.contentWindow.document;
	doc.open();
	doc.write('<html><body bgcolor="#FFFFFF">'+mail_content+'</body></html>');
	doc.close();
	doc.designMode = 'on';

	if ( type != 'new' ) buildAttachments( $('#divFiles_'+new_border_ID).data( $('#attachments_'+border_ID).data() ), true, ( type == 'edit' || type == 'forward' ) );

	// Set signature frame
	SignatureFrame.init( body, target_signature );

	// Set paragraphs margin
	$(body).on('onkeyup',function(e){
		if ( e.keyCode == 13 ) $(e.currentTarget.contentWindow.document).find('p').css('margin','0px');
	});

	// Set focus on main editor frame
	// reply_to_all_with_history, reply_to_all_without_history, reply_with_history, reply_without_history
	if( type.indexOf("reply_") > -1 ){
		if ( is_ie ) setTimeout(()=>{ document.getElementById("body_"+ new_border_ID ).contentWindow.focus(); }, 300);
		else body.contentWindow.focus();
	} else {
		// forward, new, edit
		Element("to_" + new_border_ID ).focus();
	}

	// IM Module Enabled
	if( window.parent.loadscript && loadscript.autoStatusIM )
	{
		config_events( body.contentWindow.document, "onkeypress", loadscript.autoStatusIM );
	}

	if (preferences.auto_save_draft == 1)
	{
		openTab.autosave_timer[new_border_ID] = false;
		var save_link = document.getElementById("save_message_options"+new_border_ID);

		function auto_sav()
			{
				if (openTab.autosave_timer[new_border_ID])
					clearTimeout(openTab.autosave_timer[new_border_ID]);

				openTab.autosave_timer[new_border_ID] = setTimeout("save_msg("+new_border_ID+")", autosave_time);
	}
		config_events(body.contentWindow.document,'keypress', auto_sav);
	}

	//BEGIN Tab event
	if (preferences.use_shortcuts == '1')
	{
		if (is_ie)
		{
			body.contentWindow.document.attachEvent('onkeydown', function(event) { if(event.keyCode==27){delete_border(new_border_ID,'false');} if(event.keyCode==9) { Element('to_'+new_border_ID).focus(); Element('send_button_'+new_border_ID).focus(); return false;} }, false);
		}
		else
		{
			body.contentWindow.document.addEventListener('keypress', function(event) { if(event.keyCode==27){delete_border(new_border_ID,'false');} if(event.keyCode==9) { Element('send_button_'+new_border_ID).focus(); event.preventDefault(); } }, false);
		}
	}
	// END Tab event

	// Load default style for <PRE> tag, inside RichTextEditor.
	RichTextEditor.loadStyle( 'pre', 'main.css' );

	Element("border_id_" + new_border_ID).title = title;
	set_border_caption("border_id_" + new_border_ID, title);

        if(!expresso_offline && preferences.use_SpellChecker != '0')
	    setupSpellChecker();

	return new_border_ID; //Preciso retornar o ID da nova mensagem.
}

function buildAttachments( $obj, editable, selected )
{
	if ( typeof $obj === 'undefined' ) return false;
	$obj     = ( $obj.__proto__ === jQuery.fn )? $obj : $($obj);
	editable = ( editable === true );
	selected = ( selected === true );
	var msg  = $obj.data();
	$obj.empty();
	if ( msg.attachs == undefined || ( msg.attachs && msg.attachs.length == 0 ) ) return true;
	$obj.append($('<div>').addClass('cids')).append($('<div>').addClass('common'));

	var msg_text = $obj.parents('.conteudo').find('iframe').contents().find('body').html();

	for ( var key in msg.attachs ) {
		var size = $('<font>').attr( { 'color': 'grey' } ).html( ' ('+borkb( parseInt( msg.attachs[key].size ) )+')' );
		if ( editable && typeof msg.attachs[key].cid !== 'undefined' && msg_text.indexOf( ' cid="'+msg.attachs[key].cid+'"' ) >= 0 ) {
			$obj.parents('.conteudo').find('iframe').contents().find('img[cid="'+msg.attachs[key].cid+'"]')
				.attr({ 'src': './inc/show_img.php?msg_folder='+msg.folder+'&msg_num='+msg.uid+'&msg_part='+msg.attachs[key].section });
			$obj.find('.cids').append(
				$('<a>')
					.attr( { 'href': 'javascript:export_attachments("'+msg.folder+'","'+msg.uid+'","'+msg.attachs[key].section+'");', 'title' : msg.attachs[key].filename+'\ncid:'+msg.attachs[key].cid } )
					.html( msg.attachs[key].filename ).append( size )
			).append( $('<br>') );
		} else {
			$obj.find('.common').append(
				$('<div>').addClass('ckb_center').append(
					$('<input>').css({ 'display': editable? undefined : 'none' })
						.attr( { 'type': 'checkbox', 'name': 'forwarding_attachments[]', 'checked': selected? 'checked' : undefined } )
						.val( JSON.stringify( Object.assign( msg.attachs[key], { folder: msg.folder, msg_no: msg.uid } ) ) )
				).append(
					$('<a>')
						.attr( { 'href': 'javascript:export_attachments("'+msg.folder+'","'+msg.uid+'","'+msg.attachs[key].section+'");' } )
						.html( msg.attachs[key].filename ).append( size )
				)
			);
		}
	}

	// Resize div attachments
	if( $($obj).find("div.common").height() > 150 ){

		$($obj).find("div.common")
			.css("height","100px")
			.css("overflow", "auto")
			.css("padding","6px 0px 8px 1px");
	}

	if ( !editable ) {
		if ( msg.attachs.length > 1 ) {
			if ( parseInt( preferences.remove_attachments_function ) )
				$obj.prepend(
					$('<a>').attr({ 'href': 'javascript:remove_all_attachments("'+msg.folder+'","'+parseInt( msg.uid )+'")' })
					.html( ' '+get_lang( 'remove all attachments' ) )
				);
			$obj.prepend(
				$('<a>').attr({ 'href': 'javascript:export_attachments("'+msg.folder+'","'+parseInt( msg.uid )+'")' })
				.html( msg.attachs.length+' '+get_lang( 'files' )+' :: '+get_lang( 'Download all atachments' ) )
			);
		}
	} else if ( $obj.find('.common div').length ) $obj.find('.common').before(
		$('<a>').attr({ 'href': 'javascript:void(0)', 'tabIndex': '-1' }).addClass( ( selected? '' : 'add_link' ) ).html( get_lang( 'Original attachments: '+( selected? 'remove' : 'add' ) ) ).on('click',function(e){
			var is_add = $(e.currentTarget).hasClass( 'add_link' );
			$(e.currentTarget).siblings('.common').find('input[type=checkbox]').prop( 'checked', is_add );
			$(e.currentTarget).toggleClass( 'add_link' ).html( get_lang( 'Original attachments: '+( is_add? 'remove' : 'add' ) ) );
		})
	);

	return true;
}

/**
 * Metodo chamado pela applet para retornar o resultado da assinatura/decifragem do e-mail.
 * para posterior envio ao servidor.
 * @author Mario Cesar Kolling <mario.kolling@serpro.gov.br>, Bruno Vieira da Costa <bruno.vieira-costa@serpro.gov.br>
 * @param smime O e-mail decifrado/assinado
 * @param ID O ID do e-mail, para saber em que aba esse e-mail sera mostrado.
 * @param operation A operacao que foi realizada pela applet (assinatura ou decifragem)
 */
function appletReturn(smime, ID, operation, folder){

	if (!smime){ // Erro aconteceu ao assinar ou decifrar e-mail
		connector = new  cConnector();
		connector.hideProgressBar();
		return;
	}

	if( $.trim(operation) === 'decript' )
	{
		Ajax( "$this.imap_functions.show_decript",{
			'source' : smime,
			'ID' : ID,
			'folder' : folder
		}, function(data){

			if(data.msg_day == '')
			{
				header=expresso_local_messages.get_msg_date(data.original_ID, proxy_mensagens.is_local_folder(get_current_folder()));
				data.fulldate=header.fulldate;
				data.smalldate=header.smalldate;
				data.msg_day = header.msg_day;
				data.msg_hour = header.msg_hour;
			}
			
			show_msg(data);
		});

	} else {
		ID_tmp = ID;
		// Le a variavel e chama a nova funcao cExecuteForm
		// Processa e envia para o servidor web
		// Faz o request do connector novamente. Talvez implementar no connector
		// para manter coerencia.

		var textArea = document.createElement("TEXTAREA");
		textArea.style.display='none';
		textArea.id = 'smime';
		textArea.name = "smime";
		textArea.value += smime;

		// Le a variavel e chama a nova funcao cExecuteForm
		// Processa e envia para o servidor web
		// Faz o request do connector novamente. Talvez implementar no connector
		// para manter coerencia.
		if (is_ie){
			var i = 0;
			while (document.forms(i).name != "form_message_"+ID){i++}
			form = document.forms(i);
		} else {
			form = document.forms["form_message_"+ID];
		}

		form.appendChild(textArea);

		Ajax( "$this.imap_functions.send_mail", form, function(data){
			send_message_return(data, this.ID_tmp); // this is a hack to escape quotation form connector bug
		});
	}
}

function send_message( ID, folder, folder_name )
{
	$('#send_button_'+ID).attr('disabled','disabled');

	//limpa autosave_timer[ID]; havia conflito quando uma mensagem ia ser enviada e nesse exato momento o autosave
	//entrava em execucao (a aba de edicao da mensagem continuava aberta e a mensagem exibida era a de que a mensagem foi
	//salva na pasta Rascunhos e nao que tinha sido enviada, como deveria);
	if ( preferences.auto_save_draft == 1 && openTab.autosave_timer[ID] ) clearTimeout( openTab.autosave_timer[ID] );

	if ( $('#user_is_blocked_to_send_email').val() == 1 ) {
		write_msg( $('#user_is_blocked_to_send_email_message').val() );
		return;
	}

	if ( trim( $('#subject_'+ID).val() ).length == 0 && !confirm( get_lang( 'Send this message without a subject?' ) ) ) {
		$('#subject_'+ID).focus()
		return;
	}

	var body = $('iframe#body_'+ID).contents().find('body');
	if ( !body ) return;

	$('#save_message_options_'+ID).addClass('message_options_inactive').off('click');

	// Remove #use_signature_anchor before send
	SignatureFrame.redraw( $('iframe#body_'+ID), body );
	var body_buffer = $('<div>');
	$(body_buffer).html( $(body).html() );
	$(body_buffer).find('iframe#use_signature_anchor').after(
		$(body).find('iframe#use_signature_anchor').contents().find('body').html()
	).remove();

	var form = $('form[name=form_message_'+ID+']');

	$(form).find('textarea[name=body]').remove();
	$(form).append( $('<textarea>')
		.attr('name','body')
		.css('display','none')
		.val( $('#textplain_rt_checkbox_'+ID).is(':checked')? $(body_buffer).text() : $(body_buffer).html() )
	);

	$(form).find('input[name=folder]').remove();
	$(form).append( $('<input>')
		.attr('name','folder')
		.attr('type','hidden')
		.val(folder)
	);

	$(form).find('input[name=msg_id]').remove();
	$(form).append( $('<input>')
		.attr('name','msg_id')
		.attr('type','hidden')
		.val(openTab.imapUid[ID])
	);

	$(form).find('input[name=type]').remove();
	$(form).append( $('<input>')
		.attr('name','type')
		.attr('type','hidden')
		.val($('#textplain_rt_checkbox_'+ID).is(':checked')? 'plain' : 'html')
	);

	// Evita que e-mails assinados sejam enviados quando o usuario tenta enviar um e-mail
	// nao assinado (desmarcou a opcao) apos tentar enviar um e-mail assinado que nao passou
	// no teste de validacao.
	var checkSign = document.getElementById('return_digital_'+ID);
	if ( checkSign && !checkSign.checked ) {
		var smime = Element('smime');
		if ( smime ) {
			var parent = smime.parentNode;
			parent.removeChild(smime);
		}
	}

	Ajax( '$this.imap_functions.send_mail', form, function( data ) {
		return send_message_return( data, ID );
	} );
}

function send_message_return( data, ID ){

	watch_changes_in_msg(ID);

	$('#send_button_'+ID).attr('disabled',null);

	if( typeof(data) == 'object' ){

		if( data.hasOwnProperty('success') && data.success ){

			var msg_number_replied = $('#msg_reply_from_'+ID);

			var msg_number_forwarded = $('#msg_forward_from_'+ID);
	
			if (msg_number_replied.length > 0) {
				proxy_mensagens.proxy_set_message_flag( msg_number_replied.val(), 'answered');
			}

			if (msg_number_forwarded.length > 0) {
				proxy_mensagens.proxy_set_message_flag( msg_number_forwarded.val(), 'forwarded');
			}

			if (wfolders.getAlertMsg()) {

				write_msg(get_lang('Your message was sent and save.'));

				wfolders.setAlertMsg(false);

				if ( data.hasOwnProperty('refresh_folders')) { ttreeBox.update_folder(); }

			} else {
				write_msg(get_lang('Your message was sent.'));
			}

			// If new dynamic contacts were added, update the autocomplete ....
			if (data.hasOwnProperty('new_contacts')) {
				var ar_contacts = data.new_contacts.split(',;');
				for (var j in ar_contacts) {
					// If the dynamic contact don't exist, update the autocomplete....
					if ((contacts + ",").indexOf(ar_contacts[j] + ",") == -1) { contacts += "," + ar_contacts[j]; }
				}
			}

			if ((!openTab.toPreserve[ID]) && (openTab.imapUid[ID] != 0)){
				Ajax( '$this.imap_functions.delete_msgs',
				{ 
					'folder' : openTab.imapBox[ID],
					'msgs_number' : openTab.imapUid[ID]
				}, function(data){ return; });
			}

			delete_border( ID, 'true' ); // Becarefull: email saved automatically should be deleted. delete_border erase information about openTab

		} else {

			if (data.hasOwnProperty('body')) {

				var crypt = false;
				var sign = false;

				if ((preferences.use_assinar_criptografar != '0') && (preferences.use_signature_digital_cripto != '0')) {

					sign = ($("#return_digital_" + ID).length > 0 && $("#return_digital" + ID).prop("checked")) ? true : false;

					crypt = ($("#return_cripto_" + ID).length > 0 && $("#return_cripto_" + ID).prop("checked")) ? true : false;
				}

				var operation = (sign || crypt) ? ((sign) ? 'sign' : 'nop') : '';

				$('#cert_applet')[0].doButtonClickAction(operation, ID, data.body);
			}

			if (data.hasOwnProperty('error')) {
				write_msg(data.error);
			}
		}
	} else {
		if (data == 'Post-Content-Length') {
			write_msg(get_lang('The size of this message has exceeded  the limit (%1B).', $('#upload_max_filesize').val()));
		} else if (data) {
			write_msg(data);
		} else {
			write_msg(get_lang("Connection failed with %1 Server. Try later.", "Web"));
		}
	}

	if( $("#save_message_options_"+ID).length > 0 ){
		
		$("#save_message_options_"+ID).on('click', function(){
			openTab.toPreserve[ID] = true; 
			save_msg(ID);
		});
	}
}

function save_msg( ID )
{
	var body = $('iframe#body_'+ID).contents().find('body');
	if ( !body ) return;

	$('#send_button_'+ID).attr('disabled','disabled');

	// Remove #use_signature_anchor before send
	SignatureFrame.redraw( $('iframe#body_'+ID), body );
	var body_buffer = $('<div>');
	$(body_buffer).html( $(body).html() );
	$(body_buffer).find('iframe#use_signature_anchor').after(
		$('<div>').attr('id','use_signature_anchor').html(
			$(body).find('iframe#use_signature_anchor').contents().find('body').html()
		)
	).remove();

	// Remove tag pre, if checkbox plain is checked
	if( $('#textplain_rt_checkbox_'+ID).is(':checked') ){
		if( $(body_buffer).find('pre:first-child').length > 0 ){
			$(body_buffer).html( $(body_buffer).find('pre:first-child').html() );
		}
	}

	var form = $('form[name=form_message_'+ID+']');

	$(form).find('textarea[name=body]').remove();
	$(form).append( $('<textarea>')
		.attr('name','body')
		.css('display','none')
		.val($(body_buffer).html())
	);

	// Gets the imap folder
	var folder_id = ( openTab.imapBox[ID] && openTab.type[ID] < 6 )? openTab.imapBox[ID] : 'INBOX'+cyrus_delimiter+draftsfolder;
	var folder_name = ( folder_id == 'INBOX' )? get_lang( folder_id ) : folder_id.substr( 6 );

	$(form).find('input[name=folder]').remove();
	$(form).append( $('<input>')
		.attr('name','folder')
		.attr('type','hidden')
		.val(folder_id)
	);

	$(form).find('input[name=msg_id]').remove();
	$(form).append( $('<input>')
		.attr('name','msg_id')
		.attr('type','hidden')
		.val(openTab.imapUid[ID])
	);

	$(form).find('input[name=type]').remove();
	$(form).append( $('<input>')
		.attr('name','type')
		.attr('type','hidden')
		.val($('#textplain_rt_checkbox_'+ID).is(':checked')? 'plain' : 'html')
	);

	Ajax( '$this.imap_functions.save_msg', form, function( data ) {
		return return_save( data, ID, folder_name, folder_id, openTab.imapUid[ID] );
	} );
}

function return_save( data, border_id, folder_name, folder_id, message_id )
{
	$('#send_button_'+border_id).attr('disabled',null);

	if ( !( data && data.status ) ) {
		if ( data.error ) {
			if ( data.error.match( /^(.*)TRYCREATE(.*)$/ ) ) {
				connector.loadScript('TreeS');
				alert(get_lang('There is not %1 folder, Expresso is creating it for you... Please, repeat your request later.',draftsfolder));
				connector.loadScript('TreeShow');
				ttree.FOLDER = 'root';
				ttreeBox.new_past(draftsfolder);
				setTimeout('save_msg('+border_id+')',3000);
			} else write_msg( data.error );
		} else {
			if ( data == 'Post-Content-Length' )
				write_msg(get_lang('The size of this message has exceeded  the limit (%1B).', preferences.max_attachment_size ? preferences.max_attachment_size : Element('upload_max_filesize').value));
			else
				write_msg(get_lang('ERROR saving your message.'));
		}
		return false;
	}

	openTab.imapUid[border_id] = data.uid;
	openTab.imapBox[border_id] = data.folder;

	var newTitle = document.getElementById('subject_'+border_id).value;
	if (newTitle == '')
		newTitle = get_lang("No subject");
	set_border_caption('border_id_'+border_id, newTitle);

	//Replace all files to new files
	buildAttachments( $('#divFiles_'+border_id).data( data ), true, true );

	if (message_id)
	{
		//cExecute ("$this.imap_functions.delete_msgs&folder="+openTab.imapBox[border_id]+"&msgs_number="+message_id,function(data){ refresh(preferences.alert_new_msg); });
		if (openTab.imapBox[0] == "INBOX" + cyrus_delimiter + draftsfolder)
		{
			//Update mailbox
			var tr_msg = document.getElementById(message_id);
			change_tr_properties(tr_msg, data.uid, data.subject);
		}
	} else {
		refresh();
	}

	watch_changes_in_msg( border_id );
	write_msg( get_lang( 'Your message was save as draft in folder %1.', lang_folder( folder_name ) ) );
	return true;
}

function change_tr_properties(tr_element, newUid, newSubject){
	message_id=tr_element.id;
	var td_who = document.getElementById('td_who_'+message_id);
	if (typeof(newSubject) != 'undefined')
		td_who.nextSibling.innerHTML = newSubject;
	tr_element.id = newUid;

	var openNewMessage = function () {
		Ajax( '$this.imap_functions.get_info_msg', { 'msg_number': newUid, 'msg_folder': current_folder }, show_msg );
	};
	for (var i=2; i < 10; i++){
		if (typeof(tr_element.childNodes[i].id) != "undefined")
			tr_element.childNodes[i].id = tr_element.childNodes[i].id.replace(message_id,newUid);
		tr_element.childNodes[i].onclick = openNewMessage;
	}
}

function save_as_msg(border_id, folder_id, folder_name){
	// hack to avoid form connector bug,  escapes quotation. Please see #179
	tmp_border_id=border_id;
	tmp_folder_name=folder_name;

	var textArea = document.createElement("TEXTAREA");
	textArea.style.display='none';
	textArea.name = "body";
	body = document.getElementById("body_"+border_id);
	textArea.value += '<body>\r\n';
	textArea.value += body.contentWindow.document.body.innerHTML;
	textArea.value += '\r\n</body>';

	var input_folder = document.createElement("INPUT");
	input_folder.style.display='none';
	input_folder.name = "folder";
	input_folder.value = folder_id;

	if (is_ie){
		var i = 0;
		while (document.forms(i).name != "form_message_"+border_id){i++}
		form = document.forms(i);
	} else {
		form = document.forms["form_message_"+border_id];
	}
	
	form.appendChild(textArea);
	form.appendChild(input_folder);

	Ajax( "$this.imap_functions.save_msg", form, function(data){
		return_saveas( data , tmp_border_id, tmp_folder_name );
	});
}

function return_saveas(data,border_id,folder_name)
{
	if(!verify_session(data))
		return;
	if (data.save_draft)
	{
		delete_border(border_id,null);
		write_msg(get_lang('Your message was save as draft in folder %1.', folder_name));
	}
	else
		write_msg('ERROR saving your message.');
}

// Get checked messages
function set_messages_flag(flag, msgs_to_set){

	var folder = get_current_folder();

	if( msgs_to_set == 'get_selected_messages'){
		var msgs_to_set = this.get_selected_messages();
	} else {
		folder = $("#input_folder_"+msgs_to_set+"_r").val();
	}

	if (msgs_to_set){

		Ajax( '$this.imap_functions.set_messages_flag', { 
			'folder' : folder,
			'msgs_to_set' : msgs_to_set,
			'flag' : flag
			}, 
			function(data){
				if(!verify_session(data)) return;

				var msgs_to_set = data.msgs_to_set.split(",");
		
				if(!data.status) {
					write_msg(data.msg);
					
					$("#chk_box_select_all_messages").attr("checked", false);
		
					for (var i = 0; i < msgs_to_set.length; i++) {
						Element("check_box_message_" + msgs_to_set[i]).checked = false;
						remove_className(Element(msgs_to_set[i]), 'selected_msg');
					}
					if(!data.msgs_unflageds)
						return;
					else
						msgs_to_set = data.msgs_unflageds.split(",");
				}
	
				for (var i=0; i<msgs_to_set.length; i++){
					if(Element("check_box_message_" + msgs_to_set[i])){
						switch(data.flag){
							case "unseen":
								set_msg_as_unread(msgs_to_set[i]);
								Element("check_box_message_" + msgs_to_set[i]).checked = false;
								break;
							case "seen":
								set_msg_as_read(msgs_to_set[i], false);
								Element("check_box_message_" + msgs_to_set[i]).checked = false;
								break;
							case "flagged":
								set_msg_as_flagged(msgs_to_set[i]);
								document.getElementById("check_box_message_" + msgs_to_set[i]).checked = false;
								break;
							case "unflagged":
								set_msg_as_unflagged(msgs_to_set[i]);
								Element("check_box_message_" + msgs_to_set[i]).checked = false;
								break;
						}
					}
				}
		
				$("#chk_box_select_all_messages").attr("checked", false);
		});
	} else {
		write_msg(get_lang('No selected message.'));
	}
}

// By message number
function set_message_flag(msg_number, flag, func_after_flag_change){
	
	var msg_number_folder = $("#new_input_folder_"+msg_number+"_r")[0]; //Mensagens respondidas/encaminhadas
	
	if (!msg_number_folder) {
		var msg_number_folder = $("#input_folder_"+msg_number+"_r")[0]; //Mensagens abertas
	}
	
	Ajax( '$this.imap_functions.set_messages_flag', 
		{
			'folder' : ( msg_number_folder ?  msg_number_folder.value : get_current_folder() ),
			'msgs_to_set' : msg_number,
			'flag' : flag
		},
		function(data){
			if(!verify_session(data)) return;
		
			if(!data.status) {
				write_msg(get_lang("this message cant be marked as normal"));
				return;
			} else if( func_after_flag_change ) {
				func_after_flag_change(true);
			}
	
			if (data.status && Element("td_message_answered_"+msg_number)) {
				
				switch(flag){
					case "unseen":
						set_msg_as_unread(msg_number);
						break;
					case "seen":
						set_msg_as_read(msg_number);
						break;
					case "flagged":
						set_msg_as_flagged(msg_number);
						break;
					case "unflagged":
						set_msg_as_unflagged(msg_number);
						break;
					case "answered":
						Element("td_message_answered_"+msg_number).innerHTML = '<img src=templates/'+template+'/images/answered.gif title=Respondida>';
						break;
					case "forwarded":
						Element("td_message_answered_"+msg_number).innerHTML = '<img src=templates/'+template+'/images/forwarded.gif title=Encaminhada>';
						break;
				}				
			} else {
				refresh();
			}
		}
	);
}

function print_all(){
	if (openTab.type[currentTab] == 2)
		return print_msg(current_folder,currentTab.substr(0,currentTab.indexOf("_r")),currentTab);
	
	var folder  = Element('border_id_0').innerHTML;
	var body    = Element('divScrollMain_'+numBox).innerHTML;
	var seekDot = /\<img /gi;
	body    = body.replace(seekDot, "<img style='display:none' ");
	seekDot = /\<input /gi;
	body    = body.replace(seekDot, "<input style='display:none' ");
	
	var window_print = popup_create();
	while (1){
		try{
			var html = '<br>';
			html += "<h4>ExpressoLivre - ExpressoMail</h4>";
			html += folder+"<hr>";
			
			window_print.document.body.innerHTML = html + '<blockquote><font size="2">' +
			'<table style="font-size:12" width="'+(is_ie ? "94%" : "100%" )+'">' +
			'<TD width="25%" align="center">'+get_lang("Who")+'</TD>' +
			'<TD align="center" width="'+(is_ie ? "50%" : "55%" )+'">'+get_lang("Subject")+'</TD>' +
			'<TD align="center" width="11%">'+get_lang("Date")+'</TD>' +
			'<TD align="center" width="'+(is_ie ? "10%" : "9%" )+'">'+get_lang("Size")+'</TD></TR></table>'
			+ body + '</font></blockquote>';
			break;
		}
		catch(e){
			//alert(e.message);
		}
	}
	popup_print( window_print );
}


function print_msg(msg_folder, msg_number, border_ID){

	let div_toaddress_full  = $("#div_toaddress_full_"+border_ID)[0];
	let div_ccaddress_full  = $("#div_ccaddress_full_"+border_ID)[0];
	let div_ccoaddress_full = $("#div_ccoaddress_full_"+border_ID)[0];
	let printListTO         = (div_toaddress_full && div_toaddress_full.style.display != 'none') || toaddress_array[border_ID].length == 1 ? true : false;
	let printListCC         = (div_ccaddress_full && div_ccaddress_full.style.display != 'none') || !div_ccaddress_full ? true : false;
	let printListCCO        = (div_ccoaddress_full && div_ccoaddress_full.style.display != 'none') || !div_ccoaddress_full ? true : false;
	let sender              = $('#sender_values_'+border_ID).length > 0 ? $('#sender_values_'+border_ID)[0].value : null;
	let from                = $('#from_values_'+border_ID).length > 0 ? $('#from_values_'+border_ID)[0].value : null;
	let to                  = $('#to_values_'+border_ID).length > 0 ? $('#to_values_'+border_ID)[0].value :null;
	let cco                 = $('#cco_values_'+border_ID).length > 0 ? $('#cco_values_'+border_ID)[0].value : null;
	let cc                  = $('#cc_values_'+border_ID).length > 0 ? $('#cc_values_'+border_ID)[0].value : null;
	let date                = $('#date_'+border_ID)[0];
	let subject             = $('#subject_'+border_ID)[0];
	let attachments         = $('#attachments_'+border_ID)[0];
	let body                = $('#body_'+border_ID).clone();
	
	//needed to get the names of the attachments... only.
	let show_attachs = "";
	
	if( $(attachments).length > 0 )
	{
		let attachs = "";

		$(attachments).find("a").each(function(){
			attachs += '<div>' + $(this).html() + '</div>';
		});

		show_attachs = "<tr><td width=7%><font size='2'>" + get_lang('Attachments: ')+ " </font></td><td><font size='2'>"+attachs+"</font></td></tr>";
	}

	let current_path = window.location.href.substr(0,window.location.href.lastIndexOf("/"));
	let head = '<head><title></title><link href="'+current_path+'/templates/default/main.css" type="text/css" rel="stylesheet"></head>';
	
	let window_print = popup_create();
	let html = "";
	while (1) {
		try {
			
			window_print.document.write(head);
			html = "<body>";
			html += "<h4>ExpressoLivre - ExpressoMail</h4><hr>";
			html += "<table><tbody>";
			html += ( sender ) ? "<tr><td width=7% noWrap><font size='2'>" + get_lang('Sent by') + ": </font></td><td><font size='2'>"+sender+"</font></td></tr>" : "";
			html += ( from ) ? "<tr><td width=7%><font size='2'>" + get_lang('From') + ": </font></td><td><font size='2'>"+from+"</font></td></tr>" : "";

			if( to ){
				to = (!printListTO) ? 'Os destinatarios nao estao sendo exibidos para esta impressao' : to;
				html += ( to ) ? "<tr><td width=7%><font size='2'>" + get_lang('To') + ": </font></td><td><font size='2'>"+to+"</font></td></tr>" : "";
			}

			if (cc) {
				cc = ( !printListCC ) ? 'Os destinatarios nao estao sendo exibidos para esta impressao' : cc ;
				html += "<tr><td width=7%><font size='2'>" + get_lang('Cc') + ": </font></td><td><font size='2'>"+cc+"</font></td></tr>";
			}
			
			if (cco) {
				cco = ( !printListCCO ) ? 'Os destinatarios nao estao sendo exibidos para esta impressao' : cco;
				html += "<tr><td width=7%><font size='2'>" + get_lang('Cco') + ": </font></td><td><font size='2'>"+cco+"</font></td></tr>";
			}
			
			html += ( date ) ? "<tr><td width=7%><font size='2'>" + get_lang('Date') + ": </font></td><td><font size='2'>"+date.innerHTML+"</font></td></tr>" : "";
			html += "<tr><td width=7%><font size='2'>" + get_lang('Subject')+ ": </font></td><td><font size='2'>"+subject.innerHTML+"</font></td></tr>";
			html += show_attachs;
			html += "</tbody></table><hr>";
			
			window_print.document.write( html + $(body).html() );

			break;
			
		} catch(e) {
			//alert(e.message);
		}
	}

	setTimeout( function(){ popup_print( window_print ); }, 500 );
}

function popup_create()
{
	var w = screen.width - 200;
	var x = ((screen.width - w) / 2);
	var y = ((screen.height - 400) / 2) - 35;
	var wndw = window.open( '', 'ExpressoMail','width='+w+',height=400,resizable=yes,scrollbars=yes,left='+x+',top='+y );
	if ( wndw == null ) {
		alert( get_lang( "The Anti Pop-Up is enabled. Allow this site (%1) for print.", document.location.hostname ) );
		return false;
	}
	wndw.document.write( "<!doctype html><html></html>" );
	return wndw;
}

function popup_print( wndw )
{
	var cont = 0;
	var doc = wndw.document;
	doc.close();
	function popup_print_show() {
		if ( doc.readyState === "complete" ) {
			wndw.focus();
			wndw.print();
			wndw.close();
		} else if ( cont < 60 ) setTimeout( popup_print_show, 100 );
		cont++;
	};
	popup_print_show();
}

function export_attachments( folder, msg_number, section )
{
	Download( '$this.exporteml.exportAttachments', {
		'folder'     : folder,
		'section'    : ( section == undefined || section == '*' )? '*' : section,
		'msg_number' : parseInt( msg_number )
	} );
}

function export_all_selected_msgs()
{
	var folders = get_selected_messages_by_folder();
	if ( folders === false ) {
		write_msg(get_lang('Error compressing messages (ZIP). Contact the administrator.'));
		return false;
	}
	
	if ( Object.keys(folders).length == 0 ) {
		write_msg(get_lang('No selected message.'));
		return false;
	}
	Download( '$this.exporteml.exportMessages', { 'folders': folders } );
	return true;
}

function get_selected_messages_by_folder()
{
	var result = {};
	switch ( Common.typeOfTab() ) {
		case 0: // Main tab
			var msgs = get_selected_messages()
			if ( msgs ) {
				msgs = msgs.split(',');
				var folder = Common.getImapFolder();
				for ( var i in msgs ) {
					var id = parseInt( msgs[i] );
					if ( result[folder] == undefined ) result[folder] = [];
					if ( result[folder].indexOf( id ) < 0 ) result[folder].push( id );
				}
			}
			break;
		case 1: // Search tab
			var tb = $('div[id^=divScrollMain]:visible').children().children();
			if ( $(tb).length < 1 ) tb = $('#tbody_box');
			$(tb).children('tr').find('input[type=checkbox]:checked').each(function( i ) {
				var tr = $(this).parents('tr:first');
				var folder = $(tr).attr('name');
				var id = parseInt( $(tr).attr('id') );
				if ( result[folder] == undefined ) result[folder] = [];
				if ( result[folder].indexOf( id ) < 0 ) result[folder].push( id );
			});
			break;
		case 2: // Mail
			var tabname = Common.getSelectedTabName();
			result[Common.getImapFolder( tabname )] = parseInt( tabname );
			break;
		default: return false;
	}
	return result;
}

function select_all_search_messages(select, id){
	var search_box = Element("table_resultsearch_" + id.substr(18)).firstChild;
	var felement = search_box.firstChild;
	while(felement)
	{
	if (select)
		felement.firstChild.firstChild.checked = true;
	else
		felement.firstChild.firstChild.checked = false;
	felement = felement.nextSibling;
	}
}

function verify_session(data){

	if(data && data.imap_error) {
		if(data.imap_error == "nosession")
			write_msg(get_lang("your session could not be verified."));
		else
			write_msg(data.imap_error);
		// Hold sesion for edit message.
		//if(!hold_session)
		//	location.href="../login.php?cd=10&phpgw_forward=%2FexpressoMail1_2%2Findex.php";
		return false;
	}
	else
		return true;
}

// Save image file.
function save_image( e, thumb, msg_folder, msg_number, section )
{
	thumb.oncontextmenu = function(e) {
		return false;
	}
	var _button = is_ie ? window.event.button : e.which;
	var	_target = is_ie ? event.srcElement : e.target;

	if(_button == 2 || _button == 3) {
		export_attachments( msg_folder, msg_number, section );
	}
}

function mark_as_spam( is_spam ) {
	
	is_spam     = ( is_spam !== false );
	
	var folders = get_selected_messages_by_folder();
	if ( Object.keys( folders ).length == 0 ) return write_msg( get_lang( 'No selected message.' ) );
	
	$.ajax({
		'type'    : 'POST',
		'url'     : '/expressoMail1_2/controller.php',
		'dataType': 'json',
		'data'    : {
			'action'     : '$this.imap_functions.spam',
			'spam'       : is_spam,
			'folders'    : folders
		}
	}).done(function( response ) {
		
		if ( Object.keys( response ).length == 0 )
			return write_msg( get_lang( 'Connection failed with %1 Server. Try later.', 'Spam' ) );
		
		if ( response.status && response.status == false ) {
			if ( response.message ) write_msg( get_lang( response.message ) );
			else write_msg( get_lang( 'Connection failed with %1 Server. Try later.', 'Spam' ) );
		}
		
		for ( var folder_dest in response.spam_result ) {
			for ( var folder_orig in response.spam_result[folder_dest]['orig'] ) {
				if ( folder_orig == folder_dest ) continue;
				var msg_numbers = response.spam_result[folder_dest]['orig'][folder_orig].split( ',' );
				proxy_mensagens.proxy_move_messages(
					folder_orig,
					response.spam_result[folder_dest]['orig'][folder_orig],
					'null',
					folder_dest,
					response.spam_result[folder_dest]['name']
				);
				for ( var i in msg_numbers ) {
					if ( openTab.imapBox[msg_numbers[i]+'_r'] === folder_orig ) delete_border( msg_numbers[i]+'_r', 'false' );
				}
			}
			//if ( openTab.type[currentTab] > 1 ) delete_border( currentTab, 'false' );
		}
		
	}).fail(function( response ) {
		write_msg( get_lang( 'Connection failed with %1 Server. Try later.', 'Spam' ) );
	});
}

function import_window()
{
	var dialogImport = $("#import_window");

	dialogImport.dialog(
	{
		modal 		: true,
		width 		: 370,
		height 		: 160,
		title 		: get_lang('zip mails to import'),
		position	: { my: "45% center" },
		resizable 	: false,
		buttons		: [
		       			{   	
							text : get_lang('Select a folder'),
		       			   	click : function()
		       			   	{
		       			   		select_import_folder( $(this) );
		       			   	}
		       		    },
		       		    {
		       			   	text : get_lang("Close"),
		       			   	click : function()
		       			   	{
		       			   		$(this).dialog("destroy");
		       			   	}
		      			}]				
	});

	dialogImport.next().css("background-color", "#E0EEEE");

	dialogImport.html( new EJS( {url: 'templates/default/importMessages.ejs'} ).render());

	dialogImport.find("input[type=file]").on("change", function()
	{
		dialogImport.find("input[type=text]").val( dialogImport.find("input[type=file]").val() );
	})
}

function import_msgs(wfolders_tree){
	function handler(data){
		// Its necessary to encapsulate the data returned (IE bugfix)
		return_import_msgs(data,wfolders_tree);
	}
	var countFiles = document.form_import.countFiles;
	if(!countFiles){
		countFiles = document.createElement("INPUT");
		countFiles.type = "hidden";
		countFiles.name = "countFiles";
		countFiles.value = "1";
		document.form_import.appendChild(countFiles);
	}
	var folder = document.createElement("INPUT");
	folder.type = "hidden";
	folder.name = "folder";
	folder.value = wfolders_tree._selected.id;
	document.form_import.appendChild(folder);
	write_msg(get_lang('You must wait while the messages will be imported...'));

	Ajax( '$this.imap_functions.import_msgs', document.form_import, handler );
}
function return_import_msgs(data, wfolders_tree){
	if(data && data.error){
		write_msg(data.error);
	} else{
		if(data == 'Post-Content-Length'){
			write_msg(get_lang('The size of this message has exceeded  the limit (%1B).', preferences.max_attachment_size ? preferences.max_attachment_size : Element('upload_max_filesize').value));
		} else {
			write_msg(get_lang(data));
				
			if(openTab.imapBox[0] == wfolders_tree._selected.id)
			{
				openTab.imapBox[0] = '';
				change_folder(wfolders_tree._selected.id, wfolders_tree._selected.caption);
			} else{
				refresh();
			}
		}
	}
}

function select_import_folder(dialogImport)
{
	//Begin: Verify if the file extension is allowed.
	var imgExtensions = new Array("eml","zip");
	var inputFile = document.form_import.file_1;
	if(!inputFile.value){
		alert(get_lang('File extension forbidden or invalid file') + '.');
		return false;
	}
	var fileExtension = inputFile.value.split(".");
	fileExtension = fileExtension[(fileExtension.length-1)];
	var deniedExtension = true;
	for(var i=0; i<imgExtensions.length; i++) {
		if(imgExtensions[i].toUpperCase() == fileExtension.toUpperCase()) {
			deniedExtension = false;
			break;
		}
	}
	if(deniedExtension) {
		alert(get_lang('File extension forbidden or invalid file') + '.');
		return false;
	}

	$( dialogImport ).dialog("destroy");
	connector.loadScript('wfolders');

	if ( typeof(wfolders) == "undefined" )
		setTimeout( 'select_import_folder()', 500 );
	else
		wfolders.makeWindow('null','import');
}

function import_calendar(hash_vcalendar){
	if(confirm(get_lang("Do you confirm this import to your Calendar?"))){
		$.ajax({
				type     : "GET",
				url      : "/api/rest/vcalendar/import?event="+hash_vcalendar,
				dataType : "json",
				success  : function( data )
				{
					if( data.result == true ){
						write_msg(get_lang("The event was imported successfully."));
					} else {
						write_msg(get_lang("The event was not imported."));
					}
				}
		});
	}
}

function searchEmail(emailString){
		var arrayInvalidEmails = new  Array();
		arrayInvalidEmails[1] = '';
		var email;
		var arrayEmailsFull = new Array();
		arrayEmailsFull = emailString.split(',');
		var er_Email =  new RegExp("<(.*?)>"); 
    	// TODO Use validateEmail of common functions !
		var er_ValidaEmail = new RegExp("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$");

		for (i=0; i < arrayEmailsFull.length; i++){
			email = er_Email.exec(arrayEmailsFull[i]);
			tempEmail = email == null  ? arrayEmailsFull[i]:email[1];
			tempEmail = tempEmail.replace(/^\s+|\s+$/g,"");
			if (tempEmail != '') {
				singleEmail = er_ValidaEmail.exec(tempEmail);

				if (singleEmail == null) {
					arrayInvalidEmails[0] = true;
					arrayInvalidEmails[1] += (email == null ? arrayEmailsFull[i] : email[1]) + "; ";
				}
			}
		}

		return arrayInvalidEmails;
}

function open_alert_new_msg( params ) {
	if ( !$('#recent').length ) {
		$('body').append( $('<div id="recent">').data(
			{ sum: 0, info: {}, fav: new Favico( { animation: 'none', fontStyle: 'normal' } ) }
		) );
	}
	var info = $('#recent').data( 'info' );
	for ( var key in params.info ) if ( params.info.hasOwnProperty( key ) ) {
		if ( info[key] == undefined ) info[key] = 0;
		info[key] += parseInt( params.info[key] );
	}
	$('#recent').data( 'info', info );
	var text = '';
	for ( var key in info ) if ( info.hasOwnProperty( key ) ) {
		text += lang_folder( key.replace(/^inbox\//i, '') )+': '+info[key]+'</br>'
	}
	var tot = $('#recent').data( 'sum' ) + params.sum;
	var title = ( ( tot > 1 )? get_lang( 'You have %1 new messages', tot ) : get_lang( 'You have 1 new message' ) ) + '!';
	$('#recent')
		.html( text )
		.data( 'sum', tot )
		.data( 'fav' ).badge( tot );
	$('#recent').dialog( {
		title: title,
		modal: true,
		draggable: false,
		resizable: false,
		maxWidth: 600,
		maxHeight: 400,
		buttons: { Ok: function() { $(this).dialog( 'close' ); } },
		close: function( event, ui ) {
			$('#recent').data( 'sum', 0 ).data( 'info', {} ).data( 'fav' ).reset();
			$(window).off( 'resize.fav' ).off( 'scroll.fav' );
		}
	} );
	$(window).on( 'resize.fav', open_alert_new_msg_resize ).on( 'scroll.fav', open_alert_new_msg_resize );
}

function open_alert_new_msg_resize() {
	$('#recent').parent().position({ my : 'center', at : 'center', of : window });
}

function Download( action, data, callback )
{
	if ( !( typeof action === 'string' && action.trim() !== '' ) ) return false;

	// Init iframe
	var iframe_download = $('#iframe_download');
	if ( $(iframe_download).length === 0 ) {
		iframe_download = $('<div>')
			.attr( 'id', 'iframe_download' )
			.css( { 'display': 'none', 'width': '0px', 'height': '0px' } )
			.append( $('<iframe>').attr( 'name', 'iframe_target' ) )
			.appendTo( 'body' );
	}

	var serializeObject = function( obj, idx ){
		var res = {};
		if ( !( obj instanceof Object ) ) res[idx] = obj;
		else for ( var key in obj ) {
			var sr = serializeObject( obj[key], ( ( idx === undefined )? key : idx+'['+key+']') );
			for ( var k in sr ) res[k] = sr[k];
		}
		return res;
	};
	data = serializeObject( data );

	// Create a post form on iframe body and post data
	var frm = $('<form>').attr( 'method', 'POST' ).attr( 'action', './controller.php?action='+action ).attr( 'target', 'iframe_target' );
	for ( var key in data ) $(frm).append( $('<input>').attr( 'type', 'hidden' ).attr( 'name', key ).val( data[key] ) );

	// Submit and remove form
	$(frm).appendTo( iframe_download ).submit().remove();
}

function Ajax( action, data, callback, method )
{
	var buildBar =  function(){

		var divBuildBar= $('#divProgressBar');

		if( divBuildBar.length == 0 ){

			divBuildBar = $("<div>")
			.attr('id','divProgressBar')
			.css('visibility','hidden')
			.css('width','103px')
			.css("background","#cc4444")
			.css("position","fixed")
			.css("top", "0px")
			.css("right","0px")
			.css("text-align", "center")
			.html('&nbsp;&nbsp;<font face="Verdana" size="2" color="WHITE">'+$('#txt_loading').val()+'...</font>&nbsp;');
			
			document.body.appendChild( $(divBuildBar)[0] );			
		}

		return divBuildBar;
	};

	var showProgressBar = function(){
		
		var div = buildBar();
		
		$(div).css('visibility','hidden');
		
		$(div).css('visibility','visible');
	};

	var hideProgressBar = function(){
		
		var div = buildBar();
		
		$(div).css('visibility','hidden');		
	}

	if ( !( typeof action === 'string' && action.trim() !== '' ) ) return false;
	if ( !( typeof method === 'string' && method === 'GET' ) ) method = 'POST';

	showProgressBar();
	
	var opts = {
		method      : method,
		type        : method,
		url         : './controller.php?action='+action,
		dataType    : 'json',
		cache       : false
	};
	
	if ( typeof data !== 'undefined' ) {
		if ( data.nodeType === Node.ELEMENT_NODE || data.__proto__ === jQuery.fn ) {
			var serializeForm = function( $obj ) {
				var formData = new FormData();
				var count_files = 0;
				$.each($obj.find('input[type=file]'), function( i, tag ) {
					$.each($(tag)[0].files, function( i, file ) {
						formData.append( tag.name, file );
						count_files++;
					});
				});
				if ( count_files ) formData.append( 'count_files', count_files );
				var params = $obj.serializeArray();
				$.each(params, function ( i, val ) {
					formData.append( val.name, val.value );
				});
				return formData;
			};
			opts.data        = serializeForm( $(data) );
			opts.processData = false;
			opts.contentType = false;
		} else opts.data = data;
	}
	// Set length for objects to compatibility in loops for( var i=0; data.length; i++ )
	var f_count = function( node ) {
		if ( node === null ) return;
		if ( typeof node === 'object' ) {
			for (var m in node ) node[m] = f_count( node[m] );
			if ( node[0] && typeof node.length === 'undefined' ) {
				node.length = 0;
				while ( node[node.length] ) node.length++;
			}
		} 
		return node;
	};
	return $.ajax( opts ).done( function( data, textStatus, jqXHR ) {
		if ( typeof callback === 'function' ) callback( f_count( data ), textStatus, jqXHR );
		hideProgressBar();
	} ).fail(function() { hideProgressBar(); write_msg( get_lang( 'An unknown error occurred. The operation could not be completed.' ) ); });
}
