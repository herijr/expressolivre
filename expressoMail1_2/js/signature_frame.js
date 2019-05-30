var SignatureFrame = new function() {

	this.init = function( $bdy_ifrm, insert_mode )
	{
		$bdy_ifrm = $bdy_ifrm.jquery? $bdy_ifrm : $($bdy_ifrm);

		SignatureFrame.redraw( $bdy_ifrm, $bdy_ifrm.contents().find('body'), insert_mode );

		$bdy_ifrm.contents().on( 'selectionchange.edit,mouseup.edit,mousedown.edit,keyup.edit', function(){
			SignatureFrame.persist( $bdy_ifrm );
		});
	};

	this.persist = function( $bdy_ifrm )
	{
		$bdy_ifrm = $bdy_ifrm.jquery? $bdy_ifrm : $($bdy_ifrm);

		window.clearTimeout( $bdy_ifrm.data('timer') );

		if ( !$bdy_ifrm.contents().find('iframe#use_signature_anchor').length )
			return SignatureFrame.redrawOnCaret( $bdy_ifrm );

		$bdy_ifrm.data( 'timer', window.setTimeout(function(){
			SignatureFrame.redrawOnCaret( $bdy_ifrm );
		},1000));
	};

	this.redrawOnCaret = function( $bdy_ifrm )
	{
		return SignatureFrame.redraw( $bdy_ifrm, undefined, 'after', false );
	}

	this.caretPosition = function( $bdy_ifrm )
	{
		try {
			$bdy_ifrm = $bdy_ifrm.jquery? $bdy_ifrm : $($bdy_ifrm);

			var win = $bdy_ifrm.get(0).contentWindow || $bdy_ifrm.get(0).document.parentWindow;
			if ( win.getSelection ) {

				var sel = win.getSelection();
				switch ( sel.focusNode.nodeType ) {

					case 1: // If the node is an element node
						return sel.focusNode.childNodes[sel.focusOffset];

					case 3: //If the node is a text node
						var tail_txt = sel.focusNode.data.slice(sel.focusOffset);
						sel.focusNode.data = sel.focusNode.data.slice(0,sel.focusOffset);
						$(sel.focusNode).after(tail_txt);
					default: return sel.focusNode;
				}

			} else if ( $bdy_ifrm.get(0).document.selection ) {
				// for IE
				$bdy_ifrm.get(0).focus();
				var range = $bdy_ifrm.get(0).document.selection.createRange();
				return range.parentElement();
			}
		} catch (e) {}
	}

	this.redraw = function( $bdy_ifrm, location, funct, extra )
	{
		$bdy_ifrm = $bdy_ifrm.jquery? $bdy_ifrm : $($bdy_ifrm);
		funct     = funct !== undefined? funct : 'append';
		extra     = extra !== undefined? extra : $('<br>');

		var ID = $bdy_ifrm.attr('id').replace( 'body_', '' )

		var data = $('select#from_'+ID).find(':selected').data();
		if ( !data ) return;

		$('img#signature').toggle( !!data.signature );

		$bdy_ifrm.contents().find('iframe#use_signature_anchor:not(:first)').remove();

		var $ifrm = $bdy_ifrm.contents().find('iframe#use_signature_anchor');

		if ( !$ifrm.length ) {
			$ifrm = $('<iframe>').attr({ 'id': 'use_signature_anchor', 'frameborder': '0', 'contenteditable': 'false' }).css({ 'width': '100%' }).on('load',function(){
				if ( $ifrm.data('writed') != $ifrm.contents().find('body').html() ) SignatureFrame.write( $ifrm, $ifrm.data('writed'), true );
				SignatureFrame.setHeight( $ifrm );
			});

			var $has_div = $bdy_ifrm.contents().find('div#use_signature_anchor');
			if ( $has_div.length > 0 ) {
				$has_div.after( $ifrm );
				$has_div.remove();
			} else {
				location = location !== undefined? location : SignatureFrame.caretPosition( $bdy_ifrm );
				if ( location.tagName == 'BODY') funct = ( funct == 'after' )? 'append' : ( ( funct == 'before' )? 'prepend' : funct );
				$(location)[funct]( extra, $ifrm );
			}
		}
		if ( !( data.default_signature || data.use_signature == '1' ) ) $ifrm.hide();
		else {
			var signature = ( $('#textplain_rt_checkbox_'+ID).is(':checked') || ( data.type_signature !== undefined && data.type_signature != 'html' ) )?
				'<pre>'+RichTextEditor.stripHTML( data.signature ).join('')+'</pre>': data.signature;
			$ifrm.show();
			SignatureFrame.write( $ifrm, signature, false )
		}
	}

	this.write = function( $ifrm, signature, force )
	{
		try {

			if ( !$ifrm.contents() ) return;

			signature = signature !== undefined? signature : '';
			force     = force     !== undefined? force : false;

			if ( ( !force ) && $ifrm.data( 'writed' ) == signature ) return;

			$ifrm.data( 'writed', signature );

			var doc = $ifrm.contents().get(0);
			doc.open();
			doc.write( signature );
			doc.close();

			$(doc).find('head').append(
				$('<style>').attr({'type':'text/css'}).text(
					'body{margin:0;}'+
					'pre{margin:0;white-space: pre-wrap !important;overflow-wrap: break-word !important;font-family: !monospace important;font-size: 11px !important;}'
				)
			);

			SignatureFrame.setHeight( $ifrm );

		} catch (e) {}
	}

	this.setHeight = function( $ifrm )
	{
		$ifrm = $ifrm.jquery? $ifrm : $($ifrm);

		if( !$ifrm.contents().find('body').children().length ) return;

		$ifrm.height( $ifrm.contents().find('body').get(0).lastChild.getBoundingClientRect().bottom );
	};
}