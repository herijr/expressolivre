
function ConstructMenuTools()
{
	var _option1 = preferences.hide_folders == "1" ? false : true;
								
	if( !expresso_offline )
	{
    	var menuToolsItems = {
    		"i01": {"name": get_lang("Preferences"), "icon": "mail-preferences", callback: preferences_mail },
    		"i02": {"name": get_lang("Search"), "icon": "mail-search", callback: function(key, opt){ search_emails(""); }},
    		"i03": {"name": get_lang("Edit filters"), "icon": "mail-filters", callback: filterbox },
    		"i04": {"name": get_lang("Edit folders"), "icon": "mail-editfolders", callback: folderbox },
    		"i05": {"name": get_lang("Share mailbox"), "icon": "mail-usersfolders", callback: sharebox },
    		"i06": {"name": get_lang("Empty trash"), "icon": "mail-trash", callback: function(key, opt){ clean_folder( 'trash' ); }}
    	};

    	if( preferences.use_local_messages == 1 )
    	{
    		if(expresso_local_messages.is_offline_installed())
    		{
    			menuToolsItems["i07"] = { "name": get_lang("Send from queue"), "icon": "queue", callback: force_check_queue };
    		}
    	}
    }
    else
    {
    	var menuToolsItems = { "i01": {"name": get_lang("Search"), "icon": "mail-search", callback: function(key, opt){ search_emails("");}}};
    }

    $.contextMenu({
    	selector	: "#link_tools",
    	trigger		: "hover",
    	className	: 'context-menu-tools',
		position	: function($menu, x, y)
    	{
			$menu.$menu.position({ my: "center top", at: "center bottom", of: this, offset:"0 0"});
    	},
    	determinePosition: function($menu, x, y)
    	{
			$menu.css('display', 'block').position({ my: "center top", at: "center bottom", of: this}).css('display', 'none');
    	},
    	delay:500,
    	autoHide:true,
    	events:
    	{
    		show: function(opt) {
    			var $trigger = $(opt.selector).css({'background-color': '#ffffff', 'border': '1px solid #CCCCCC'});
    			$('.context-menu-tools.context-menu-list.context-menu-root').css({'width': '150px' });
    			$('.context-menu-tools.context-menu-list').css({'background': '#ffffff', 'list-style': 'none', 'list-style-image':'none' })
    			.find(".context-menu-item").css({'background-color': '#ffffff'}).hover(
    				function(){
    					$(this).css({'background-color': '#CCCCCC'});
    				}, 
    				function(){
    					$(this).css({'background-color': '#ffffff'});
    				}
    			);
    			return true;
    		},
    		hide: function(opt) {
    			$(opt.selector).css({'background-color': '', 'border': 'none'});
    			return true;
    		}
    	},
    	items: menuToolsItems
    });
}

function openListUsers( border_id )
{
	connector.loadScript("QuickCatalogSearch");

	if ( typeof(QuickCatalogSearch) == 'undefined' )
	{
		setTimeout('openListUsers('+border_id+')',500);
		return false;
	}
	
	QuickCatalogSearch.showCatalogList(border_id);
}

function ConstructRightMenu( _event )
{
    var target = $( _event.currentTarget );

    if( $(target).attr('id') != undefined )
    {
	    var menuRightItems = {
			"j01"	: {"name": get_lang("Mark as")+"&nbsp;"+get_lang('seen') },
			"j02"	: {"name": get_lang("Mark as")+"&nbsp;"+get_lang('unseen') },
			"j03"	: {"name": get_lang("Mark as")+"&nbsp;"+get_lang('important') },
			"j04"	: {"name": get_lang("Mark as")+"&nbsp;"+get_lang('normal') },
			"sep1"	: "---------",
			"j05"	: {"name": get_lang("Move to")+" ..." },
			"j06"	: {"name": get_lang("Delete") },
			"j07"	: {"name": get_lang("Export") }
		};

	    $.contextMenu({
			selector	: "#"+$(target).attr('id'),
	    	className	: 'context-menu-tools',
	    	autoHide	: true,	    	
	    	callback	: function( key )
	    	{
	    		switch( key )
	    		{
	    			case "j01" : 
						proxy_mensagens.proxy_set_messages_flag('seen','get_selected_messages');
	    				break;

	    			case "j02" : 
						proxy_mensagens.proxy_set_messages_flag('unseen','get_selected_messages');
	    				break;

	    			case "j03" : 
						proxy_mensagens.proxy_set_messages_flag('flagged','get_selected_messages');
	    				break;

	    			case "j04" : 
						proxy_mensagens.proxy_set_messages_flag('unflagged','get_selected_messages');
	    				break;

	    			case "j05" : 
	    				wfolders.makeWindow('', 'move_to');
	    				break;

	    			case "j06" : 
	    				proxy_mensagens.delete_msgs('null','selected','null');
	    				break;

	    			case "j07" : 
	    				proxy_mensagens.export_all_messages();
		    			break;
	    		}
	    	},	    	
	    	events		:
	    	{
	    		show: function( opt )
	    		{
	    			$('.context-menu-tools.context-menu-list.context-menu-root').css({'width': '170px' });
	    			$('.context-menu-tools.context-menu-list').css({'list-style': 'none', 'list-style-image':'none' });
	    			_dragArea.setMoveDragged( null );
	    		},
	    		hide: function( opt )
	    		{
    				$(opt.selector).css({'background-color': '', 'border': ''});
    				_dragArea.setMoveDragged( null );
	    		}
	    	},
	    	items: menuRightItems
	    });

	}
}