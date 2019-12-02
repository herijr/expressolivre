// BEGIN: FUNCTION RESIZE WINDOW
if (!expresso_offline) {
	var _showBar = showBar;
	var _hideBar = hideBar;
}

function __showBar(){
	_showBar();
	resizeWindow();
}

function __hideBar(){
	_hideBar();
	resizeWindow();
}
showBar = __showBar;
hideBar = __hideBar;

window.onresize = resizeWindow;

var Common = new function()
{
	/**
	 * Get the imap folder of current tab
	 * Ex.: "INBOX", "INBOX/Sent"
	 **/
	this.getCurrentFolder = function() {
		return current_folder;
	}
	
	/**
	 * Return type of current tab.
	 * 
	 * 0: Main tab
	 * 1: Search tab
	 * 2: Mail tab
	 **/
	this.typeOfTab = function( tabName ) {
		if ( tabName == undefined ) tabName = Common.getSelectedTabName();
		return openTab.type[tabName];
	}
	
	/**
	 * Get the imap folder of tab
	 * Ex.: "INBOX", "INBOX/Sent"
	 **/
	this.getImapFolder = function( tabName ) {
		if ( tabName == undefined ) return openTab.imapBox[0];
		return openTab.imapBox[tabName];
	}
	
	/**
	 * Get the imap folder of tab
	 * Ex.: "INBOX", "INBOX/Sent"
	 **/
	this.getSelectedTabName = function() {
		return currentTab;
	}
}

function config_events(pObj, pEvent, pHandler)
{
    if( typeof pObj == 'object')
    {
        if( pEvent.substring(0, 2) == 'on')
            pEvent = pEvent.substring(2, pEvent.length);

        if ( pObj.addEventListener )
            pObj.addEventListener(pEvent, pHandler, false);
        else if( pObj.attachEvent )
            pObj.attachEvent('on' + pEvent, pHandler );
    }
}

function addContactMessenger( uid, name )
{
	if( $.xmpp.isConnected() )
	{
		var contact = 
		{ 
			'to' 	: uid+"@"+$.xmpp.getDomain(),
			'name'	: name,
			'group' : "",
			'type'  : "subscribe"
		}

		// Add Contact
		$.xmpp.addContact(contact);
		
		// Send Subscription
		$.xmpp.subscription(contact);

		write_msg( get_lang("added contact") );
	}
	else
	{
		write_msg( get_lang("You must be logged in Messenger Express") );
	}
}	

function initMessenger( event )
{
	var init_open = ( event.type == 'click' );
	
	$( event.currentTarget ).off( 'click' );
	
	// Load IM
	$( event.currentTarget ).find( 'div#_plugin' ).im( {
		'resource'  : event.data['dt__a'],
		'url'       : event.data['dt__b'],
		'domain'    : event.data['dt__c'],
		'username'  : event.data['dt__d'],
		'auth'      : event.data['dt__e'],
		'debug'     : false,
		'soundPath' : '../prototype/plugins/messenger/'
	} );
	
	//Full Name Expresso Messenger
	var fullName = $( 'input[name=messenger_fullName]' ).val();
	
	$( '.chat-title' ).find( '.chat-name' )
		.html( fullName.substring( 0, 20 ) + '...' )
		.attr( 'alt', fullName )
		.attr( 'title', fullName );
	
	$( '#conversation-bar-container' ).css( { 'overflow': 'hidden', 'bottom': '1px' } );
	
	$( event.currentTarget ).find( 'div#_menu' ).on( 'click', { 'fadeIn': ( init_open? 3000 : 0 ) }, openMessenger );
	
	if ( init_open ) $( event.currentTarget ).find( 'div#_menu' ).trigger( 'click' );
	
}

function openMessenger( event )
{
	$( '#content_folders' ).hide();
	
	if ( event && event.data && event.data.fadeIn ) $( '#content_messenger #_plugin ').fadeIn( event.data.fadeIn );
	else $( '#content_messenger #_plugin ').show();
	
	$( '#content_messenger #_menu' ).off( 'click', openMessenger ).on( 'click', closeMessenger );
	
	resizeWindow();
}

function closeMessenger()
{
	$( '#content_messenger #_plugin ').hide();
	
	$( '#content_folders' ).show();
	
	$( '#content_messenger #_menu' ).off( 'click', closeMessenger ).on( 'click', openMessenger );
	
	resizeWindow();
}

