var BordersArray = new Array();
BordersArray[0] = new setBorderAttributes(0);

$(document).ready(function() {
	$('.button-border').click(step_border);
});

function step_border(event)
{
	if ($("#border_tr td[id^=border_id_]:not(:first):visible:last").length == 0)
		$("#border_tr td[id^=border_id_]:not(:first):last").css({'display': ''});
	var lidv = $("#border_tr td[id^=border_id_]:not(:first):visible:last");
	if ( event.currentTarget.id == 'border-left' ) {
		$(lidv).prev('td[id^=border_id_]').css({'display': ''});
		$(lidv).css({'display': 'none'});
	} else {
		$(lidv).next('td[id^=border_id_]').css({'display': ''});
	}
	redim_borders();
}

function setBorderAttributes(ID)
{
	this.border_id = "border_id_"+ID;
	this.sequence = ID; 
}

function alternate_border(ID)
{
	if ( typeof win == 'object' && win.close && win.close.constructor == Function )
		win.close( );

	if (! Element('border_id_'+ID))
		return false; // Not possible to alternate
	
	show_hide_span_paging(ID);
	
	spanD = Element("span_D");
	
	if (spanD)
		spanD.style.display = (openTab.type[ID] == 0 ? '' : 'none');
	
	if( document.getElementById('divScrollMain_0') != null )
	{
		var _RSS = document.getElementById('divScrollMain_0');
	}	
	
	var footer_menu = Element("footer_menu");	
	if (footer_menu) {

		var attrRSS = _RSS.getAttribute("rss");
		
		if( attrRSS == "rss" )
		{
			footer_menu.style.display = "none";	
		}
		else
		{
			footer_menu.style.display = (openTab.type[ID] != 4 ? '' : 'none');
		}
				
		var options_search = Element('span_options');
		if (options_search){
			var spans_search = options_search.getElementsByTagName("span");
			var span_search;
			for (i = 0; i < spans_search.length; i++){
				span_search = spans_search[i];
				span_search.className == 'message_options_import'?span_search.style.display = (openTab.type[ID] == 0 ? '' : 'none'):'';
				(span_search.title  == get_lang("Archive") || span_search.title == get_lang("Unarchive"))?span_search.style.display = (openTab.type[ID] == 0 ? '' : 'none'):'';
			}
		}

	}

	var len = BordersArray.length;
	for (var i=0; i < len; i++)
	{
		m = document.getElementById(BordersArray[i].border_id);
		if ((m)&&(m.className == 'menu-sel'))
		{
			m.className = 'menu';
			c = document.getElementById("content_id_"+BordersArray[i].sequence);
			c.style.display = 'none';
			if(Element("font_border_id_"+BordersArray[i].sequence))
				Element("font_border_id_"+BordersArray[i].sequence).className = 'font-menu';	
			var body = Element('body_'+BordersArray[i].sequence);
			if (body)
			{
				try
				{
					if (Element('viewsource_rt_checkbox_' + ID).checked)
					{  
						connector.loadScript("rich_text_editor");
						RichTextEditor.viewsource(false);
					}
				}
				catch(e)
				{
					//alert(e.message)
				}
			}
		}
	}

	m = Element("border_id_"+ID);
	if (m)
		m.className = 'menu-sel';
	if(Element("font_border_id_" + ID))
		Element("font_border_id_" + ID).className = 'font-menu-sel';
	var c = Element("content_id_"+ID)
	if (c)
		c.style.display = '';

	body = document.getElementById('body_'+ ID);
	if (body){
		try{
			if(ID){
				connector.loadScript("rich_text_editor");
				if(typeof(RichTextEditor) == 'undefined'){
					setTimeout('alternate_border(\''+ID+'\');',500);
					return false;
				}
				RichTextEditor.loadEditor(ID);
			}
			body.contentWindow.document.designMode="on";
		}
		catch(e){
			//alert(e.message);
		}
	}

	// hide the DropDrowContact, if necessary
	window_DropDownContacts = Element('tipDiv');
	if ((window_DropDownContacts)&&(window_DropDownContacts.style.visibility != 'hidden')){
		window_DropDownContacts.style.visibility = 'hidden';
	}

	if (typeof(ID) == 'number')
	{
        numBox=ID;
 	}
	else
	{
		if (ID.match("search_"))
			numBox=ID.substr(7);
	}

	currentTab=ID;
	
	if( document.getElementById('to_'+ID) && document.getElementById('to_'+ID).type == "textarea"){
		document.getElementById('to_'+ID).focus();
	}
	
	redraw_mark_as_spam();
	
	return ID;
}

