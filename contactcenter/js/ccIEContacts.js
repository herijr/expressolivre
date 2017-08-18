	function cIEContacts ()	{
		this.win;
		this.el;		
		this.wWidth = 610;
		this.wHeight = 255;
	}
	
	cIEContacts.prototype.changeOptions = function(type){	
		if(type == 'i') {
			Element('export_span').style.display = 'none';
			Element('import_span').style.display = '';
		}
		else{
			Element('import_span').style.display = 'none';
			Element('export_span').style.display = '';		
		}	
	}
	
	cIEContacts.prototype.showList = function(){

		if (!this.el){		
			this.el = document.createElement("DIV");
			this.el.style.visibility = "hidden";
			this.el.style.position = "absolute";
			this.el.style.left = "0px";
			this.el.style.top = "0px";
			this.el.style.width = this.wWidth	+ 'px';
			this.el.style.height = this.wHeight + 'px';
			if(is_ie) {
				this.el.style.width = "430";
				this.el.style.overflowY = "auto";	
				this.el.style.overflowX = "hidden";
			}													
			else {									
				this.el.style.overflow = "-moz-scrollbars-vertical";
			}
			this.el.id = 'cc_rectIEContacts';
			document.body.appendChild(this.el);

			var lang_import_contacts = Element('cc_msg_import_contacts').value;
            var lang_close_win = Element('cc_msg_close_win').value
            var lang_export_contacts = Element('cc_msg_export_contacts').value;
            var lang_expresso_info_csv = Element('cc_msg_expresso_info_csv').value;
            var lang_expresso_default = Element('cc_msg_expresso_default').value;
            var lang_choose_contacts_file	= Element('cc_msg_choose_contacts_file').value;
            var lang_msg_choose_type		= Element('cc_msg_choose_file_type').value;
			var lang_msg_expresso_info_csv	= Element('cc_msg_expresso_info_csv').value;
			var lang_msg_export_csv			= Element('cc_msg_export_csv').value;
			var lang_msg_automatic = Element('cc_msg_automatic').value;
            var lang_close = Element('cc_msg_close').value;
			var lang_moz_tb = Element('cc_msg_moz_thunderbird').value;
			var lang_outl_pt = Element('cc_msg_outlook_express_pt').value;
			var lang_outl_en = Element('cc_msg_outlook_express_en').value;
			var lang_outl2k_pt = Element('cc_msg_outlook_2k_pt').value;
			var lang_outl2k_en = Element('cc_msg_outlook_2k_en').value;
			var lang_expresso_default_csv = Element('cc_msg_expresso_default_csv').value;

		
			this.el.innerHTML = 
			'<div align="left" id="divAppbox"><table width="100%" border=0>'+
			'<tr><td style="border-bottom:1px solid black"><input onclick="javascript:ccIEContacts.changeOptions(this.value)" id="type" type="radio" name="type" value="i" style="border:0" checked>'+lang_import_contacts+
			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input onclick="javascript:ccIEContacts.changeOptions(this.value)" id="type" type="radio" name="type" style="border:0" value="e"/>'+lang_export_contacts+' <br></td></tr>'+
			'</table>'+
			'<table border=0 height="208px"  width="100%" id="import_span">'+
			'<tr><td>'+
			'<font color="DARKBLUE" size="2">'+lang_expresso_info_csv+'</font></td></tr>'+
			'<tr><td height="75px" valign="top">'+
			'<form name="formCSV" method="POST" enctype="multipart/form-data">'+ lang_msg_choose_type +
			':&nbsp;<select id="typeImport"><option value="auto" selected>'+lang_msg_automatic+'</option>'+
			'<option value="outlook">'+("Outlook Express")+'</option>'+
			'<option value="outlook2000">'+("Outlook 2000")+'</option>'+
			'<option value="thunderbird">'+("Mozilla Thunderbird")+'</option>'+
			'<option value="expresso" selected>'+lang_expresso_default+'</option></select><br>'+
			'<br> Selecione um grupo:&nbsp;' + Element('cc_select_groups').value + '<br>' +
			'<br>'+lang_choose_contacts_file+'<br><br>'+		
			'<input id="import_file" type="file" name="import_file">'+
			'</form></td></tr>'+
			'<tr><td height="10px" align="center" nowrap><span id="s_info"></span></td></tr>'+
			'<tr><td height="10px" align="center"></td></tr>'+
			'<tr><td nowrap><center><input id="import_button" type="button" value='+lang_import_contacts+' onClick="javascript:ccIEContacts.importCSV(this)">&nbsp;&nbsp;&nbsp;&nbsp;'+
			'<input type="button" value='+lang_close_win+' onClick="javascript:ccIEContacts.close()"></center></td></tr>'+
			'</table>'+
			'<table border=0  height="208px"  width="100%" style="display:none" id="export_span">'+
			'<tr><td>'+						
			'<font color="DARKBLUE" size="2">'+ lang_msg_expresso_info_csv+'</font></td></tr>'+
			'<tr><td height="85px" valign="top">'+lang_msg_export_csv+'<br><br>'+
			'<select id="typeExport">'+
			'<option value="expresso" selected>'+lang_expresso_default_csv+'</option>'+
			'<option value="outlook_pt-BR">'+lang_outl_pt+'</option>'+
			'<option value="outlook_en">'+lang_outl_en+'</option>'+
			'<option value="outlook2000_pt-BR">'+lang_outl2k_pt+'</option>'+
			'<option value="outlook2000_en">'+lang_outl2k_en+'</option>'+
			'<option value="thunderbird">'+lang_moz_tb+'</option>'+
			'</select>'+			
			'</td></tr>'+
			'<tr><td align="center">&nbsp;</td></tr>'+
			'<tr><td nowrap><center><input id="export_button" type="button" value='+lang_export_contacts+ ' onClick="javascript:ccIEContacts.exportCSV(this)">&nbsp;&nbsp;&nbsp;&nbsp;'+
			'<input type="button" value='+lang_close_win+ ' onClick="javascript:ccIEContacts.close()"></center></td></tr>'+
			'</table></div>';
		}		
		this.showWindow();
	}
	
	cIEContacts.prototype.showWindow = function ()
	{						
		if(!this.win) {
	
				this.win = new dJSWin({			
				id: 'ccIEContacts',
				content_id: this.el.id,
				width: (this.wWidth +(is_ie ? 41 : 0))  +'px',
				height: this.wHeight +'px',
				title_color: '#3978d6',
				bg_color: '#eee',
				title: Element('cc_msg_ie_personal').value, 
				title_text_color: 'white',
				button_x_img: '../phpgwapi/images/winclose.gif',
				border: true });
			
			this.win.draw();			
		}
		
		this.win.open();
	}
	
	cIEContacts.prototype.importWriteStatus = function( status, args )
	{
		var msg = '';
		switch ( status ) {
			case 'success':
				msg += '<font face="Verdana" size="1" color="GREEN">['+args['new']+' '+jQuery('#cc_msg_new').val()+']</font>';
				msg += '<font face="Verdana" size="1" color="DARKBLUE">['+args['exist']+' '+jQuery('#cc_msg_exists').val()+']</font>';
				if ( args['fail'].length ) {
					msg += '<font face="Verdana" size="1" color="RED">['+args['fail'].length+' '+jQuery('#cc_msg_failure').val()+']</font></br></br>';
					msg += '<a id="cc_msg_fail_det_op" font face="Verdana" size="1" href="javascript:ccIEContacts.showFailures()">'+jQuery('#cc_msg_show_more_info').val()+'</a>';
					msg += '<div id="cc_msg_fail_det" style="display: none;">';
					msg += '<a font face="Verdana" size="1" href="javascript:ccIEContacts.showFailures()">'+jQuery('#cc_msg_clean').val()+'</a></br></br><table>';
					for ( var line in args['fail'] )
						msg += '<tr><td><font face="Verdana" size="1" color="RED">'+args['fail'][line]+'</font></td></tr>';
					msg += '</table></div>';
				}
				break;
			case 'error':
				msg = '<span style="height:15px;background:#cc4444">'+
					'&nbsp;&nbsp;<font face="Verdana" size="1" color="WHITE">'+
					jQuery('#cc_msg_import_fail').val()+': '+args+
					'&nbsp;</font></span>';
				break;
			case 'importing':
				msg = '<span style="height:15px;background:rgb(250, 209, 99)">'+
					'&nbsp;&nbsp;<font face="Verdana" size="1" color="DARKBLUE">'+
					jQuery('#cc_msg_importing_contacts').val()+
					'&nbsp;</font></span>';
				break;
		}
		jQuery('#s_info').html( msg );
	}
	
	cIEContacts.prototype.showFailures = function()
	{
		jQuery('#cc_msg_fail_det_op').toggle();
		jQuery('#cc_msg_fail_det').toggle();
	}
	
	cIEContacts.prototype.importCSV = function ()
	{
		if ( window.FormData === undefined ) {
			alert( jQuery('#cc_msg_browser_support').val() );
			return;
		}
		var files = jQuery('#import_file')[0].files;
		if ( files.length != 1 ) {
			alert( jQuery('#cc_msg_invalid_csv').val() );
			return;
		}
		var file = files[0];
		if ( !file.name.match( /.csv$/i ) ) {
			alert( jQuery('#cc_msg_invalid_csv').val() );
			return;
		}
		
		jQuery('form[name=formCSV]').hide();
		jQuery('#import_button').prop('disabled',true);
		ccIEContacts.importWriteStatus( 'importing' );
		
		var data = new FormData();
		data.append( 'typeImport', jQuery('#typeImport').val() );
		data.append( 'id_group', jQuery('#id_group').val() );
		data.append( 0, file );
		jQuery.ajax({
			url: '../index.php?menuaction=contactcenter.ui_data.data_manager&method=import_contacts',
			type: 'POST',
			data: data,
			cache: false,
			dataType: 'json',
			processData: false,
			contentType: false,
		}).done(function( data, textStatus, jqXHR ){
			
			if ( data == undefined || data.error )
				ccIEContacts.importWriteStatus( 'error', ( data && data.error )? data.error : 'undefined' );
			else
				ccIEContacts.importWriteStatus( 'success', data );
			
		}).fail(function( jqXHR, textStatus, errorThrown ){
			
			ccIEContacts.importWriteStatus( 'error', textStatus );
			
		}).always(function(){
			
			jQuery('form[name=formCSV]').show();
			jQuery('#import_button').prop('disabled',false);
			jQuery('#import_file').val( null );
			
		});
	}
	
	cIEContacts.prototype.close = function() {
		jQuery('#s_info').html( '' );
		this.win.close();
	}
	
	cIEContacts.prototype.exportCSV = function() {
		
		var typeExport = Element("typeExport");
		document.location.href = '../index.php?menuaction=contactcenter.ui_data.data_manager&method=export_contacts&typeExport='+typeExport.value;
		
	}

/* Build the Object */
	var	ccIEContacts = new cIEContacts();
