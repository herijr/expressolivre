function cShareMailbox(){

	this.arrayWin = new Array();
	this.el;
	this.alert = false;
	this.context = "";
	this.finderTimeout = '';
}

cShareMailbox.prototype.get_available_users = function (context) {
	if (sharedFolders_users_auto_search.toString() === "true") {
		this.get_available_users2(context);
	}
}

cShareMailbox.prototype.get_available_users2 = function(){
	var context = "";
	var cn = "";

	var handler_get_available_users = function (data) {
		select_available_users = document.getElementById('em_select_available_users');

		//Limpa o select
		for (var i = 0; i < select_available_users.options.length; i++) {
			select_available_users.options[i] = null;
			i--;
		}

		if ((data) && (data.length > 0)) {
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_users.innerHTML = '#' + data;
			select_available_users.outerHTML = select_available_users.outerHTML;

			select_available_users.disabled = false;
			select_available_users_clone = document.getElementById('em_select_available_users').cloneNode(true);
			document.getElementById('em_input_searchUser').value = '';
		}
	}

	if( arguments.length > 1 ) {
		context = arguments[0];
		cn = arguments[1];
		cExecute("$this.ldap_functions.get_available_users2&context=" + context + "&cn=" + cn, handler_get_available_users);
	} else {
		context = arguments[0];
		cExecute("$this.ldap_functions.get_available_users2&context=" + context, handler_get_available_users);
	}

}

cShareMailbox.prototype.setCheckBox = function( attribute, value ){
	$("input[type='checkbox'][id*='em_input_']").each(function(i) {
		if( attribute === 'disabled' ){ 
			if( i > 0 ){ $(this).prop( attribute , value ); }
		} else {
			$(this).prop( attribute , value ); 
		}
		if( i == 3 ){ $(this).prop( 'disabled' , true ); } 
		if( i == 4 ){ $(this).prop( 'disabled' , true ); }
	});
}

cShareMailbox.prototype.getaclfromuser = function(user){
	
	var getAclFromUser = function(data){

		var aclUser = ($.trim(data[user]) == "false" ? false : $.trim(data[user]));

		sharemailbox.setCheckBox( 'disabled' , true );
		
		sharemailbox.setCheckBox( 'checked' , false );

		if (aclUser) {

			// Acl read
			var _l = new RegExp("l");
			var _r = new RegExp("r");
			var _s = new RegExp("s");
			var readAcl = _l.test(aclUser) && _r.test(aclUser) && _s.test(aclUser);
			$("#em_input_readAcl").prop({ checked: readAcl });

			var _disabled = ( $("#em_input_readAcl").is(":checked") ? false : true );
			
			sharemailbox.setCheckBox( 'disabled' , _disabled );

			// Acl delete
			var _d = new RegExp("d");
			var deleteAcl = _d.test(aclUser);
			
			if( !deleteAcl ){
				var _x = new RegExp("x");
				var _t = new RegExp("t");
				var _e = new RegExp("e");

				var deleteAcl = _x.test(aclUser) && _t.test(aclUser) && _e.test(aclUser);
			}

			$("#em_input_deleteAcl").prop({ checked: deleteAcl });

			// Acl write
			var _w = new RegExp("w");
			var _i = new RegExp("i");

			var _k = new RegExp("k");
			var writeAcl = ( ( _w.test(aclUser) && _i.test(aclUser) ) || _k.test(aclUser) );
			$("#em_input_writeAcl").prop({ checked: writeAcl });

			// Acl send
			var _a = new RegExp("a");
			var sendAcl = _a.test(aclUser);
			$("#em_input_sendAcl").prop({ checked: sendAcl });

			// Acl save
			var _p = new RegExp("p");
			var saveAcl = _p.test(aclUser);
			$("#em_input_saveAcl").prop({ checked: saveAcl });

			if ($("#em_input_writeAcl").is(":checked")) {
				$("#em_input_sendAcl").prop('disabled', false);
			}

			if ($("#em_input_sendAcl").is(":checked")) {
				$("#em_input_sendAcl").prop('disabled', false);
				$("#em_input_saveAcl").prop('disabled', false);
			}
		}
	}

	cExecute("$this.imap_functions.getaclfromuser&user=" + user, getAclFromUser );
}

