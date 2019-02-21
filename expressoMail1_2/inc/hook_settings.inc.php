<?php
    /**************************************************************************\
    * eGroupWare - ExpressoMail Preferences                                    *
    * http://www.expressolivre.org                                             *    
    * Modified by Alexandre Felipe Muller de Souza <amuller@celepar.pr.gov.br> *
    * --------------------------------------------                             *
    *  This program is free software; you can redistribute it and/or modify it *
    *  under the terms of the GNU General Public License as published by the   *
    *  Free Software Foundation; either version 2 of the License, or (at your  *
    *  option) any later version.                                              *
    \**************************************************************************/
if(!isset($GLOBALS['phpgw_info'])){
	$GLOBALS['phpgw_info']['flags'] = array(
		'currentapp' => 'expressoMail1_2',
		'nonavbar'   => true,
		'noheader'   => true
	);
}
require_once '../header.inc.php';
include_once("../prototype/library/fckeditor/fckeditor.php");
include_once("class.functions.inc.php");
$type = isset($_GET['type']) ? $_GET['type']:$GLOBALS['type']; // FIX ME

//if ($type == 'user' || $type == ''){
create_script('function exibir_ocultar()
{
    var type = ("'.$type.'" == "") ? "user" : "'.$type.'";
    var use_signature_digital_cripto = null;

    if (document.all)
    {
        // is_ie
        use_signature_digital_cripto = document.getElementsByName(type+"[use_signature_digital_cripto]")[1];
    }
    else
    {
        // not_ie
        use_signature_digital_cripto = document.getElementsByName(type+"[use_signature_digital_cripto]")[0];
    }

    var default_signature_digital_cripto = "'.$GLOBALS['phpgw_info']['default']['preferences']['expressoMail']['use_signature_digital_cripto'].'";

    if (use_signature_digital_cripto)
    {
        var element_signature_digital = document.getElementById(type+"[use_signature_digital]");
        var element_signature_cripto = document.getElementById(type+"[use_signature_cripto]");

        switch (use_signature_digital_cripto[use_signature_digital_cripto.selectedIndex].value){

            case "1":
                element_signature_digital.style.display="";
                element_signature_cripto.style.display="";
                break;
            case "0":
                element_signature_digital.style.display="none";
                element_signature_cripto.style.display="none";
                break;
            case "":
                if (default_signature_digital_cripto){
                    element_signature_digital.style.display="";
                    element_signature_cripto.style.display="";
                 }
                 else
                 {
                    element_signature_digital.style.display="none";
                    element_signature_cripto.style.display="none";
                 }

        }

    }

}');
//}
$default = false;
create_check_box('Do you want to show common name instead of UID?','uid2cn',$default,
	'Do you want to show common name instead of UID?');
create_check_box('Do you want to automatically display the message header?','show_head_msg_full',$default,'');
$default = array(
	'25'	=> '25',
	'50'	=> '50',
	'75'	=> '75',
	'100'	=> '100'
);

create_select_box('What is the maximum number of messages per page?','max_email_per_page',$default,'This is the number of messages shown in your mailbox per page');

//$default = 0;
create_check_box('Preview message text within subject column','preview_msg_subject','this exhibits a sample of message within the message subject column');

//$default = 0;
create_check_box('Preview message text within a tool-tip box','preview_msg_tip','this exhibits a sample of message within a tool-tip box');

create_check_box('View extended information about users','extended_info','This exhibits employeenumber and ou from LDAP in searchs');

create_check_box('Save deleted messages in trash folder?','save_deleted_msg','When delete message, send it automatically to trash folder');
$default = array(
	'1'    => lang('1 day'),
	'2'    => lang('2 days'),
	'3'    => lang('3 days'),
	'4'   => lang('4 days'),
	'5'   => lang('5 days')
);

$arquived_messages = array(true => lang("Copy"), false => lang("Move"));

create_select_box('Delete trash messages after how many days?','delete_trash_messages_after_n_days',$default,'Delete automatically the messages in trash folder in how many days');
create_select_box('Delete spam messages after how many days?','delete_spam_messages_after_n_days',$default,'Delete automatically the messages in spam folder in how many days');
create_check_box('Would you like to use local messages?','use_local_messages','Enabling this options you will be able to store messages in your local computer');
create_select_box('Desired action to archive messages to local folders','keep_archived_messages',$arquived_messages,'After store email in your local computer delete it from server');
create_check_box('Automaticaly create Default local folders?','auto_create_local','Enable this option if you want to automaticaly create the Inbox, Draft, Trash and Sent folders');
create_check_box('Show previous message, after delete actual message?','delete_and_show_previous_message','Enable this option if you want to read the next message everytime you delete a message');
$default = array(
	'0' => lang( 'no' ),
	'1' => lang( 'Current box only' ),
	'2' => lang( 'Inbox only' ),
	'3' => lang( 'All boxes'),
);
create_select_box('Do you wanna receive an alert for new messages?','alert_new_msg',$default,'Everytime you receive new messages you will be informed');
create_check_box('Show default view on main screen?','mainscreen_showmail','Show unread messages in your home page');
create_check_box('Do you want to use remove attachments function?','remove_attachments_function','It allow you to remove attachments from messages');
create_check_box('Do you want to use important flag in email editor?','use_important_flag','It allow you to send emails with important flag, but you can receive unwanted messages with important flag');
create_check_box('Do you want to use SpellChecker in email editor?','use_SpellChecker','It allow you to check the spelling of your emails');
//Use user folders from email

require_once('class.imap_functions.inc.php');
$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];
$e_server = $_SESSION['phpgw_info']['expressomail']['email_server'];
$imap = CreateObject('expressoMail1_2.imap_functions');

