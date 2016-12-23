// Variaveis Globais
countFiles = 0;

var typeForm = "";

function validate_fields(type, restrictionsOnGroup)
{
	typeForm = type;

	document.forms[0].cn.value = document.forms[0].cn.value.toLowerCase();
	
	if (document.forms[0].cn.value == ''){
		alert(get_lang('NAME field is empty') + '.');
		return;
	}
		
	if (document.forms[0].description.value == ''){
		alert(get_lang('DESCRIPTION field is empty') + '.');
		return;
	}
	
	if (restrictionsOnGroup == 'true')
	{
		cn_tmp = document.forms[0].cn.value.split("-");
		if ( (cn_tmp.length < 3) || ((cn_tmp[0] != 'grupo') && (cn_tmp[0] != 'smb')) ){
			alert(
				get_lang('NAME field is incomplete') + '.\n' +
				get_lang('the name field must be formed like') + ':\n' +
				get_lang('group') + '-' + get_lang('organization') + '-' + get_lang('group name') + '.\n' +
				get_lang('eg') + ': ' + 'grupo-celepar-rh.');
			return;
		}
	}
	
	var reCn = /^([a-zA-Z0-9_\-])+$/;
	if(!reCn.test(document.forms[0].cn.value)){
		alert(get_lang('NAME field contains characters not allowed') + '.');
		document.forms[0].cn.focus();
		return;
	}

	var reEmail = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if ( (!$('input[name=grp_of_names]')[0].checked) && (document.forms[0].email.value != '') && (!reEmail.test(document.forms[0].email.value)) )
	{
		alert(get_lang('EMAIL field is empty') + '.');
		return false;
	}
	
	var handler_validate_fields = function(data)
	{
		if ( !( data && data.status ) )
			alert( (data && data.msg)? data.msg : 'Error' );
		else
		{
			if (type == 'create_group')
				cExecuteForm ("$this.group.create", document.forms[0], callBackReturn );
			else if (type == 'edit_group')
				cExecuteForm ("$this.group.save", document.forms[0], callBackReturn);
		}
	}

	// Needed select all options from select
	select_userInGroup = document.getElementById('ea_select_usersInGroup');
	for(var i=0; i<select_userInGroup.options.length; i++)
		select_userInGroup.options[i].selected = true;

	select_user_scm = document.getElementById('ea_select_users_scm');
	for(var i=0; i<select_user_scm.options.length; i++)
		select_user_scm.options[i].selected = true;
	
	// Uid, Mail and CPF exist?
	var attrs_array = new Array();
	attrs_array['cn']           = document.forms[0].cn.value;
	attrs_array['type']         = type;
	attrs_array['email']        = document.forms[0].email.value;
	attrs_array['context']      = $('select[name=context]').val();
	attrs_array['gidnumber']    = document.forms[0].gidnumber.value;
	attrs_array['grp_of_names'] = $('input[name=grp_of_names]')[0].checked? 'on' : '';
	var attributes = connector.serialize(attrs_array);
	
	// Validate some datas on PHP side.

	cExecute ('$this.ldap_functions.validate_fields_group2&attributes='+attributes, handler_validate_fields);
}

// HANDLER CREATE
// É necessário 2 funcões de retorno por causa do cExecuteForm.
function callBackReturn( data ){ _processReturn( data ); }

function _processReturn( data )
{
	if( data.status && $.trim(data.msg) === "" )
	{
		var _msg = get_lang('Group successful created') + '.';
	
		if( typeForm == "edit_group" )
		{	
			_msg = get_lang('Group successful saved') + '.';	
		}

		alert( _msg );

		typeForm = "";

		location.href = "./index.php?menuaction=expressoAdmin1_2.uigroups.list_groups";
	}
	else
	{
		if( data.msg ){ alert(data.msg); }
	}
}

