countFiles = 1;
function validate_fields(type)
{
	var attrs_array = new Array();
	
	if (type == 'create_user')
	{
		//UID
		document.forms[0].uid.value = document.forms[0].uid.value.toLowerCase();
		
		if (document.forms[0].uid.value == ''){
			alert(get_lang('LOGIN field is empty') + '.');
			return;
		}
		else if (document.forms[0].uid.value.length < document.forms[0].minimumSizeLogin.value){
			alert(get_lang('LOGIN field must be bigger than') + ' ' + document.forms[0].minimumSizeLogin.value + ' ' + get_lang('characters') + '.');
			return;
		}
		
		// Verifica se o delimitador do Cyrus permite ponto (dot.) nas mailboxes;
		var reUid = new RegExp('^[\\w\\-'+(Profile.getDelimiter()=='.'?'':'\\.')+']+$');
		if(!reUid.test(document.forms[0].uid.value)){
			alert(get_lang('LOGIN field contains characters not allowed') + '.');
			return;
		}
	
		//PASSWORD's
		if (document.forms[0].password1.value == ''){
			alert(get_lang('Password field is empty') + '.');
			return;
		}
		if (document.forms[0].password2.value == ''){
			alert(get_lang('repassword field is empty') + '.');
			return;
		}
	}

	if (document.forms[0].password1.value != document.forms[0].password2.value){
		alert(get_lang('password and repassword are different') + '.');
		return;
	}
	
	// Corporative Information
	if (document.forms[0].corporative_information_employeenumber.value != "")
	{
		var re_employeenumber = /^([0-9])+$/;
		
		if(!re_employeenumber.test(document.forms[0].corporative_information_employeenumber.value))
		{
			alert(get_lang('EmployeeNumber contains characters not allowed') + '. ' + get_lang('Only numbers are allowed') + '.');
			document.forms[0].corporative_information_employeenumber.focus();
			return;
		}
	}

	//MAIL
	document.forms[0].mail.value = document.forms[0].mail.value.toLowerCase();
	if (document.forms[0].mail.value == ''){
		alert(get_lang('Email field is empty') + '.');
		return;
	}
	var RegExp_mail = /^([a-zA-Z0-9_\-])+(\.[a-zA-Z0-9_\-]+)*\@([a-zA-Z0-9_\-])+(\.[a-zA-Z0-9_\-]+)*$/;
	if(!RegExp_mail.test(document.forms[0].mail.value)){
		alert(get_lang('Email field is not valid') + '.');
		return false;
	}
	
	//FIRSTNAME
	var reGivenname = /^[a-zA-Z0-9]([a-zA-Z0-9 \-\.]*[a-zA-Z0-9\-\.])?$/;
	if(!reGivenname.test(document.forms[0].givenname.value)){
		alert(get_lang('First name field contains characters not allowed') + '.');
		return false;
	}
	else if (document.forms[0].givenname.value == ''){
		alert(get_lang('First name field is empty') + '.');
		return;
	}
	
	//LASTNAME
	var reSn1 = /[a-zA-Z0-9]+/;
	var reSn2 = /^[a-zA-Z0-9\-]([a-zA-Z0-9 \-\.]*[a-zA-Z0-9\-\.])?$/;
	if ( document.forms[0].sn.value == '' ) {
		alert( get_lang( 'The surname field is empty' )+'.' );
		return false;
	} else if ( !reSn1.test( document.forms[0].sn.value ) ) {
		alert( get_lang( 'The surname field must contain letters or numbers' )+'.' );
		return false;
	} else if ( !reSn2.test( document.forms[0].sn.value ) ) {
		alert( get_lang( 'The surname field contains characters not allowed' )+'.' );
		return false;
	}
	
	//TELEPHONENUMBER
	if (document.forms[0].telephonenumber.value != '')
	{
		reg_tel = /\(\d{2}\)\d{4}-\d{4}$/;
		if (!reg_tel.exec(document.forms[0].telephonenumber.value))
		{
			alert(get_lang('Phone field is incorrect') + '.');
			return;
		}
	}
	
	// ALTERNATE AND FORWARD MAIL
	var count_mail = { 'mailalternateaddress': 0, 'mailforwardingaddress': 0 };
	var arr_mail = [ $('input[name=mail]').val() ];
	for ( var mail_type in count_mail ) {
		var check_mail = true;
		$('input[name^='+mail_type+']').each(function(i,e){
			if ( $(e).val() != '' ) {
				if ( RegExp_mail.test( $(e).val() ) && arr_mail.indexOf( $(e).val() ) == -1 ) {
					if ( attrs_array[mail_type] == undefined ) attrs_array[mail_type] = new Array();
					attrs_array[mail_type][count_mail[mail_type]] = $(e).val();
					arr_mail.push( $(e).val() );
					count_mail[mail_type]++;
				} else {
					alert( get_lang( ( (mail_type == 'mailalternateaddress' )? 'Alternate email' : 'Forwarding email' ) )+' '+get_lang('is not valid or duplicate' )+': '+$(e).val() );
					check_mail = false;
				}
			}
		});
		if ( ( !check_mail ) ) return;
	}
	
	//FORWAR ONLY
	if ( document.forms[0].deliverymode.checked && count_mail['mailforwardingaddress'] == 0 ) {
		alert(get_lang('Forward email is empty') + '.');
		return;
	}
	
	// Email Quota
	if( Profile.hasPerfil() && document.forms[0].mailquota.value == '' )
	{
		alert( get_lang('User without email quota') + '.');
		return;
	}
	
	//GROUPS
	if (document.getElementById('ea_select_user_groups').length < 1){
		alert(get_lang('User is not in any group') + '.');
		return;
	}

	//SAMBA
	if (document.getElementById('tabcontent6').style.display != 'none'){
		//if ((document.forms[0].sambalogonscript.value == '') && (!document.forms[0].sambalogonscript.disabled))
		if ((document.forms[0].sambalogonscript.value == '') && (document.forms[0].use_attrs_samba.checked))
		{
			alert(get_lang('Logon script is empty') + '.');
			return;
		}
		//if ((document.forms[0].sambahomedirectory.value == '') && (!document.forms[0].sambahomedirectory.disabled))
		if ((document.forms[0].sambahomedirectory.value == '') && (document.forms[0].use_attrs_samba.checked))
		{
			alert(get_lang('Users home directory is empty') + '.');
			return;
		}
	}
	
	if (Profile.hasDeleteAction() && (!confirm(get_lang('This action completely erase the mailbox')+'.\n'+get_lang('Do you want to continue anyway')+'?')) )
		return false;

	// Uid, Mail and CPF exist?
	attrs_array['type'] = type;
	attrs_array['uid'] = document.forms[0].uid.value;
	attrs_array['mail'] = document.forms[0].mail.value;
	attrs_array['cpf'] = document.forms[0].corporative_information_cpf.value;
	
	var attributes = connector.serialize(attrs_array);
	var handler_validate_fields = function(data)
	{
		if (!data.status)
		{
			alert(data.msg);
		}
		else
		{
			if ( (data.question) && (!confirm(data.question)) )
			{
				return false;
			}

			if (type == 'create_user')
			{
				document.getElementById('passwd_expired').disabled = false;
				cExecuteForm ("$this.user.create", document.forms[0], handler_create);
			}
			else
			{
				//Turn enabled all checkboxes and inputs
				document.getElementById('changepassword').disabled = false;
				document.getElementById('passwd_expired').disabled = false;
				document.getElementById('phpgwaccountstatus').disabled = false;
				document.getElementById('phpgwaccountvisible').disabled = false;
				document.getElementById('telephonenumber').disabled = false;
				document.getElementById('mailforwardingaddress').disabled = false;
				document.getElementById('mailalternateaddress').disabled = false;
				document.getElementById('accountstatus').disabled = false;
				document.getElementById('deliverymode').disabled = false;
				document.getElementById('use_attrs_samba').disabled = false;
				
				table_apps = document.getElementById('ea_table_apps');
				var inputs = table_apps.getElementsByTagName("input");
				for (var i = 0; i < inputs.length; i++)
				{
					inputs[i].disabled = false;
				}
				
				//Necessario para lista de email, quando a edição ain
				
				$("input[type=text][name=mail]").attr("disabled",false);

				cExecuteForm ("$this.user.save", document.forms[0], handler_save);
			}
		}
	}
	
	// Needed select all options from select
	
	// Mail lists
	select_user_maillists = document.getElementById('ea_select_user_maillists');

	for(var i=0; i<select_user_maillists.options.length; i++)
		select_user_maillists.options[i].selected = true;

	// Groups lists
	select_user_groups = document.getElementById('ea_select_user_groups');
	
	for(var i=0; i<select_user_groups.options.length; i++)
		select_user_groups.options[i].selected = true;

	if( typeof ExpressoLivre != 'undefined' )
	{
		if ( ExpressoLivre.ExpressoAdmin && ExpressoLivre.ExpressoAdmin.radius )
		{
			ExpressoLivre.ExpressoAdmin.radius.end( );
		}
	}

	cExecute ('$this.ldap_functions.validate_fields&attributes='+attributes, handler_validate_fields);
}