if ($type != "" && $type != "user"){
	
	$trash = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'];
	$drafts = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'];
	$spam = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'];
	$sent = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'];
	$default = Array(
		'INBOX' =>      lang('INBOX'), 
		'INBOX' . $imap->imap_delimiter . $drafts => lang($drafts),
		'INBOX' . $imap->imap_delimiter . $spam => lang($spam),
		'INBOX' . $imap->imap_delimiter . $trash => lang($trash),  
		'INBOX' . $imap->imap_delimiter . $sent => lang($sent)
	);
}
else
{
$save_in_folder_selected = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'];

// Load Special Folders (Sent, Trash, Draft, Spam) from EmailAdmin (if exists, else get_lang)
$specialFolders = array ("Trash" => lang("Trash"), "Drafts" => lang("Drafts"), "Spam" => lang("Spam"), "Sent" => lang("Sent"));

foreach ($specialFolders as $key => $value){
	if($e_server['imapDefault'.$key.'Folder'])
		$specialFolders[$key] = $e_server['imapDefault'.$key.'Folder'];
}
unset($default);
$default[-1] = lang('Select on send');
	
	foreach($imap -> get_folders_list(array('noSharedFolders' => true)) as $id => $folder){
		if(!is_numeric($id))
			continue;
		else{
			// Translate INBOX (root folder)
			if (strtolower($folder['folder_name']) == "inbox")
				$folder['folder_name'] = lang("Inbox");
			// Translate Special Folders
			elseif (($keyFolder = array_search($folder['folder_name'], $specialFolders)) !== false)
				$folder['folder_name'] = lang($keyFolder);
			// Identation for subfolders
			$folder_id = explode($e_server['imapDelimiter'],$folder['folder_id']);       
			$level = count($folder_id);
			$ident = '';
			for($i = 2; $level > 2 && $i < $level;$i++)
				$ident .= ' - ';
			
			$default[$folder['folder_id']] = $ident.$folder['folder_name'];
		}		
	}

}
create_select_box('Save sent messages in folder','save_in_folder',$default,'Save automatically sent messages in selected folder');
create_check_box('Show TO: in place of FROM: only in Automatic SEND folder','from_to_sent','Show TO: in place of FROM: only in Automatic SEND folder');


create_check_box('Hide menu folders?','hide_folders','You can use it if your screen does not have good resolution');

$default = array(
	'20' => lang('normal'),
	'30' => lang('medium'),
	'40' => lang('big')
);

$default =  array(
    '50'    => '50',
    '100'   => '100',
    '150'   => '150',
    '200'   => '200',
    '300'   => '300',
    '400'   => '400',
    '65536' => lang('unlimited')
);

create_select_box('What is the maximum number of results in an e-mail search?','search_result_number',$default,'');

$default =  array(
    '1' 	=> lang('unlimited'),
    '2'     => '2',
    '3'     => '3',
    '4'     => '4',
    '5'     => '5'
);

create_select_box('What is the minimum number of characters in searching contacts?','search_characters_number',$default,'what is the minimum number of characters in searching contacts');

$default =  array(
	'1' => '1',
	'2' => '2',
	'3' => '3',
	'4' => '4',
	'5' => '5',
);

create_select_box('What is the height of the lines in the list of messages?','line_height',$default,'');
create_check_box('Increases th maximum size of show messages?','max_msg_size','Increases the maximum size of show emails from 100kb to 1mb');
create_check_box('Use dynamic contacts?','use_dynamic_contacts','Store your\'s most used contacts');
create_check_box('Use shortcuts?','use_shortcuts','n key (Open new message)<br>ESC key (Close tab)<br>i key (print)<br>e key (forward)<br>r key (reply)<br>DELETE key (delete the current message)<br>Ctrl + up (go to previous message)<br>Ctrl + down (go to next message)<br>Shift + up or down (select multiple messages)<br>F9  key (search at catalog)<br>');
create_check_box('Auto save draft','auto_save_draft','When you are away from computer it saves automatically the message you are writing');

unset($default);
$functions = new functions();
$zones = $functions->getTimezones();
$zones = array_combine( $zones, $zones );
create_select_box('What is your timezone?', 'timezone', $zones, 'The Timezone you\'re in.', 'America/Sao_Paulo');

$default =  array(
    '1' => lang('contacts'),
    '2' => lang('email')
);

create_select_box('Where should the quick search be performed by default?','quick_search_default',$default,'It is where the keyword should be searched when the user executes a quick search.');

