	function emQuickSearchUser ()
	{
		this.arrayWin = new Array();
		this.el;
		this.cc_contacts = new Array();
		this.cc_groups  = new Array();
	}

	emQuickSearchUser.prototype.showList = function(data)
	{
		id = '1';
		_this = this;

		var el = document.createElement("DIV");
		el.style.visibility = "hidden";
		el.style.position = "absolute";
		el.style.left = "0px";
		el.style.top = "0px";
		el.style.width = "0px";
		el.style.height = "0px";
		el.id = 'window_QuickSearchUser';
		document.body.appendChild(el);
		el.innerHTML = '<br>';
		
		if (document.getElementById('div_QuickSearchUser') == null)
		{
			el.innerHTML += '<div id="div_QuickSearchUser" class="quicksearchcontacts">' + 
			'<table class="quicksearchcontacts"><tbody id="table_QuickSearchUser">' + data + '</tbody></table>' +
			'</div>';
			el.innerHTML += '&nbsp;&nbsp;<input type="button" value=' + get_lang("Close")+ ' id="QuickSearchUser_button_close" onClick="QuickSearchUser.close_window();">';
		}
		else
		{
			var div_QuickSearchUser = document.getElementById('div_QuickSearchUser');
			div_QuickSearchUser.style.display = "";
			div_QuickSearchUser.innerHTML = '<table class="quicksearchcontacts"><tbody id="table_QuickSearchUser">' + data + '</tbody></table>';
			var butt_close = document.getElementById("QuickSearchUser_button_close");
			butt_close.onclick = function () {QuickSearchUser.close_window();};
		}
		_this.showWindow(el);

		// Expresso Messenger Disabled
		$(el).find("img").each(function()
		{
			var _flag = false;

			if( $(this).attr("src").indexOf("add_user.png") > -1 )
			{
				if( $("input[name=expresso_messenger_enabled]").length == 0 )
				{
					$(this).remove();
				}
			}	
		});
	}

	emQuickSearchUser.prototype.showWindow = function (div)
	{
		if(! div) {
			alert(get_lang('The list has no participant.'));
			return;
		}
							
		if(! this.arrayWin[div.id]) {
			div.style.width = "600px";
			div.style.height = "350px";
			var title = get_lang('The results were found in the Global Catalog')+':';
			var wHeight = div.offsetHeight + "px";
			var wWidth =  div.offsetWidth   + "px";
			div.style.width = div.offsetWidth - 5;

			win = new dJSWin({			
				id: 'QuickSearchUser_'+div.id,
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
		win.open();
	}

	emQuickSearchUser.prototype.close_window = function() {
		Element("em_message_search").value = "";
		this.arrayWin['window_QuickSearchUser'].close();
	}
	
	emQuickSearchUser.prototype.create_new_message = function (cn, mail) {
		QuickSearchUser.close_window();
		if (openTab.type[currentTab] != 4)
		{
			Element("msg_number").value = "\""+cn+"\" <"+mail+">";
			new_message("new","null");
		}
		else
		{
			var ToField = Element('to_'+currentTab);
			ToField.value = ToField.value +"\""+cn+"\" <"+mail+">,";
		}
	}

/* Build the Object */
	var QuickSearchUser;
	QuickSearchUser = new emQuickSearchUser();
