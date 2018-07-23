function check_overquota() {
	if ( parseInt( $('input[name=mailquota]').val() ) <= parseInt( $('#mailquotaused').html() ) ) $('#overquotamsg').show();
	else $('#overquotamsg').hide();
}
$(document).ready(function()
{

	check_overquota();
	$('input[name=mailquota]').on('keyup.overquota',check_overquota);
	
	var _loadData = function()
	{
		$('#ea_combo_org_info').trigger('change');
	};
	
	Domain.init( $('select[name=context]'), $('input[name=ldap_context]'), $('input[name=defaultDomain]') );
	Domain.preLoadDomains();
	Domain.redraw( function( domains, changed ) {
		if ( domains == undefined ) return false;
		var uidlogin = $('input[name=uid]').val().replace(/@.*/,'');
		if ( changed ) {
			var label = $('ul#suggestmails').parent().find('label:first');
			$(label).html( $(label).data('label').split(':')[(domains.length > 1? 1 : 0)]+':' );
			var lilabel = $('ul#suggestmails').data('label');
			$('ul#suggestmails').html('');
			$.each( domains, function( index, value ) {
				$('ul#suggestmails').append(
					$('<li>').css( { 'padding': '0 0 3px' } ).append(
						$('<input>')
							.attr( { 'disabled': 'disabled', 'size': '50', 'autocomlete': 'off' } )
							.data( { 'domain': value } )
							.css( { 'background': 'none repeat scroll 0 0 rgba(0, 0, 0, 0)', 'width': '303px' } )
							.val( uidlogin + '@' + value )
					).append(
						$('<span>').css( { 'cursor': 'pointer' } ).html( ' '+lilabel ).on( 'click', function() {
							$('input[name=mail]').val(
								$(this).parents('li:first').find('input').val()
							).trigger('paste.pfl')
						} )
					)
				);
			} );
		}
		$('ul#suggestmails li input').each( function() {
			$(this).val( uidlogin + '@' + $(this).data('domain') );
		} );
		if ( ( $('input[name=mail]').data( 'auto' ) || $('input[name=mail]').val().length == 0 ) ) {
			$('input[name=mail]').val( $('ul#suggestmails li input:first').val() ).data( 'auto', true );
			if ( changed ) Profile.checkMailEvent();
		}
	});
	
	Profile.init();
	Profile.redraw();
	
	// Select Groups
	$('#ea_select_available_groups').ready(function(){
		setTimeout(function(){_loadData()},1000);
	});

	// Select MailList
	$('#ea_select_available_maillists').ready(function(){
		setTimeout(function(){_loadData()},1000);
	});

});

