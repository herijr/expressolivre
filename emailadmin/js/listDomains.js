
jQuery.expr.filters.available = function( e ) {
	return !( jQuery.css( e, "display" ) === "none" || !jQuery.contains( e.ownerDocument, e ) );
}

function openDomainDialog( event ) {
	
	var params = [];
	if ( $(event.currentTarget).parent().data( 'id' ) != undefined ) {
		var parent         = $(event.currentTarget).parent();
		params.id          = $(parent).data( 'id' );
		params.description = $(parent).find( '[name=description]' ).html();
		params.domain      = $(parent).find( '[name=domain]' ).html();
		params.ous         = [];
		$(parent).find( '[name=ous] p' ).each( function() {
			params.ous.push( $(this).html() );
		} );
	}
	
	$.ajax({
		type    : 'POST',
		url     : 'index.php?menuaction=emailadmin.ui.getProfiles',
		params  : params,
		success : function( data ) {
			var _data = JSON.parse( data );
			
			if ( _data.error ) {
				alert( _data.error );
				return false;
			}
			
			$('#div_add_domain').dialog( {
				modal       : true,
				width       : 400,
				title       : $('input[type=hidden][name=lang_admin_add_domain]').val(),
				position    : { my: 'center center' },
				resizable   : false,
				buttons     : [
					{
						text  : $('input[type=hidden][name=lang_save]').val(),
						click : function() { addDomains( $(this) ); }
					},
					{
						text  : $('input[type=hidden][name=lang_close]').val(),
						click : function() { $(this).dialog( 'destroy' ); }
					}
				]
			} ).parent().hide();
			
			$('#div_add_domain').next().css( { 'background-color': '#E0EEEE' } );
			
			_data.lang = {
				'add'           : $('input[type=hidden][name=lang_add]').val(),
				'remove'        : $('input[type=hidden][name=lang_remove]').val(),
				'label_profile' : $('input[type=hidden][name=lang_label_profile]').val(),
				'label_domain'  : $('input[type=hidden][name=lang_label_domain]').val(),
				'label_ous'     : $('input[type=hidden][name=lang_label_ous]').val()
			};
			
			$("#div_add_domain")
				.html( new EJS( { url: './emailadmin/templates/default/addDomain.ejs', cache: false } ).render( _data ) )
				.find('button').button();
			
			$('#select_ous_domain option').each( function() {
				$('#selected_organization_units')
					.append( $( '<option>' ).val( $(this).val() ).html( $(this).val() ).hide() );
			});
			
			// Action is edit
			if ( this.params.id ) {
				var sdiv = $('#selected_profile').parent().parent();
				$('#selected_profile').parent().remove();
				$(sdiv).append( $('<input>').attr( {
					'type'     : 'text',
					'size'     : '50',
					'disabled' : 'disabled'
				}).val( this.params.description ) );
				$(sdiv).append( $('<input>').attr( {
					'id'       : 'input_domainid',
					'type'     : 'hidden',
					'size'     : '50',
					'disabled' : 'disabled'
				}).val( this.params.id ) );
				$('#input_search_domain').val( this.params.domain ).attr('disabled','disabled');
				$.each( this.params.ous, function( idx, val ){
					$('#selected_organization_units option[value$="'+val+'"]').hide();
					$('#selected_organization_units option[value="'+val+'"]').show();
				} );
			}
			
			$('#selected_ous_domain_add').on( 'click', function() {
				$('#select_ous_domain option:selected').each( function(){
					$('#selected_organization_units option[value$="'+$(this).val()+'"]').hide();
					$('#selected_organization_units option[value="'+$(this).val()+'"]').show();
				} );
				redrawSelectOU();
			} );
			
			$('#selected_ous_domain_remove').on( 'click', function() {
				$('#selected_organization_units option:selected').hide();
				redrawSelectOU();
			} );
			
			$('#div_add_domain').parent().show();
			redrawSelectOU();
		}
	});
}

function redrawSelectOU() {
	$('#select_ous_domain').val( false );
	$('#selected_organization_units').val( false );
	$('#select_ous_domain option').removeAttr('disabled');
	$('#selected_organization_units option:available').each( function(){
		$('#select_ous_domain option[value$="'+$(this).val()+'"]').attr('disabled','disabled');
	} );
}

//Button Search Domain
$("form input[type=submit][name=button_search_domain]").button();

$('#add_domain').on( 'click' , openDomainDialog ).button();
$('#tables_domains').find( 'td[menu_action=edit]' ).on( 'click', openDomainDialog ).css( { 'cursor': 'pointer' } );

$("#tables_domains").find("td[menu_action=delete]").each(function(){

    // Delete Domain
    $(this).on("click", function()
    {
       deleteDomains( { 'domainid' : $(this).attr("domainid") }, $(this) );
    
    }).css("cursor", "pointer");
});

