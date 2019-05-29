	function emQuickCatalogSearch ()
	{
		this.arrayWin = new Array();
		this.el;
		this.cc_contacts = new Array();
		this.cc_groups  = new Array();
	}

	emQuickCatalogSearch.prototype.showList = function(data, begin, end){
		connector.loadScript("ccQuickAdd");
		
		id = '1';
		_this = this;
		var el = document.createElement("DIV");
		el.style.visibility = "hidden";
		el.style.position = "absolute";
		el.style.left = "0px";
		el.style.top = "0px";
		el.style.width = "0px";
		el.style.height = "0px";
		el.id = 'window_QuickCatalogSearch';
		document.body.appendChild(el);
		el.innerHTML = "";
		
		
		func_add_contact = function () { 
			var select_QuickCatalogSearch = document.getElementById("select_QuickCatalogSearch");
			var contact_selected = select_QuickCatalogSearch.options[select_QuickCatalogSearch.selectedIndex].text;
			
			contact_selected = contact_selected.split('(');
			
			var first_and_last_name = contact_selected[0].split(" ");
			
			var data = [];
			data[0] = first_and_last_name[0];
			data[1] = first_and_last_name[0];
			data[2] = "";
			
			for (i=1; i < first_and_last_name.length; i++)
				data[2] += first_and_last_name[i] + " ";
			
			data[2] = data[2].replace(/\s*$/g,'');
			data[3] = contact_selected[1].substring(0, contact_selected[1].indexOf(")") );
			
			ccQuickAddOne.showList( data );			
		};		
		
		if (document.getElementById('select_QuickCatalogSearch') == null){

			var title_innerHTML = get_lang('Select a name') + ':';
			if (data.quickSearch_only_in_userSector)
				title_innerHTML += "<font color='BLACK' nowrap> ("+get_lang('Showing only the results found in your organization')+".)</font>"

			var title = document.createElement("SPAM");
			title.id = 'window_QuickCatalogSearch_title';
			title.innerHTML = "&nbsp;&nbsp;<b><font color='BLUE' nowrap>"+title_innerHTML+"</font></b><br>&nbsp;&nbsp;";
			el.appendChild(title);
			
			var cmb = document.createElement("SELECT");
			cmb.id = "select_QuickCatalogSearch";
			cmb.style.width = "685px";
			cmb.size = "12";
			
			el.appendChild(cmb);

			var space = document.createElement('SPAN');
			space.innerHTML = "<BR>&nbsp;&nbsp;";
			el.appendChild(space);

			var butt = document.createElement('BUTTON');
			var buttext = document.createTextNode('OK');
			butt.id = "QuickCatalogSearch_button_ok";
			butt.appendChild(buttext);
			butt.onclick = function () {QuickCatalogSearch.transfer_result(data.field, data.ID, begin, end);};
			el.appendChild(butt);

			var space = document.createElement('SPAN');
			space.innerHTML = "&nbsp;&nbsp;";
			el.appendChild(space);

			var butt = document.createElement('BUTTON');
			butt.id = "QuickCatalogSearch_button_close";
			var buttext = document.createTextNode(get_lang('Close'));
			butt.appendChild(buttext);
			butt.onclick = function () {QuickCatalogSearch.close_QuickSearch_window(data.field, data.ID);};
			el.appendChild(butt);
			
			var space = document.createElement('SPAN');
			space.innerHTML = "&nbsp;&nbsp;";
			el.appendChild(space);

			var butt = document.createElement('BUTTON');
			butt.id = "QuickCatalogSearch_button_add_contact";
			var buttext = document.createTextNode(get_lang("Add Contact"));
			butt.appendChild(buttext);
			butt.onclick = func_add_contact;
			el.appendChild(butt);			
		}
		else{
			var title_innerHTML = get_lang('Select a name') + ':';
			if (data.quickSearch_only_in_userSector)
				title_innerHTML += "<font color='BLACK' nowrap> ("+get_lang('Showing only the results found in your organization')+".)</font>"

			var title = Element('window_QuickCatalogSearch_title');
			title.innerHTML = "&nbsp;&nbsp;<b><font color='BLUE' nowrap>"+title_innerHTML+"</font></b><br>&nbsp;&nbsp;";
			
			var cmb = document.getElementById('select_QuickCatalogSearch');
			
			for (i=0; i<cmb.length; i++)
				cmb.options[i--] = null;
			
			var butt_ok = document.getElementById("QuickCatalogSearch_button_ok");
			var butt_close = document.getElementById("QuickCatalogSearch_button_close");
			var butt_add_contact = document.getElementById("QuickCatalogSearch_button_add_contact");
			butt_ok.onclick = function () {QuickCatalogSearch.transfer_result(data.field, data.ID, begin, end);};
			butt_close.onclick = function () {QuickCatalogSearch.close_QuickSearch_window(data.field, data.ID);};
			butt_add_contact.onclick = func_add_contact;
		}

		for (i=0; i<data.length; i++){
			var Op = document.createElement("OPTION");
			Op.text = data[i].cn + ' (' + data[i].mail + ')';
			if (data[i].phone != '')
				Op.text += ' - ' + data[i].phone;
			if (data[i].ou != '')
				Op.text += ' - ' + data[i].ou; // adicionado "data[i].ou" para exibir setor (F9)
			Op.value = '"' + data[i].cn + '" ' + '<' + data[i].mail + '>';
			cmb.options.add(Op);
		}
		cmb.options[0].selected = true;
		$(cmb).off('keydown').on('keydown',function(e){
			if ( e.which == 13 || e.which == 27 ) {
				$(e.currentTarget).parent()
				.find('button#QuickCatalogSearch_button_'+(e.which == 13?'ok':'close'))
				.trigger('click');
				e.stopImmediatePropagation();
			}
		});
		_this.showWindow(el);
	}
	
	emQuickCatalogSearch.prototype.showWindow = function (div)
	{
		if(! div) {
			alert(get_lang('The list has no participant.'));
			return;
		}
							
		if(! this.arrayWin[div.id]) {
			div.style.width = "700px";
			div.style.height = "230px";
			var title = get_lang('The results were found in the Global Catalog')+':';
			var wHeight = div.offsetHeight + "px";
			var wWidth =  div.offsetWidth   + "px";
			div.style.width = div.offsetWidth - 5;

			win = new dJSWin({			
				id: 'QuickCatalogSearch_'+div.id,
				content_id: div.id,
				width: wWidth,
				height: wHeight,
				title_color: '#3978d6',
				bg_color: '#eee',
				title: title,						
				title_text_color: 'white',
				button_x_img: '../phpgwapi/images/winclose.gif',
				border: true });
			
			this.arrayWin[div.id] = win;
			win.draw();
		}
		else {
			win = this.arrayWin[div.id];
		}
		//document.getElementById('QuickCatalogSearch_window_QuickCatalogSearch').style.display = "";
		win.open();
		$('#select_QuickCatalogSearch').focus();
	}
	
	emQuickCatalogSearch.prototype.transfer_result = function (field, ID, begin, end){
		cm = document.getElementById('select_QuickCatalogSearch');
		option_selected = cm.options[cm.selectedIndex].value + ", ";
		emailList = document.getElementById(field + "_" + ID).value;
		
		new_emailList = emailList.substring(0, begin) + option_selected + emailList.substring((parseInt(end) + 2), emailList.length);
		document.getElementById(field + "_" + ID).value = new_emailList;
		document.getElementById(field + "_" + ID).focus();
		
		//document.getElementById('QuickCatalogSearch_window_QuickCatalogSearch').style.display = "none";
		this.arrayWin['window_QuickCatalogSearch'].close();
	}
	
	emQuickCatalogSearch.prototype.close_QuickSearch_window = function (field, ID){
		//document.getElementById('QuickCatalogSearch_window_QuickCatalogSearch').style.display = "none";
		document.getElementById(field + "_" + ID).focus();
		this.arrayWin['window_QuickCatalogSearch'].close();
	}

	emQuickCatalogSearch.prototype.close_window = function(id) {
		this.arrayWin[id].close();
		var group_values = Element('list_values');
		var user_values = Element('user_values');	
	}
	emQuickCatalogSearch.prototype.showCatalogList = function (border_id){
		var el = Element('catalog_list');

		if(el) {
			Element('border_id').value = border_id;
			win = this.arrayWin[el.id];
			win.open();
			return;		
		}
		var border_input   = document.createElement("INPUT");
		border_input.type  = 'hidden';
		border_input.id    = 'border_id';
		border_input.value = border_id;
		document.body.appendChild(border_input);
		el = document.createElement("DIV");		
		el.id = 'catalog_list';
		document.body.appendChild(el);			
		el.style.visibility = "hidden";
		el.style.position = "absolute";
		el.style.width = "700px";
		el.style.height = is_ie ? "360px" : "375px";		
		el.style.left = "0px";
		el.style.top = "0px";			
		el.innerHTML = "<table border='0' cellpading='0' cellspacing='0' width='100%'>"+
					   "<tr><td id='td1' style='cursor:pointer' align='center' onclick='QuickCatalogSearch.select_div(\"tab1\")'><a href='#' class='catalog' onclick='QuickCatalogSearch.select_div(\"tab1\");'>"+get_lang('Global Catalog')+"</a></td>"+
					   "<td id='td2' style='background:#cecece;cursor:pointer' onclick='QuickCatalogSearch.select_div(\"tab2\")' align='center'><a href='#' class='catalog' onclick='QuickCatalogSearch.select_div(\"tab2\");'>"+get_lang('Personal Catalog')+"</a></td></tr>"+
					   '</table>'+
					   '<div id="tab1" align="center"><br>'+
					   '<table border="0" cellpading="0" cellspacing="0">'+
					   '<tr><td>'+get_lang("Select an organization and click on button <b>Search</b>")+'&nbsp;:</td></tr>'+
  					'<tr><td><select style="display:none;width:150px" id="select_catalog" name="select_catalog" onchange="javascript:QuickCatalogSearch.update_organizations();"></select>&nbsp'+
					'<select id="select_organization" style="width:150px" name="select_organization"></select>&nbsp;'+   
					'<input type="text" id="search_for" name="search_for" value="" size="30" maxlength="30"/>&nbsp;<input type="button" onclick="QuickCatalogSearch.searchCatalogList(true)" class="button" value="'+get_lang('Search')+'">&nbsp;<input style="display:visible" type="button" onclick="QuickCatalogSearch.searchCatalogList(false)" class="button" value="'+get_lang('List All')+'"></td></tr>'+
					   '<tr><td><input onclick="javascript:QuickCatalogSearch.changeOptions(this.value)" id="type_l" type="radio" name="type" value="l"/>'+get_lang('Public Lists')+'&nbsp;&nbsp;<input type="radio" id="type_u" name="type" value="u" onclick="javascript:QuickCatalogSearch.changeOptions(this.value)" checked/>'+get_lang('Users')+'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+
					   '<span style="visibility:hidden;background:#cc4444" id=msg_search>&nbsp;&nbsp;<font face="Verdana" size="1" color="WHITE">'+get_lang('Search in Catalog')+'...</font>&nbsp;</span></td></tr>'+
					   '<tr><td>'+
				  	   '<span id="list_span"><select multiple style="display:none;width:580px" size="14" id="list_values"></select></span>'+
				  	   '<span id="user_span"><select multiple style="width:580px" size="14" id="user_values"></select></span>'+
				 	   '</td></tr>'+
					   '<tr><td nowrap><center>'+get_lang('Click here to add into the fields')+':&nbsp;<input type="button" class="button" value="'+get_lang('TO')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'to\')">&nbsp;'+
				  	   '<input type="button" class="button" value="'+get_lang('CC')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'cc\')">&nbsp;'+
				   	   '<input type="button" class="button" value="'+get_lang('CCo')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'cco\')">'+
				  	   '</center></td></tr><tr><td nowrap><center><input type="button" value="'+get_lang('Close')+'" onClick="javascript:QuickCatalogSearch.close_window(\'catalog_list\')"></center></td></tr>'+
					   '</table>'+
					   '</div>'+
					   '<div style="display:none" id="tab2" align="center">'+
					   '<br><br><br>'+
					   '<table border="0" cellpading="0" cellspacing="0">'+
					   '<tr><td>'+get_lang("Select the type of contact that you want to view")+'&nbsp;:</td></tr>'+
					   '<tr><td><input onclick="javascript:QuickCatalogSearch.changeOptions(this.value)" type="radio" name="type" value="p"/>' + get_lang('People') + '&nbsp;&nbsp;<input type="radio" name="type" value="g" onclick="javascript:QuickCatalogSearch.changeOptions(this.value)"/>' + get_lang('Groups') + ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+
					   '<tr><td>'+
				  	   '<span id="personal_span"><select multiple style="width:580px" size="14" id="list_personal"></select></span>'+
				  	   '<span id="groups_span"><select multiple style="display:none;width:580px" size="14" id="list_groups"></select></span>'+
				 	   '</td></tr>'+
					   '<tr><td nowrap><center>'+get_lang('Click here to add into the fields')+':&nbsp;<input type="button" class="button" value="'+get_lang('TO')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'to1\')">&nbsp;'+
				  	   '<input type="button" class="button" value="'+get_lang('CC')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'cc1\')">&nbsp;'+
				   	   '<input type="button" class="button" value="'+get_lang('CCo')+'" onClick="javascript:QuickCatalogSearch.addContacts(\'cco1\')">'+
				  	   '</center></td></tr><tr><td nowrap><center><input type="button" value="'+get_lang('Close')+'" onClick="javascript:QuickCatalogSearch.close_window(\'catalog_list\')"></center></td></tr>'+
					   '</table>'+
					   '</div>';

		var title = get_lang('Search in the Global Catalog');
		var wHeight = el.offsetHeight - (is_ie ? 10 : 0) + "px";
		var wWidth =  el.offsetWidth   + "px";
		el.style.width = el.offsetWidth;
		
		win = new dJSWin({			
			id: 'win_'+el.id,
			content_id: el.id,
			width: wWidth,
			height: wHeight,
			title_color: '#3978d6',
			bg_color: '#eee',
			title: title,						
			title_text_color: 'white',
			button_x_img: '../phpgwapi/images/winclose.gif',
			border: true });
			
		this.arrayWin[el.id] = win;		
		win.draw();
		win.open();
		
		$('input#search_for').focus().off('keydown').on('keydown',function(e){
			if ( e.which == 13 ) QuickCatalogSearch.searchCatalogList( true );
		});
		
		var handler_catalogs = function(data){
			var user_catalog = get_lang("Global Catalog");
			if(data.length > 1) {
				Element('select_catalog').style.display = '';
				for(i = 0; i < data.length; i++) {		
					Element('select_catalog').options[i] = new Option(data[i],i);
					if(user_catalog.toUpperCase() == data[i].toUpperCase())
						Element('select_catalog').options[i].selected = true;
				}
			}
		}
		cExecute ("$this.ldap_functions.get_catalogs", handler_catalogs);		
		
		this.update_organizations();
		var handler_cc_contacts= function(data){
			if(data && data.length > 0){
				var aux = data.split(",");
				for(var i=0; i< aux.length; i++){
					QuickCatalogSearch.cc_contacts[QuickCatalogSearch.cc_contacts.length] = aux[i];				
				}
			}
		}
		cExecute("$this.db_functions.get_cc_contacts",handler_cc_contacts);

		var handler_cc_groups = function(data){
			if(data && data.length > 0){
				var aux = data.split(",");
				for(var i=0; i < data.length; i++){
					QuickCatalogSearch.cc_groups[QuickCatalogSearch.cc_groups.length] = aux[i];								
				}	
			}
		}
		cExecute("$this.db_functions.get_cc_groups",handler_cc_groups);
	
	}
	
	emQuickCatalogSearch.prototype.select_div = function(element){
		if(element == 'tab1'){
		   Element('tab1').style.display = '';
		   Element('tab2').style.display = 'none';
		   Element('td1').style.background = '#eee';	
		   Element('td2').style.background = '#cecece';
		}
		if(element == 'tab2'){
		   Element('tab1').style.display = 'none';
		   Element('tab2').style.display = '';
		   Element('td1').style.background = '#cecece';
		   Element('td2').style.background = '#eee';		   

		}
	}
	
	emQuickCatalogSearch.prototype.load_catalog = function(){
	
		var _this = this;
		var content = new Array;
		var select = Element('list_personal').style.display == 'none'? Element('list_groups'): Element('list_personal');
		
		if(Element('list_personal').style.display == 'none'){
			content = _this.cc_groups;
		}else{
			content = _this.cc_contacts;
		}
		if(select.options.length > 0){
			for(var i=0; i < select.options.length; i++){
				select.options[i] = null;
				i--;
			}
		}
		for(var i=0; i < content.length; i++){
			if(content[i] != undefined){
				var aux = content[i].split(";");
				var opt = new Option(aux[0] + ' (' + aux[1] + ')','"' + aux[0] + '" ' + '<' + aux[1] + '>',false,false);
				select[select.length] = opt;
			}
		}
		content.splice(0,(content.length));
	}

	emQuickCatalogSearch.prototype.update_organizations = function(){
		while(Element('select_organization').options.length > 0) {
			Element('select_organization').remove(0);
		}
	 	
	 	var handler_org = function(data)
	 	{
			if( data && data != false )
			{
				Element('select_organization').options[0] = new Option(get_lang('all'),'all');

				var user_organization = Element('user_organization').value;
				
				for(x = 0; x < data.length; x++)
				{
					Element('select_organization').options[x+1] = new Option(data[x].toUpperCase(),data[x]);
					
					if(user_organization.toUpperCase() == data[x].toUpperCase())
					{
						Element('select_organization').options[x+1].selected = true;
					}
				}
			}
		};

		cExecute ("$this.ldap_functions.get_organizations&referral=false&catalog="+Element('select_catalog').value, handler_org);		
	}

	emQuickCatalogSearch.prototype.changeOptions = function(type){	

		switch(type){
			case 'u':
				Element('list_values').style.display = 'none';
				Element('user_values').style.display = '';
				break;
			
			case 'l':		
				Element('user_values').style.display = 'none';
				Element('list_values').style.display = '';		
				break;

			case 'p':
				Element('list_personal').style.display = '';
				Element('list_groups').style.display = 'none'
				QuickCatalogSearch.load_catalog();				
				break;
			
			case 'g':
				Element('list_personal').style.display = 'none';
				Element('list_groups').style.display = ''
				QuickCatalogSearch.load_catalog();
				break;
		}

	}

	emQuickCatalogSearch.prototype.addContacts = function(field) {
		
		var border_id 	= Element('border_id').value;
 		var select		= Element('user_values').style.display == 'none' ? Element('list_values') : Element('user_values');
		if(field == "to1" || field == "cc1" || field == "cco1"){
			field = field.substr(0,field.length - 1);
			var select = Element('list_personal').style.display == 'none' ? Element('list_groups') : Element('list_personal');
		}
		var fieldOpener = Element(field+"_"+border_id);
		var not_selected = true;
		
		fieldOpener.value = trim(fieldOpener.value);
		
		for (i = 0 ; i < select.length ; i++) {
			if (select.options[i].selected && select.options[i].value != '-1') {
				if(fieldOpener.value.length > 0 && (fieldOpener.value.lastIndexOf(',') != (fieldOpener.value.length - 1))){
					fieldOpener.value += ",";
				}
				fieldOpener.value += select.options[i].value + ",";
				not_selected = false;
				select.options[i].selected = false;
			}
		}
		
		if(not_selected)
			return false;
			
		if(field != 'to'){
 			a_link = Element("a_"+field+"_link_"+border_id);
 			if(a_link)
 				a_link.onclick();
 		}
	}
	
	emQuickCatalogSearch.prototype.searchCatalogList = function (itemSearch){

		if(itemSearch && Element('search_for').value.length < preferences.search_characters_number){
			alert(get_lang('Your search argument must be longer than %1 characters.', preferences.search_characters_number));
			Element('search_for').focus();
			return false;
		}
		var organization = Element('select_organization').value;		
		var search		 = itemSearch ? Element('search_for').value : '';
		var catalog		 = Element('select_catalog').value;
		var max_result	= 400;

		var handler_searchResults = function(data){
			Element('msg_search').style.visibility = 'hidden';
			if(data.error){
				alert(get_lang('More than %1 results. Please, try to refine your search.',max_result));
				return false;
			}else if(data.users.length == 0 && data.groups.length == 0){
				alert(get_lang('None result was found.'));
			}

			
			var group = Element('list_span');
			var user  = Element('user_span');	
			if(is_ie){
				group.innerHTML = '';
				user.innerHTML = '';
			}
			else {
				group = Element('list_values');
				user  = Element('user_values');	
				for(var i = 0;i < group.options.length; i++)				
					group.options[i--] = null;
				for(var i = 0;i < user.options.length; i++)				
					user.options[i--] = null;
			}

			var arr 	= new Array(max_result);

			for(i = 0; data.groups && i < data.groups.length; i++) {
				// Maneiras diferentes de se montar uma tag OPTION, pois no IE o objeto Option é muito lento.
				if(is_ie)
					arr[i] = '<option value="'+'&quot;'+data.groups[i].name+'&quot; &lt;'+data.groups[i].email+'&gt;">'+data.groups[i].name+' ('+data.groups[i].email+')'+'</option>';
				else
					group.options[i] = new Option(data.groups[i].name+' ('+data.groups[i].email+')','"'+data.groups[i].name+'" <'+data.groups[i].email+'>');
			}
	
			
			if(is_ie)
				group.innerHTML = '<select multiple style="display:none;width:580px" size="14" id="list_values">'+ arr.join() +'</select>';

			arr = new Array(max_result);
			
			for(i = 0; data.users && i < data.users.length; i++) {
			/*******************************************************************************************/
			/* O resultado pratico do bloco de codigo a seguir eh a exibicao dos valores em tela,
			ja que vai verificar se o departamento e o email estao vazios ou nulos e a partir dai o
			resultado apresentado em tela sera exibido de maneira mais apresentavel;
			*/
				//verifica se departamento eh null ou nao;
				var department = data.users[i].department ? " - " + data.users[i].department : "";
				//verifica se email eh null ou nao;
				var email = data.users[i].email ? data.users[i].email : "";

				// Maneiras diferentes de se montar uma tag OPTION, pois no IE o objeto Option é muito lento.
				if(is_ie)
					arr[i] = '<option value="'+'&quot;'+data.users[i].name+'&quot; &lt;'+email+'&gt;">'+data.users[i].name+' ('+email+')'+department+'</option>';
				else {
					user.options[i] = new Option(data.users[i].name+' ('+email+')'+department,'"'+data.users[i].name+'" <'+email+'>'); // incluido data.users[i].department para exibir setor na opcao "Pesquisar" do email;
				}
			}
	
			if(is_ie)
				user.innerHTML = '<select multiple style="width:580px" size="14" id="user_values">'+ arr.join() +'</select>';

			// Display entries found.
			var type = (data.groups.length > 0 && data.users.length == 0) ? 'l' : 'u';
			Element("type_"+type).checked = true;
			QuickCatalogSearch.changeOptions(type);
		}

		Element('msg_search').style.visibility = 'visible';
		cExecute ("$this.ldap_functions.catalogsearch&max_result="+max_result+"&organization="+organization+"&search_for="+search+"&catalog="+catalog, handler_searchResults);
	}

	
/* Build the Object */
	var QuickCatalogSearch;
	QuickCatalogSearch = new emQuickCatalogSearch();