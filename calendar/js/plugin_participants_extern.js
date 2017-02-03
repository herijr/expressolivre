(function($){

	$.PluginParticipantExtern = 
	{
		init : function( options )
		{
			var contacts	= options.contacts;
			var element		= options.elements;
			$(element).css("width", "99%").attr("wrap", "soft");
			$(element).on("focus", function(){ clearTimeout(parseInt(setTimeOutLayer)); search_contacts('onfocus', $(this).attr('id')); });
			$(element).on("blur", function(){ setTimeOutLayer=setTimeout('search_contacts("lostfocus","'+$(this).attr('id')+'")',100); });
			$(element).keyup( function(event){ $.PluginParticipantExtern.keypress(event, $(this) ); });
		},

		keypress : function( event, element )
		{
			var codeKey = ( $.browser.msie ) ? event.which : event.keyCode;

			if( codeKey == 120 )
			{
				$.PluginParticipantExtern.quickSearch( $(element) );
			}
			else
			{
				if( codeKey == 27 )
				{
					Tooltip.hide();
				}
				else
				{
					search_contacts( codeKey, $(element).attr('id') );

					if( codeKey == 13  )
					{
						$(element).val( $.trim( $(element).val() ) );
					}
				}
			}
		},

		showWindow : function( data ,searchFor )
		{
			Tooltip.hide();

			var _window = $("#participantsExterns");

			if( !$.browser.msie )
			{	
				_window.dialog({
	                modal       : true,
	                width       : 690,
	                height      : 450,
	                title       : "Resultados do Catalogo Geral",
	                position	: "center top",
	                resizable   : false,
	                buttons     : [
	                               {
	                                    text  : "Adicionar",
	                                    click : function()
	                                    {
	                                    	$.PluginParticipantExtern.transferResult($(this), searchFor );
	                                        $(this).dialog("destroy");
	                                    }
	                               },
	                               {
	                                    text  : "Fechar",
	                                    click : function()
	                                    {
	                                        $(this).dialog("destroy");
	                                    }
	                               }]               
				});
				
				_window.next().css("background-color", "#E0EEEE");
				_window.html( new EJS( {url: './calendar/templates/default/calendarContacts.ejs'} ).render( { 'obj' : data } ) )
			}
			else
			{
				_window.css({
					"padding"			:	"5px",
					"width"				:	"650px",
					"height"			: 	"400px",
					"text-align"		:	"center",
					"background-color"	: 	"#FFFFFF",
					"position"			: 	"absolute",
					"display"			: 	"none",
					"left"				: 	"200px",
					"top"				: 	"45%",
					"border"			: 	"1px solid #cecece"
				});

				_window.show("fast");
				_window.html( new EJS( {url: './calendar/templates/default/calendarContactsIE.ejs'} ).render( { 'obj' : data } ) );
				
				// Add Button
				_window.find("button.add-button").button()
				.html("Adicionar").css({ 'border-radius': '0px', 'padding' : '5px', 'margin-right': '10px;'})
				.on('click', function(){ $.PluginParticipantExtern.transferResult( _window, searchFor ); });
				
				// Close Button
				_window.find("button.close-button").button()
				.html("Fechar").css({ 'border-radius': '0px', 'padding' : '5px'})
				.on('click',function(){ _window.hide( 1000 ); });						
			}	
		},

		transferResult: function(element, searchFor)
		{
            $(element).find("select option:selected").each(function(){
            	
            	$("#ex_participants").val( $("#ex_participants").val() + "," + $(this).val()+"," );
            });

			var string = $("#ex_participants").val();
		
			$("#ex_participants").val( string.replace( searchFor + "," , "") );

			$("#ex_participants").focus();
		},

		quickSearch: function(element)
		{
			$(element).focus();

			var contacts 	= (( $(element).val().indexOf(",") > -1 ) ? $(element).val().split(",") : [ $(element).val() ]);
			var searchFor 	= $.trim( contacts[contacts.length-1] );
			var field		= $(element).attr('id');

			if( searchFor.length > 4 )
			{	
				$.ajax(
				{
					type 			: "POST",
					dataType		: "json",
				    accepts			: { json: 'application/json' },
					url 			: "expressoMail1_2/controller.php?action=expressoMail1_2.ldap_functions.quickSearch",
					data			: 
					{
	  		   			search_for	: searchFor,
	  		   			field	 	: field
	  				},
	  				success: function(response)
	  				{
	  					var data = response;
	  					
	  					$.PluginParticipantExtern.showWindow( data, searchFor );
	  				}
	  			})
	  			.done(function()
	  			{
	  				$("#participantsExterns").find("select option:first-child").attr("selected",true);
	  			});
	  		}
	  		else
	  		{	
	  			if( !$.browser.msie)
	  			{
		  			$.Zebra_Dialog( $("input[type=hidden][name=lang_your_search_argument_must_be_longer_than_4_characters]").val() , 
					{
						'type'				: 'warning',
						'modal'				: true,
						'position'			: ['center', 'top + 250']
					});	  			
				}
				else
				{
					alert($("input[type=hidden][name=lang_your_search_argument_must_be_longer_than_4_characters]").val());
				}
	  		}
		}
	}

 })(jQuery);