	function cQuickAddContact ()
	{
		this.arrayWin = new Array();
		this.el;
		this._nFields = 5;
	}

	
	cQuickAddContact.prototype.showList = function(data){
		_this = this;

		var	cc_data = (typeof data) == 'object' ? data : data.split(',');
		
		if (document.getElementById('cc_rectQuickAddContact') == null){		
			el = document.createElement("DIV");
			el.style.visibility = "hidden";									
			el.style.position = "absolute";
			el.style.left = "0px";
			el.style.top = "0px";
			el.style.width = "0px";
			wHeight = 165;
			el.style.height = wHeight + 'px';
			el.className = "div_cc_rectQuickAddContact";
			el.id = 'cc_rectQuickAddContact';
			document.body.appendChild(el);																
		}
		else {
			el = document.getElementById('cc_rectQuickAddContact');
		}
		el.innerHTML = "";
		var fieldsTop = 10;
		var fieldsSpace = 30;
		var lang_save 	= get_lang('Save');
		var lang_cancel = get_lang('Cancel');
		
		//funcao lang
		fields = new Array(get_lang('Nickname')+":", get_lang('First Name')+":", get_lang('Last Name')+":", 'E-mail:');
		for (i=0; i<fields.length; i++) {
			el.innerHTML += '<span id="ccQuickAddCT' + i + '" style="position: absolute; top: ' +  (fieldsTop+i*fieldsSpace) + 'px; left: 5px; width: 100px; text-align: right; border: 0px solid #999;">' + fields[i] + '</span>';
			el.innerHTML += '<input id="ccQuickAddCI' + i + '" type="text" value="' + cc_data[i] + '" maxlength="50" style="position: absolute; top: ' + (fieldsTop+i*fieldsSpace) + 'px; left: 110px; width: 135px;">';
		}
		el.innerHTML +='<div id="ccQAFunctions" style="border: 0px solid black; width: 220px; height: 20px">' +
			'<input title="'+lang_save+'" type="button" onclick="ccQuickAddOne.send(\''+data+'\');" value="'+lang_save+'" style="position: absolute; top: ' + (fieldsTop+(i*fieldsSpace)) + 'px; left: 75px; width: 60px" />' +
			'<input title="'+lang_cancel+'" type="button" onclick="ccQuickAddOne.fechar(\'' + id + '\');" value="'+lang_cancel+'" style="position: absolute; top: ' + (fieldsTop+(i*fieldsSpace)) + 'px; left: 140px; width: 60px" />' +
			'</div>';
		el.innerHTML +=	"<br>";
		_this.showWindow(el);

	}
		
	cQuickAddContact.prototype.showWindow = function (div)
	{						
		if(! this.arrayWin[div.id]) {
			win = new dJSWin({			
				id: 'ccQuickAddOne_'+div.id,
				content_id: div.id,
				width: '255px',
				height: wHeight+'px',
				title_color: '#3978d6',
				bg_color: '#eee',
				title: get_lang("Quick Add"),
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
	
	cQuickAddContact.prototype.send = function (data)
	{
		
		var handler = function (responseText)
		{
			var data = responseText;
			if (!data || typeof(data) != 'object')
			{
				write_msg("Problema ao contactar servidor");
				return;
			}
			else if (data['status'] == 'alreadyExists')
			{
				alert(data['msg']);
				return;
			}
			else if (data['status'] != 'ok')
			{
				return;
			}
			contacts += ',' + full_name + ';' + email;
			write_msg("Contato adicionado com sucesso.");
			win.close();

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
				
		var _this = this;
		var sdata = data.split(",");
		var temp = sdata[3];
		sdata[3]='';
		sdata[4]=temp;
		sdata = new Array();

		for (i=0; i< fields.length; i++) {			
			if(i == 3){
				sdata[i] = '';
				sdata[i+1] = document.getElementById("ccQuickAddCI" + i).value;
			}
			else
				sdata[i] = document.getElementById("ccQuickAddCI" + i).value;
		}

		var full_name = trim(sdata[1]) + ' ' + trim(sdata[2]);
		var email = trim(sdata[4]);
		if(email == ''){
			alert(get_lang("QuickAddEmptyMail",email));
			return false;
		}
		if(!validateEmail(email)){
			//alert(get_lang("The email address %1 is not valid, please use a valid address.",email));
			//alert("O endereco de e-mail %1 nao e valido, por favor use uma e-mail validoooo.",email);
			alert(get_lang("QuickAddInvalidMail",email));
			return false;
		}

		var sdata = 'add='+escape(connector.serialize(sdata));
		var CC_url = '../index.php?menuaction=contactcenter.ui_data.data_manager&method=';
		connector.newRequest('cQuickAdd.Send', CC_url+'quick_add', 'POST', handler, sdata);
	}
	
	cQuickAddContact.prototype.fechar = function(id) {
		var div = document.getElementById('cc_rectQuickAddContact');
		win = this.arrayWin[div.id];
		win.close();
	}
	
	
/* Build the Object */
	var	ccQuickAddOne = new cQuickAddContact();
