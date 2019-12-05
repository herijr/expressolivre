$(function()
{
	$("#tabs").tabs().css("margin-left","20px");
	$("#tabs ul.ui-tabs-nav.ui-helper-reset.ui-helper-clearfix.ui-widget-header.ui-corner-all")
	.css("border","1px solid #D3DCE3")
	.css("background","#D3DCE3");

	if($("select[name=imapencryption]").val() == "no") {
		$("input[type=checkbox][name=imapvalidatecert]").prop("checked", false);
		$("input[type=checkbox][name=imapvalidatecert]").prop("disabled", true);
	} else {
		$("input[type=checkbox][name=imapvalidatecert]").prop("disabled", false);
	}
});

$("#button_save").button().click(function(){

	if( $.trim($("input[type=text][name=description]").val()) !== "" )
	{	
		$(document).find("form[name=mailsettings]").submit();
	}
	else
	{
		alert( $("input[type=hidden][name=lang_profile_name_blank]").val() );
	}

});

// Dominios
$(".domains div label").css("width","9em");
$(".domains div").css("margin-top","20px");
$(".domains div a").button().click(function()
{
	addDomains();
});

// Domains Delete
$(".td_size[menu_action=delete]")
.css("cursor","pointer")
.click(function()
{	
	if( $(this).attr("domainid") )
		deleteDomains( { 'domainid' : $(this).attr("domainid"), 'profileid' : $("input[type=hidden][name=profileid]").val(), 'return' : true } );
	else
		deleteDomains( { 'domain' : $(this).attr("domain"), 'return' : true } );
});

// Accordion Domains
$("#acc_domains").accordion({ collapsible: true });

// Accordion SMTP
$("#acc_smtp").accordion({
	heightStyle: "content"
});

// Accordion POP3/IMAP
$("#acc_pop3_imap").accordion({
	heightStyle: "content"
});

// SMTPPORT : Keypress, verify is number
$("input[type=text][name=smtpport]").on('keypress', function(e)
{
	var key = ( window.event) ? event.keyCode: e.which; return _is_number( key );
});

// IMAPPORT : Keypress, verify is number
$("input[type=text][name=imapport]").on('keypress', function(e)
{
	var key = ( window.event) ? event.keyCode: e.which; return _is_number( key );
});

// IMAPSIEVEPORT : Keypress, verify is number
$("input[type=text][name=imapsieveport]").on('keypress', function(e)
{
	var key = ( window.event) ? event.keyCode: e.which; return _is_number( key );
});

//IMAPENCRIPTION : IF SELECTED VALID OPTION, DISABLE VALIDADE CERT
$("select[name=imapencryption]").on('change', function(e)
{
	if(this.value == "no") {
		$("input[type=checkbox][name=imapvalidatecert]").prop("checked", false);
		$("input[type=checkbox][name=imapvalidatecert]").prop("disabled", true);
	} else {
		$("input[type=checkbox][name=imapvalidatecert]").prop("disabled", false);
	}
});

function _is_number(key)
{
	if( (key>47 && key<58) )
	{
		return true;
	}
	else
	{
		if( key==8 || key==0 )
			return true;
	}

	return false;
}

function addDomains()
{
	var domain 		= $.trim($(".domains input[type=text]").val());
	var profileid	= $("input[type=hidden][name=profileid]").val();

	if( domain != "" )
	{	
		$.ajax({
            type	: 'POST',
            url		: 'index.php?menuaction=emailadmin.ui.addDomains',
            data	: { 'domain' : domain, 'profileid' : profileid },
            success: function( data )
            {
            	var _data = JSON.parse(data);
	            var _tableDomains = $("#table_domains");

            	if( !_data.error )
            	{
            		drawTable( _data );

					var _height = _tableDomains.parent().parent().height() + 14;

					_tableDomains.parent().parent().css("height", _height);

					// Clear input domain;	
					$(".domains input[type=text]").val('');
				}
				else
				{
					if( _data.error === "add_domain_registered" )
						$("#msg_erro_add_domain").css("display","block").delay(3000).fadeOut('slow');

					if( _data.error === "domain_invalid")
						$("#msg_erro_invalid_domain").css("display","block").delay(3000).fadeOut('slow');
				}
				_tableDomains.parent().parent().css("overflow","hidden");
            }
        });
    }
}

function deleteDomains( params )
{
	if( confirm( $("input[type=hidden][name=lang_confirm_domain]").val() ) )
	{
		$.ajax({
	        type	: 'POST',
	        url		: 'index.php?menuaction=emailadmin.ui.deleteDomains',
	        data	: params,
	        success: function(data)
	        {
	        	var _data = JSON.parse(data);
	        	var _tableDomains = $("#table_domains");
	        	
	        	if( !_data.error )
	        	{
		       		drawTable( _data );

					var _height = _tableDomains.parent().parent().height() - 14;

					_tableDomains.parent().parent().css("height", _height);
				}
				_tableDomains.parent().parent().css("overflow","hidden");        	
	        }
    	});
	}
}

function drawTable( _data )
{
	var _tableDomains = $("#table_domains");

	var _tableTH = [];

	_tableDomains.find("th").each(function( key )
	{
		_tableTH[key] = $(this).html();
	});

	// Clear table domains;
	_tableDomains.html('');
	_tableDomains.append('<tr></tr>');
	_tableDomains.find("tr").append('<th>'+_tableTH[0]+'</th>');
	_tableDomains.find("tr").append('<th class="th_size">'+_tableTH[1]+'</th>');


	// Create Table;
	var rows = '';
	
	for( var i in _data )
	{
		rows =  '<tr><td>'+_data[i].domain+'</td>';

		if( _data[i].domainid )
			rows += '<td class="td_size" menu_action="delete" domainid="'+_data[i].domainid+'">'+_tableTH[1]+'</td></tr>';
		else
			rows += '<td class="td_size" menu_action="delete" domain="'+_data[i].domain+'">'+_tableTH[1]+'</td></tr>';	        			

		_tableDomains.append( rows );
	}

	// Delete domains
	$(".td_size[menu_action=delete]")
	.css("cursor","pointer")
	.click(function()
	{	
		if( $(this).attr("domainid") )
			deleteDomains( { 'domainid' : $(this).attr("domainid"), 'profileid' : $("input[type=hidden][name=profileid]").val(), 'return' : true } );
		else
			deleteDomains( { 'domain' : $(this).attr("domain"), 'return' : true } );
	});
}
