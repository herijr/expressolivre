countFiles = 0;

var typeForm 	= "";

function validate_fields(type)
{
	typeForm = type;

	document.forms[0].uid.value = document.forms[0].uid.value.toLowerCase();
	document.forms[0].old_uid.value = document.forms[0].old_uid.value.toLowerCase();

	if (document.forms[0].uid.value == ''){
		alert(get_lang('login field is empty') + '.');
		return;
	}

	if (document.forms[0].cn.value == ''){
		alert(get_lang('name field is empty') + '.');
		return;
	}
	
	if (document.forms[0].restrictionsOnEmailLists.value == 'true')
	{
		uid_tmp = document.forms[0].uid.value.split("-");
		if ((uid_tmp.length < 3) || (uid_tmp[0] != 'lista')){
			alert(
				get_lang('login field is incomplete') + '.\n' +
				get_lang('the login field must be formed like') + ':\n' +
				get_lang('list') + '-' + get_lang('organization') + '-' + get_lang('listname') + '.\n' +
				get_lang('eg') + ': ' + 'lista-celepar-rh.');
			return;
		}
	}
		
	if (document.forms[0].uid.value.split(" ").length > 1){
		alert(get_lang('LOGIN field contains characters not allowed') + '.');
		document.forms[0].uid.focus();
		return;
	}
	
	if (document.forms[0].mail.value == ''){
		alert(get_lang('EMAIL field is empty') + '.');
		document.forms[0].mail.focus();
		return;
	}
	var reEmail = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if(!reEmail.test(document.forms[0].mail.value)){
		alert(get_lang('Email field is not valid') + '.');
		return false;
	}
	
	select_userInMaillist = document.getElementById('ea_select_usersInMaillist');
	if (select_userInMaillist.options.length == 0){
		alert(get_lang('Any user is in the list') + '.');
		return;
	}
	
	var handler_validate_fields = function(data)
	{
		if (!data.status)
			alert(data.msg);
		else
		{
			if (type == 'create_maillist')
				cExecuteForm ("$this.maillist.create", document.forms[0], callBackReturn );
			else if (type == 'edit_maillist')
				cExecuteForm ("$this.maillist.save", document.forms[0], callBackReturn );
		}
	}

	// Needed select all options from select
	for(var i=0; i<select_userInMaillist.options.length; i++)
	{
		// No IE, não seleciona o separador do select
		if (select_userInMaillist.options[i].value != -1)
			select_userInMaillist.options[i].selected = true;
		else
			select_userInMaillist.options[i].selected = false;
	}

	// O UID da lista foi alterado ou é uma nova lista.
	if ((document.forms[0].old_uid.value != document.forms[0].uid.value) || (type == 'create_maillist')){
		cExecute ('$this.maillist.validate_fields&uid='+document.forms[0].uid.value+'&mail='+document.forms[0].mail.value, handler_validate_fields);
	}
	else if (type == 'edit_maillist')
	{
		cExecuteForm ("$this.maillist.save", document.forms[0], callBackReturn );
	}
}


// HANDLER CREATE / SAVE
// É necessário 2 funcões de retorno por causa do cExecuteForm.
function callBackReturn( data ){ _processReturn( data ); }

function _processReturn( data )
{
	if( data.status && $.trim(data.msg) === "" )
	{
		var _msg = get_lang('Email list successful created') + '.';
	
		if( typeForm == "edit_maillist" )
		{	
			_msg = get_lang('Email list successful saved') + '.';	
		}

		alert( _msg );

		typeForm = "";

		location.href="./index.php?menuaction=expressoAdmin1_2.uimaillists.list_maillists";
	}
	else
	{
		if( data.msg ){ alert(data.msg); }
	}
}

function save_scl()
{
	select_users_SCL_Maillist = document.getElementById('ea_select_users_SCL_Maillist');
	// Needed select all options from select
	for(var i=0; i<select_users_SCL_Maillist.options.length; i++)
		select_users_SCL_Maillist.options[i].selected = true;

	cExecuteForm ("$this.maillist.save_scl", document.forms[0], handler_save_scl);
}
function handler_save_scl(data)
{
	return_handler_save_scl(data);
}

function return_handler_save_scl(data)
{
	if (!data.status)
		alert(data.msg);
	else
		alert(get_lang('SCL successful saved') + '.');
	location.href="./index.php?menuaction=expressoAdmin1_2.uimaillists.list_maillists";
	return;
}

function sinc_combos_org(context, recursive)
{
	combo_org_maillists = document.getElementById('ea_combo_org_maillists');

	for (i=0; i<combo_org_maillists.length; i++)
	{
		if (combo_org_maillists.options[i].value == context)
		{
			combo_org_maillists.options[i].selected = true;
			get_available_users(context, recursive);
			break;
		}
	}
}

