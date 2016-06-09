(function()
{
	var alertMsg		= false;
	var wfolders_tree	= null;

	function actionClick( type, border_id )
	{
		switch( type )
		{
			case 'save' :
				
				save_as_msg( border_id, wfolders_tree._selected.id, wfolders_tree._selected.caption,true );
				break;
			
			case 'send_and_file' : 

				send_message( border_id, wfolders_tree._selected.id, wfolders_tree._selected.caption);
				alertMsg = true;

				break;
			
			case 'move_to':
				
				var msg_number =  (border_id ? border_id.replace('_r','') : 'selected');

				if( border_id.match('search') )
				{
					move_search_msgs(border_id, wfolders_tree._selected.id, wfolders_tree._selected.caption);	
				}
				else
				{
					proxy_mensagens.proxy_move_messages('null',msg_number, border_id, wfolders_tree._selected.id, wfolders_tree._selected.caption);
					alertMsg = true;
				}

				break;
			
			case 'change_folder' :
				
				change_folder(wfolders_tree._selected.id, wfolders_tree._selected.caption);
				alertMsg = true;
				break;	
			
			case'import':
				
				import_msgs(wfolders_tree);
				break;
		}
	}

	function showDFTree( from_search )
	{
		wfolders_tree = new dFTree({name: 'wfolders_tree'});

		if( !expresso_offline )
			var n_root = new dNode({id:'root', caption:get_lang("My Folders")});
		else
			var n_root = new dNode({id:'local_root', caption:get_lang("local messages")});
		
		//Places the root; second argument can be anything.
		wfolders_tree.add(n_root,'anything'); 

		var folders = tree_folders.getNodesList(cyrus_delimiter);
		
		for ( var i = 1 ; i < folders.length ; i++ )
		{
			if ( proxy_mensagens.is_local_folder(folders[i].id ) && from_search )
			{
				continue;
			}
			var nn = new dNode(
			{
				'id'		: folders[i].id,
				'caption'	: lang_folder(folders[i].caption),
				'plusSign'	: folders[i].plusSign
			});

			wfolders_tree.add(nn, folders[i].parent);
			
		}
		
		//As buscas não podem incluir mover entre pastas locais, pelo menos por enquanto
		$("#wfolders_content_tree").html('');
		
		wfolders_tree.draw( document.getElementById("wfolders_content_tree") );

		n_root.changeState();

		if( !expresso_offline )
		{
			wfolders_tree.getNodeById('INBOX')._select();
		}
		else
		{
			wfolders_tree.getNodeById('local_Inbox')._select();
		}
	}

	function makeWindow()
	{
		var border_id	= arguments[0];
		var from_search	= arguments[2];
		var type		= arguments[1];
		var text_button = "";
		
		switch( type )
		{
			case 'save' :
				textButton = get_lang('Save');
				break;
			case 'send_and_file' :
				textButton = get_lang('Send and file'); 
				break;
			case 'move_to' :
				textButton = get_lang('Move');
				break;
			case 'change_folder' :
				textButton = get_lang('Change folder');	
				break
			default :
				textButton = get_lang(type);		
		}

		var dialogFolders = $("#expressoFolders");

		dialogFolders.dialog(
		{
			modal 		: true,
			width 		: 275,
			height 		: 250,
			title 		: get_lang('Select a folder')+":",
			position	: { my: "45% center" },
			resizable 	: true,
			buttons		: [
			       		    {
			       			   	text : textButton,
			       			   	click : function()
			       			   	{
		       			   			$(this).dialog("destroy");
		       			   			actionClick( type, border_id );
			       			   	}
			       			},
			       		   {
			       			   	text : get_lang("Close"),
			       			   	click : function()
			       			   	{
			       			   		$(this).dialog("destroy");
			       			   	}
			       		   }]				
		});

		dialogFolders.next().css("background-color", "#E0EEEE");

		dialogFolders.html( new EJS( {url: 'templates/default/expressoFolders.ejs'} ).render());

		showDFTree( from_search );
	}

	function getAlertMsg()
	{
		return alertMsg;
	}

	function setAlertMsg( value )
	{
		alertMsg = value;
	}

	function folders(){ }

	folders.prototype.getAlertMsg	= getAlertMsg;
	folders.prototype.setAlertMsg 	= setAlertMsg;
	folders.prototype.makeWindow 	= makeWindow;

	window.wfolders = new folders;

})();