// HANDLER CREATE
// É necessário 2 funcões de retorno por causa do cExecuteForm.
function handler_create(data)
{
	return_handler_create(data);
}
function return_handler_create(data)
{
	if (!data.status)
		alert(data.msg);
	else
		alert(get_lang('User successful created') + '.');

	location.href="./index.php?menuaction=expressoAdmin1_2.uiaccounts.list_users";
	return;
}


// HANDLER SAVE
// É necessário 2 funcões de retorno por causa do cExecuteForm.
function handler_save(data)
{
	return_handler_save(data);
}
function return_handler_save(data)
{
	if (!data.status){
		alert(data.msg);
	}
	else{
		alert(get_lang('User successful saved') + '.');
	}
	location.href="./index.php?menuaction=expressoAdmin1_2.uiaccounts.list_users";
	return;
}

function get_available_groups(context)
{
	var handler_get_available_groups = function(data)
	{
		select_available_groups = document.getElementById('ea_select_available_groups');

		if ((data) && (data.length > 0))
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_groups.innerHTML = '#' + data;
			select_available_groups.outerHTML = select_available_groups.outerHTML;
			select_available_groups_clone = select_available_groups.cloneNode(true);
			document.getElementById('ea_input_searchGroup').value = '';
		}
		else
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_groups.innerHTML = '#';
			select_available_groups.outerHTML = select_available_groups.outerHTML;
		}
	}	
		
	cExecute ('$this.ldap_functions.get_available_groups&context='+context, handler_get_available_groups);
}
	
