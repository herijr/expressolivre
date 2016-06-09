//Funcoes
function remUser(){
	select = self.document.getElementById('user_list');
	for(var i = 0;i < select.options.length; i++)
		if(select.options[i].selected){
			ids = getIds(select.options[i].value);
			for(j = 0; j < ids.length; j++)
				document.getElementById(ids[j]).disabled = true;
			select.options[i--] = null;
		}
	
	if(select.options.length)
		select.options[0].selected = true;
	
	execAction('LOAD');
}

function openListUsers(newWidth,newHeight){
	newScreenX  = screen.width - newWidth;
	newScreenY  = 0;
	window.open('preferences/templates/celepar/listUsers.php',"","width="+newWidth+",height="+newHeight+",screenX="+newScreenX+",left="+newScreenX+",screenY="+newScreenY+",top="+newScreenY+",toolbar=no,scrollbars=yes,resizable=no");
	
}

function execAction(action){
	
	if(!window.opener)
		doc = window.document;
	else
		doc = window.opener.document;
	
	select = doc.getElementById('user_list');
	checkAttr = doc.formAcl.checkAttr;
	for(i = 0; i < select.length; i++) {
		if(select.options[i].selected){
			ids = getIds(select.options[i].value);
			
			for(j = 0; j < ids.length; j++){
				
				if(action == 'SAVE') {
					doc.getElementById(ids[j]).disabled = !checkAttr[j].checked;
				}
				if(action == 'LOAD') {
					
					checkAttr[j].checked = !doc.getElementById(ids[j]).disabled;
				}
			}
		}
	}
	
	if(!select.length)
		for(j = 0; j < checkAttr.length; j++)
			checkAttr[j].disabled = true;
	
	else
		for(j = 0; j < checkAttr.length; j++)
			checkAttr[j].disabled = false;
	
}

function getIds(value){
	
	ids = new Array();
	ids[0] = value + '_1]' ;
	ids[1] = value + '_2]' ;
	ids[2] = value + '_4]' ;
	ids[3] = value + '_8]' ;
	ids[4] = value + '_16]';
	
	return ids;
}

function optionFinder(oText) {
		
	for(var i = 0;i < select.options.length; i++)
		select.options[i--] = null;
	
	for(i = 0; i < users.length; i++)
		
		if(users[i].text.substring(0 ,oText.value.length).toUpperCase() == oText.value.toUpperCase() ||
			(users[i].text.substring(0 ,3) == '(G)' &&
			users[i].text.substring(4 ,4+oText.value.length).toUpperCase() == oText.value.toUpperCase())) {
			sel = select.options;
			option = new Option(users[i].text,users[i].value);
			option.onclick = users[i].onclick;
			sel[sel.length] = option;
		
		}
}

function adicionaLista() {
	var select = window.document.getElementById('user_list_in');
	var selectOpener = window.opener.document.getElementById('user_list');
	for (i = 0 ; i < select.length ; i++) {
		
		if (select.options[i].selected) {
			isSelected = false;
			
			for(var j = 0;j < selectOpener.options.length; j++) {
				if(selectOpener.options[j].value == select.options[i].value){
					isSelected = true;
					break;
				}
			}
			
			if(!isSelected){
				
				option = window.opener.document.createElement('option');
				option.value =select.options[i].value;
				option.text = select.options[i].text;
				selectOpener.options[selectOpener.options.length] = option;	
				ids = getIds(select.options[i].value);
				for(k = 0; k < ids.length; k++) {
					el = window.opener.document.createElement('input');
					el.type='hidden';
					el.value ='Y';
					el.name = ids[k];
					el.disabled = true;
					el.id = ids[k];
					window.opener.document.getElementById("tdHiddens").appendChild(el);
				}
			}
			
		}
	}
	selectOpener.options[selectOpener.options.length-1].selected = true;
	execAction('LOAD');
	window.close();
}