$("#tables_domains").find("td[menu_action=move]").each(function(){

    var namePerfil  = $(this).parent().find("td:first-child").html();
    var domainId    = $(this).attr("domainid");

    // Move Domain
    $(this).on("click", function()
    {
        $.ajax({
            type    : "POST",
            url     : "index.php?menuaction=emailadmin.ui.getProfiles",
            success : function(data)
            {
                var _data = JSON.parse(data);

                if( !_data.error )
                {
                    $("#div_add_domain").dialog(
                    {
                        modal       : true,
                        width       : 400,
                        height      : 200,
                        title       : $("input[type=hidden][name=lang_admin_move_domain]").val(),
                        position    : { my: "center center" },
                        resizable   : false,
                        buttons     : [
                                       {
                                            text    : $("input[type=hidden][name=lang_move]").val(),
                                            click   : function()
                                            {
                                                moveDomains( domainId, $(this).find("select").val(), $(this) );
                                            }
                                       },
                                       {
                                            text    : $("input[type=hidden][name=lang_close]").val(),
                                            click   : function()
                                            {
                                                $(this).dialog("destroy");
                                            }
                                       }]               
                    });

                    $("#div_add_domain").next().css("background-color", "#E0EEEE");

                    var _EJS = { 
                        'profiles'      : _data, 
                        'name_perfil'   : namePerfil
                    };

                    $("#div_add_domain").html( new EJS( { url: './emailadmin/templates/default/moveDomain.ejs', cache: false } ).render( _EJS ));
                }
                else
                {    
                    alert( _data.error );
                }
            }
        });

    }).css("cursor", "pointer");
});

function addDomains( dialog )
{
	if ( $.trim( $('#input_search_domain').val() ) != '' ) {
		
		var ous = [];
		$('#selected_organization_units option:available').each( function() {
			ous.push( $(this).val() );
		} );
		
		var params = {
			'domain'    : $('#input_search_domain').val(),
			'profileid' : $('#selected_profile').val(),
			'domainid'  : $('#input_domainid').val(),
			'ous'       : ous,
			'return'    : false
		};
		
		$.ajax( {
			type    : 'POST',
			url     : 'index.php?menuaction=emailadmin.ui.addDomains',
			data    : params,
			params  : params,
			success : function( data ) {
				
				var _data = JSON.parse( data );
				
				if ( _data.return ) {
					
					if ( _data.return == 'add_domain_ok' ) alert( $('input[type=hidden][name=lang_added_domain]').val() );
					if ( _data.return == 'edit_domain_ok' ){
						var td = $('#tables_domains tr[data-id='+this.params.domainid+'] td[name=ous]');
						$(td).html( ( this.params.ous.length > 0 )? '' : '-' );
						$.each( this.params.ous, function( idx, val ) { $(td).append( $('<p>').html( val ) ) } );
						
						alert( $('input[type=hidden][name=lang_edited_domain]').val() );
					}
					
					$(dialog).dialog( 'destroy' );
					
				} else {
					if ( _data.error == 'add_domain_registered' ) {
						alert( $('input[type=hidden][name=lang_erro_add_domain]').val() );
					}
				}
			}
		});
	} else {
		alert( $('input[type=hidden][name=lang_enter_domain]').val() );
	}
}

function deleteDomains( params, element )
{
    if( confirm( $("input[type=hidden][name=lang_confirm_domain]").val() ) )
    {
        var _element = $(element);

        $.ajax({
            type    : 'POST',
            url     : 'index.php?menuaction=emailadmin.ui.deleteDomains',
            data    : params,
            success: function(data)
            {
                var _data = JSON.parse(data);
                var _tableDomains = $("#tables_domains");
                
                if( _data.result )
                {     
                    _element.parent().remove();
                }
            }
        });
    }
}    

function moveDomains( domainID, newProfileID, dialog )
{
    if( confirm( $("input[type=hidden][name=lang_msg_move_domain]").val() + "\n" + 
        $("input[type=hidden][name=lang_confirm_move_domain]").val() ) )
    {
        $.ajax({
            type    : 'POST',
            url     : 'index.php?menuaction=emailadmin.ui.moveDomain',
            data    : { 'domainid' : domainID ,  'newprofileid' : newProfileID },
            success : function(data)
            {
                var _data = JSON.parse( data );

                if( _data.return )
                {
                    $(dialog).dialog('destroy');

                    alert( $("input[type=hidden][name=lang_domain_success]").val() );

                    window.location = './index.php?menuaction=emailadmin.ui.listDomains';
                }
                
                if( _data.error ) alert( _data.error );
            }
        });
    }

}