cShareMailbox.prototype.setaclfromuser = function () {
	
	if( $('#em_select_sharefolders_users').val() != null ){

		var user = $('#em_select_sharefolders_users').val();
	
		if( $("#em_input_readAcl").is(":checked") ){
			this.setCheckBox( 'disabled' , false );
		} else {
			this.setCheckBox( 'checked' , false );
			this.setCheckBox( 'disabled' , true );
		}

		// Set acl
		var acl = "";

		acl += $("#em_input_readAcl").is(":checked") ? 'lrs' : '';
		acl += $("#em_input_deleteAcl").is(":checked") ? 'xte' : '';
		acl += $("#em_input_writeAcl").is(":checked") ? 'kwi' : '';
		acl += $("#em_input_sendAcl").is(":checked") ? 'a' : '';

		if ($("#em_input_writeAcl").is(":checked")) {

			$("#em_input_sendAcl").prop('disabled', false);

			if ($("#em_input_writeAcl").is(":checked") && $("#em_input_sendAcl").is(":checked")) {
				$("#em_input_saveAcl").prop('disabled', false);
			} else {
				$("#em_input_saveAcl").prop({ 'disabled': true, 'checked': false });

				acl = acl.replace(/p/,'');
			}
		} else {
			$("#em_input_sendAcl").prop({ 'disabled': true, 'checked': false });
			$("#em_input_saveAcl").prop({ 'disabled': true, 'checked': false });
			
			acl = acl.replace(/p/,'');
			acl = acl.replace(/a/,'');
		}

		acl += $("#em_input_saveAcl").is(":checked") ? 'p' : '';

		var setAclFromUser = function(data){ return true; };

		cExecute("$this.imap_functions.setaclfromuser&user=" + user + "&acl=" + acl, setAclFromUser);

	} else {
		
		alert("Selecione antes um usuário!"); 
		
		return false;
	}
}