function resizeWindow()
{
	var content_folders		= $("#content_folders");
	var content_messenger 	= $("#content_messenger");
	var divScrollMain		= $("#divScrollMain_" + numBox);
	var table_message		= $("#table_message");

	// Size window innerHeight and innerTop
	var innerH = $(window).innerHeight() - 10;
	var innerW = $(window).innerWidth();

	var positionDiv 	= divScrollMain.position();

	if( divScrollMain.length > 0 )
	{
		if( $('#conversation-bar-container').children(":visible").length > 0)
			divScrollMain.css("height", ( innerH - ( positionDiv.top  + table_message.height() ) ) - 38 );
		else
			divScrollMain.css("height", innerH - ( positionDiv.top  + table_message.height() ) - 2 );
		
		divScrollMain.css("width", ( innerW - ( positionDiv.left + 5 ) ) );
	}	

	if( content_folders.length > 0 )
	{
		var positionContent = content_folders.position();

		if( content_messenger.find("div#_menu").length > 0 )
		{
			var _heightBrowser = 0;
			
			// FIREFOX
			if( $.browser.mozilla )
			{
				_heightBrowser = ( parseInt($.browser.version) > 15 ) ? _heightBrowser = innerH - 335 : _heightBrowser = innerH - 330 ;
			}

			// CHROME
			if( $.browser.chrome )
			{
				_heightBrowser = innerH - 341;
			}

			// MSIE
			if( $.browser.msie )
			{
				_heightBrowser = innerH - 332;
			}

			if( BordersArray.length > 1 )
			{
				content_folders.css("height", innerH - ( positionContent.top + table_message.height() + 27 ) );
			}
			else
			{
				content_folders.css("height", innerH - ( positionContent.top + table_message.height() + 15 ) );
			}

			content_messenger.find("div ul.chat-list").css("height", _heightBrowser );
		}		
		else
		{
			content_folders.css("height", innerH - ( positionContent.top + table_message.height() ) - 2 );
		}
	}

	if( typeof(BordersArray) != "undefined" )
	{
		for( var i = 1; BordersArray.length > 1 && i < BordersArray.length; i++ )
		{
			var position = null;

			if( $("#content_id_"+BordersArray[i].sequence).length > 0 )
			{
				position = $("#content_id_"+BordersArray[i].sequence).position();
				
				//Set Height
				$("#content_id_"+BordersArray[i].sequence).css("height",innerH - ( position.top + table_message.height() + 2 ) );

				//Set Width
				$("#content_id_"+BordersArray[i].sequence).css("width", innerW - ( position.left + 10 ) );			

				position = null;
			}

			if( $("#div_message_scroll_"+BordersArray[i].sequence).length > 0 )
			{
				position = $("#div_message_scroll_"+BordersArray[i].sequence).position();

				//Set Height
				$("#div_message_scroll_"+BordersArray[i].sequence).css("height",innerH - ( position.top + table_message.height() + 5 ) );

				//Set Width
				$("#div_message_scroll_"+BordersArray[i].sequence).css("width", innerW - ( position.left + 15 ) );			

				position = null;
			}
		}
	}

	redim_borders();
}
// END: FUNCTION RESIZE WINDOW

var _beforeunload_ = window.onbeforeunload;

window.onbeforeunload = function()
{
	// Terminates the connection messenger
    if( $("#content_messenger").length > 0 )
    {
		$.xmpp.disconnect();
    }

	return unloadMess();
}

function unloadMess()
{
	if (typeof BordersArray == 'undefined'){
		return; // We're not on expressoMail
	} else {
		var mess = get_lang("Your message has not been sent and will be discarted.");
		for (var i = 0; i < BordersArray.length; i++) {
			var body = Element('body_' + BordersArray[i].sequence);
			if (body && body.contentWindow && body.contentWindow.document.designMode.toLowerCase() == 'on') {
				return mess;
			}
		}
	}
}

// Translate words and phrases using user language from eGroupware.
function get_lang(_key) {
	if (typeof(_key) == 'undefined')
		return false;
	var key = _key.toLowerCase();
	if(array_lang[key])
		var _value = array_lang[key];
	else
		var _value = _key+"*";

	if(arguments.length > 1)
		for(j = 1; typeof(arguments[j]) != 'undefined'; j++)
			_value = _value.replace("%"+j,arguments[j]);
	return _value;
}

// Make decimal round, using in size message
function round(value, decimal){
	var return_value = Math.round( value * Math.pow( 10 , decimal ) ) / Math.pow( 10 , decimal );
	return( return_value );
}

