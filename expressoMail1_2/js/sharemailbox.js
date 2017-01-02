	function cShareMailbox()
	{
		this.arrayWin = new Array();
		this.el;
		this.alert = false;
                this.context = "";
                this.finderTimeout = '';
	}
        
    cShareMailbox.prototype.get_available_users = function(context)
    {
        if( sharedFolders_users_auto_search.toString() === "true" )
        {    
            this.get_available_users2(context);
        }
    }

	cShareMailbox.prototype.get_available_users2 = function()
	{
            var context = "";
            var cn      = "";
            
            var handler_get_available_users = function(data)
            {
                    select_available_users = document.getElementById('em_select_available_users');

                    //Limpa o select
                    for(var i=0; i<select_available_users.options.length; i++)
                    {
                            select_available_users.options[i] = null;
                            i--;
                    }

                    if ((data) && (data.length > 0))
                    {
                            // Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
                            select_available_users.innerHTML = '#' + data;
                            select_available_users.outerHTML = select_available_users.outerHTML;

                            select_available_users.disabled = false;
                            select_available_users_clone = document.getElementById('em_select_available_users').cloneNode(true);
                            document.getElementById('em_input_searchUser').value = '';
                    }
            }

            if( arguments.length > 1 )
            {
                context = arguments[0];
                cn      = arguments[1];
                cExecute ("$this.ldap_functions.get_available_users2&context="+context+"&cn="+cn, handler_get_available_users);
            }   
            else
            {
                context = arguments[0];
                cExecute ("$this.ldap_functions.get_available_users2&context="+context, handler_get_available_users);
            }
            
	}

	cShareMailbox.prototype.getaclfromuser = function(user)
	{
		var handler_getaclfromuser = function(data)
		{
			Element('em_input_readAcl').checked = false;
			Element('em_input_deleteAcl').checked = false;
			Element('em_input_writeAcl').checked = false;
			Element('em_input_sendAcl').checked = false;
			Element('em_input_saveAcl').checked = false;
			Element('em_input_saveAcl').disabled = true;
			
			if (data[user].indexOf('lrs',0) >= 0)
			{
				Element('em_input_sendAcl').disabled = false;
				Element('em_input_readAcl').checked = true;
			}
			else
				Element('em_input_sendAcl').disabled = true;
				
			if (data[user].indexOf('d',0) >= 0)
			{
				Element('em_input_deleteAcl').checked = true;
			}
			if (data[user].indexOf('wi',0) >= 0)
			{
				Element('em_input_writeAcl').checked = true;
			}
			
			if (data[user] != "false" && data[user].indexOf('a',0) >= 0)
			{
				Element('em_input_sendAcl').disabled = false;
				Element('em_input_sendAcl').checked = true;
			}
			if (data[user] != "false" && data[user].indexOf('p',0) >= 0)
			{
				Element('em_input_saveAcl').disabled = false;
				Element('em_input_saveAcl').checked = true;
			} 
			if( data[user] != "false" && Element('em_input_writeAcl').checked && Element('em_input_sendAcl').checked ){
				Element('em_input_saveAcl').disabled = false;
			} else Element('em_input_saveAcl').disabled = true;
		}
		cExecute ("$this.imap_functions.getaclfromuser&user="+user, handler_getaclfromuser);
	}
	
	cShareMailbox.prototype.setaclfromuser = function()
	{
		var acl		= '';
		var select 	= Element('em_select_sharefolders_users');

		if(select.selectedIndex == "-1"){
			alert("Selecione antes um usuário!");
			return false;
		}
		var user = select.options[select.selectedIndex].value;
		
		if (Element('em_input_readAcl').checked) {
			Element('em_input_sendAcl').disabled = false;
			acl = 'lrs';
		}
		else{
			Element('em_input_sendAcl').disabled = true;
			Element('em_input_sendAcl').checked = false;
		}
				
		if (Element('em_input_deleteAcl').checked)
			acl += 'd';

		if (Element('em_input_writeAcl').checked) {
			acl += 'wi';			
		}		
		if (Element('em_input_sendAcl').checked){
			acl += 'a';			
		}
		if (Element('em_input_sendAcl').checked && Element('em_input_writeAcl').checked){
			Element('em_input_saveAcl').disabled = false;				
		} else {
			Element('em_input_saveAcl').disabled = true;
			Element('em_input_saveAcl').checked = false;
		}
		
		if (Element('em_input_saveAcl').checked)
			acl += 'p';
		
		var handler_setaclfromuser = function(data) {
			if (!data)
				alert(data);	
			return true;
		}
		cExecute ("$this.imap_functions.setaclfromuser&user="+user+"&acl="+acl, handler_setaclfromuser);
	}
	
	cShareMailbox.prototype.makeWindow = function(options)
	{
		_this = this;
	
		var el = document.createElement("DIV");
                    el.style.visibility = "hidden";
                    el.style.position = "absolute";
                    el.style.left = "0px";
                    el.style.top = "0px";
                    el.style.width = "0px";
                    el.style.height = "0px";
                    el.id = 'dJSWin_sharefolders';
		
                document.body.appendChild(el);

		if (Element('em_select_sharefolders_users'))
		{
			var select_users = Element('em_select_sharefolders_users');
                            select_users.innerHTML = '#' + options;
                            select_users.outerHTML = select_users.outerHTML;
			
			Element('em_input_readAcl').checked     = false;
			Element('em_input_deleteAcl').checked   = false;
			Element('em_input_writeAcl').checked    = false;
			Element('em_input_sendAcl').checked     = false;
			Element('em_input_saveAcl').checked     = false;
		}
		else
		{
                    el.innerHTML =  '<div style="width:645px; height:340px; margin: 2px !important; ">'+
                                        '<fieldset style="height:300px;">'+
                                            '<div style="width:500px; height:15px; font-size:8pt; color:red;">'+
                                                get_lang('Note: This sharing will take action on all of your folders and messages.')+
                                            '</div>'+
                                            '<br clear="all"/>'+
                                            '<div style="width:250px; height: 300px; position:aboslute; float:left;">'+
                                                '<label>'+get_lang('Organization')+'</label>'+
                                                '<br/>'+
                                                '<select id="em_combo_org" onchange="javascript:sharemailbox.get_available_users(this.value);"></select>'+
                                                '<br/><br/>'+
                                                '<label>'+get_lang('Search user')+'<span style="margin-left:10px; color:red;" id="em_span_searching">&nbsp;</span><br></label>'+
                                                '<input id="em_input_searchUser" size="30" autocomplete="off"  onkeyup="javascript:sharemailbox.optionFinderTimeout(this, event)">'+
                                                '<div style="margin-top:17px;"><label>'+get_lang('Users')+':</label></div>'+
                                                '<select id="em_select_available_users" style="width:250px; height:150px" multiple></select></td>'+
                                            '</div>'+
                                            '<div style="width:20px; height: 300px; position:relative; float:left;">'+
                                                '<div style="margin-top:120px;margin-left:3px;">'+
                                                    '<img onClick="javascript:sharemailbox.add_user();" src="../phpgwapi/templates/azul/images/tabs-r0.gif" style="vertical-align:middle;cursor:pointer;">'+
                                                    '<br/><br/>'+
                                                    '<img onClick="javascript:sharemailbox.remove_user();" src="../phpgwapi/templates/azul/images/tabs-l0.gif" style="vertical-align:middle;cursor:pointer;">'+
                                                '</div>'+
                                            '</div>'+
                                            '<div style="width:348px; height:300px; position:relative; float:right;">'+
                                                '<div style="margin-top:90px;"><label>'+get_lang('Your mailbox is shared with')+' :</label></div>'+
                                                '<div style="position:absolute; float:left;">'+
                                                    '<select onchange=sharemailbox.getaclfromuser(this.value); id="em_select_sharefolders_users" size="13" style="width:245px;height:150px">'+options+'</select>'+
                                                '</div>'+
                                                '<div style="position:relative; float:right; width:98px;">'+
                                                        '<fieldset>'+
                                                            '<legend>'+get_lang('Permission')+'</legend>'+
                                                            '<div title="'+get_lang("hlp_msg_read_acl")+'" alt="'+get_lang("hlp_msg_read_acl")+'"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_readAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">'+get_lang('Read')+'</label><div/>'+
                                                            '<div title="'+get_lang("hlp_msg_delmov_acl")+'" alt="'+get_lang("hlp_msg_delmov_acl")+'"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_deleteAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">'+get_lang('Exclusion')+'</label></div>'+
                                                            '<div title="'+get_lang("hlp_msg_addcreate_acl")+'" alt="'+get_lang("hlp_msg_addcreate_acl")+'"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_writeAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">'+get_lang('Write')+'</label></div>'+
                                                            '<div title="'+get_lang("hlp_msg_sendlike_acl")+'" alt="'+get_lang("hlp_msg_sendlike_acl")+'"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_sendAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">'+get_lang('Send')+'</label></div>'+
                                                            '<div title="'+get_lang("hlp_msg_savelike_acl")+'" alt="'+get_lang("hlp_msg_savelike_acl")+'"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_saveAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">'+get_lang('Save')+'</label></div>'+
                                                        '</fieldset>'+
                                                '</div>'+
                                            '</div>'+
                                        '</fieldset>'+
                                     '</div>';
		}

		var handler_organizations = function(data)
        {
			if( data && data != false )
			{				
				var user_organization = Element('user_organization').value;

				for(i = 0; i < data.length; i++)
	            {
					Element('em_combo_org').options[i] = new Option(data[i].ou,data[i].dn);
				
					if(data[i].ou.indexOf("dc=") != -1 || user_organization.toUpperCase() == data[i].ou.toUpperCase())
					{
						Element('em_combo_org').options[i].selected = true;
						_this.get_available_users(data[i].dn);
					}
				}
			}
		}
		
		cExecute ("$this.ldap_functions.get_organizations&referral=false", handler_organizations);

		var butt = Element('dJSWin_wfolders_bok')
                
		if ( !butt )
                {
			butt = document.createElement('INPUT');
                        butt.style.marginLeft = "5px";
			butt.id = 'dJSWin_wfolders_bok';
			butt.type = 'button';
			butt.value = get_lang('Save');
			el.appendChild(butt);
		}
		butt.onclick = function ()
		{
			// Needed select all options from select
			var users_setacl = new Array();
			select_users = Element('em_select_sharefolders_users');
			for(var i=0; i<select_users.options.length; i++)
			{
				users_setacl[i] = select_users.options[i].value;
			}
			attributes = connector.serialize(users_setacl);
			
			var handler_save = function(data)
			{
				if (data)
				{
					alert(get_lang('Shared options saved with success'));
					sharemailbox.arrayWin[el.id].close();
				}
			}

			cExecute ("$this.imap_functions.setacl", handler_save, 'users='+attributes);

		}
		
		var space = document.createElement('SPAN');
		space.innerHTML = "&nbsp;&nbsp;";
		el.appendChild(space);
		
		var butt = document.createElement('BUTTON');
		var buttext = document.createTextNode(get_lang('Close'));
		butt.appendChild(buttext);
		butt.onclick = function () {sharemailbox.arrayWin[el.id].close();};
		el.appendChild(butt);
		
		_this.showWindow(el);
	}
	
	cShareMailbox.prototype.showWindow = function (div)
	{
		if(! div) {
			alert(get_lang('This list has no participants'));
			return;
		}
		
		if(! this.arrayWin[div.id])
		{
			div.style.height = "370px";
			div.style.width = "655px";
			var title = ":: "+get_lang("Mailbox Sharing")+" ::";
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
			
			this.arrayWin[div.id] = win;
			win.draw();
		}
		else {
			win = this.arrayWin[div.id];
		}
		Element('em_input_sendAcl').disabled = true;
		Element('em_input_saveAcl').disabled = true;
		win.open();
	}
	
    cShareMailbox.prototype.optionFinderTimeout = function(Obj, Event )
	{
		var minNumChar  = trim(sharedFolders_min_num_characters);
		minNumChar  = ( minNumChar === "" || parseInt(minNumChar) == 0 ) ? 1 : minNumChar;
		
		var oWait       = document.getElementById("em_span_searching");
        this.context    = document.getElementById('em_combo_org').value;

        if( parseInt(minNumChar) > 0  && sharedFolders_users_auto_search.toString() === "false" )
        {
			var key             = [8,27,37,38,39,40];
			var ev              = Event;
			var _inputSearch    = Obj;
			
			var cleanLabel = function(obj)
			{
				obj.innerHTML = "";
			}
			
			var getUsers = function( _input, obj )
			{
                            var context = sharemailbox.context;
                            var cn      = _input.value;
                            
                            sharemailbox.get_available_users2( context, cn );

                            cleanLabel(obj);
			}

			for( var i in key )
			{
				if( ev.keyCode == key[i])
                {    
					return false;
                }
			}

			if( _inputSearch.value.length < parseInt(minNumChar) )
			{
				oWait.innerHTML = " ( Digite mais " + ( parseInt(minNumChar) - _inputSearch.value.length ) + " )";
				setTimeout(function(){cleanLabel(oWait);}, 2000);
			}
			else
			{
				oWait.innerHTML = get_lang('Searching')+"...";
				
				if( this.finderTimeout )
					clearTimeout(this.finderTimeout);

				this.finderTimeout = setTimeout(function(){ getUsers( _inputSearch, oWait); }, 1000);
			}	
         }
         else
         {
            if( this.finderTimeout )
                clearTimeout(this.finderTimeout);

            oWait.innerHTML = get_lang('Searching')+"...";
            
            this.finderTimeout = setTimeout(function(){ sharemailbox.optionFinder(Obj.id); }, 1000);
         }
	}
	
	cShareMailbox.prototype.optionFinder = function(id)
	{
		var oWait = document.getElementById("em_span_searching");
		var oText = document.getElementById(id);
			
		//Limpa todo o select
		
		var select_available_users_tmp = document.getElementById('em_select_available_users')
		for(var i = 0;i < select_available_users_tmp.options.length; i++)
			select_available_users_tmp.options[i--] = null;

		var RegExp_name = new RegExp("\\b"+oText.value, "i");
		
		//Inclui usuário começando com a pesquisa
		if (typeof(select_available_users_clone)  != "undefined"){
			for(i = 0; i < select_available_users_clone.length; i++){
				if (RegExp_name.test(select_available_users_clone[i].text))
				{
					sel = select_available_users_tmp.options;
					option = new Option(select_available_users_clone[i].text,select_available_users_clone[i].value);				
					sel[sel.length] = option;
				}
			}
		}
		
                oWait.innerHTML = '';
	}
	
	cShareMailbox.prototype.add_user = function()
	{
		var select_available_users = document.getElementById('em_select_available_users');
		var select_users = document.getElementById('em_select_sharefolders_users');

		var count_available_users = select_available_users.length;
		var count_users = select_users.options.length;
		var new_options = '';
	
		for (i = 0 ; i < count_available_users ; i++)
		{
			if (select_available_users.options[i].selected)
			{
				if(document.all)
				{
					if ( (select_users.innerHTML.indexOf('value='+select_available_users.options[i].value)) == '-1' )
					{
						new_options +=  '<option value='
									+ select_available_users.options[i].value
									+ '>'
									+ select_available_users.options[i].text
									+ '</option>';
					}
				}
				else
				{
					if ( (select_users.innerHTML.indexOf('value="'+select_available_users.options[i].value+'"')) == '-1' )
					{
						new_options +=  '<option value='
									+ select_available_users.options[i].value
									+ '>'
									+ select_available_users.options[i].text
									+ '</option>';
					}
				}
			}
		}

		if (new_options != '')
		{
			select_users.innerHTML = '#' + new_options + select_users.innerHTML;
			select_users.outerHTML = select_users.outerHTML;
		}
	}

	cShareMailbox.prototype.remove_user = function()
	{
		select_users = document.getElementById('em_select_sharefolders_users');
	
		for(var i = 0;i < select_users.options.length; i++)
			if(select_users.options[i].selected)
				select_users.options[i--] = null;
				
		Element('em_input_readAcl').checked = false;
		Element('em_input_deleteAcl').checked = false;
		Element('em_input_writeAcl').checked = false;
		Element('em_input_sendAcl').checked = false;
		Element('em_input_saveAcl').checked = false;
	}
	
	
/* Build the Object */
	var sharemailbox;
	sharemailbox = new cShareMailbox();
