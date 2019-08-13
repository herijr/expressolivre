<?php

$GLOBALS['phpgw_info']['flags'] = array(
	'noheader'                => false,
	'nonavbar'                => false,
	'currentapp'              => 'expressoMail1_2',
	'enable_nextmatchs_class' => true,
);

require_once('../header.inc.php');

if ( !isset( $GLOBALS['phpgw']->js ) ) $GLOBALS['phpgw']->js = createobject( 'phpgwapi.javascript' );

$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];

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

//Local messages
$_SESSION['phpgw_info']['server']['expressomail']['enable_local_messages'] = $current_config['enable_local_messages'];

// Loading Admin Config Module
$c = CreateObject('phpgwapi.config','expressoMail1_2');
$c->read_repository();
$current_config = $c->config_data;    

$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
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

// Fix problem with cyrus delimiter changes in preferences.
// Dots in names: enabled/disabled.
$save_in_folder = @preg_replace("#INBOX/#i", "INBOX".$_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'], $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder']);
$save_in_folder = @preg_replace("#INBOX.#i", "INBOX".$_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'], $save_in_folder);
$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'] = $save_in_folder;
// End Fix.

$_SESSION['phpgw_info']['server']['expressomail']['expressoMail_enable_log_messages'] = $current_config['expressoMail_enable_log_messages'];

// Begin Set Anti-Spam options.
$_SESSION['phpgw_info']['server']['expressomail']['expressoMail_use_spam_filter'] = $current_config['expressoMail_use_spam_filter'];
$_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_url']        = $current_config['expressoMail_spam_url'];
$_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_fields']     = $current_config['expressoMail_spam_fields'];
// End Set Anti-Spam options.

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

// setting timezone preference
$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['timezone'] =
$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] ?
$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['timezone'] : 'America/Sao_Paulo';

//----------------------------------------------------------------------------------------------------------------------
//- JAVASCRIPT ---------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

$GLOBALS['phpgw']->js->add( 'txt' , 'var template = "'.$_SESSION['phpgw_info']['expressoMail1_2']['user']['preferences']['common']['template_set'].'";' );
$GLOBALS['phpgw']->js->add( 'file', 'js/modal/modal.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/globals.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/sniff_browser.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/jscalendar/calendar.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/jscalendar/calendar-setup.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/jscalendar/lang/calendar-br.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/x_tools/xtools.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/wz_dragdrop/wz_dragdrop.js' );
$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/dJSWin/dJSWin.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/json/json.min.js', 'utf-8' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery/jquery-latest.min.js', 'utf-8' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery/jquery-migrate.min.js', 'utf-8' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery/jquery-ui-latest.min.js', 'utf-8' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/ejs/ejs_production.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/ejs/view.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery.cookies/cookie.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/contextmenu/jquery.contextMenu.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/wijmo/jquery.wijmo.min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/sort/jquery.tinysort.min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/sort/jquery.tinysort.charorder.min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/sort/jquery.opensource.min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/wijmo/jquery.wijmo.wijdialog.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/linkify/ba-linkify.min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/dateFormat/dateFormat.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/scrollto/jquery.scrollTo-min.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/datejs/sugarpak.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/datejs/date-pt-BR.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery.mask-phone/jquery.mask-phone.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery-xmpp/APIAjax.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/jquery-xmpp/jquery.xmpp.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/messenger/lang/messages.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/messenger/im.js' );
$GLOBALS['phpgw']->js->add( 'file', '../prototype/plugins/ejci/favico-0.3.10.min.js' );

$GLOBALS['phpgw']->js->add( 'txt' , 'var use_spam_filter = "'.$current_config['expressoMail_use_spam_filter'].'";' );
$GLOBALS['phpgw']->js->add( 'txt' , 'var sieve_forward_domains = "'.$current_config['expressoMail_sieve_forward_domains'].'";' );


if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] !='0'){
	$GLOBALS['phpgw']->js->add( 'file', 'spell_checker/cpaint/cpaint2.inc.js' );
	$GLOBALS['phpgw']->js->add( 'file', 'spell_checker/js/spell_checker.js' );
}

// Begin Search Users characteres shared folders.
$GLOBALS['phpgw']->js->add( 'txt' , 'var sharedFolders_min_num_characters = "'.( isset($current_config['expressoMail_min_num_characters'] )? $current_config['expressoMail_min_num_characters'] : '' ).'";' );
$GLOBALS['phpgw']->js->add( 'txt' , 'var sharedFolders_users_auto_search = "'.( isset($current_config['expressoMail_users_auto_search'] )? $current_config['expressoMail_users_auto_search'] : 'true' ).'";' );
// End Search Users characteres shared folders.

// Begin Enabled Read RSS
$GLOBALS['phpgw']->js->add( 'txt' , 'var enabledReadRSS  = "'.( isset($current_config['expressoMail_enabled_read_rss'] )? $current_config['expressoMail_enabled_read_rss'] : 'false' ).'";' );

$GLOBALS['phpgw']->js->add( 'txt' , 'var special_folders = new Array(4);
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'].'"] = \'Trash\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'].'"] = \'Drafts\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'].'"] = \'Spam\';
    special_folders["'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'].'"] = \'Sent\';
    var trashfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'].'";
    var draftsfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'].'";
    var sentfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'].'";
    var spamfolder = "'.$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'].'";
    var token_param = "'.$var_tokens.'";
    var locale = "'.$GLOBALS['phpgw']->common->getPreferredLanguage().'";' );
// End Set Imap Folder names options

//User info
$GLOBALS['phpgw']->js->add( 'txt' , 'var account_id = '.$GLOBALS['phpgw_info']['user']['account_id'].'; var expresso_offline = false;' );

