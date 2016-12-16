countFiles = 1;

var available_users = null;

function create_institutional_accounts()
{
	select_owners = Element('ea_select_owners');
	for(var i = 0;i < select_owners.options.length; i++)
		select_owners.options[i].selected = true;
	cExecuteForm ("$this.ldap_functions.create_institutional_accounts", document.forms['institutional_accounts_form'], handler_create_institutional_accounts);
}

function handler_create_institutional_accounts(data_return)
{
	handler_create_institutional_accounts2(data_return);
	return;
}

function handler_create_institutional_accounts2(data_return)
{
	if (!data_return.status)
	{
		write_msg(data_return.msg, 'error');
	}
	else
	{
		close_lightbox();
		write_msg(get_lang('Institutional account successful created') + '.', 'normal');
	}
	return;
}

function set_onload()
{
	sinc_combos_org( $("#ea_combo_org").val() );

	get_available_users( $("#ea_combo_org").val() );
}

function get_available_users(context)
{
	$("#ea_select_available_users").find("option").each(function()
	{
		if( $(this).length > 0 )
		{
			$(this).remove();
		}
	});

	var	handler_get_users = function(data)
	{
		if( data && data.length > 0 )
		{
			$("#ea_select_available_users").html( data );
		}
	}

	cExecute ('$this.ldap_functions.get_available_users&context='+context, handler_get_users);
}

function search_organization(event, key, element)
{
	var organizations = Element(element);
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

function sinc_combos_org(context)
{
	combo_org_available_users = Element('ea_combo_org_available_users');

	for (i=0; i<combo_org_available_users.length; i++)
	{
		if (combo_org_available_users.options[i].value == context)
		{
			combo_org_available_users.options[i].selected = true;
			get_available_users(context);
			break;
		}
	}
}

var finderTimeout = '';
function optionFinderTimeout(obj)
{
	clearTimeout(finderTimeout);
	var oWait = Element("ea_span_searching");
	oWait.innerHTML = get_lang('searching') + '...';
	finderTimeout = setTimeout("optionFinder('"+obj.id+"')",500);
}
function optionFinder( elementID )
{
	var _findUser = $("#"+elementID).val();

	var RegExpName = new RegExp("\\b"+_findUser, "i");

	$.fn.optVisible = function( show )
	{
		if( show )
			this.filter( "span > option").unwrap();
		else
			this.filter( ":not( span > option )").wrap( "<span>" ).parent().hide();

		return this;
	}

	$("#ea_select_available_users").find("option").each(function()
	{
		if( RegExpName.test( $(this).text() ) )
		{
			$(this).optVisible( true );
		}
		else
		{
			$(this).optVisible( false );
		}
	});


	$("#ea_span_searching").html("");
}

function add_user()
{
	var select_available_users = $("#ea_select_available_users");

	var select_owners = $("#ea_select_owners");

	select_available_users.find("option:selected").each(function()
	{
		if( select_owners.find("option[value="+$(this).val()+"]").length == 0 )
		{
			select_owners.append( new Option( $(this).text(), $(this).val() ) );
		}
	});
}

function remove_user()
{
	var select_owners = $("#ea_select_owners");

	select_owners.find("option:selected").each(function()
	{
		$(this).remove();
	});
}

function get_institutional_accounts_timeOut(input)
{
	var head_table = '<table border="0" width="90%"><tr bgcolor="#d3dce3"><td width="30%">'+get_lang("full name")+'</td><td width="30%">'+get_lang("mail")+'</td><td width="5%" align="center">'+get_lang("remove")+'</td></tr></table>';

	if (input.length > 4)
	{
		clearTimeout(finderTimeout);

		finderTimeout = setTimeout("get_institutional_accounts('"+input+"')",500);
	}
	else
	{
		Element('institutional_accounts_content').innerHTML = head_table;
	}
}

function get_institutional_accounts(input)
{
	var handler_get_institutional_accounts = function(data)
	{
		if( data.status && data.status.toLowerCase() === 'true' )
		{
			var table = '<table border="0" width="90%"><tr bgcolor="#d3dce3"><td width="30%">'+get_lang("full name")+'</td><td width="30%">'+get_lang("mail")+'</td><td width="5%" align="center">'+get_lang("remove")+'</td></tr>'+data.trs+'</table>';
			Element('institutional_accounts_content').innerHTML = table;
		}
		else
		{
			if(data.msg && data.msg.length > 0 )
			{
				write_msg(data.msg, 'error');
			}
		}
	}
	cExecute ('$this.ldap_functions.get_institutional_accounts&input='+input, handler_get_institutional_accounts);
}

function edit_institutional_account(uid)
{
	var handle_edit_institutional_account = function(data)
	{
		if( data.status && data.status.toLowerCase() === 'true' )
		{
			modal('institutional_accounts_modal','save');

			var combo_org = Element('ea_combo_org');
			for (i=0; i<combo_org.length; i++)
			{
				if (combo_org.options[i].value == data.user_context)
				{
					combo_org.options[i].selected = true;
					break;
				}
			}

			// anchor
			$("#anchor").val( "uid=" + uid + ',' + data.user_context );
			if(data.accountStatus != 'active'){ $('#accountStatus').attr("checked","false"); }
			if(data.phpgwAccountVisible == '-1'){ $('#phpgwAccountVisible').attr("checked","true"); }
			$('#cn').val( data.cn);
			$('#mail').val( data.mail );
			$('#description').val( data.description );

			$("#ea_select_owners").html( data.owners );

			sinc_combos_org(data.user_context);
		}
		else
		{
			if( data.msg && data.msg.length > 0 )
			{
				write_msg(data.msg, 'error');
			}
		}
	}
	cExecute ('$this.ldap_functions.get_institutional_account_data&uid='+uid, handle_edit_institutional_account);
}

function save_institutional_accounts()
{
	$("#ea_select_owners").find("option").each(function()
	{
		$(this).prop( "selected", true );
	});

	cExecuteForm( "$this.ldap_functions.save_institutional_accounts", document.forms[0], handler_save_institutional_accounts );
}

function handler_save_institutional_accounts(data_return)
{
	handler_save_institutional_accounts2(data_return);
	return;
}
function handler_save_institutional_accounts2(data_return)
{
	if ( data_return.msg && data_return.msg.length > 0 )
	{
		write_msg(data_return.msg, 'error');
	}
	else
	{
		get_institutional_accounts(Element('ea_institutional_account_search').value);

		close_lightbox();

		write_msg(get_lang('Institutional account successful saved') + '.', 'normal');
	}

	return;
}

function delete_institutional_accounts(uid)
{
	if (!confirm(get_lang('Are you sure that you want to delete this institutional account') + "?"))
	{
		return;
	}

	var handle_delete_institutional_account = function(data_return)
	{
		if (!data_return.status)
		{
			write_msg(data_return.msg, 'error');
		}
		else
		{
			write_msg(get_lang('Institutional account successful deleted') + '.', 'normal');

			get_institutional_accounts(Element('ea_institutional_account_search').value);
		}

		return;
	}

	cExecute ('$this.ldap_functions.delete_institutional_account_data&uid='+uid, handle_delete_institutional_account);
}
