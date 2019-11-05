 var openTab = {
	'type' : [], // 8 types of tabs, further info. see expressolivre.org/dev/wiki/mail/Documentacao
	'content_id' : [],
	'imapUid' : [], // Stores the imap email number of current tab
	'countFile' : [0,0,0,0,0,0,0,0,0,0], // Stores the number of files attached in current tab
	'imapBox' : [], // Stores the folder name
	'toPreserve' : [], // Check if the message should be removed from draft after send
	'autosave_timer' : [] // The timeout timer for autosave function
};
var tabTypes = {
	'new':4,
	'forward':6,
	'reply_with_history':7,
	'reply_to_all_with_history':8,
	'reply_without_history':9,
	'reply_to_all_without_history':10,
	'edit':5
	}
var currentTab,numBox = 0; // Open Tab and num of mailboxes opened at context
// Objeto Map, talvez o ideal fosse adicionar este objeto a Api do egroupware, e carrega-lo
// aqui no expressoMail.
function Map()
{
	this.keys = new Array();
	this.values = new Array();
}

Map.prototype.add = function(key, value)
{
	this.keys.push(key);
	this.values.push(value);
}

Map.prototype.get = function(key)
{
	result = new Array();
	for (i = 0; i < this.keys.length; i++)
	{
		if (this.keys[i] == key)
		{
			result.push(this.values[i]);
		}
	}

	if (result.length == 0)
	{
		return null;
	}

	return result;
}

var translatedFolders = new Map();

function draw_tree_folders(folders){

	// Check if the tree folders alredy exist.
	translatedFolders = new Map();

	if (Element('dftree_tree_folders')){

		var update_tree_folders = function (data) {
			build_quota(data);
			var unseen_in_mailbox = 0;
			var unseen_in_shared_folders = 0;
			for (var i = 0; i < data.length; i++) {
				if (data[i].folder_unseen > 0) {
					unseen_in_mailbox = parseInt(unseen_in_mailbox + data[i].folder_unseen);
					if (data[i].folder_id.indexOf('INBOX') !== 0)
						unseen_in_shared_folders = parseInt(unseen_in_shared_folders + data[i].folder_unseen);
				}

				var folder_unseen = Element('dftree_' + data[i].folder_id + '_unseen');

				if ((folder_unseen) && (data[i].folder_unseen > 0)) {
					folder_unseen.innerHTML = data[i].folder_unseen;
				}
				else if (data[i].folder_unseen > 0) {
					tree_folders.getNodeById(data[i].folder_id).alter({ caption: lang_folder(data[i].folder_name) + '<font style=color:red>&nbsp(</font><span id="dftree_' + data[i].folder_id + '_unseen" style=color:red>' + data[i].folder_unseen + '</span><font style=color:red>)</font>' });
					tree_folders.getNodeById(data[i].folder_id)._refresh();
				}
				else if (data[i].folder_unseen <= 0) {
					tree_folders.getNodeById(data[i].folder_id).alter({ caption: lang_folder(data[i].folder_name) });
					tree_folders.getNodeById(data[i].folder_id)._refresh();
				}

				if (data[i].folder_id == current_folder) {
					var old_new_m = isNaN(parseInt(Element('new_m').innerHTML)) ? parseInt(Element('new_m').firstChild.innerHTML) : parseInt(Element('new_m').innerHTML);
					Element('new_m').innerHTML = data[i].folder_unseen ? '<font color="RED">' + data[i].folder_unseen + '</font>' : 0;
					draw_paging(Element('tot_m').innerHTML);
					redim_borders();
				}
			}

			var display_unseen_in_mailbox = tree_folders.getNodeById('root');
			display_unseen_in_mailbox.alter({ caption: get_lang("My Folders") });
			display_unseen_in_mailbox._refresh();

			var display_unseen_in_shared_folders = tree_folders.getNodeById('user');
			if (display_unseen_in_shared_folders) {
				if (unseen_in_shared_folders)
					display_unseen_in_shared_folders.alter({ caption: '<font style=color:red>[</font><span id="dftree_user_unseen" style="color:red">' + unseen_in_shared_folders + '</span><font style=color:red>]</font> ' + get_lang("Shared folders") });
				else
					display_unseen_in_shared_folders.alter({ caption: get_lang("Shared folders") });
				display_unseen_in_shared_folders._refresh();
			}
		}
		
		Ajax( '$this.imap_functions.get_folders_list', { 'folder': current_folder }, update_tree_folders );

		return;

	} else {

		tree_folders = new dFTree({ name: 'tree_folders' });

		var n_root = new dNode({ id: 'root', caption: get_lang("My Folders") });
		tree_folders.add(n_root, 'anything'); //Places the root; second argument can be anything.

		var unseen_in_mailbox = 0;
		var unseen_in_shared_folders = 0;
		for (var i = 0; i < folders.length; i++) {
			if (folders[i].folder_unseen > 0) {
				unseen_in_mailbox = parseInt(unseen_in_mailbox + folders[i].folder_unseen);
				if (folders[i].folder_id.indexOf('INBOX') !== 0)
					unseen_in_shared_folders = parseInt(unseen_in_shared_folders + folders[i].folder_unseen);

				var nn = new dNode({ id: folders[i].folder_id, caption: lang_folder(folders[i].folder_name) + '<font style=color:red>&nbsp(</font><span id="dftree_' + folders[i].folder_id + '_unseen" style=color:red>' + folders[i].folder_unseen + '</span><font style=color:red>)</font>', onClick: "change_folder('" + folders[i].folder_id + "','" + folders[i].folder_name + "')", plusSign: folders[i].folder_hasChildren });

				if (folders[i].folder_name.toLowerCase() == 'inbox')
					Element('new_m').innerHTML = '<font style="color:red">' + folders[i].folder_unseen + '</font>';
			}
			else
				var nn = new dNode({ id: folders[i].folder_id, caption: lang_folder(folders[i].folder_name), onClick: "change_folder('" + folders[i].folder_id + "','" + folders[i].folder_name + "')", plusSign: folders[i].folder_hasChildren });

			if (folders[i].folder_parent == '')
				folders[i].folder_parent = 'root';
			else if (folders[i].folder_parent == 'user') {

				if (!tree_folders.getNodeById('user')) {
					tmpFolderId = folders[i].folder_id.split(cyrus_delimiter).pop();
					if (tmpFolderId != folders[i].folder_name) {
						translatedFolders.add(tmpFolderId, folders[i].folder_name);
					}
					var n_root_shared_folders = new dNode({ id: 'user', caption: get_lang("Shared folders"), plusSign: true });
					tree_folders.add(n_root_shared_folders, 'root');
				}
			}
			if (folders[i].folder_parent != 'root') {
				var node_parent = tree_folders.getNodeById(folders[i].folder_parent);
				node_parent.plusSign = true;
				tree_folders.alter(node_parent);
			}
			tree_folders.add(nn, folders[i].folder_parent);
		}

		tree_folders.draw(Element('content_folders'));

		n_root.changeState();

		tree_folders.getNodeById('INBOX')._select();

		var trash_span=document.getElementById('lINBOX/'+trashfolder+'tree_folders');
		var draft_span=document.getElementById('lINBOX/'+draftsfolder+'tree_folders');
		var sent_span=document.getElementById('l'+this.preferences.save_in_folder+'tree_folders');
		var sent_span_default=document.getElementById('lINBOX/'+sentfolder+'tree_folders');
		var spam_span=document.getElementById('lINBOX/'+spamfolder+'tree_folders');

		if (trash_span){
			trash_span.style.backgroundImage = "url(../phpgwapi/templates/" + template + "/images/foldertree_trash.png)";
		}

		if (draft_span){
			draft_span.style.backgroundImage = "url(../phpgwapi/templates/" + template + "/images/foldertree_draft.png)"
		}

		if (sent_span){
			sent_span.style.backgroundImage = "url(../phpgwapi/templates/" + template + "/images/foldertree_sent.png)";
		}
		
		if (spam_span){
			spam_span.style.backgroundImage = "url(../phpgwapi/templates/" + template + "/images/foldertree_spam.png)";
		}

		if (sent_span_default){
			sent_span_default.style.backgroundImage = "url(../phpgwapi/templates/" + template + "/images/foldertree_sent.png)";
		}

		draw_paging(Element('tot_m').innerHTML);
		if(document.getElementById("nINBOX/"+trashfolder+"tree_folders"))
		{
			var trash = document.createElement("SPAN");
			trash.id = 'empty_trash';
			trash.className = 'clean_folder';
			trash.style.cursor = 'pointer';
			trash.onclick = function () { clean_folder( 'trash' ); };
			trash.innerHTML = "["+get_lang("Clean")+"]";
			trash.title=get_lang("Empty trash");
			trash.onmouseover = function() {trash.style.color="red";};
			trash.onmouseout= function() {trash.style.color="#666666";};
			document.getElementById("nINBOX/"+trashfolder+"tree_folders").appendChild(trash);
		}
		if(document.getElementById("nINBOX/"+spamfolder+"tree_folders"))
		{
			var spam = document.createElement("SPAN");
			spam.id = 'empty_spam';
			spam.className = 'clean_folder';
			spam.style.cursor = 'pointer';
			spam.style.padding = '0 0 0 6px';
			spam.onclick = function () { clean_folder( 'spam' ); };
			spam.innerHTML = "["+get_lang("Clean")+"]";
			spam.title=get_lang("Empty Spam Folder");
			spam.onmouseover = function() {spam.style.color="red";};
			spam.onmouseout= function() {spam.style.color="#666666";};
			document.getElementById("nINBOX/"+spamfolder+"tree_folders").appendChild(spam);
		}

		var display_unseen_in_mailbox = tree_folders.getNodeById( 'root' );
		display_unseen_in_mailbox.alter({caption:get_lang("My Folders")});
		display_unseen_in_mailbox._refresh();

		var display_unseen_in_shared_folders = tree_folders.getNodeById( 'user' );
		if ( display_unseen_in_shared_folders )
		{
			if ( unseen_in_shared_folders )
				display_unseen_in_shared_folders.alter({caption:'<font style=color:red>[</font><span id="dftree_user_unseen" style="color:red">' + unseen_in_shared_folders +'</span><font style=color:red>]</font> ' + get_lang("Shared folders")});
			else
				display_unseen_in_shared_folders.alter({caption:get_lang("Shared folders")});
			display_unseen_in_shared_folders._refresh();
		}
	}

	var folder_create = "";
	var nm1 = "";
	if (tree_folders._folderPr.length > 0) {
		folder_create = tree_folders._folderPr.join(';');
	}
	if (folder_create != "") {
		if (confirm(get_lang("There are folders with invalid format. If you want to fix now, click on button OK."))) {
			Ajax( "$this.imap_functions.create_extra_mailbox", { 'nw_folders' : folder_create }, function(data){
				if (data) {
					write_msg(get_lang('The folders were fixed with success.'));
					setTimeout("connector.loadScript('TreeShow');ttreeBox.update_folder();", 500);
				}
			});
		} else {
			write_msg(get_lang('Warning: The folders with invalid format will be unavailable.'));
		}
	}
}

function draw_tree_local_folders() {
	/**
	 * Pastas locais
	 */
	if(preferences.use_local_messages==1 || expresso_offline) {
		var local_folders = expresso_local_messages.list_local_folders();
		var has_changes = false;
		for (var i in local_folders) { //Coloca as pastas locais.

			var new_caption = local_folders[i][0];
			if(local_folders[i][0].indexOf("/")!="-1") {
				final_pos = local_folders[i][0].lastIndexOf("/");
				new_caption = local_folders[i][0].substr(final_pos+1);
			}

			var folder_unseen = Element('dftree_local_'+local_folders[i][0]+'_unseen');

			if ((folder_unseen) && (local_folders[i][1] > 0))
			{
				folder_unseen.innerHTML = local_folders[i][1];
				has_changes = true;
			}
			else if (local_folders[i][1] > 0)
			{
				tree_folders.getNodeById("local_"+local_folders[i][0]).alter({caption:lang_folder(new_caption) + '<font style=color:red>&nbsp(</font><span id="dftree_local_'+local_folders[i][0]+'_unseen" style=color:red>'+local_folders[i][1]+'</span><font style=color:red>)</font>'});
				tree_folders.getNodeById("local_"+local_folders[i][0])._refresh();
				has_changes = true;
			}
			else if (local_folders[i][1] <= 0)
			{
				tree_folders.getNodeById("local_"+local_folders[i][0]).alter({caption:lang_folder(new_caption)});
				tree_folders.getNodeById("local_"+local_folders[i][0])._refresh();
				has_changes = true;
			}

			if("local_"+local_folders[i][0] == get_current_folder()){
				var old_new_m = isNaN(parseInt(Element('new_m').innerHTML)) ? parseInt(Element('new_m').firstChild.innerHTML) : parseInt(Element('new_m').innerHTML);
				if(!isNaN(old_new_m) && old_new_m < local_folders[i][1]){
					Element('tot_m').innerHTML = parseInt(Element('tot_m').innerHTML) + (parseInt(local_folders[i][1])-old_new_m);
				}
				Element('new_m').innerHTML = local_folders[i][1] ? '<font color="RED">'+local_folders[i][1]+'</font>' : 0;
				draw_paging(Element('tot_m').innerHTML);
				has_changes = true;
			}
		}
		if(has_changes)
			tree_folders.getNodeById("local_root").open();

	}
}

function update_menu(data)
{
	if( data && data.migrate_execution )
	{	
	 	$("#divAppboxHeader").html("Expresso Mail - " + get_lang("Mailbox in migration"));

	 	var migrate_status = "";

	 	switch(data.migrate_status)
	 	{
	 		case '-1':
	 			migrate_status 	= get_lang("Contact the Administrator");
	 			data.migrate_queue 	= -1;
	 			break;

	 		case '0' :
	 			migrate_status = get_lang("Awaiting execution");
	 			break;
	 		case '1':
	 			migrate_status = get_lang("Running");
	 			break;
	 	}

 		$("#divAppbox").html(new EJS( {url: 'templates/default/mailBoxMigration.ejs'} ).render({ 'status' : migrate_status, 'queue' : data.migrate_queue }));
	}
	else
	{
		if ( data && data.imap_error )
		{
	 		$("#divAppboxHeader").html("Expresso Mail - " + get_lang("Service unavailable"));
	 		$("#divAppbox").html(new EJS( {url: 'templates/default/errorExpresso.ejs'} ).render());
		}
		else
		{
			draw_tree_folders( data );
			
			if( data )
			{
				build_quota(data);
				var f_unseen = Element('dftree_'+current_folder+'_unseen');
				if(f_unseen && f_unseen.innerHTML)
					Element('new_m').innerHTML = '<font face="Verdana" size="1" color="RED">'+f_unseen.innerHTML+'</font>';
				else
				{
					if( parseInt(Element('new_m').innerHTML) == 0 )
						Element('new_m').innerHTML = 0;
				}
				folders = data;
			}
		}
	}
}

// Action on change folders.
function change_folder(folder, folder_name){
	if (openTab.imapBox[0] != folder)
	{
		current_folder = folder;
		proxy_mensagens.messages_list(current_folder,1,preferences.max_email_per_page,sort_box_type,search_box_type,sort_box_reverse,preferences.preview_msg_subject,preferences.preview_msg_tip, function( data ) {
			if(!verify_session(data))
				return;
			alternate_border(0);
			Element("border_id_0").innerHTML = "&nbsp;" + lang_folder(folder_name) + '&nbsp;<font face="Verdana" size="1" color="#505050">[<span id="new_m">&nbsp;</span> / <span id="tot_m"></span>]</font>';
			//redim_borders();
			draw_box(data, folder, true);
			draw_paging(data.num_msgs);
			Element("tot_m").innerHTML = data.num_msgs;
			update_menu();
			return true;
		} );
	}
	else
		alternate_border(0);
}

function open_folder(folder, folder_name){
	if (current_folder!= folder) {
		current_folder = folder;
		Ajax( '$this.imap_functions.get_range_msgs2', {
			'folder'          : current_folder,
			'msg_range_begin' : '1',
			'msg_range_end'   : preferences.max_email_per_page,
			'sort_box_type'   : sort_box_type,
			'search_box_type' : search_box_type,
			'sort_box_reverse': sort_box_reverse
		}, function( data ) {
			if(!verify_session(data))
				return false;
			numBox++;
			create_border(folder_name,numBox.toString());
			draw_box(data, current_folder, false);
			alternate_border(numBox);
			return true;
		} );
	}
	else
		alternate_border(numBox);
	return true;
}

