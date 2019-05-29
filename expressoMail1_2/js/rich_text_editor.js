function cRichTextEditor(){
	this.emwindow   = new Array;
	this.editor = "body_1";
	this.table = "";
	this.id = "1";
	this.buildEditor();
	this.saveFlag = 0;
}

cRichTextEditor.prototype.loadEditor = function(ID) {
	var _this = this;
	_this.id = ID;
	parentDiv = document.getElementById("body_position_"+this.id);
	this.editor = "body_"+this.id;

	if(this.table.parentNode)
		this.table.parentNode.removeChild(this.table);

	$(parentDiv).prepend( this.table );

	$(this.table).toggle( !$('#textplain_rt_checkbox_'+this.id).is(':checked') );

	if(!Element(this.editor))
	{
		this.createElementEditor(this.editor);
	}

	document.getElementById('fontname').selectedIndex = 0;
	document.getElementById('fontsize').selectedIndex = 0;
}

cRichTextEditor.prototype.createElementEditor = function ( iframe_id  ) {

	$('#body_position_'+this.id).append(
		$('<iframe>')
		.attr({ 'id': iframe_id, 'name': iframe_id, 'unselectable': 'on', 'tabIndex': '1', 'frameborder': '1' })
		.css({ 'width': 'calc( 100% - 4px )', 'height': 'calc( 100% - 32px )' })
		.on('load',function(e){
			var doc = e.currentTarget.contentWindow.document;
			if ( doc.body && doc.body.contentEditable ) {
				if ( mobile_device ) doc.body.contentEditable = true;
				else doc.designMode = 'on';
			}
			$(doc).css({ 'background': '#fff', 'fontSize': '16px' });
		})
	);
}

cRichTextEditor.prototype.loadStyle = function(tag, css_file) {
	var theRules = new Array();
	var stylePRE = "";	
	for(var s = 0; s < document.styleSheets.length; s++) {
		if(document.styleSheets[s].href != null && 
				document.styleSheets[s].href.match("templates/"+template+"/"+css_file)){			
			if (document.styleSheets[s].cssRules)
				theRules = document.styleSheets[s].cssRules;
			else if (document.styleSheets[s].rules)
				theRules = document.styleSheets[s].rules;
			break;
		}
	}
	for(var s = 0;s < theRules.length; s++){
		if(theRules[s].selectorText.toLowerCase() == tag.toLowerCase()){			
			stylePRE = theRules[s].style;
			break;
		}
	}
	var _body = Element(this.editor);
	var i_doc = (document.all) ? _body.contentWindow.document: _body.contentDocument;
	var hh1 = i_doc.getElementsByTagName('head')[0];
	// For IE
	if(typeof(hh1) == 'undefined'){
		hh1 = i_doc.createElement("head");
		i_doc.appendChild(hh1);
	}
	var ss1 = i_doc.createElement('style');	
	ss1.setAttribute("type", "text/css"); 
	var def = tag.toLowerCase()+' {'+stylePRE.cssText+'}';
	if (ss1.styleSheet) { 
	    ss1.styleSheet.cssText = def;
	} else {
	    var tt1 = i_doc.createTextNode(def);
	    ss1.appendChild(tt1);
	}
	hh1.appendChild(ss1);
}

cRichTextEditor.prototype.stripHTML = function( string ) {
	return string
		.replace(/<style([\s\S]*?)<\/style>/gi, '')
		.replace(/<script([\s\S]*?)<\/script>/gi, '')
		.replace(/([^>])<div/ig,'$1\n<div')
		.replace(/<\/div>/ig, '\n')
		.replace(/<\/li>/ig, '\n')
		.replace(/<li>/ig, '  *  ')
		.replace(/<\/ul>/ig, '\n')
		.replace(/<\/p>/ig, '\n')
		.replace(/<br\s*[\/]?>/gi, "\n")
		.replace(/<iframe([\s\S]*?)use_signature_anchor([\s\S]*?)<\/iframe>/gi, '€@€')
		.replace(/<[^>]+>/ig, '')
		.replace(/\ufeff/g, '')
		.split('€@€');
}

