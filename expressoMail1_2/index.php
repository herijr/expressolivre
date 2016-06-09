<?php
	
	$GLOBALS['phpgw_info']['flags'] = array(
		'noheader' => False,
		'nonavbar' => False,
		'currentapp' => 'expressoMail1_2',
		'enable_nextmatchs_class' => True
	);
	
	require_once('../header.inc.php');
	$update_version = $GLOBALS['phpgw_info']['apps']['expressoMail1_2']['version'];
	$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];
	$GLOBALS['phpgw']->css->validate_file('expressoMail1_2/templates/' . $GLOBALS['phpgw_info']['server']['template_set'] . '/main.css');
	$GLOBALS['phpgw']->css->validate_file('phpgwapi/js/dftree/dftree.css');
	echo $GLOBALS['phpgw']->css->get_css();
	echo "<script type='text/javascript'>var template = '".$_SESSION['phpgw_info']['expressoMail1_2']['user']['preferences']['common']['template_set']."';</script>";
	echo "<script src='js/modal/modal.js'></script>";
	echo "<script src='js/globals.js?".$update_version."' type='text/javascript'></script>";
	echo "<script src='js/sniff_browser.js?".$update_version."' type='text/javascript'></script>";
	echo "<style type='text/css'>@import url(../phpgwapi/js/jscalendar/calendar-win2k-1.css);</style>";
	echo "<script src='../phpgwapi/js/jscalendar/calendar.js?".$update_version."' type='text/javascript'></script>";
	echo "<script src='../phpgwapi/js/jscalendar/calendar-setup.js?".$update_version."' type='text/javascript'></script>";
	echo "<script src='../phpgwapi/js/jscalendar/lang/calendar-br.js?".$update_version."' type='text/javascript'></script>";
    echo "<script src='../phpgwapi/js/x_tools/xtools.js?".$update_version."' type='text/javascript'></script>";
	echo '<script type="text/javascript" src="../phpgwapi/js/wz_dragdrop/wz_dragdrop.js?'.$update_version.'"></script>';
	echo '<script type="text/javascript" src="../phpgwapi/js/dJSWin/dJSWin.js?'.$update_version.'"></script>';

	// Jquery
	echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css">';
	echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/contextmenu/jquery.contextMenu.css"/>';

	// Jquery - Expresso Messenger
	echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/wijmo/jquery.wijmo.css"/>';
	echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/contextmenu/jquery.contextMenu.css"/>';
	echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/messenger/im.css"/>';

	// JSON
	echo '<script type="text/javascript" src="../prototype/plugins/json/json.min.js" language="javascript" charset="utf-8"></script>';

	// Jquery
	echo '<script type="text/javascript" src="../prototype/plugins/jquery/jquery-latest.min.js" language="javascript" charset="utf-8"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/jquery/jquery-migrate.min.js" language="javascript" charset="utf-8"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/jquery/jquery-ui-latest.min.js" language="javascript" charset="utf-8"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/ejs/ejs_production.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/ejs/view.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/jquery.cookies/cookie.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/contextmenu/jquery.contextMenu.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/wijmo/jquery.wijmo.min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/sort/jquery.tinysort.min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/sort/jquery.tinysort.charorder.min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/sort/jquery.opensource.min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/wijmo/jquery.wijmo.wijdialog.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/linkify/ba-linkify.min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/dateFormat/dateFormat.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/scrollto/jquery.scrollTo-min.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/datejs/sugarpak.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/datejs/date-pt-BR.js"></script>';

	// Jquery - Expresso Messenger
	echo '<script type="text/javascript" src="../prototype/plugins/jquery-xmpp/APIAjax.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/jquery-xmpp/jquery.xmpp.js"></script>';
	echo '<script type="text/javascript" src="../prototype/plugins/messenger/lang/messages.js"></script>';	
	echo '<script type="text/javascript" src="../prototype/plugins/messenger/im.js"></script>';

	//Enable/Disable VoIP Service -> Voip Server Config
	$voip_enabled = false;
	$voip_groups = array();	
	if($GLOBALS['phpgw_info']['server']['voip_groups']) {
		$emailVoip = false;
		foreach(explode(",",$GLOBALS['phpgw_info']['server']['voip_groups']) as $i => $voip_group){
			$a_voip = explode(";",$voip_group);			
			$voip_groups[] = $a_voip[1];
		}
		foreach($GLOBALS['phpgw']->accounts->membership() as $idx => $group){			
			if(array_search($group['account_name'],$voip_groups) !== FALSE){		 
				$voip_enabled = true;
				$emailVoip = $GLOBALS['phpgw_info']['server']['voip_email_redirect'];
				break;
			}
		}
	}

	//Enable/Disable Expresso Messenger -> ExpressoMail Config
	$im = CreateObject('phpgwapi.messenger');
	if ( $im->checkAuth() ) echo '<input type="hidden" name="expresso_messenger_enabled" value="true">';

	//Local messages
	$_SESSION['phpgw_info']['server']['expressomail']['enable_local_messages'] = $current_config['enable_local_messages'];

	// Get Data from ldap_manager and emailadmin.
	$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');
	
	// Loading ExpressoAdmin Config
	$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
	$c->read_repository();
	$_SESSION['phpgw_info']['expresso']['expressoAdmin1_2'] = $c->config_data;
	
    // Loading Admin Config Module
    $c = CreateObject('phpgwapi.config','expressoMail1_2');
    $c->read_repository();
    $current_config = $c->config_data;    

	$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
	$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
	$_SESSION['phpgw_info']['expressomail']['ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
	$_SESSION['phpgw_info']['expressomail']['user']['email'] = $GLOBALS['phpgw']->preferences->values['email'];
	$_SESSION['phpgw_info']['server']['temp_dir'] = $GLOBALS['phpgw_info']['server']['temp_dir'];
	
	$preferences = $GLOBALS['phpgw']->preferences->read();
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail'] = $preferences['enable_local_messages']; 
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail'] = $preferences['expressoMail'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['voip_enabled'] = $voip_enabled;
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['voip_email_redirect'] = $emailVoip;
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['outoffice'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['outoffice'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number'] = $GLOBALS['phpgw_info']['user']['telephonenumber'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_cache'] = $current_config['expressoMail_enable_cache'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_x_origin'] = $current_config['expressoMail_use_x_origin'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['number_of_contacts'] = $current_config['expressoMail_Number_of_dynamic_contacts'] ? $current_config['expressoMail_Number_of_dynamic_contacts'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['notification_domains'] = $current_config['expressoMail_notification_domains'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['googlegears_url'] = $current_config['expressoMail_googlegears_url'];
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_assinar_criptografar'] = $GLOBALS['phpgw_info']['server']['use_assinar_criptografar'] ?  $GLOBALS['phpgw_info']['server']['use_assinar_criptografar'] : "0";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'] : "0";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital'] : "0";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'] : "50";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['search_characters_number'] : "4";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['num_max_certs_to_cipher'] = $GLOBALS['phpgw_info']['server']['num_max_certs_to_cipher'] ?  $GLOBALS['phpgw_info']['server']['num_max_certs_to_cipher'] : "10";
    $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_signature_cripto'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_cripto'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_cripto'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['keep_after_auto_archiving'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['keep_after_auto_archiving'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['keep_after_auto_archiving'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['display_user_email'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['display_user_email'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['display_user_email'] : "";

	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size'] = $current_config['expressoMail_Max_attachment_size'] ? $current_config['expressoMail_Max_attachment_size']."M" : ini_get('upload_max_filesize');
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_msg_size'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['imap_max_folders'] = $current_config['expressoMail_imap_max_folders'];
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'] : "50";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['extended_info']?$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['extended_info'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['extended_info']:'0';
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['from_to_sent'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['auto_create_local'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['auto_create_local'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['auto_create_local'] : "0";
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] : "0";
	
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] ? $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['quick_search_default'] : 1;
	// ACL for block edit Personal Data.
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['blockpersonaldata'] = $GLOBALS['phpgw']->acl->check('blockpersonaldata',1,'preferences');

	//SpellChecker
	if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] !='0'){
	    echo '<link rel="stylesheet" type="text/css" href="spell_checker/css/spell_checker.css">';
	    echo '<script src="spell_checker/cpaint/cpaint2.inc.js" type="text/javascript"></script>';
	    echo '<script src="spell_checker/js/spell_checker.js" type="text/javascript"></script>';
	}
	
	$template = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
	$template->set_var("txt_loading",lang("Loading"));
	$template->set_var("txt_clear_trash",lang("message(s) deleted from your trash folder."));
    $template->set_var("new_message", lang("New Message"));
	$template->set_var("lang_inbox", lang("Inbox"));
    $template->set_var("refresh", lang("Refresh"));
    $template->set_var("tools", lang("Tools"));	
	$template->set_var("lang_Open_Search_Window", lang("Open search window") . '...');
	$template->set_var("lang_search_user", lang("Search user") . '...'); 
	$template->set_var("upload_max_filesize",ini_get('upload_max_filesize'));
	$template->set_var("msg_folder",$_GET['msgball']['folder']);
	$template->set_var("msg_number",$_GET['msgball']['msgnum'] ? $_GET['msgball']['msgnum'] : $_GET['to']);
	$template->set_var("user_email",$_SESSION['phpgw_info']['expressomail']['user']['email']);
	$acc = CreateObject('phpgwapi.accounts');
	$template->set_var("user_organization", $acc->get_organization($GLOBALS['phpgw_info']['user']['account_dn']));
	$template->set_var("cyrus_delimiter",$_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter']);	
	// Fix problem with cyrus delimiter changes in preferences.
	// Dots in names: enabled/disabled.
	$save_in_folder = @preg_replace("#INBOX/#i", "INBOX".$_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'], $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder']);
	$save_in_folder = @preg_replace("#INBOX.#i", "INBOX".$_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'], $save_in_folder);
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'] = $save_in_folder;
	// End Fix.

	$template->set_file(Array('expressoMail' => 'index.tpl'));
	$template->set_block('expressoMail','list');
	$template->pfp('out','list');
	$GLOBALS['phpgw']->common->phpgw_footer();
    
    $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_enable_log_messages'] = $current_config['expressoMail_enable_log_messages'];
	
    // Begin Set Anti-Spam options.
    $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_use_spam_filter'] = $current_config['expressoMail_use_spam_filter'];
    $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_url']        = $current_config['expressoMail_spam_url'];
    $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_fields']     = $current_config['expressoMail_spam_fields'];
    
    echo '<script> var use_spam_filter = "'.$current_config['expressoMail_use_spam_filter'].'"</script>'; 
    echo '<script> var sieve_forward_domains = "'.$current_config['expressoMail_sieve_forward_domains'].'"</script>';
    // End Set Anti-Spam options.
    
    // Begin Search Users characteres shared folders.
    
    if( isset($current_config['expressoMail_min_num_characters'] ) )
        echo '<script> var sharedFolders_min_num_characters = "'.$current_config['expressoMail_min_num_characters'].'"</script>';
    else
        echo '<script> var sharedFolders_min_num_characters = "" </script>';
    
    if( isset($current_config['expressoMail_users_auto_search']) )
        echo '<script> var sharedFolders_users_auto_search  = "'.$current_config['expressoMail_users_auto_search'].'" </script>';
    else
        echo '<script> var sharedFolders_users_auto_search  = "true" </script>';
    
    // End Search Users characteres shared folders.
    
    // Begin Enabled Read RSS    
	if( isset( $current_config['expressoMail_enabled_read_rss'] ) )        
	{
		echo '<script>var enabledReadRSS = "'.$current_config['expressoMail_enabled_read_rss'].'"</script>'; 
	}
	else
	{
		echo '<script>var enabledReadRSS = "false"; </script>';
	}

	// Set Imap Folder names options

    $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'] 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder']	? $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder']		: lang("Trash");
    $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'] 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'] ? $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'] 	: lang("Drafts");
    $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'] 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']	? $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']		: lang("Spam");
    $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder']	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'] 	? $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'] 		: lang("Sent");

    // gera paramero com tokens suportados ....
    $var_tokens = '';
    for($ii = 1; $ii < 11; $ii++)
    {
        if($GLOBALS['phpgw_info']['server']['test_token' . $ii . '1'])
            $var_tokens .= $GLOBALS['phpgw_info']['server']['test_token' . $ii . '1'] . ',';
    }

    if(!$var_tokens)
    {
        $var_tokens = 'ePass2000Lx;/usr/lib/libepsng_p11.so,ePass2000Win;c:/windows/system32/ngp11v211.dll';
    }

    echo '<script> var special_folders = new Array(4);
   	special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'].'"] = \'Trash\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'].'"] = \'Drafts\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'].'"] = \'Spam\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'].'"] = \'Sent\';
    var trashfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'].'";
    var draftsfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'].'";
    var sentfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'].'";
    var spamfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'].'";
    var token_param = "'.$var_tokens.'";
    var locale = "'.$GLOBALS['phpgw']->common->getPreferredLanguage().'";
    </script>';

    // End Set Imap Folder names options
	//User info
	echo "<script language='javascript'> var account_id = ".$GLOBALS['phpgw_info']['user']['account_id'].";var expresso_offline = false;</script>";

	$obj = createobject("expressoMail1_2.functions");

	// setting timezone preference
	$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['timezone'] =
		$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] ?
		$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] : 'America/Sao_Paulo';

	// este arquivo deve ser carregado antes que
	// os demais pois nele contem a função get_lang
	// que é utilizada em diversas partes
	echo $obj -> getFilesJs("js/common_functions.js",$update_version);
	include("inc/load_lang.php");

        // INCLUDE these JS Files:
	if ($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages']) 
		echo "<script src='js/gears_init.js?".$update_version."'></script>";
	
	echo '<script src="../phpgwapi/js/dftree/dftree.js?'.$update_version.'"></script>';
	echo '<script src="js/drag_area.js?'.$GLOBALS['phpgw_info']['apps']['expressoMail1_2']['version'].'"></script>';

	$scripts = "js/abas.js," .
				"js/main.js," .
				"js/draw_api.js,";
	if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages'])
		$scripts .= "js/local_messages.js,";
	
	$scripts .= "js/messages_controller.js," .
				"js/DropDownContacts.js," .
				"js/doiMenuData.js," .
				"js/connector.js";
	
	echo $obj -> getFilesJs($scripts,$update_version);
	echo '<script type="text/javascript">init();</script>';

	if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_shortcuts'])
	{
		echo $obj -> getFilesJs("js/shortcut.js", $update_version);
	}

	// Get Preferences or redirect to preferences page.
	$GLOBALS['phpgw']->preferences->read_repository();
	//print_r($_SESSION['phpgw_info']['user']['preferences']['expressoMail']);
	unset($_SESSION['phpgw_info']['expressomail']['user']['preferences']);
	unset($_SESSION['phpgw_info']['expressomail']['user']['acl']);
	unset($_SESSION['phpgw_info']['expressomail']['user']['apps']);
	unset($_SESSION['phpgw_info']['expressomail']['server']['global_denied_users']);
	unset($_SESSION['phpgw_info']['expressomail']['server']['global_denied_groups']);
	
	// MOTD : Messages of the Day
	echo $obj->getFilesJs("js/messagesOfToday.js", $update_version);
	echo '<input type="hidden" name="motd_enabled" value="'.$current_config['expressoMail_motd_enabled'].'">';
	echo '<input type="hidden" name="motd_body" value="'.$current_config['expressoMail_motd_msg_body'].'">';
	echo '<input type="hidden" name="motd_title" value="'.$current_config['expressoMail_motd_msg_title'].'">';
	echo '<input type="hidden" name="motd_number_views" value="'.$current_config['expressoMail_motd_number_views'].'">';
	echo '<input type="hidden" name="motd_rangeMsg" value="'.$current_config['expressoMail_motd_range_of_messages_in_minutes'].'">';
	echo '<input type="hidden" name="motd_type_message" value="'.$current_config['expressoMail_motd_type_message'].'">';

	// FullName Messenger
	echo '<input type="hidden" name="messenger_fullName" value="'.$GLOBALS['phpgw_info']['user']['fullname'].'">';

	$user_is_blocked_to_send_email = 0;

	$block_send_email_enabled = $current_config['expressoMail_block_send_email_enabled'];

	if ($block_send_email_enabled == "true") {

		$ldap_functions = CreateObject('expressoMail1_2.ldap_functions');

		$group_cn_block_send_email_group = $current_config['expressoMail_block_send_email_group'];

		$arr_groups = explode(",",$group_cn_block_send_email_group);

		$arr_gid_numbers = array();

		foreach ($arr_groups as $group_to_search) {
			$gid_number = $ldap_functions->groupcn2gidNumber($group_to_search);
			if (($gid_number != "") && ($gid_number != false)) {
				array_push($arr_gid_numbers,$gid_number);
			}
		}
		//VERIFICA SE O USUÁRIO ESTÁ FAZENDO PARTE DE ALGUM DOS GRUPOS BLOQUEADOS PARA ENVIO DE EMAIL
		foreach($GLOBALS['phpgw']->accounts->membership() as $idx => $group){	
			if (in_array($group['account_name'],$arr_gid_numbers)) {
				$user_is_blocked_to_send_email = 1;
			}
		}

	}

	echo '<input type="hidden" id="user_is_blocked_to_send_email" name="user_is_blocked_to_send_email" value="' . $user_is_blocked_to_send_email . '">';
	echo '<input type="hidden" id="user_is_blocked_to_send_email_message" name="user_is_blocked_to_send_email_message" value="'.$current_config['expressoMail_block_send_email_error_message'].'">';

	$delete_trash = isset( $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'] ) &&
		((int)$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days']) > 0;

	$delete_spam = isset( $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'] ) &&
		((int)$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days']) > 0;

	if ( $delete_trash || $delete_spam ) {
		$imap = CreateObject('expressoMail1_2.imap_functions');
		if ( $delete_spam ) {
			$result = $imap->clean_folder( array( 'type' => 'spam', 'days' => (int)$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'] ) );
			if ( $result['status']  ) echo '<script type="text/javascript"> $(document).ready( function() { write_msg( "'.utf8_decode( $result['message'] ).'" ); }); </script>';
		}
		if ( $delete_trash ) {
			$result = $imap->clean_folder( array( 'type' => 'trash', 'days' => (int)$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'] ) );
			if ( $result['status']  ) echo '<script type="text/javascript"> $(document).ready( function() { write_msg( "'.utf8_decode( $result['message'] ).'" ); }); </script>';
		}
	}
?>
<html>
<head>
<title>ExpressoMail</title>
</head>
<body scroll="no" style="overflow:hidden">
</body>
</html>
<script type="text/javascript">connector.updateVersion = "<?=$update_version?>";</script>
<!-----Expresso Mail - Version Updated:<?=$update_version?>-------->