var lastPage = 1;
var numPages = 5;
var last_folder = 'INBOX';
function draw_paging(num_msgs){
	num_msgs = parseInt(num_msgs);
	total_pages = 1;

	if(last_folder != current_folder){
		lastPage = 1;
		current_page = 1;
		last_folder = current_folder;
  	}

	if(num_msgs > parseInt(preferences.max_email_per_page)) {
		total_pages = parseInt(num_msgs/preferences.max_email_per_page);
		if((num_msgs/preferences.max_email_per_page) > total_pages)
			total_pages++;
	}

	if(total_pages == 1) {
		if(span_paging = document.getElementById("span_paging")) {
			span_paging.parentNode.removeChild(span_paging);
		}
		return;
	}
  	span_paging = document.getElementById("span_paging");
	if(!span_paging){
		span_paging = document.createElement("DIV");
		span_paging.id = "span_paging";
		span_paging.className = "boxHeaderText";
		span_paging.align="right";
		document.getElementById("div_menu_c3").appendChild(span_paging);
	}
	span_paging.style.width="100%";
  	span_paging.innerHTML="";
  	msg_range_begin = 1;
	msg_range_end = preferences.max_email_per_page;
  	if(current_page != 1) {
	  	lnk_page = document.createElement("A");
		lnk_page.setAttribute("href", "javascript:current_page=1;kill_current_box(); draw_paging("+num_msgs+"); proxy_mensagens.messages_list(get_current_folder(),"+msg_range_begin+","+msg_range_end+",'"+sort_box_type+"','"+search_box_type+"',"+sort_box_reverse+","+preferences.preview_msg_subject+","+preferences.preview_msg_tip+",function handler(data){alternate_border(0); draw_box(data, get_current_folder());});");
  	}
  	else {
  	 	lnk_page = document.createElement("SPAN");
  	}
  	span_paging.appendChild(lnk_page);

  	lnk_page.innerHTML = "&lt;&lt;";
	lnk_page.title = get_lang("First");
  	span_paging.innerHTML += "&nbsp;";

  	if(current_page == lastPage + numPages)
  		lastPage = current_page - 1;
  	else if((lastPage != 1 && lastPage == current_page) || current_page == total_pages)
  		lastPage = current_page - (numPages - 1);
  	else if(current_page == 1)
  	 	lastPage = 1;

	if(lastPage < 1)
		lastPage = 1;
	else if(lastPage > 1 && (lastPage > (total_pages -(numPages - 1))))
		lastPage = total_pages -(numPages - 1);

	var	hasMarked = false;

  	for(i = lastPage; i <= total_pages; i++) {

  		if(current_page == i || (i == total_pages && !hasMarked)) {
  			lnk_page = document.createElement("SPAN");
  			span_paging.appendChild(lnk_page);
  			lnk_page.innerHTML = "&nbsp;<b>"+i+"</b>&nbsp;";
  			hasMarked = true;
  			continue;
  		}
  		else{
  			lnk_page = document.createElement("A");
  			span_paging.appendChild(lnk_page);
  			msg_range_begin = ((i*preferences.max_email_per_page)-(preferences.max_email_per_page-1));
			msg_range_end = (i*preferences.max_email_per_page);
			lnk_page.setAttribute("href", "javascript:current_page="+i+";kill_current_box(); draw_paging("+num_msgs+"); proxy_mensagens.messages_list(get_current_folder(),"+msg_range_begin+","+msg_range_end+",'"+sort_box_type+"','"+search_box_type+"',"+sort_box_reverse+","+preferences.preview_msg_subject+","+preferences.preview_msg_tip+",function handler(data){alternate_border(0); draw_box(data, get_current_folder());});");
  		}
  		lnk_page.innerHTML = "&nbsp;...&nbsp;";
  		if(i == (lastPage + numPages))
  				break;
  		else if(lastPage == 1 || i != lastPage)
  			lnk_page.innerHTML = "&nbsp;"+i+"&nbsp;";
  		span_paging.innerHTML += "&nbsp;";
  	}

 	if(current_page != total_pages) {
  		lnk_page = document.createElement("A");
  		msg_range_begin = ((total_pages*preferences.max_email_per_page)-(preferences.max_email_per_page-1));
		msg_range_end = (total_pages*preferences.max_email_per_page);
		lnk_page.setAttribute("href", "javascript:current_page="+total_pages+";kill_current_box(); draw_paging("+num_msgs+"); proxy_mensagens.messages_list(get_current_folder(),"+msg_range_begin+","+msg_range_end+",'"+sort_box_type+"','"+search_box_type+"',"+sort_box_reverse+","+preferences.preview_msg_subject+","+preferences.preview_msg_tip+",function handler(data){alternate_border(0); draw_box(data, get_current_folder());});");
	}
	else {
		lnk_page = document.createElement("SPAN");
	}
  	span_paging.innerHTML += "&nbsp;";
  	span_paging.appendChild(lnk_page);

	lnk_page.title = get_lang("Last");
  	lnk_page.innerHTML = "&gt;&gt;";
}