function get_available_users(context, recursive)
{
	var handler_get_available_users = function(data)
	{
		select_available_users = document.getElementById('ea_select_available_users');
		
		//Limpa o select
		for(var i=0; i<select_available_users.options.length; i++)
		{
			select_available_users.options[i] = null;
			i--;
		}

		if ((data) && (data.length > 0))
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_users.innerHTML = '&nbsp;' + data;
			select_available_users.outerHTML = select_available_users.outerHTML;
			
			select_available_users.disabled = false;
			select_available_users_clone = document.getElementById('ea_select_available_users').cloneNode(true);
			document.getElementById('ea_input_searchUser').value = '';
		}
	}
	
	var chkbox = document.getElementById('ea_check_allUsers');
	chkbox.disabled = ( document.forms[0].ldap_context.value == document.getElementById('ea_combo_org_maillists').value );
	
	cExecute (
		'$this.ldap_functions.get_available_users_and_maillist'+
		'&context='+context+
		'&recursive='+(chkbox.checked && !chkbox.disabled)+
		'&denied_uidnumber='+document.forms[0].uidnumber.value,
		handler_get_available_users
	);
}

function add_user2maillist()
{
	select_usersInMaillist = document.getElementById('ea_select_usersInMaillist');
	
	ea_input_externalEmail = document.getElementById('ea_input_externalEmail').value;
	if (ea_input_externalEmail != '')
	{
		if (email_validation(ea_input_externalEmail))
		{
			externalEmail_option =  "<option value="
						+ ea_input_externalEmail
						+ ">"
						+ ea_input_externalEmail
						+ "</options>";
					
			select_usersInMaillist.innerHTML = '&nbsp;' + externalEmail_option + select_usersInMaillist.innerHTML;
			select_usersInMaillist.outerHTML = select_usersInMaillist.outerHTML;
			document.getElementById('ea_input_searchUser').value = "";
		
			document.getElementById('ea_input_externalEmail').value = '';
		}
		else
		{
			alert(get_lang('invalid external email') + '.');
		}
	}
	else
	{
		select_available_users = document.getElementById('ea_select_available_users');

		var count_available_users = select_available_users.length;
		var count_usersInMailList = select_usersInMaillist.options.length;
		var new_options = '';

		for (i = 0 ; i < count_available_users ; i++)
		{
			if (select_available_users.options[i].selected)
			{
				if(document.all)
				{
					if ( (select_usersInMaillist.innerHTML.indexOf('value='+select_available_users.options[i].value)) == '-1' )
					{
						new_options +=  "<option value="
									+ select_available_users.options[i].value
									+ ">"
									+ select_available_users.options[i].text
									+ "</options>";
					}
				}
				else
				{		
					if ( (select_usersInMaillist.innerHTML.indexOf('value="'+select_available_users.options[i].value+'"')) == '-1' )
					{
						new_options +=  "<option value="
									+ select_available_users.options[i].value
									+ ">"
									+ select_available_users.options[i].text
									+ "</options>";
					}
				}
			}
		}

		if (new_options != '')
		{
			select_usersInMaillist.innerHTML = '&nbsp;' + new_options + select_usersInMaillist.innerHTML;
			select_usersInMaillist.outerHTML = select_usersInMaillist.outerHTML;
			document.getElementById('ea_input_searchUser').value = "";
		}
	}
}

function remove_user2maillist()
{
	select_usersInMaillist = document.getElementById('ea_select_usersInMaillist');
	
	for(var i = 0;i < select_usersInMaillist.options.length; i++)
		if(select_usersInMaillist.options[i].selected)
			select_usersInMaillist.options[i--] = null;
}

function add_user2scl_maillist()
{
	select_available_users = document.getElementById('ea_select_available_users');
	select_usersInMaillist = document.getElementById('ea_select_users_SCL_Maillist');

	var count_available_users = select_available_users.length;
	var count_usersInMailList = select_usersInMaillist.options.length;
	var new_options = '';

	for (i = 0 ; i < count_available_users ; i++)
	{
		if (select_available_users.options[i].selected)
		{
			if(document.all)
			{
				if ( (select_usersInMaillist.innerHTML.indexOf('value='+select_available_users.options[i].value)) == '-1' )
				{
					new_options +=  "<option value="
								+ select_available_users.options[i].value
								+ ">"
								+ select_available_users.options[i].text
								+ "</options>";
				}
			}
			else
			{		
				if ( (select_usersInMaillist.innerHTML.indexOf('value="'+select_available_users.options[i].value+'"')) == '-1' )
				{
					new_options +=  "<option value="
								+ select_available_users.options[i].value
								+ ">"
								+ select_available_users.options[i].text
								+ "</options>";
				}
			}
		}
	}

	if (new_options != '')
	{
		select_usersInMaillist.innerHTML = '#' + new_options + select_usersInMaillist.innerHTML;
		select_usersInMaillist.outerHTML = select_usersInMaillist.outerHTML;
	}
}