cShareMailbox.prototype.makeWindow = function (options) {
	_this = this;

	var el = $("<div>").css({
		'visibility': 'hidden',
		'position': 'absolute',
		'lef': '0px',
		'top': '0px',
		'width': '0px',
		'height': '0px',
	}).attr('id', 'dJSWin_sharefolders')[0];

	document.body.appendChild(el);

	if ($('#em_select_sharefolders_users').length > 0) {
		var select_users = Element('em_select_sharefolders_users');
		select_users.innerHTML = '#' + options;
		select_users.outerHTML = select_users.outerHTML;

	} else {

		el.innerHTML = '<div style="width:645px; height:340px; margin: 2px !important; ">' +
			'<fieldset style="height:300px;">' +
			'<div style="width:500px; height:15px; font-size:8pt; color:red;">' +
			get_lang('Note: This sharing will take action on all of your folders and messages.') +
			'</div>' +
			'<br clear="all"/>' +
			'<div style="width:250px; height: 300px; position:aboslute; float:left;">' +
			'<label>' + get_lang('Organization') + '</label>' +
			'<br/>' +
			'<select id="em_combo_org" onchange="javascript:sharemailbox.get_available_users(this.value);"></select>' +
			'<br/><br/>' +
			'<label>' + get_lang('Search user') + '<span style="margin-left:10px; color:red;" id="em_span_searching">&nbsp;</span><br></label>' +
			'<input id="em_input_searchUser" size="30" autocomplete="off"  onkeyup="javascript:sharemailbox.optionFinderTimeout(this, event)">' +
			'<div style="margin-top:17px;"><label>' + get_lang('Users') + ':</label></div>' +
			'<select id="em_select_available_users" style="width:230px; height:150px" multiple></select></td>' +
			'</div>' +
			'<div style="width:20px; height: 300px; position:relative; float:left;">' +
			'<div style="margin-top:120px;margin-left:-3px;">' +
			'<img onClick="javascript:sharemailbox.add_user();" src="../phpgwapi/templates/azul/images/tabs-r0.gif" style="vertical-align:middle;cursor:pointer;">' +
			'<br/><br/>' +
			'<img onClick="javascript:sharemailbox.remove_user();" src="../phpgwapi/templates/azul/images/tabs-l0.gif" style="vertical-align:middle;cursor:pointer;">' +
			'</div>' +
			'</div>' +
			'<div style="width:348px; height:300px; position:relative; float:right;">' +
			'<div style="margin-top:90px;"><label>' + get_lang('Your mailbox is shared with') + ' :</label></div>' +
			'<div style="position:absolute; float:left;">' +
			'<select onchange=sharemailbox.getaclfromuser(this.value); id="em_select_sharefolders_users" size="13" style="width:230px;height:150px">' + options + '</select>' +
			'</div>' +
			'<div style="position:relative; float:right; width:auto;">' +
			'<fieldset>' +
			'<legend>' + get_lang('Permission') + '</legend>' +
			'<div title="' + get_lang("hlp_msg_read_acl") + '" alt="' + get_lang("hlp_msg_read_acl") + '"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_readAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">' + get_lang('Read') + '</label><div/>' +
			'<div title="' + get_lang("hlp_msg_delmov_acl") + '" alt="' + get_lang("hlp_msg_delmov_acl") + '"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_deleteAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">' + get_lang('Exclusion') + '</label></div>' +
			'<div title="' + get_lang("hlp_msg_addcreate_acl") + '" alt="' + get_lang("hlp_msg_addcreate_acl") + '"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_writeAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">' + get_lang('Write') + '</label></div>' +
			'<div title="' + get_lang("hlp_msg_sendlike_acl") + '" alt="' + get_lang("hlp_msg_sendlike_acl") + '"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_sendAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">' + get_lang('Send') + '</label></div>' +
			'<div title="' + get_lang("hlp_msg_savelike_acl") + '" alt="' + get_lang("hlp_msg_savelike_acl") + '"><label style="padding-left:10px ;text-indent:-15px;"><input style="height:13px; padding:0; margin:0; vertical-align: bottom; position: relative;" id="em_input_saveAcl" onClick="return sharemailbox.setaclfromuser();" type="checkbox">' + get_lang('Save') + '</label></div>' +
			'</fieldset>' +
			'</div>' +
			'</div>' +
			'</fieldset>' +
			'</div>';
	}

	var handler_organizations = function (data) {
		if (typeof data === 'object') {
			var user_organization = Element('user_organization').value;

			for (i = 0; i < data.length; i++) {
				Element('em_combo_org').options[i] = new Option(data[i].ou, data[i].dn);

				if (data[i].ou.indexOf("dc=") != -1 || user_organization.toUpperCase() == data[i].ou.toUpperCase()) {
					Element('em_combo_org').options[i].selected = true;
					_this.get_available_users(data[i].dn);
				}
			}
		}
	}

	cExecute("$this.ldap_functions.get_organizations&referral=false", handler_organizations);

	var butt = Element('dJSWin_wfolders_bok')
	var space = document.createElement('SPAN');
	space.innerHTML = "&nbsp;&nbsp;";
	el.appendChild(space);

	var butt = document.createElement('BUTTON');
	var buttext = document.createTextNode(get_lang('Close'));
	butt.appendChild(buttext);
	butt.onclick = function(){ 
		sharemailbox.setCheckBox( 'disabled', true );
		sharemailbox.setCheckBox( 'checked', false );
		sharemailbox.arrayWin[el.id].close(); 
	};

	el.appendChild(butt);

	_this.showWindow(el);
}

cShareMailbox.prototype.showWindow = function(div){
	
	if (!div) {
		alert(get_lang('This list has no participants'));
		return;
	}

	if (!this.arrayWin[div.id]) {
		div.style.height = "370px";
		div.style.width = "655px";
		var title = ":: " + get_lang("Mailbox Sharing") + " ::";
		var wHeight = div.offsetHeight + "px";
		var wWidth = div.offsetWidth + "px";
		div.style.width = div.offsetWidth - 5;

		win = new dJSWin({
			id: 'win_' + div.id,
			content_id: div.id,
			width: wWidth,
			height: wHeight,
			title_color: '#3978d6',
			bg_color: '#eee',
			title: title,
			title_text_color: 'white',
			button_x_img: '../phpgwapi/images/winclose.gif',
			border: true
		});

		this.arrayWin[div.id] = win;
		win.draw();
	} else {

		win = this.arrayWin[div.id];
	}

	win.open();

	this.setCheckBox( 'checked', false );

	this.setCheckBox( 'disabled', true );
}