cRichTextEditor.prototype.plain = function( to_plain, silent ) {

	silent = ( typeof silent != 'undefined' )? silent : false;

	var $bdy_doc = $('iframe#body_'+this.id).contents();

	if ( to_plain ) {
		if ( ( !silent ) &&( !mobile_device ) && !( $('#textplain_rt_checkbox_'+this.id)[0].checked = confirm( get_lang( 'The text format will be lost' ) + '.' ) ) )
			return false;
		$(this.table).find('img#signature').siblings().hide()
		var arr = this.stripHTML( $bdy_doc.find('body').get(0).innerHTML );
		var pre = $('<pre>')
			.append(arr.shift())
			.append($bdy_doc.find('iframe#use_signature_anchor').detach())
			.append(arr.join(''));
		$bdy_doc.find('body').empty().append(pre);
	} else {
		$(this.table).find('img#signature').siblings().show();
		var html = $bdy_doc.find('pre').html().replace(/\r?\n/gi,'</br>');
		$bdy_doc.find('body').empty().html( html );
	}
	SignatureFrame.redrawOnCaret( $('iframe#body_'+this.id) );
}

cRichTextEditor.prototype.buildEditor = function() {
	this.table = document.createElement("TABLE");
	this.table.id = "table_richtext_toolbar";
	this.table.className = "richtext_toolbar";
	this.table.width = "100%";
	var tbody = document.createElement("TBODY");
	var tr = document.createElement("TR");
	var td = document.createElement("TD");
	var div_button_rt = document.createElement("DIV");
	
	selectBox=document.createElement("SELECT");
	selectBox.id="fontname";
	selectBox.setAttribute("tabIndex","-1");
	selectBox.onchange = function () {RichTextEditor.Select("fontname");};
	selectBox.className = 'select_richtext';
	var option1 = new Option(get_lang('Font'), 'Font');
	var option2 = new Option('Arial', 'Arial');
	var option3 = new Option('Courier', 'Courier');
	var option4 = new Option('Times New Roman', 'Times');
	if (is_ie){
		selectBox.add(option1);
		selectBox.add(option2);
		selectBox.add(option3);
		selectBox.add(option4);
	}
	else{
		selectBox.add(option1, null);
		selectBox.add(option2, null);
		selectBox.add(option3, null);
		selectBox.add(option4, null);
	}
	div_button_rt.appendChild(selectBox);

	selectBox=document.createElement("SELECT");
	selectBox.id="fontsize";
	selectBox.setAttribute("tabIndex","-1");
	selectBox.setAttribute("unselectable","on");
	selectBox.className = 'select_richtext';
	selectBox.onchange = function () {RichTextEditor.Select("fontsize");};
	var option1 = new Option(get_lang('Size'), 'Size');
	var option2 = new Option('1 (8 pt)','1' );
	var option3 = new Option('2 (10 pt)','2');
	var option4 = new Option('3 (12 pt)','3');
	var option5 = new Option('4 (14 pt)','4');
	var option6 = new Option('5 (18 pt)','5');
	var option7 = new Option('6 (24 pt)','6');
	var option8 = new Option('7 (36 pt)','7');
	if (is_ie){
		selectBox.add(option1);
		selectBox.add(option2);
		selectBox.add(option3);
		selectBox.add(option4);
		selectBox.add(option5);
		selectBox.add(option6);
		selectBox.add(option7);
		selectBox.add(option8);
	}
	else{
		selectBox.add(option1, null);
		selectBox.add(option2, null);
		selectBox.add(option3, null);
		selectBox.add(option4, null);
		selectBox.add(option5, null);
		selectBox.add(option6, null);
		selectBox.add(option7, null);
		selectBox.add(option8, null);	
	}
	div_button_rt.appendChild(selectBox);
	
	var buttons = ['bold', 'italic', 'underline', 'forecolor', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull',
				   'undo', 'redo', 'insertorderedlist', 'insertunorderedlist', 'outdent', 'indent', 'link', 'image', 'table', 'signature'];

	for (var i=0; i<buttons.length; i++){
		var img = document.createElement("IMG");
		img.id = buttons[i];
		img.className = 'imagebutton';
		img.align = 'center';
		img.src = './templates/'+template+'/images/'+buttons[i]+'.gif';
		img.title = get_lang(buttons[i]);
		img.style.cursor = 'pointer';

		if (buttons[i] == 'forecolor')
			img.onclick = function () {RichTextEditor.show_pc('forecolor')};
		else if (buttons[i] == 'link')
			img.onclick = function () {RichTextEditor.createLink();};
		else if (buttons[i] == 'image')
			img.onclick = function () {RichTextEditor.createImage();};
		else if (buttons[i] == 'table')
			img.onclick = function () {RichTextEditor.createTable();};
		else
			img.onclick = function () {RichTextEditor.editorCommand(this.id,'');};
		
		img.onmouseover = function () {this.style.border="outset 2px";};
		img.onmouseout = function () {this.style.border="solid 2px #C0C0C0";};
		div_button_rt.appendChild(img);
	}
	if(preferences.use_SpellChecker != '0'){
	    selectBox=document.createElement("SELECT");
	    selectBox.id="selectLanguage";
	    selectBox.setAttribute("tabIndex","-1");
	    selectBox.setAttribute("unselectable","on");
	    selectBox.className = 'select_richtext';
	    selectBox.onchange = function () {RichTextEditor.Select("selectLanguage");};
	    var option1 = new Option(get_lang("Portuguese"),"pt_BR" );
	    option1.selected = true;
	    var option2 = new Option(get_lang("English"),'en');
	    var option3 = new Option(get_lang("Spanish"),'es');
	    if (is_ie){
		    selectBox.add(option1);
		    selectBox.add(option2);
		    selectBox.add(option3);
	    }
	    else{
		    selectBox.add(option1, null);
		    selectBox.add(option2, null);
		    selectBox.add(option3, null);
	    }
	    div_button_rt.appendChild(selectBox);

	    // spellCheck button
	    var img = document.createElement("IMG");
	    img.id = "spellCheck";
	    img.className = 'imagebutton';
	    img.align = 'center';
	    img.src = './templates/'+template+'/images/'+img.id+'.gif';
	    img.title = get_lang(img.id);
	    img.style.cursor = 'pointer';
	    img.onclick = function () {RichTextEditor.editorCommand(this.id,'');};
	    img.onmouseover = function () {this.style.border="outset 2px";};
	    img.onmouseout = function () {this.style.border="solid 2px #C0C0C0";};
	    div_button_rt.appendChild(img);
	}


	td.appendChild(div_button_rt);
	tr.appendChild(td);
	tbody.appendChild(tr);
	this.table.appendChild(tbody);
}