// Draw the inbox and another folders
function draw_box(headers_msgs, msg_folder, alternate){
	/* 
	 * When the paging response is not in the correct folder you need to change folder
	 * This occurs when the Ajax response is not fast enough and the user click in outher 
	 * folder before finishing the Ajax request
	 */
	if (msg_folder != headers_msgs['folder']) { 
		
		if (headers_msgs['folder']) {
			array_folder = headers_msgs['folder'].split('/');
			
			if (array_folder.length > 1) {
				name_folder = array_folder[1];
			}
			else {
				name_folder = headers_msgs['folder'];
			}
			current_folder = headers_msgs['folder'];
			Element("border_id_0").innerHTML = "&nbsp;" + lang_folder(name_folder) + '&nbsp;<font face="Verdana" size="1" color="#505050">[<span id="new_m">&nbsp;</span> / <span id="tot_m"></span>]</font>';

			Element('new_m').innerHTML = headers_msgs['tot_unseen'] ? '<font color="RED">'+headers_msgs['tot_unseen']+'</font>' : 0;
			Element("tot_m").innerHTML = headers_msgs['num_msgs'];

			tree_folders.getNodeById(headers_msgs['folder'])._select();
		}
	}
	/* --- */
	
	if( alternate ) kill_current_box();

	if( is_ie ) document.getElementById("border_table").width = "99.5%";

	if( document.getElementById("content_id_"+numBox) == null ) return false;

	openTab.content_id[numBox] = document.getElementById("content_id_"+numBox);
	openTab.content_id[numBox].innerHTML = "";
	openTab.imapBox[numBox] = msg_folder;
	openTab.type[numBox] = 0;

	table_message_header_box = document.getElementById("table_message_header_box_"+numBox);
	if (table_message_header_box == null){
		var table_element = document.createElement("TABLE");
		var tbody_element = document.createElement("TBODY");
		table_element.setAttribute("id", "table_message_header_box_"+numBox);
		table_element.className = "table_message_header_box";

		tr_element = document.createElement("TR");
		tr_element.className = "message_header";
		td_element1 = document.createElement("TD");
		td_element1.setAttribute("width", "1%");
		chk_box_element = document.createElement("INPUT");
		chk_box_element.id  = "chk_box_select_all_messages";
		chk_box_element.setAttribute("type", "checkbox");
		chk_box_element.className = "checkbox";
		chk_box_element.onclick = function(){select_all_messages(this.checked);};
		chk_box_element.onmouseover = function () {this.title=get_lang('Select all messages.')};
		chk_box_element.onkeydown = function (e){
			if (is_ie)
			{
				if ((window.event.keyCode) == 46)
					proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
			else
			{
				if ((e.keyCode) == 46)
					proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		};

		td_element1.appendChild(chk_box_element);

		td_element2 = document.createElement("TD");
		td_element2.setAttribute("width", "7%");
		td_element3 = document.createElement("TD");
		td_element3.setAttribute("width", "29%");
		td_element3.onclick = function () {sort_box(search_box_type,'SORTFROM');};
		td_element3.id = "message_header_SORTFROM_"+numBox;
		td_element3.align = "left";
		td_element3.innerHTML = get_lang("Who");

		td_element4 = document.createElement("TD");
		td_element4.setAttribute("width", "38%");
		td_element4.onclick = function () {sort_box(search_box_type,'SORTSUBJECT');};
		td_element4.id = "message_header_SORTSUBJECT_"+numBox;
		td_element4.align = "left";
		td_element4.innerHTML = get_lang("Subject");

		td_element5 = document.createElement("TD");
		td_element5.setAttribute("width", "14%");
		td_element5.onclick = function () {sort_box(search_box_type,'SORTARRIVAL');};
		td_element5.id = "message_header_SORTARRIVAL_"+numBox;
		td_element5.align = "center";
		td_element5.innerHTML = get_lang("Date");
		td_element6 = document.createElement("TD");
		td_element6.setAttribute("width", "14%");
		td_element6.onclick = function () {sort_box(search_box_type,'SORTSIZE');}
		td_element6.id = "message_header_SORTSIZE_"+numBox;
		td_element6.align = "left";
		td_element6.innerHTML = get_lang("Size");

		tr_element.appendChild(td_element1);
		tr_element.appendChild(td_element2);
		var td_element21 = document.createElement("TD");
		td_element21.innerHTML = "&nbsp;&nbsp;&nbsp;";
		var td_element22 = document.createElement("TD");
		td_element22.innerHTML = "&nbsp;&nbsp;&nbsp;";
		var td_element23 = document.createElement("TD");
		td_element23.innerHTML = "&nbsp;&nbsp;";
		tr_element.appendChild(td_element21);
		tr_element.appendChild(td_element22);
		tr_element.appendChild(td_element23);
		tr_element.appendChild(td_element3);
		tr_element.appendChild(td_element4);
		tr_element.appendChild(td_element5);
		tr_element.appendChild(td_element6);
		tbody_element.appendChild(tr_element);
		table_element.appendChild(tbody_element);
		openTab.content_id[numBox].appendChild(table_element);
	}
	
	draw_header_box();
	var table_element = document.createElement("TABLE");
	var tbody_element = document.createElement("TBODY");
	table_element.id = "table_box";
	table_element.className = "table_box";
	table_element.borderColorDark = "#bbbbbb";
	table_element.frame = "void";
	table_element.rules = "rows";
	table_element.cellPadding = "0";
	table_element.cellSpacing = "0";

	table_element.onkeydown = function (e)
	{
		if (is_ie)
		{
			if ((window.event.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
		else
		{
			if ((e.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
	};
	
	if(is_ie){
		table_element.style.cursor = "hand";
	}

	tbody_element.setAttribute("id", "tbody_box");
	table_element.appendChild(tbody_element);

	var _divScroll = document.getElementById("divScrollMain_"+numBox);

	if(!_divScroll){
		_divScroll = document.createElement("DIV");
		_divScroll.id = "divScrollMain_"+numBox;
	}

	_divScroll.style.overflowY = "auto";
	_divScroll.style.overflowX = "hidden";
	_divScroll.style.width	="100%";

	if (is_mozilla){
		_divScroll.style.overflow = "-moz-scrollbars-vertical";
		_divScroll.style.width	="99.3%";
	}
	_divScroll.appendChild(table_element);
	openTab.content_id[numBox].appendChild(_divScroll);

	var f_unseen = 0;

	if (headers_msgs.num_msgs == 0)
	{
		var tr_info = document.createElement("TR");
		var td_info = document.createElement("TD");
		td_info.setAttribute("colspan", "10");
		td_info.setAttribute("background", "#FFF");
		td_info.setAttribute("id", "msg_info");
		td_info.id = "msg_info";
		td_info.align = "center";
		td_info.style.padding = "25px";
		td_info.style.fontWeight = "bold";
		td_info.style.fontSize = "11pt";
		td_info.innerHTML = get_lang("This mail box is empty");
		tr_info.appendChild(td_info);
		tbody_element.appendChild(td_info);
	}
	
	for (var i=0; i < headers_msgs.length; i++)
	{
		if ((headers_msgs[i].Unseen == 'U') || (headers_msgs[i].Recent == 'N'))
		{
			f_unseen++;
		}
		
		tr_element = make_tr_message(headers_msgs[i], msg_folder, headers_msgs.offsetToGMT);
		
		if (tr_element)
		{
			tbody_element.appendChild(tr_element);
			add_className(tr_element, i%2 != 0 ? 'tr_msg_read2' : 'tr_msg_read');
		}
		
		$(tr_element).on("oncontextmenu", function(e){ return false; });

		$(tr_element).on("mousedown", function(e)
		{ 
    		if (typeof e.preventDefault != 'undefined')
				e.preventDefault();
			else
				e.onselectstart = new Function("return false;");

		    _dragArea.makeDraggedMsg( $(this), e );
		});
	}

	if ((preferences.use_shortcuts == '1') && (headers_msgs[0])){
		shortcutExpresso.selectMsg( headers_msgs[0].msg_number, false, true );
	}

	var tdFolders  =  Element("folderscol");
	tdFolders.style.display = preferences.hide_folders == '1'  ? "none" : "";
	
	draw_footer_box(headers_msgs.num_msgs);
	
	if ( !currentTab )
		alternate_border(numBox);
	
	Element('main_table').style.display = '';
	if(is_ie6)	  // Stupid Fixing on IE6.
		setTimeout("resizeWindow()",1);
	else
		resizeWindow();
	if(debug) {
		var _eTime = new Date();
		_eTime = _eTime.getTime();
		alert("Carregou em "+(_eTime - _bTime)+" ms");
	}
	var msg_folder = Element('msg_folder').value;
	var msg_number = Element('msg_number').value;
	if(!msg_folder && msg_number) {
		new_message('new','null');
	}
	else if(msg_folder && msg_number){
		Ajax( '$this.imap_functions.get_info_msg', { 'msg_number': msg_number, 'msg_folder': msg_folder }, show_msg );
		Element('msg_folder').value = '';
		Element('msg_number').value = '';
	}
	var scripts = new Array("InfoContact", "TreeShow");
	connector.loadAllScripts(scripts);
	connector.loadScript("QuickAddTelephone");
}

// Passar o parametro offset para esta funcao
function make_tr_message(headers_msgs, msg_folder, offsetToGMT){
                if (typeof offsetToGMT == 'undefined')
                {
                    // In older local messages headers_msgs.offsetToGMT is undefined.
                    offsetToGMT = typeof headers_msgs.offsetToGMT != 'undefined'?headers_msgs.offsetToGMT:0;
                }
		var tr_element = document.createElement('tr');
		if(typeof(preferences.line_height) != 'undefined')
			tr_element.style.padding = preferences.line_height+'px 0';
		tr_element.id = headers_msgs.msg_number;

		tr_element.msg_sample = "";
		//if(headers_msgs.msg_sample && headers_msgs.msg_sample.preview_msg_subject != "")
		if(headers_msgs.msg_sample && preferences.preview_msg_subject == "1")
		{
			tr_element.msg_sample = headers_msgs.msg_sample.body.substr(0,120) + "..."; //trecho do body que sera exibido com o assunto;
		}

		tr_element.tip = "";
		if(headers_msgs.msg_sample && preferences.preview_msg_tip == "1")
		{
			tr_element.tip = headers_msgs.msg_sample.body.substr(3,300) + "..."; //trecho do body que sera exibido no tool-tip;
		}

		if ((headers_msgs.Unseen == 'U') || (headers_msgs.Recent == 'N')){
			if ((headers_msgs.Flagged == 'F') || parseInt(preferences.use_important_flag) && headers_msgs.Importance.toLowerCase().indexOf("high")!=-1 )
				add_className(tr_element, 'flagged_msg');
			add_className(tr_element, 'tr_msg_unread');
		}
		else{
			if ((headers_msgs.Flagged == 'F') || parseInt(preferences.use_important_flag) && headers_msgs.Importance.toLowerCase().indexOf("high")!=-1 )
				add_className(tr_element,'flagged_msg');
		}

		if ((headers_msgs.Unseen == 'U') || (headers_msgs.Recent == 'N'))
			add_className(tr_element, 'tr_msg_unread');

		if (headers_msgs.Flagged == 'F')
			add_className(tr_element,'flagged_msg');

		td_element1 = document.createElement("TD");
		td_element1.className = "td_msg";
		td_element1.setAttribute("width", "1%");
		chk_box_element = document.createElement("INPUT");
		chk_box_element.setAttribute("type", "checkbox");
		chk_box_element.className = "checkbox";
		chk_box_element.setAttribute("id", "check_box_message_"+headers_msgs.msg_number);
		chk_box_element.onclick = function(e){
			if (is_ie)
				changeBgColor(window.event,headers_msgs.msg_number);
			else
				changeBgColor(e,headers_msgs.msg_number);
		};
		td_element1.appendChild(chk_box_element);

		td_element2 = document.createElement("TD");
		td_element2.className = "td_msg";
		td_element2.setAttribute("width", "2%");
		if (headers_msgs.attachment && headers_msgs.attachment.number_attachments > 0)
			td_element2.innerHTML = "<img src ='templates/"+template+"/images/clip.gif' title='" + url_decode(headers_msgs.attachment.names) + "'>";

		td_element21 = document.createElement("TD");
		td_element21.className = "td_msg";
		td_element21.setAttribute("width", "1%");
		td_element21.id = "td_message_answered_"+headers_msgs.msg_number;

		if (headers_msgs.attachment && headers_msgs.attachment.number_attachments > 0) {
			attach_name = headers_msgs.attachment.names.split(", ");
			for(var item in attach_name)
			{
				if (url_decode(attach_name[item]) != 'smime.p7s' && url_decode(attach_name[item]) != 'smime.p7m'){
					td_element21.innerHTML = "<img src ='templates/"+template+"/images/clip.gif' title='" + url_decode(attach_name[item]) + "'>";
					break;
				}
			}
		}

		if ((headers_msgs.Forwarded == 'F')  || (headers_msgs.Draft == 'X' && headers_msgs.Answered == 'A')){
			td_element21.innerHTML = "<img src ='templates/"+template+"/images/forwarded.gif' title='"+get_lang('Forwarded')+"'>";
			headers_msgs.Draft = ''
			headers_msgs.Answered = '';
			headers_msgs.Forwarded = 'F';
		}
		else if (headers_msgs.Draft == 'X')
			td_element21.innerHTML = "<img src ='templates/"+template+"/images/draft.gif' title='"+get_lang('Draft')+"'>";
		else if (headers_msgs.Answered == 'A')
			td_element21.innerHTML = "<img src ='templates/"+template+"/images/answered.gif' title='"+get_lang('Answered')+"'>";
		else
			td_element21.innerHTML = "&nbsp;&nbsp;&nbsp;";

		td_element22 = document.createElement("TD");
		td_element22.className = "td_msg";
		td_element22.setAttribute("width", "1%");
		td_element22.id = "td_message_signed_"+headers_msgs.msg_number;
		switch(headers_msgs.ContentType)
		{
			case "signature":
			{
				td_element22.innerHTML = "<img src ='templates/"+template+"/images/signed_msg.gif' title='" + get_lang('Signed message') + "'>";
				break;
			}
			case "cipher":
			{
				td_element22.innerHTML = "<img src ='templates/"+template+"/images/lock.gif' title='" + get_lang('Crypted message') + "'>";
				break;
			}
			default:
			{
				break;
			}
		}

		td_element23 = document.createElement("TD");
		td_element23.className = "td_msg"
		td_element23.setAttribute("width", "1%");
		td_element23.id = "td_message_important_"+headers_msgs.msg_number;

		if (headers_msgs.Flagged == 'F' || (parseInt(preferences.use_important_flag) && headers_msgs.Importance.toLowerCase().indexOf("high") != -1 ))
		{
			td_element23.innerHTML = "<img src ='templates/"+template+"/images/important.gif' title='"+get_lang('Important')+"'>";
		}
		else
			td_element23.innerHTML = "&nbsp;&nbsp;&nbsp;";

		td_element24 = document.createElement("TD");
		td_element24.className = "td_msg";
		td_element24.setAttribute("width", "1%");
		td_element24.id = "td_message_sent_"+headers_msgs.msg_number;
		td_element24.innerHTML = "&nbsp;&nbsp;&nbsp;";
		// preload image
		var _img_sent = new Image();
		_img_sent.src 	 = "templates/"+template+"/images/sent.gif";



		var td_element25 = document.createElement("TD");
		td_element25.className = "td_msg";
		td_element25.setAttribute("width", "1%");
		td_element25.id = "td_message_unseen_"+headers_msgs.msg_number;
		if ((headers_msgs.Unseen == 'U') || (headers_msgs.Recent == 'N'))
			td_element25.innerHTML = "<img src ='templates/"+template+"/images/unseen.gif' title='"+get_lang('Unseen')+"'>";
		else
			td_element25.innerHTML = "<img src ='templates/"+template+"/images/seen.gif' title='"+get_lang('Seen')+"'>";


		td_element3 = document.createElement("TD");
		td_element3.className = "td_msg";
		td_element3.id = "td_who_"+ headers_msgs.msg_number;
		td_element3.setAttribute("width", "20%");
		var _onclick = function(){InfoContact.hide();proxy_mensagens.get_msg(headers_msgs.msg_number, msg_folder, show_msg);};
		td_element3.onclick = _onclick;
		td_element3.innerHTML = '&nbsp;';
		
		var isCurrentMailOnFROM = ( headers_msgs.from.email.toLowerCase() == Element("user_email").value.toLowerCase() );
		var isChangeOnSendFolderPreference = ( preferences.from_to_sent == "1" );
		var isSendFolder = (
			msg_folder.indexOf(sentfolder) != -1 ||
			msg_folder.indexOf(preferences.save_in_folder) != -1 ||
			msg_folder.replace("local_","INBOX/").indexOf(preferences.save_in_folder) != -1
		);
		
		if ( isCurrentMailOnFROM || ( isChangeOnSendFolderPreference && isSendFolder ) ) {
			// Use field TO
			td_element3.onmouseover = function () {this.title=headers_msgs.to.email;};
			if (headers_msgs.Draft == 'X')
				td_element3.innerHTML += "<span style=\"color:red\">("+get_lang("Draft")+") </span>";
			else{
				if(headers_msgs.to.email != null && headers_msgs.to.email.toLowerCase() != Element("user_email").value)
					td_element24.innerHTML = "<img valign='center' src ='templates/"+template+"/images/sent.gif' title='"+get_lang('Sent')+"'>";

				if( headers_msgs.to ){
					if(  headers_msgs.to.name != null && ( typeof(headers_msgs.to.name) == "string" &&  $.trim(headers_msgs.to.name) !== "" ) ){
						td_element3.innerHTML += headers_msgs.to.name;
					} else if(headers_msgs.to.email != null) {
						td_element3.innerHTML += headers_msgs.to.email;
					} else {
						td_element3.innerHTML += get_lang("without destination");
					}
				
				}
			}
		} else {
			// Use field FROM
			if (headers_msgs.Draft == 'X'){
				td_element3.innerHTML = "<span style=\"color:red\">("+get_lang("Draft")+") </span>";
			}
			else{
				var spanSender = document.createElement("SPAN");
				spanSender.onmouseover = function (event) {this.style.textDecoration = "underline";try {InfoContact.begin(this,headers_msgs.from.email)} catch(e){};};
				spanSender.onmouseout = function (){try {this.style.textDecoration = "none";clearTimeout(InfoContact.timeout);} catch(e){}};
				spanSender.innerHTML =  headers_msgs.from.name != null ? headers_msgs.from.name : headers_msgs.from.email;
				if (spanSender.innerHTML.indexOf(" ") == '-1' && spanSender.innerHTML.length > 25){
					spanSender.innerHTML = spanSender.innerHTML.substring(0,25) + "...";
				}
				else if (spanSender.innerHTML.length > 40 ){
					spanSender.innerHTML = spanSender.innerHTML.substring(0,40) + "...";
				}
				td_element3.appendChild(spanSender);
			}
		}
		
		td_element4 = document.createElement("TD");
		td_element4.className = "td_msg";
		td_element4.setAttribute("width", "50%");
		td_element4.onclick = _onclick;
		td_element4.innerHTML = !is_ie ? "<a nowrap id='a_message_"+tr_element.id+"'>&nbsp;" : "&nbsp;";

		if ((headers_msgs.subject)&&(headers_msgs.subject.length > 50))
		{
			//Modificacao para evitar que o truncamento do assunto quebre uma NCR - #1189
			pos = headers_msgs.subject.indexOf("&",45);
			if ((pos > 0) && (pos <= 50) && ((headers_msgs.subject.charAt(pos+5) == ";") || (headers_msgs.subject.charAt(pos+6) == ";")))
				td_element4.innerHTML += "<span class='_class_gamb_'>" + headers_msgs.subject.substring(0,pos+6) + "</span>" + "..." + "<span style=\"color:#b3b3b3;\">  " + tr_element.msg_sample +"</span>";
			else
				td_element4.innerHTML += "<span class='_class_gamb_'>" + headers_msgs.subject.substring(0,50) + "</span>" + "..." + "<span style=\"color:#b3b3b3;\">  " + tr_element.msg_sample +"</span>";//modificacao feita para exibir o trecho do body ao lado do assunto da mensagem;
		}
		else
		{
			td_element4.innerHTML += "<span class='_class_gamb_'>" +headers_msgs.subject + "</span>" + "<span style=\"color:#b3b3b3;\">  " + tr_element.msg_sample + "</span>";//modificacao feita para exibir o trecho do body ao lado do assunto da mensagem;
		}

		td_element4.title=tr_element.tip;
		if(!is_ie){
			td_element4.innerHTML += "</a>";
		}


		td_element5 = document.createElement("TD");
		td_element5.className = "td_msg";
		td_element5.setAttribute("width", "14%");
		td_element5.onclick = _onclick;
		td_element5.setAttribute("align", "center");
		var norm = function (arg) {return (arg < 10 ? '0'+arg : arg);};
		var weekDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

                var today = new Date();
		today.setHours(23);
		today.setMinutes(59);
                today.setSeconds(59);
                today.setMilliseconds(999)

                var udate_local = null;
                var date_msg = null;

                // old local messages can capture headers_msgs.udate as "hh:mm" or "dd/mm/yyyy"
                if (headers_msgs.udate.toString().match(/\d{2}:\d{2}/) || headers_msgs.udate.toString().match(/\d{2}\/\d{2}\/\d{4}/))
                {
                    temp_msg_day = headers_msgs.msg_day.split('/');
                    temp_msg_hour = headers_msgs.msg_hour.split(':');
                    date_msg = new Date(temp_msg_day[2], temp_msg_day[1]-1, temp_msg_day[0], temp_msg_hour[0], temp_msg_hour[1]);
                }
                else
                    {
                        // The new date implementation
                        // Using offset between user defined timezone and GMT
                        // Date object converts time to local timezone, so we have to adjust it
                        udate_local = headers_msgs.udate*1000 + offsetToGMT*1000 + today.getTimezoneOffset()*60*1000;
                        date_msg = new Date(udate_local);
                    }

                if (today.getTime() - date_msg.getTime() < 86400000)
			td_element5.innerHTML = norm(date_msg.getHours()) + ':' + norm(date_msg.getMinutes());
		else
			if (today.getTime() - date_msg.getTime() < 172800000)
				td_element5.innerHTML = get_lang('Yesterday');
			else
				if (today.getTime() - date_msg.getTime() < 259200000)
					td_element5.innerHTML = get_lang(weekDays[date_msg.getDay()]);
				else
					td_element5.innerHTML = norm(date_msg.getDate()) + '/' + norm(date_msg.getMonth()+1) + '/' +date_msg.getFullYear();
		td_element5.title = norm(date_msg.getDate()) + '/' + norm(date_msg.getMonth()+1) + '/' +date_msg.getFullYear();
		td_element5.alt = td_element5.title;



		td_element6 = document.createElement("TD");
		td_element6.className = "td_msg";
		td_element6.setAttribute("width", "14%");
		td_element6.onclick = _onclick;
		td_element6.setAttribute("noWrap","true");
		td_element6.setAttribute("align", "center");

		td_element6.innerHTML = borkb(headers_msgs.Size);


		tr_element.appendChild(td_element1);
		tr_element.appendChild(td_element2);
		tr_element.appendChild(td_element21);
		tr_element.appendChild(td_element22);
		tr_element.appendChild(td_element23);
			tr_element.appendChild(td_element24);
		tr_element.appendChild(td_element25);
		tr_element.appendChild(td_element3);
		tr_element.appendChild(td_element4);
		tr_element.appendChild(td_element5);
		tr_element.appendChild(td_element6);
		return tr_element;
}

function sort_box(search, sort){
	var message_header = Element("message_header_"+search);

	if(sort_box_type == sort && search_box_type == search){
		sort_box_reverse = sort_box_reverse ? 0 : 1;
	}
	else if(sort_box_type != sort){
		if ( (sort == 'SORTFROM') || (sort == 'SORTSUBJECT') )
			sort_box_reverse = 0;
		else
			sort_box_reverse = 1;
	}

	// Global variable.
	sort_box_type = sort;
	search_box_type = search;

	proxy_mensagens.messages_list(current_folder,1,preferences.max_email_per_page,sort,search,sort_box_reverse,preferences.preview_msg_subject,preferences.preview_msg_tip, function( data ) {
		draw_box(data, current_folder,true);
		//Mostrar as msgs nao lidas de acordo com o filtro de relevancia
		var msgs_unseen = 0;
		draw_paging(data.num_msgs);
		Element("new_m").innerHTML = '<font style="color:'+(data.tot_unseen == 0 ? '': 'red')+'">' + data.tot_unseen + '</font>';
		Element("tot_m").innerHTML = data.num_msgs;
	} );
	current_page = 1;
}
function draw_header_box(){
	switch(sort_box_type){
		case 'SORTFROM':
			type_name = get_lang("Who");
			break;
		case 'SORTSUBJECT':
			type_name = get_lang("Subject");
			break;
		case 'SORTARRIVAL':
			type_name = get_lang("Date");
			break;
		case 'SORTSIZE':
			type_name = get_lang("Size");
			break;
		default:
			type_name = get_lang("Date");
			break;
	}
	document.getElementById("message_header_SORTFROM_"+numBox).innerHTML 	= get_lang("Who");
	document.getElementById("message_header_SORTSUBJECT_"+numBox).innerHTML = get_lang("Subject");
	document.getElementById("message_header_SORTARRIVAL_"+numBox).innerHTML = get_lang("Date");
	document.getElementById("message_header_SORTSIZE_"+numBox).innerHTML	= get_lang("Size");
	document.getElementById("message_header_"+(sort_box_type.lastIndexOf("SORT") != "-1" ? sort_box_type : "SORTARRIVAL")+"_"+numBox ).innerHTML = "<B>"+type_name+"</B><img src ='templates/"+template+"/images/arrow_"+(sort_box_reverse == 1 ? 'desc' : 'asc')+"endant.gif'>";
}
function draw_message( info_msg, ID )
{
	var menuHidden = Element("folderscol").style.display == 'none' ? true : false;
	 //////////////////////////////////////////////////////////////////////////////////////////////////////
	//Make the next/previous buttom.
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	var next_previous_msg_td = document.createElement("TD");
	next_previous_msg_td.setAttribute("noWrap","true");
	next_previous_msg_td.align = "right";
	next_previous_msg_td.width = "40px";
	var img_next_msg = document.createElement("IMG");
	img_next_msg.id = 'msg_opt_next_' + ID;
	img_next_msg.src = './templates/'+template+'/images/down.button.png';
	img_next_msg.title = get_lang('Next');
	img_next_msg.style.cursor = 'pointer';

        var folder_id = ID.match(/\d+/)[0];
        var folder;

        //Correcao para fazer funcionar e-mails assinados no formato encapsulado.
       // folder_id = info_msg.original_ID ? info_msg.original_ID: info_msg.msg_number;
        if ((folder = document.getElementById(info_msg.original_ID)) == null)
        //if ((folder = document.getElementById(info_msg.msg_number)) == null)
            folder = document.getElementById(info_msg.msg_number);
	if (!folder){
		delete_border(ID);
		return;
		}
	if (folder){ // mensagem local criptografada nao tem ID da pasta local
		if (folder.nextSibling){
			var nextMsgBox = folder.nextSibling.name?folder.nextSibling.name:info_msg.msg_folder;

			if (nextMsgBox == "INBOX/decifradas")// teste para ver se a mensagem vem da pasta oculta decifradas
					nextMsgBox = get_current_folder();

			img_next_msg.onclick = function()
			{
				currentTab = ID;
				openTab.type[ID] = 2;
				proxy_mensagens.get_msg(folder.nextSibling.id,nextMsgBox,show_msg);
			};
		}
		else
		{
			img_next_msg.src = "./templates/"+template+"/images/down.gray.button.png";
			img_next_msg.style.cursor = 'default';

		}
	}
	else
	{
		img_next_msg.src = "./templates/"+template+"/images/down.gray.button.png";
		img_next_msg.style.cursor = 'default';
		if (!proxy_mensagens.is_local_folder(get_current_folder()) && !(info_msg.msg_folder == "INBOX/decifradas")) // testa se a mensagem e local
		{
			img_next_msg.onclick = function()
				{
					delete_border(ID);
				};
		}
	}
	var img_space = document.createElement("SPAN");
	img_space.innerHTML = "&nbsp;";
	var img_previous_msg = document.createElement("IMG");
	img_previous_msg.id = 'msg_opt_previous_' + ID;
	img_previous_msg.src = './templates/'+template+'/images/up.button.png';
	img_previous_msg.title = get_lang('Previous');
	img_previous_msg.style.cursor = 'pointer';

	if (!folder){
			delete_border(ID);
		return;
		}
	if (folder){ // mensagem local criptografada nao tem ID da pasta local
		if (folder.previousSibling)
		{
			var previousMsgBox = folder.previousSibling.name?folder.previousSibling.name:info_msg.msg_folder;

			if (previousMsgBox == "INBOX/decifradas") // teste para ver se a mensagem vem da pasta oculta decifradas
					previousMsgBox = get_current_folder();

			img_previous_msg.onclick = function()
			{
				currentTab = ID;
				openTab.type[ID] = 2;
				proxy_mensagens.get_msg(folder.previousSibling.id,previousMsgBox,show_msg);
			};
		}
		else
		{
			img_previous_msg.src = "./templates/"+template+"/images/up.gray.button.png";
			img_previous_msg.style.cursor = 'default';
		}
	}
	else
	{
		img_previous_msg.src = "./templates/"+template+"/images/up.gray.button.png";
		img_previous_msg.style.cursor = 'default';
		if (!proxy_mensagens.is_local_folder(get_current_folder()) && !(info_msg.msg_folder == "INBOX/decifradas")) // testa se a mensagem e local
		{
			img_previous_msg.onclick = function()
			{
				delete_border(ID);
			};
		}
	}
	next_previous_msg_td.appendChild(img_previous_msg);
	next_previous_msg_td.appendChild(img_space);
	next_previous_msg_td.appendChild(img_next_msg);
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	//Make the header message.
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	var table_message = document.createElement("TABLE");
	var tbody_message = document.createElement("TBODY");
	table_message.border = "0";
	table_message.width = "100%";

	//////////////////////////////////////////////////////////////////////////////////////////////////////
	//Make the options message.
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr0 = document.createElement("TR");
	tr0.className = "tr_message_header";
	var td0 = document.createElement("TD");
	var table_message_options = document.createElement("TABLE");
	table_message_options.width = "100%";
	table_message_options.border = '0';
	table_message_options.className = 'table_message';
	var tbody_message_options = document.createElement("TBODY");
	var tr = document.createElement("TR");
	var td = document.createElement("TD");
	td.setAttribute("noWrap","true");
	var _name = '';
	var _maxChar = menuHidden ? 40 : 15;

	if (info_msg.from.name)
	{
		var spanName = document.createElement("SPAN");
			spanName.innerHTML = info_msg.from.name;
		_name = spanName.innerHTML.length > _maxChar ? spanName.innerHTML.substring(0,_maxChar) + "..." : spanName.innerHTML;
	}
	else
		_name = info_msg.from.email.length > _maxChar ? info_msg.from.email.substring(0,_maxChar) + "..." : info_msg.from.email;

	td.innerHTML = _name.bold() + ', ' + info_msg.smalldate;
	if (info_msg.attachments && info_msg.attachments.length > 0)
		td.innerHTML += "&nbsp<img style='cursor:pointer' onclick='javascript:Element(\"option_hide_more_"+ID+"\").onclick()' src ='templates/"+template+"/images/clip.gif' title='"+info_msg.attachments[0].name+"'>";

	if (typeof(info_msg.signature) == 'string')
	{
		if (info_msg.signature != "void")
			td.innerHTML += '&nbsp;<img style="cursor:pointer" onclick="alert(\''+ get_lang("This message is signed, and you can trust.") + info_msg.signature +'\');" src="templates/'+template+'/images/signed.gif">';
		else
			td.innerHTML += "&nbsp;<img style='cursor:pointer' onclick='alert(\""+get_lang("This message is signed, but it is invalid. You should not trust on it.")+"\");' title='"+get_lang("Voided message")+"' src='templates/"+template+"/images/invalid.gif'>";
	}
	
	if (info_msg.DispositionNotificationTo)
	{
		td.innerHTML += '&nbsp;<img id="disposition_notification_'+ID+'" style="cursor:pointer" alt="'+ get_lang('Message with read notification') + '" title="'+ get_lang('Message with read notification') + '" src="templates/'+template+'/images/notification.gif">';
	}

	//Verifica se a mensagem local foi encaminhada.
	if (info_msg.Draft == 'X' && info_msg.Answered == 'A')
		info_msg.Forwarded = 'F';
	// NORMAL MSG
	if(info_msg.Draft != 'X' || info_msg.Forwarded == 'F')
	{
	var options = document.createElement("TD");
	options.width = "30%";
	options.setAttribute("noWrap","true");
	var option_hide_more = document.createElement("SPAN");
	option_hide_more.className = 'message_options';
        option_hide_more.onmouseover=function () {this.className='message_options_active';};
        option_hide_more.onmouseout=function () {this.className='message_options'};
	options.align = 'right';
	option_hide_more.value = 'more_options';
	option_hide_more.id = 'option_hide_more_'+ID;
	option_hide_more.onclick = function(){
		if (this.value == 'more_options'){
			this.innerHTML = "<b><u>"+get_lang('Hide details')+"</u></b>";
			this.value = 'hide_options';
			Element('table_message_others_options_'+ID).style.display = '';
		}
		else{
			this.innerHTML = get_lang('Show details');
			this.value = 'more_options';
			Element('table_message_others_options_'+ID).style.display = 'none';
		}
		resizeWindow();
	};
	var option_mark = document.createElement('TD');
	option_mark.align = "left";
	option_mark.width = "50%";
	var option_mark_as = '<span>'+get_lang("Mark as")+'</span>: ';
	var option_mark_as_unseen = document.createElement("SPAN");
	option_mark_as_unseen.className = "message_options";
	option_mark_as_unseen.onclick = function () {changeLinkState(this,'seen');
		proxy_mensagens.proxy_set_message_flag(folder_id,'unseen');
		write_msg(get_lang('Message marked as ')+get_lang("Unseen"));
	};
	option_mark_as_unseen.onmouseover=function () {this.className='message_options_active';};
	option_mark_as_unseen.onmouseout=function () {this.className='message_options'};
	option_mark_as_unseen.innerHTML = get_lang("Unseen");

	var option_mark_important = document.createElement("SPAN");
	option_mark_important.className = 'message_options';
	option_mark_important.style.paddingLeft = "10px";
	option_mark_important.onmouseover=function () {this.className='message_options_active';};
	option_mark_important.onmouseout=function () {this.className='message_options'};

	if (info_msg.Flagged == "F"){
		option_mark_important.onclick = function() { 
			var _this = this;
			proxy_mensagens.proxy_set_message_flag(folder_id,'unflagged', function(success){
				if (success) {
					changeLinkState(_this, 'important');
					write_msg(get_lang('Message marked as ') + get_lang("Normal"));
				}
			} );
			
		};
		option_mark_important.innerHTML = get_lang("Normal");
	}
	else{
		option_mark_important.onclick = function() {changeLinkState(this,'normal');
			proxy_mensagens.proxy_set_message_flag(folder_id,'flagged');
			write_msg(get_lang('Message marked as ')+get_lang("Important"));
		};
		option_mark_important.innerHTML = get_lang("Important");
	}
	option_mark.innerHTML = option_mark_as;
	option_mark.appendChild(option_mark_as_unseen);
	option_mark.appendChild(option_mark_important);
	option_hide_more.innerHTML = get_lang('Show details');
	options.appendChild(option_hide_more);

	var space0 = document.createElement("SPAN");
	space0.innerHTML = '&nbsp;|&nbsp;';
	var space1 = document.createElement("SPAN");
	space1.innerHTML = '&nbsp;|&nbsp;';
	var space2 = document.createElement("SPAN");
	space2.innerHTML = '&nbsp;|&nbsp;';

	var option_forward = document.createElement("SPAN");
	option_forward.id = 'msg_opt_forward_'+ID;
	option_forward.className = 'message_options';
	option_forward.onclick = function(){new_message('forward', ID);};
	option_forward.onmouseover=function () {this.className='message_options_active';};
        option_forward.onmouseout=function () {this.className='message_options'};
	option_forward.innerHTML = get_lang('Forward');
	options.appendChild(space1);
	options.appendChild(option_forward);
	var option_reply = document.createElement("SPAN");
	option_reply.id = 'msg_opt_reply_'+ID;
	option_reply.className = 'message_options';
	option_reply.onclick = function(){new_message('reply_with_history', ID);};
	option_reply.innerHTML = get_lang('Reply');
	option_reply.onmouseover=function () {this.className='message_options_active';};
	option_reply.onmouseout=function () {this.className='message_options'};

	options.appendChild(space2);

	var option_reply_options = document.createElement('IMG');
	option_reply_options.id = 'msg_opt_reply_options_'+ID;
	option_reply_options.src = '../expressoMail1_2/templates/default/images/down.png';
	option_reply_options.value = 'show';

	option_reply_options.onmouseover = function(){
		option_reply_options.src= '../expressoMail1_2/templates/default/images/over.png';
	};
	option_reply_options.onmouseout = function(){
		if (this.value == 'show')
		{
			option_reply_options.src= '../expressoMail1_2/templates/default/images/down.png';
		}
		else
		{
			option_reply_options.src= '../expressoMail1_2/templates/default/images/pressed.png';
		}
	};
	option_reply_options.onclick = function(){
		if (this.value != 'hide'){
			this.value = 'hide';
			option_reply_options.src= '../expressoMail1_2/templates/default/images/pressed.png';
			Element('tr_other_options_'+ID).style.display = '';

		}
		else{
			this.value = 'show';
			option_reply_options.src= '../expressoMail1_2/templates/default/images/down.png';
			Element('tr_other_options_'+ID).style.display = 'none';
		}
	};
	options.appendChild(option_reply_options);
	options.appendChild(option_reply);

	tr.appendChild(td);
	tr.appendChild(option_mark);
	tr.appendChild(options);
	tr.appendChild(next_previous_msg_td);
	tbody_message_options.appendChild(tr);

	////////// OTHER OPTIONS ////////////////////
	var tr_other_options = document.createElement("TR");
	tr_other_options.id = 'tr_other_options_' + ID;
	tr_other_options.style.display = 'none';

	var td_other_options = document.createElement("TD");
	td_other_options.colSpan = '3';
	var div_other_options = document.createElement("DIV");

	var option_mark_as_unseen = '<span class="message_options" onclick="proxy_mensagens.proxy_set_messages_flag(\'unseen\','+info_msg.msg_number+');write_msg(\''+get_lang('Message marked as ')+get_lang("Unseen")+'.\');">'+get_lang("Unseen")+'</span>, ';
	var option_mark_as_important			= '<span class="message_options" onclick="proxy_mensagens.proxy_set_messages_flag(\'flagged\','+info_msg.msg_number+');write_msg(\''+get_lang('Message marked as ')+get_lang("Important")+'.\');">'+get_lang("Important")+'</span>, ';
	var option_mark_as_normal				= '<span class="message_options" onclick="proxy_mensagens.proxy_set_messages_flag(\'unflagged\','+info_msg.msg_number+');write_msg(\''+get_lang('Message marked as ')+get_lang("Normal")+'.\');">'+get_lang("Normal")+'</span> | ';

	var option_move	= '<span class="message_options" onclick=wfolders.makeWindow("'+ID+'","move_to");>'+get_lang("Move")+'</span> | ';
	var option_reply_to_all = '<span onmouseover="this.className=\'reply_options_active\'" onmouseout="this.className=\'reply_options\'" class="reply_options" onclick=new_message("reply_to_all_with_history","'+ID+'");>'+get_lang("Reply to all")+'</span> | ';
	var option_reply_without_history = '<span onmouseover="this.className=\'reply_options_active\'" onmouseout="this.className=\'reply_options\'" class="reply_options" onclick=new_message("reply_without_history","'+ID+'");>'+get_lang("Reply without history")+'</span> | ';
	var option_reply_to_all_without_history	= '<span onmouseover="this.className=\'reply_options_active\'" onmouseout="this.className=\'reply_options\'" class="reply_options" onclick=new_message("reply_to_all_without_history","'+ID+'");>'+get_lang("Reply to all without history")+'</span>';

	div_other_options.innerHTML = option_reply_to_all + option_reply_without_history + option_reply_to_all_without_history;

	td_other_options.align = 'right';
	td_other_options.style.paddingTop = '3px';
	td_other_options.appendChild(div_other_options);


	tr_other_options.appendChild(td_other_options);
	tbody_message_options.appendChild(tr_other_options);
	////////// END OTHER OPTIONS ////////////////

		////////// BEGIN SIGNATURE //////////////////
	if (info_msg.signature && info_msg.signature.length > 0)
	{
            var tr_signature = document.createElement("TR");
            var td_signature = document.createElement("TD");
            td_signature.className = 'tr_message_header';
            tr_signature.id = 'tr_signature_'+ID;
            td_signature.colSpan = "5";
            tr_signature.style.display = 'none';
            for (i in info_msg.signature)
                {
                    if(typeof(info_msg.signature[i]) == 'object')
                        {
                            var aux = '';
                            for (ii in info_msg.signature[i])
                                {
                                    if(info_msg.signature[i][ii].indexOf("###") > -1)
                                        {
                                         aux += get_lang(info_msg.signature[i][ii].substring(0,info_msg.signature[i][ii].indexOf("###"))) + info_msg.signature[i][ii].substring(info_msg.signature[i][ii].indexOf("###")+3);
                                        }
                                    else
                                        {
                                         aux += info_msg.signature[i][ii];
                                        }
                                }
                            td_signature.innerHTML += "<a onclick=\"javascript:alert('" + aux + "')\"><b><font color=\"#0000FF\">" + get_lang("More") + "...</font></b></a>";
                            continue;
                        }
                    if(info_msg.signature[i].indexOf("#@#") > -1)
                        {
                         td_signature.innerHTML += '<span style=color:red><strong>'+get_lang(info_msg.signature[i].substring(0,info_msg.signature[i].indexOf("#@#")))+'</strong> '+info_msg.signature[i].substring(info_msg.signature[i].indexOf("#@#")+3)+'</span> <br /> ';
                        }
                    if(info_msg.signature[i].indexOf("###") > -1)
                        {
                         td_signature.innerHTML += '<span><strong>'+get_lang(info_msg.signature[i].substring(0,info_msg.signature[i].indexOf("###")))+'</strong> '+info_msg.signature[i].substring(info_msg.signature[i].indexOf("###")+3)+'</span> <br /> ';
                        }
                }
            var signature_status_pos = info_msg.signature[0].indexOf('Message untouched');
            td_signature.id = "td_signature_"+ID;
            if(signature_status_pos < 0 )
                {
                    td.innerHTML += '&nbsp;<img style="cursor:pointer" src="templates/'+template+'/images/signed_error.gif" title="'+get_lang("Details")+'">';
                    tr_signature.style.display = '';
                }
            else
                {
                    td.innerHTML += '&nbsp;<img style="cursor:pointer" src="templates/'+template+'/images/signed_table.gif" title="'+get_lang("Details")+'">';
                }
            td.onclick = function(){
            var _height = Element("div_message_scroll_"+ID).style.height;
            _height = parseInt(_height.replace("px",""));
            var _offset = 130;
            if (this.value == 'more_cert'){
                this.value = 'hide_cert';
                Element("div_message_scroll_"+ID).style.height = (_height + _offset)+"px";
                Element('tr_signature_'+ID).style.display = 'none';
                Element('td_signature_'+ID).style.display = 'none';

            }
            else{
                //this.innerHTML += "Mais Informacoes";
                this.value = 'more_cert';
                Element("div_message_scroll_"+ID).style.height = (_height - _offset)+"px";
                Element('tr_signature_'+ID).style.display = '';
                Element('td_signature_'+ID).style.display = '';
            }
	};

            tr_signature.appendChild(td_signature);
            tbody_message_options.appendChild(tr_signature);
	}
	//////////// END SIGNATURE ////////////////

	table_message_options.appendChild(tbody_message_options);
	td0.appendChild(table_message_options);
	tr0.appendChild(td0);
	tbody_message.appendChild(tr0);
	}
	// IF DRAFT
	else
	{
		var options = document.createElement("TD");
		options.width = "1%";
		options.setAttribute("noWrap","true");
		var option_edit	  = ' | <span class="message_options" onclick="new_message(\'edit\',\''+ID+'\');">'+get_lang('Edit')+'</span>';
		var option_print = ' | <span class="message_options" onclick="print_msg(\''+info_msg.msg_folder+'\',\''+info_msg.msg_number+'\',\''+ID+'\');">'+get_lang('Print')+'</span>';
		var option_hide_more = document.createElement("SPAN");
		option_hide_more.className = 'message_options';
		options.align = 'right';
		option_hide_more.value = 'more_options';
		option_hide_more.id = 'option_hide_more_'+ID;
		option_hide_more.innerHTML = get_lang('Show details');
		option_hide_more.onclick = function(){
			var _height = Element("div_message_scroll_"+ID).style.height;
			_height = parseInt(_height.replace("px",""));
			var _offset = 35;
			if (this.value == 'more_options'){
				this.innerHTML = "<b><u>"+get_lang('Hide details')+"</u></b>";
				this.value = 'hide_options';
				Element("div_message_scroll_"+ID).style.height = (_height - _offset)+"px";
				Element('table_message_others_options_'+ID).style.display = '';
			}
			else{
				this.innerHTML = get_lang('Show details');
				this.value = 'more_options';
				Element("div_message_scroll_"+ID).style.height = (_height + _offset)+"px";
				Element('table_message_others_options_'+ID).style.display = 'none';
			}
		};
		options.appendChild(option_hide_more);
		options_actions = document.createElement('SPAN');
		options_actions.innerHTML = option_edit + option_print;
		options.appendChild(options_actions);
		tr.appendChild(td);
		tr.appendChild(options);
		tr.appendChild(next_previous_msg_td);
		tbody_message_options.appendChild(tr);
		table_message_options.appendChild(tbody_message_options);
		td0.appendChild(table_message_options);
		tr0.appendChild(td0);
		tbody_message.appendChild(tr0);
		
		var important_message = document.createElement("INPUT");
		important_message.id = "is_important_"+ID;
		important_message.name = "is_important";
		important_message.type = "HIDDEN";
		important_message.value = (info_msg.Importance == "" || info_msg.Importance == "Normal") ? "0": "1";
		
		options.appendChild(important_message);
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	// END options message.
	//////////////////////////////////////////////////////////////////////////////////////////////////////

	var table_message_others_options = document.createElement("TABLE");
	table_message_others_options.id = 'table_message_others_options_' + ID;
	table_message_others_options.width = "100%";
	table_message_others_options.style.display = 'none';
	table_message_others_options.className = "table_message";
	var tbody_message_others_options = document.createElement("TBODY");
	var tr1 = document.createElement("TR");
	tr1.className = "tr_message_header";
	var td1 = document.createElement("TD");
	td1.innerHTML = get_lang("From: ");
	td1.appendChild(deny_email(info_msg.from.email));
	td1.width = "7%";

	if (info_msg.sender){
		var tr111 = document.createElement("TR");
		tr111.className = "tr_message_header";
		var td111 = document.createElement("TD");
		td111.innerHTML = get_lang("Sent by")+": ";
		td111.appendChild(deny_email(info_msg.sender.email));
		td111.setAttribute("noWrap","true");
		var sender = document.createElement("TD");
		sender.id = "sender_"+ID;
		var sender_values = document.createElement("INPUT");
		sender_values.id = "sender_values_"+ID;
		sender_values.type = "HIDDEN";
		sender_values.value = info_msg.sender.full; //Veio do IMAP, sem images nem links.
		sender.innerHTML += draw_plugin_cc(ID, info_msg.sender.full);
		sender.className = "header_message_field";
		tr111.appendChild(td111);
		tr111.appendChild(sender);
		tr111.appendChild(sender_values);
		tbody_message_others_options.appendChild(tr111);
	}

	var from = document.createElement("TD");
	from.id = "from_"+ID;
	from.innerHTML = info_msg.from.full;
	if (info_msg.Draft != "X"){
		from.innerHTML = draw_plugin_cc(ID, info_msg.from);
		tbody_message_others_options.appendChild(tr1);
	}
	from.className = "header_message_field";
	var from_values = document.createElement("INPUT");
	from_values.id = "from_values_"+ID;
	from_values.type = "HIDDEN";
	from_values.value = info_msg.from.full; //Veio do IMAP, sem images nem links.

	var local_message = document.createElement("INPUT");
	local_message.id = "is_local_"+ID;
	local_message.name = "is_local";
	local_message.type = "HIDDEN";
	local_message.value = (info_msg.local_message)?"1":"0";

	tr1.appendChild(td1);
	tr1.appendChild(from);
	tr1.appendChild(from_values);
	tr1.appendChild(local_message);

	if (info_msg.reply_to){
		var tr11 = document.createElement("TR");
		tr11.className = "tr_message_header";
		var td11 = document.createElement("TD");
		td11.innerHTML = get_lang("Reply to")+": ";
		td11.setAttribute("noWrap","true");
		var reply_to = document.createElement("TD");
		reply_to.id = "reply_to_"+ID;

		var reply_to_values = document.createElement("INPUT");
		reply_to_values.id = "reply_to_values_"+ID;
		reply_to_values.type = "HIDDEN";
		reply_to_values.value = info_msg.reply_to; //Veio do IMAP, sem images nem links.
		reply_to.innerHTML = draw_plugin_cc(ID, info_msg.reply_to);
		reply_to.className = "header_message_field";
		tr11.appendChild(td11);
		tr11.appendChild(reply_to);
		tr11.appendChild(reply_to_values);
		tbody_message_others_options.appendChild(tr11);
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr2 = document.createElement("TR");
	tr2.className = "tr_message_header";
	var td2 = document.createElement("TD");
	td2.innerHTML = get_lang("To: ");
	var to = document.createElement("TD");
	to.id = "to_"+ID;

	var to_values = document.createElement("INPUT");
	to_values.id = "to_values_"+ID;
	to_values.type = "HIDDEN";
	to_values.value = info_msg.toaddress2; //Veio do IMAP, sem images nem links.

	// Salva a pasta da mensagem
	var input_current_folder = document.createElement('input');
	input_current_folder.id = "input_folder_"+ID;
	input_current_folder.name = "input_folder";
	input_current_folder.type = "hidden";
	input_current_folder.value = info_msg.msg_folder;
	td2.appendChild(input_current_folder);
	// fim
	if(info_msg.toaddress2 != null )
	{
		toaddress_array[ID] = info_msg.toaddress2.split(",");
		
		if (toaddress_array[ID].length > 1)
		{
			to.innerHTML += draw_plugin_cc(ID, toaddress_array[ID][0]);
			var div_toaddress = document.createElement("SPAN");
			div_toaddress.id = "div_toaddress_"+ID;
			div_toaddress.style.display="";
			div_toaddress.innerHTML += " (<a STYLE='color: RED;' onclick=javascript:show_div_address_full('"+ID+"','to');>"+get_lang('more')+"</a>)";
			to.appendChild(div_toaddress);
		} 
		else
		{
			toAdd = toaddress_array[ID].toString()
			if( trim(toAdd) != "" ) {
				toAdd = toAdd.replace("<","&lt;").replace(">","&gt;");
			} else {
				toAdd = get_lang("without destination");
			}
			
			to.innerHTML += draw_plugin_cc(ID,toAdd);
		}
	
		to.className = "header_message_field";
		tr2.appendChild(td2);
		tr2.appendChild(to);
		tr2.appendChild(to_values);
	}
	
	tbody_message_others_options.appendChild(tr2);

	if (info_msg.cc){
		var tr3 = document.createElement("TR");
		tr3.className = "tr_message_header";
		var td3 = document.createElement("TD");
		td3.innerHTML = "CC: ";
		var cc = document.createElement("TD");
		cc.id = "cc_"+ID;

		var cc_values = document.createElement("INPUT");
		cc_values.id = "cc_values_"+ID;
		cc_values.type = "HIDDEN";
		cc_values.value = info_msg.cc;

		ccaddress_array[ID] = info_msg.cc.split(",");
		if (ccaddress_array[ID].length > 1){
			var div_ccaddress = document.createElement("SPAN");
			div_ccaddress.id = "div_ccaddress_"+ID;
			var div_ccaddress_full = document.createElement("SPAN");
			div_ccaddress_full.id = "div_ccaddress_full_"+ID;
			div_ccaddress.style.display="";
			cc.innerHTML = draw_plugin_cc(ID, ccaddress_array[ID][0]);
			div_ccaddress.innerHTML += " (<a STYLE='color: RED;' onclick=javascript:show_div_address_full('"+ID+"','cc');>"+get_lang('more')+"</a>)";
			cc.appendChild(div_ccaddress);
		}
		else{
			cc.innerHTML = draw_plugin_cc(ID, info_msg.cc);
		}
		cc.className = "header_message_field";
		tr3.appendChild(td3);
		tr3.appendChild(cc);
		tr3.appendChild(cc_values);
		tbody_message_others_options.appendChild(tr3);
	}

	/*
	 * @AUTHOR Rodrigo Souza dos Santos
	 * @MODIFY-DATE 2008/09/11
	 * @BRIEF Adding routine to create bcc field if there is one.
	 */
	if (info_msg.bcc)
	{
		var tr3 = document.createElement("tr");
		tr3.className = "tr_message_header";
		var td3 = document.createElement("td");
		td3.innerHTML = get_lang("BCC") + " : ";
		var cco = document.createElement("td");
		cco.id = "cco_"+ID;

		var cco_values = document.createElement("input");
		cco_values.id = "cco_values_"+ID;
		cco_values.type = "hidden";
		cco_values.value = info_msg.bcc;

		ccoaddress_array[ID] = info_msg.bcc.split(",");
		if (ccoaddress_array[ID].length > 1){
			var div_ccoaddress = document.createElement("SPAN");
			div_ccoaddress.id = "div_ccoaddress_"+ID;
			var div_ccoaddress_full = document.createElement("SPAN");
			div_ccoaddress_full.id = "div_ccoaddress_full_"+ID;
			div_ccoaddress.style.display="";

			//cco.innerHTML = draw_plugin_cc(ID, ccoaddress_array[ID][0]);
			cco.innerHTML = ccoaddress_array[ID][0];
			div_ccoaddress.innerHTML += " (<a STYLE='color: RED;' onclick=javascript:show_div_address_full('"+ID+"','cco');>"+get_lang('more')+"</a>)";
			cco.appendChild(div_ccoaddress);
		}
		else{
			//cco.innerHTML = draw_plugin_cc(ID, info_msg.cco);
			cco.innerHTML = info_msg.bcc;
		}
		cco.className = "header_message_field";
		tr3.appendChild(td3);
		tr3.appendChild(cco);
		tr3.appendChild(cco_values);
		tbody_message_others_options.appendChild(tr3);
	}

	var tr4 = document.createElement("TR");
	tr4.className = "tr_message_header";
	var td4 = document.createElement("TD");
	td4.innerHTML = get_lang("Date: ");
	var date = document.createElement("TD");
	date.id = "date_"+ID;
	date.innerHTML = info_msg.fulldate;
	var date_day = document.createElement("INPUT");
	date_day.id = "date_day_"+ID;
	date_day.type = "HIDDEN";
	date_day.value = info_msg.msg_day;
	var date_hour = document.createElement("INPUT");
	date_hour.id = "date_hour_"+ID;
	date_hour.type = "HIDDEN";
	date_hour.value = info_msg.msg_hour
	date.className = "header_message_field";
	tr4.appendChild(td4);
	tr4.appendChild(date);
	tr4.appendChild(date_day);
	tr4.appendChild(date_hour);
	tbody_message_others_options.appendChild(tr4);

	var tr5 = document.createElement("TR");
	tr5.className = "tr_message_header";
	var td5 = document.createElement("TD");
	td5.innerHTML = get_lang("Subject");
	var subject = document.createElement("TD");
	subject.id = "subject_"+ID;
	subject.innerHTML = info_msg.subject;
	subject.className = "header_message_field";
	tr5.appendChild(td5);
	tr5.appendChild(subject);
	tbody_message_others_options.appendChild(tr5);

	if ( info_msg.attachs && info_msg.attachs.length > 0 ) {
		$(tbody_message_others_options).append(
			$('<tr>').addClass('tr_message_header').append(
				$('<td>').attr({ 'valign': 'top' }).html( get_lang( 'Attachments: ' ) )
			).append(
				$('<td>').attr({ 'id': 'attachments_'+ID, 'align': 'left' })
			)
		);
		buildAttachments( $(tbody_message_others_options).find('#attachments_'+ID).data( info_msg ) );
	}

	var div = document.createElement("DIV");
	div.id = "div_message_scroll_"+ID;
	div.style.background = 'WHITE';
	div.style.overflow = "auto";
	div.style.width = "100%";
	table_message_others_options.appendChild(tbody_message_others_options);
	var tr = document.createElement("TR");
	var td = document.createElement("TD");
	td.colSpan = '2';
	div.appendChild(table_message_others_options);
	var imgTag = info_msg.body.match(/(<img[^>]*src[^>=]*=['"]?[^'">]*["']?[^>]*>)|(<[^>]*(style[^=>]*=['"][^>]*background(-image)?:[^:;>]*url\()[^>]*>)/gi);
	var newBody = info_msg.body;

	if (
		( !info_msg.showImg ) &&
		imgTag &&
		preferences.notification_domains != null &&
		typeof(preferences.notification_domains) != 'undefined'
	) {
		var domains = preferences.notification_domains.split(',');
		jQuery.each( domains, function( i, v ) { domains[i] = new RegExp( jQuery.ui.autocomplete.escapeRegex( $.trim(v) )+'$' ); } );

		var quoteprt = function( str ) { return '"'+str.replace( /"/g,'\\\"' )+'"'; };

		var testDomain = function( url ) {
			url = url.replace( /.*\/\//, '' ).replace( /\/.*/, '' );
			for ( var i = 0; i < domains.length; i++ )
				if ( url.match( domains[i] ) ) return false;
			return url;
		};

		var domainBlocked = function( img_tag ) {

			var delim = img_tag.toLowerCase().match( /src=\\?(['"])?/i );
			delim = ( delim && delim[1] )? delim[1] : ' ';

			img_tag = img_tag.replace( new RegExp( jQuery.ui.autocomplete.escapeRegex( '\\'+delim ), 'g' ), '\u2620' ).replace( /\\/g, '' );

			var img_src = img_tag.match( new RegExp( 'src=['+delim+']?([^'+delim+']*)', 'i' ) );
			img_src = ( img_src && img_src[1] )? img_src[1].replace( /\u2620/g, delim ) : false;
			if ( !img_src ) return get_lang( 'unknown' );

			if ( img_src.search( /^.\/inc\/show_img\.php/ ) == 0 ) return false;

			var img_scheme = img_src.match( /^([a-z]+):[//]*(.*)/ );
			if ( !( img_scheme && img_scheme[1] ) ) return quoteprt( img_src.substring( 0, 25 ) );

			if ( img_scheme[1] == 'cid' && img_scheme[2] && img_scheme[2].search( /^[\w.@_*#$-]+$/ ) >= 0 )return false;

			if ( img_scheme[1].search(/https?/i) >= 0 && img_scheme[2] ) {
				return testDomain( img_scheme[2] );
			}

			if ( img_scheme[1] == 'data' && img_scheme[2] )
				return quoteprt( 'data:'+img_scheme[2].replace( /,.*/, '' ) );

			return quoteprt( img_src.substring( 0, 25 ) );
		};

		var checkDomain = function( img_tag ) {
			var domain, domain_result;
			if ( ( domain = img_tag.toLowerCase().match( /(https?:)?\/\/[^/?'" )]*/g ) ) && domain.length )
				for ( var k = 0; k < domain.length; k++ )
					if ( !( domain_result = testDomain( domain[k] ) ) ) return domain_result;
			return domainBlocked( img_tag );
		};

		var blocked = [];
		for ( var j = 0; j < imgTag.length; j++ ) {
			var domain = checkDomain( imgTag[j] );
			if ( domain ) {
				newBody = newBody.replace( imgTag[j], "<img src='templates/"+template+"/images/forbidden.jpg'>" );
				blocked.push( domain );
			}
		}

		if ( blocked.length > 0 ) {
			var showImgLink = document.createElement('DIV');
			showImgLink.id="show_img_link_"+ID;
			showImgLink.onclick = function(){
				info_msg.showImg = true;
				draw_message(info_msg, ID);
			};
			showImgLink.className="show_img_link";
			showImgLink.innerHTML = get_lang("Show images from")+": "+info_msg.from.email+' ( '+jQuery.unique( blocked ).join(', ')+' )';
			td.appendChild(showImgLink);
		}
	}

	td.appendChild(div);
	tr.appendChild(td)
	tbody_message.appendChild(tr);

	//////////////////////////////////////////////////////////////////////////////////////////////////////
	//Make the body message.
	///////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr = document.createElement("TR");
	tr.className = "tr_message_body";
	var td = document.createElement("TD");

	var _body = document.createElement( 'div' );
	_body.id = 'body_' + ID;
	_body.style.fontSize = '16px';

	if ( info_msg.type == 'plain' ) {
		
		var pre_plain = $('<pre>').text( newBody );
		
		$(_body).append( pre_plain );

		if( info_msg.Draft && $.trim(info_msg.Draft) === 'X' ){

			var regexSignature = /<div id\="use_signature_anchor">.*<\/div>/g;

			if( newBody.search(regexSignature) >= 0 ){

				var signatureUser = newBody.match( regexSignature );

				$(pre_plain).append( signatureUser[0] ).append( ' ' );
				$(pre_plain)[0].childNodes[2].data = newBody.substring( newBody.search(regexSignature) + signatureUser[0].length )
				$(pre_plain)[0].childNodes[0].data = newBody.substring(0, newBody.search(regexSignature));
			}
		}			

		pre_plain.html( pre_plain.text() );

	} else {
		$(_body).html( newBody.replace(/<\/?body[^>]*>/ig,''));
	}

	// Remove all ids attributes, preserve signature anchor
	$(_body).find('*').not('div#use_signature_anchor').attr('id',null);

	$(div).append( $(_body) );

	function mailto( link )
	{
		var mail = link.href.substr( 7 );
		link.onclick = function( )
		{
			new_message_to( mail );
			return false;
		};
	}
	
	var links = div.getElementsByTagName( 'a' );
	
	for ( var i = 0; i < links.length; i++ ){
		try{
			if ( links.item( i ).href.indexOf( 'mailto:' ) === 0 ){
				mailto( links.item( i ) );
			}
			else{
				var anchor_pattern = "http://"+location.host+location.pathname+"#"; 
				
				if ( ( links.item( i ).href.indexOf( 'javascript:' ) !== 0 ) && 
					(links.item( i ).href.indexOf(anchor_pattern) !== 0) ) //se nao for ancora
						links.item( i ).setAttribute( 'target', '_blank' );
			}
		}catch(e){
		}
	}

	if( info_msg.hash_vcalendar !== undefined )
	{
		var div_vcalendar = $("<div>")
			.css('margin','10px 5px 5px 2px')
			.css('border','1px solid #eff0f1')
			.css("height","140px")
			.css("width", "400px")
			.css("font-size","11pt")
			.css("color", "black")
			.css("background-color","#eff0f1")
			.css("box-shadow","5px 5px 8px #888888")
			.css("cursor","pointer")
			.off("click").on("click", function(){ import_calendar( url_decode(info_msg.hash_vcalendar) ); })
			.html(
						"<div style='margin:5px;'>"+
						"<p><img align='middle' style='margin:2px;' src='templates/default/images/hash_vcalendar.png'>"+
						"Adicionar evento ao meu Expresso</p>" +
						"</div>"
					);
		
		$(div).append(div_vcalendar);
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////
	//Make the thumbs of the message.
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	if ((info_msg.thumbs)&&(info_msg.thumbs.length > 0)){
		var table_message_thumbs = document.createElement("TABLE");
		table_message_thumbs.width = "80%";
		table_message_thumbs.style.borderTop = "2px solid rgb(170, 170, 170)";
		var tbody_message_thumbs = document.createElement("TBODY");
		var tr = document.createElement("TR");
		tr.className = "tr_message_body";
		var td = document.createElement("TD");
		td.setAttribute("colSpan","2");
		td.id = "body_thumbs_"+ID;
		td.innerHTML += "&nbsp;<font color='DARKBLUE' size='2'><b>"+info_msg.attachments.length+" "+get_lang("attachment")+(info_msg.attachments.length > 1 ? "s" : "")+" "+get_lang("in this message")+"</font></b>";
		var _link_attachments = '';
		if(info_msg.thumbs.length > 1){
			_link_attachments 	= document.createElement("A");
			_link_attachments.className = "message_options";
			_link_attachments.setAttribute("href", "javascript:export_attachments('"+info_msg.msg_folder+"','"+parseInt( info_msg.msg_number )+"')");
			_link_attachments.innerHTML = get_lang('Download all atachments');
		}

		if(_link_attachments){
			td.innerHTML +=	" :: ";
			td.appendChild(_link_attachments);
		}

		td.innerHTML +=	"<BR><img src='templates/"+template+"/images/menu/ktip.png'>"+get_lang("<b>Tip:</b> For faster save, click over the image with <u>right button</u>.");
		td.innerHTML += "<BR>";

		for (var i=0; i<info_msg.thumbs.length; i++){
			if ((i % 4) == 0)
				td.innerHTML += "<BR>";
			td.innerHTML += info_msg.thumbs[i];
			td.innerHTML += "&nbsp;&nbsp;";
		}
		tr.appendChild(td);
		tbody_message_thumbs.appendChild(tr);
		table_message_thumbs.appendChild(tbody_message_thumbs);
		div.appendChild(table_message_thumbs);
	}
	table_message.appendChild(tbody_message);

	//////////////////////////////////////////////////////////////////////////////////////////////////////
	$('#content_id_'+ID)
		.empty()
		.data( info_msg )
		.append( table_message )
		.append( $('<input>').attr({ 'id': 'msg_number_'+ID, 'type': 'hidden' }).val( info_msg.msg_number ) );
	resizeWindow();
	//////////////////////////////////////////////////////////////////////////////////////////////////////

	//Exibe o cabecalho da mensagem totalmente aberto caso esteja setado nas preferencias do usuario
	if (preferences.show_head_msg_full == 1)
	{
		option_hide_more.onclick();
		if (Element('div_toaddress_'+ID) != null)
			show_div_address_full(ID,'to');
		if (Element('div_ccaddress_'+ID) != null)
			show_div_address_full(ID,'cc');
	}

}
function changeLinkState(el,state){
	el.innerHTML = get_lang(state);
	switch (state){
		case 'important':
			{
				el.onclick = function(){changeLinkState(el,'normal');proxy_mensagens.proxy_set_message_flag(currentTab.substr(0,currentTab.indexOf("_r")),'flagged');write_msg(get_lang('Message marked as ')+get_lang("Important"))}
				break;
			}
		case 'normal':
			{
				el.onclick = function(){ 
					var _this = this;
					proxy_mensagens.proxy_set_message_flag(currentTab.substr(0,currentTab.indexOf("_r")),'unflagged', function(success){
						if (success) {
							changeLinkState(_this, 'important');
							write_msg(get_lang('Message marked as ') + get_lang("Normal"));
						}
					} );
				}
				break;
			}
		case 'unseen':
			{
				el.onclick = function(){changeLinkState(el,'seen');proxy_mensagens.proxy_set_message_flag(currentTab.substr(0,currentTab.indexOf("_r")),'unseen');write_msg(get_lang('Message marked as ')+get_lang("unseen"))}
				break;

			}
		case 'seen':
			{
				el.onclick = function(){changeLinkState(el,'unseen');proxy_mensagens.proxy_set_message_flag(currentTab.substr(0,currentTab.indexOf("_r")),'seen');write_msg(get_lang('Message marked as ')+get_lang("seen"))}
				break;

			}
		default:
			{
				break;
			}
	}
}
function draw_new_message(border_ID){
	connector.loadScript("color_palette");
	connector.loadScript("rich_text_editor");
	connector.loadScript('wfolders');
	load_from_field();
	if (
		typeof RichTextEditor  == 'undefined' ||
		typeof ColorPalette    == 'undefined' ||
		typeof wfolders        == 'undefined' ||
		typeof SharedUsersData == 'undefined'
	) return false;

	var ID = create_border("",border_ID);
	if (ID == 0)
		return 0;
	hold_session = true;

	var footer_menu = Element("footer_menu");
	if (footer_menu) {
		footer_menu.style.display = 'none';
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////
	var form = document.createElement("FORM");
	form.name = "form_message_"+ID;
	form.method = "POST";
	form.onsubmit = function(){return false;}
	$(form).css('height', '100%');
	if(!is_ie)
		form.enctype="multipart/form-data";
	else
		form.encoding="multipart/form-data";
/////////////////////////////////////////////////////////////////////////////////////////////////////////
	//ConstructMenuNewMessage(ID);
////////////////////////////////////////////////////////////////////////////////////////////////////////
	var content = Element('content_id_' + ID);
	var table_message = document.createElement("TABLE");
	table_message.width = "100%";
	$(table_message).css('height', '100%');
	var tbody_message = document.createElement("TBODY");
	var tr0 = document.createElement("TR");
	tr0.className = "tr_message_header";
	var td0 = document.createElement("TD");
	td0.colSpan = '3';

	var table_menu_new_message = document.createElement("TABLE");
	table_menu_new_message.width = "100%";
	table_menu_new_message.border = '0';
	table_menu_new_message.className = 'table_message';
	var tbody_menu_new_message = document.createElement("TBODY");
	var tr_menu_new_message = document.createElement("TR");
	var td_menu_new_message = $('<td>').attr({'noWrap': 'true'});

	if ( preferences.save_in_folder == '-1' || preferences.save_in_folder == '' ) {
		td_menu_new_message.append(
			$('<input>').attr({'id':'send_button_'+ID,'type':'button','tabindex':'1'}).addClass('em_button_like_span').val(get_lang('Send and not file')).on('click',function(){ send_message( ID, null, null ); })
		);
		if ( !expresso_offline )
			td_menu_new_message.append(' | ').append(
				$('<input>').attr({'type':'button','tabindex':'2'}).addClass('em_button_like_span').val(get_lang('Send and file')).on('click',function(){ wfolders.makeWindow( ID, 'send_and_file' ); })
			);
	} else {
		td_menu_new_message.append(
			$('<input>').attr({'id':'send_button_'+ID,'type':'button','tabindex':'1'}).addClass('em_button_like_span').val(get_lang('Send')).on('click',function(){ send_message( ID, preferences.save_in_folder, null ); })
		);
		wfolders.setAlertMsg( true );
	}
	if ( !expresso_offline ) {
		td_menu_new_message.append(' | ').append(
			$('<input>').attr({'id':'save_message_options_'+ID,'type':'button','tabindex':'3'}).addClass('em_button_like_span').val(get_lang('Save')).on('click',function(){ openTab.toPreserve[ID] = true; save_msg( ID ); })
		).append(' | ').append(
			$('<input>').attr({'type':'button','tabindex':'4'}).addClass('em_button_like_span').val(get_lang('Search')).on('click',function(){ openListUsers( ID ); })
		);
	} else {
		td_menu_new_message.append(
			$('<input>').attr({'id':'save_message_options_'+ID,'type':'hidden'})
		);
	}

	$(tr_menu_new_message).append(td_menu_new_message);

	tbody_menu_new_message.appendChild(tr_menu_new_message);
	table_menu_new_message.appendChild(tbody_menu_new_message);

	content.appendChild(table_menu_new_message);
	tr0.appendChild(td0);
	tbody_message.appendChild(tr0);
////////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr1_1 = document.createElement("TR");
	tr1_1.style.display = 'none';
	var td1_1 = document.createElement("TD");
	td1_1.innerHTML = get_lang("From")+":";
	td1_1.setAttribute("noWrap","true");
	td1_1.style.width = "1%";

	var td_from = document.createElement("TD");
	td_from.setAttribute("noWrap","true");
	td_from.style.width = "100%";

	var sel_from = document.createElement('SELECT');
	sel_from.id = "from_"+ID;
	sel_from.name = "input_from";
	sel_from.style.width = "70%";
	sel_from.setAttribute("wrap","soft");
	$(sel_from).on('change',function(e){ SignatureFrame.redraw( $('iframe#body_'+ID) ); });
	td_from.appendChild(sel_from);
	tr1_1.appendChild(td1_1);
	tr1_1.appendChild(td_from);
	tbody_message.appendChild(tr1_1);
///////////////////////////////////////////////////////////////////////
	var tr1_2 = document.createElement("TR");
	tr1_2.id = "tr_replyto_"+ID;
	var td1_2 = document.createElement("TD");
	tr1_2.style.display = 'none';
        td1_2.innerHTML = get_lang("Reply to")+":";
	td1_2.setAttribute("noWrap","true");
	td1_2.style.width = "1%";

	var td_replyto = document.createElement("TD");
        td_replyto.setAttribute("noWrap","true");
	td_replyto.style.width = "100%";

        var input_replyto = document.createElement('INPUT');
	input_replyto.id = "replyto_"+ID;
        input_replyto.name = "input_replyto";
	input_replyto.setAttribute("tabIndex","1");
        input_replyto.style.width = "100%";
	input_replyto.setAttribute("wrap","soft");
        input_replyto.onfocus = function(){clearTimeout(parseInt(setTimeOutLayer));search_contacts('onfocus', this.id);};
	input_replyto.onblur = function(){setTimeOutLayer=setTimeout('search_contacts("lostfocus","'+this.id+'")',100);};

        if (!is_ie)
	{
        	input_replyto.rows = 2;
	        input_replyto.onkeydown = function (e)
	{
            if ((e.keyCode) == 120) //F9
	    {
                emQuickSearch(input_replyto.value, 'replyto', ID);
	    }
	    else
	    {
	        if (((e.keyCode == 13) || ((e.keyCode == 38)||(e.keyCode == 40))) && (document.getElementById('tipDiv').style.visibility!='hidden'))
	        {
        	    e.preventDefault();
                    search_contacts(e.keyCode,this.id);
                }
	    }
        }
        input_replyto.onkeyup = function (e)

        {
	    if ((e.keyCode != 13) && (e.keyCode != 38) && (e.keyCode != 40))
	    {
	        search_contacts(e.keyCode,this.id);
            }
        }
        }
        else
        {
                input_replyto.rows = 3;
                input_replyto.onkeyup = function (e)
	{
	    if ((window.event.keyCode) == 120) //F9
	    {
                emQuickSearch(input_replyto.value, 'replyto', ID);
            }
            else
	    {
                search_contacts(window.event.keyCode,this.id);
            }
	}
        }
	td_replyto.appendChild(input_replyto);

    var img_search = document.createElement("IMG");
	img_search.src = "./templates/"+template+"/images/search.gif";
	img_search.onclick = function () {emQuickSearch(document.getElementById('replyto_'+ID).value, 'replyto', ID)}
	img_search.title = get_lang('Search') + " | " + get_lang('Use F9 Key as shortcut.');
    var span_search = document.createElement("SPAN");
	span_search.innerHTML = get_lang('Search');

        var td1_2_img_search = document.createElement("TD");
	td1_2_img_search.setAttribute("noWrap","true");
        var td1_2_span_search = document.createElement("TD");
        td1_2_span_search.setAttribute("noWrap","true");

	td1_2_img_search.appendChild(img_search);
        td1_2_span_search.appendChild(span_search);

        tr1_2.appendChild(td1_2);
	tr1_2.appendChild(td_replyto);
        tr1_2.appendChild(td1_2_img_search);
        tbody_message.appendChild(tr1_2);
////////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr1 = document.createElement("TR");
	var td1 = document.createElement("TD");
	td1.innerHTML = get_lang("To")+":";
	td1.setAttribute("noWrap","true");
	td1.style.width = "1%";

	var td_to = document.createElement("TD");
	td_to.setAttribute("noWrap","true");
	td_to.style.width = "100%";

	var input_to = document.createElement('TEXTAREA');
	input_to.id = "to_"+ID;
	input_to.name = "input_to";
	input_to.setAttribute("tabIndex","1");
	input_to.setAttribute("placeholder", get_lang('without destination'));
	input_to.style.width = "100%";
	input_to.setAttribute("wrap","soft");
	input_to.onfocus = function(){clearTimeout(parseInt(setTimeOutLayer));search_contacts('onfocus', this.id);};
	input_to.onblur = function(){setTimeOutLayer=setTimeout('search_contacts("lostfocus","'+this.id+'")',100);};
	if (!is_ie)
	{
		input_to.rows = 2;
		input_to.onkeydown = function (e)
		{
			if ((e.keyCode) == 120) //F9
			{
				emQuickSearch(input_to.value, 'to', ID);
			}
			else
			{
				if (((e.keyCode == 13) || ((e.keyCode == 38)||(e.keyCode == 40))) && (document.getElementById('tipDiv').style.visibility!='hidden'))
				{
					e.preventDefault();
					search_contacts(e.keyCode,this.id);
				}
			}
		}
		input_to.onkeyup = function (e)
		{
			if ((e.keyCode != 13) && (e.keyCode != 38) && (e.keyCode != 40))
			{
				search_contacts(e.keyCode,this.id);
			}
		}
	}
	else
	{
		input_to.rows = 3;
		input_to.onkeyup = function (e)
		{
			if ((window.event.keyCode) == 120) //F9
			{
				emQuickSearch(input_to.value, 'to', ID);
			}
			else
			{
				search_contacts(window.event.keyCode,this.id);
			}
		}
	}
	td_to.appendChild(input_to);

	var forwarded_local_message = document.createElement("INPUT"); //Hidden para indicar se eh um forward de uma mensagem local
	forwarded_local_message.id = "is_local_forward"+ID;
	forwarded_local_message.name = "is_local_forward";
	forwarded_local_message.type = "HIDDEN";
	forwarded_local_message.value = "0";
	td_to.appendChild(forwarded_local_message);

	if (!expresso_offline) {
		var img_search = document.createElement("IMG");
		img_search.src = "./templates/"+template+"/images/search.gif";
		img_search.style.margin = '4px';
		img_search.title = get_lang('Search') + " | " + get_lang('Use F9 Key as shortcut.');
		img_search.onclick = function () {emQuickSearch(document.getElementById('to_'+ID).value, 'to', ID);};
	}
	else {
		var img_search = document.createElement("SPAN");
	}

	var span_search = document.createElement("SPAN");
	span_search.innerHTML = get_lang('Search');

	var td1_img_search = document.createElement("TD");
	td1_img_search.setAttribute("noWrap","true");
	var td1_span_search = document.createElement("TD");
	td1_span_search.setAttribute("noWrap","true");

	td1_img_search.appendChild(img_search);
	td1_span_search.appendChild(span_search);

	tr1.appendChild(td1);
	tr1.appendChild(td_to);
	tr1.appendChild(td1_img_search);

	tbody_message.appendChild(tr1);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr2 = document.createElement("TR");
	tr2.id = "tr_cc_"+ID;
	tr2.style.display = 'none';
	var td2 = document.createElement("TD");
	td2.innerHTML = "Cc:";
	td2.setAttribute("noWrap","true");
	td2.style.width = "1%";

	var td_cc = document.createElement("TD");
	td_cc.setAttribute("noWrap","true");
	td_cc.style.width = "100%";

	var input_cc = document.createElement('TEXTAREA');
	input_cc.id = "cc_"+ID;
	input_cc.name = "input_cc";
	input_cc.setAttribute("tabIndex","1");
	input_cc.setAttribute("placeholder", get_lang('without destination'));
	input_cc.style.width = "100%";
	input_cc.onfocus = function(){clearTimeout(parseInt(setTimeOutLayer));search_contacts('onfocus',this.id);};
	input_cc.onblur = function(){setTimeOutLayer=setTimeout('search_contacts("lostfocus","'+this.id+'")',100);};
	if (!is_ie)
	{
		input_cc.rows = 2;
		input_cc.onkeydown = function (e)
		{
			if ((e.keyCode) == 120) //F9
			{
				emQuickSearch(input_cc.value, 'cc', ID);
			}
			else
			{
				if (((e.keyCode == 13) || ((e.keyCode == 38)||(e.keyCode == 40))) && (document.getElementById('tipDiv').style.visibility!='hidden'))
				{
					e.preventDefault();
					search_contacts(e.keyCode,this.id);
				}
			}
		}
		input_cc.onkeyup = function (e)
		{
			if ((e.keyCode != 13) && (e.keyCode != 38) && (e.keyCode != 40))
			{
				search_contacts(e.keyCode,this.id);
			}
		}
	}
	else if (is_ie)
	{
		input_cc.rows = 3;
		input_cc.onkeyup = function (e)
		{
			if ((window.event.keyCode) == 120) //F9
			{
				emQuickSearch(input_cc.value, 'cc', ID);
			}
			else
			{
				search_contacts(window.event.keyCode,this.id);
			}
		}
	}

	td_cc.appendChild(input_cc);
	var img_search = document.createElement("IMG");
	img_search.src = "./templates/"+template+"/images/search.gif";
	img_search.style.margin = '4px';
	img_search.onclick = function () {emQuickSearch(document.getElementById('cc_'+ID).value, 'cc', ID)}
	img_search.title = get_lang('Search') + " | " + get_lang('Use F9 Key as shortcut.');
	var span_search = document.createElement("SPAN");
	span_search.innerHTML = get_lang('Search');

	var td2_img_search = document.createElement("TD");
	td2_img_search.setAttribute("noWrap","true");
	var td2_span_search = document.createElement("TD");
	td2_span_search.setAttribute("noWrap","true");

	td2_img_search.appendChild(img_search);
	td2_span_search.appendChild(span_search);

	tr2.appendChild(td2);
	tr2.appendChild(td_cc);
	tr2.appendChild(td2_img_search);
	tbody_message.appendChild(tr2);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr3 = document.createElement("TR");
	tr3.id = "tr_cco_"+ID;
	tr3.style.display = 'none';
	var td3 = document.createElement("TD");
	td3.setAttribute("noWrap","true");
	td3.innerHTML = get_lang("CCo")+":";
	td3.style.width = "1%";

	var td_cco = document.createElement("TD");
	td_cco.setAttribute("noWrap","true");
	td_cco.style.width = "100%";

	var input_cco = document.createElement('TEXTAREA');
	input_cco.id = "cco_"+ID;
	input_cco.name = "input_cco";
	input_cco.setAttribute("tabIndex","1");
	input_cco.setAttribute("placeholder", get_lang('without destination'));
	input_cco.style.width = "100%";
	input_cco.onfocus = function(){clearTimeout(parseInt(setTimeOutLayer));search_contacts('onfocus',this.id);};
	input_cco.onblur = function(){setTimeOutLayer=setTimeout('search_contacts("lostfocus","'+this.id+'")',100);};

	if (!is_ie)
	{
		input_cco.rows = 2;
		input_cco.onkeydown = function (e)
		{
			if ((e.keyCode) == 120) //F9
			{
				emQuickSearch(input_cco.value, 'cco', ID);
			}
			else
			{
				if (((e.keyCode == 13) || ((e.keyCode == 38)||(e.keyCode == 40))) && (document.getElementById('tipDiv').style.visibility!='hidden'))
				{
					e.preventDefault();
					search_contacts(e.keyCode,this.id);
				}
			}
		}
		input_cco.onkeyup = function (e)
		{
			if ((e.keyCode != 13) && (e.keyCode != 38) && (e.keyCode != 40))
			{
				search_contacts(e.keyCode,this.id);
			}
		}
	}
	else if (is_ie)
	{
		input_cco.rows = 3;
		input_cco.onkeyup = function (e)
		{
			if ((window.event.keyCode) == 120) //F9
			{
				emQuickSearch(input_cco.value, 'cco', ID);
			}
			else
			{
				search_contacts(window.event.keyCode,this.id);
			}
		}
	}

	td_cco.appendChild(input_cco);
	var img_search = document.createElement("IMG");
	img_search.src = "./templates/"+template+"/images/search.gif";
	img_search.style.margin = '4px';
	img_search.title = get_lang('Search') + " | " + get_lang('Use F9 Key as shortcut.');
	img_search.onclick = function () {emQuickSearch(document.getElementById('cco_'+ID).value, 'cco', ID);};
	var span_search = document.createElement("SPAN");
	span_search.innerHTML = get_lang('Search');

	var td3_img_search = document.createElement("TD");
	td3_img_search.setAttribute("noWrap","true");
	var td3_span_search = document.createElement("TD");
	td3_span_search.setAttribute("noWrap","true");

	td3_img_search.appendChild(img_search);
	td3_span_search.appendChild(span_search);

	tr3.appendChild(td3);
	tr3.appendChild(td_cco);
	tr3.appendChild(td3_img_search);
	tbody_message.appendChild(tr3);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr_link = document.createElement("TR");
	tr_link.id = "tr_link_"+ID;
	var td1_link = document.createElement("TD");
	var td2_link = document.createElement("TD");
	td1_link.innerHTML = "&nbsp;";
	var a_cc_link = document.createElement('A');
	a_cc_link.innerHTML = get_lang('Add CC');
	a_cc_link.id = "a_cc_link_"+ID;
	a_cc_link.setAttribute("href","javascript:void(0)");
	a_cc_link.setAttribute("tabIndex","-1");
	a_cc_link.onclick = function () {this.style.display='none';document.getElementById('tr_cc_'+ID).style.display='';document.getElementById('space_link_'+ID).style.display='none';input_cc.focus();return false;}
	td2_link.appendChild(a_cc_link);
	var space = document.createElement("span");
	space.id ="space_link_"+ID;
	space.innerHTML="&nbsp;|&nbsp;";
	td2_link.appendChild(space);
	var a_cco_link = document.createElement('A');
	a_cco_link.innerHTML = get_lang('Add BCC');
	a_cco_link.id = "a_cco_link_"+ID;
	a_cco_link.setAttribute("href","javascript:void(0)");
	a_cco_link.setAttribute("tabIndex","-1");
	a_cco_link.onclick = function () {this.style.display='none';document.getElementById('tr_cco_'+ID).style.display='';document.getElementById('space_link_'+ID).style.display='none';input_cco.focus();return false;}
	td2_link.appendChild(a_cco_link);
	var space = document.createElement("span");
	space.id ="space_link_2_"+ID;
	space.innerHTML="&nbsp;|&nbsp;";
	td2_link.appendChild(space);
	var a_replyto_link = document.createElement('A');
	a_replyto_link.innerHTML = get_lang('Reply to');
	a_replyto_link.id = "a_replyto_link_"+ID;
	a_replyto_link.setAttribute("href","javascript:void(0)");
	a_replyto_link.setAttribute("tabIndex","-1");
	a_replyto_link.onclick = function () {this.style.display='none';document.getElementById('tr_replyto_'+ID).style.display='';document.getElementById('space_link_2_'+ID).style.display='none';input_replyto.focus();return false;}
	td2_link.appendChild(a_replyto_link);
	tr_link.appendChild(td1_link);
	tr_link.appendChild(td2_link);
	tbody_message.appendChild(tr_link);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr4 = document.createElement("TR");
	var td4 = document.createElement("TD");
	//td4.setAttribute("noWrap","true");
	td4.innerHTML = get_lang("Subject")+":";
	var td_subject = document.createElement("TD");
	var input_subject = document.createElement('input');
	input_subject.id = "subject_"+ID;
	input_subject.name = "input_subject";
	input_subject.setAttribute("tabIndex","1");
	input_subject.style.width = "90%";
	input_subject.setAttribute("autocomplete","off");
	td_subject.appendChild(input_subject);
	tr4.appendChild(td4);
	tr4.appendChild(td_subject);
	tbody_message.appendChild(tr4);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr5 = document.createElement("TR");
	var td5 = document.createElement("TD");
	td5.innerHTML = "&nbsp;";
	var td_return_receipt = document.createElement("TD");
	td_return_receipt.setAttribute("noWrap","true");
	td_return_receipt.innerHTML = get_lang("Return receipt")+":";
	//var input_return_receipt = $("<input>");
	//input_return_receipt.attr("name","input_return_receipt");
	/*if (is_ie)
	{
		var input_return_receipt = document.createElement('input name=input_return_receipt');
	}
	else
	{*/
		var input_return_receipt = document.createElement('input');
		input_return_receipt.name = "input_return_receipt";
	//}
	input_return_receipt.type = "checkbox";
	input_return_receipt.className = "checkbox";
	input_return_receipt.id = "return_receipt_"+ID;
	input_return_receipt.setAttribute("tabIndex","-1");
	td_return_receipt.appendChild(input_return_receipt);

	// Workaround para resolver problema ao enviar e-mails
	if((preferences.use_assinar_criptografar != '0'))
	{
		if(parseInt(preferences.use_signature_digital_cripto)==1)
		{
			td_return_receipt.innerHTML +=  "&nbsp;&nbsp;" + get_lang("Digitally sign message?")+"";

			if (is_ie)
			{
				var input_return_digital = document.createElement('input name=input_return_digital');
			}
			else
			{
			var input_return_digital = document.createElement('input');
				input_return_digital.name = "input_return_digital";
			}

			input_return_digital.type = "checkbox";
			input_return_digital.className = "checkbox";
			input_return_digital.id = "return_digital_"+ID;
			input_return_digital.setAttribute("tabIndex","-1");
			if(parseInt(preferences.use_signature_digital)==1)
			{
                            if (is_ie)
                            {
				input_return_digital.checked=true;
                            }
                            else
                            {
                                input_return_digital.defaultChecked=true;
                            }
			}
			td_return_receipt.appendChild(input_return_digital);
			td_return_receipt.innerHTML +=  "&nbsp;&nbsp;" + get_lang("Digitally crypt message?")+"";

			if (is_ie)
			{
				var input_return_cripto = document.createElement('input name=input_return_cripto');
			}
			else
			{
			var input_return_cripto = document.createElement('input');
				input_return_cripto.name = "input_return_cripto";
			}

			input_return_cripto.type = "checkbox";
			input_return_cripto.className = "checkbox";
			input_return_cripto.id = "return_cripto_"+ID;
			input_return_cripto.setAttribute("tabIndex","-1");
			input_return_cripto.defaultChecked=false;

			if(parseInt(preferences.use_signature_cripto)==1)
			{
                            if (is_ie)
                            {
				input_return_cripto.checked=true;
                            }
                            else
                            {
				input_return_cripto.defaultChecked=true;
                            }
			}

			td_return_receipt.appendChild(input_return_cripto);
		}
	}
	td_return_receipt.innerHTML += "";
	tr5.appendChild(td5);
	tr5.appendChild(td_return_receipt);

	if ( ! expresso_offline )
	{
		var text_plain = td_return_receipt.appendChild(
			document.createElement( 'span' )
		).appendChild(
			document.createTextNode( ' | ' )
		).parentNode.appendChild(
			document.createTextNode( get_lang('Send this mail as text plain') + ':')
		);
		$(text_plain).after( $('<input>')
			.attr({ 'id': 'textplain_rt_checkbox_'+ID, 'type': 'checkbox', 'tabIndex': '-1', 'class': 'checkbox' })
			.on('change',function(e){
				RichTextEditor.plain( this.checked );
			})
		);
	}

	tbody_message.appendChild(tr5);
	if (parseInt(preferences.use_important_flag))
	{
		var trn = document.createElement("TR");
		var tdn = document.createElement("TD");
		tdn.innerHTML = "&nbsp;";
		var td_important_msg = document.createElement("TD");
		td_important_msg.setAttribute("noWrap","true");
		td_important_msg.innerHTML = get_lang("Important message")+":";
		var input_important_message = document.createElement('input');
		input_important_message.type = "checkbox";
		input_important_message.className = "checkbox";
		input_important_message.id = "important_message_"+ID;
		input_important_message.name = "input_important_message";
		input_important_message.setAttribute("tabIndex","-1");
		td_important_msg.appendChild(input_important_message);
		trn.appendChild(tdn);
		trn.appendChild(td_important_msg);
		tbody_message.appendChild(trn);
	}

	var add_files = document.createElement("A");
	add_files.setAttribute("href", "javascript:void(0)");
	add_files.onclick = function () {addFile(ID);return false;};
	add_files.innerHTML =  get_lang("Attachments: add+");
	add_files.setAttribute("tabIndex","-1");
	var divfiles = document.createElement("DIV");
	divfiles.id = "divFiles_"+ID;
	$(divfiles).addClass('msg_attachs');
	var tr5 = document.createElement("TR");
	var td5_link = document.createElement("TD");
	var td5_input = document.createElement("TD");
	td5_input.innerHTML = "&nbsp;"
	td5_link.setAttribute("valign","top");
	td5_link.setAttribute("colSpan","2");
	td5_link.appendChild(add_files);
	tr5.appendChild(td5_input);
	tr5.appendChild(td5_link);
	tbody_message.appendChild(tr5);
	var tr6 = document.createElement("TR");
	var td6_link  = document.createElement("TD");
	var td6_input = document.createElement("TD");
	tr6.appendChild(td6_link);
	td6_input.appendChild(divfiles);
	tr6.appendChild(td6_input);
	tbody_message.appendChild(tr6);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	var tr5 = document.createElement("TR");
	$(tr5).css('height', '100%');
	var td5 = document.createElement("TD");
	td5.innerHTML = "&nbsp;";
	var td_body = document.createElement("TD");
	td_body.setAttribute("colSpan","2");
	var div_body_position = document.createElement("DIV");
	div_body_position.id = "body_position_" + ID;
	$(div_body_position).css('height', '100%');
	td_body.appendChild(div_body_position);
	tr5.appendChild(td5);
	tr5.appendChild(td_body);
	tbody_message.appendChild(tr5);
   	var _div = document.createElement("DIV");
	_div.id = "div_message_scroll_"+ID;
	_div.style.overflow = "auto";
	_div.style.width = "100%";

	// Hide the contac tips and re-position the pallete color.
	_div.onscroll = function() {
		var intElemScrollTop = Element("div_message_scroll_"+ID).scrollTop;
		if (!is_ie)
			ColorPalette.repos(intElemScrollTop);
		Tooltip.scrollChanged();
	};
//////////////////////////////////////////////////////////////////////////////////////////////////////
	_div.appendChild(form);
	content.appendChild(_div);
	table_message.appendChild(tbody_message);
	form.appendChild(table_message);
	RichTextEditor.loadEditor(ID);
//////////////////////////////////////////////////////////////////////////////////////////////////////
	if(!expresso_offline)
		draw_from_field(sel_from,tr1_1);
	resizeWindow();

	return ID;
}

//	Verify if any user is sharing his name/email address
//	for use in the new messages's "From " field.
function draw_from_field( sel_from, tr1_1 )
{
	var myname = SharedUsersData.myname? SharedUsersData.myname : '';

	$(sel_from).append( $('<option>')
		.val( myname+";"+$('#user_email').val() )
		.data( {
			'mail': $('#user_email').val(),
			'signature': preferences.signature,
			'use_signature': preferences.use_signature,
			'type_signature': preferences.type_signature,
			'default_signature': preferences.default_signature
		} )
		.text( '"'+myname+'" <'+$('#user_email').val()+'>' ) );

	for ( var i in SharedUsersData ) {
		if ( isNaN( parseInt( i ) ) ) continue;
		tr1_1.style.display = '';
		$(sel_from).append( $('<option>')
			.data( SharedUsersData[i] )
			.val( SharedUsersData[i].cn + ';'+SharedUsersData[i].mail+';'+SharedUsersData[i].save_shared+';'+SharedUsersData[i].uid )
			.text( '"'+SharedUsersData[i].cn+'" <'+SharedUsersData[i].mail+'>' ) );
	}
}

function load_from_field()
{
	if ( typeof SharedUsersData !== 'undefined' ) return;

	// Get the shared folders.....
	var sharedFolders = new Array();
	for(var i = 0; i < folders.length; i++) {
		var x = folders[i].folder_id;
		if (folders[i].folder_parent == 'user'){
			sharedFolders[sharedFolders.length] = x;
		}
	}

	var matchUser = '#';
	var sharedUsers = new Array();
	// Filter the shared folders (only root folders) .....
	for(var i = 0; i < sharedFolders.length; i++) {
		matchUser = sharedFolders[i];
		sharedUsers[sharedUsers.length] = matchUser.substring(("user"+cyrus_delimiter).length,matchUser.length);
	}

	SharedUsersData = false;
	var form = $('<form>').append($('<input>').attr({'name':'uids'}).val(sharedUsers.join(';')));
	Ajax( '$this.ldap_functions.getSharedUsersFrom', form, function( data ) {
		SharedUsersData = data;
	} );
}

function changeBgColorToON(all_messages, begin, end){
	for (begin; begin<=end; begin++)
	{
		add_className(all_messages[begin], 'selected_msg');
		Element("check_box_message_" + all_messages[begin].id).checked = true;
	}
}
function updateBoxBgColor(box){
	// Set first TR Class
	var _className = 'tr_msg_read2';
	for(var i = 0; i < box.length;i++){
		if(exist_className(box[i],_className))
			remove_className(box[i], _className);
		_className = (_className == 'tr_msg_read2' ? 'tr_msg_read' : 'tr_msg_read2');
		if(!exist_className(box[i],_className))
			add_className( box[i], _className);
	}
}

function changeBgColor(event, msg_number){
	actual_tr = Element(msg_number);

	if (event.shiftKey)
	{
		last_tr = Element(last_message_selected);
		if(!last_tr)
			last_tr = actual_tr;

		all_messages = actual_tr.parentNode.childNodes;

		for (var i=0; i < all_messages.length; i++)
		{
			if (actual_tr.id == all_messages[i].id)
				first_order = i;
			if (last_tr.id == all_messages[i].id)
				last_order = i;
		}

		if (parseInt(first_order) > parseInt(last_order))
			changeBgColorToON(all_messages, last_order, first_order);
		else
			changeBgColorToON(all_messages, first_order, last_order);
	}
	else{
		//if ( exist_className(actual_tr, 'selected_msg') )
		if ( Element('check_box_message_' + msg_number).checked )
			add_className(actual_tr, 'selected_msg');
		else
			remove_className(actual_tr, 'selected_msg');
	}
	last_message_selected = msg_number;
}

function build_quota( data )
{
	var content_quota = $("#content_quota");

	var quota_limit = data['quota_limit'];
	var quota_used	= data['quota_used'];
	var value 		= data['quota_percent'];


	if( !quota_limit )
	{
		content_quota.html('<span><font size="2" style="color:red"><strong>'+get_lang("Without Quota")+'</strong></font></span>');
	}
	else
	{
		content_quota.html('');
		content_quota.css({'height':'15px'});

		var divDrawQuota = $("<div>");
		divDrawQuota.width(102);
		divDrawQuota.height(15);
		divDrawQuota.css({"background": "url(../phpgwapi/templates/"+template+"/images/dsunused.gif)","float":"left","margin-right":"5px"});

		var divQuotaUsed = $("<div>");
		divQuotaUsed.width(value+"%");
		divQuotaUsed.height(15);

		var imageBackground = "";

		if( value > 90 )
		{
			if( value >= 100 ){
				write_msg(get_lang("Your Mailbox is 100% full! You must free more space or will not receive messages."));
			} else {
				write_msg(get_lang("Warning: Your Mailbox is almost full!"));
			}
			imageBackground = "url(./templates/"+template+"/images/dsalert.gif)";
		} else if( value > 80 ) {
			imageBackground = "url(./templates/"+template+"/images/dswarn.gif)";
		} else {
			imageBackground = "url(./templates/"+template+"/images/dsused.gif)";
		}
		
		divQuotaUsed.css({"background": imageBackground });
		divDrawQuota.append(divQuotaUsed);

		var spanInfoQuota = $("<span>")
			.attr("class","boxHeaderText")
			.html( value + "% ("+borkb(quota_used*1024)+"/"+borkb(quota_limit*1024)+")" );

		content_quota.append(divDrawQuota);
		content_quota.append(spanInfoQuota);
	}
}

function update_quota(folder_id){
	Ajax( "$this.imap_functions.get_quota", { 'folder_id' : folder_id } , build_quota );
}

function draw_search(headers_msgs){
	Element("border_id_0").innerHTML = "&nbsp;&nbsp;" + get_lang('Search Result') + "&nbsp;&nbsp;";

	var tbody = Element('tbody_box');
	for (var i=0; i<(headers_msgs.length); i++){
            // passa parametro offset
		var tr = this.make_tr_message(headers_msgs[i], headers_msgs[i].msg_folder);
		if (tr)
			tbody.appendChild(tr);
	}
}

function draw_search_header_box(){
	var table_message_header_box = Element("table_message_header_box");
	table_message_header_box.parentNode.removeChild(table_message_header_box);

	var content_id_0 = Element("content_id_0");
	var table_element = document.createElement("TABLE");
	var tbody_element = document.createElement("TBODY");
	table_element.setAttribute("id", "table_message_header_box");
	table_element.className = "table_message_header_box";
	tr_element = document.createElement("TR");
	tr_element.className = "message_header";
	td_element1 = document.createElement("TD");
	td_element1.setAttribute("width", "1%");
	chk_box_element = document.createElement("INPUT");
	chk_box_element.id  = "chk_box_select_all_messages";
	chk_box_element.setAttribute("type", "checkbox");
	chk_box_element.className = "checkbox";
	chk_box_element.onclick = function(){select_all_messages(this.checked);};
	chk_box_element.onmouseover = function () {this.title=get_lang('Select all messages.')};
	chk_box_element.onkeydown = function (e){
		if (is_ie)
		{
			if ((window.event.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
		else
		{
			if ((e.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
	};

	td_element1.appendChild(chk_box_element);
	td_element2 = document.createElement("TD");
	td_element2.setAttribute("width", "3%");
	td_element3 = document.createElement("TD");
	td_element3.setAttribute("width", "30%");
	td_element3.id = "message_header_SORTFROM";
	td_element3.align = "left";
	td_element3.innerHTML = get_lang("From");
	td_element4 = document.createElement("TD");
	td_element4.setAttribute("width", "49%");
	td_element4.id = "message_header_SORTSUBJECT";
	td_element4.align = "left";
	td_element4.innerHTML = get_lang("Subject");
	td_element5 = document.createElement("TD");
	td_element5.setAttribute("width", "10%");
	td_element5.id = "message_header_SORTARRIVAL";
	td_element5.align = "center";
	td_element5.innerHTML = "<B>"+get_lang("Date")+"</B>";
	td_element5.innerHTML += "<img src ='templates/"+template+"/images/arrow_descendant.gif'>";
	td_element6 = document.createElement("TD");
	td_element6.setAttribute("width", "10%");
	td_element6.id = "message_header_SORTSIZE";
	td_element6.align = "right";
	td_element6.innerHTML = get_lang("Size");
	tr_element.appendChild(td_element1);
	tr_element.appendChild(td_element2);
	tr_element.appendChild(td_element3);
	tr_element.appendChild(td_element4);
	tr_element.appendChild(td_element5);
	tr_element.appendChild(td_element6);

	tbody_element.appendChild(tr_element);
	table_element.appendChild(tbody_element);
	content_id_0.appendChild(table_element);
}

function draw_search_division(msg){
	var tbody = Element('tbody_box');
	var tr = document.createElement("TR");
	var td = document.createElement("TD");
	td.colSpan = '7';
	td.width = '100%';

	var action_info_table = document.createElement("TABLE");
	var action_info_tbody = document.createElement("TBODY");

	action_info_table.className = "action_info_table";
	action_info_table.width = "100%";

	var action_info_tr = document.createElement("TR");

	var action_info_th1 = document.createElement("TH");
	action_info_th1.width = "40%";
	action_info_th1.innerHTML = "&nbsp;";

	var action_info_th2 = document.createElement("TH");

	action_info_th2.innerHTML = msg;
	action_info_th2.className = "action_info_th";
	action_info_th2.setAttribute("noWrap", "true");

	var action_info_th3 = document.createElement("TH");
	action_info_th3.width = "40%";
	action_info_th3.innerHTML = "&nbsp;";

	action_info_tr.appendChild(action_info_th1);
	action_info_tr.appendChild(action_info_th2);
	action_info_tr.appendChild(action_info_th3);
	action_info_tbody.appendChild(action_info_tr);
	action_info_table.appendChild(action_info_tbody);

	td.appendChild(action_info_table);
	tr.appendChild(td);
	tbody.appendChild(tr);
}

function draw_search_box(){
	var content_id_0 = Element("content_id_0");
	var table = document.createElement("TABLE");
	table.id = "table_box";
	table.width = 'auto';
	var tbody = document.createElement("TBODY");
	tbody.id = "tbody_box";

	table.className = "table_box";
	table.setAttribute("frame", "below");
	table.setAttribute("rules", "none");
	table.setAttribute("cellpadding", "0");
	table.onkeydown = function (e){
		if (is_ie)
		{
			if ((window.event.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
		else
		{
			if ((e.keyCode) == 46)
			{
				//delete_all_selected_msgs_imap();
				proxy_mensagens.delete_msgs(get_current_folder(),'selected','null');
			}
		}
	};
	if (is_ie)
		table.style.cursor = "hand";

	table.appendChild(tbody);
	content_id_0.appendChild(table);
}

var idx_cc = 0;

function draw_plugin_cc(ID, addrs)
{
	connector.loadScript("ccQuickAdd");

	if ( typeof addrs.full !== 'undefined' ) addrs = addrs.full;

	var array_addrs = "";
	var array_name 	= "";
	var cc_data = new Array();
	
	if(typeof(addrs.name) != 'undefined') {
		array_name 	= $.trim(addrs.name).split(" ");
		array_addrs = new Array(addrs.email);
	} else {
		array_addrs = (typeof addrs == 'object' ? addrs.toString().split("\" ") : addrs.split("\" "));
		array_name 	= $.trim(array_addrs[0]).replace('"','').split(" ");
	}

	var _split = array_name[0].split('@');
	cc_data[0] = _split[0];
	cc_data[1] = _split[0];
	cc_data[2] = "";

	for (i=1; i < array_name.length; i++){
		cc_data[2] += array_name[i] + " ";
	}

	if( array_addrs.length > 1){
		cc_data[3] = array_addrs[1] ? array_addrs[1] : "";
		cc_data[3] = cc_data[3].replace("\<","&lt;").replace("\>","&gt;");
		cc_data[3] = cc_data[3].replace("&lt;","").replace("&gt;","");
	} else {
		cc_data[3] = array_addrs[0];
	}

	var sm_envelope_img1 = '<img style="cursor:'+ (is_ie ? 'hand' : 'pointer') +'" title="' + get_lang("Add Contact") +
	'" onclick="ccQuickAddOne.showList(\''+cc_data+'\')" src="./templates/'+template+'/images/user_card.png">';
	var to_addybook_add = "<SPAN id='insert_plugin_"+idx_cc+"_"+ID+"'>";
	to_addybook_add += addrs.replace("\<","&lt;").replace("\>","&gt;");
	to_addybook_add +=  sm_envelope_img1;
	idx_cc++;
	to_addybook_add += "</SPAN>";
	return to_addybook_add;
}
function deny_email(email){
	connector.loadScript("filter");
	connector.loadScript("filters");
	var dn_em 	= document.createElement("SPAN");
		dn_em.id = "tt_d";
		dn_em.onclick = function(){filter.new_rule(email);};
		dn_em.setAttribute("title",get_lang("Block Sender"));
		dn_em.style.cursor = "pointer";
		dn_em.innerHTML = "<img align='top' src='./templates/"+template+"/images/deny.gif'>";
	return dn_em;

}
function show_div_address_full(id, type) {
	var div_address_full = Element("div_"+type+"address_full_"+id);
	if(!div_address_full) {
		div_address_full = document.createElement("SPAN");
		div_address_full.id = "div_"+type+"address_full_"+id;
		div_address_full.style.display="none";
		var _address = eval(type+"address_array['"+id+"']");
		var isOverLimit = (_address.length > 100);

		if(isOverLimit) {
			alert("Esse campo possui muitos enderecos ("+_address.length+" destinatarios).\r\n"+
			"Para evitar o travamento do navegador, o botao 'Adicionar Contato' foi desabilitado!");
		}

		for(var idx = 1 ; idx  < _address.length;idx++) {
			div_address_full.innerHTML += isOverLimit ?  '<br>'+_address[idx] : ','+draw_plugin_cc(id,_address[idx]);
		}
		div_address_full.innerHTML += " (<a STYLE='color: RED;' onclick=document.getElementById('div_"+type+"address_full_"+id+"').style.display='none';document.getElementById('div_"+type+"address_"+id+"').style.display='';>"+get_lang('less')+"</a>)";
		Element(type+"_"+id).appendChild(div_address_full);
	}
	Element('div_'+type+'address_'+id).style.display='none';
	div_address_full.style.display='';
}
function draw_footer_box(num_msgs){
	folder = get_current_folder();
	connector.loadScript('wfolders');
	var span_R = Element("table_message");
	var span_options = Element("span_options");
	if(!span_options) {
		span_options = document.createElement("TD");
		span_options.style.fontSize = "12";
		span_options.id = "span_options";
		span_R.appendChild(span_options);
	}

	var change_font_color = 'onmouseover="var last_class = this.className;'+
				'if (this.className != \'message_options_over\')'+
				'this.className=\'message_options_active\'; '+
				'this.onmouseout=function(){this.className=last_class;}"';

	span_options.innerHTML =
		'<span class="message_options_trash"><span ' + change_font_color + ' title="'+get_lang("Delete")+'" class="message_options" onclick=proxy_mensagens.delete_msgs(\'null\',\'selected\',\'null\')>'+get_lang("Delete")+'</span></span>'+
		'<span class="message_options_move"><span ' + change_font_color + ' title="'+get_lang("Move")+'" class="message_options" onclick=wfolders.makeWindow(\"\",\"move_to\")>'+get_lang("Move")+'</span></span>'+
   		((expresso_offline)?" ":'<span class="message_options_print"><span ' + change_font_color + ' title="'+get_lang("Print")+'" class="message_options" onclick=print_all()>'+get_lang("Print")+'</span></span>')+
		((expresso_offline)?" ":'<span class="message_options_export"><span ' + change_font_color + ' title="'+get_lang("Export")+'" class="message_options" onclick="proxy_mensagens.export_all_messages()">'+get_lang("Export")+'</span></span>') +
		((expresso_offline)?" ":'<span class="message_options_import"><span ' + change_font_color + ' title="'+get_lang("Import")+'" class="message_options" onclick="import_window()">'+get_lang("Import")+'</span></span>');

	span_options.innerHTML += '<span id="mark_as_spam" '+change_font_color+' title="'+get_lang( 'Mark as Spam' )+'" class="message_options" onclick="mark_as_spam( true );" style="display: none; background: url(\'../phpgwapi/templates/default/images/foldertree_sent.png\') no-repeat left center; padding: 0 6px 1px 17px;">'+get_lang( 'Mark as Spam' )+'</span>';
	span_options.innerHTML += '<span id="mark_as_not_spam" '+change_font_color+' title="'+get_lang( 'Not Spam' )+'" class="message_options" onclick="mark_as_spam( false );" style="display: none; background: url(\'../phpgwapi/templates/default/images/foldertree_sent.png\') no-repeat left center; padding: 0 6px 1px 17px;">'+get_lang( 'Not Spam' )+'</span>';
	
	var span_D = Element("span_D");
	if(!span_D){
		span_D = document.createElement("TD");
		span_D.align = "right";
		span_D.style.fontSize = "12";
		span_D.id = "span_D";
		span_R.appendChild(span_D);
	}
	span_D.innerHTML =
   		 get_lang("List")+': '+
   	'<span ' + change_font_color + ' id="span_flag_SORTARRIVAL" class="'+(search_box_type == 'ALL' ? 'message_options_over' : 'message_options')+'" title="'+get_lang("All")+'" onclick="if(\'ALL\' == \''+search_box_type+'\') return false;sort_box(\'ALL\',\''+sort_box_type+'\')">'+get_lang("All")+'</span>, '+
   	'<span ' + change_font_color + ' id="span_flag_UNSEEN" class="'+(search_box_type == 'UNSEEN' ? 'message_options_over' : 'message_options')+'" title="'+get_lang("l_unseen")+'" onclick="if(\'UNSEEN\' == \''+search_box_type+'\') return false;sort_box(\'UNSEEN\',\''+sort_box_type+'\')">'+get_lang("l_unseen")+'</span>, '+
  	'<span ' + change_font_color + ' id="span_flag_SEEN" class="'+(search_box_type == 'SEEN' ? 'message_options_over' : 'message_options')+'" title="'+get_lang("l_seen")+'" onclick="if(\'SEEN\' == \''+search_box_type+'\') return false;sort_box(\'SEEN\',\''+sort_box_type+'\')">'+get_lang("l_seen")+'</span>, '+
   	'<span ' + change_font_color + ' id="span_flag_ANSWERED" class="'+(search_box_type == 'ANSWERED' ? 'message_options_over' : 'message_options')+'" title="'+get_lang("title_answered")+'" onclick="if(\'ANSWERED\' == \''+search_box_type+'\') return false;sort_box(\'ANSWERED\',\''+sort_box_type+'\')">'+get_lang("l_answered")+'</span>, '+
   	'<span ' + change_font_color + ' id="span_flag_FLAGGED" class="'+(search_box_type == 'FLAGGED' ? 'message_options_over' : 'message_options')+'" title="'+get_lang("l_important")+'" onclick="if(\'FLAGGED\' == \''+search_box_type+'\') return false;sort_box(\'FLAGGED\',\''+sort_box_type+'\')">'+get_lang("l_important")+'</span>&nbsp;&nbsp;';
	draw_paging(num_msgs);
	Element("tot_m").innerHTML = num_msgs;
}