// Change the class of message.
// In refresh, the flags UnRead and UnSeen don't exist anymore.
function set_msg_as_read(msg_number, selected){
	tr_message = Element(msg_number);
	if (exist_className(tr_message, 'tr_msg_unread'))
		decrement_folder_unseen();
	remove_className(tr_message, 'tr_msg_unread');
	remove_className(tr_message, 'selected_msg');
	
	if( document.getElementById("td_message_unseen_"+msg_number) != null )
		Element("td_message_unseen_"+msg_number).innerHTML = "<img src ='templates/"+template+"/images/seen.gif' title='"+get_lang('Seen')+"'>";
	
	connector.purgeCache();
	return true;
}

function msg_is_read(msg_number, selected){
	tr_message = Element(msg_number);
	return !(tr_message && LTrim(tr_message.className).match('tr_msg_unread'))
}

function set_msg_as_unread(msg_number){
	tr_message = Element(msg_number);
	if ((exist_className(tr_message, 'tr_msg_read') || exist_className(tr_message, 'tr_msg_read2')) && (!exist_className(tr_message, 'tr_msg_unread')))
		increment_folder_unseen();
	remove_className(tr_message, 'selected_msg');
	add_className(tr_message, 'tr_msg_unread');
	Element("td_message_unseen_"+msg_number).innerHTML = "<img src ='templates/"+template+"/images/unseen.gif' title='"+get_lang('Unseen')+"'>";
}

function set_msg_as_flagged(msg_number){
	var msg = Element(msg_number);
	remove_className(msg, 'selected_msg');
	add_className(msg, 'flagged_msg');
	Element("td_message_important_"+msg_number).innerHTML = "<img src ='templates/"+template+"/images/important.gif' title='"+get_lang('Important')+"'>";
}

function set_msg_as_unflagged(msg_number){
	var msg = Element(msg_number);
	remove_className(msg, 'selected_msg');
	remove_className(msg, 'flagged_msg');
	Element("td_message_important_"+msg_number).innerHTML = "&nbsp;&nbsp;&nbsp;";
}

function removeAll(id) {

	if ($("#" + id).length > 0) { $("#" + id).remove(); }
}

function get_current_folder(){
	return current_folder;
}

// Kill current box (folder or page).
function kill_current_box(){
	var box = document.getElementById("table_box");
	if (box != null)
		box.parentNode.removeChild(box);
	else
		return false;
}

// Kill current paging.
function kill_current_paging(){
	var paging = Element("span_paging");
	if (paging != null)
		paging.parentNode.removeChild(paging);
}

function show_hide_span_paging( ID ) {
	$('[id^=span_paging]').hide();
	$('#span_paging'+( ( ID == 0 )? '' : ID )).show();
}

//Get the current number of messages in a page.
function get_messages_number_in_page(){
	//Get element tBody.
	main = document.getElementById("tbody_box");

	// Get all TR (messages) in tBody.
	main_list = main.childNodes;

	return main_list.length;
}

function download_local_attachment(url) {
	url=encodeURI(url);
	url=url.replace("%25","%");
	if (div_attachment == null){
		var div_attachment = document.createElement("DIV");
		div_attachment.id="id_div_attachment";
		document.body.appendChild(div_attachment);
	}
	div_attachment.innerHTML="<iframe style='display:none;width:0;height:0' name='attachment' src='"+url+"'></iframe>";
}

// Add Input File Dynamically.
function addFile(id_border){
	divFiles = document.getElementById("divFiles_"+id_border);
	if (! divFiles)
		return false;

	if (divFiles.lastChild)
		var countDivFiles = parseInt(divFiles.lastChild.id.split('_')[2]) + 1;

	if (! countDivFiles)
		var countDivFiles = 1;

	divFile = document.createElement('DIV');


	divFile.innerHTML = "<input type='file' id_border='"+id_border+"' size='50' maxLength='255' onchange=\"validateFileExtension(this.value, this.id.replace('input','div'), this.getAttribute('id_border'))\" id='"+"inputFile_"+id_border+"_"+countDivFiles+"' name='file_"+countDivFiles+"'>";


	var linkFile = document.createElement("A");
	linkFile.id = "linkFile_"+id_border+"_"+countDivFiles;
	linkFile.href='javascript:void(0)';
	linkFile.onclick=function () {removeFile(this.id.replace("link","div")); return false;};
	linkFile.innerHTML=get_lang("Remove");
	//divFile.innerHTML += "&nbsp;&nbsp;";
	divFile.appendChild(linkFile);
	divFile.id = "divFile_"+id_border+"_"+countDivFiles;
	divFiles.appendChild(divFile);



	return document.getElementById("inputFile_"+id_border+"_"+countDivFiles);
}
//	Remove Input File Dynamically.
function removeFile(id){
	var border_id = id.substr(8,1);
	var el = Element(id);
	el.parentNode.removeChild(el);
}

