/**************************************************************************\
 Início 
\**************************************************************************/
	function searchE()
	{
		this.searchW   		= [];
		this.folders		= new Array();
		this.condition		= "";
		this.sort_type		= "";
		this.page 			= 0;
		this.name_box_search = "";
		this.all_boxes		= [];
		this.type			= "";
		this.txtfields 		= new Array("txt_ass","txt_de","txt_body","txt_para","txt_cc","txt_cco", "since_date", "before_date", "on_date");
		this.selectFields	= new Array("flagged", "seen", "answered", "recent");
	}

	//Monta os forms dentro da janela;
	searchE.prototype.showForms = function(value)
	{
		// Principal
		if(!Element("window_search"))
		{
			var form_search = document.createElement("DIV");
			form_search.style.visibility = 'hidden';
			form_search.style.top = '0px';
			form_search.style.left = '0px';
			form_search.id = "window_search";
			document.body.appendChild(form_search);
		}

		// Pesquisa
		if(!Element("div_form_search"))
		{
			var div_form_search = document.createElement("DIV");
			div_form_search.id = "div_form_search";
			div_form_search.style.position = "absolute";
			div_form_search.style.left = "10px";
			div_form_search.style.top  = "10px";
			div_form_search.style.borderStyle = "outset";
			div_form_search.style.borderColor = "black";
			div_form_search.style.borderWidth = "1px";
			div_form_search.style.width = "784px";
			div_form_search.style.height = "418px";
			div_form_search.style.overflow = "hidden";
			div_form_search.innerHTML = "";
			var call_form_search = EsearchE.mount_form();
			div_form_search.appendChild(call_form_search);
			form_search.appendChild(div_form_search);

                        Calendar._TT['WEEKEND'] = '0,6'; // hack
                        Calendar._TT['DAY_FIRST'] = 'Mostrar %s primeiro';
                        Calendar.setup({
                            inputField  : 'since_date',
                            button      : 'since_date-trigger',
                            ifFormat    : '%d/%m/%Y',
                            daFormat    : '%d/%m/%Y'
                        });

                        Calendar.setup({
                            inputField  : 'before_date',
                            button      : 'before_date-trigger',
                            ifFormat    : '%d/%m/%Y',
                            daFormat    : '%d/%m/%Y'
                        });

                        Calendar.setup({
                            inputField  : 'on_date',
                            button      : 'on_date-trigger',
                            ifFormat    : '%d/%m/%Y',
                            daFormat    : '%d/%m/%Y'
                        });

		}

		if(!Element("div_button_search")){
			var div_button_search    = document.createElement("DIV");
				div_button_search.id = "div_button_search";
				div_button_search.style.position = "absolute";
				div_button_search.style.marginLeft = "430px";
				div_button_search.style.top = "223px";
				div_button_search.style.width = "350px";
				div_button_search.style.height = "25px";
				div_button_search.innerHTML = "<table style='width: 100%;' border='0' cellpadding='0' cellspacing='0' align='center'>"+
											  "<tr>"+
											  "<td width='33%' align='center'><input type='button' value=" + get_lang('Search') + " onclick='EsearchE.func_search()'></td>"+
											  "<td width='33%' align='center'><input type='button' value=" + get_lang('Clean') + " onclick='EsearchE.func_clean()'></td>"+
											  "<td width='33%' align='center'><input type='button' value=" + get_lang('Close') + " onclick='EsearchE.func_close(\"hidden\")'></td>"+
											  "</tr>"+
											  "</table>";
				form_search.appendChild(div_button_search);
		}

		if(!Element("table_layer")){
			var table_layer    = "";
		}

		if(value == "")
			EsearchE.showWindow(Element("window_search"));

		// Cria as caixas postais;
		EsearchE.mount_folders();

		if(value)
		{
			Element("check_all_msg").checked = true;
			EsearchE.all_mailboxes();
			EsearchE.func_search(value);
		}

	}
	
	//Form
	searchE.prototype.mount_form = function(value)
	{
		var form_sch = document.createElement("FORM");
			form_sch.id  = "form_sch";

            form_sch.innerHTML =  '<fieldset style="width:400px; text-align:right; padding: 5px; position:absolute;">'
						+ '	<legend>'+get_lang('Inform your search in the text fields')+'</legend>'
						+ '	<label>'+get_lang("From")+':</label>'
						+ '	<input style="margin-left: 6px;" type="text" id="txt_de" size="39">'
						+ '	<br style="margin-bottom:15px"/>'
						+ '	<label>'+get_lang('To')+':</label>'
						+ '	<input style="margin-left: 6px;" type="text" id="txt_para" size="39">'
						+ '	<br style="margin-bottom:15px"/>'
						+ '	<label>'+get_lang('Cc')+':</label>'
						+ '	<input style="margin-left: 6px;" type="text" id="txt_cc" size="39">'
						+ '	<br style="margin-bottom:15px"/>'
						+ '	<label>'+get_lang('Subject')+':</label>'
						+ '	<input style="margin-left: 6px;" type="text" id="txt_ass" size="39">'
						+ '	<br style="margin-bottom:15px"/>'
						+ '	<label>'+get_lang('Message body')+':</label>'
						+ '	<input style="margin-left: 6px;" type="text" id="txt_body" size="39">'

						+ '	<br style="margin-bottom:30px"/>'
                        + '     <label>'+get_lang("Since Date")+':</label>'
                        + '     <input style="margin-left: 6px;" type="text" id="since_date" size="8" maxlength="10" onkeypress="return dateMask(this, event);">'
                        + '     <img id="since_date-trigger" src="../phpgwapi/templates/default/images/datepopup.gif" title="'+get_lang("Select Date")+'" style="cursor:pointer; cursor:hand;"/>'
                        + '     <label style="margin-left: 20px;">'+get_lang('Before Date')+':</label>'
                        + '     <input style="margin-left: 6px;" type="text" id="before_date" size="8" maxlength="10" onkeypress="return dateMask(this, event);">'
                        + '     <img id="before_date-trigger" src="../phpgwapi/templates/default/images/datepopup.gif" title="'+get_lang("Select Date")+'" style="cursor:pointer; cursor:hand;"/>'
                        + '     <br style="margin-bottom:15px"/>'

                        + '     <label>'+get_lang('On Date')+':</label>'
                        + '     <input style="margin-left: 6px;" type="text" id="on_date" size="8" maxlength="10" onkeypress="return dateMask(this, event);">'
                        + '     <img style="margin-right: -8px" id="on_date-trigger" src="../phpgwapi/templates/default/images/datepopup.gif" title="'+get_lang("Select Date")+'" style="cursor:pointer; cursor:hand;"/><span>&nbsp;&nbsp;</span>'
                        + '     <br style="margin-bottom:30px"/>'

                        + '     <label>'+get_lang('Flags')+':</label>'
                        + '     <select style="width:15em; margin-left: 6px" name="flagged" id="flagged">'
                        + '     <option value=""/>'
                        + '     <option value="FLAGGED">'+ get_lang("Flagged") +'</option>'
                        + '     <option value="UNFLAGGED">'+ get_lang("Unflagged") +'</option>'
                        + '     </select>'
                        + '     <br style="margin-bottom:15px"/>'
                        + '     <select style="width:15em;" name="seen" id="seen">'
                        + '     <option value=""/>'
                        + '     <option value="SEEN">'+ get_lang("Seen") +'</option>'
                        + '     <option value="UNSEEN">'+ get_lang("Unseen") +'</option>'
                        + '     </select>'
                        + '     <br style="margin-bottom:15px"/>'
                        + '     <select style="width:15em;" name="answered" id="answered">'
                        + '     <option value=""/>'
                        + '     <option value="ANSWERED">'+ get_lang('Answered/Forwarded') +'</option>'
                        + '     <option value="UNANSWERED">'+ get_lang('Unanswered/Unforwarded') +'</option>'
                        + '     </select>'
                        + '     <br style="margin-bottom:15px"/>'
                        + '     <select style="width:15em;" name="recent" id="recent">'
                        + '     <option value=""/>'
                        + '     <option value="RECENT">'+ get_lang('Recent') +'</option>'
                        + '     <option value="OLD">'+ get_lang('Old') +'</option>'
                        + '     </select>'

                        + '	<br style="margin-bottom:60px"/>'
						+ '</fieldset>'
						+ '<fieldset style="width:350px; padding: 5px; position:absolute; margin-left: 414px">'
						+ '	<legend>'+get_lang('Search the messages in these folders')+'</legend>'
						+ '	<div id="folders" style="width:160px; height:150px;float:left;margin-bottom:10px;"></div>'
						+ '	<div style="float:left;height:100px;padding-top:50px;margin: 0 3px;">'
						+ '		<input type="button" id="incluir" name="incluir" value=">>" onclick="EsearchE.add_mailboxes()">'
						+ '		<br style="margin-bottom:15px">'
						+ '		<input type="button" id="excluir" name="excluir" value="<<" onclick="EsearchE.del_mailboxes()">'
						+ '	</div>'
						+ ' <div style="float:left;">'
						+ '	 <select multiple id="sel_search_nm_box1" name="sel_search_nm_box1" style="width:140px;height:150px;"></select>'
						+ ' </div>'
						+ '	<br clear="both">'
						+ '	<input type="checkBox" id="check_all_msg" name="check_all_msg" onclick="EsearchE.all_mailboxes()">'
						+ '	<b>'+get_lang('In all the folders')+'</b>'
						+ '</fieldset>';

		return form_sch;
	}

	// Pastas;
	searchE.prototype.mount_folders = function()
	{
		connector.loadScript("TreeS");

		if( Element("div_folders_search") == null)
		{
			var div_folders = document.createElement("DIV");
				div_folders.id = "div_folders_search";
				div_folders.style.width = "155px";
				div_folders.style.height = "152px";
				div_folders.style.borderStyle = "outset";
				div_folders.style.borderColor = "black";
				div_folders.style.borderWidth = "1px";
				div_folders.style.background  = "#F7F7F7";
				div_folders.style.overflow = "auto";
				div_folders.innerHTML = "";
				var dest_div = Element("folders");
				dest_div.appendChild(div_folders);
		}
		ttree.make_tree(folders,"div_folders_search","_folders_tree_search","","","","");
	}
	
	function openpage(data)
	{
		var _data			= [3];
		var _gears			= [];
    	var local_folders 	= [];

    	// Gears - local
		if ( preferences.use_local_messages == 1 )
		{
			temp = expresso_local_messages.list_local_folders();
			for (var x in temp)
			{
				local_folders.push(temp[x][0]);
			}
		}
		
		if ( local_folders.length > 0 )
			_gears = expresso_local_messages.search( local_folders, expresso_local_messages.getFilter() );

		_data['data'] 			= data['data'];
		_data['num_msgs']		= data['num_msgs'];
		_data['gears_num_msgs']	= _gears.length;

		delete_border( data['currentTab'], false);
		
		EsearchE.mount_result(_data);
	}

	searchE.prototype.show_paging = function(size)
	{
		var span_pg = Element("span_paging"+currentTab);
		
		if( span_pg == null )
		{
			span_pg 	= document.createElement('span');
			span_pg.id	= "span_paging"+currentTab;
		}
		else
			span_pg.innerHTML = "";
		
		if ( size > preferences.max_email_per_page )
		{
			for ( var i = (this.page > 2 ? this.page-2 : 0) ; i <= parseInt( this.page )+4 ; i+= 1 )
			{
				if( ( i * preferences.max_email_per_page ) > size)
				{
					break;
				}

				if( this.page == i )
				{
					var _link = document.createElement('span');
						_link.setAttribute("style", "font-weight:bold; color:red")
						_link.innerHTML = ( this.page + 1 ) + "&nbsp;&nbsp;";
				}
				else
				{
					var _page = i;
					var _link = document.createElement('A');					
						_link.innerHTML = ( _page + 1 ) + "&nbsp;&nbsp;";
						_link.href  = 'javascript:EsearchE.page='+i+';';
						_link.href += 'cExecute("$this.imap_functions.search_msg",openpage,"condition='+this.condition+'&sort_type='+this.sort_type+'&page='+_page+'&current_tab='+currentTab+'");';
				}
						
				span_pg.appendChild( _link );
			}

			Element("div_menu_c3").appendChild(span_pg);
		}
	}

	searchE.prototype.searchFor = function( borderID, sortType )
	{
		var border_id 	= borderID;
		var sort_type	= sortType;

		
		var args   = "$this.imap_functions.search_msg";
		var params = "condition="+EsearchE.condition+"&page="+EsearchE.page+"&sort_type="+sort_type;

		var handler = function( data )
		{
        	var allMsg			= [3];
    		var gears			= [];
    		var local_folders	= [];

    		if ( preferences.use_local_messages == 1 )
    		{
    			temp = expresso_local_messages.list_local_folders();
    			
    			for (var x in temp)
    			{
    				local_folders.push( temp[x][0] );
    			}

    		
    			if ( local_folders.length > 0 )
    				gears = expresso_local_messages.search( local_folders, expresso_local_messages.getFilter() );
    		}
        	
        	if( data['num_msgs'] )
            {
            	allMsg['data'] 				= data['data'];
            	allMsg['num_msgs']			= data['num_msgs'];
            	allMsg['gears_num_msgs'] 	= gears.length;
            }
        	
        	delete_border( border_id, false );
			
			EsearchE.mount_result( allMsg , sort_type ); 
		};
		cExecute(args,handler,params);
	}
	
	searchE.prototype.viewLocalMessage = function()
	{
		var data		  	= [2];
		var gears			= [];
		var local_folders	= [];
		
    	// Gears - local
		if ( preferences.use_local_messages == 1 )
		{
			temp = expresso_local_messages.list_local_folders();
			
			for (var x in temp)
			{
				local_folders.push( temp[x][0] );
			}

			if ( local_folders.length > 0 ){
				if (this.folders.length >0)
					gears = expresso_local_messages.search( this.folders, expresso_local_messages.getFilter() );
				else
					gears = expresso_local_messages.search( local_folders, expresso_local_messages.getFilter() );
			}
			data['data_gears']	= gears;
			data['num_msgs']	= gears.length;
	
        	write_msg( data['num_msgs'] + " " + get_lang("results found") );
			
			EsearchE.mount_result( data );
		}
	}
	
	// Form resultado
	searchE.prototype.mount_result = function( Data, sort_type )
	{
		var data = ( Data['data'] ) ? Data['data'] : Data['data_gears'];
		
		if ( data == undefined )
			return;
		
		var cont = parseInt(0);

		if ( typeof(sort_type) != 'undefined')
			this.sort_type = sort_type;
		else
			sort_type = this.sort_type;

		numBox++;

 		if( Data['data'] )
			var border_id = create_border(get_lang("Server Results"), "search_" + numBox,1);
		
		if( Data['data_gears'])
			var border_id = create_border(get_lang("Local Results"), "search_local_msg" + numBox,1);
			
		if (!border_id)
            return;

        currentTab = border_id;
        openTab.content_id[currentTab] = Element('content_id_search_' + numBox);
        openTab.type[currentTab] = 1;
                                   
		var table = document.createElement("TABLE");
			table.id    = "table_resultsearch_" + numBox;
			table.frame = "void";
			table.rules = "rows";
			table.cellPadding	= "0";
			table.cellSpacing	= "0";
			table.className		= "table_box";

		var tbody		= document.createElement("TBODY");
			tbody.id	= "tbody_box_" + numBox;

		for( var i=0; i < data.length; i++)
		{
			var tr = document.createElement("TR");

			if( typeof(preferences.line_height) != 'undefined' )
				tr.style.padding = preferences.line_height+'px 0';
				
			var aux = data[i];
			var mailbox = aux.boxname; 
			var uid_msg = aux.uid; 
			var subject = aux.subject; 
			
			tr.id = uid_msg+"_s"+global_search;

			// Keep the two lines together please
			tr.setAttribute('name',mailbox);
			tr.name = mailbox;

			if ( aux.flag.match("U") )
				add_className(tr,'tr_msg_unread');
			
			add_className(tr, i%2 != 0 ? 'tr_msg_read2' : 'tr_msg_read');
			
			var _onclick = function()
			{
				proxy_mensagens.get_msg(this.parentNode.id,url_encode(this.parentNode.getAttribute('name')),show_msg);
			};
			
            for(var j=0 ; j <= 10 ; j++)
			{
				var td = document.createElement("TD");
				if (j == 0)
				{
					td.style.width = "1%";
					var td1 = '<input type="checkbox" id="search_' + numBox + '_check_box_message_'+uid_msg+'"></input>';
					
				}
				if (j == 1)
				{
					td.style.width = "2%";
					if (aux.flag.match('T'))
					{
						attachNum = parseInt(aux.flag.substr(aux.flag.indexOf('T')+1));
						td1 = "<img src='templates/"+template+"/images/clip.gif' title='"+attachNum +' '+ get_lang('attachment(s)')+"'>";
					}
					else
						td1 = '';
				}
				if (j == 2)
				{
					td.style.width = "1%";
					td.id = "td_message_answered_"+uid_msg;
					if (aux.flag.match('X'))
						td1 = '<img src=templates/'+template+'/images/forwarded.gif title="'+get_lang('Forwarded')+'">';
					else
						if (aux.flag.match('A'))
							td1 = '<img src=templates/'+template+'/images/answered.gif title="'+get_lang('Answered')+'">';
						else
							td1 = '';
				}
				if (j == 3)
				{
					td.style.width = "1%";
					td.id = "td_message_important_"+uid_msg;
					if (aux.flag.match("F"))
					{
						add_className(tr, 'flagged_msg');
						td1 = "<img src='templates/"+template+"/images/important.gif' title='"+get_lang('Flagged')+"'>";
					}
					else
						td1 = '';
				}
				if (j == 4)
				{
					td.style.width = "1%";
					td.id = "td_message_sent_"+uid_msg;
					td1 = '';
				}
				
				if ( j == 5 )
				{
					td.style.width = "20%";
					td.onclick = _onclick;
					var nm_box = aux.boxname.split(cyrus_delimiter);
					var td1 = nm_box.pop();
					td.setAttribute("NoWrap","true");
					td.style.overflow = "hidden";
					td.style.color = "#42795b";
					td.style.fontWeight = "bold";
					
					var td1  = get_lang(td1).substr(get_lang(td1).length-1) == "*"?td1:get_lang(td1);
                    if ((tmp = translatedFolders.get(td1)))
                    {
                        td1 = tmp;
                    }

					if( proxy_mensagens.is_local_folder(td1))
					{
						var td1 = this.aux_local_folder_display(td1);
					}
				}
				
				if( j == 6 )
				{
					if (aux.from.length > 29)
						aux.from = aux.from.substr(0,29) + "...";
					
					td.style.width = "20%";
					td.onclick = _onclick;
					td.setAttribute("NoWrap","true");
                                        td.style.overflow = "hidden";
					var td1  =  '<div style="width:100%;overflow:hidden">'+aux.from+"</div>";
				}
				
				if( j == 7 )
				{
					var subject_encode = url_encode(subject);
					
					if (! subject_encode)
						aux.subject = get_lang("no subject") + "...";
					if (aux.subject.length > 70)
						aux.subject = aux.subject.substr(0,70) + "...";
					
					td.style.width = "35%";
					td.onclick = _onclick;
					td.setAttribute("NoWrap","true");
                    td.style.overflow = "hidden";

                    var td1  = aux.subject;
				}
				
				if( j == 8 )
				{
					td.style.width	= "13%";
					td.align		= "center";
					td.onclick		= _onclick;

					if( validate_date( aux.udate ) )
					{
						var td1 = aux.udate;
					}
					else
					{
						var dt	= new Date( aux.udate * 1000 );
	                    var td1	 = dt.getDate() + "/";
	                    
	                    if( !( dt.getMonth() + 1 ).toString().match(/\d{2}/) )
	                    	td1 += "0"+( dt.getMonth() + 1 ) + "/";
	                    else
	                    	td1 += ( dt.getMonth() + 1 ) + "/";
	                    
	                    td1 += dt.getFullYear();
					}
				}

				if( j == 9 )
				{
					td.style.width = "10%";
					td.align = "center";
					td.onclick = _onclick;
					var td1  = borkb(aux.size);
				}
				
				if( j == 10 )
				{
					if (aux.flag.match("U"))
						add_className(tr, 'tr_msg_unread');
					if (aux.flag.match("F"))
						add_className(tr, 'flagged_msg');
					var td1 = '';
				}
				td.innerHTML = td1;
				tr.appendChild(td);
			}
		
			$(tr).on("oncontextmenu", function(e){ return false; });

			$(tr).on("mousedown", function(e)
			{ 
	    		if (typeof e.preventDefault != 'undefined')
					e.preventDefault();
				else
					e.onselectstart = new Function("return false;");

			    _dragArea.makeDraggedMsg( $(this) , e );
			});

            tbody.appendChild(tr);
		}
		
		global_search++; //Tabs from search must not have the same id on its tr's
		
		table.appendChild(tbody);

		var content_search =  Element('content_id_search_' + numBox);
		var div_scroll_result = document.createElement("DIV");
			div_scroll_result.id = "divScrollMain_"+numBox;
			div_scroll_result.style.overflow = "auto";
	
		if(is_ie)
			Element("border_table").width = "99.5%";

		// Put header
		var table_element = document.createElement("TABLE");
		var tbody_element = document.createElement("TBODY");
		table_element.setAttribute("id", "table_message_header_box");
		table_element.className = "table_message_header_box";
		tr_element = document.createElement("TR");
		tr_element.className = "message_header";
		td_element0 = document.createElement("TD");
		td_element0.setAttribute("width", "7%");
		chk_box_element = document.createElement("INPUT");
		chk_box_element.id  = "chk_box_select_all_messages";
		chk_box_element.setAttribute("type", "checkbox");
		chk_box_element.className = "checkbox";
		chk_box_element.onclick = function(){select_all_search_messages(this.checked,content_search.id);};
		chk_box_element.onmouseover = function () {this.title=get_lang('Select all messages.')};
		chk_box_element.onkeydown = function (e)
		{
			if (is_ie)
			{
				if ((window.event.keyCode) == 46)
					delete_msgs(current_folder,'selected','null');
			}
			else
			{
				if ((e.keyCode) == 46)
					delete_msgs(current_folder,'selected','null');
			}
		};

		td_element0.appendChild(chk_box_element);
		td_element1 = document.createElement("TD");
		td_element1.setAttribute("width", "20%");
		td_element1.align = "left";
		
		var arrow_ascendant = function(Text)
		{
			return "<b>" + Text + "</b><img src='templates/"+template+"/images/arrow_ascendant.gif'>";
		}
		
		// Ordernar Pasta
		if ( sort_type == 'SORTBOX')
		{
			if( Data['data'] )
			{
				td_element1.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTBOX_REVERSE'); };
				td_element1.innerHTML	= "<b>"+get_lang("Folder")+"</b><img src='templates/"+template+"/images/arrow_descendant.gif'>";
			}
			else
			{
				td_element1.innerHTML	= "<b>"+get_lang("Folder")+"</b>";
			}
		}
		else
		{
			if( Data['data'] )
			{
				td_element1.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTBOX'); };
			}
			else
			{
				//alert('Ordenando localMessage');
			}
			td_element1.innerHTML	= ( sort_type == 'SORTBOX_REVERSE' ) ? arrow_ascendant(get_lang("Folder")) : get_lang("Folder");
		}
		
		// Ordernar Quem
		td_element2 = document.createElement("TD");
		td_element2.setAttribute("width", "20%");
		td_element2.align = "left";

		if (sort_type == 'SORTWHO')
		{
			if(Data['data'])
			{
				td_element2.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTWHO_REVERSE'); };
				td_element2.innerHTML	= "<b>"+get_lang("who")+"</b><img src='templates/"+template+"/images/arrow_descendant.gif'>";
			}
			else
			{
				td_element2.innerHTML	= "<b>"+get_lang("who")+"</b>";
			}
		}
		else
		{
			if( Data['data'] )
			{
				td_element2.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTWHO'); };
			}
			else
			{
				//alert('Ordenando localMessage');
			}
			td_element2.innerHTML	= ( sort_type == 'SORTWHO_REVERSE' ) ? arrow_ascendant(get_lang("who")) : get_lang("who");
		}
		
		// Ordernar Subject
		td_element3 = document.createElement("TD");
		td_element3.setAttribute("width", "35%");
		td_element3.align = "left";
		
		if (sort_type == 'SORTSUBJECT')
		{
			if( Data['data'])
			{
				td_element3.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTSUBJECT_REVERSE'); };
				td_element3.innerHTML	= "<b>"+get_lang("subject")+"</b><img src='templates/"+template+"/images/arrow_descendant.gif'>";				
			}
			else
			{
				td_element3.innerHTML	= "<b>"+get_lang("subject")+"</b>";
			}
		}
		else
		{
			if( Data['data'] )
			{
				td_element3.onclick		= function(){ EsearchE.searchFor( border_id, 'SORTSUBJECT'); };
			}
			else
			{
				//alert('Ordenando localMessage');
			}
			td_element3.innerHTML	= ( sort_type == 'SORTSUBJECT_REVERSE' ) ? arrow_ascendant(get_lang("subject")) : get_lang("subject");
		}
		
		// Ordernar Data
		td_element4 = document.createElement("TD");
		td_element4.setAttribute("width", "12%");
		td_element4.align = "center";
		
		if ( sort_type == 'SORTDATE' )
		{
			if( Data['data'] )
			{
				td_element4.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTDATE_REVERSE'); };
				td_element4.innerHTML	= "<b>"+get_lang("Date")+"</b><img src='templates/"+template+"/images/arrow_descendant.gif'>";
			}
			else
			{
				td_element4.innerHTML	= "<b>"+get_lang("Date")+"</b>";
			}
		}
		else
		{
			if( Data['data'] )
			{
				td_element4.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTDATE'); };
			}
			else
			{
				//alert('Ordenando localMessage');
			}
			td_element4.innerHTML	= ( sort_type == 'SORTDATE_REVERSE' ) ? arrow_ascendant(get_lang("Date")) : get_lang("Date");
		}			

		// Ordernar Tamanho
		td_element5 = document.createElement("TD");
		td_element5.setAttribute("width", "8%");
		td_element5.align = "center";
		
		if ( sort_type == 'SORTSIZE' )
		{
			if( Data['data'] )
			{
				td_element5.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTSIZE_REVERSE'); };
				td_element5.innerHTML	= "<b>"+get_lang("size")+"</b><img src='templates/"+template+"/images/arrow_descendant.gif'>";				
			}
			else
			{
				td_element5.innerHTML	= "<b>"+get_lang("size")+"</b>";
			}
		}
		else
		{
			if( Data['data'] )
			{	
				td_element5.onclick		= function(){ EsearchE.searchFor(border_id, 'SORTSIZE'); };
			}
			else
			{
				//alert('Ordenando localMessage');
			}
			td_element5.innerHTML	= ( sort_type == 'SORTSIZE_REVERSE' ) ? arrow_ascendant(get_lang("size")) : get_lang("size");
		}
		
		tr_element.appendChild(td_element0);
		tr_element.appendChild(td_element1);
		tr_element.appendChild(td_element2);
		tr_element.appendChild(td_element3);
		tr_element.appendChild(td_element4);
		tr_element.appendChild(td_element5);
		tbody_element.appendChild(tr_element);
		table_element.appendChild(tbody_element);

		if( parseInt( Data['gears_num_msgs'] ) > 0 )
		{
			var _div_gears = document.createElement("div");
				_div_gears.onclick = function(){ EsearchE.viewLocalMessage(); };
				_div_gears.setAttribute("style", "cursor: pointer; background: none repeat scroll 0% 0% rgb(255, 238, 187); color: red; line-height: 2em; font-size: 1.2em; text-align: center;");
				_div_gears.innerHTML = get_lang("The search has% 1 messages stored locally. Want to see them ? Click here.", Data['gears_num_msgs']);

			content_search.appendChild(_div_gears);		
		}		

		content_search.appendChild(table_element);

		/*end of "put header"*/
		if ( !expresso_offline )
		{
			div_scroll_result.appendChild(table);
			content_search.appendChild(div_scroll_result);
		}
		else
		{
			div_scroll_result.appendChild(table);
			content_search.appendChild(div_scroll_result);
		}
		
		resizeWindow();

		EsearchE.show_paging( Data['num_msgs'] );
	}

	searchE.prototype.open_msg = function(mailbox, uid_msg, subject)
	{
		var handler_get_msg = function(data)
		{
			if( Element("border_id_" + uid_msg + "_r") )
				alert(get_lang("This message is already opened!"));
			else
				draw_message( data, create_border(url_decode(subject), uid_msg + "_r") );
		}
		
		proxy_mensagens.get_msg(uid_msg,mailbox,handler_get_msg);
	}

	// Adiciona caixas postais na busca;
	searchE.prototype.add_mailboxes = function()
	{
		var sel = Element("sel_search_nm_box1");
		if (!proxy_mensagens.is_local_folder(this.name_box_search)) {
			var name_box     = this.name_box_search.split(cyrus_delimiter);
			if(this.name_box_search == "")
				return false;
			var name_box_def = "";
			if(name_box.length != 1){
				name_box_def = name_box[(name_box.length-1)];
			}else{
				name_box_def = get_lang("Inbox");
			}
		}
		else {
			if(this.name_box_search=='local_root')
				return;
			if(this.name_box_search=='local_Inbox')
				name_box_def = get_lang("Inbox");
			else if(this.name_box_search.indexOf("/")!="-1") {
				final_pos = this.name_box_search.lastIndexOf("/");
				name_box_def = this.name_box_search.substr(final_pos+1);
			}
			else
				name_box_def = this.name_box_search.substr(6);//Retira o 'local_'
		}
		if( sel.length > 0){
			for(var i=0; i < sel.options.length; i++){
				if(sel.options[i].value == this.name_box_search){
					alert(get_lang('This message is already selected!'));
					return false;
				}
			}
		}
		var opt = new Option(lang_folder(name_box_def),this.name_box_search,false,true);
		sel[sel.length] = opt;
	}

	//	Remove as caixas postais na busca;
	searchE.prototype.del_mailboxes = function()
	{
		var sel = Element("sel_search_nm_box1");
		if(sel.length > 0)
		{
			for(var i=0; i < sel.options.length; i++)
			{
				if(sel.options[i].selected == true)
				{
					sel.options[i] = null;
					i--;
				}
			}
		}

	}

	// todas as caixas
	searchE.prototype.all_mailboxes = function()
	{
		var value = Element("check_all_msg").checked;
		var cont = parseInt(0);
		if(value)
		{
			if(EsearchE.all_boxes.length > 0)
			{
				EsearchE.all_boxes.splice(0,(EsearchE.all_boxes.length));
			}
			for(var i=0; i < folders.length; i++)
			{
				EsearchE.all_boxes[cont++] = folders[i].folder_id;
			}
		}
		else
		{
			EsearchE.all_boxes.splice(0,(EsearchE.all_boxes.length));
		}
	}

	// Search;
	searchE.prototype.func_search = function(value)
	{
		var fields = "##";
			// Verifica se os campos estão preenchidos;
			if(trim(Element("txt_ass").value) != ""){
				fields += "SUBJECT " +  "<=>" +url_encode(Element("txt_ass").value) + "##";
			}
			if(trim(Element("txt_body").value) != ""){
				fields += "BODY " + "<=>" + url_encode(Element("txt_body").value) + "##";
			}
			if(trim(Element("txt_de").value) != ""){
				fields += "FROM " + "<=>" + url_encode(Element("txt_de").value) + "##";
			}
			if(trim(Element("txt_para").value) != ""){
				fields += "TO " + "<=>" + url_encode(Element("txt_para").value) + "##";
			}
			if(trim(Element("txt_cc").value) != ""){
				fields += "CC " + "<=>" + url_encode(Element("txt_cc").value) + "##";
			}
            if (trim(Element("since_date").value) != "")
            {
                if (validate_date(Element("since_date").value))
                {
                    fields += "SINCE " + "<=>" + url_encode(Element("since_date").value) + "##";
                }
                else
                {
                	alert(get_lang('Invalid date on field %1', get_lang('Since Date')));
                	return false;
                }
            }

            if (trim(Element("before_date").value) != "")
            {
                if (validate_date(Element("before_date").value))
                {
                    fields += "BEFORE " + "<=>" + url_encode(Element("before_date").value) + "##";
                }
                else
                    {
                        alert(get_lang('Invalid date on field %1', get_lang('Before Date')));
                        return false;
                    }
            }

            if(trim(Element("on_date").value) != "")
            {
                if (validate_date(Element("on_date").value))
                {
                    fields += "ON " + "<=>" + url_encode(Element("on_date").value) + "##";
                }
                else
                {
                	alert(get_lang('Invalid date on field %1', get_lang('On Date')));
                    return false;
                }

            }

            if(trim(Element("flagged").options[Element("flagged").selectedIndex].value) != "")
            {
                if (Element("flagged").options[Element("flagged").selectedIndex].value == "FLAGGED")
                {
                    fields += "FLAGGED##";
                }
                else
                {
                    fields += "UNFLAGGED##";
                }
            }

            if(trim(Element("seen").options[Element("seen").selectedIndex].value) != "")
            {
                if (Element("seen").options[Element("seen").selectedIndex].value == "SEEN")
                {
                    fields += "SEEN##";
                }
                else
                {
                    fields += "UNSEEN##";
                }
            }
            
            if(trim(Element("answered").options[Element("answered").selectedIndex].value) != "")
            {
                if (Element("answered").options[Element("answered").selectedIndex].value == "ANSWERED"){
                    fields += "ANSWERED##";
                }
                else {
                    fields += "UNANSWERED##";
                }
            }
            
            if(trim(Element("recent").options[Element("recent").selectedIndex].value) != "")
            {
                if (Element("answered").options[Element("answered").selectedIndex].value == "RECENT")
                {
                    fields += "RECENT##";
                }
                else
                {
                    fields += "OLD##";
                }
            }

            if ( value )
            {
				fields = "##ALL " +  "<=>" +url_encode(value) + "##";
			}

		if(fields == "##")
		{
			alert(get_lang("Define some search parameters!"));
			return false;
		}
		
		var local_folders = new Array();
		var temp;

		if( Element("check_all_msg").checked )
		{
			this.all_mailboxes();
			var nm_box = new Array;
			for(var i=0; i < EsearchE.all_boxes.length; i++)
			{
				nm_box[i] = EsearchE.all_boxes[i] + fields;
			}
			if (preferences.use_local_messages == 1)
			{
				temp = expresso_local_messages.list_local_folders();
				for (var x in temp)
				{
					local_folders.push(temp[x][0]);
				}
			}
		}
		else
		{
			var nm_box = new Array;
			var sel_combo = Element("sel_search_nm_box1");
			
			if( sel_combo.options.length <= 0)
			{
				alert(get_lang("Define the boxes to search!"));
				return false;
			}

			for(var i=0; i < sel_combo.options.length; i++)
			{
				sel_combo.options[i].selected = true;
			}
			
			for(var i=0; i < sel_combo.options.length; i++)
			{
				if( sel_combo.options[i].selected == true )
				{
					if(!proxy_mensagens.is_local_folder(sel_combo.options[i].value))
						nm_box[nm_box.length] = sel_combo.options[i].value + fields;
					else
						local_folders.push(sel_combo.options[i].value.substr(6));
				}
			}
		}
		
		var handler = function( data )
		{
        	var allMsg 	= [3];
        	var count  	= ( data['num_msgs'] ) ?  data['num_msgs'] : "0";
			var tmp		= [];

			// Gears - local
			if ( local_folders.length > 0 ){
				tmp = expresso_local_messages.search( local_folders, fields );
			}
            if( data['num_msgs'] )
            {
            	allMsg['data'] 		= data['data'];
            	allMsg['num_msgs']	= data['num_msgs'];
            } 

        	if( tmp.length > 0 )	
            {
        		allMsg['gears_num_msgs'] = tmp.length ;
            }
            
        	if( ( data['num_msgs'] ) == 0 )
        	{
                alert( get_lang("None result was found.") );
        	}
            else
            {
            	if( (tmp.length > 0) && (!data['num_msgs']) )
            	{
            		EsearchE.folders = local_folders; 
            		EsearchE.viewLocalMessage();
            	}
            	else
            	{
            		write_msg( count + " " + get_lang("results found") );
            		EsearchE.mount_result( allMsg, 'SORTDATE' );
            	}
            }
		}

		this.condition	= nm_box;
		this.page		= 0;
		var args		= "$this.imap_functions.search_msg";
		var params		= "condition=" + nm_box+ "&page=0"+ "&sort_type=SORTDATE";
		
		if( expresso_offline )
			handler('none');
		else
			cExecute( args, handler, params);
	}
	// clean;
	searchE.prototype.func_clean = function()
	{
		// Limpa os campos;
		for( var i=0; i < this.txtfields.length; i++ )
		{
			if( Element(this.txtfields[i]) != null )
				Element(this.txtfields[i]).value = "";
		}

        for(i = 0; i < this.selectFields.length; i++)
        {
            if (Element(this.selectFields[i]))
                Element(this.selectFields[i]).selectedIndex = 0;
        }
        
	    if( Element("check_all_msg") != null )
	    	Element("check_all_msg").checked = false;

	    EsearchE.all_boxes.splice(0,(EsearchE.all_boxes.length));
	  	EsearchE.del_mailboxes();
	}

	// close
	searchE.prototype.func_close = function(type)
	{
		var _this = this;
		_this.name_box_search = "";
		EsearchE.all_boxes.splice(0,(EsearchE.all_boxes.length));
		_this.type = type;
		_this.searchW['window_search'].close();
	}
	// Monta a janela em tela;
	searchE.prototype.showWindow = function(div)
	{
		if(!this.searchW[div.id])
		{
			div.style.width = "804px";
			div.style.height = "440px";
			div.style.visibility = "hidden";
			div.style.position = "absolute";
			div.style.zIndex = "10003";
			var title = ":: "+ get_lang("Search")+" ::";
			var wHeight = div.offsetHeight + "px";
			var wWidth =  div.offsetWidth   + "px";

			win = new dJSWin({
				id: 'ccList'+div.id,
				content_id: div.id,
				width: wWidth,
				height: wHeight,
				title_color: '#3978d6',
				bg_color: '#eee',
				title: title,
				title_text_color: 'white',
				button_y_img: '../phpgwapi/images/win_min.gif',
				button_x_img: '../phpgwapi/images/winclose.gif',
				border: true});
			this.searchW[div.id] = win;
			win.draw();
		}
		else
		{
			var _this = this;
			win = this.searchW[div.id];
			if((_this.type == "close" && win.state == 0) || win.state == 2){EsearchE.func_clean();}
			win.draw();
		}
		win.open();	
	}

	searchE.prototype.aux_local_folder_display = function(folder)
	{
		if(!expresso_offline)
			return "(Local) " + lang_folder(folder.substr(6));
		else
			return lang_folder(folder.substr(6));
	}

// Cria o objeto
	var EsearchE;
	EsearchE = new searchE();