cRichTextEditor.prototype.editorCommand = function(command, option) {
	try {
		var mainField = document.getElementById(this.editor).contentWindow;
		mainField.focus();
		if ( command == 'signature' ) {
			var ID = this.editor.replace( 'body_', '' );
			$('select#from_'+ID).find(':selected').data( 'use_signature', '1' );
			$('iframe#body_'+ID).contents().find('iframe#use_signature_anchor').remove();
			SignatureFrame.redrawOnCaret( $('iframe#body_'+ID) );
		}
		else if (command == 'CreateLink')
			mainField.document.execCommand('CreateLink', false, option);
		else if (command == 'Table'){
			if (is_ie){
				var sel = document.selection;
				if (sel!=null)
				{
				    var rng = sel.createRange();
				    if (rng!=null)
			        rng.pasteHTML(option);
				}
			}
			else 
				mainField.document.execCommand('inserthtml', false, option);
			}
		else if (command == 'Image')
			mainField.document.execCommand('InsertImage', false, option);
                else if (command == 'spellCheck' && preferences.use_SpellChecker != '0'){
                        beginSpellCheck(); // configure
                        spellCheck(); // run spellChecker
                }
		else
			mainField.document.execCommand(command, false, option);
		//mainField.focus();
    } catch (e) {/* alert(e);*/ }
}

cRichTextEditor.prototype.createLink = function(){
	var mainField = document.getElementById(this.editor).contentWindow;
	if (is_ie){
		if ((mainField.document.selection.createRange().text) == ''){
				alert(get_lang('Chose the text you want transform in link before.')); 
			return;
		}
	}
	else{
		if (mainField.window.getSelection() == ''){
				alert(get_lang('Chose the text you want transform in link before.')); 
			return;
		}
	}
		var szURL = prompt(get_lang('Enter with link URL:'), 'http://'); 
	if ((szURL != null) && (szURL != "")){
		this.editorCommand("CreateLink", szURL);
	}
}

// It include the image file in emails body
// It saves and attach in drafts folder and open it
cRichTextEditor.prototype.addInputFile = function()
{
	//Begin: Verify if the image extension is allowed.
	var imgExtensions = new Array("jpeg", "jpg", "gif", "png", "bmp", "xbm", "tiff", "pcx");
	var inputFile = document.getElementById('inputFile_img');	
	if(!inputFile.value) return false;
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
	// End: Verify image extension.
	var id = this.editor.substr(5); // border_id
	divFiles = document.getElementById("divFiles_"+id);
	var countDivFiles = divFiles.childNodes.length + 1;

	var divFiles = document.getElementById('divFiles_'+id);
	inputFile.id = 'inputFile_'+id +"_"+countDivFiles;
	inputFile.name = 'file_'+countDivFiles;
	divFile = document.createElement('DIV');
	divFile.appendChild(inputFile);
	divFiles.appendChild(divFile);

	var form_upload = document.getElementById('form_upload');
	form_upload.parentNode.removeChild(form_upload);
	win.close();

	RichTextEditor.saveFlag = 0; // See if save function finished
	var save_link = document.getElementById("save_message_options_"+id);
	save_msg(id,true);
	RichTextEditor.insertImgHtml(id);
}

