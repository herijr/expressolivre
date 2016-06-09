  /***************************************************************************\
  * eGroupWare - Contacts Center                                              *
  * http://www.egroupware.org                                                 *
  * Written by:                                                               *
  *  - Raphael Derosso Pereira <raphaelpereira@users.sourceforge.net>         *
  *  sponsored by Thyamad - http://www.thyamad.com                            *
  * ------------------------------------------------------------------------- *
  *  This program is free software; you can redistribute it and/or modify it  *
  *  under the terms of the GNU General Public License as published by the    *
  *  Free Software Foundation; either version 2 of the License, or (at your   *
  *  option) any later version.                                               *
  \***************************************************************************/

	/*
	 * ContactCenter API - Add Group
	 *
	 * USAGE INSTRUCTIONS
	 */

	function cAddGroup ()
	{
		// Private
		this._card;
		this._button = new Array();		

		// Public
		this.window;
		this.afterSave;
		this.load;
		
		// Constructor
		var wHeight = 0;
		
		// Elements
		this.title = Element('title');
		this.contact_in_list = Element('contact_in_list');
		this.group_id = Element('group_id');
				
		ccAGWinHeight = 'ccAGWinHeightMO';

		if (is_ie)
			ccAGWinHeight = 'ccAGWinHeightIE';

		wHeight = Element(ccAGWinHeight).value;

		
		this.window = new dJSWin({
			id: 'ccAddGroup DOM',
			content_id: 'ccAddGroupContent',
			width: '700px',
			height: wHeight+'px',
			title_color: '#3978d6',
			bg_color: '#eee',
			title: Element('ccAGTitle').value,
			title_text_color: 'white',
			button_x_img: Element('cc_phpgw_img_dir').value+'/winclose.gif',
			border: true });

		this.window.draw();
		
	}

	/*!
		@method associateAsButton
		@abstract Associates the button functions with the spacified DOM Element
		@author Raphael Derosso Pereira

		@param div DOMElement The HTML DOM element that will "host" the
			plugin button.

		@param func function The function that returns the data to be used
			to pre-populate the quickAdd fields. The return format MUST be
			an Array like:

				var return_data = new Array();
				return_data[0] = <value>;  // Value for the first field
				return_data[1] = <value>;  // Value for the second field
				...
		
	 */
	cAddGroup.prototype.associateAsButton = function (div)
	{
		var _this = this;		
		div.onclick = function() {
					
			if (_this.load)	{
				switch (typeof(_this.load)) {
				
					case 'function':
						_this.load();
						break;

					case 'string':
						eval(_this.load);
						break;
				}
			} 
		};
		
	}
		
	/*!
	
		@method send
		@abstract Sends data to server
		@author Raphael Derosso Pereira

	*/
	cAddGroup.prototype.send = function ()
	{
		var _this = this;

		var handler = function (responseText)
		{
			Element('cc_debug').innerHTML = responseText;
			var data = ccAux.unserialize(responseText);				
			
			if (!data || typeof(data) != 'object')
			{
				showMessage(Element('cc_msg_err_contacting_server').value);
				return;
			}

			//showMessage(data['msg']);

			if (data['status'] != 'ok')
			{
				showMessage(data['msg']);
				return;
			}

			_this.clear();
			_this.window.close();
			
			if (_this.afterSave)
			{
				switch (typeof(_this.afterSave))
				{
					case 'function':
						_this.afterSave();
						break;

					case 'string':
						eval(_this.afterSave);
						break;
				}
			}
		}

		var sdata = new Array();
		var empty = true;
		
		sdata[0] = this.title.value;
		var contacts = new Array();				
		
		for (j =0; j < this.contact_in_list.length; j++)
			contacts[j] = this.contact_in_list.options[j].value;			
		
		if(!this.title.value) {
			alert(Element('cc_msg_fill_field_name').value);
			this.title.focus();
			return false;
		}
				
		if(! contacts.length) {
			alert(Element('cc_msg_add_contact_to_group').value);
			return false;
		}

		sdata[1] = contacts;		
		sdata[2] = this.group_id.value == 'undefined' ? 	sdata[2] = 0 : sdata[2]  = this.group_id.value; 						
		var sdata = 'add='+escape(ccAux.serialize(sdata));
		Connector.newRequest('cAddGroup.Send', CC_url+'add_group', 'POST', handler, sdata);
	}

	/*!

		@method clear
		@abstract Clear all Plugin Fields
		@author Raphael Derosso Pereira

	*/
	cAddGroup.prototype.clear = function (reload)
	{
		for (j =0; j < this.contact_in_list.options.length; j++) {
			this.contact_in_list.options[j].selected = false;
			this.contact_in_list.options[j--] = null;
		}
		
		if(reload) {
			if(Element("contact_list"))
			for (j =0; j < Element("contact_list").options.length; j++) {
					Element("contact_list").options[j].selected = false;
					Element("contact_list").options[j--] = null;
			}
		}
			
		this.title.value = '';				
	}
	
	/* Função para remover contato da lista */	
	
	cAddGroup.prototype.remUser = function(){
		
		select_in = this.contact_in_list;								

		for(var i = 0;i < select_in.options.length; i++)				
			if(select_in.options[i].selected)
				select_in.options[i--] = null;
	}	
 	
	/* Função para adicionar contato na lista */	
	cAddGroup.prototype.addUser = function(){

		select = Element("contact_list");
		select_in = this.contact_in_list;								
		
		for (i = 0 ; i < select.length ; i++) {				

			if (select.options[i].selected) {
				isSelected = false;

				for(var j = 0;j < select_in.options.length; j++) {																			
					if(select_in.options[j].value == select.options[i].value){
						isSelected = true;						
						break;	
					}
				}

				if(!isSelected){

					option = document.createElement('option');
					option.value =select.options[i].value;
					option.text = select.options[i].text;
					option.selected = true;
					select_in.options[select_in.options.length] = option;
											
				}
												
			}
		}
		
		for (j =0; j < select.options.length; j++)
			select .options[j].selected = false;		
	} 	

	/* Build the Object */
	var ccAddGroup ;
	var cAddGroup_pre_load = document.body.onload;
	/* Se for IE, modifica a largura da coluna dos botoes.*/	
	if(document.all)
		document.getElementById('buttons').width = 140;	

	if (is_ie)
	{ 
		document.body.onload = function (e) 
		{ 
			cAddGroup_pre_load();
			ccAddGroup = new cAddGroup();
			
		};
	}
	else
	{
		ccAddGroup = new cAddGroup();
	}
