function submit_pagination( obj, value ) {
	if ( $(obj).hasClass('disabled') ) return false;
	$('input[name=page]').val( value);
	$('#pagination').submit();
}

function open_sessions( username ) {
	$('#view-sessions-dialog').data( 'refresh', false ).find( 'tbody' ).html( '' );
	$('#view-sessions-dialog').attr( 'title', username ).dialog({
		width: 800,
		resizable: false,
		position: 'center',
		modal: true,
		close: function() {
			if ( $('#view-sessions-dialog').data( 'refresh' ) ) {
				submit_pagination( undefined, $('#pagination').data( 'page' ) );
				$('#dialog-refresh').dialog({
					resizable: false,
					position: 'center',
					dialogClass: "no-close",
					closeOnEscape: false,
					modal: true
				});
			}
		}
	});
	var dialog = $('#view-sessions-dialog').parents( 'div.ui-dialog:first' );
	if ( $(dialog).find( '.loadgif' ).length == 0 ) $(dialog).find( '.ui-dialog-titlebar' ).append( $('<img class="loadgif" src="./prototype/plugins/messenger/images/ajax-loader.gif">') );
	
	$(dialog).find('.loadgif').show();
	$(dialog).find('.err-msg').css({ opacity: 0 });
	
	$.ajax({
		url: '/index.php?menuaction=admin.uicurrentsessions.get_sessions',
		type: 'GET',
		data: { id: username },
		dataType: 'json'
	}).fail( function( jqXHR, textStatus ){
		updateTips( $('#view-sessions-dialog .err-msg'), jqXHR.responseJSON && jqXHR.responseJSON.error? jqXHR.responseJSON.error : jqXHR.statusText, false );
		$('#view-sessions-dialog').data( 'refresh', true );
	}).done( function( data ){
		$.each( data, function( i ) {
			
			$('#view-sessions-dialog').find('tbody').append(
				$('<tr>').addClass( data[i].isvalid? '' : 'ui-state-error' )
				.append( $('<td>').html( data[i].session + '...' ) )
				.append( $('<td>').html( data[i].uidnumber ) )
				.append( $('<td>').html( data[i].ip? data[i].ip : '-' ) )
				.append( $('<td>').html( data[i].login ) )
				.append( $('<td>').html( data[i].idle? data[i].idle : '-' ) )
				.append( $('<td>').html( data[i].action? data[i].action : '-' ) )
			);
			
			if ( data[i].isvalid && data[i].kill ) {
				$('#view-sessions-dialog').find('tbody tr:last')
				.append( $('<td>').addClass( 'icon' )
					.append( $('<img>').attr( 'src', '/phpgwapi/templates/default/images/delete.png' ).data({ id: username, session: data[i].session }).click(function(){
						$.ajax({
							obj: this,
							url: '/index.php?menuaction=admin.uicurrentsessions.kill',
							type: 'GET',
							data: $(this).data(),
							dataType: 'json'
						}).fail( function( jqXHR, textStatus ){
							updateTips( $('#view-sessions-dialog .err-msg'), jqXHR.responseJSON && jqXHR.responseJSON.error? jqXHR.responseJSON.error : jqXHR.statusText );
						}).done( function( data ){
							var tbody = $(this.obj).parents('tbody:first');
							$(this.obj).parents('tr:first').remove();
							$('#view-sessions-dialog').data( 'refresh', true );
							if ( $(tbody).find('tr').length == 0 ) $('#view-sessions-dialog').dialog('close');
						})
					}) )
				)
			} else {
				$('#view-sessions-dialog').find('tbody tr:last').append( $('<td>').html( '-' ) );
				if ( !data[i].isvalid ) $('#view-sessions-dialog').data( 'refresh', true );
			}
			
		});
	}).always( function(){
		$(dialog).find('.loadgif').hide();
	});
};

function kill_all( total, filter ) {
	if ( $( "#dialog-confirm" ).data( 'refresh' ) ) return;
	var opts = {
		resizable: false,
		modal: true,
		buttons: {}
	};
	opts.buttons[$( "#dialog-confirm" ).data('button-ok')] = function( event ) {
		$(event.currentTarget).addClass('ui-state-disabled').attr('disabled','disabled');
		send_kill_all( event.currentTarget, total, filter );
	};
	opts.buttons[$( "#dialog-confirm" ).data('button-cancel')] = function() {
		$( this ).dialog( 'close' );
	};
	$( '#kill-err-msg' ).html('');
	$( "#dialog-confirm" ).dialog( opts );
};

function send_kill_all( btn, total, filter ) {
	
	$.ajax({
		url: '/index.php?menuaction=admin.uicurrentsessions.kill_all',
		type: 'GET',
		data: { filter: filter },
		dataType: 'json'
	}).fail( function( jqXHR, textStatus ){
		$( '#kill-err-msg' ).html( jqXHR.responseJSON && jqXHR.responseJSON.error? jqXHR.responseJSON.error : jqXHR.statusText );
	}).done( function( data ) {
		if ( parseInt( data.status ) > 0 && $( "#dialog-confirm" ).dialog( "isOpen" ) ) {
			$( "#dialog-confirm" ).data( 'refresh', true );
			$('#kill-cont').html( parseInt( $('#kill-cont').html() ) + parseInt( data.status ) );
			send_kill_all( btn, total, filter );
		} else {
			if ( parseInt( data.status ) > 0 || $( "#dialog-confirm" ).data( 'refresh' ) )
				 submit_pagination( undefined, $('#pagination').data( 'page' ) );
			$( btn ).removeClass('ui-state-disabled').attr('disabled',null);
			$( "#dialog-confirm" ).dialog( 'close' );
		}
	});
}

function updateTips( obj, msg, hide ) {
	if ( hide == undefined ) hide = true;
	$( obj ).find( 'label' ).html( msg )
	$( obj ).animate( { opacity: 1 }, 500 );
	if ( hide ) setTimeout( function() { $(obj).animate( { opacity: 0 }, 1000 ); }, 3000 );
};