cRichTextEditor.prototype.insertImgHtml = function (id)
{
	if ( RichTextEditor.saveFlag == 0 )
        {
            setTimeout( function(){ RichTextEditor.insertImgHtml(id); },1000 );
        }
	else
        {
            if ( RichTextEditor.saveFlag == 1 )
            {
                var folderNameDraft = "INBOX" + cyrus_delimiter + draftsfolder;
                this.editorCommand('Image', './inc/show_embedded_attach.php?msg_folder=' + folderNameDraft + '&msg_num='+openTab.imapUid[id]+'&msg_part='+(openTab.countFile[id]+1));
                openTab.toPreserve[id] = true;
                save_msg(id,true);
            }
        }
}

cRichTextEditor.prototype.insertTableHtml = function (){
	var id = this.editor.substr(5); // border_id
	var	rows = document.getElementById('rows').value;
	var	cols = document.getElementById('cols').value;
	var border = document.getElementById('border').value;
	var insertTable = '<table border="'+border+'px"><tbody>';
	for (var i = 0; i < rows; i++){
		insertTable += "<tr>";	
		for (var j = 0; j < cols; j++)
			insertTable += "<td>&nbsp;</td>";	
		insertTable += "</tr>";
	}
	insertTable += "</tbody></table>";
	this.editorCommand('Table', insertTable);
}

cRichTextEditor.prototype.createTable = function(){
	var form = document.getElementById("table_window");
	if (form == null){
		form = document.createElement("DIV");
		form.id  = "table_window";
		form.style.visibility = "hidden";
		form.style.position = "absolute";
		form.style.background = "#eeeeee";
		form.style.left = "0px";
		form.style.top  = "0px"; 
		form.style.width = "0px";
		form.style.height = "0px";
		document.body.appendChild(form);
	}
		
		var form_table = document.createElement("DIV");
		form_table.id = "form_table";
		form_table.style.position = "absolute";
		form_table.style.top = "5px";
		form_table.style.left = "5px";
		form_table.style.width = "190px";
		form_table.style.height = "90px";
		form_table.name = get_lang("Insert Table");		
		form_table.innerHTML = get_lang('Select the table size')+':<br><br><table cellspacing="0"><tbody><tr><td align="center">'+
								get_lang('Rows')+':</td><td></td><td align="center">'+get_lang('Cols')+':</td><td></td><td align="center">'+get_lang('Border')+':</td></tr>'+
									'<tr><td align="right"><input type="text" readonly="true" id="rows" size="2" maxlength="2" value="1"></input></td><td align="left"><img src="templates/'+template+'/images/plus.png" onclick="javascript:RichTextEditor.incrementField(\'rows\');"></img><br><img src="templates/'+template+'/images/minus.png" onclick="javascript:RichTextEditor.decrementField(\'rows\');"></img></td>'+
									'<td align="right"><input type="text" readonly="true" id="cols" size="2" maxlength="2" value="1"></input></td><td align="left"><img src="templates/'+template+'/images/plus.png" onclick="javascript:RichTextEditor.incrementField(\'cols\');"></img><br><img src="templates/'+template+'/images/minus.png" onclick="javascript:RichTextEditor.decrementField(\'cols\');"></img></td>'+
									'<td align="right"><input type="text" readonly="true" id="border" size="2" maxlength="2" value="1"></input></td><td align="left"><img src="templates/'+template+'/images/plus.png" onclick="javascript:RichTextEditor.incrementField(\'border\');"></img><br><img src="templates/'+template+'/images/minus.png" onclick="javascript:RichTextEditor.decrementField(\'border\');"></img></td>'+
									'</tr></tbody></table>'+
	  								'&nbsp;&nbsp;&nbsp;<input title="'+get_lang('Close')+'"  value="' + get_lang('Close') + '" type="button" onclick="win.close()">&nbsp;'+
									'<input title="' + get_lang('Include') + '"  value="' + get_lang('Include') + '" type="button" onclick="RichTextEditor.insertTableHtml();win.close();">';	
		form.appendChild(form_table);
		
		this.showWindow(form);
		}

