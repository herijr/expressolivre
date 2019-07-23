var SignatureFrame = new function() {

	this.init = function( $bdy_ifrm, insert_mode )
	{
		$bdy_ifrm = $bdy_ifrm.jquery? $bdy_ifrm : $($bdy_ifrm);

		SignatureFrame.redraw( $bdy_ifrm, $bdy_ifrm.contents().find('body'), insert_mode );

		var disableEnableSignature = function(){

			var ID = $($bdy_ifrm).attr("id").replace( 'body_', '' );

			var data = $('select#from_'+ID).find(':selected').data();

			if( $bdy_ifrm.contents().find('iframe#use_signature_anchor').contents().find('body').contents().length == 0 ){
				
				if( !data.default_signature ){ 
					
					window.clearTimeout( $bdy_ifrm.data('timer') ); 
					
					$('select#from_'+ID).find(':selected').data( 'use_signature', '0' );
				}
			} else {
				
				$('select#from_'+ID).find(':selected').data( 'use_signature', '1' );
				
				SignatureFrame.persist( $bdy_ifrm );
			}			
		};

		$bdy_ifrm.contents().on('selectionchange.edit', function(){ disableEnableSignature(); });

		$bdy_ifrm.contents().on('mouseup.edit', function(){ disableEnableSignature(); });

		$bdy_ifrm.contents().on('mousedown.edit', function(){ disableEnableSignature(); });

		$bdy_ifrm.contents().on('keyup.edit', function(){ disableEnableSignature(); });
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
		var _redraw = SignatureFrame.redraw( $bdy_ifrm, undefined, 'after', false );

		if( $bdy_ifrm.contents().find('iframe#use_signature_anchor').length == 0 ){
			_redraw = SignatureFrame.redraw( $bdy_ifrm, $bdy_ifrm.contents().find('body'), 'append' );
		}
		
		return _redraw;
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
		funct     = typeof(funct) !== 'undefined'? funct : 'append';
		extra     = typeof(extra) !== 'undefined'? extra : $('<br>');

		var ID = $bdy_ifrm.attr('id').replace( 'body_', '' );

		var data = $('select#from_'+ID).find(':selected').data();
		if ( !data ) return;

		$('img#signature').toggle( !!data.signature );

		$bdy_ifrm.contents().find('iframe#use_signature_anchor:not(:first)').remove();

		var $ifrm = $bdy_ifrm.contents().find('iframe#use_signature_anchor');
		if ( !$ifrm.length ) {
			$ifrm = $('<iframe>').attr({ 'id': 'use_signature_anchor', 'frameborder': '0', 'contentEditable': 'true', 'scrolling' : 'no' }).css({ 'width': '100%' }).on('load',function(){
				var data = $('select#from_'+ID).find(':selected').data();
				if ( $ifrm.data('writed') != $ifrm.contents().find('body').html() ) SignatureFrame.write( $ifrm, data.mail, $ifrm.data('writed'), true, data.default_signature );

				$ifrm.contents().find('head').append(
					$('<style>').attr({'type':'text/css'}).text(
						'body { margin:0; }'+
						'body:hover { background-color: aliceblue; }'+
						'pre { margin:0; white-space:pre-wrap; overflow-wrap:break-word; font-family: monospace; font-size:11px;}'
					)
				);

				SignatureFrame.setHeight( $ifrm );
			});

			var $has_div = $bdy_ifrm.contents().find('div#use_signature_anchor');
			if ( $has_div.length > 0 ) {
				$has_div.after( $ifrm );
				$has_div.remove();
			} else {
				location = typeof(location) !== 'undefined' ? location : SignatureFrame.caretPosition( $bdy_ifrm );
				if ( typeof(location) !== 'undefined' && location.tagName == 'BODY') funct = ( funct == 'after' )? 'append' : ( ( funct == 'before' )? 'prepend' : funct );
				$(location)[funct]( extra, $ifrm );
			}
		} else if ( data.mail == $ifrm.data('mail') && data.use_signature == '1' ) {
			$('select#from_'+ID).find(':selected').data( 'signature', $ifrm.contents().find('body').html() );
		}

		$ifrm.toggle( ( data.default_signature || data.use_signature == '1' ) );
		var signature = ( data.default_signature || ( data.use_signature == '1' && data.signature ) )? (
			( $('#textplain_rt_checkbox_'+ID).is(':checked') || ( typeof(data.type_signature) !== 'undefined' && data.type_signature != 'html' ) )?
			'<pre>'+RichTextEditor.stripHTML( data.signature ).join('')+'</pre>' : data.signature
		) : '';

		SignatureFrame.write( $ifrm, data.mail, signature, ( data.mail != $ifrm.data('mail') ), data.default_signature );

	}

	this.write = function( $ifrm, mail, signature, force, isDefault )
	{
		try {
			if ( !$ifrm.contents() ) return;

			signature = typeof(signature) !== 'undefined'? signature : '';
			force     = typeof(force)     !== 'undefined'? force : false;

			if ( ( !force ) && $ifrm.data( 'writed' ) == signature ) return;

			$ifrm.data( 'writed', signature );

			$ifrm.data( 'mail', mail );

			var doc = $ifrm.contents().get(0);

			$(doc).find('body').attr( 'contentEditable', !isDefault ).html( signature ).find('img').off('load').on('load',function(){ SignatureFrame.setHeight( $ifrm ); });

			SignatureFrame.setHeight( $ifrm );

		} catch (e) {}
	}

	this.setHeight = function( $ifrm )
	{
		$ifrm = $ifrm.jquery? $ifrm : $($ifrm);

		if( !$ifrm.contents().find('body').children().length ) return;

		try{
			$ifrm.height( $ifrm.contents().find('body').get(0).lastChild.getBoundingClientRect().bottom );
		} catch(e){
			//alert(e);
		}
	};
}