cShareMailbox.prototype.optionFinderTimeout = function (Obj, Event) {
	var minNumChar = trim(sharedFolders_min_num_characters);
	minNumChar = (minNumChar === "" || parseInt(minNumChar) == 0) ? 1 : minNumChar;

	var oWait = document.getElementById("em_span_searching");
	this.context = document.getElementById('em_combo_org').value;

	if (parseInt(minNumChar) > 0 && sharedFolders_users_auto_search.toString() === "false") {
		var key = [8, 27, 37, 38, 39, 40];
		var ev = Event;
		var _inputSearch = Obj;

		var cleanLabel = function (obj) {
			obj.innerHTML = "";
		}

		var getUsers = function (_input, obj) {
			var context = sharemailbox.context;
			var cn = _input.value;

			sharemailbox.get_available_users2(context, cn);

			cleanLabel(obj);
		}

		for (var i in key) {
			if (ev.keyCode == key[i]) {
				return false;
			}
		}

		if (_inputSearch.value.length < parseInt(minNumChar)) {
			oWait.innerHTML = " ( Digite mais " + (parseInt(minNumChar) - _inputSearch.value.length) + " )";
			setTimeout(function () { cleanLabel(oWait); }, 2000);
		} else {
			oWait.innerHTML = get_lang('Searching') + "...";

			if (this.finderTimeout)
				clearTimeout(this.finderTimeout);

			this.finderTimeout = setTimeout(function () { getUsers(_inputSearch, oWait); }, 1000);
		}
	} else {
		if (this.finderTimeout)
			clearTimeout(this.finderTimeout);

		oWait.innerHTML = get_lang('Searching') + "...";

		this.finderTimeout = setTimeout(function () { sharemailbox.optionFinder(Obj.id); }, 1000);
	}
}

cShareMailbox.prototype.optionFinder = function (id) {
	var oWait = document.getElementById("em_span_searching");
	var oText = document.getElementById(id);

	//Limpa todo o select

	var select_available_users_tmp = document.getElementById('em_select_available_users')
	for (var i = 0; i < select_available_users_tmp.options.length; i++)
		select_available_users_tmp.options[i--] = null;

	var RegExp_name = new RegExp("\\b" + oText.value, "i");

	//Inclui usuário começando com a pesquisa
	if (typeof (select_available_users_clone) != "undefined") {
		for (i = 0; i < select_available_users_clone.length; i++) {
			if (RegExp_name.test(select_available_users_clone[i].text)) {
				sel = select_available_users_tmp.options;
				option = new Option(select_available_users_clone[i].text, select_available_users_clone[i].value);
				sel[sel.length] = option;
			}
		}
	}

	oWait.innerHTML = '';
}

cShareMailbox.prototype.add_user = function () {
	var select_available_users = document.getElementById('em_select_available_users');
	var select_users = document.getElementById('em_select_sharefolders_users');

	var count_available_users = select_available_users.length;
	var count_users = select_users.options.length;
	var new_options = '';

	for (i = 0; i < count_available_users; i++) {
		if (select_available_users.options[i].selected) {
			if (document.all) {
				if ((select_users.innerHTML.indexOf('value=' + select_available_users.options[i].value)) == '-1') {
					new_options += '<option value='
						+ select_available_users.options[i].value
						+ '>'
						+ select_available_users.options[i].text
						+ '</option>';
				}
			}
			else {
				if ((select_users.innerHTML.indexOf('value="' + select_available_users.options[i].value + '"')) == '-1') {
					new_options += '<option value='
						+ select_available_users.options[i].value
						+ '>'
						+ select_available_users.options[i].text
						+ '</option>';
				}
			}
		}
	}

	if (new_options != '') {
		select_users.innerHTML = '#' + new_options + select_users.innerHTML;
		select_users.outerHTML = select_users.outerHTML;
	}
}

cShareMailbox.prototype.remove_user = function () {
	
	var user = $("#em_select_sharefolders_users option:selected").val();
	
	$("#em_select_sharefolders_users option:selected").remove();

	this.setCheckBox( 'checked' , false );

	this.setCheckBox( 'disabled' , true );

	var removeUser = function( data ){ return true; };

	cExecute("$this.imap_functions.setaclfromuser&user=" + user + "&acl=none", removeUser );
}


/* Build the Object */
var sharemailbox;
sharemailbox = new cShareMailbox();