function redraw_mark_as_spam()
{
	var mkspam = false;
	var mknspam = false;
	if ( use_spam_filter && openTab.type[currentTab] < 4 ) {
		var folder_base  = openTab.type[currentTab] == 1 ? 'INBOX' : ( openTab.type[currentTab] == 0 ? current_folder : openTab.imapBox[currentTab] );
		var folder_spam  = 'INBOX'+cyrus_delimiter+'Spam';
		var not_is_share = folder_base.substr(0,5) === 'INBOX';
		mkspam  = not_is_share && !( folder_base === folder_spam );
		mknspam = not_is_share && !mkspam;
	}
	$('#mark_as_spam').css( 'display', mkspam? '' : 'none' );
	$('#mark_as_not_spam').css( 'display', mknspam? '' : 'none' );
}

function create_border(borderTitle, id_value, border_type, imap_folder)
{
	if (borderTitle == get_lang("Server Results"))
	{
		id_value = "search_" + parseInt(BordersArray.length-1) + 1;
	}

	if( !id_value ) // Is new message?
	{
		var ID = parseInt(String(BordersArray[(BordersArray.length-1)].sequence).replace(/^[^0-9]*/,'')) + 1;
	}
	else
	{
		if (Element("border_id_"+id_value)) // It's opened already!
			return alternate_border(id_value);
		
		var ID = id_value;
		openTab.imapBox[ID] = (imap_folder == undefined)? current_folder : imap_folder;
	}
	
	var td			= $('<td>');
	var tab			= $('<table>');
	var tr			= $('<tr>');
	var tdTitle		= $('<td>');
	var divLabel	= $('<div>');
	var tdIcon		= $('<td>');
	var imgIcon		= $('<img>');
	var div			= $('<div>');
	
	$(td)
		.attr( 'id', 'border_id_' + ID )
		.attr( 'alt', borderTitle.replace( '&nbsp;', ' ' ) )
		.attr( 'title', borderTitle )
		.click(function(){
			alternate_border(ID);
			resizeWindow()
		});
	borderTitle = borderTitle ?  borderTitle : ( id_value ? get_lang("No Subject") : "&nbsp;" );
	$(td).val( borderTitle );
	
	divLabel
		.attr( 'id', 'font_border_id_'+ID )
		.css({ 'width': '100%', 'float': '', 'margin': '0', 'padding': '0' })
		.html( borderTitle );
	$(tdTitle).css({ 'width': '100%' });
	$(tdIcon).css({ 'width': '16px' });
	
	$(imgIcon)
		.attr( 'src', './templates/'+template+'/images/close_button.gif' )
		.css({ 'cursor': 'pointer' })
		.mousedown(function(){ return false })
		.click(function(){ delete_border(ID,'false'); });
	
	$(tdIcon).append(imgIcon);
	$(tdTitle).append(divLabel);
	$(tr).append(tdTitle);
	$(tr).append(tdIcon);
	$(tab).append(tr);
	$(td).append(tab);
	$(td).insertBefore($('#border-right'));
	
	$(div)
		.attr( 'id', 'content_id_'+ID )
		.addClass( 'conteudo' )
		.css({ 'display': '', 'overflow': 'hidden' });
	
	$(div).insertBefore($('#footer_menu'));
	
	BordersArray[BordersArray.length] = new setBorderAttributes(ID);
	if( border_type ) openTab.type[ID] = border_type;
	
	alternate_border(ID);
	
	// Chrome bug fix, lazy property table-layout: fixed
	$(tab).fadeOut(0).delay(0).fadeIn(200).addClass('table-tab');
	return ID;
}

