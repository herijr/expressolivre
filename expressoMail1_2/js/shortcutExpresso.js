$(document).on('keydown.shorcut', function( event ){

    switch( event.keyCode ){
        case 13: shortcutExpresso.buttonEnter(); break;
        case 27: shortcutExpresso.buttonEsc(); break;
        case 38: 
            if( event.shiftKey ){ shortcutExpresso.buttonShift('up'); }
            if( event.ctrlKey ){ shortcutExpresso.buttonCtrl('up'); }
            if( !event.shiftKey && !event.ctrlKey ){ shortcutExpresso.selectMsg( false, 'up' ); }
            break;
        case 40:
            if( event.shiftKey ){ shortcutExpresso.buttonShift('down'); }
            if( event.ctrlKey ){ shortcutExpresso.buttonCtrl('down'); }
            if( !event.shiftKey && !event.ctrlKey ){ shortcutExpresso.selectMsg( false, 'down' ); }
            break;
        case 46: shortcutExpresso.buttonDel(); break;
        case 69: shortcutExpresso.buttonE(event); break;
        case 73: shortcutExpresso.buttonI(); break;
        case 78: shortcutExpresso.buttonN(event); break;
        case 79: shortcutExpresso.buttonO(); break;
        case 82: shortcutExpresso.buttonR(event); break;
        case 120: shortcutExpresso.buttonF9(); break;
    }
});

var Shortcut = new function() {
    var _focus_index = 1;
    this.focus_index = function( value ) {
        if ( value == undefined ) return _focus_index;
        var int = parseInt(String(value))
        _focus_index = (isNaN(int))? 1 : int;
        return this;
    };
}