function sinc_combos_org(context, recursive)
{
	combo_org_groups = document.getElementById('ea_combo_org_groups');

	for (i=0; i<combo_org_groups.length; i++)
	{
		if (combo_org_groups.options[i].value == context)
		{
			combo_org_groups.options[i].selected = true;
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
		select_available_users_scm = document.getElementById('ea_select_available_users_scm');
		
		//Limpa o select
		for(var i=0; i<select_available_users.options.length; i++)
		{
			select_available_users.options[i] = null;
			i--;
		}

		for(var i=0; i<select_available_users_scm.options.length; i++)
		{
			select_available_users_scm.options[i] = null;
			i--;
		}

		if ((data) && (data.length > 0))
		{
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_users.innerHTML = '&nbsp;' + data;
			select_available_users.outerHTML = select_available_users.outerHTML;
			
			select_available_users_scm.innerHTML = '&nbsp;' + data;
			select_available_users_scm.outerHTML = select_available_users_scm.outerHTML;

			select_available_users.disabled = false;
			select_available_users_clone = document.getElementById('ea_select_available_users').cloneNode(true);
			document.getElementById('ea_input_searchUser').value = '';
		}
	}
	
	var chkbox = document.getElementById('ea_check_allUsers');
	chkbox.disabled = ( document.forms[0].ldap_context.value == document.getElementById('ea_combo_org_groups').value );
	
	cExecute (
		'$this.ldap_functions.get_available_users'+
		'&context='+context+
		'&recursive='+(chkbox.checked && !chkbox.disabled),
		handler_get_available_users
	);
}

function add_user2group()
{
	var select_available_users = document.getElementById('ea_select_available_users');
	var select_usersInGroup = document.getElementById('ea_select_usersInGroup');

	var count_available_users = select_available_users.length;
	var count_usersInGroup = select_usersInGroup.options.length;
	var new_options = '';
	
	for (i = 0 ; i < count_available_users ; i++)
	{
		if (select_available_users.options[i].selected)
		{
			if(document.all)
			{
				if ( (select_usersInGroup.innerHTML.indexOf('value='+select_available_users.options[i].value)) == '-1' )
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
				if ( (select_usersInGroup.innerHTML.indexOf('value="'+select_available_users.options[i].value+'"')) == '-1' )
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
		select_usersInGroup.innerHTML = '&nbsp;' + new_options + select_usersInGroup.innerHTML;
		select_usersInGroup.outerHTML = select_usersInGroup.outerHTML;
		document.getElementById('ea_input_searchUser').value = "";
	}
}

function remove_user2group()
{
	select_usersInGroup = document.getElementById('ea_select_usersInGroup');
	
	for(var i = 0;i < select_usersInGroup.options.length; i++)
		if(select_usersInGroup.options[i].selected)
			select_usersInGroup.options[i--] = null;
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
	var finderTimeout = setTimeout("optionFinder('"+obj.id+"')",500);
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
		if (RegExp_name.test(select_available_users_clone[i].text))
		{
			sel = select_available_users_tmp.options;
			option = new Option(select_available_users_clone[i].text,select_available_users_clone[i].value);				
			sel[sel.length] = option;
		}
	}
	
	oWait.innerHTML = '&nbsp;';
}			

function delete_group(obj, dn)
{
	if (confirm(get_lang('Do you really want delete the group') + ' ' + $(obj).parents('tr:first').find('td:first').html() + " ?"))
	{
		var handler_delete_group = function(data)
		{
			if (!data.status)
				alert(data.msg);
			else
				alert(get_lang('Group successful deleted') + '.');
			window.location.reload();
			return;
		}
		cExecute ('$this.group.delete&id='+dn, handler_delete_group);
	}
}

function use_samba_attrs(value)
{
	document.forms[0].sambasid.disabled = !value;
}

function get_available_sambadomains(context, type)
{
	if ((type == 'create_group') && (document.getElementById('ea_div_display_samba_options').style.display != 'none'))
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

function groupEmailSuggestion(concatenateDomain)
{
	if (document.forms[0].email.disabled)
		return;
	
	if (document.forms[0].email.value != '')
		return;

	if (concatenateDomain != 'true')
		return;
	
	var default_domain = document.forms[0].defaultDomain.value;
	var selected_context = dn2ufn(document.forms[0].context.value.toLowerCase());
	var array_org_name = selected_context.split('.');	
	var org_name = array_org_name[0];
	
	document.forms[0].email.value = document.forms[0].cn.value + "@" + org_name + "." + default_domain;
	
	return;
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

function popup_group_info()
{
	var select_usersInGroup = document.getElementById('ea_select_usersInGroup');
	var count_usersInGroup = select_usersInGroup.options.length;
	var html = '';
	
	for (i = 0 ; i < count_usersInGroup ; i++)
	{
		if(parseInt(select_usersInGroup.options[i].value) > 0)
			html += select_usersInGroup.options[i].text + '<br>';
	}

	var window_group = window.open('','','width=300,height=400,resizable=yes,scrollbars=yes,left=100,top=100');
	window_group.document.body.innerHTML = '<html><head></head><body><H1>'+ document.forms[0].cn.value + '</H1>'+html+'</body></html>';
	window_group.document.close();
	return true;
}

function copy_group(gidnumber)
{
	
	if (!(cn = prompt(get_lang('type the common name of the new group') + ':',"")))
	{
		return;
	}
	
	var handler_copy = function(data)
	{
		if (!data.status)
			alert(data.msg);
		else{
			alert(get_lang('group successful copied') + '.');
			window.location.reload();
		}
		return;
	}
	cExecute ("$this.group.copy&gidnumber="+gidnumber+"&cn="+cn, handler_copy);
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

function add_user2scm()
{
	select_available_users_scm = document.getElementById('ea_select_available_users_scm');
	select_users_scm = document.getElementById('ea_select_users_scm');

	var count_available_users_scm = select_available_users_scm.length;
	var count_users_scm = select_users_scm.options.length;
	var new_options = '';

	for (i = 0 ; i < count_available_users_scm ; i++)
	{
		if (select_available_users_scm.options[i].selected)
		{
			if(document.all)
			{
				if ( (select_users_scm.innerHTML.indexOf('value='+select_available_users_scm.options[i].value)) == '-1' )
				{
					new_options +=  "<option value="
								+ select_available_users_scm.options[i].value
								+ ">"
								+ select_available_users_scm.options[i].text
								+ "</options>";
				}
			}
			else
			{		
				if ( (select_users_scm.innerHTML.indexOf('value="'+select_available_users_scm.options[i].value+'"')) == '-1' )
				{
					new_options +=  "<option value="
								+ select_available_users_scm.options[i].value
								+ ">"
								+ select_available_users_scm.options[i].text
								+ "</options>";
				}
			}
		}
	}

	if (new_options != '')
	{
		select_users_scm.innerHTML = '#' + new_options + select_users_scm.innerHTML;
		select_users_scm.outerHTML = select_users_scm.outerHTML;
	}
}

function remove_user2scm()
{
	select_users_scm = document.getElementById('ea_select_users_scm');
	
	for(var i = 0;i < select_users_scm.options.length; i++)
		if(select_users_scm.options[i].selected)
			select_users_scm.options[i--] = null;
}