function add_user2group()
{
	select_available_groups = document.getElementById('ea_select_available_groups');
	select_user_groups = document.getElementById('ea_select_user_groups');
	combo_primary_user_group = document.getElementById('ea_combo_primary_user_group');

	for (i = 0 ; i < select_available_groups.length ; i++)
	{
		if (select_available_groups.options[i].selected)
		{
			isSelected = false;
			for(var j = 0;j < select_user_groups.options.length; j++)
			{
				if(select_user_groups.options[j].value == select_available_groups.options[i].value)
				{
					isSelected = true;						
					break;	
				}
			}

			if(!isSelected)
			{
				new_option1 = document.createElement('option');
				new_option1.value =select_available_groups.options[i].value;
				new_option1.text = select_available_groups.options[i].text;
				new_option1.selected = true;
				select_user_groups.options[select_user_groups.options.length] = new_option1;
				
				new_option2 = document.createElement('option');
				new_option2.value =select_available_groups.options[i].value;
				new_option2.text = select_available_groups.options[i].text;
				combo_primary_user_group.options[combo_primary_user_group.options.length] = new_option2;
			}
		}
	}
		
	for (j =0; j < select_available_groups.options.length; j++)
		select_available_groups.options[j].selected = false;
} 	
	
function remove_user2group()
{
	select_user_groups = document.getElementById('ea_select_user_groups');
	combo_primary_user_group = document.getElementById('ea_combo_primary_user_group');
	
	var x;
	var j=0;
	var to_remove = new Array();
	
	for(var i = 0;i < select_user_groups.options.length; i++)
	{
		if(select_user_groups.options[i].selected)
		{
			to_remove[j] = select_user_groups.options[i].value;
			j++;
			select_user_groups.options[i--] = null;
		}
	}
	
	for (x in to_remove)
	{
		for(var i=0; i<combo_primary_user_group.options.length; i++)
		{
			if (combo_primary_user_group.options[i].value == to_remove[x])
			{
				combo_primary_user_group.options[i] = null;
			}	
		}
	}
}
	
