/**
 * @author diogenes
 */

	function messages_proxy() {
		
	}
/**
 * Retorna os cabeçalhos das mensagens a serem desenhadas na caixa de email.
 */
	messages_proxy.prototype.messages_list = function(folder,msg_range_begin,emails_per_page,sort_box_type,search_box_type,sort_box_reverse,preview_msg_subject,preview_msg_tip,call_back) {
		if(this.is_local_folder(folder)) {
			var real_folder = folder.substr(6);		
			var msgs = expresso_local_messages.get_local_range_msgs(real_folder,msg_range_begin,preferences.max_email_per_page,sort_box_type,sort_box_reverse,search_box_type,preferences.preview_msg_subject,preferences.preview_msg_tip);
			eval('call_back(msgs)');
			draw_tree_local_folders();
		}else {
			cExecute("$this.imap_functions.get_range_msgs2&folder="+encodeURIComponent(folder)+"&msg_range_begin="+msg_range_begin+"&msg_range_end="+emails_per_page+"&sort_box_type="+sort_box_type+ "&search_box_type="+ search_box_type +"&sort_box_reverse="+sort_box_reverse+"", call_back);
		}
	}

	messages_proxy.prototype.get_msg = function( msg_number, msg_folder, callback )
	{
		Ajax( '$this.imap_functions.get_info_msg', { 'msg_number': msg_number, 'msg_folder': msg_folder }, callback );
	}
	
	messages_proxy.prototype.delete_msgs = function(folder, msgs_number, border_ID) {

		if (folder == 'null')
			folder = get_current_folder();
		if (msgs_number == 'selected'){
			if (openTab.type[currentTab] > 1){
				msgs_number = currentTab.substring(0,currentTab.length-2,currentTab);
		    }else{
				msgs_number = get_selected_messages();
		    }
		}
		if (!this.is_local_folder(folder)) 
			delete_msgs(folder, msgs_number, border_ID);
		else {
			//draw_tree_local_folders();
			var msg_to_delete = Element(msgs_number);
			if (parseInt(preferences.delete_and_show_previous_message) && msg_to_delete) {
				if (msg_to_delete.previousSibling){
					var previous_msg = msg_to_delete.previousSibling.id;
					expresso_local_messages.delete_msgs(msgs_number, border_ID);
					show_msg(expresso_local_messages.get_local_mail(previous_msg));
				} 
				else{
					expresso_local_messages.delete_msgs(msgs_number, border_ID);
					delete_border(currentTab,'false');
				}
			}
			else{
				expresso_local_messages.delete_msgs(msgs_number, border_ID);
				delete_border(currentTab,'false');
			}
		}
	}
	
	messages_proxy.prototype.link_anexo = function (info_msg,numero_ordem_anexo) {

		if(info_msg.local_message==true) {
			return "javascript:download_local_attachment('"+
					expresso_local_messages.get_url_anexo(info_msg.msg_number,info_msg.attachments[numero_ordem_anexo].pid)+
					"')";
		}
		else {
			return "javascript:download_attachments('"+info_msg.msg_folder+"','"+info_msg.msg_number+"',"+numero_ordem_anexo+",'"+info_msg.attachments[numero_ordem_anexo].pid+"','"+info_msg.attachments[numero_ordem_anexo].encoding+"','"+info_msg.attachments[numero_ordem_anexo].name+"')";
		}
	}
	
	messages_proxy.prototype.proxy_set_messages_flag = function (flag,msg_number){
		if(this.is_local_folder(get_current_folder())) {
			expresso_local_messages.set_messages_flag(msg_number,flag);
		}
		else {
			set_messages_flag(flag,msg_number);
		}
	}
	
	messages_proxy.prototype.proxy_set_message_flag = function (msg_number,flag,func_after_flag_change){
		var msg_number_folder = Element("new_input_folder_"+msg_number+"_r"); //Mensagens respondidas/encaminhadas
		if(!msg_number_folder)
			var msg_number_folder = Element("input_folder_"+msg_number+"_r"); //Mensagens abertas
		var folder = msg_number_folder ?  msg_number_folder.value : get_current_folder();
		if(this.is_local_folder(folder)) {
			expresso_local_messages.set_message_flag(msg_number,flag, func_after_flag_change);
		}
		else {
			set_message_flag(msg_number,flag, func_after_flag_change);
		}
	}
	
	messages_proxy.prototype.is_local_folder = function(folder) {
		if(typeof(folder) == "undefined" || folder.indexOf("local_")==-1)
			return false;
		return true;
	}
	

	messages_proxy.prototype.proxy_rename_folder = function(){

		if (ttree.FOLDER == 'local_Inbox') {
			alert(get_lang("It's not possible rename the folder: ") + lang_folder(ttree.FOLDER.substr(6)) + '.');
			return false;
		}
		if(ttree.FOLDER == 'local_root') {
				alert(get_lang("It's not possible rename this folder!"));
				return false;
		}
		if (this.is_local_folder(ttree.FOLDER)) {
			folder = prompt(get_lang("Enter a name for the box"), "");
		        if(folder.match(/[\/\\\!\@\#\$\%\&\*\(\)]/gi)){
			    alert(get_lang("It's not possible rename this folder. try other folder name"));
			    return false;
			}
			if(trim(folder) == "" || trim(folder) == null){
				alert(get_lang("you have to enter the name of the new folder"));
				return false;
			}
			var temp = expresso_local_messages.rename_folder(folder, ttree.FOLDER.substr(6));
			if (!temp) 
				alert(get_lang("cannot rename folder. try other folder name"));
			ttreeBox.update_folder();
		}
		else {
			ttreeBox.validate("rename");
		}
		
	}

	messages_proxy.prototype.proxy_create_folder = function() {
		if (folders.length == preferences.imap_max_folders){
			alert(get_lang("Limit reached folders"));
				return false;
		}
		if (this.is_local_folder(ttree.FOLDER)) {
			folder = prompt(get_lang('Enter the name of the new folder:'), "");

                        if(folder == null)
                            return;


			if(trim(folder) == ""){
				alert(get_lang("you have to enter the name of the new folder"));
				return false;
			}
			if(folder.match(/[\/\\\!\@\#\$\%\&\*\(\)]/gi)){
			    alert(get_lang("cannot create folder. try other folder name"));
			    return false;
			}
			if(ttree.FOLDER=="local_root")
				var temp = expresso_local_messages.create_folder(folder);
			else
				var temp = expresso_local_messages.create_folder(ttree.FOLDER.substr(6)+"/"+folder);
			if (!temp) 
				alert(get_lang("cannot create folder. try other folder name"));
			ttreeBox.update_folder(true);
		}
		else			
			if(ttree.FOLDER == "INBOX")
				alert(get_lang("It's not possible create inside: ") + lang_folder(ttree.FOLDER)+".");
			else if (!this.is_local_folder(ttree.FOLDER))
				ttreeBox.validate("newpast");
			else 
				alert(get_lang("It's not possible create inside: ") + lang_folder(ttree.FOLDER.substr(6))+".");
	}
	
	messages_proxy.prototype.proxy_remove_folder = function() {
		if (this.is_local_folder(ttree.FOLDER)) {
			if(ttree.FOLDER == 'local_root') {
				alert(get_lang("Select a folder!"));
				return false;
			}
			if (ttree.FOLDER == 'local_Inbox' || (preferences.auto_create_local == '1' && (ttree.FOLDER == 'local_Sent' || ttree.FOLDER == 'local_Drafts' || ttree.FOLDER == 'local_Trash'))) {
				alert(get_lang("It's not possible delete the folder: ")  + lang_folder(ttree.FOLDER.substr(6)) + '.');
				return false;
			}
			if(ttree.FOLDER.indexOf("/")!="-1") {
				final_pos = ttree.FOLDER.lastIndexOf("/");
				new_caption = ttree.FOLDER.substr(final_pos+1);
			}
			else {
				new_caption = ttree.FOLDER.substr(6);
			}
			var string_confirm = get_lang("Do you wish to exclude the folder ") + new_caption + "?";

			if (confirm(string_confirm)) {
				var flag = expresso_local_messages.remove_folder(ttree.FOLDER.substr(6));
				if (flag) {
					write_msg(get_lang("The folder %1 was successfully removed", new_caption));
					draw_tree_local_folders();
					ttreeBox.update_folder(true);
				}
				else 
					alert(get_lang("Delete your sub-folders first"));
				
			}
		}
		else
			ttreeBox.del();
	}

	messages_proxy.prototype.proxy_move_messages = function (folder, msgs_number, border_ID, new_folder, new_folder_name) {
		if (! folder || folder == 'null')
			folder = Element("input_folder_"+msgs_number+"_r") ? Element("input_folder_"+msgs_number+"_r").value : (openTab.imapBox[currentTab] ? openTab.imapBox[currentTab]:get_current_folder());
		if ((this.is_local_folder(folder)) && (this.is_local_folder(new_folder))) { //Move entre pastas não locais...
			if (folder == new_folder){
				write_msg(get_lang('The origin folder and the destination folder are the same.'));
				return;
			}
			if(msgs_number=='selected'){
				if (openTab.type[currentTab] > 1){
					msgs_number = currentTab.substring(0,currentTab.length-2,currentTab);
			    }else{
					msgs_number = get_selected_messages();
			    }
			}
			if (new_folder == 'local_root')
				alert(get_lang("Select a folder!"));
			if (parseInt(msgs_number) > 0 || msgs_number.length > 0) {
				expresso_local_messages.move_messages(new_folder.substr(6), msgs_number);
				this.aux_interface_remove_mails(msgs_number, new_folder_name, border_ID);
				draw_tree_local_folders();
			}
			else 
				write_msg(get_lang('No selected message.'));
		}
		else 
			if ((!this.is_local_folder(folder)) && (!this.is_local_folder(new_folder))) { //Move entre pastas locais...
				move_msgs(folder, msgs_number, border_ID, new_folder, new_folder_name);
			}
			else if ((!this.is_local_folder(folder)) && (this.is_local_folder(new_folder))) {
				if(msgs_number=='selected')
					archive_msgs(folder,new_folder);
				else
					archive_msgs(folder,new_folder,msgs_number);
				draw_tree_local_folders();
			}
			else {
                //Por Bruno Costa (bruno.vieira-costa@serpro.gov.br) permite o desarquivamento de menssagens chamando a função unarchive_msgs quando uma msg é movida de uma pasta local para uma pasta remota.

				expresso_local_messages.unarchive_msgs(folder,new_folder,msgs_number);
                //write_msg(get_lang("you can't move mails from local to server folders"));
			}
		
		
	}
	
	messages_proxy.prototype.proxy_move_search_messages = function(border_id, new_folder, new_folder_name) {
		
		
		/*
		
		
		if ((this.is_local_folder(folder)) && (this.is_local_folder(new_folder))) { //Move entre pastas não locais...
			if (folder == new_folder){
				write_msg(get_lang('The origin folder and the destination folder are the same.'));
				return;
			}
			if(msgs_number=='selected')
				msgs_number = get_selected_messages();
			if (new_folder == 'local_root')
				alert(get_lang("Select a folder!"));
			if (parseInt(msgs_number) > 0 || msgs_number.length > 0) {
				expresso_local_messages.move_messages(new_folder.substr(6), msgs_number);
				this.aux_interface_remove_mails(msgs_number, new_folder_name, border_ID);
			}
			else 
				write_msg(get_lang('No selected message.'));
		}
		else 
			if ((!this.is_local_folder(folder)) && (!this.is_local_folder(new_folder))) { //Move entre pastas locais...
				move_msgs(folder, msgs_number, border_ID, new_folder, new_folder_name);
			}
			else if ((!this.is_local_folder(folder)) && (this.is_local_folder(new_folder))) {
				archive_msgs(folder,new_folder);
			}
			else {
				write_msg(get_lang("you can't move mails from local to server folders"));
			}*/
	}
	
	messages_proxy.prototype.aux_interface_remove_mails = function(msgs_number,new_folder_name,border_ID,previous_msg) {
		Element('chk_box_select_all_messages').checked = false;
		mail_msg = Element("tbody_box");
		msgs_number = msgs_number.split(",");
		var msg_to_delete;
		for (var i=0; i<msgs_number.length; i++){
			msg_to_delete = Element(msgs_number[i]);
			if (msg_to_delete){
				if ( (msg_to_delete.style.backgroundColor != '') && (preferences.use_shortcuts == '1') ){
					shortcutExpresso.selectMsg( false, 'down' );
				}
				mail_msg.removeChild(msg_to_delete);
			}
		}
		if (msgs_number.length == 1)
			write_msg(get_lang("The message was moved to folder ") + new_folder_name);
		else
			write_msg(get_lang("The messages were moved to folder ") + new_folder_name);

		if (border_ID != '' && border_ID != 'null'){
				delete_border(border_ID,'false');
		}
		if(folder == get_current_folder())
			Element('tot_m').innerHTML = parseInt(Element('tot_m').innerHTML) - msgs_number.length;			
		refresh();		
		
	}

	messages_proxy.prototype.export_all_messages = function( folder )
	{
		if ( !folder ) folder = get_current_folder();
		export_all_selected_msgs();
	}

	messages_proxy.prototype.proxy_export_all_msg = function()
	{
		// Usuario não selecionou uma pasta local e esta no começo dos nós
		if ( ttree.FOLDER == "local_root" ) return false;
		ttreeBox.export_all_msg();
	}

	var proxy_mensagens = new messages_proxy();