function FormatTelephoneNumber(event, campo)
{
	separador1 = '(';
	separador2 = ')';
	separador3 = '-';
	
	vr = campo.value;
	tam = vr.length;
	
	if ((tam == 1) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = '';
	
	if ((tam == 3) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = vr.substr( 0, tam - 1 );
	
	if (( tam == 1 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
		campo.value = separador1 + vr;
	
	if (( tam == 3 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
		campo.value = vr + separador2;
		
	if (( tam == 8 ) && (( event.keyCode != 8 ) && ( event.keyCode != 46 )))
		campo.value = vr + separador3;
	
	if ((( tam == 9 ) || ( tam == 8 )) && (( event.keyCode == 8 ) || ( event.keyCode == 46 )))
		campo.value = vr.substr( 0, tam - 1 );
}

/*Função que padroniza DATA*/
function formatDate(obj){
	obj.value = obj.value.replace(/\D/g, "");
	obj.value = obj.value.replace(/(\d{2})(\d)/, "$1/$2");
	obj.value = obj.value.replace(/(\d{2})(\d{2})/, "$1/$2");
	obj.value = obj.value.replace(/(\d{2})(\d{2})(\d)/, "$1/$2/$3");
}

$(document).ready(function() {
	jQuery.fn.visible = function(property,value) {
		if (value == undefined){
			if (property == 'display') value = ($(this).css(property)!='none');
			if (property == 'visibility') value = ($(this).css(property)!='hidden');
		} else {
			if (property == 'display') $(this).css(property,value? '' : 'none');
			if (property == 'visibility') $(this).css(property,value? 'visible' : 'hidden');
		}
		return value;
	};
	mobileData.init();
});

var mobileData = new function() {
	
	var _prevMobile		= undefined;
	
	// Definitions
	this.isValid		= function() { return ( mobileData.lastChange.length >= 10); };
	this.isEqualSend	= function() { return ( mobileData.lastChange == mobileData.sendNumber ); };
	this.inChecked		= function() { return ( jQuery.inArray(mobileData.lastChange, mobileData.listChecked) !== -1); };
	this.isFiveDigits	= function() { return ( $("input[name=mobile_code]").val().replace(/[^0-9]/g,'').length == 5); };
	this.hasChange		= function() { return ( $("input[name=mobile]").val().replace(/[^0-9]/g,'') !== _prevMobile ); };
	
	// Init
	this.init = function() {
		_prevMobile = $("input[name=mobile]").val().replace(/[^0-9]/g,'');
		if ( $("input[name=sms_enabled]").val() == "true" ) {
			mobileData.lastChange = '?';
			mobileData.sendNumber = $("input[name=sms_send_number]").val();
			mobileData.listChecked = $("input[name=sms_list_checked]").val().split(",");
			$(".sms_opts").show();
			$("input[name=send_code]").click(mobileData.send);
			$("input[name=send_new_code]").click(mobileData.send);
			$("input[name=change]").click(mobileData.submit);
			$("input[name=mobile]").keyup(mobileData.changed).trigger('keyup');
			$("input[name=mobile_code]").keyup(mobileData.refresh);
			if ( $("input[name=mobile]").val().length > 0 && !mobileData.inChecked() )
				alert( $("input[name=mobile_msg]").val() );
		}
	};
	
	this.refresh = function() {
		
		var valid = mobileData.isValid();
		var checked = mobileData.inChecked();
		var sended = mobileData.isEqualSend();
		var fivedig = mobileData.isFiveDigits();
		var bt_disabled = mobileData.hasChange() && valid && (!checked) && (!fivedig);
		
		$("#use_chk_code").visible('display', (valid && (!checked)));
		$("#right_arrow").visible('display', bt_disabled);
		$(".tr_send_code").visible('display', (valid && (!checked) && (!sended)));
		$(".tr_recv_code").visible('display', (valid && (!checked) && sended));

		if(fivedig) $("input[name=mobile_code]").removeClass('cl_red');
		else $("input[name=mobile_code]").addClass('cl_red');
		
		if(bt_disabled) $("input[name=change]").prop('disabled',true).addClass('bt_disabled');
		else $("input[name=change]").prop('disabled',false).removeClass('bt_disabled');
	};
	
	this.changed = function() {
		
		var value = $("input[name=mobile]").val().replace(/[^0-9]/g,'');
		if (mobileData.lastChange == value) return;
		mobileData.lastChange = value;

		$("input[name=mobile_code]").val('');
		mobileData.refresh();
	};
	
	this.send = function(e) {
		if ((!mobileData.isEqualSend()) || confirm($('#confirm_send_new_code').html().replace(/\\n/gm,"\n"))) {
			
			var cur = $(e.currentTarget).css('cursor');
			$(e.currentTarget).prop('disabled',true);
			$(e.currentTarget).css('cursor','wait');
			
			ExpressoAjax.options({
				resource: 'phpgwapi.sms.SendCheckCode',
				params: {
					"phoneNumber": $("input[name=mobile]").val(),
				}
			}).done(function() {
				$("input[name=mobile_code]").val('');
				mobileData.sendNumber = mobileData.lastChange;
				mobileData.refresh();
			}).always(function() {
				$(e.currentTarget).css('cursor',cur);
				$(e.currentTarget).prop('disabled',false);
			}).execute();
		}
		return false;
	};
	
	this.submit = function(e) {
		if ( !( mobileData.hasChange() || mobileData.isFiveDigits() ) ) return mobileData.submitForm();
		var cur = $(e.currentTarget).css('cursor');
		$(e.currentTarget).prop('disabled',true);
		$(e.currentTarget).css('cursor','wait');
		
		ExpressoAjax.options({
			resource: 'phpgwapi.sms.SubmitPersonalForm',
			params: {
				"phoneNumber": $("input[name=mobile]").val(),
				"checkCode": $("input[name=mobile_code]").val(),
				"SMSAuth": $("select[name=mobile_autz]").val(),
			}
		}).done(function() {
			if (($("select[name=mobile_autz]").val()=='1') || confirm($('#confirm_mobile_autz').html().replace(/\\n/gm,"\n")))
				mobileData.submitForm();
		}).fail(function( json ) {
			if(json && json.error && json.error.message) alert(json.error.message);
			if(json && json.error && json.error.code && json.error.code == 2125 ){
				mobileData.sendNumber = '?';
				mobileData.refresh();
			}
		}).always(function() {
			$(e.currentTarget).css('cursor',cur);
			$(e.currentTarget).prop('disabled',false);
		}).execute();
		return false;
	};
	
	this.submitForm = function( e ) {
		$("input[name=change]")
		.parents('form')
		.first()
		.append('<input name="change" type="hidden" value="change"/>')
		.submit();
		return true;
	};
}