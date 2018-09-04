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
		if (preferences.use_local_messages==1) { //O I.E 7 estava se atrapalhando caso esses loads fossem feitos após as chamadas ajax. Antes não apresentou problemas...
			connector.loadScript('mail_sync');
			if (is_ie)
				connector.loadScript('TreeShow');
			setTimeout('auto_archiving()', 30000);
			
		}
		cExecute ("$this.imap_functions.get_range_msgs2&folder=INBOX&msg_range_begin=1&msg_range_end="+preferences.max_email_per_page+"&sort_box_type=SORTARRIVAL&search_box_type=ALL&sort_box_reverse=1", handler_draw_box);
		cExecute ("$this.imap_functions.get_folders_list&onload=true", update_menu);
		cExecute ("$this.db_functions.get_dropdown_contacts", save_contacts); //Save contacts needs preferences.
		
		if(preferences.hide_folders == "1")
		{
			$('#divAppboxHeader').html(title_app_menu);

			//Quando a esta marcada a opcao ocultar pastas( preferences.hide_folder ), o titulo ExpressoMail é apagado;
			$("#main_title").html('');
		}
		
		if( preferences.outoffice && preferences.outoffice == "1" )
			write_msg(get_lang("Attention, you are in out of office mode."), true);

		ConstructMenuTools();
		
		if ( (preferences.use_local_messages==1) && (expresso_local_messages.is_offline_installed()) )  //Precisa ser feito após a renderização da caixa de emails
			check_mail_in_queue();
		
		// Insere a applet de criptografia
		if (preferences.use_signature_digital_cripto == '1'){
			loadApplet();
		}
		// Fim da inserção da applet

		// Inicia Messenger
		setTimeout( function(){ init_messenger(); }, 1000 );
	}

	// Versão
	Element('divAppboxHeader').innerHTML = title_app;

	// Get cyrus delimiter
	cyrus_delimiter = Element('cyrus_delimiter').value;

	cExecute ("$this.functions.get_preferences", save_preferences);
	cExecute ("phpgwapi.browser.isMobile", function( data )
	{
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
		'<b>ExpressoMail Offline</b><font size=1><b> - Versão 1.0</b></font></td>' +
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
 * @author Mário César Kolling <mario.kolling@serpro.gov.br>
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
	função que remove todos os anexos...
*/
function remove_all_attachments(folder,msg_num) {

	var call_back = function(data) {
		if(!data.status) {
			alert(data.msg);
		}
		else {
			msg_to_delete = Element(msg_num);
			change_tr_properties(msg_to_delete, data.msg_no);
			msg_to_delete.childNodes[1].innerHTML = "";
			write_msg(get_lang("Attachments removed"));
			delete_border(msg_num+'_r','false'); //close email tab
		}
	};
	if (confirm(get_lang("delete all attachments confirmation")))
		cExecute ("$this.imap_functions.remove_attachments&folder="
				+folder+"&msg_num="+msg_num, call_back);
}
function watch_changes_in_msg(border_id)
{
	if (document.getElementById('border_id_'+border_id))
	{
		function keypress_handler ()
		{
			away=false;
			var save_link = Element("save_message_options_"+border_id);
			save_link.onclick = function onclick(event) { openTab.toPreserve[border_id] = true; save_msg(border_id); } ;
			save_link.className = 'message_options';
		};

		var obj = document.getElementById('body_'+border_id).contentWindow.document;
		if ( obj.addEventListener )
				obj.addEventListener('keypress', keypress_handler, false);
		else if ( obj.attachEvent )
			obj.attachEvent('onkeypress', keypress_handler);

		var subject_obj = document.getElementById('subject_'+border_id);
		if ( subject_obj.addEventListener )
				subject_obj.addEventListener('keypress', keypress_handler, false);
		else if ( subject_obj.attachEvent )
			subject_obj.attachEvent('onkeypress', keypress_handler);

		var to_obj = document.getElementById('to_'+border_id);
		if ( to_obj.addEventListener )
				to_obj.addEventListener('keypress', keypress_handler, false);
		else if ( to_obj.attachEvent )
			to_obj.attachEvent('onkeypress', keypress_handler);

	}
}

function show_msg_img(msg_number,folder){
	var call_back = function(data){
	   data.showImg = true;
	   if (!Element(data.msg_number)){
		   trElement = document.createElement('DIV');
		   trElement.id = data.msg_number;
		   Element("tbody_box").appendChild(trElement);
	   }
	   show_msg(data);
	}

	proxy_mensagens.msg_img(msg_number,folder,call_back);

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
			// se não existir, mostra mensagem de erro.
			write_msg(get_lang('The preference "%1" isn\'t enabled.', get_lang('Enable digitally sign/cipher the message?')));
		} else {
			// se existir prepara os dados para serem enviados e chama a
			// operação na applet

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
	}
	else{
		var ID = msg_info.original_ID ? msg_info.original_ID : msg_info.msg_number;
		var id_msg_read = ID+"_r";

		if (preferences.use_shortcuts == '1')
			select_msg(ID, 'null');
		// Call function to draw message
		// If needed, delete old border
		if (openTab.type[currentTab] == 2 || openTab.type[currentTab] == 3)
			delete_border(currentTab,'false');


		if(Element("border_id_" + id_msg_read)) {
			alternate_border(id_msg_read);
			resizeWindow(); 
		}
		else {
			var border_id = create_border(msg_info.subject, id_msg_read, 2 , msg_info.msg_folder );
			if(border_id)
			{
				draw_message(msg_info,border_id);
				var unseen_sort = document.getElementById('span_flag_UNSEEN').getAttribute('onclick');
				unseen_sort = unseen_sort.toString();
				if ( !(unseen_sort.indexOf("'UNSEEN' == 'UNSEEN'") < 0) )
				{
					var sort_type = sort_box_type;
					sort_box_type = null;
					sort_box('UNSEEN', sort_type);
				}
			}
			else
				return;
		}

		var domains = "";
		if ((msg_info.DispositionNotificationTo) && (!msg_is_read(ID) || (msg_info.Recent == 'N')))
		{
			if (preferences.notification_domains != undefined && preferences.notification_domains != "")
			{
				domains = preferences.notification_domains.split(',');
			}
			else
			{
				var confNotification = true;
			 }
			for (var i = 0; i < domains.length; i++)
				if (msg_info.DispositionNotificationTo.match(domains[i]+">"))
				{
					var confNotification = true;
					break;
				}
				if (confNotification == undefined)
					var confNotification = confirm(get_lang("The sender:\n%1\nwaits your notification of reading. Do you want to confirm this?",msg_info.DispositionNotificationTo), "");

			if (confNotification)
				cExecute ("$this.imap_functions.send_notification&notificationto="+msg_info.DispositionNotificationTo+"&date="+msg_info.udate+"&subject="+url_encode(msg_info.subject), handler_sendNotification);
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

function auto_archiving() {
	expresso_mail_sync.start_sync();
	setTimeout('auto_archiving()',600000);
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
				if ( idx > 0 ) select_msg( $('#tbody_box tr:nth-child('+idx+')').attr('id') );
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
		cExecute ("$this.imap_functions.refresh&folder="+current_folder+"&msgs_existent="+string_msgs_in_main+"&msg_range_begin="+msg_range_begin+"&msg_range_end="+msg_range_end+"&sort_box_type="+sort_box_type+"&search_box_type="+search_box_type+"&sort_box_reverse="+sort_box_reverse, handler_refresh);
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

		Element('chk_box_select_all_messages').checked = false;
		if (currentTab)
			mail_msg = Element("tbody_box_"+currentTab);
		else
			mail_msg = Element("tbody_box");

		if ( preferences.use_shortcuts == '1') {
				//Last msg is selected
				if (mail_msg && exist_className(mail_msg.childNodes[mail_msg.childNodes.length-1], 'selected_shortcut_msg') ) {
					select_msg('null', 'up', true);
				}
				else {
					if (!select_msg('null', 'down', true)) {
						select_msg('null', 'up', true);
					}
				}
			}

		if (show_success_msg) {
			if (data.msgs_number.length == 1)
				write_msg(get_lang("The message was deleted."));
			else
				write_msg(get_lang("The messages were deleted."));
		}
		if (openTab.type[currentTab] > 1){
			var msg_to_delete = Element(msgs_number);
			if (parseInt(preferences.delete_and_show_previous_message) && msg_to_delete) {
				if (msg_to_delete.previousSibling){
 					var previous_msg = msg_to_delete.previousSibling.id;
 					cExecute("$this.imap_functions.get_info_msg&msg_number="+previous_msg+"&msg_folder=" + url_encode(current_folder), show_msg);
 				} 
				else
					delete_border(currentTab,'false');
			}
			else
				delete_border(currentTab,'false');
		}
		for (var i=0; i<data.msgs_number.length; i++){
				var msg_to_delete = Element(data.msgs_number[i]);
				if (msg_to_delete){
						removeAll(msg_to_delete.id);
				}
		}
		Element('tot_m').innerHTML = parseInt(Element('tot_m').innerHTML) - data.msgs_number.length;
		refresh();
	}

	if (msgs_number == 'selected')
		msgs_number = get_selected_messages();
	if (msgs_number.length > 0 || parseInt(msgs_number) > 0)
		cExecute ("$this.imap_functions.delete_msgs&folder="+folder+"&msgs_number="+msgs_number+"&border_ID="+border_ID+"&sort_box_type="+sort_box_type+"&search_box_type="+search_box_type+"&sort_box_reverse="+sort_box_reverse, handler_delete_msgs);
	else
		write_msg(get_lang('No selected message.'));
}


function move_search_msgs(border_id, new_folder, new_folder_name){
	var selected_messages = '';
	var temp_msg;
	var main_list = Element("tbody_box_"+currentTab.substr(7)).childNodes;
	for (j = 0; j < main_list.length; j++)	{
		var check_box = main_list[j].firstChild.firstChild;
		if(check_box && check_box.checked) {
			if (proxy_mensagens.is_local_folder(main_list[j].getAttribute('name'))) {
				alert(get_lang("You cant manipulate local messages on search"));
				return;
			}
			selected_messages += main_list[j].id + ',';
		}
	}
	selected_messages = selected_messages.substring(0,(selected_messages.length-1));
	var handler_move_search_msgs = function(data){
		if(!data || !data.msgs_number)
			return;
		else if(data.deleted) {
			if (data.msgs_number.length == 1)
				write_msg(get_lang("The message was deleted."));
			else
				write_msg(get_lang("The messages were deleted."));
		}
		else{
			if (data.msgs_number.length == 1)
				write_msg(get_lang("The message was moved to folder ") + lang_folder(data.new_folder_name));
			else
				write_msg(get_lang("The messages were moved to folder ") + lang_folder(data.new_folder_name));
		}

		selected_messages = selected_messages.split(",");
		for (i = 0; i < selected_messages.length; i++){
			removeAll(selected_messages[i]);
		}
		// Update Box BgColor
		var box = Element("tbody_box_"+currentTab.substr(7)).childNodes;
		if(main_list.length > 1){
			updateBoxBgColor(box);
		}
		connector.purgeCache();
	}

	if (selected_messages){
		var selected_param = "";
		if (selected_messages.indexOf(',') != -1)
		{
			selected_msg_array = selected_messages.split(",");
			for (i = 0; i < selected_msg_array.length; i++){
				var tr = Element(selected_msg_array[i]);
				if (tr.getAttribute('name') == new_folder)
				{
					write_msg(get_lang('At least one message have the same origin'));
					return false;
				}
				trfolder = (tr.getAttribute('name') == null?get_current_folder():tr.getAttribute('name'));
					selected_param += ','+trfolder+';'+tr.id.replace(/_[a-zA-Z0-9]+/,"");
			}
		}
		else
		{
			var tr=Element(selected_messages);
			if (tr.getAttribute('name') == new_folder)
			{
				write_msg(get_lang('The origin folder and the destination folder are the same.'));
				return false;
			}
			trfolder = (tr.getAttribute('name') == null?get_current_folder():tr.getAttribute('name'));
			selected_param=trfolder+';'+tr.id.replace(/_[a-zA-Z0-9]+/,"");
		}
		var params = "";
		if (!new_folder && parseInt(preferences.save_deleted_msg)){
			new_folder = 'INBOX'+cyrus_delimiter+trashfolder;
			new_folder_name = trashfolder;
			params = "&delete=true";
		}

		params += "&selected_messages="+url_encode(selected_param);
		if(new_folder) {
			params += "&new_folder="+url_encode(new_folder);
			params += "&new_folder_name="+url_encode(new_folder_name);
		}
		cExecute ("$this.imap_functions.move_search_messages", handler_move_search_msgs, params);
	}
	else
		write_msg(get_lang('No selected message.'));
}

function move_msgs2(folder, msgs_number, border_ID, new_folder, new_folder_name,show_success_msg)
{
	if ( ( !folder ) || ( folder == 'null' ) )
		folder = Element("input_folder_"+msgs_number+"_r") ? Element("input_folder_"+msgs_number+"_r").value : (openTab.imapBox[currentTab] ? openTab.imapBox[currentTab]:get_current_folder());
	
	if ( msgs_number == 'selected' && openTab.type[currentTab] == 1 )
		return move_search_msgs( 'content_id_'+currentTab, new_folder, new_folder_name );
	
	var handler_move_msgs = function( data )
	{
		if ( typeof( data ) == 'string' ) {
			alert( get_lang( 'Error moving message.' )+":\n"+data );
			return false;
		}
		
		if ( data && data.error ) {
			if ( !data.error.match( /^(.*)TRYCREATE(.*)$/ ) ) alert( get_lang( 'Error moving message.' )+":\n"+data.error );
			else {
				var move_to_folder = data.folder.split( cyrus_delimiter ).pop();
				connector.loadScript( 'TreeS' );
				alert( get_lang( 'There is not %1 folder, Expresso is creating it for you... Please, repeat your request later.', move_to_folder ) );
				connector.loadScript( 'TreeShow' );
				ttree.FOLDER = 'root';
				ttreeBox.new_past( move_to_folder );
			}
			return false;
		}
		
		//Este bloco verifica as permissoes ACL sobre pastas compartilhadas
		if ( data.status == false ) {
			alert(get_lang("You don't have permission for this operation in this shared folder!"));
			return false;
		}
		
		mail_msg = ( Element( "divScrollMain_"+numBox ) ) ? Element("divScrollMain_"+numBox).firstChild.firstChild : Element("divScrollMain_0").firstChild.firstChild;
		
		if ( typeof( data.msgs_number ) == 'string' ) data.msgs_number = data.msgs_number.split( ',' );
		write_msg( get_lang( 'The message'+( ( data.msgs_number.length == 1 )? ' was' : 's were' )+' moved to folder ' )+lang_folder( data.new_folder_name ) );
		
        if ( openTab.type[currentTab] > 1 )
        {
            msg_to_delete = Element( data.msgs_number );
            
            if ( parseInt(preferences.delete_and_show_previous_message) && msg_to_delete )
            {
                if ( msg_to_delete.previousSibling )
                {
                    var previous_msg = msg_to_delete.previousSibling.id;
                    cExecute("$this.imap_functions.get_info_msg&msg_number="+previous_msg+"&msg_folder=" + url_encode(folder), show_msg);
                }
                else
                {    
                    delete_border( data.border_ID, 'false' );
                }
            }
            else
            {    
                delete_border( data.border_ID, 'false' );
            }
            
            if( msg_to_delete != null )
            {    
                msg_to_delete.parentNode.removeChild( msg_to_delete );
                //mail_msg.removeChild(msg_to_delete);
            }
            
            if( data.border_ID.toString().indexOf("_r") > -1 )
            {
                var _msgSearch = data.border_ID.toString();
                    _msgSearch = _msgSearch.substr(0, _msgSearch.indexOf("_r") );
                
                if( Element(_msgSearch) != null )
                {
                    Element(_msgSearch).parentNode.removeChild( Element(_msgSearch) );
                }    
            }    

            // Update Box BgColor
            if( Element("tbody_box") != null )
            {
                var box = Element("tbody_box");

                if( box.childNodes.length > 0)
                {
                    updateBoxBgColor(box.childNodes);
                }
            }    
        }
		else
		{
			Element('chk_box_select_all_messages').checked = false;
			
			if ( !mail_msg ) mail_msg = Element( 'tbody_box' );
			
			var msg_to_delete;
			
			if ( typeof( msgs_number ) == 'string' ) all_search_msg = msgs_number.split( ',' );
			else if( typeof( msgs_number ) == 'number') all_search_msg = msgs_number;
			
			for ( var i=0; i <= all_search_msg.length; i++)
            {
                msg_to_delete = Element(folder+';'+all_search_msg[i]);
                if (msg_to_delete)
                    msg_to_delete.parentNode.removeChild(msg_to_delete);
            }
			
			// Store index of focus message
			if ( preferences.use_shortcuts == '1') Shortcut.focus_index( $('.selected_shortcut_msg').prevAll().length + 1 );
			
			// Remove messages rows
			$.each( data.msgs_number, function( i, id ) { $('tr#'+id).remove(); } );
			
            if (data.border_ID.indexOf('r') != -1){
                if (parseInt(preferences.delete_and_show_previous_message) && folder == get_current_folder()){
                    delete_border(data.border_ID,'false');
                    show_msg(data.previous_msg);
                }
                else
                    delete_border(data.border_ID,'false');
            }
            if(folder == get_current_folder())
                Element('tot_m').innerHTML = parseInt(Element('tot_m').innerHTML) - data.msgs_number.length;

            refresh();
        }   
        
    }// END VAR HANDLER_MOVE_MSG

    if (msgs_number == 'selected')
    {
        msgs_number = get_selected_messages();
    }

    if(currentTab.toString().indexOf("_r") != -1)
    {
        msgs_number = currentTab.toString().substr(0,currentTab.toString().indexOf("_r"));
        border_ID   = currentTab.toString();
    }

    if( msgs_number.toString().indexOf("_s") != -1 )
    {    
        folder = Element(msgs_number).getAttribute("name");
        msgs_number = msgs_number.toString().substr(0 ,msgs_number.toString().indexOf("_s") );
    }
    
    if ( folder == new_folder )
    {
        write_msg(get_lang('The origin folder and the destination folder are the same.'));
        return;
    }

    if (parseInt(msgs_number) > 0 || msgs_number.length > 0)
		cExecute("$this.imap_functions.move_messages&folder="+encodeURIComponent(folder)+"&msgs_number="+msgs_number+"&border_ID="+border_ID+"&sort_box_type="+sort_box_type+"&search_box_type="+search_box_type+"&sort_box_reverse="+sort_box_reverse+"&reuse_border="+border_ID+"&new_folder="+encodeURIComponent(new_folder)+"&new_folder_name="+encodeURIComponent(new_folder_name)+"&get_previous_msg="+preferences.delete_and_show_previous_message, handler_move_msgs);    
    else
        write_msg(get_lang('No selected message.'));
}


function move_msgs(folder, msgs_number, border_ID, new_folder, new_folder_name) {
	move_msgs2(folder, msgs_number, border_ID, new_folder, new_folder_name,true);
}

function archive_msgs(folder,folder_dest,id_msgs) {
	if(proxy_mensagens.is_local_folder(folder)) {
		write_msg(get_lang("You cant archive local mails"));
		return;
	}

	if(currentTab.toString().indexOf("_r") != -1){
            id_msgs = currentTab.toString().substr(0,currentTab.toString().indexOf("_r"));
        }

	if(!id_msgs)
		id_msgs = get_selected_messages();

	if(folder_dest=='local_root' || folder_dest==null) //Caso seja o primeiro arquivamento...
		folder_dest = 'local_Inbox';

	if (parseInt(id_msgs) > 0 || id_msgs.length > 0)
		expresso_mail_sync.archive_msgs(folder,folder_dest,id_msgs);
		//cExecute("$this.imap_functions.get_info_msgs&folder=" + folder + "&msgs_number=" + id_msgs , handler_arquivar_mensagens);
	else
		write_msg(get_lang('No selected message.'));

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

function new_message(type, border_ID){
		if (Element('show_img_link_'+border_ID))
		{
			show_msg_img(border_ID.match(/^\d*/)[0], Element('input_folder_'+border_ID).value);
		}
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
	var title = '';
	data = [];
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
	if (document.getElementById("body_" + border_ID))
		data.body = document.getElementById("body_" + border_ID).innerHTML;

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
	var signature = preferences.type_signature == 'html' ? preferences.signature : preferences.signature.replace(/\n/g, "<br>");
	if(type!="new" && type!="edit")
		data.is_local_message = (document.getElementById("is_local_"+border_ID).value=="1")?true:false;
	switch(type){
		case "reply_without_history":
			Element("to_" + new_border_ID).value = data.to;
			title = "Re: " + data.subject;
			Element("subject_" + new_border_ID).value = "Re: " + data.subject;			
			useOriginalAttachments(new_border_ID,border_ID,data.is_local_message);
			var body = Element("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1"){
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>");
			}
			else{
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'></body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			//Focus
			if (is_ie)
				window.setTimeout('document.getElementById("body_'+new_border_ID+'").contentWindow.focus();', 300);
			else
				body.contentWindow.focus();
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			msg_reply_from = document.createElement('input');
			msg_reply_from.id = "msg_reply_from_" + new_border_ID;
			msg_reply_from.type = "hidden";
			msg_reply_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_reply_from);
			break;
		case "reply_with_history":
			title = "Re: " + data.subject;
			Element("subject_" + new_border_ID).value = "Re: " + data.subject;
			Element("to_" + new_border_ID).value = data.to;
			useOriginalAttachments(new_border_ID,border_ID,data.is_local_message);
			block_quoted_body = make_body_reply(data.body, data.to, data.date_day, data.date_hour);
			var body = Element("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1") {
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>" + block_quoted_body + "</body></html>");
			}
			else {
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>"+block_quoted_body+"</body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			//Focus
			if (is_ie)
				window.setTimeout('document.getElementById("body_'+new_border_ID+'").contentWindow.focus();', 300);
			else
				body.contentWindow.focus();
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			msg_reply_from = document.createElement('input');
			msg_reply_from.id = "msg_reply_from_" + new_border_ID;
			msg_reply_from.type = "hidden";
			msg_reply_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_reply_from);
			break;
		case "reply_to_all_without_history":
			// delete user email from to_all array.
			data.to_all = new Array();
			var j = 0;
			for(i = 0; i < _array_to_all.length; i++) {
				if(_array_to_all[i].lastIndexOf(Element("user_email").value) == "-1"){
					data.to_all[j++] = _array_to_all[i];
				}
			}
			data.to_all = data.to_all.join(",");

			title = "Re: " + data.subject;
			Element("subject_" + new_border_ID).value = "Re: " + data.subject;
			Element("to_" + new_border_ID).value = data.to;
			Element("to_" + new_border_ID).value += ', ' + data.to_all;
			if (data.cc){
				Element("cc_" + new_border_ID).value = data.cc;
				Element("a_cc_link_" + new_border_ID).style.display='none';
				Element("tr_cc_" + new_border_ID).style.display='';
				Element('space_link_' + new_border_ID).style.display='none';
			}
			useOriginalAttachments(new_border_ID,border_ID,data.is_local_message);
			var body = Element("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1") {
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>");
			}
			else {
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'></body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			//Focus
			if (is_ie)
				window.setTimeout('document.getElementById("body_'+new_border_ID+'").contentWindow.focus();', 300);
			else
				body.contentWindow.focus();
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			msg_reply_from = document.createElement('input');
			msg_reply_from.id = "msg_reply_from_" + new_border_ID;
			msg_reply_from.type = "hidden";
			msg_reply_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_reply_from);
			break;
		case "reply_to_all_with_history":
			// delete user email from to_all array.
			data.to_all = new Array();
			var j = 0;
				for(i = 0; i < _array_to_all.length; i++) {
				if(_array_to_all[i].lastIndexOf(Element("user_email").value) == "-1"){
					data.to_all[j++] = _array_to_all[i];
				}
		}
			if (data.to_all != get_lang("undisclosed-recipient"))
				data.to_all = data.to_all.join(",");
			else
				data.to_all = "";
			title = "Re: " + data.subject;
			Element("to_" + new_border_ID).value = data.to;
			Element("to_" + new_border_ID).value += ', ' + data.to_all;
			if (data.cc){
				document.getElementById("cc_" + new_border_ID).value = data.cc;
				document.getElementById("a_cc_link_" + new_border_ID).style.display='none';
				document.getElementById("tr_cc_" + new_border_ID).style.display='';
				document.getElementById('space_link_' + new_border_ID).style.display='none';
			}
			document.getElementById("subject_" + new_border_ID).value = "Re: " + data.subject;
			useOriginalAttachments(new_border_ID,border_ID,data.is_local_message);
			block_quoted_body = make_body_reply(data.body, data.to, data.date_day, data.date_hour);
			var body = document.getElementById("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1") {
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>" + block_quoted_body + "</body></html>");
			}
			else {
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>"+block_quoted_body+"</body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			//Focus
			if (is_ie)
				window.setTimeout('document.getElementById("body_'+new_border_ID+'").contentWindow.focus();', 300);
			else
				body.contentWindow.focus();
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			msg_reply_from = document.createElement('input');
			msg_reply_from.id = "msg_reply_from_" + new_border_ID;
			msg_reply_from.type = "hidden";
			msg_reply_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_reply_from);
			break;
		case "forward":
			data.is_local_message = (document.getElementById("is_local_"+border_ID).value=="1")?true:false;
			msg_forward_from = document.createElement('input');
			msg_forward_from.id = "msg_forward_from_" + new_border_ID;
			msg_forward_from.type = "hidden";
			msg_forward_from.value = Element("msg_number_" + border_ID).value;
			Element("content_id_" + new_border_ID).appendChild(msg_forward_from);
			title = "Fw: " + data.subject;
			document.getElementById("subject_" + new_border_ID).value = "Fw: " + data.subject;
			var divFiles = Element("divFiles_"+new_border_ID);
			var campo_arquivo;
			if (Element("attachments_" + border_ID)){
				var attachments = document.getElementById("attachments_" + border_ID).getElementsByTagName("a");
				
				for (var i = (attachments.length > 1 ? 1 : 0); i < attachments.length; i++){
					if((attachments[i].tagName=="SPAN") || (attachments[i].tagName=="IMG") ||
							((attachments[i].href.indexOf("javascript:download_local_attachment")==-1)&&(attachments[i].href.indexOf("javascript:export_attachments")==-1)))
						continue;
					var link_attachment = document.createElement("A");
					link_attachment.setAttribute("href", attachments[i].href);
					link_attachment.innerHTML = attachments[i].firstChild.nodeValue + '<br/>';
					$(link_attachment).data($(attachments[i]).data());
					
					if (data.is_local_message) {//Local messages
						document.getElementById("is_local_forward"+new_border_ID).value = "1";
						var tmp = link_attachment.href.substring(link_attachment.href.indexOf("(") + 2);//Pula o parenteses e a aspas
						tmp = tmp.substring(0, tmp.length - 2);//corta a aspas e o parenteses
						tmp = replaceAll(tmp,"%20"," ");
						if (!tmp.match(/inc\/gotodownload.php/)) { //Anexos após ticket #1257 que usa gotodownload
							var tempNomeArquivo = tmp.split("/");
							var nomeArquivo = tempNomeArquivo[tempNomeArquivo.length - 1];
						}
						else {
							var tempNomeArquivo = tmp.split("&newfilename=");
							var nomeArquivo = tempNomeArquivo[tempNomeArquivo.length - 1];
						}
						
						if(nomeArquivo.match(/\.[a-z]{1,3}\.php$/i)!=null) //Anexos no gears podem vir com .php depois de sua extensão. tenho que tirar o .php para ficar o nome real do arquivo.
							nomeArquivo = nomeArquivo.substring(0, nomeArquivo.length - 4);
						campo_arquivo = addForwardedFile(new_border_ID, nomeArquivo, link_attachment.href);
							
						if(!expresso_offline)
							expresso_local_messages.getInputFileFromAnexo(campo_arquivo, tmp);
						else //To offline, you just set the url on value of a hidden input.
							campo_arquivo.value = tmp;

					}
					else {
						var sdata = escape(connector.serialize($(link_attachment).data()));
						divFiles.innerHTML += "<input style='border:0' type='CHECKBOX' name='forwarding_attachments[]' checked value=\""+sdata+"\"/>";
						divFiles.innerHTML += "<link style='border:0' name='file_"+i+"' id='inputFile_"+border_ID+i+"'/>";
						divFiles.appendChild(link_attachment);
					}
				}
			}
			var body = Element("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1") {
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>" + make_forward_body(data.body, data.to, data.date, data.subject, data.to_all, data.cc) + "</body></html>");
			}
			else {
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>"+make_forward_body(data.body, data.to, data.date, data.subject, data.to_all, data.cc)+"</body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			Element("to_" + new_border_ID).focus();
			break;
		case "new":
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
			var body = document.getElementById("body_" + new_border_ID);
			body.contentWindow.document.open();
			// Insert the signature automaticaly at message body if use_signature preference is set
			if (preferences.use_signature == "1") {
				body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>" + "<br>" + signature + "</body></html>");
			}
			else {
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'></body></html>");
			}
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			Element("to_" + new_border_ID).focus();
			break;
		case "edit":
			openTab.imapBox[new_border_ID] = folder_message.value;
			openTab.toPreserve[new_border_ID] = true;
			openTab.imapUid[new_border_ID] = parseInt(border_ID.substr(0,border_ID.indexOf("_")));
			document.getElementById('font_border_id_'+new_border_ID).innerHTML = data.subject;
			title = "Edição: "+data.subject;
			
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

			var divFiles = Element("divFiles_"+new_border_ID);
			if (Element("attachments_" + border_ID)){
				var attachments = document.getElementById("attachments_" + border_ID).getElementsByTagName("a");
				for (var i = (attachments.length > 1 ? 1 : 0); i < attachments.length; i++){
					if((attachments[i].tagName=="SPAN") || (attachments[i].tagName=="IMG") ||
							((attachments[i].href.indexOf("javascript:download_local_attachment")==-1)&&(attachments[i].href.indexOf("javascript:export_attachments")==-1)))
						continue;
					var link_attachment = document.createElement("A");
					link_attachment.setAttribute("href", attachments[i].href);
					link_attachment.innerHTML = attachments[i].innerHTML;
					$(link_attachment).data($(attachments[i]).data());
					var sdata = escape(connector.serialize($(link_attachment).data()));
					divFiles.innerHTML += "<input style='border:0' type='CHECKBOX' name='forwarding_attachments[]' checked value=\""+sdata+"\"/>";
					divFiles.appendChild(link_attachment);
				}
			}
			var body = Element("body_" + new_border_ID);
			body.contentWindow.document.open();
			body.contentWindow.document.write("<html><body bgcolor='#FFFFFF'>"+data.body+"</body></html>");
			body.contentWindow.document.close();
			body.contentWindow.document.designMode = "on";
			//Focus
			if (is_ie)
				window.setTimeout('document.getElementById("body_'+new_border_ID+'").contentWindow.focus();', 300);
			else
				body.contentWindow.focus();
			config_events( body.contentWindow.document, 'onkeyup', function( e )
			{
				if ( e.keyCode == 13 )
				{
					var paragraphs = body.contentWindow.document.getElementsByTagName( 'p' );
					for ( p = 0; p < paragraphs.length; p++ )
						paragraphs.item( p ).style.margin = '0px';
				}
			});
			break;
		default:
	}

	// IM Module Enabled
	if( window.parent.loadscript && loadscript.autoStatusIM )
	{
		config_events( body.contentWindow.document, "onkeypress", loadscript.autoStatusIM );
	}

	if ( ! expresso_offline )
	{
		if ( mobile_device )
		{
			var text_plain = document.getElementById( 'textplain_rt_checkbox_' + new_border_ID );
			text_plain.click( );
			text_plain.parentNode.style.display = 'none';
		}
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
	RichTextEditor.loadStyle("pre","main.css");

	Element("border_id_" + new_border_ID).title = title;
	set_border_caption("border_id_" + new_border_ID, title);

        if(!expresso_offline && preferences.use_SpellChecker != '0')
	    setupSpellChecker();

	return new_border_ID; //Preciso retornar o ID da nova mensagem.
}

function useOriginalAttachments(new_id_border,old_id_border,is_local)
{
	var divFiles = Element("divFiles_"+new_id_border);
	if (Element("attachments_" + old_id_border)) {
		var areaOldAttachments = document.createElement("DIV");
		areaOldAttachments.id = "area_div_attachments_"+new_id_border;
		divFiles.appendChild(areaOldAttachments);
		var optAttachments = document.createElement("A");
		optAttachments.setAttribute("href","javascript:void(0)");
		optAttachments.tabIndex = -1;
		optAttachments.innerHTML = get_lang("Original attachments: add")+"</br>";
		areaOldAttachments.appendChild(optAttachments);
		var divOriginalAttachments = document.createElement("DIV");
		divOriginalAttachments.id = "div_attachments_"+new_id_border;
		optAttachments.onclick = function(){
			if(document.getElementById('div_attachments_'+new_id_border))
			{
				areaOldAttachments.removeChild(document.getElementById('div_attachments_'+new_id_border));
				optAttachments.innerHTML = get_lang("Original attachments: add")+"</br>";
			}
			else
			{
				areaOldAttachments.appendChild(divOriginalAttachments);
				optAttachments.innerHTML = get_lang("Original attachments: remove")+"</br>";
			}
			return false;};
			var attachments = document.getElementById("attachments_" + old_id_border).getElementsByTagName("a");
			for (var i = (attachments.length > 1 ? 1 : 0); i < attachments.length; i++){
				if (!is_local) {
					var link_attachment = document.createElement("A");
					link_attachment.setAttribute("href", attachments[i].href);
					link_attachment.innerHTML = attachments[i].firstChild.nodeValue + '<br/>';
					$(link_attachment).data($(attachments[i]).data());
					var sdata = escape(connector.serialize($(link_attachment).data()));
					divOriginalAttachments.innerHTML += "<input style='border:0' type='CHECKBOX' name='forwarding_attachments[]' checked value=\""+sdata+"\"/>";
					divOriginalAttachments.appendChild(link_attachment);
				}
				else {
					document.getElementById("is_local_forward"+new_id_border).value = "1";
					var link = attachments[i].href.replace("javascript:download_local_attachment('", "").replace("')", "");
					var name = attachments[i].innerHTML.substring(0, attachments[i].innerHTML.lastIndexOf("("));
					var campo_arquivo = addForwardedFile(new_id_border, name, attachments[i].href, divOriginalAttachments);
					expresso_local_messages.getInputFileFromAnexo(campo_arquivo, link);
				}
			}
	}
}

function send_message_return(data, ID){
	watch_changes_in_msg(ID);

	var sign = false;
	var crypt = false;
	if ((preferences.use_assinar_criptografar != '0') && (preferences.use_signature_digital_cripto != '0')){
		var checkSign = document.getElementById('return_digital_'+ID)
		if (checkSign.checked){
			sign = true;
		}

		var checkCript = document.getElementById('return_cripto_'+ID);
		if (checkCript.checked){
			crypt = true;
		}
	}

	if (typeof(data) == 'object' && !data.success)
	{
		connector = new  cConnector();
		connector.showProgressBar();

		if (sign || crypt){
			var operation = '';
			if (sign){
				operation = 'sign';
			}
			else { // crypt
				//TODO: Colocar mensagem de erro, e finalizar o método.
				operation = 'nop';
			}
		}

		if (data.body){
			Element('cert_applet').doButtonClickAction(operation, ID, data.body);
		}
		else {
			alert(data.error);
		}

		return;
	}
	if(data && data.success == true ){
		// if send ok, set a flag as answered or forwarded
		var msg_number_replied = Element('msg_reply_from_'+ID);
		var msg_number_forwarded = Element('msg_forward_from_'+ID);

		if (msg_number_replied){
			proxy_mensagens.proxy_set_message_flag(msg_number_replied.value, 'answered');
		}
		else if (msg_number_forwarded){
			proxy_mensagens.proxy_set_message_flag(msg_number_forwarded.value, 'forwarded');
		}
		if(expresso_offline){
			write_msg(get_lang('Your message was sent to queue'));
			delete_border(ID,'true');
			return;
		}else{
			if( wfolders.getAlertMsg() )
			{
				write_msg(get_lang('Your message was sent and save.'));
				wfolders.setAlertMsg( false );
				if ( data.refresh_folders ) ttreeBox.update_folder();
			}
			else {
				write_msg(get_lang('Your message was sent.'));
			}
		}
		// If new dynamic contacts were added, update the autocomplete ....
		if(data.new_contacts){
			var ar_contacts = data.new_contacts.split(',;');
			for(var j in ar_contacts){
				// If the dynamic contact don't exist, update the autocomplete....
				if((contacts+",").indexOf(ar_contacts[j]+",") == -1)
					contacts += "," + ar_contacts[j];
			}
		}
		if ((! openTab.toPreserve[ID]) && (openTab.imapUid[ID] != 0))
			cExecute ("$this.imap_functions.delete_msgs&folder="+openTab.imapBox[ID]+"&msgs_number="+openTab.imapUid[ID],function(data){ return });
		delete_border(ID,'true'); // Becarefull: email saved automatically should be deleted. delete_border erase information about openTab
 	}
	else{
		if(data == 'Post-Content-Length')
			write_msg(get_lang('The size of this message has exceeded  the limit (%1B).',Element('upload_max_filesize').value));
		else if(data)
			write_msg(data);
		else
			write_msg(get_lang("Connection failed with %1 Server. Try later.", "Web"));
		
		var save_link = Element("save_message_options_"+ID);
		save_link.onclick = function onclick(event) { openTab.toPreserve[ID] = true; save_msg(ID); } ;
		save_link.className = 'message_options';
	}
	if(!expresso_offline)
		connector.hideProgressBar();
}

/**
 * Método chamado pela applet para retornar o resultado da assinatura/decifragem do e-mail.
 * para posterior envio ao servidor.
 * @author Mário César Kolling <mario.kolling@serpro.gov.br>, Bruno Vieira da Costa <bruno.vieira-costa@serpro.gov.br>
 * @param smime O e-mail decifrado/assinado
 * @param ID O ID do e-mail, para saber em que aba esse e-mail será mostrado.
 * @param operation A operação que foi realizada pela applet (assinatura ou decifragem)
 */
function appletReturn(smime, ID, operation, folder){

	if (!smime){ // Erro aconteceu ao assinar ou decifrar e-mail
		connector = new  cConnector();
		connector.hideProgressBar();
		return;
	}

	if(operation=='decript')
	{
		var handler = function(data){

			if(data.msg_day == '')
			{
				header=expresso_local_messages.get_msg_date(data.original_ID, proxy_mensagens.is_local_folder(get_current_folder()));

				data.fulldate=header.fulldate;
				data.smalldate=header.smalldate;
				data.msg_day = header.msg_day;
				data.msg_hour = header.msg_hour;

			}
			this.show_msg(data);
		}
		para="&source="+smime+"&ID="+ID+"&folder="+folder;
		cExecute ("$this.imap_functions.show_decript&", handler, para);
	}else
	{
		ID_tmp = ID;
		// Lê a variável e chama a nova função cExecuteForm
		// Processa e envia para o servidor web
		// Faz o request do connector novamente. Talvez implementar no connector
		// para manter coerência.

		var handler_send_smime = function(data){
			send_message_return(data, this.ID_tmp); // this is a hack to escape quotation form connector bug
		};

		var textArea = document.createElement("TEXTAREA");
		textArea.style.display='none';
		textArea.id = 'smime';
		textArea.name = "smime";
		textArea.value += smime;

		// Lê a variável e chama a nova função cExecuteForm
		// Processa e envia para o servidor web
		// Faz o request do connector novamente. Talvez implementar no connector
		// para manter coerência.
		if (is_ie){
			var i = 0;
			while (document.forms(i).name != "form_message_"+ID){i++}
			form = document.forms(i);
		}
		else
			form = document.forms["form_message_"+ID];

		form.appendChild(textArea);

		cExecuteForm ("$this.imap_functions.send_mail", form, handler_send_smime, ID);
	}
}

function send_message(ID, folder, folder_name){

	//limpa autosave_timer[ID]; havia conflito quando uma mensagem ia ser enviada e nesse exato momento o autosave
		//entrava em execucao (a aba de edicao da mensagem continuava aberta e a mensagem exibida era a de que a mensagem foi
		//salva na pasta Rascunhos e nao que tinha sido enviada, como deveria);
		if (preferences.auto_save_draft == 1)
	{
		if (openTab.autosave_timer[ID])
		{
			clearTimeout(openTab.autosave_timer[ID]);
		}
	}

	var isBlocked = document.getElementById('user_is_blocked_to_send_email').value;

	if (isBlocked == 1) {
		write_msg(document.getElementById('user_is_blocked_to_send_email_message').value);
		return;
	}

	if ( document.getElementById('viewsource_rt_checkbox_' + ID).checked == true )
		document.getElementById('viewsource_rt_checkbox_' + ID).click();

	var save_link = Element("save_message_options_"+ID);
	save_link.onclick = '';
	save_link.className = 'message_options_inactive';

	ID_tmp = ID;

	var handler_send_message = function(data){
		send_message_return(data, this.ID_tmp); // this is a hack to escape quotation form connector bug
	};

	var mail_as_plain = document.getElementById( 'textplain_rt_checkbox_' + ID );
	mail_as_plain = ( mail_as_plain ) ? mail_as_plain.checked : false;

	var textArea = document.createElement("TEXTAREA");
	textArea.style.display='none';
	textArea.name = "body";
	body = document.getElementById("body_"+ID);
	textArea.value = ( ( mail_as_plain ) ? (is_ie ? body.contentWindow.document.body.innerHTML : body.previousSibling.value) : ( '<body>\r\n' + body.contentWindow.document.body.innerHTML + '\r\n</body>' ) );
	var input_folder = document.createElement("INPUT");
	input_folder.style.display='none';
	input_folder.name = "folder";
	input_folder.value = folder;
	var msg_id = document.createElement("INPUT");
	msg_id.style.display='none';
	msg_id.name = "msg_id";
	msg_id.value = openTab.imapUid[ID];

	if (is_ie){
		var i = 0;
		while (document.forms(i).name != "form_message_"+ID){i++}
		form = document.forms(i);
	}
	else
		form = document.forms["form_message_"+ID];

		// Evita que e-mails assinados sejam enviados quando o usuário tenta enviar um e-mail
		// não assinado (desmarcou a opção) após tentar enviar um e-mail assinado que não passou
		// no teste de validação.
		var checkSign = document.getElementById('return_digital_'+ID);
		if (checkSign && !checkSign.checked){
			var smime = Element('smime');
			if (smime)
			{
				var parent = smime.parentNode;
				parent.removeChild(smime);
			}
		 }

	form.appendChild(textArea);
	form.appendChild(input_folder);
	form.appendChild(msg_id);

	var mail_type = document.createElement('input');
	mail_type.setAttribute('type', 'hidden');
	mail_type.name = 'type';
	mail_type.value = ( mail_as_plain ) ? 'plain' : 'html';
	form.appendChild(mail_type); 

	var _subject = trim(Element("subject_"+ID).value);
	if((_subject.length == 0) && !confirm(get_lang("Send this message without a subject?"))) {
		Element("subject_"+ID).focus();
		return;
	}

	if (expresso_offline) {
		stringEmail = Element("to_"+ID).value;
		stringEmail += Element("cco_"+ID).value =='' ? "":", "+Element("cco_"+ID).value;
		stringEmail += Element("cc_"+ID).value =='' ? "":", "+Element("cc_"+ID).value;
		var invalidEmail = searchEmail(stringEmail);
		if(Element("to_"+ID).value=="" && Element("cco_"+ID).value=="" && Element("cc_"+ID).value=="") {
			write_msg(get_lang("message without receiver."));
			return;
		}else if(invalidEmail[0] == true){
			write_msg("Os endereços de destinatário a seguir estão incorretos: "+invalidEmail[1]);
			return;
		}

		sucess = expresso_local_messages.send_to_queue(form);
		var data_return = new Array();
		data_return.success = sucess;
		handler_send_message(data_return,ID);
	}
	else
		cExecuteForm("$this.imap_functions.send_mail", form, handler_send_message, ID);
}
function change_tr_properties(tr_element, newUid, newSubject){
	message_id=tr_element.id;
	var td_who = document.getElementById('td_who_'+message_id);
	if (typeof(newSubject) != 'undefined')
		td_who.nextSibling.innerHTML = newSubject;
	tr_element.id = newUid;

	var openNewMessage = function () {
		cExecute("$this.imap_functions.get_info_msg&msg_number="+newUid
				+"&msg_folder="+url_encode(current_folder),show_msg);
	};
	for (var i=2; i < 10; i++){
		if (typeof(tr_element.childNodes[i].id) != "undefined")
			tr_element.childNodes[i].id = tr_element.childNodes[i].id.replace(message_id,newUid);
		tr_element.childNodes[i].onclick = openNewMessage;
	}
}

function return_save(data,border_id,folder_name,folder_id,message_id)
{
	Element("send_button_"+border_id).style.visibility="visible";
	var handler_delete_msg = function(data){ refresh(preferences.alert_new_msg); };
	
	if (data.save_draft != true || !data)
	{
		RichTextEditor.saveFlag = 0;
		if (! data.save_draft)
			if(data == 'Post-Content-Length')
				write_msg(get_lang('The size of this message has exceeded  the limit (%1B).', preferences.max_attachment_size ? preferences.max_attachment_size : Element('upload_max_filesize').value));
			else
				write_msg(get_lang('ERROR saving your message.'));
		else
		{
			if (data.save_draft.match(/^(.*)TRYCREATE(.*)$/))
			{
				connector.loadScript('TreeS');
				alert(get_lang('There is not %1 folder, Expresso is creating it for you... Please, repeat your request later.',draftsfolder));
				connector.loadScript('TreeShow');
				ttree.FOLDER = 'root';
				ttreeBox.new_past(draftsfolder);
				setTimeout('save_msg('+border_id+')',3000);
			}
			else
				write_msg('*');//data.save_draft);
		}
	}
	else
	{
		openTab.imapUid[border_id] = data.msg_no;
		openTab.imapBox[border_id] = data.folder_id;

		var newTitle = document.getElementById('subject_'+border_id).value;
		if (newTitle == '')
			newTitle = get_lang("No subject");
		set_border_caption('border_id_'+border_id, newTitle);

		// Replace the embedded images for new uids
		var mainField = document.getElementById('body_'+border_id).contentWindow;
		var content_body =  mainField.document.getElementsByTagName('body').item(0).innerHTML;
		var body_images = content_body.match(/msg_num=\d*/g);
		var images_part = content_body.match(/msg_part=\d*/g);
		if (body_images)
		{
			for (var i=0; i<body_images.length; i++){
				content_body = 	content_body.replace(body_images[i],"msg_num="+openTab.imapUid[border_id]);
			}
			var allImgs = new Array (images_part.length);
				var j=-1;
				for (var i in images_part){

					if (is_ie)
						if (i == 0)
							var image_number = parseInt(images_part[i].substr(9));
						else
							image_number = "null";
					else
						var image_number = parseInt(images_part[i].substr(9));

				if (! isNaN(image_number))
					{
						if (! allImgs[image_number])
						{
							allImgs[image_number] = true;
							j--;
						}
					content_body = content_body.replace(images_part[i],'msg_part='+j);
					}
				}
			content_body = content_body.replace(/msg_part=-/g,'msg_part=');

			mainField.document.getElementsByTagName('body').item(0).innerHTML = content_body;
		}

		//Replace all files to new files
		var divFiles = Element("divFiles_"+border_id);
		elFiles = divFiles.getElementsByTagName("input");
		var countCheck =0;
		for (var i=0; i<elFiles.length; i++) {
			if(elFiles[i].value !=""){
				if (elFiles[i].type == "checkbox") {
					var tmpData = connector.unserialize(decodeURIComponent(elFiles[i].value));
					tmpData[0] = data.folder_id;
					tmpData[1] = data.msg_no;
					elFiles[i].value = encodeURIComponent(connector.serialize(tmpData)); 
					countCheck++;
				}
				else {
					elFiles[i].value ="";
					parantNodeFile = elFiles[i].parentNode.parentNode;
					parantNodeFile.removeChild(elFiles[i].parentNode);
					i--;
				}

			}
		};

		var attach_files = connector.unserialize(data.files);
		if (attach_files != null) {
			openTab.countFile[border_id] = attach_files.length;
			att_index = countCheck;
		for (att_index; att_index < attach_files.length; att_index++){

			var link_attachment = document.createElement("A");
			var fileName = attach_files[att_index].substr(0,attach_files[att_index].indexOf('_SIZE_'));
			var fileSize = parseInt(attach_files[att_index].substr(attach_files[att_index].indexOf('_SIZE_')+6))/1024
			link_attachment.innerHTML = fileName + " ("+borkb((parseInt(fileSize)*1024))+")";

			var href = "'"+data.folder_id+"','"+data.msg_no+"','"+(att_index+2)+"'";

			link_attachment.setAttribute("href", "javascript:export_attachments("+href+")");
			$(link_attachment).data([ data.folder_id, data.msg_no, fileName, (att_index + 2), 'base64' ]);
			
			var sdata = escape(connector.serialize($(link_attachment).data()));
			var check_attachment = document.createElement("INPUT");
			check_attachment.type = 'CHECKBOX';
			check_attachment.name = 'forwarding_attachments[]';
			check_attachment.value = sdata;

                        if (!divFiles.childNodes[0])
                        {
                            divFiles.appendChild(document.createElement("BR"));
                        }
                        else
                            {
                                divFiles.insertBefore(document.createElement("BR"),divFiles.childNodes[0]);
                            }

                        divFiles.insertBefore(link_attachment,divFiles.childNodes[0]);
                        divFiles.insertBefore(check_attachment,divFiles.childNodes[0]);

			check_attachment.checked = true;
		}
	}
		if (message_id)
		{
			cExecute ("$this.imap_functions.delete_msgs&folder="+openTab.imapBox[border_id]+"&msgs_number="+message_id,handler_delete_msg);
			if (openTab.imapBox[0] == "INBOX" + cyrus_delimiter + draftsfolder)
			{
				//Update mailbox
						var tr_msg = document.getElementById(message_id);
				change_tr_properties(tr_msg, data.msg_no, data.subject);
			}
		} else {
			refresh();
		}

		var save_link = Element("save_message_options_"+border_id);
		save_link.onclick = '';
		save_link.className = 'message_options_inactive';
		watch_changes_in_msg(border_id);
		write_msg(get_lang('Your message was save as draft in folder %1.', lang_folder(folder_name)));
                setTimeout( function(){ RichTextEditor.saveFlag = 1; }, 1000 );
	}
}

function save_msg(border_id,withImage){
	if (typeof(withImage) == 'undefined')
		withImage = false;

	var rt_checkbox = Element('viewsource_rt_checkbox_' + border_id);
	if (rt_checkbox == null)
		return false;
	if (rt_checkbox.checked == true)
		rt_checkbox.click();

	var sendButton = Element("send_button_"+border_id);
	if (sendButton)
		sendButton.style.visibility="hidden";

	if (openTab.imapBox[border_id] && openTab.type[border_id] < 6) //Gets the imap folder
		var folder_id = openTab.imapBox[border_id];
	else
		var folder_id = "INBOX" + cyrus_delimiter + draftsfolder;

	if (folder_id == 'INBOX') // and folder name from border
		var folder_name = get_lang(folder_id);
	else
		var folder_name = folder_id.substr(6);

	// hack to avoid form connector bug,  escapes quotation. Please see #179
	tmp_border_id=border_id;
	tmp_folder_name=folder_name;
	tmp_folder_id=folder_id;
	message_id = openTab.imapUid[border_id];
 	var handler_save_msg = function(data){ return_save(data,this.tmp_border_id,this.tmp_folder_name,this.tmp_folder_id,this.message_id); }

	var mail_as_plain = document.getElementById( 'textplain_rt_checkbox_' + border_id );
	mail_as_plain = ( mail_as_plain ) ? mail_as_plain.checked : false;

	var textArea = document.createElement("TEXTAREA");
	textArea.style.display='none';
	textArea.name = "body";
	body = document.getElementById("body_"+border_id);
	if (! body)
		return;
	textArea.value = ( ( mail_as_plain ) ? body.previousSibling.value : ( '<body>\r\n' + body.contentWindow.document.body.innerHTML + '\r\n</body>' ) );
	var input_folder = document.createElement("INPUT");
	input_folder.style.display='none';
	input_folder.name = "folder";
	input_folder.value = folder_id;
	var input_msgid = document.createElement("INPUT");
	input_msgid.style.display='none';
	input_msgid.name = "msg_id";
	input_msgid.value = message_id;
	var input_insertImg = document.createElement("INPUT");
	input_insertImg.style.display='none';
	input_insertImg.name = "insertImg";
	input_insertImg.value = withImage;

	if( is_ie )
	{
		var i = 0;
		while (document.forms(i).name != "form_message_"+border_id){i++}
		form = document.forms(i);
	} else {
		form = document.forms["form_message_"+border_id];
	}

	form.appendChild(textArea);
	form.appendChild(input_folder);
	form.appendChild(input_msgid);
	form.appendChild(input_insertImg);

	var mail_type = document.createElement('input');
	mail_type.name = 'type';
	mail_type.setAttribute('type', 'hidden');
	mail_type.value = ( mail_as_plain ) ? 'plain' : 'html';
	form.appendChild(mail_type);

	cExecuteForm ("$this.imap_functions.save_msg", form, handler_save_msg,border_id);
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


function save_as_msg(border_id, folder_id, folder_name){
	// hack to avoid form connector bug,  escapes quotation. Please see #179
	tmp_border_id=border_id;
	tmp_folder_name=folder_name;
	var handler_save_msg = function(data){ return_saveas(data,this.tmp_border_id,this.tmp_folder_name); }
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
	}
	else
		form = document.forms["form_message_"+border_id];
	form.appendChild(textArea);
	form.appendChild(input_folder);

	cExecuteForm ("$this.imap_functions.save_msg", form, handler_save_msg,border_id);
}


// Get checked messages
function set_messages_flag(flag, msgs_to_set){
	var handler_set_messages_flag = function (data){
		if(!verify_session(data))
			return;
		var msgs_to_set = data.msgs_to_set.split(",");

		if(!data.status) {
			write_msg(data.msg);
			Element('chk_box_select_all_messages').checked = false;
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
			if (preferences.use_cache == 'True')
			{
				if (current_folder == '')
					current_folder = 'INBOX';
				var setFlag = function(msgObj) {
					switch(data.flag){
						case "unseen":
							msgObj.Unseen = "U";
							break;
						case "seen":
							msgObj.Unseen = "";
							break;
						case "flagged":
							msgObj.Flagged = "F";
							break;
						case "unflagged":
							msgObj.Flagged = "";
							break;
					}
				}
				proxy_mensagens.get_msg(msgs_to_set[i],current_folder, false, setFlag);


			}
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
		Element('chk_box_select_all_messages').checked = false;
	}

	var folder = get_current_folder();
	if (msgs_to_set == 'get_selected_messages')
		var msgs_to_set = this.get_selected_messages();
	else
		folder = Element("input_folder_"+msgs_to_set+"_r").value;

	if (msgs_to_set)
		cExecute ("$this.imap_functions.set_messages_flag&folder="+folder+"&msgs_to_set="+msgs_to_set+"&flag="+flag, handler_set_messages_flag);
	else
		write_msg(get_lang('No selected message.'));
}

// By message number
function set_message_flag(msg_number, flag, func_after_flag_change){
	var msg_number_folder = Element("new_input_folder_"+msg_number+"_r"); //Mensagens respondidas/encaminhadas
	if(!msg_number_folder)
		var msg_number_folder = Element("input_folder_"+msg_number+"_r"); //Mensagens abertas
	
	var handler_set_messages_flag = function (data){
		if(!verify_session(data))
			return;
		if(!data.status) {
			write_msg(get_lang("this message cant be marked as normal"));
			return;
		}
		else if(func_after_flag_change) {
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
	cExecute ("$this.imap_functions.set_messages_flag&folder="+( msg_number_folder ?  msg_number_folder.value : get_current_folder() )+"&msgs_to_set="+msg_number+"&flag="+flag, handler_set_messages_flag);
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
	var div_toaddress_full  = Element("div_toaddress_full_"+border_ID);
	var div_ccaddress_full  = Element("div_ccaddress_full_"+border_ID);
	var div_ccoaddress_full = Element("div_ccoaddress_full_"+border_ID);
	var printListTO         = (div_toaddress_full && div_toaddress_full.style.display != 'none') || toaddress_array[border_ID].length == 1 ? true : false;
	var printListCC         = (div_ccaddress_full && div_ccaddress_full.style.display != 'none') || !div_ccaddress_full ? true : false;
	var printListCCO        = (div_ccoaddress_full && div_ccoaddress_full.style.display != 'none') || !div_ccoaddress_full ? true : false;
	var sender              = Element('sender_values_'+border_ID) ? Element('sender_values_'+border_ID).value : null;
	var from                = Element('from_values_'+border_ID) ? Element('from_values_'+border_ID).value : null;
	var to                  = Element('to_values_'+border_ID) ? Element('to_values_'+border_ID).value :null;
	var cco                 = Element('cco_values_'+border_ID) ? Element('cco_values_'+border_ID).value : null;
	var cc                  = Element('cc_values_'+border_ID) ? Element('cc_values_'+border_ID).value : null;
	var date                = Element('date_'+border_ID);
	var subject             = Element('subject_'+border_ID);
	var attachments         = Element('attachments_'+border_ID);
	var body                = Element('body_'+border_ID);
	
	if(!is_ie)
	{
		var link = location.href.replace(/\/expressoMail1_2\/(.*)/, "");
		var tab_tags = body.getElementsByTagName("IMG");
		for(var i = 0; i < tab_tags.length;i++)
		{
			var _img = document.createElement("IMG");

			_img.src = tab_tags[i].src;

			if( tab_tags[i].align )
			{
				_img.align = tab_tags[i].align;
			}

			if(tab_tags[i].src.toUpperCase().indexOf('/INC/SHOW_EMBEDDED_ATTACH.PHP?MSG_FOLDER=') > -1)
			{
				_img.src = link + '/expressoMail1_2'+tab_tags[i].src.substr(tab_tags[i].src.toUpperCase().indexOf('/INC/SHOW_EMBEDDED_ATTACH.PHP?MSG_FOLDER='));
			}
			tab_tags[i].parentNode.replaceChild(_img,tab_tags[i]);
		}
	}
	
	//needed to get the names of the attachments... only.
	if(attachments != null)
	{
		var a = attachments.childNodes;
		var attachs = "";
		var show_attachs = "";
		var ii = a.length >2?2:1;
		for(i=ii;i<a.length;i++)
		{
			if(a[i].tagName && a[i].tagName == "A")
			{
				attachs += a[i].innerHTML;
			}
		}
		show_attachs = "<tr><td width=7%><font size='2'>" + get_lang('Attachments: ')+ " </font></td><td><font size='2'>"+attachs+"</font></td></tr>";
	} else{
		show_attachs = "";
	}
	var current_path = window.location.href.substr(0,window.location.href.lastIndexOf("/"));
	var head = '<head><title></title><link href="'+current_path+'/templates/default/main.css" type="text/css" rel="stylesheet"></head>';
	
	var window_print = popup_create();
	while (1){
		try{
			window_print.document.write(head);
			var html ='<body>';
			html += "<h4>ExpressoLivre - ExpressoMail</h4><hr>";
			html += '<table><tbody>';
			if(sender)
				html += "<tr><td width=7% noWrap><font size='2'>" + get_lang('Sent by') + ": </font></td><td><font size='2'>"+sender+"</font></td></tr>";
			if(from)
				html += "<tr><td width=7%><font size='2'>" + get_lang('From') + ": </font></td><td><font size='2'>"+from+"</font></td></tr>";
			if(to) {
				if(!printListTO)
					to = 'Os destinatários não estão sendo exibidos para esta impressão';
				html += "<tr><td width=7%><font size='2'>" + get_lang('To') + ": </font></td><td><font size='2'>"+to+"</font></td></tr>";
			}
			if (cc) {
				if(!printListCC)
					cc = 'Os destinatários não estão sendo exibidos para esta impressão';
				html += "<tr><td width=7%><font size='2'>" + get_lang('Cc') + ": </font></td><td><font size='2'>"+cc+"</font></td></tr>";
			}
			if (cco) {
				if(!printListCCO)
					cco = 'Os destinatários não estão sendo exibidos para esta impressão';
				html += "<tr><td width=7%><font size='2'>" + get_lang('Cco') + ": </font></td><td><font size='2'>"+cco+"</font></td></tr>";
			}
			if(date)
				html += "<tr><td width=7%><font size='2'>" + get_lang('Date') + ": </font></td><td><font size='2'>"+date.innerHTML+"</font></td></tr>";
			html += "<tr><td width=7%><font size='2'>" + get_lang('Subject')+ ": </font></td><td><font size='2'>"+subject.innerHTML+"</font></td></tr>";
			html += show_attachs; //to show the names of the attachments
			html += "</tbody></table><hr>";
			window_print.document.write(html + body.innerHTML);
			if(!is_ie){
				var tab_tags = window_print.document.getElementsByTagName("IMG");
				for(var i = 0; i < tab_tags.length;i++)
				{
					var _img = document.createElement("IMG");

					_img.src = tab_tags[i].src;

					if( tab_tags[i].align )
					{
						_img.align = tab_tags[i].align;
					}

					tab_tags[i].parentNode.replaceChild(_img,tab_tags[i]);
				}
			}
			break;
		}
		catch(e)
		{
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
	msg_number = parseInt( msg_number );
	section = ( section == undefined || section == '*' )? '*' : section;
	proxy_mensagens.exportEml( 'exportAttachments', {
		'folder': folder, 'msg_number': msg_number, 'section': section
	} );
}

function export_all_selected_msgs(){
	
	var folders = get_selected_messages_by_folder();
	if ( folders === false ) {
		write_msg(get_lang('Error compressing messages (ZIP). Contact the administrator.'));
		return false;
	}
	
	if ( Object.keys(folders).length == 0 ) {
		write_msg(get_lang('No selected message.'));
		return false;
	}
	proxy_mensagens.exportEml( 'exportMessages', folders );
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

function iframe_download( url, data ) {
	
	// Init iframe
	var iframe_content = $('#iframe_content');
	if ( $(iframe_content).length === 0 ) {
		
		iframe_content = $('<div>')
			.attr( 'id', 'iframe_content' )
			.css( { 'display': 'none', 'width': '0px', 'height': '0px' } )
			.append( $('<iframe>').attr( 'name', 'iframe_target' ) )
			.appendTo( 'body' );
		
	}
	
	// Create a post form on iframe body
	var frm = $('<form>')
		.attr( 'method', 'POST' )
		.attr( 'action', url )
		.attr( 'target', 'iframe_target' );
	
	// Add post data
	for ( var key in data ) {
		$(frm).append(
			$('<input>')
				.attr( 'name', key )
				.attr( 'type', 'hidden' )
				.val( ( data[key] instanceof Object )? encodeURIComponent( JSON.stringify( data[key] ) ) : data[key] )
		);
	}
	
	// Submit and remove form
	$(frm).appendTo( iframe_content ).submit().remove();
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

	cExecuteForm('$this.imap_functions.import_msgs', document.form_import, handler);
}
function return_import_msgs(data, wfolders_tree){
	if(data && data.error){
		write_msg(data.error);
	}
	else{
		if(data == 'Post-Content-Length')
			write_msg(get_lang('The size of this message has exceeded  the limit (%1B).', preferences.max_attachment_size ? preferences.max_attachment_size : Element('upload_max_filesize').value));
		else {	/*
			* @author Rommel Cysne (rommel.cysne@serpro.gov.br)
			* @date 2009/05/15
			* Foi colocado um teste para verificar se a pasta selecionada, passada como parametro,
			* eh uma pasta local (qualquer uma)
			*/
			var er = /^local_/;
			if ( er.test(wfolders_tree._selected.id) )
			{
				archive_msgs('INBOX/Lixeira/tmpMoveToLocal',wfolders_tree._selected.id,data);
				cExecute('$this.imap_functions.delete_mailbox',function(){},'del_past=INBOX/Lixeira/tmpMoveToLocal');
			}
						 else{
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
				type    : "GET",
				url     : "/api/rest/vcalendar/import?event="+hash_vcalendar,
				success : function( data )
				{
					var _data = $.parseJSON( data );
					if( eval( _data.result ) ){
						write_msg(get_lang("The event was imported successfully."));
					} else {
						write_msg(get_lang("The event was not imported."));
					}
				}
		});
	}
}
function hack_sent_queue(data,rowid_message) {

	if (data.success != true) {
		queue_send_errors = true;
		expresso_local_messages.set_problem_on_sent(rowid_message,data);
	}
	else {
		expresso_local_messages.set_as_sent(rowid_message);
		if(document.getElementById('_action')) { //Não posso manter esse elemento, pois o connector irá criar outro com o mesmo id para a próxima mensagem.
			el =document.getElementById('_action');
			father = el.parentNode;
			father.removeChild(el);
		}
		send_mail_from_queue(false);
	}
}

function send_mail_from_queue(first_pass) {
	if(first_pass)
		modal('send_queue');
	var num_msgs = expresso_local_messages.get_num_msgs_to_send();
	if (num_msgs <= 0) {
		close_lightbox();
		return;
	}
	document.getElementById('text_send_queue').innerHTML = get_lang('Number of messages to send:')+' '+num_msgs;
	var handler_send_queue_message = function(data,rowid_message) {
		hack_sent_queue(data,this.ID_tmp);
	}
	var msg_to_send = expresso_local_messages.get_form_msg_to_send();
	if(!is_ie)
		ID_tmp = msg_to_send.rowid.value;
	else {//I.E kills me of shame...
		for (var i=0;i<msg_to_send.length;i++) {
			if(msg_to_send.elements[i].name=='rowid') {
				ID_tmp = msg_to_send.elements[i].value;
				break;
			}
		}
	}
	expresso_local_messages.set_as_sent(ID_tmp);
	cExecuteForm("$this.imap_functions.send_mail", msg_to_send, handler_send_queue_message,"queue_"+ID_tmp);
	send_mail_from_queue(false);
}

function check_mail_in_queue() {
	var num_msgs = expresso_local_messages.get_num_msgs_to_send();
	if(num_msgs>0) {
		control = confirm(get_lang('You have messages to send. Want you to send them now?'));
		if(control) {
			send_mail_from_queue(true);
		}
		return true;
	}
	else {
		return false;
	}
}

function force_check_queue() {
	if(!check_mail_in_queue()) {
		write_msg(get_lang("No messages to send"));
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
