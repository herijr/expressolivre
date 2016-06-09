(function()
{
	var dragging	= null;
	var msgId		= null;

	function countMsg()
	{
		var listMsgs	= 0;
		var tableBox = ( currentTab ) ? $("#tbody_box_" + currentTab.replace('search_','') ) : $("#tbody_box");

		tableBox.find('td input[type=checkbox]').each(function()
		{
			if( $(this).is(":checked") )
			{
				var _idCheckBox = $(this).attr('id');

				if( _idCheckBox !== 'chk_box_select_all_messages' )
				{
					listMsgs++;
				}
			}
		});

		return listMsgs;
	}

	function makeDraggedAba()
	{
		if( arguments.length )
		{
			var element = $(arguments[0]);
			var id		= element.attr('id').replace("border_id_","").replace("_r","");
			var subject	= arguments[1];

			move_element( element, id, subject );
		}
	}

	function makeDraggedMsg()
	{	
		if( arguments.length )
		{
			var element		= arguments[0];
			var eventMouse	= arguments[1];

			if( eventMouse.button === 2  || eventMouse.button === 3 )
			{ 
				var _id = $(element).attr('id');
				
				if( !$("#check_box_message_" + _id ).is(":checked") )
				{
					$("#chk_box_select_all_messages").attr("checked", false);
					$('input[id^=check_box_message_]:checked').each(function(){
						$(this)
							.attr("checked", false)
							.parents('tr')
							.first()
							.removeClass('selected_msg');
					})
					
					$("#check_box_message_" + _id )
						.attr("checked", true)
						.parents('tr')
						.first()
						.addClass('selected_msg');
				}
				
				ConstructRightMenu(eventMouse);
			}
		    else
		    {
				if( countMsg() > 1 )
				{
					move_list( countMsg() );
				}	
				else
				{
					var id		= $(element).attr('id');
					var subject = ( $(element).attr('name') ) ? $(element).find('td:nth-child(8)').html() : $(element).find('span._class_gamb_').html();

					move_element( element , id, subject );
				}
			}
		}
	}

	function move_list( listMsgs )
	{
		if( listMsgs > 0 )
		{
			$(document).on("mousemove", function(e)
			{
			    var colorFont = "red";

			    if( $(e.target).attr('id') != undefined )
			    {
			    	if( ($(e.target).attr('id')).indexOf("tree_folders") > -1 )
			    	{
			    		colorFont = "green";
			    	}
			    }

			    if( dragging )
			    {
			    	var message = "Vc esta movendo <span style='font-size:11pt;color:#000;'>" + listMsgs + "</span> mensagens.";

					$("#div_sel_messages").css({"display":"block","left": ( dragging.left + 10 ), "top": ( dragging.top + 10 ) });		
					$("#div_sel_messages").css({"color": colorFont ,'font-weight':'bold'});
					$("#div_sel_messages").html("<img src='templates/default/images/envelope.png' align='middle'> : " + message );
				}
			});
		}
	}

	function move_element( element, id , subject )
	{
		if( $(element).length > 0 )
		{	
			$(document).on("mousemove", element, function(e)
			{
			    var colorFont = "red";

			    if( $(e.target).attr('id') != undefined )
			    {
			    	if( ($(e.target).attr('id')).indexOf("tree_folders") > -1 )
			    	{
			    		colorFont = "green";
			    	}
			    }

			    if( dragging )
			    {
					$("#div_sel_messages").css({"display":"block","left": ( dragging.left + 10 ), "top": ( dragging.top + 10 ) });		
					$("#div_sel_messages").css({"color": colorFont ,'font-weight':'bold'});
					$("#div_sel_messages").html("<img src='templates/default/images/envelope.png' align='middle'> : " + subject );
				}

				msgId = id;
			});
		}	
	}

	function move_msgs( target, id_msg )
	{
		var reg 			= /^((n|l)(?!root))(.*)tree_folders$/;
		var new_folder 		= "";
		var new_folder_name = "";

		if( reg.test($(target).parent().attr('id') ) )
		{
			new_folder 		= $(target).parent().attr('id').substring(1,$(target).parent().attr('id').length).replace('tree_folders','');			
			new_folder_name = new_folder.replace("INBOX"+cyrus_delimiter, "");
			
			if( new_folder_name === 'INBOX' )
			{
				new_folder_name = get_lang("Inbox");
			}

			if( countMsg() > 1 )
				proxy_mensagens.proxy_move_messages("null", 'selected', 0, new_folder, new_folder_name);
			else
				proxy_mensagens.proxy_move_messages( "null", id_msg, id_msg + "_r", new_folder, new_folder_name );
		}

	}

	function setMoveDragged( value )
	{
		dragging = value;
	}	

	function DragArea()
	{
		$("#div_sel_messages").css({
			'border'	: '1px solid black',
			'zIndex' 	: '15',
			'position'	: 'absolute',
			'background': '#EEEEEE',
			'opacity'	: ( 7.5 / 10 ),
			'filter'	: 'alpha(opacity='+( 7.5 * 10 )+')',
			'padding'	: '3px',
			'width'		: 'auto',
			'height'	: '18px'
		});

        $(document).on("mousemove",function(e)
        {
            if( dragging )
            {
                dragging = { 'top' :  e.pageY , 'left' : e.pageX };                        
            }
        });

        $(document).on("mousedown", function(e)
        {
        	if( dragging == null )
        	{
				var isDrag = false;

				if( $(e.target).parent('div').parent('td').length > 0 )
				{
					// Aba
					if( $(e.target).parent('div').parent('td').parent('tr').parent('tbody[id=border_tbody]').length > 0 )
					{	
						isDrag = true;
					}
				}	

				if( $(e.target).parent('td').length > 0 )
				{
					// Msg
					if( $(e.target).parent('td').parent('tr').parent('tbody[id^=tbody_box]').length > 0 )	
					{
						isDrag = true;
					}

					// Aba
					if( $(e.target).parent('td').parent('tr').parent('tbody[id=border_tbody]').length > 0 )	
					{
						isDrag = true;
					}
				}	

				if( $(e.target).parent('tr').length > 0 )
				{		
					// Msg
					if( $(e.target).parent('tr').parent('tbody[id^=tbody_box]').length > 0 )
					{
						isDrag = true;
					}
					
					// Aba
					if( $(e.target).parent('tr').parent('tbody[id=border_tbody]').length > 0 )
					{
						isDrag = true;
					}
				}

				if( isDrag )
				{
					dragging = { 'top' :  e.pageY , 'left' : e.pageX };
				}
	        }
        });

        $(document).on("mouseup", function(e)
        {
            if( dragging != null )
            {
	            if($("#div_sel_messages").length > 0 )
	            {
					$("#div_sel_messages").css({"display":"none"});
					$("#div_sel_messages").html('');
				}
				
				move_msgs( $(e.target), msgId );

				dragging = null;
				msgId	 = "";
			}
        });
	}

	DragArea.prototype.makeDraggedAba	= makeDraggedAba;
	DragArea.prototype.makeDraggedMsg 	= makeDraggedMsg;
	DragArea.prototype.setMoveDragged	= setMoveDragged;

	window._dragArea = new DragArea;

})();