function validateFileExtension(fileName, id, id_border){

	var error_flag  = false;

	if ( fileName.indexOf('/') != -1 )
	{
		if (fileName[0] != '/'){ // file name is windows format?
			var file = fileName.substr(fileName.lastIndexOf('\\') + 1, fileName.length);
			if ((fileName.indexOf(':\\') != 1) && (fileName.indexOf('\\\\') != 0)) // Is stored in partition or a network file?
				error_flag = true;
		}
		else // is Unix
			var file = fileName.substr(fileName.lastIndexOf('/') + 1, fileName.length);
	}
	else  // is Firefox 3
		var file = fileName;

	var fileExtension = file.split(".");
	fileExtension = fileExtension[(fileExtension.length-1)];
	for(var i=0; i<denyFileExtensions.length; i++)
	{
		if(denyFileExtensions[i] == fileExtension)
		{
			error_flag = true;
			break;
		}

	}

	if ( error_flag == true )
	{
		alert(get_lang('File extension forbidden or invalid file') + '.');
		removeFile(id);
		addFile(id_border);
		return false;
	}
	return true;
}

var setTimeout_write_msg = 0;
var old_msg = false;	
// Funcao usada para escrever mensagem
// notimeout = True : mensagem nao apaga
function write_msg(msg, notimeout){

	if (setTimeout_write_msg)
		clearTimeout(setTimeout_write_msg);

	var msg_div = Element('em_div_write_msg');
	var old_divStatusBar = Element("divStatusBar");

	if(!msg_div) {
		msg_div = document.createElement('DIV');
		msg_div.id = 'em_div_write_msg';
		msg_div.className = 'em_div_write_msg';
		old_divStatusBar.parentNode.insertBefore(msg_div,old_divStatusBar);
	}

	msg_div.innerHTML = '<table width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><th width="40%"></th><th noWrap class="action_info_th">'+msg+'</th><th width="40%"></th></tr></tbody></table>';

	old_divStatusBar.style.display = 'none';
	msg_div.style.display = '';
	// Nao ponha var na frente!! jakjr
	handle_write_msg = function(){
		try{
			if(!old_msg)
				clean_msg();
			else
				write_msg(old_msg, true);
		}
		catch(e){}
	}
	if(notimeout)
		old_msg = msg;
	else
		setTimeout_write_msg = setTimeout("handle_write_msg();", 5000);
}
// Funcao usada para apagar mensagem sem timeout
function clean_msg(){
	old_msg = false;
	var msg_div = Element('em_div_write_msg');
	var old_divStatusBar = Element("divStatusBar");
	if(msg_div)
		msg_div.style.display = 'none';
	old_divStatusBar.style.display = '';
}

function make_body_reply(body, to, date_day, date_hour){
	to = to.replace("<","&lt;");
	to = to.replace(">","&gt;");
	block_quoted_body ="<br><br>";
	block_quoted_body += get_lang('At %1, %2 hours, %3 wrote:', date_day, date_hour, to);
	block_quoted_body += "<blockquote style=\"border-left: 1px solid rgb(204, 204, 204); margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;\">";
	block_quoted_body += body;
	block_quoted_body += "</blockquote>";
	return block_quoted_body;
}

function make_forward_body(body, from, date, subject, to, cc){
	from = from.replace(/</g,"&lt;");
	from = from.replace(/>/g,"&gt;");
	to = to.replace(/</g,"&lt;");
	to = to.replace(/>/g,"&gt;");
	var forward_body = '<BR><BR>---------- ' + get_lang('Forwarded message') + ' ----------<BR>';
	forward_body += get_lang('From') + ': ' + from + '<BR>';
	forward_body += get_lang('Date') + ': ' + date + '<BR>';
	forward_body += get_lang('Subject') + ': ' + subject + '<BR>';
	forward_body += get_lang('To') + ': ' + to+ '<BR>';
	if(cc != undefined){
		cc = cc.replace(/</g,"&lt;");
		cc = cc.replace(/>/g,"&gt;");
		forward_body += get_lang('CC') + ': ' + cc+ '<BR><BR>';
	}
	forward_body += body;
	return forward_body;
}

function emMessageSearch(e,value){
	var	e  = is_ie ? window.event : e;
	if(e.keyCode == 13) {
		search_emails(value);
	}
}