function get_available_maillists(context)
{
	var handler_get_available_maillists = function(data)
	{
		select_available_maillists = document.getElementById('ea_select_available_maillists');

		if ((data) && (data.length > 0))
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_maillists.innerHTML = '#' + data;
			select_available_maillists.outerHTML = select_available_maillists.outerHTML;
			select_available_maillists_clone = select_available_maillists.cloneNode(true);
			document.getElementById('ea_input_searchMailList').value = '';
		}
		else
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_maillists.innerHTML = '#';
			select_available_maillists.outerHTML = select_available_maillists.outerHTML;
		}
	}
	recursive = document.getElementById('ea_checkbox_recursive_list_search').checked;
	cExecute ('$this.ldap_functions.get_available_maillists&context='+context+'&recursive='+recursive, handler_get_available_maillists);
}
	
function add_user2maillist()
{
	select_available_maillists = document.getElementById('ea_select_available_maillists');
	select_user_maillists = document.getElementById('ea_select_user_maillists');

	for (i = 0 ; i < select_available_maillists.length ; i++)
	{

		if (select_available_maillists.options[i].selected)
		{
			isSelected = false;
			for(var j = 0;j < select_user_maillists.options.length; j++)
			{
				if(select_user_maillists.options[j].value == select_available_maillists.options[i].value)
				{
					isSelected = true;						
					break;	
				}
			}

			if(!isSelected)
			{
				new_option = document.createElement('option');
				new_option.value =select_available_maillists.options[i].value;
				new_option.text = select_available_maillists.options[i].text;
				new_option.selected = true;
					
				select_user_maillists.options[select_user_maillists.options.length] = new_option;
			}
		}
	}
		
	for (j =0; j < select_available_maillists.options.length; j++)
		select_available_maillists.options[j].selected = false;
} 	
	
function remove_user2maillist()
{
	select_user_maillists = document.getElementById('ea_select_user_maillists');

	for(var i = 0;i < select_user_maillists.options.length; i++)
		if(select_user_maillists.options[i].selected)
			select_user_maillists.options[i--] = null;
}
	
function sinc_combos_org(context)
{
	combo_org_groups = document.getElementById('ea_combo_org_groups');
	combo_org_maillists = document.getElementById('ea_combo_org_maillists');

	for (i=0; i<combo_org_groups.length; i++)
	{
		if (combo_org_groups.options[i].value == context)
		{
			combo_org_groups.options[i].selected = true;
			combo_org_maillists.options[i].selected = true;
		}
	}
}
	
function use_samba_attrs(value)
{
	if (value)
	{
		if (document.forms[0].sambalogonscript.value == '')
		{
			if (document.forms[0].defaultLogonScript.value == '')
			{
				document.forms[0].sambalogonscript.value = document.forms[0].uid.value + '.bat';
			}
			else
			{
				document.forms[0].sambalogonscript.value = document.forms[0].defaultLogonScript.value;
			}
		}
		if (document.forms[0].sambahomedirectory.value == '')
		{
			document.forms[0].sambahomedirectory.value = '/home/'+document.forms[0].uid.value+'/';
		}
	}
	
	if (!document.forms[0].use_attrs_samba.disabled)
	{
		document.forms[0].sambaacctflags.disabled = !value;
		document.forms[0].sambadomain.disabled = !value;
		document.forms[0].sambalogonscript.disabled = !value;
		//document.forms[0].sambahomedirectory.disabled = !value;
	}
}
	