function redim_borders()
{
	$('#border_blank').css('display','');
	$('.button-border').css({'display': '', 'width': ''});
	var cols = $("#border_tr td[id^=border_id_]:not(:first)").length;
	if (cols<=0) return;
	
	var maxW = 200;
	var minW = 100;
	var area = $(window).innerWidth() - $('#border_table').position().left - $("#border_id_0").outerWidth(true) - 4;
	var size = (cols>0)? Math.floor(area/cols) : 0;
	var diff = 0;
	if ( size < minW ) {
		$('#border_blank').css('display','none');
		$('.button-border').css({'display': 'table-cell'});
		var lidv = $("#border_tr td[id^=border_id_]:not(:first):visible:last");
		if (lidv.length==0) lidv = $("#border_tr td[id^=border_id_]:not(:first):last");
		var red_area = area - ($(".button-border").outerWidth(true)*2);
		var vcols = Math.max(Math.floor(red_area/minW),1);
		size = Math.floor(red_area/vcols);
		diff = red_area - vcols*size;
		var borders = $("#border_tr td[id^=border_id_]:not(:first)");
		var idx = 0;
		var i = 0;
		for ( i = borders.length-1; i > 0 && idx == 0; i-- ) {
			if ($(borders[i]).css('display') != 'none') {
				idx = i;
			}
		}
		idx = Math.max((idx-vcols+1),0);
		for ( i = 0; i < borders.length; i++ ) {
			$(borders[i]).css('display',(i<idx||i>(idx+vcols-1))?'none':'');
		}
		
		if ($("#border_tr td[id^=border_id_]:not(:first):first").css('display') == 'none' ) $('#border-left').removeClass('disabled');
		else  $('#border-left').addClass('disabled');
		if ($("#border_tr td[id^=border_id_]:not(:first):last").css('display') == 'none' ) $('#border-right').removeClass('disabled');
		else  $('#border-right').addClass('disabled');
		
	} else if (size < maxW) {
		$("#border_tr td[id^=border_id_]:not(:first)").css({'display': ''});
		$('#border_blank').css('display','none');
		diff = area - cols*size;
	} else $("#border_tr td[id^=border_id_]:not(:first)").css({'display': ''});
	
	// Calc valid width and subtract parent padding and border (.menu-sel ,.menu { padding: 0 5px; border-width: 1px 1px 0; })
	var wth = Math.max(Math.min(size,maxW),minW)-12; 
	
	$("#border_tr td[id^=border_id_]:not(:first):visible:lt("+diff+") > table").css('width', (wth+1) +'px' );
	$("#border_tr td[id^=border_id_]:not(:first):visible:not(:lt("+diff+")) > table").css('width', (wth) +'px' );
}

function set_border_caption(border_id, title)
{
	$('#font_'+border_id).html(title);
}

function delete_border(ID, msg_sent)
{
	var borderElem = Element("border_id_" + ID)
	if (borderElem)
		borderElem.onclick = null; // It's avoid a FF3 bug
	else
		return false;

	if (msg_sent == 'false')
	{
		var body = document.getElementById('body_'+ ID);
		if (body)
		{
			var save_link = Element("save_message_options_"+ID);
			if (openTab.toPreserve[ID] == undefined)
				openTab.toPreserve[ID] = false;
			if ((! openTab.toPreserve[ID] && ! ID.toString().match("_r")) || ((body.contentWindow) == 'object' && body.contentWindow.document.designMode.toLowerCase() == 'on') && (save_link.onclick != ''))
			{
				var discard_msg = confirm(get_lang("Your message has not been sent. Discard your message?"), "");
				if (!discard_msg)
				{
					Element("border_id_"+ID).onclick = function () { alternate_border(ID);};
					return;
				}
				else
				{
					if (openTab.imapBox[ID] && openTab.imapUid[ID] && !openTab.toPreserve[ID]){
						delete_msgs(openTab.imapBox[ID], openTab.imapUid[ID].toString(), 0)
							openTab.toPreserve[ID] = false;
					}
					delete(openTab.imapBox[ID]);
					// Element('to_'+ID).focus(); It crash on IE 
				}
			}
		}
	}

	openTab.toPreserve[ID] = false;
	openTab.imapUid[ID] = 0;
	delete(openTab.type[ID]);

	if (preferences.auto_save_draft == 1)
	{
		if (openTab.autosave_timer[ID])
			clearTimeout(openTab.autosave_timer[ID]);
		openTab.autosave_timer[ID] = false;
	}

	hold_session = false;
	if (exist_className(Element('border_id_'+ID),'menu-sel'))
	{
		if (BordersArray[BordersArray.length-2].sequence == ID)
			this.alternate_border(0);
		else
			this.alternate_border(BordersArray[BordersArray.length-2].sequence);
	}

	// Remove TD, title
	border = Element('border_id_' + ID);
	border.parentNode.removeChild(border);
	var j=0;
	var new_BordersArray = new Array();
	for (i=0;i<BordersArray.length;i++)
		if (document.getElementById(BordersArray[i].border_id) != null){
			new_BordersArray[j] = BordersArray[i];
			j++;
		}
	if(j == 1)
	{	
		if( document.getElementById('divScrollMain_0') != null )
		{
			var _RSS = document.getElementById('divScrollMain_0');
		}	
		
		var attrRSS = _RSS.getAttribute("rss");
		
		if( attrRSS == "rss" )
		{
			Element("footer_menu").style.display = 'none';			
		}
		else
		{
			Element("footer_menu").style.display = '';
		}		
	}
	
	BordersArray = new_BordersArray;

	// Remove Div Content
	content = Element('content_id_' + ID);
	content.parentNode.removeChild(content);
	resizeWindow();
	return true;
}