function validateEmail(email){
	if (typeof(email) != 'string')
		return false;
	var validName = /^[a-z0-9][a-z-_0-9\.]*/i;
	emailParts = email.split('@');
	return (validName.test(emailParts[0]) && validateDomain(emailParts[1]));
}
function validateDomain(domain){
	var domainReg = /^(([A-Za-z\d][A-Za-z\d-]{0,61}[A-Za-z\d]\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\])$/i;
	return (domainReg.test(domain));
}

function validateUrl(url){
	var urlReg = /([A-Za-z]{2,7}:\/\/)(.*)/i;
	urlParts = url.split(urlReg);
	return (urlParts[1].length > 4 &&  validateDomain(urlParts[2]));
}

function performQuickSearch(keyword){
	if (preferences.quick_search_default=='1')
		emQuickSearch(keyword, 'null', 'null');
	else
		search_emails(keyword);
}

function emQuickSearch(emailList, field, ID){
	var quickSearchKeyBegin;
	var quickSearchKeyEnd;
	if(expresso_offline) {
		alert(get_lang('Not allowed in offline mode'));
		return;
	}
	if ((field != 'null') && (ID != 'null'))
	{
		connector.loadScript("QuickCatalogSearch");
		if (typeof(QuickCatalogSearch) == 'undefined'){
			setTimeout(() =>{
				emQuickSearch( emailList , field , ID );
			}, 500 );
			return false;
		}
	}
	else
	{
		connector.loadScript("QuickSearchUser");
		if (typeof(QuickSearchUser) == 'undefined'){
			setTimeout('emQuickSearch("'+emailList+'", "'+field+'", "'+ID+'")',500);
			return false;
		}
	}	

	var handler_emQuickSearch = function(data)
	{
		window_DropDownContacts = Element('tipDiv');
		if (window_DropDownContacts.style.visibility != 'hidden'){
			window_DropDownContacts.style.visibility = 'hidden';
		}

		if ((!data.status) && (data.error == "many results")){
			alert(get_lang('More than %1 results. Please, try to refine your search.',200));
			return false;
		}

		if (data.length > 0){
			if ((field != 'null') && (ID != 'null'))
			{
				QuickCatalogSearch.showList(data, quickSearchKeyBegin, quickSearchKeyEnd);
			}
			else
			{
				QuickSearchUser.showList(data);
			}
		}
		else
			alert(get_lang('None result was found.'));
		return true;
	}

	if ((field != 'null') && (ID != 'null'))
	{
		Element(field +'_'+ ID).focus(); //It requires for IE.
		var i = getPosition(Element(field +'_'+ ID)); //inputBox.selectionStart;
		var j = --i;

		// Acha o inicio
    	while ((j >= 0) && (emailList.charAt(j) != ',')){j--};
	    quickSearchKeyBegin = ++j;

	    // Acha o final
    	while ((i <= emailList.length) && (emailList.charAt(i) != ',')){i++};
	    quickSearchKeyEnd = i;

	    // A Chave da Pesquisa
    	var search_for = trim(emailList.substring(quickSearchKeyBegin, quickSearchKeyEnd));
	}
	else
		var search_for = emailList;

	if (search_for.length < preferences.search_characters_number){
            alert(get_lang('Your search argument must be longer than %1 characters.', preferences.search_characters_number));
            return false;
	}

	cExecute ("$this.ldap_functions.quickSearch&search_for="+search_for+"&field="+field+"&ID="+ID, handler_emQuickSearch);
}

function folderbox(){
	connector.loadScript("TreeS");
	if (typeof(ttree) == 'undefined'){
		setTimeout('folderbox()',500);
		return false;
	}
	ttree.make_Window();
}

function filterbox(){
	connector.loadScript("filter");
	connector.loadScript("filters");
	if (typeof(filters) == 'undefined')
	{
		 setTimeout('filterbox()',500);
	         return false;
	}
	filters.Forms();
}

function sharebox()
{
	Ajax( '$this.imap_functions.getacl' , undefined, function(data){

		connector.loadScript("sharemailbox");

		if( typeof(sharemailbox) == 'undefined' )
		{
			setTimeout('sharebox()',500);

			return false;
		}

		sharemailbox.makeWindow( data );
	});
}

function open_rss(param){
	connector.loadScript("news_edit");
	if (typeof(news_edit) == 'undefined')
	{
		setTimeout('open_rss(\''+param+'\')',500);
		return false;
	}
	news_edit.read_rss(param);
	return true;
}