function set_user_default_password()
{
	var handler_set_user_default_password = function(data)
	{
		if (!data.status)
			alert(data.msg);
		else
			alert(get_lang('Default password successful saved') + '.');
		return;
	}
	cExecute ('$this.user.set_user_default_password&uid='+document.forms[0].uid.value, handler_set_user_default_password);	
}

function return_user_password()
{
	var handler_return_user_password = function(data)
	{
		if (!data.status)
			alert(data.msg);
		else
			alert(get_lang('Users password successful returned') + '.');
		return;
	}
	cExecute ('$this.user.return_user_password&uid='+document.forms[0].uid.value, handler_return_user_password);
}

function delete_user(uid, uidnumber)
{
	if (confirm(get_lang("Do you really want delete the user") + " " + uid + "?"))
	{
		var handler_delete_user = function(data)
		{
			if (!data.status)
				alert(data.msg);
			else
				alert(get_lang('User successful deleted') + '.');
			
			location.href="./index.php?menuaction=expressoAdmin1_2.uiaccounts.list_users";
			return;
		}
		cExecute ('$this.user.delete&uidnumber='+uidnumber+'&uid='+uid, handler_delete_user);
	}
}

function rename_user(uid, uidnumber)
{
	var handler_get_delimiter = function(delimiter)
	{
		var reUid = new RegExp('^([a-zA-Z0-9_\\-'+(delimiter == '.'?'':'\\.')+'])+$');
		
		new_uid = prompt(get_lang('Rename users login from') + ': ' + uid + " " + get_lang("to") + ': ', uid);
	
		if(!reUid.test(new_uid)){
			alert(get_lang('LOGIN field contains characters not allowed') + '.');
			document.forms[0].account_lid.focus();
			return;
		}
		
		if ((new_uid) && (new_uid != uid))
		{
			var handler_validate_fields = function(data)
			{
				if (!data.status)
					alert(data.msg);
				else
					cExecute ('$this.user.rename&uid='+uid+'&new_uid='+new_uid, handler_rename);
				
				return;
			}
			
			// New uid exist?
			attrs_array = new Array();
			attrs_array['type'] = 'rename_user';
			attrs_array['uid'] = new_uid;
			attributes = connector.serialize(attrs_array);
		
			cExecute ('$this.ldap_functions.validate_fields&attributes='+attributes, handler_validate_fields);
		}
	}
	cExecute ('$this.imap_functions.get_delimiter&uid='+uid, handler_get_delimiter);
}

// HANDLER RENAME
function handler_rename(data)
{
	if (!data.status)
		alert(data.msg);
	else{
		var msg = get_lang('User login successful renamed');
		if ( data.msg ) msg += "\n"+get_lang('With error')+': '+data.msg;
		alert(msg);
		location.href="./index.php?menuaction=expressoAdmin1_2.uiaccounts.list_users";
	}
	return;

}


// Variaveis Locais
var finderTimeout_maillist = '';

// Funcoes Find MailList
function optionFinderTimeout_maillist(obj)
{
	clearTimeout(finderTimeout_maillist);
	var oWait = document.getElementById("ea_span_searching_maillist");
	oWait.innerHTML = get_lang('Searching') + '...';
	finderTimeout_maillist = setTimeout("optionFinder_maillist('"+obj.id+"')",500);
}
function optionFinder_maillist(id) {
	var oWait = document.getElementById("ea_span_searching_maillist");
	var oText = document.getElementById(id);
	var select_available_maillists = document.getElementById( 'ea_select_available_maillists' );
	select_available_maillists_clone = select_available_maillists.cloneNode(true);
		
	//Limpa todo o select
	for(var i = 0;i < select_available_maillists.options.length; i++)
		select_available_maillists.options[i--] = null;
	
	var RegExp_name = new RegExp(oText.value, "i");
	
	//Inclui as listas começando com a pesquisa
	for(i = 0; i < select_available_maillists_clone.length; i++){
		if (RegExp_name.test(select_available_maillists_clone[i].text))
		{
			sel = select_available_maillists.options;
			option = new Option(select_available_maillists_clone[i].text,select_available_maillists_clone[i].value);				
			sel[sel.length] = option;
		}
	}
	oWait.innerHTML = '&nbsp;';
}			

