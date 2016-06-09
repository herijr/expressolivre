$( document ).on('GlangLoaded',function() {
	
	function addRadiusProfile( id, desc ) {
		
		$('#radius-groups').append('<h3 id="radius-groups-'+id+'">'+id+'<div class="iconbar"></div></h3>');
		
		var tplAddField = $('#tpl-add-field').clone( true ).attr('id',null);
		$('#radius-groups').append(tplAddField);
		createOptionAddField(tplAddField);
		addInput(tplAddField, 'description', [desc], { 'label': Glang.get('description'), 'buttons': { 'cancel': clearInput } });
		
	};
	
	function createOptionAddField( target ) {
		var sel = $(target).find('.add-field select');
		var opts = $(target).data().schema.may;
		for (var i in opts) {
			$(sel).append('<option value="'+opts[i]+'">'+opts[i]+'</option>');
		}
		$(sel).change(changedAddField);
	};
	
	function initAddField( id, opt_name, value ) {
		var grp = $('#radius-groups-'+id);
		var opt = $(grp).next().find('select option[value='+opt_name+']').first();
		if ( $(opt).length > 0 && $(opt).css('display') != 'none' ) {
			$(opt).css('display','none');
			addInput( $(grp).next(), opt_name, value, makeOpts( opt_name ));
		}
	};
	
	function changedAddField() {
		if ( $(this).val().length > 0 ) {
			addInput( $(this).parents('.ui-accordion-content').first(), $(this).val(), [''], makeOpts( $(this).val() ));
			$(this).find('option:selected').css('display','none');
			$(this).val('')
			$('#radius-groups').accordion('refresh');
		}
	};
	
	function makeOpts( name ) {
		var schema = $('#tpl-add-field').data().schema;
		var opts = { 'buttons': { 'cancel': clearInput, 'trash': removeInput} };
		var attr = schema.attr_dict[name];
		if ( attr != undefined ) {
			if ( !attr['single-value'] ) opts.buttons.plus = addInputField;
			opts.syntax = attr.syntax;
		} 
		if ( name == 'radiusGroupName' ) {
			opts.type = 'select';
			opts.label = Glang.get('admin group');
			opts.adm_groups = schema.adm_groups;
		}
		return opts;
	};
	
	function clearInput() {
		$(this).parents('.hline').find('input').val('');
		$(this).parents('.hline').find('select').val('');
	};
	
	function removeInput() {
		var name = $(this).parents('.hline').first().find('label').first().data().value;
		$(this).parents('.ui-accordion-content').first().find('select option[value='+name+']').css('display','');
		$(this).parents('.hline').remove();
		$('#radius-groups').accordion('refresh');
	};
	
	function addInput( target, name, value, opts ) {
		var id = $(target).prev().attr('id').replace(/^radius-groups-/,'');
		var tplType = opts.type? opts.type : 'input';
		var tplInput = $('#tpl-'+tplType).clone().attr('id',null);
		$(tplInput).find('label').html((opts.label)?opts.label:name).data({'value':name});
		for (var i in value) {
			var tplBox = $('#tpl-input-box').clone(true).attr('id',null);
			var objInput = null;
			if ( tplType == 'select' ) {
				objInput = $('<select>');
				$(objInput).append($('<option>').val("").html(Glang.get('Select value')));
				for ( var gid in opts.adm_groups ) $(objInput).append($('<option>').val(gid).html(opts.adm_groups[gid]));
				$(objInput).val("");
			} else objInput = $('<input type="text" placeholder="'+Glang.get('Required value')+'">');
			$(objInput).addClass('transparent').attr('name','profiles['+id+']['+name+'][]').val(value[i]);
			$(tplInput).find('div.input-right').append($(tplBox).append(objInput));
		}
		testShowInputField( tplInput );
		if ( opts.buttons )
			for ( var btn in opts.buttons )
				$(tplInput).find('.iconbar').append( $('<button></button>').button({ icons: { primary: 'ui-icon-'+btn } }).click(opts.buttons[btn]));
		$(target).find('.input-grp').append(tplInput);
	};
	
	function updateTips( t ) {
		$('#tpl-dialog-add .err-msg').find('label').html( t )
		$('#tpl-dialog-add .err-msg').animate({ opacity: 1 }, 500);
		setTimeout(function() { $('#tpl-dialog-add .err-msg').animate({opacity: 0}, 1000 ); }, 3000 );
	};
	
	function addMessage( target, msg, type, prefix ) {
		if ( typeof msg === 'object' ) {
			for ( var i in msg ) addMessage( target, msg[i], type, ((!i.match(/^[0-9]+$/))||prefix)? ((prefix?prefix+': ':'')+((!i.match(/^[0-9]+$/))?i:'')) : undefined );
		} else {
			$(target).append(
				$('<div>').addClass('hline bmargin ui-state-'+type+' ui-corner-all').append(
					$('<label>').addClass('cell').html((prefix?prefix+': ':'')+msg+'.')));
		}
	};
	
	function checkInUse( o, n ) {
		if ( $('#radius-groups-'+(o.find('input').val().trim())).length > 0 ) {
			o.addClass( "ui-state-error" );
			updateTips( n );
			return false;
		}
		return true;
	};
	
	function checkLength( o, min, max, n ) {
		if ( o.find('input').val().trim().length > max || o.find('input').val().trim().length < min ) {
			o.addClass( "ui-state-error" );
			updateTips( n );
			return false;
		}
		return true;
	};
	
	function checkRegexp( o, expr, n ) {
		o.removeClass( "ui-state-error" );
		if ( !( expr.test( o.find('input').val().trim() ) ) ) {
			o.addClass( "ui-state-error" );
			updateTips( n );
			return false;
		}
		return true;
	};
	
	function checkInput( target, flags ) {
		var tplInput = $('#tpl-input').clone().attr('id',null);
		$(tplInput).find('label').html(name);
		$(tplInput).find('input').val(value).attr('name','profiles['+id+']['+name+']');
		$(target).find('.input-grp').append(tplInput);
	};
	
	function addInputField() {
		var tplInput = $(this).parents('div.hline:first').find('div.input-right');
		var newInput = $(tplInput).find('div.input-box:first').clone(true);
		$(newInput).find('button').next().val(null);
		$(tplInput).append(newInput);
		testShowInputField( tplInput );
		$('#radius-groups').accordion('refresh');
	};
	
	function remInputField() {
		var tplInput = $(this).parents('div.input-right:first');
		$(this).parent().remove();
		testShowInputField( tplInput );
		$('#radius-groups').accordion('refresh');
	};
	
	function testShowInputField( tplInput ) {
		var arrButtons = $(tplInput).find('.input-box button');
		if ( $(arrButtons).length > 1) $(arrButtons).parent().addClass('show');
		else $(arrButtons).parent().removeClass('show');
	};
	
	$('#tpl-input-box').find('button').button({ icons: { primary: 'ui-icon-minus' } }).click(remInputField);
	
	$.post( '/index.php?menuaction=expressoAdmin1_2.uiradius.config' )
	.done(function( response, message ) {
		
		$('#tpl-add-field').data({'schema':response.schema});
		
		if ( response && response.profiles ) {
			for ( var id in response.profiles ) {
				addRadiusProfile(id,response.profiles[id].description);
				for ( var field in response.profiles[id] )
					initAddField(id,field,response.profiles[id][field]);
			}
		}
		$('#radius-groups').accordion({ collapsible: true, active: false }).show(400).accordion('refresh');
		
	}).fail(function( response ) {
		
		var message = response? (( response.responseJSON && response.responseJSON.message)? response.responseJSON.message : response.statusText ) : Glang.get('unknown');
		alert( Glang.get('Error requesting page')+': '+message+' ('+response.status+')' );
		
	}).always(function() {
		
		$('form.ajax').submit(function( event ) {
			if ( $(this).data('inputHandlerEvent') == 'save' ) {
				
				$('#tpl-dialog-save').dialog( "open" );
				
				var data = $(this).serialize();
				$.post( '/index.php?menuaction=expressoAdmin1_2.uiradius.save', data )
				.done(function( response, message ) {
					
					$('#tpl-dialog-save').data({status:'success'});
					if ( response ) addMessage( $('#tpl-dialog-save'), response, 'highlight' );
					
				}).fail(function( response, h, x, y ) {
					
					$('#tpl-dialog-save').data({status:'error'});
					addMessage( $('#tpl-dialog-save'), response? (( response.responseJSON )? response.responseJSON : response.statusText ) : Glang.get('unknown'), 'error' ); 
					
				}).always(function() {
					var top = $('#tpl-dialog-save').parents('.ui-dialog');
					$(top).find('.loadgif').hide();
					$(top).find('.ui-dialog-buttonset button').prop('disabled', false).removeClass("ui-state-disabled");
				});
			} else if ( $(this).data('inputHandlerEvent') == 'cancel' ) {
				
				window.location = '/admin/index.php';
				
			}
			$(this).data({'inputHandlerEvent': 'none'});
			event.preventDefault();
		}).find('input[type=submit]').prop('disabled', false).button().click(function() {
			
			$('form.ajax').data({'inputHandlerEvent': this.name});
			
		});
		
		$('#tpl-dialog-save').dialog({
			width: 500,
			position: 'center',
			modal: true,
			autoOpen: false,
			draggable: false,
			resizable: false,
			closeOnEscape: false,
			open: function(event) {
				var top = $(this).parents('.ui-dialog');
				$(top).find('.ui-dialog-titlebar-close').hide();
				$(top).find('.loadgif').show();
				$(top).find('.ui-dialog-buttonset button').prop('disabled', true).addClass("ui-state-disabled");
			},
			close: function(event) {
				if ($(this).data().status == 'success' ) window.location = '/admin/index.php';
				else $(this).html('');
			},
			buttons: [
				{
					text: Glang.get('Close'),
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		}).parents('.ui-dialog').find('.ui-dialog-titlebar').append('<img class="loadgif" src="./prototype/plugins/messenger/images/ajax-loader.gif">');
		
		$('#tpl-dialog-add').dialog({
			autoOpen: false,
			width: 500,
			draggable: false,
			resizable: false,
			position: 'center',
			modal: true,
			buttons: [ 
				{
					text: Glang.get('Add'),
					click: function() {
						var bValid = true;
						$('#tpl-dialog-add input[type=text]').parents('.ui-state-error').removeClass( "ui-state-error" );
						bValid = bValid && checkLength( $('#input-profile-name'), 1, 32, Glang.get('The name length must be between 1 and 32 characters')+'.' );
						bValid = bValid && checkRegexp( $('#input-profile-name'), /^[a-zA-Z]([0-9a-zA-Z\-_])*$/, Glang.get('The name must begin with a letter, without spaces')+'.' );
						bValid = bValid && checkInUse( $('#input-profile-name'), Glang.get('This name is in use')+'.' );
						bValid = bValid && checkLength( $('#input-profile-desc'), 1, 1024, Glang.get('Description must be non empty')+'.' );
						bValid = bValid && checkRegexp( $('#input-profile-desc'), /^[\w\d ¿¡¬√ƒ«»… ÀÃÕŒœ“”‘’÷Ÿ⁄€‹‡·‚„‰ÁËÈÍÎÏÌÓÔÚÛÙıˆ˘˙˚¸]+$/i, Glang.get('Description has an invalid character')+'.' );
						if ( bValid ) {
							addRadiusProfile($('#input-profile-name').find('input').val().trim(),$('#input-profile-desc').find('input').val().trim());
							$('#radius-groups').accordion('refresh');
							$( this ).dialog( "close" );
						}
					}
				}
			],
			close: function() {
				$('#tpl-dialog-add input[type=text]').val( "" ).parents('.ui-state-error').removeClass( "ui-state-error" );
			}
		});
		
		$('input[name=addProfileButton]').click(function() {
			$('#tpl-dialog-add').dialog( "open" );
		}).prop('disabled', false).button();
		
		$('input[name=remProfileButton]').click(function() {
			$(this).parents('.ui-accordion-content').prev().remove();
			$(this).parents('.ui-accordion-content').remove();
		}).button();
		
		$('#radius-groups').parents('.ui-dialog:first').find('.loadgif').hide();
	});
	
});