function editrss()
{
    connector.loadScript("news_edit");

    if (typeof(news_edit) == 'undefined')
    {
            setTimeout('editrss()',500);
            return false;
    }
    news_edit.makeWindow();
}


function preferences_mail(){
	location.href="../preferences/preferences.php?appname=expressoMail1_2";
}

function search_emails(value){
	connector.loadScript("TreeS");
	connector.loadScript("search");
	if (typeof(EsearchE) == 'undefined' || typeof(ttree) == 'undefined'){
		setTimeout('search_emails("'+value+'")',500);
		return false;
	}
	EsearchE.showForms(value);
}

function url_encode(str){
    var hex_chars = "0123456789ABCDEF";
    var noEncode = /^([a-zA-Z0-9\_\-\.])$/;
    var n, strCode, hex1, hex2, strEncode = "";

    for(n = 0; n < str.length; n++) {
        if (noEncode.test(str.charAt(n))) {
            strEncode += str.charAt(n);
        } else {
            strCode = str.charCodeAt(n);
            hex1 = hex_chars.charAt(Math.floor(strCode / 16));
            hex2 = hex_chars.charAt(strCode % 16);
            strEncode += "%" + (hex1 + hex2);
        }
    }
    return strEncode;
}

function url_decode(str) {

	var n, strCode, strDecode = "";
	for (n = 0; n < str.length; n++) {
            strDecode += str.charAt(n);
	    //if (str.charAt(n) == "%") {
	    //    strCode = str.charAt(n + 1) + str.charAt(n + 2);
	    //    strDecode += String.fromCharCode(parseInt(strCode, 16));
	    //    n += 2;
	    //} else {
	    //    strDecode += str.charAt(n);
	    //}
	}
	return strDecode;
}

function Element (el) {
	return	document.getElementById(el);
}

function getPosition(obj)
{
	if(typeof obj.selectionStart != "undefined")
	{
    	return obj.selectionStart;
	}
	else if(document.selection && document.selection.createRange)
	{
		var M = document.selection.createRange();
		try
		{
			var Lp = M.duplicate();
			Lp.moveToElementText(obj);
		}
		catch(e)
		{
			var Lp=obj.createTextRange();
		}

		Lp.setEndPoint("EndToStart",M);
		var rb=Lp.text.length;

		if(rb > obj.value.length)
		{
			return -1;
		}
		return rb;
	}
}

function trim(inputString) {
   if (typeof inputString != "string")
   	return inputString;

   var retValue = inputString;
   var ch = retValue.substring(0, 1);
   while (ch == " ") {
	  retValue = retValue.substring(1, retValue.length);
	  ch = retValue.substring(0, 1);
   }
   ch = retValue.substring(retValue.length-1, retValue.length);
   while (ch == " ") {
	  retValue = retValue.substring(0, retValue.length-1);
	  ch = retValue.substring(retValue.length-1, retValue.length);
   }
   while (retValue.indexOf("  ") != -1) {
	  retValue = retValue.substring(0, retValue.indexOf("  ")) + retValue.substring(retValue.indexOf("  ")+1, retValue.length);
   }
   return retValue;
}

function increment_folder_unseen(){
	var folder_id = get_current_folder();

	var folder_unseen = Element('dftree_'+folder_id+'_unseen');
	var abas_unseen = Element('new_m').innerHTML;
    abas_unseen = abas_unseen.match(/(<font.*?>){0,1} *([0-9]+) *(<\/font>){0,1}/)[2];

	if (folder_unseen)
		folder_unseen.innerHTML = (parseInt(folder_unseen.innerHTML) + 1);
	else
	{
		tree_folders.getNodeById(folder_id).alter({caption: tree_folders.getNodeById(current_folder).caption + '<font style=color:red>&nbsp(</font><span id="dftree_'+current_folder+'_unseen" style=color:red>1</span><font style=color:red>)</font>'});
		tree_folders.getNodeById(folder_id)._refresh();
	}

	if( abas_unseen == NaN || abas_unseen == undefined )
		abas_unseen = 1;
	else
		abas_unseen = parseInt(abas_unseen) + 1;

	Element('new_m').innerHTML = '<font style="color:red">' + abas_unseen + '</font>';
	
	if ( current_folder.indexOf( 'INBOX' ) !== 0 && current_folder.indexOf( 'local_' ) !== 0 )
	{
		var display_unseen_in_shared_folders = Element('dftree_user_unseen');
		if ( display_unseen_in_shared_folders )
			tree_folders.getNodeById( 'user' ).alter({caption:'<font style=color:red>[</font><span id="dftree_user_unseen" style="color:red">' + ( parseInt( display_unseen_in_shared_folders.innerHTML) + 1 ) + '</span><font style=color:red>]</font>' + get_lang("Shared folders")});
		else
			tree_folders.getNodeById( 'user' ).alter({caption:'<font style=color:red>[</font><span id="dftree_user_unseen" style="color:red">1</span><font style=color:red>]</font>' + get_lang("Shared folders")});
		tree_folders.getNodeById( 'user' )._refresh();
	}
	var display_unseen_in_mailbox = Element('dftree_root_unseen');
	if(!expresso_offline)
		var node_to_refresh = 'root';
	else
		var node_to_refresh = 'local_root';
	tree_folders.getNodeById( node_to_refresh )._refresh();
}