// Variaveis Locais
var finderTimeout_group = '';


// Funcoes Find Group
function optionFinderTimeout_group(obj)
{
	clearTimeout(finderTimeout_group);
	var oWait = document.getElementById("ea_span_searching_group");
	oWait.innerHTML = get_lang('Searching') + '...';
	finderTimeout_group = setTimeout("optionFinder_group('"+obj.id+"')",500);
}
function optionFinder_group(id) {	
	var oWait = document.getElementById("ea_span_searching_group");
	var oText = document.getElementById(id);
	var select_available_groups = document.getElementById( 'ea_select_available_groups' );
		
	//Limpa todo o select
	for(var i = 0;i < select_available_groups.options.length; i++)
		select_available_groups.options[i--] = null;
	
	var RegExp_name = new RegExp(oText.value, "i");
	
	//Inclui as listas começando com a pesquisa
	for(i = 0; i < select_available_groups_clone.length; i++){
		if (RegExp_name.test(select_available_groups_clone[i].text))
		{
			sel = select_available_groups.options;
			option = new Option(select_available_groups_clone[i].text,select_available_groups_clone[i].value);				
			sel[sel.length] = option;
		}
	}
	oWait.innerHTML = '&nbsp;';
}

function get_available_sambadomains(context, type)
{
	if ((type == 'create_user') && (document.getElementById('tabcontent7').style.display != 'none'))
	{
		var handler_get_available_sambadomains = function(data)
		{
			document.forms[0].use_attrs_samba.checked = data.status;
			use_samba_attrs(data.status);
			
			if (data.status)
			{
				combo_sambadomains = document.getElementById('ea_combo_sambadomains');
				for (i=0; i<data.sambaDomains.length; i++)
				{
					for (j=0; j<combo_sambadomains.length; j++)
					{
						if (combo_sambadomains.options[j].text == data.sambaDomains[i])
						{
							combo_sambadomains.options[j].selected = true;
							break;
						}
					}
				}
				
			}
		}
		
		cExecute ('$this.ldap_functions.exist_sambadomains_in_context&context='+context, handler_get_available_sambadomains);
	}
}

function empty_inbox(uid)
{
	var action = get_lang('Cleanned user mailbox');
	var handler_write_log = function(){}
	var handler_empty_inbox = function(data)
	{
		if (!data.status)
			alert(data.msg);
		else{
			cExecute ('$this.user.write_log_from_ajax&_action='+action+'&userinfo='+uid, handler_write_log);
			alert(get_lang('Emptied ') + data.inbox_size + ' ' + get_lang('MB from user inbox'));
			document.getElementById('mailquota_used').value = data.mailquota_used;
		}
	}
	cExecute ('$this.imap_functions.empty_inbox&uid='+uid, handler_empty_inbox);
}

function validarCPF(cpf)
{
	if(cpf.length != 11 || cpf == "00000000000" || cpf == "11111111111" ||
		cpf == "22222222222" || cpf == "33333333333" || cpf == "44444444444" ||
		cpf == "55555555555" || cpf == "66666666666" || cpf == "77777777777" ||
		cpf == "88888888888" || cpf == "99999999999"){
	  return false;
   }

	soma = 0;
	for(i = 0; i < 9; i++)
		soma += parseInt(cpf.charAt(i)) * (10 - i);
	resto = 11 - (soma % 11);
	if(resto == 10 || resto == 11)
		resto = 0;
	if(resto != parseInt(cpf.charAt(9)))
	{
		return false;
	}
	
	soma = 0;
	for(i = 0; i < 10; i ++)
		soma += parseInt(cpf.charAt(i)) * (11 - i);
	resto = 11 - (soma % 11);
	if(resto == 10 || resto == 11)
		resto = 0;
	if(resto != parseInt(cpf.charAt(10))){
		return false;
	}
	return true;
}