function remove_user2scl_maillist()
{
	select_usersInMaillist = document.getElementById('ea_select_users_SCL_Maillist');
	
	for(var i = 0;i < select_usersInMaillist.options.length; i++)
		if(select_usersInMaillist.options[i].selected)
			select_usersInMaillist.options[i--] = null;
}


// Variaveis Locais 
if (document.getElementById('ea_select_available_users'))
{
	var select_available_users  = document.getElementById('ea_select_available_users');
	var select_available_users_clone = select_available_users.cloneNode(true);
}
else
{
	var select_available_users  = '';
	var select_available_users_clone = '';
}
var finderTimeout = '';

// Funcoes
function optionFinderTimeout(obj)
{
	clearTimeout(finderTimeout);	
	var oWait = document.getElementById("ea_span_searching");
	oWait.innerHTML = get_lang('Searching') + '...';
	finderTimeout = setTimeout("optionFinder('"+obj.id+"')",500);
}
function optionFinder(id) {
	var oWait = document.getElementById("ea_span_searching");
	var oText = document.getElementById(id);
		
	//Limpa todo o select
	var select_available_users_tmp = document.getElementById('ea_select_available_users')

	for(var i = 0;i < select_available_users_tmp.options.length; i++)
		select_available_users_tmp.options[i--] = null;

	var RegExp_name = new RegExp("\\b"+oText.value, "i");
	
	//Inclui usuário começando com a pesquisa
	for(i = 0; i < select_available_users_clone.length; i++){
		if ( RegExp_name.test(select_available_users_clone[i].text) || (select_available_users_clone[i].value == -1) )
		{
			sel = select_available_users_tmp.options;
			option = new Option(select_available_users_clone[i].text,select_available_users_clone[i].value);

			if (select_available_users_clone[i].value == -1)
				option.disabled = true;

			sel[sel.length] = option;
		}
	}
	oWait.innerHTML = '&nbsp;';
}			

function delete_maillist(uid, uidnumber)
{
	if (confirm(get_lang('Do you really want delete the email list') + ' ' + uid + " ??"))
	
	{
		var handler_delete_maillist = function(data)
		{
			if (!data.status)
				alert(data.msg);
			else
				alert(get_lang('Email list successful deleted') + '.');
			
			location.href="./index.php?menuaction=expressoAdmin1_2.uimaillists.list_maillists";
			return;
		}
		cExecute ('$this.maillist.delete&uidnumber='+uidnumber, handler_delete_maillist);
	}
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
			return;
		}
	}
	
	if ( k == 13 ) search_organization( undefined, key, element );
}

function emailSuggestion_maillist()
{
	var defaultDomain = document.forms[0].defaultDomain.value;
	var base_dn = "." + dn2ufn(document.forms[0].ldap_context.value);
	var selected_context = dn2ufn(document.forms[0].context.value.toLowerCase());

	var uid = document.getElementById("ea_maillist_uid");
	var mail= document.getElementById("ea_maillist_mail");
	
	var raw_selected_context = selected_context.replace(base_dn, "");
	
	var array_org_name = raw_selected_context.split('.');
	var org_name = array_org_name[array_org_name.length-1];
	
	if (mail.value == "")
		mail.value = uid.value + "@" + org_name + "." + defaultDomain;
}

function dn2ufn(dn)
{
	var ufn = '';
	var array_dn = dn.split(",");
	for (x in array_dn)
	{
		var tmp = array_dn[x].split("=");
		ufn += tmp[1] + '.';
	}
	return ufn.substring(0,(ufn.length-1));
}

function email_validation(str)
{
	var at="@"
	var dot="."
	var lat=str.indexOf(at)
	var lstr=str.length
	var ldot=str.indexOf(dot)
	if (str.indexOf(at)==-1){
	   return false
	}

	if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
	   return false
	}

	if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
	    return false
	}

	 if (str.indexOf(at,(lat+1))!=-1){
	    return false
	 }

	 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
	    return false
	 }

	 if (str.indexOf(dot,(lat+2))==-1){
	    return false
	 }
		
	 if (str.indexOf(" ")!=-1){
	    return false
	 }

 	 return true					
}

function popup_maillist_info()
{
	var select_usersInMaillist = document.getElementById('ea_select_usersInMaillist');
	var count_usersInMaillist = select_usersInMaillist.options.length;
	var html = '';
	
	for (i = 0 ; i < count_usersInMaillist ; i++)
	{
		if(parseInt(select_usersInMaillist.options[i].value) != -1)
			html += select_usersInMaillist.options[i].text + '<br>';
	}

	var window_maillist = window.open('','','width=400,height=400,resizable=yes,scrollbars=yes,left=100,top=100');
	window_maillist.document.body.innerHTML = '<html><head></head><body><H1>'+ document.forms[0].uid.value + '</H1><H3>'+ document.forms[0].cn.value + '</H3>'+html+'</body></html>';
	window_maillist.document.close();

	return true;
}