function decrement_folder_unseen(){
	var folder_id = get_current_folder();

	var folder_unseen = Element('dftree_'+folder_id+'_unseen');
	var abas_unseen = Element('new_m').innerHTML;
    abas_unseen = abas_unseen.match( /(<font.*?>){0,1} *([0-9]+) *(<\/font>){0,1}/)[2];

	if(!folder_unseen || !abas_unseen)
		return;

	if ((folder_unseen) && (parseInt(folder_unseen.innerHTML) > 1))
	{
		folder_unseen.innerHTML = (parseInt(folder_unseen.innerHTML) - 1);
	}
	else if (parseInt(folder_unseen.innerHTML) <= 1)
	{
		var tmp_folder_name = tree_folders.getNodeById(folder_id).caption.split('<');
		var folder_name = tmp_folder_name[0];
		tree_folders.getNodeById(folder_id).alter({caption: folder_name});
		tree_folders.getNodeById(folder_id)._refresh();
	}
	if (parseInt(abas_unseen) > 1) {
        Element('new_m').innerHTML = '<font style="color:red">' + (parseInt(abas_unseen) - 1) + '</font>';
	} else {
		Element('new_m').innerHTML = '0';
	}
	if ( current_folder.indexOf( 'INBOX' ) !== 0 )
	{
		var display_unseen_in_shared_folders = Element('dftree_user_unseen');
		if ( display_unseen_in_shared_folders )
		{
			var unseen_in_shared_folders = parseInt( display_unseen_in_shared_folders.innerHTML );
			unseen_in_shared_folders--;
			if ( unseen_in_shared_folders > 0 )
				tree_folders.getNodeById( 'user' ).alter({caption:'<font style=color:red>[</font><span id="dftree_root_unseen" style="color:red">' + unseen_in_shared_folders + '</span><font style=color:red>]</font>' + get_lang("My Folders")});
			else
				tree_folders.getNodeById( 'user' ).alter({caption:get_lang("Shared folders")});
			tree_folders.getNodeById( 'user' )._refresh();
		}
	}
	var display_unseen_in_mailbox = Element('dftree_root_unseen');
	if ( display_unseen_in_mailbox )
	{
		var unseen_in_mailbox = parseInt( display_unseen_in_mailbox.innerHTML );
		unseen_in_mailbox--;
		//if ( unseen_in_mailbox > 0 )
		//	tree_folders.getNodeById( 'root' ).alter({caption:'<font style=color:red>[</font><span id="dftree_root_unseen" style="color:red">' + unseen_in_mailbox + '</span><font style=color:red>]</font>' + get_lang("My Folders")});
		//else
		if(!expresso_offline)
			var node_to_refresh = 'root';
		else
			var node_to_refresh = 'local_root';
		tree_folders.getNodeById( node_to_refresh ).alter({caption:get_lang("My Folders")});
		tree_folders.getNodeById( node_to_refresh )._refresh();
	}
}

function LTrim(value){
	var w_space = String.fromCharCode(32);
	var strTemp = "";
	var iTemp = 0;

	var v_length = value ? value.length : 0;
	if(v_length < 1)
		return "";

	while(iTemp < v_length){
		if(value && value.charAt(iTemp) != w_space){
			strTemp = value.substring(iTemp,v_length);
			break;
		}
		iTemp++;
	}
	return strTemp;
}

//changes MENU background color.
function set_menu_bg(menu)
{
	menu.style.backgroundColor = 'white';
	menu.style.border = '1px solid black';
	menu.style.padding = '0px 0px';
}
//changes MENU background color.
function unset_menu_bg(menu)
{
	menu.style.backgroundColor = '';
	menu.style.border = '0px';
	menu.style.padding = '1px 0px';
}