function search_organization(event, key, element)
{
	var organizations = document.getElementById(element);
	var RegExp_org = new RegExp("\\b"+key, "i");
	var k = event? event.which || event.keyCode : 0;
	var inc = ( k == 38 )? -1 : 1;
	var ini = ( k == 13 || k == 38 || k == 40 )? $(organizations)[0].selectedIndex + inc : 0;
	
	for ( i = ini; i < organizations.length && i >= 0; i += inc )
	{
		if (RegExp_org.test(organizations[i].text))
		{
			organizations[i].selected = true;
			Domain.getDomains();
			return;
		}
	}
	
	if ( k == 13 ) search_organization( undefined, key, element );
}

function add_input_mailalternateaddress()
{
	var input = document.createElement("INPUT");
	input.size = 50;
	input.name = "mailalternateaddress[]";
	input.setAttribute("autocomplete","off");
	document.getElementById("td_input_mailalternateaddress").appendChild(document.createElement("br"));
	document.getElementById("td_input_mailalternateaddress").appendChild(input);
}

function add_input_mailforwardingaddress()
{
	var input = document.createElement("INPUT");
	input.size = 50;
	input.name = "mailforwardingaddress[]";
	input.setAttribute("autocomplete","off");
	document.getElementById("td_input_mailforwardingaddress").appendChild(document.createElement("br"));
	document.getElementById("td_input_mailforwardingaddress").appendChild(input);
}

function random_passwd()
{
	pw_length=8;
	//chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; //62
	chars = "abcdefghijklmnopqrstuvwxyz1234567890";// Numeros: 27-36
	pass = "";
	
	i = "";
	old_i = "";
	
	for(x=0; x<pw_length; x++)
	{
		i = Math.floor(Math.random() * 36);
		
		// Evita caracteres idênticos consecutivos
		// Evita caracteres numéricos consecutivos
		while ( (old_i == i) || (((old_i >= 27) && (old_i <=36)) && ((i >= 27) && (i <=36))) )
		{
			i = Math.floor(Math.random() * 36);
		}
		
		pass += chars.charAt(i);
		old_i = i;
	}
	
	document.getElementById("password1").value = pass;
	document.getElementById("password2").value = pass;
	
	document.getElementById('passwd_expired').checked = true;
	document.getElementById('passwd_expired').disabled = true;
}

function disable_passwd_expired_field()
{
	if (document.getElementById("password1").value.length > 0)
	{
		document.getElementById('passwd_expired').checked = true;
		document.getElementById('passwd_expired').disabled = true;
	}
	else
	{
		document.getElementById('passwd_expired').disabled = false;
	}
}

function create_user_inbox(uid)
{
	var action = get_lang('Created user inbox');
	var quota = document.getElementById('mailquota').value;
	
	var handler_write_log = function(){}
	var handler_create_user_inbox = function(data)
	{
		if (!data.status)
		{
			alert(data.msg);
		}
		else
		{
			cExecute ('$this.user.write_log_from_ajax&_action='+action+'&userinfo='+uid, handler_write_log);

			document.getElementById('display_quota').style.display = '';
			document.getElementById('mailquota').value = data.quota;
			document.getElementById('display_quota_used').style.display = '';
			document.getElementById('display_empty_user_inbox').style.display = ''
			document.getElementById('display_create_user_inbox').style.display = 'none'

			alert( get_lang('User inbox successful created') + '.');
		}
	}

	cExecute ('$this.imap_functions.create_user_inbox&uid='+uid+'&mailquota='+quota, handler_create_user_inbox);
}