// este arquivo deve ser carregado antes que
// os demais pois nele contem a fun\E7\E3o get_lang
// que \E9 utilizada em diversas partes

$GLOBALS['phpgw']->js->add( 'file', 'js/common_functions.js' );
include( 'inc/load_lang.php' );

// INCLUDE these JS Files:
if ( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages'] )
	$GLOBALS['phpgw']->js->add( 'file', 'js/gears_init.js' );

$GLOBALS['phpgw']->js->add( 'file', '../phpgwapi/js/dftree/dftree.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/drag_area.js' );

$GLOBALS['phpgw']->js->add( 'file', 'js/abas.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/main.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/draw_api.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/signature_frame.js' );

if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages'])
	$GLOBALS['phpgw']->js->add( 'file', 'js/local_messages.js' );

$GLOBALS['phpgw']->js->add( 'file', 'js/messages_controller.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/DropDownContacts.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/doiMenuData.js' );
$GLOBALS['phpgw']->js->add( 'file', 'js/connector.js' );


if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_shortcuts'])
	$GLOBALS['phpgw']->js->add( 'file', 'js/shortcutExpresso.js' );

$GLOBALS['phpgw']->js->add( 'file', 'js/messagesOfToday.js' );

$GLOBALS['phpgw']->js->add( 'txt' , '
	var parent = $("#em_message_search").parent();
	parent.find(\'input\').on(\'keypress\', function(e){
		if( e.keyCode == 13) performQuickSearch( parent.find(\'input\').val() );
	});

	// a href emails
	parent.find("a").first().first().html("<img align=\'top\' src=\'templates/default/images/search.gif\'>");
	parent.find("a").first().bind("click", function(){ search_emails( parent.find("input").val() ); });
	parent.find("a").first().attr("title", "'.lang( 'Open search window' ).'");
	parent.find("a").first().attr("alt", "'.lang( 'Open Search Window' ).'");

	// a href users;
	parent.find("a").first().next().html("<img align=\'top\' src=\'templates/default/images/users.gif\'>");
	parent.find("a").first().next().bind("click",function(){ emQuickSearch( parent.find("input").val(), \'null\', \'null\'); });
	parent.find("a").first().next().attr("title", "'.lang( 'search user' ).'");
	parent.find("a").first().next().attr("alt", "'.lang( 'search user' ).'");

	parent.find("a").children(\'a\').each(function(){
		$(this).css({\'padding\':\'1 8px\',\'width\':\'16px\',\'height\':\'16px\'});
	});'
);

$str = 'connector.updateVersion = {};'.PHP_EOL;
foreach ( array( 'ccQuickAdd', 'color_palette', 'filter', 'filters', 'InfoContact', 'mail_sync', 'preferences',
	'QuickAddTelephone', 'QuickCatalogSearch', 'QuickSearchUser', 'rich_text_editor', 'search', 'sharemailbox',
	'TreeS', 'TreeShow', 'wfolders',
) as $fname ) $str .= 'connector.updateVersion["'.$fname.'"] = "'.$GLOBALS['phpgw']->js->version('js/'.$fname.'.js').'";'.PHP_EOL;
$GLOBALS['phpgw']->js->add( 'txt' , $str );

$GLOBALS['phpgw']->js->add( 'txt' , 'init();' );

//----------------------------------------------------------------------------------------------------------------------
//- CSS ----------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

$GLOBALS['phpgw']->css->validate_file( 'expressoMail1_2/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/main.css' );
$GLOBALS['phpgw']->css->validate_file( 'phpgwapi/js/dftree/dftree.css' );
echo $GLOBALS['phpgw']->css->get_css();

echo "<style type='text/css'>@import url(../phpgwapi/js/jscalendar/calendar-win2k-1.css);</style>";

// Jquery
echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css">';
echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/contextmenu/jquery.contextMenu.css"/>';

// Jquery - Expresso Messenger
echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/wijmo/jquery.wijmo.css"/>';
echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/contextmenu/jquery.contextMenu.css"/>';
echo '<link rel="stylesheet" type="text/css" href="../prototype/plugins/messenger/im.css"/>';

//SpellChecker
if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_SpellChecker'] !='0'){
	echo '<link rel="stylesheet" type="text/css" href="spell_checker/css/spell_checker.css">';
}

//----------------------------------------------------------------------------------------------------------------------
//- CSS END ------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

//echo $GLOBALS['phpgw']->js->get_scripts();

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

$template->set_file(Array('expressoMail' => 'index.tpl'));
$template->set_block('expressoMail','list');
$template->pfp('out','list');
$GLOBALS['phpgw']->common->phpgw_footer();

echo $GLOBALS['phpgw']->js->get_scripts();

// Get Preferences or redirect to preferences page.
$GLOBALS['phpgw']->preferences->read_repository();
//print_r($_SESSION['phpgw_info']['user']['preferences']['expressoMail']);
unset($_SESSION['phpgw_info']['expressomail']['user']['preferences']);
unset($_SESSION['phpgw_info']['expressomail']['user']['acl']);
unset($_SESSION['phpgw_info']['expressomail']['user']['apps']);
unset($_SESSION['phpgw_info']['expressomail']['server']['global_denied_users']);
unset($_SESSION['phpgw_info']['expressomail']['server']['global_denied_groups']);

//Enable/Disable Expresso Messenger -> ExpressoMail Config
if ( $im->checkAuth() ) echo '<input type="hidden" name="expresso_messenger_enabled" value="true">';

// MOTD : Messages of the Day
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
	//VERIFICA SE O USU\C1RIO EST\C1 FAZENDO PARTE DE ALGUM DOS GRUPOS BLOQUEADOS PARA ENVIO DE EMAIL
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

