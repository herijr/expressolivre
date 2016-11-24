var countFiles = 1;

var sharedAccounts = new function()
{
	var _acls = [];
	
	var _this = this;

	this.addOwner = function()
	{
		$("#ea_select_available_users option:selected").each(function()
		{	
			var _findOption = false;

			var _optionAvailable = $(this);

			$("#ea_select_owners option").each(function()
			{
				if( $(this).val() === _optionAvailable.val() )
				{
					_findOption = true;
				}
			});

			if( !_findOption )
			{ 
				$("#ea_select_owners").append( new Option( _optionAvailable.text(), _optionAvailable.val() ) );

				_this.cleanComboACLS();
			}
		});
	}

	this.cleanComboACLS = function()
	{
		$("#em_input_readAcl").prop("checked",false);
		$("#em_input_deleteAcl").prop("checked",false);
		$("#em_input_writeAcl").prop("checked", false);
		$("#em_input_sendAcl").prop("checked", false);
	};

	this.close = function()
	{
		$.each( _acls,function(i){ _acls.splice( i, 1); });

		$("#ea_combo_org option").each(function(){ $(this).prop("checked", false ); });

		$("#ea_combo_org_available_users").each(function(){ $(this).prop("checked", false ); });

		close_lightbox();
	};

	this.delOwner = function()
	{
		$("#ea_select_owners option:selected").each(function()
		{
			var _option = $(this);

			$.each( _acls, function(i)
			{ 
				if( $.trim( _option.val() ) === $.trim( _acls[i].user ) )
				{
					_acls.splice( i, 1); 
					
					_this.cleanComboACLS();
				}
			});

			$(this).remove();
		});
	};

	this.edit = function( uid )
	{
		cExecute ('$this.shared_accounts.get_data&uid='+uid, function(data)
		{	
			if( data.status && $.trim(data.status) == "true" )
			{
				modal( 'shared_accounts_modal', 'save' );

				$("#ea_combo_org option").each(function()
				{
					if( $.trim($(this).val()) ===  $.trim(data.user_context) )
					{
						$(this).attr('selected','selected');
					}
				});

				$("#ea_combo_org_available_users option").each(function()
				{
					if( $.trim($(this).val()) ===  $.trim(data.user_context) )
					{
						$(this).attr('selected','selected');

						cExecute ('$this.ldap_functions.get_available_users2&context='+$.trim(data.user_context), function(data_get_users)
						{ 
							$("#ea_select_available_users options").remove();

							$("#ea_select_available_users").html( data_get_users );
						});
					}
				});

				$("#anchor").val( "uid=" + uid + "," + data.user_context );

				if( data.accountStatus && $.trim(data.accountStatus == "active" ) )
					$("#accountStatus").attr( "checked", true );

				if( data.phpgwAccountVisible && $.trim(data.phpgwAccountVisible) == '-1' )
					$("#phpgwAccountVisible").attr("checked", true );

				$("#cn").val( $.trim(data.cn) );
				$("#mail").val( $.trim(data.mail) );
				$("#mail").attr( "disabled", "disabled" );
				$("#mailquota").val( $.trim(data.mailquota) );
	            $("#mailquota").val( $.trim(data.mailquota) );
	            $("#mailquota_used").val( $.trim(data.mailquota_used));
	            $("#quota_used_field").css("display", "inline");
				$("#description").val( $.trim(data.description));
				$("#ea_select_owners").html( data.owners_options && data.owners_options.length > 0  ? data.owners_options : "" );
				$("#display_empty_inbox").css("visibility", data.display_empty_inbox ); 
				$("#quota_used_field").css("visibility", "visible" ); 

				if( (data.owners && data.owners.length > 0 ) && ( data.owners_acl && data.owners_acl.length > 0 ) )
	 			{	
	 				$.each( data.owners_acl, function( i, value)
	 				{
	 					_this.setAclUser( $.trim(data.owners[i]) , $.trim(value) );
	 				});
				}
			}
			else
			{
				if( data.msg && data.msg.length > 0 )
				{
					write_msg( data.msg, 'error' );
				}
			}
		});
	};

	this.emptyMailBox = function( params )
	{
		if( confirm( get_lang('empty inbox') + "?") )
		{	
		    cExecute ('$this.shared_accounts.empty_inbox&uid='+Element('anchor').value, function(data)
		    {
		    	if( data.status )
		    	{
		    		$("#mailquota_used").val("0");

		    		cExecute('$this.user.write_log_from_ajax&_action='+get_lang('Cleanned user mailbox')+'&userinfo='+data.uid, function(){});
		    	}
		    	else
		    	{
		    		if( data.msg ){ alert( "ERRO : " + data.msg ); }
		    	}
		    });
		}
	};

	this.getAvailableUsers = function( context )
	{
		cExecute('$this.ldap_functions.get_available_users2&context='+context, function(data)
		{ 
			$("#ea_select_available_users options").remove();

			$("#ea_select_available_users").html( data );
		});
	};

	this.getAcl = function( user )
	{
		this.cleanComboACLS();
		
		var result = $.grep( _acls, function(value){ return value.user === $.trim(user); } );

		if( result.length > 0 )
		{
			$('#em_input_sendAcl').prop("disabled", true );

			if( result[0].acl.indexOf('lrs',0) >= 0)
			{
				$('#em_input_sendAcl').prop("disabled", false);
				$('#em_input_readAcl').prop("checked", true );
			}
				
			if( result[0].acl.indexOf('d',0) >= 0 )
			{
				$('#em_input_deleteAcl').prop("checked", true );
			}
			if( result[0].acl.indexOf('wip',0) >= 0 )
			{
				$('#em_input_writeAcl').prop("checked", true );
			}
			if( result[0].acl.indexOf('a',0) >= 0 )
			{
				$('#em_input_sendAcl').prop("disabled",false);
				$('#em_input_sendAcl').prop("checked",true);
			}		
		}
	};

	this.save = function()
	{
		var _form = 
		{ 
			'anchor' 			: $.trim($("#anchor").val()),
			'owners_acl' 		: _acls,
			'context'			: $("#ea_combo_org option:selected").val(),
			'cn'				: $.trim($("#cn").val()), 
			'mail'				: $.trim($("#mail").val()),
			'description' 		: $.trim($("#description").val()),
			'mailquota' 			: $.trim($("#mailquota").val()),
			'accountStatus' 		: $("#accountStatus").is(":checked") ? "on" : "off",
			'phpgwAccountVisible' 	: $("#phpgwAccountVisible").is(":checked") ? "on" : "off"
		};

		$.ajax(
		{
			'method'    : 'POST',
			'url'     	:'./expressoAdmin1_2/controller.php',
			'data'		: 
			{
				'action': '$this.shared_accounts.save', 
				'data'	: _form
			},
			success	: function(response)
			{
				var _data = connector.unserialize( response );

				if( _data.status && ( _data.status == 1 || _data.status == true ) ) 
				{
					_this.close();

					var _write_msg = get_lang('Shared account successful saved') + '.';

					if( $.trim( _form.achor ) === "" )
					{
						_write_msg = get_lang('Shared account successful created') + '.';
					}

					write_msg( _write_msg, "normal" );
				}
				else
				{
					if( _data.msg )
					{
						write_msg( _data.msg, 'error' );
					}
				}
			}
		});
	};

	this.setAclUser = function( user, acl )
	{
		var result = $.grep( _acls, function(value){ return value.user == user; } );

		if( result.length > 0 )
		{
			for( var i in _acls )
			{
				if( _acls[i].user == user ){ _acls[i].acl = acl; }
			}
		}
		else
		{
			_acls[ _acls.length ] = { "user" : $.trim(user) , "acl" : $.trim(acl) };
		}
	};

	this.setAcl = function( checkBox )
	{
		var _options = $("#ea_select_owners option:selected");

		if( _options.length == 1 )
		{
			var _user = _options.val();

			var _acl = "";

			if( $("#em_input_readAcl").is(":checked") )
			{
				$("#em_input_sendAcl").prop("disabled", false);
				
				_acl = "lrs";
			}
			else
			{
				$("#em_input_sendAcl").prop( "disabled ", true );
				$("#em_input_sendAcl").prop( "checked", false );
			}

			if( $("#em_input_deleteAcl").is(":checked") ){ _acl += "d"; }
			if( $("#em_input_writeAcl").is(":checked") ){ _acl += "wip"; }
			if( $("#em_input_sendAcl").is(":checked") ){ _acl += "a"; }

			this.setAclUser( _user , _acl );
		}
		else
		{
			var _getLang = "";

			switch( _options.length )
			{
				case 0: _getLang = get_lang("select an user"); break;
				case 2: _getLang = get_lang("select only one"); break;
			}
		
			$(checkBox).prop( "checked", false );

			alert( _getLang );
		}
	};

	this.remove = function( uid )
	{
		if (!confirm(get_lang('Are you sure that you want to delete this shared account') + "?")) return;

		cExecute ('$this.shared_accounts.delete&uid='+uid, function(data)
		{
			if( !data.status )
			{
				write_msg( data.msg, 'error' );
			}
			else
			{
				write_msg(get_lang('Shared account successful deleted') + '.', 'normal');
				
				get_shared_accounts(Element('ea_shared_account_search').value);
			}
			
			return;
		});
	};
};