var shortcutExpresso = new function(){

    var shiftLast = null;

    this.buttonCtrl = function( action ){

        if( $('tr.tr_message_header').length > 0 ){

            let imgDisable = new RegExp('gray.button');

            let btnImg = ( action === "up" ) ? 
                    $('tr.tr_message_header').find('img[id^="msg_opt_previous_"]') : 
                        $('tr.tr_message_header').find('img[id^="msg_opt_next_"]');
            
            if( !imgDisable.test( $(btnImg).attr('src') ) ){
                
                $(btnImg).click();

                setTimeout(() => {

                    let ID = $("input[id^='msg_number_']").attr('id').replace("msg_number_","").replace("_r","");

                    shiftLast = $("#" + ID);

                    $("#tbody_box").find(".selected_shortcut_msg").each(function(){
                        $(this).removeClass("selected_shortcut_msg");
                    });

                    $(shiftLast).addClass("selected_shortcut_msg");
                    
                }, 600 );
            }
        }
    };

    this.buttonDel = function(){
        
        let selectedMsgs = [];

        $("#tbody_box").find(".selected_shortcut_msg").each(function(){
            selectedMsgs[selectedMsgs.length]= $(this).attr('id');
        });

        let firstElement = ( $("#"+selectedMsgs[0]).prev().length ? $("#"+selectedMsgs[0]).prev() : false );

        shiftLast = firstElement ? $(firstElement) : $(shiftLast);

        //Delete Msgs
        proxy_mensagens.delete_msgs(current_folder, selectedMsgs.join(','), 'null');
        
        setTimeout(() => {
            if( !$(shiftLast).length ){ shiftLast = $("#tbody_box tr").first(); }

            add_className( shiftLast[0], "selected_shortcut_msg" );
                
        }, 300);
    };

    this.buttonEnter = function(){
        if( $('#border_id_0').hasClass('menu-sel') ){
            $('#tbody_box')
                .find('.selected_shortcut_msg td[id^="td_who_"]')
                .first()
                .click();
        }
    };

    this.buttonE = function(ev){

        let msgId = this.getMsgId();

        if ( msgId ){
            
            $( "#msg_opt_forward_" + msgId ).click();
            
            if( !ev.shiftKey || !ev.ctrlKey ){ ev.stopPropagation(); ev.preventDefault(); }
        }
    };

    this.buttonI = function(){
        print_all();
    };

    this.buttonEsc = function(){
      
        var windowClosed = false;
      
        for( var w in arrayJSWin )
        {
            if( arrayJSWin[w].visible ) { arrayJSWin[w].close(); windowClosed = true; }
        }
      
        if ( !windowClosed ){ delete_border( this.getMsgId(), 'false'); }
    };

    this.buttonN = function(ev){

        new_message("new","null");
        
        if( !ev.shiftKey || !ev.ctrlKey ){ ev.stopPropagation(); ev.preventDefault(); }
    };

    this.buttonR = function(ev){

        let msgId = this.getMsgId();

        if ( msgId ){
            
            $( "#msg_opt_reply_" + msgId ).click();
            
            if( !ev.shiftKey || !ev.ctrlKey ){ ev.stopPropagation(); ev.preventDefault(); }
        }
    }

    this.buttonF9 = function(){
        $("#em_refresh_button").click();
    };

    this.buttonShift = function( button ){

        shiftLast = ( shiftLast == null ) ? $("#tbody_box").find(".selected_shortcut_msg") : shiftLast;

        if( shiftLast.hasClass('selected_shortcut_msg') ){
            if( button == 'up' ){
                shiftLast = ( shiftLast.prev().length > 0 ) ? shiftLast.prev(): shiftLast;
                add_className( shiftLast[0] , "selected_shortcut_msg" );
            } else {
                if( $("#tbody_box").find(".selected_shortcut_msg").length > 1 ){
                    remove_className( shiftLast[0], "selected_shortcut_msg" );
                    shiftLast = shiftLast.next();
                } else {
                    add_className( shiftLast.next()[0] , "selected_shortcut_msg" );
                    shiftLast = shiftLast.next().next();
                }
            }
        } else {
            if( button == 'up') {
                shiftLast = shiftLast.prev();
                if( shiftLast.hasClass('selected_shortcut_msg') ){
                    if( $("#tbody_box").find(".selected_shortcut_msg").length > 1 ){
                        remove_className( shiftLast[0], "selected_shortcut_msg" );
                    } else {
                        shiftLast = shiftLast.prev();
                        add_className( shiftLast[0] , "selected_shortcut_msg" );
                    }
                } else {
                    if( $("#tbody_box").find(".selected_shortcut_msg").length > 1 ){
                        shiftLast = $("#tbody_box").find(".selected_shortcut_msg");
                        add_className( shiftLast[0] , "selected_shortcut_msg" );
                    }
                }
            } else {
                add_className( shiftLast[0] , "selected_shortcut_msg" );
                shiftLast = shiftLast.next();
            }
        }
    };

    this.getMsgId = function(){

        let msgId = '';

        $('#border_tr').find('td').each( function(){
            if( $(this).hasClass('menu-sel') && $(this).attr('id') !== 'border_id_0' ){
                msgId = $(this).attr('id').replace( 'border_id_','' );
            }
        });

        return $.trim(msgId) !== "" ?  msgId : false ;
    };

    this.selectMsg = function( msg_number, keyboard_action, force_msg_selection ){

        if( $('#border_id_0').hasClass('menu-sel') || force_msg_selection ){

            let childSelect = $("#tbody_box").find(".selected_shortcut_msg");

            if( msg_number ){
                
                remove_className( childSelect[0], 'selected_shortcut_msg' );
                
                add_className( $("#tbody_box").find("#"+msg_number)[0] , 'selected_shortcut_msg' );

            } else { 

                if( $("#tbody_box").find(".selected_shortcut_msg").length > 1 ){

                    childSelect = ( shiftLast != null ) ? shiftLast : childSelect ;

                    $("#tbody_box").find(".selected_shortcut_msg").each(function(){
                        remove_className( $(this)[0], 'selected_shortcut_msg' );
                    });

                } else {
                    remove_className( childSelect[0], 'selected_shortcut_msg' );
                }

                if( keyboard_action === 'down' ){
                    if( childSelect.next().length > 0 ){
                        add_className( childSelect.next()[0] , 'selected_shortcut_msg' );                                
                        shiftLast = childSelect.next();
                    } else {
                        add_className( childSelect[0] , 'selected_shortcut_msg' );                                
                        shiftLast = childSelect;
                    }
                } else {

                    if( childSelect.prev().length > 0 ){
                        add_className( childSelect.prev()[0] , 'selected_shortcut_msg' );
                        shiftLast = childSelect.prev();
                    } else {
                        add_className( childSelect[0] , 'selected_shortcut_msg' );
                        shiftLast = childSelect;
                    }
                }
                
                if ($('.selected_shortcut_msg').length > 0) {
                    let scroller = $('#divScrollMain_0');
                    let sel = $('.selected_shortcut_msg').first();
                    let linf = scroller.position().top;
                    let pinf = sel.position().top;
                    if ( pinf < linf ) scroller.scrollTop( scroller.scrollTop() - linf + pinf );
                    else {
                        let lsup = linf + scroller.height();
                        let psup = pinf + sel.innerHeight();
                        if ( psup > lsup ) scroller.scrollTop( scroller.scrollTop() + psup - lsup );
                    }
                }
            }
        }
    };

    this.buttonO = function()
    {
        let msgId = this.getMsgId();

        if( msgId ){
            if( $('#option_hide_more_' + msgId ).length > 0 ){
                $('#option_hide_more_' + msgId ).click();
            }    
        }
    };
}
