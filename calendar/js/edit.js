/******************************************************************************\
|**************************   MODULO   AGENDA   *******************************|
|********** SCRIPT REFERENTE A CAIXA DE INCLUSAO DE PARTICIPANTES  ************|
\******************************************************************************/

// Variaveis Locais 
	var select_in  	= document.getElementById('user_list');
	var select_out  = document.getElementById('user_list_in');	
	var users_out 	= select_out ? select_out.cloneNode(true) : '';
	var finderTimeout = '';
																		
// Funcoes				
	function showExParticipants(el,path){
		document.getElementById('tbl_ext_participants').style.display='';
		el.style.display='none';
	}
	
	function hideExParticipants(el,path){
		document.getElementById('a_ext_participants').style.display = '';
		document.getElementById('tbl_ext_participants').style.display ='none';
	}
							
	function optionFinderTimeout(obj){

		clearTimeout(finderTimeout);	
		var oWait = document.getElementById("wait");
		oWait.innerHTML = 'Buscando...';
		finderTimeout = setTimeout("optionFinder('"+obj.id+"')",500);
	}
	function optionFinder(id) {	
		var oWait = document.getElementById("wait");
		var oText = document.getElementById(id);
		for(var i = 0;i < select_out.options.length; i++)				
			select_out.options[i--] = null;
																							
		for(i = 0; i < users_out.length; i++){																							
			if(users_out[i].text.substring(0 ,oText.value.length).toUpperCase() == oText.value.toUpperCase()) {
				sel = select_out.options;						
				option = new Option(users_out[i].text,users_out[i].value);				
				sel[sel.length] = option;
			}
		}
		oWait.innerHTML = '&nbsp;';
	}			
									
	function rem()
	{
		for(var i = 0;i < select_in.options.length; i++)				
			if(select_in.options[i].selected)
				select_in.options[i--] = null;																	
	}
	
	function show_disponibility()
	{
		var participants = "";

		if( $("#user_list").children().length > 0 )
		{
			$("#user_list option").each(function()
			{
				participants += $(this).val() + ",";
			});

			$.ajax(
			{
				url			: "./index.php?menuaction=calendar.uicalendar.disponibility",
				type		: "POST",
				data		: {"participants" : participants , "date" : $("input[id^=start]").eq(0).val() },
				dataType	: "json",
				success: function(data)
				{
					var winAvailability = $("#availability");

				  	if( !$.browser.msie )
				  	{	
						winAvailability.dialog(
						{
							modal 		: true,
							width 		: 650,
							height 		: 390,
							title 		: $("input[type=hidden][name=lang_disponibility_map]").val(),
							resizable 	: false,
							buttons		: [
							       		   {
							       			   	text : "Fechar",
							       			   	click : function()
							       			   	{
							       			   		$(this).dialog("destroy");

													begin_hour.splice(0,2);

													end_hour.splice(0,2);
							       			   	}
							       		   }]				
						});

						// Background Color	
						winAvailability.next().css("background-color", "#E0EEEE");

						// Span Title:
						winAvailability.parent().find("span.ui-dialog-title").css("text-align","left");
						
						winAvailability.html( new EJS( {url: './calendar/templates/default/availabilityMap.ejs'} ).render( { 'obj' : data } ) );
					}
					else
					{
						winAvailability.css({
							"padding"			:	"5px",
							"width"				:	"650px",
							"height"			: 	"390px",
							"text-align"		:	"center",
							"background-color"	: 	"#FFFFFF",
							"position"			: 	"absolute",
							"display"			: 	"none",
							"left"				: 	"200px",
							"top"				: 	"45%",
							"border"			: 	"1px solid #cecece"
						});

						winAvailability.show("fast");
						winAvailability.html( new EJS( {url: './calendar/templates/default/availabilityMapIE.ejs'} ).render( { 'obj' : data } ) );
						winAvailability.find("button").button();
						winAvailability.find("button").html("Fechar");
						winAvailability.find("button").click(function(){ winAvailability.hide( 1000 ); });						
					}
				}
			});
		}
		else
		{
			alert($("input[type=hidden][name=lang_event_without_participants]").val() );
		}
	}						

	function submitValues(alert_msg){
		var typeField = document.getElementById('cal[type]');
		if (typeField && typeField.value == 'hourAppointment')
			if(document.getElementsByName('categories[]')[0].value == ""){
				alert(alert_msg);
				return false;
				}
		for(i = 0; i < select_in.length; i++)
		 	select_in.options[i].selected = true;
	}
	
	function openListUsers(newWidth,newHeight, owner){					
		newScreenX  = screen.width - newWidth;		
		newScreenY  = 0;		
		window.open('calendar/templates/classic/listUsers.php?owner='+owner,"","width="+newWidth+",height="+newHeight+",screenX="+newScreenX+",left="+newScreenX+",screenY="+newScreenY+",top="+newScreenY+",toolbar=no,scrollbars=yes,resizable=no");
	}

 	function adicionaListaCalendar() 
 	{
		var select = window.document.getElementById('user_list_in');
		var selectOpener = window.opener.document.getElementById('user_list');
		for (i = 0 ; i < select.length ; i++) {				

			if (select.options[i].selected) {
				isSelected = false;

				for(var j = 0;j < selectOpener.options.length; j++) {																			
					if(selectOpener.options[j].value == select.options[i].value){
						isSelected = true;						
						break;	
					}
				}

				if(!isSelected){

					option = window.opener.document.createElement('option');
					option.value =select.options[i].value;
					option.text = select.options[i].text;
					selectOpener.options[selectOpener.options.length] = option;	
				}
				
			}
		}
		selectOpener.options[selectOpener.options.length-1].selected = true;
 	}
	
// Fim        

$(document).ready(function() {
	$('#usuarioParticipa').change(function(){
		var checked = $('#usuarioParticipa').is(':checked');
		$("input[name=sms_check_owner]")
			.prop('disabled',!checked)
			.parents('tr')
			.first()
			.css('color',checked?'':'#999999');
	}).change();
});
