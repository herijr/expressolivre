(function()
{
	var verifyMOTD		= null;
	var dialogMOTD 		= $("#expressoMOTD");
	var messageTitle	= "";
	var messageBody		= "";
	var date			= new Date();
	var nonCookies		= 0;

	function showMessage()
	{
		dialogMOTD.dialog(
		{
			modal 		: true,
			width 		: 370,
			height 		: 310,
			title 		: $("input[name=motd_title]").val(),
			position	: { my: "45% center" },
			resizable 	: false,
			buttons		: [
			       		   {
			       			   	text : get_lang("Close"),
			       			   	click : function()
			       			   	{
			       			   		$(this).dialog("destroy");
			       			   	}
			       		   }]				
		});

		dialogMOTD.next().css("background-color", "#E0EEEE");

		// Span Title:
		dialogMOTD.parent().find("span.ui-dialog-title").css("text-align","left");

		var objEJS = {	'message' : $("input[name=motd_body]").val(), 
						'type_message' : $("input[name=motd_type_message]").val(),
						'title' : get_lang($("input[name=motd_type_message]").val())
					 };

		dialogMOTD.html( new EJS( {url: 'templates/default/messagesOfToday.ejs'} ).render(objEJS));
	}

	function checkCookie()
	{
		if( verifyMOTD )
		{
			clearTimeout( verifyMOTD );
		}

		var cookie 		= ( $.cookie('messagesOfToday') ) ? $.cookie('messagesOfToday') : "";
		var nextMessage = new Date();
		var now			= null;
		var rangeMsg 	= ( $("input[name=motd_rangeMsg]").val() != null ) ? $("input[name=motd_rangeMsg]").val() : 60 ;	


		now = new Date();

		nextMessage.setTime( ( parseInt( now.getTime() ) ) + ( 1000*60*rangeMsg ) );

		if( cookie )
		{
			var vals = cookie.split(":");

			if( parseInt( vals[2] ) > 0 )
			{
				if( parseInt( now.getTime() ) >  parseInt( vals[1] ) )	
				{	
					date.setTime( vals[0] );

					$.cookie( 'messagesOfToday', date.getTime() + ":" + nextMessage.getTime() + ":" + --vals[2], { 'expires' : date.toUTCString(), 'path' : '/' });
					
					showMessage();
				}
			}
			else
			{	
				clearTimeout( verifyMOTD );
			}
		}
		else
		{
		
			//Expire 18:59
			date.setHours(18); date.setMinutes(59);

			$.cookie( 'messagesOfToday', now.getTime() + ":" + nextMessage.getTime() + ":" + $("input[name=motd_number_views]").val(), { 'expires' : date.toUTCString(), 'path' : '/' } );

			showMessage(); nonCookies++;
		}

		// Cookies esta habilitado ?
		if( nonCookies < 2 )
		{
			// Verifica o cookie a cada 15 segundos;
			verifyMOTD = setTimeout(function(){ checkCookie(); }, 15000);
		}
		else
		{
			// Limpa cookies
			clearTimeout( verifyMOTD );
		}
	}

	function messagesOfToday()
	{
		$(document).ready(function()
		{
			if( $("input[name=motd_enabled]").val() === "true" )
			{
				checkCookie();
			}
			else
			{
				$.cookie( 'messagesOfToday', null );
			}
		});
	}

	window.MOTD = new messagesOfToday;

})();