function array_search(needle, haystack) {
	var n = haystack.length;
	for (var i=0; i<n; i++) {
		if (haystack[i]==needle) {
			return true;
		}
	}
	return false;
}

function lang_folder(fn) {
 	if (fn.toUpperCase() == "INBOX") return get_lang("Inbox");
 	if (special_folders[fn] && typeof(special_folders[fn]) == 'string') {
 		return get_lang(special_folders[fn]);
 	}
 	return fn;
}

function add_className(obj, className){
	if (obj && !exist_className(obj, className))
		obj.className = obj.className + ' ' + className;
}

function remove_className(obj, className){
	var re = new RegExp("\\s*"+className);
	if (obj)
		obj.className = obj.className.replace(re, ' ');
}

function exist_className(obj, className){
	return ( obj && obj.className.indexOf(className) != -1 )
}

function select_all_messages(select)
{
	var listEmails = $("#tbody_box")[0].childNodes;

	if( listEmails.length > 1 ){
		for( i = 0; i < listEmails.length; i++ )
		{
			if (select) {
				$("#check_box_message_"+listEmails[i].id).attr('checked',true);
				add_className($("#"+listEmails[i].id)[0], 'selected_msg');
			} else {
				$("#check_box_message_"+listEmails[i].id).attr('checked',false);
				remove_className( $("#"+listEmails[i].id)[0], 'selected_msg');
			}
		}
	}
}

function borkb(size){
	kbyte = 1024;
	mbyte = kbyte*1024;
	gbyte = mbyte*1024;
	if (!size)
		size = 0;
	if (size < kbyte)
		return size + ' B';
	else if (size < mbyte)
		return parseInt(size/kbyte) + ' KB';
	else if (size < gbyte)
		if (size/mbyte > 100)
			return (size/mbyte).toFixed(0) + ' MB';
		else
			return (size/mbyte).toFixed(1) + ' MB';
	else
		return (size/gbyte).toFixed(1) + ' GB';
}

function validate_date(date){
    if (date.match(/^[0-3][0-9]\/[0-1][0-9]\/\d{4,4}$/))
    {
        tmp = date.split('/');

        day = new Number(tmp[0]);
        month = new Number(tmp[1]);
        year = new Number(tmp[2]);
        if (month >= 1 && month <= 12 && day >= 1 && day <= 31)
        {
            if (month == 02 && day <= 29)
            {
                return true;
            }
            return true;
        }
        else
            {
                return false;
            }
    }
    else
        {
            return false;
        }
}

function dateMask(inputData, e){
	if(document.all) // Internet Explorer
		var tecla = event.keyCode;
	else //Outros Browsers
		var tecla = e.which;

	if(tecla >= 47 && tecla < 58){ // numeros de 0 a 9 e "/"
		var data = inputData.value;
		if (data.length == 2 || data.length == 5){
			data += '/';
			inputData.value = data;
		}
	} else {
		if(tecla == 8 || tecla == 0) // Backspace, Delete e setas direcionais(para mover o cursor, apenas para FF)
			return true;
		else
			return false;
	}
}
var Catalog = new function() {
	
	var _xhr             = null;
	var _timeout         = null;
	var _lastTimeout     = null;
	var _lastSearchValue = null;
	var _minChars        = 3;
	
	this.search = function( type, value, afterCallback, beforeCallback, delay ) {
		if ( delay == undefined ) delay = 1500;
		if ( value != _lastSearchValue || ( delay == 0 && _lastTimeout != 0 ) || type == 'get' ) {
			clearTimeout( _timeout );
			_timeout = setTimeout( function(){
				Catalog.searchDispatch( type, value, beforeCallback, afterCallback, delay );
			}, delay );
		}
		return this;
	};
	
	this.searchDispatch = function( type, value, beforeCallback, afterCallback, delay ) {
		if ( value && value.length >= _minChars ) {
			if ( _xhr && _xhr.readystate != 4 ) _xhr.abort();
			if ( beforeCallback ) beforeCallback();
			_lastSearchValue = value;
			_lastTimeout     = ( type == 'get' )? 10 : delay;
			var params = { action: '$this.ldap_functions.simpleSearch' };
			if ( type == 'get' || type == 'search' ) params[type] = value;
			if ( delay == 0 ) params['timelimit'] = 0;
			_xhr = $.ajax( {
				url: 'controller.php',
				dataType: 'json',
				data: params,
				success: afterCallback
			} );
		}
		_timeout = null;
		return this;
	};
}