$default = array(
	'65536' => lang('unlimited'),
	'640' => '640',
	'768' => '768',
	'800' => '800',
	'1024' => '1024',
	'1080' => '1080'
);

create_select_box('What is the maximum size of embedded images?','image_size',$default,'When user send an email with image in body message, it changes the size');

create_input_box('Display name for sending email','display_user_email','',$_SESSION['phpgw_info']['expressomail']['user']['fullname'], '60', '60' );

$im = CreateObject('phpgwapi.messenger');
if ( $im->enabled ) {
	create_check_box( 'Start the Expresso Messenger automatically', 'messenger_auto_start', '' );
}

if($GLOBALS['phpgw_info']['server']['use_assinar_criptografar'])
{
    create_check_box('Enable digitally sign/cipher the message?','use_signature_digital_cripto','','',True,'onchange="javascript:exibir_ocultar();"');
    if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'])
    {
        create_check_box('Always sign message digitally?','use_signature_digital','');
        create_check_box('Always cipher message digitally?','use_signature_cripto','');
    }
    else
    {
        create_check_box('Always sign message digitally?','use_signature_digital','','',True,'',False);
        create_check_box('Always cipher message digitally?','use_signature_cripto','','',True,'',False);
    }
}

$auto_signature = $_SESSION['phpgw_info']['expressomail']['email_server']['defaultUserSignature'];

$default = array(
	'text' => lang('simple text'),
	'html' => lang('rich text')
);
create_check_box( 'Insert signature automatically in new messages?', 'use_signature', '', null, null, null, !$auto_signature);
create_select_box( 'Signature Type', 'type_signature', $default, '', '', '', 'onchange="javascript:changeType(this.value);" onload="javascript:alert(this.value);"', !$auto_signature);

if ($type == 'user' || $type == ''){
	$oFCKeditor = new FCKeditor('html_signature');
	$oFCKeditor->BasePath   = '../prototype/library/fckeditor/';
	$oFCKeditor->ToolbarSet = 'ExpressoLivre';

	$vars = $GLOBALS['phpgw']->preferences->user[$appname];

	create_html_code("signature","<div id='text_signature'>
		<textarea rows='5' cols='50' id='user_signature' name='user[signature]'>","</textarea></div>
		<div style='display:none;' id='html_signature'>".$oFCKeditor->Create()."</div>
		<script language='javascript'>
//document.getElementById('user_signature').value  = '".$vars['signature']."';
function changeType(value){
	var html_signature = FCKeditorAPI.GetInstance(\"html_signature\");
        value=value.replace('d','');
	if(value == 'text'){
		document.getElementById('user_signature').value = html_signature.GetHTML();
		document.getElementById(\"text_signature\").style.display = '';
		document.getElementById(\"html_signature\").style.display = 'none';
		document.getElementsByName('html_signature')[0].disabled = true;
	}
	else if(value == 'html'){
		html_signature.SetHTML(document.getElementById('user_signature').value);
		document.getElementById(\"text_signature\").style.display = 'none';
		document.getElementById(\"html_signature\").style.display  = '';
		document.getElementsByName('html_signature')[0].disabled = false;
	}
}
function get_html_translation_table(table, quote_style) {
    // http://kevin.vanzonneveld.net 
    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';
 
    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';
 
    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error(\"Table: \"+useTable+' not supported');
        // return false;
    }
 
    entities['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }
    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#39;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';
  
    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        hash_map[symbol] = entities[decimal];
    }
    return hash_map;
}
function html_entity_decode( string, quote_style ) {
    // http://kevin.vanzonneveld.net
    var hash_map = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }
    for (symbol in hash_map) {
        entity = hash_map[symbol];
        tmp_str = tmp_str.split(entity).join(symbol);
    }
    tmp_str = tmp_str.split('&#039;').join(\"'\");
    return tmp_str;
}

function getTypeSignature() {
   var elementoSelects  = document.getElementsByTagName('select');
   for(i=0;i<elementoSelects.length;i++){
	if( elementoSelects[i].name == \"user[type_signature]\" ){
	         return elementoSelects[i];
	}
    }
    return null;
}

function config_form(pObj,pHandler)
{
	pObj.onclick=function () {
		if (getTypeSignature().value == \"html\" || getTypeSignature().value == \"htmld\") {
			return pHandler(\"text\");
		}
	};

}
document.getElementById('user_signature').value=html_entity_decode(document.getElementById('user_signature').innerHTML);

function setDefaultTypeSignature() {
	if(getTypeSignature()){
		getTypeSignature().options[0].value = '".$GLOBALS['phpgw']->preferences->default['expressoMail']['type_signature']."d';
		if(getTypeSignature().value.indexOf('html')!=-1){
		   changeType(getTypeSignature().value);
	    }
	}else{
		 changeType('".$GLOBALS['phpgw']->preferences->forced['expressoMail']['type_signature']."');
	}
}

setTimeout('setDefaultTypeSignature();config_form(document.getElementsByName(\'submit\')[0],changeType);',2000);
</script>");
}