cRichTextEditor.prototype.incrementField = function(id_val){
	var field_text = document.getElementById(id_val);
	field_text.value = parseInt(field_text.value)+1;
}

cRichTextEditor.prototype.decrementField = function(id_val){
	var field_text = document.getElementById(id_val);
	if (parseInt(field_text.value) > 0)
		field_text.value = parseInt(field_text.value)-1;
}

cRichTextEditor.prototype.createImage = function(){
	if (preferences.auto_save_draft == 1){
			autosave_time = 200000;
			clearTimeout(openTab.autosave_timer[currentTab]);
		}
	var form = document.getElementById("attachment_window");
	if (form == null){
		form = document.createElement("DIV");
		form.id  = "attachment_window";
		form.style.visibility = "hidden";
		form.style.position = "absolute";
		form.style.background = "#eeeeee";
		form.style.left = "0px";
		form.style.top  = "0px"; 
		form.style.width = "0px";
		form.style.height = "0px";
		document.body.appendChild(form);
	}
		var form_upload = Element('form_upload');
		if (form_upload == null)
			form_upload = document.createElement("DIV");
		form_upload.id = "form_upload";
		form_upload.style.position = "absolute";
		form_upload.style.top = "5px";
		form_upload.style.left = "5px";
		form_upload.name = get_lang("Upload File");
		form_upload.style.width = "550px";
		form_upload.style.height = "100px";
		form_upload.innerHTML = get_lang('Select the desired image file')+':<br>'+
                                        '<input name="image_at" maxlength="255" size="50" id="inputFile_img" type="file"><br/><br/>' +
					'<input title="' + get_lang('Include') + '"  value="' + get_lang('Include') + '"' + 'type="button" onclick="RichTextEditor.addInputFile();">&nbsp;' +
					'<input title="' + get_lang('Close') + '"  value="' + get_lang('Close') + '"' +
					' type="button" onclick="win.close()">';
                                    
		form.appendChild(form_upload);
		
		this.showWindow(form);
}
cRichTextEditor.prototype.showWindow = function (div){

		if(! div) {
			return;
		}
		
		if(! this.emwindow[div.id]) {
			div.style.width  =  div.firstChild.style.width;
			div.style.height = div.firstChild.style.height;
			div.style.zIndex = "10000";			
			var title = div.firstChild.name;
			var wHeight = div.offsetHeight + "px";
			var wWidth =  div.offsetWidth   + "px";
			div.style.width = div.offsetWidth - 5;

			win = new dJSWin({
				id: 'win_'+div.id,
				content_id: div.id,
				width: wWidth,
				height: wHeight,
				title_color: '#3978d6',
				bg_color: '#eee',
				title: title,
				title_text_color: 'white',
				button_x_img: '../phpgwapi/images/winclose.gif',
				border: true });
			
			this.emwindow[div.id] = win;
			win.draw();
		}
		else
			win = this.emwindow[div.id];
		win.open();	
}

cRichTextEditor.prototype.Select = function(selectname)
{
	var mainField = Element(this.editor).contentWindow;
	var cursel = document.getElementById(selectname).selectedIndex;

	if (cursel != 0) {
		var selected = document.getElementById(selectname).options[cursel].value;
		mainField.document.execCommand(selectname, false, selected);
		document.getElementById(selectname).selectedIndex = "Size"; //cursel;
	}
	mainField.focus();
}

cRichTextEditor.prototype.show_pc = function(command)
{
	connector.loadScript("color_palette");
	ColorPalette.loadPalette(this.id);
  	if (ColorPalette.div.style.visibility != "visible")
		ColorPalette.div.style.visibility="visible";
	else
		this.hide_pc();
}

cRichTextEditor.prototype.hide_pc = function()
{
	document.getElementById("palettecolor").style.visibility="hidden";
}

cRichTextEditor.prototype.getOffsetTop = function(elm) {
  var mOffsetTop = elm.offsetTop;1
  var mOffsetParent = elm.offsetParent;
  while(mOffsetParent){
    mOffsetTop += mOffsetParent.offsetTop;
    mOffsetParent = mOffsetParent.offsetParent;
  }
  return mOffsetTop;
}

cRichTextEditor.prototype.getOffsetLeft = function(elm) {
  var mOffsetLeft = elm.offsetLeft;
  var mOffsetParent = elm.offsetParent;
  while(mOffsetParent){
    mOffsetLeft += mOffsetParent.offsetLeft;
    mOffsetParent = mOffsetParent.offsetParent;
  }
  return mOffsetLeft;
}

//Build the Object
RichTextEditor = new cRichTextEditor();
