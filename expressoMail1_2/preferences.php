<?php
	/**************************************************************************/
	
	$GLOBALS['phpgw_info']['flags'] = array(
		'currentapp' => 'expressoMail1_2',
		'noheader'   => True, 
		'nonavbar'   => True,
		'enable_nextmatchs_class' => True
	);

	
	require_once('../header.session.inc.php');
	include('inc/class.imap_functions.inc.php');	
	include_once("../prototype/library/fckeditor/fckeditor.php");
	
	if (!$_POST['try_saved'])
	{
		// Read Config and get default values;
		
		$GLOBALS['phpgw']->preferences->read_repository();
		// Loading Admin Config Module
    	$c = CreateObject('phpgwapi.config','expressoMail1_2');
    	$c->read_repository();
    	$current_config = $c->config_data;    
		
		
		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'])
			$GLOBALS['phpgw']->template->set_var('option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'].'_selected','selected');
		else
		$GLOBALS['phpgw']->template->set_var('option_50_selected','selected');		

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['save_deleted_msg'])		
			$GLOBALS['phpgw']->template->set_var('checked_save_deleted_msg','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_save_deleted_msg','');

		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'])
			$GLOBALS['phpgw']->template->set_var('delete_trash_messages_option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'].'_selected','selected');
		else
			$GLOBALS['phpgw']->template->set_var('delete_trash_messages_option_0_selected','selected');		
		
		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'])
			$GLOBALS['phpgw']->template->set_var('delete_spam_messages_option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'].'_selected','selected');
		else
			$GLOBALS['phpgw']->template->set_var('delete_spam_messages_option_0_selected','selected');
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_and_show_previous_message'])		
			$GLOBALS['phpgw']->template->set_var('checked_delete_and_show_previous_message','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_delete_and_show_previous_message','');

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['alert_new_msg'])		
			$GLOBALS['phpgw']->template->set_var('checked_alert_new_msg','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_alert_new_msg','');

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['mainscreen_showmail'])
			$GLOBALS['phpgw']->template->set_var('checked_mainscreen_showmail','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_mainscreen_showmail','');

		if (!is_numeric($current_config['expressoMail_Number_of_dynamic_contacts'])) 
		{										
			$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','');
			$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','disabled');
		}
		else
		{
			if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_dynamic_contacts'])
				$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','checked');
			else
				$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','');
		}

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_shortcuts'])
			$GLOBALS['phpgw']->template->set_var('checked_shortcuts','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_shortcuts','');

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['auto_save_draft'])
			$GLOBALS['phpgw']->template->set_var('checked_auto_save_draft','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_auto_save_draft','');


        if($GLOBALS['phpgw_info']['server']['use_assinar_criptografar'])
		{
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'])
			{
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_digital_cripto','checked');
			$GLOBALS['phpgw']->template->set_var('display_digital','');
			$GLOBALS['phpgw']->template->set_var('display_cripto','');
			}
		else
			{
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_digital','');
			$GLOBALS['phpgw']->template->set_var('display_digital','style="display: none;"');
			$GLOBALS['phpgw']->template->set_var('display_cripto','style="display: none;"');
			}

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital'])
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_digital','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_digital','');
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_cripto'])
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_cripto','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_use_signature_cripto','');
		}
		else
		{
			$GLOBALS['phpgw']->template->set_var('display_digital','style="display: none;"');
			$GLOBALS['phpgw']->template->set_var('display_cripto','style="display: none;"');
			$GLOBALS['phpgw']->template->set_var('display_digital_cripto','style="display: none;"');
		}


		// Insert new expressoMail preference use_signature: defines if the signature will be automatically inserted
		// at the e-mail body
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature'])
			$GLOBALS['phpgw']->template->set_var('checked_use_signature','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_use_signature','');

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['signature'])
			$GLOBALS['phpgw']->template->set_var('text_signature',$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['signature']);
		else
			$GLOBALS['phpgw']->template->set_var('text_signature','');
		
		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['type_signature']){
			$GLOBALS['phpgw']->template->set_var('type_signature_option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['type_signature'].'_selected','selected');
			$GLOBALS['phpgw']->template->set_var('type_signature_td_'.($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['type_signature'] == 'html' ? 'text' : 'html'),'display:none');
		}
		else{
			$GLOBALS['phpgw']->template->set_var('type_signature_option_text_selected','selected');
			$GLOBALS['phpgw']->template->set_var('type_signature_td_html','display:none');
		}

		// BEGIN FCKEDITOR
		$oFCKeditor = new FCKeditor('html_signature') ;
		$oFCKeditor->BasePath 	= '../prototype/library/fckeditor/';
		$oFCKeditor->ToolbarSet = 'ExpressoLivre';
		if(is_array($GLOBALS['phpgw_info']['user']['preferences']['expressoMail'])) {
			$oFCKeditor->Value 	= $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['signature'];
		}
		// First Time: The user has no preferences. If the template file exists, then it loads a default signature.
		// See signature_example.tpl
		elseif(file_exists($GLOBALS['phpgw']->template->root.'/signature.tpl')){
			$filein = fopen($GLOBALS['phpgw']->template->root.'/signature.tpl',"r");
			while (!feof ($filein))
				$oFCKeditor->Value .= fgets($filein, 1024); 
		}
		$oFCKeditor->Value = str_replace("{full_name}",$phpgw_info['user']['fullname'],$oFCKeditor->Value);
		$oFCKeditor->Value = str_replace("{first_name}",$phpgw_info['user']['firstname'],$oFCKeditor->Value);
		
		$GLOBALS['phpgw']->template->set_var('rtf_signature',$oFCKeditor->Create());
		$GLOBALS['phpgw']->template->set_var('text_signature',strip_tags($oFCKeditor->Value));
		// END FCKEDITOR
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['hide_folders'])
			$GLOBALS['phpgw']->template->set_var('checked_menu','checked');
		else
			$GLOBALS['phpgw']->template->set_var('checked_menu','');

		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['line_height'])
			$GLOBALS['phpgw']->template->set_var('line_height_option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['line_height'].'_selected','selected');
		else
			$GLOBALS['phpgw']->template->set_var('line_height_option_20_selected','selected');
		
		if($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['font_size'])
			$GLOBALS['phpgw']->template->set_var('font_size_option_'.$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['font_size'].'_selected','selected');
		else
			$GLOBALS['phpgw']->template->set_var('font_size_option_11_selected','selected');
	    $c = CreateObject('phpgwapi.config','expressoMail1_2');
	    $c->read_repository();
	    $current_config = $c->config_data;
		
		if($current_config['enable_local_messages']!='True') {
			$GLOBALS['phpgw']->template->set_var('open_comment_local_messages_config',"<!--");
			$GLOBALS['phpgw']->template->set_var('close_comment_local_messages_config',"-->");
		}
		else {
			$GLOBALS['phpgw']->template->set_var('open_comment_local_messages_config'," ");
			$GLOBALS['phpgw']->template->set_var('close_comment_local_messages_config'," ");
		}
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages'])
			$GLOBALS['phpgw']->template->set_var('use_local_messages_option_Yes_selected','selected');
		else {
			$GLOBALS['phpgw']->template->set_var('use_local_messages_option_No_selected','');
			$GLOBALS['phpgw']->template->set_var('use_local_messages_option_Yes_selected','');
		}
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['keep_archived_messages'])
			$GLOBALS['phpgw']->template->set_var('keep_archived_messages_option_Yes_selected','selected');
		else {
			$GLOBALS['phpgw']->template->set_var('keep_archived_messages_option_No_selected','');
			$GLOBALS['phpgw']->template->set_var('keep_archived_messages_option_Yes_selected','');
		}		
		
	}
	else //Save Config
	{
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['max_email_per_page'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','max_email_per_page',$_POST['max_emails_per_page']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','max_email_per_page',$_POST['max_emails_per_page']);
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['save_deleted_msg'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','save_deleted_msg',$_POST['save_deleted_msg']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','save_deleted_msg',$_POST['save_deleted_msg']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','delete_trash_messages_after_n_days',$_POST['delete_trash_messages_after_n_days']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','delete_trash_messages_after_n_days',$_POST['delete_trash_messages_after_n_days']);
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','delete_spam_messages_after_n_days',$_POST['delete_spam_messages_after_n_days']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','delete_spam_messages_after_n_days',$_POST['delete_spam_messages_after_n_days']);
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['delete_and_show_previous_message'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','delete_and_show_previous_message',$_POST['delete_and_show_previous_message']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','delete_and_show_previous_message',$_POST['delete_and_show_previous_message']);
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['alert_new_msg'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','alert_new_msg',$_POST['alert_new_msg']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','alert_new_msg',$_POST['alert_new_msg']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['mainscreen_showmail'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','mainscreen_showmail',$_POST['mainscreen_showmail']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','mainscreen_showmail',$_POST['mainscreen_showmail']);

		if (!is_numeric($_SESSION['phpgw_info']['server']['expressomail']['expressoMail_Number_of_dynamic_contacts'])) 
		{										
			$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','');
			$GLOBALS['phpgw']->template->set_var('checked_dynamic_contacts','disabled');
		}
		else
		{
			if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_dynamic_contacts'])
			{
				$GLOBALS['phpgw']->preferences->change('expressoMail','use_dynamic_contacts',$_POST['use_dynamic_contacts']);
				if($_POST['use_dynamic_contacts'] == '')
				{
					$contacts = CreateObject('expressoMail1_2.dynamic_contacts');
					$contacts->delete_dynamic_contacts();
				}
			}
			else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_dynamic_contacts',$_POST['use_dynamic_contacts']);
		}

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_shortcuts'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_shortcuts',$_POST['use_shortcuts']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_shortcuts',$_POST['use_shortcuts']);
			
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['auto_save_draft'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','auto_save_draft',$_POST['auto_save_draft']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','auto_save_draft',$_POST['auto_save_draft']);	

        if($GLOBALS['phpgw_info']['server']['use_assinar_criptografar'])
		{
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital_cripto'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_signature_digital_cripto',$_POST['use_signature_digital_cripto']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_signature_digital_cripto',$_POST['use_signature_digital_cripto']);
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_digital'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_signature_digital',$_POST['use_signature_digital']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_signature_digital',$_POST['use_signature_digital']);
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature_cripto'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_signature_cripto',$_POST['use_signature_cripto']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_signature_cripto',$_POST['use_signature_cripto']);
		}
		// Insert new expressoMail preference use_signature: defines if the signature will be automatically inserted
		// at the e-mail body
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_signature'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_signature',$_POST['use_signature']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_signature',$_POST['use_signature']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['signature'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','signature',$_POST['signature']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','signature',$_POST['signature']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['type_signature'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','type_signature',$_POST['type_signature']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','type_signature',$_POST['type_signature']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['hide_folders'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','hide_folders',$_POST['check_menu']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','hide_folders',$_POST['check_menu']);
			
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','save_in_folder',$_POST['save_in_folder']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','save_in_folder',$_POST['save_in_folder']);

		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['line_height'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','line_height',$_POST['line_height']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','line_height',$_POST['line_height']);
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['font_size'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','font_size',$_POST['font_size']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','font_size',$_POST['font_size']);		
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['use_local_messages'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','use_local_messages',$_POST['use_local_messages']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','use_local_messages',$_POST['use_local_messages']);
			
		if ($GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['keep_archived_messages'])
			$GLOBALS['phpgw']->preferences->change('expressoMail','keep_archived_messages',$_POST['keep_archived_messages']);
		else
			$GLOBALS['phpgw']->preferences->add('expressoMail','keep_archived_messages',$_POST['keep_archived_messages']);			

		$GLOBALS['phpgw']->preferences->save_repository();
		
		// Back to preferences.
		$url = ($GLOBALS['phpgw']->link('/'.'expressoMail1_2'.'/save_preferences.php'));
		$GLOBALS['phpgw']->redirect($url);
	}
	
	$GLOBALS['phpgw']->common->phpgw_header();
	print parse_navbar();

	$GLOBALS['phpgw']->template->set_file(array(
		'expressoMail_prefs' => 'preferences.tpl'
	));

	$GLOBALS['phpgw']->template->set_var('lang_config_expressoMail',lang('Config for ExpressoMail'));
	$GLOBALS['phpgw']->template->set_var('lang_max_emails_per_page',lang('What is the maximum number of messages per page?'));
	$GLOBALS['phpgw']->template->set_var('lang_save_deleted_msg',lang('Save deleted messages in trash folder?'));
	$GLOBALS['phpgw']->template->set_var('lang_delete_trash_messages_after_n_days',lang('Delete trash messages after how many days?'));
	$GLOBALS['phpgw']->template->set_var('lang_delete_spam_messages_after_n_days',lang('Delete spam messages after how many days?'));
	$GLOBALS['phpgw']->template->set_var('lang_delete_and_show_previous_message',lang('Show previous message, after delete actual message?'));
	$GLOBALS['phpgw']->template->set_var('lang_alert_new_msg',lang('Do you wanna receive an alert for new messages?'));
	$GLOBALS['phpgw']->template->set_var('lang_hook_home',lang('Show default view on main screen?'));
	$GLOBALS['phpgw']->template->set_var('lang_save_in_folder',lang('Save sent messages in folder'));
	$GLOBALS['phpgw']->template->set_var('lang_hide_menu',lang('Hide menu folders?'));
	$GLOBALS['phpgw']->template->set_var('lang_line_height',lang('What is the height of the lines in the list of messages?'));
	$GLOBALS['phpgw']->template->set_var('lang_font_size',lang('What the font size in the list of messages?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_dynamic_contacts',lang('Use dynamic contacts?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_shortcuts',lang('Use shortcuts?'));
	$GLOBALS['phpgw']->template->set_var('lang_auto_save_draft',lang('Auto save draft'));
	$GLOBALS['phpgw']->template->set_var('lang_signature',lang('Signature'));
	$GLOBALS['phpgw']->template->set_var('lang_none',lang('None'));
	$GLOBALS['phpgw']->template->set_var('one_day',lang('1 Day'));
	$GLOBALS['phpgw']->template->set_var('two_days',lang('2 Days'));
	$GLOBALS['phpgw']->template->set_var('three_days',lang('3 Days'));
	$GLOBALS['phpgw']->template->set_var('four_days',lang('4 Days'));
	$GLOBALS['phpgw']->template->set_var('five_days',lang('5 Day'));
	$GLOBALS['phpgw']->template->set_var('small',lang('Small'));
	$GLOBALS['phpgw']->template->set_var('medium',lang('Medium'));
	$GLOBALS['phpgw']->template->set_var('normal',lang('Normal'));
	$GLOBALS['phpgw']->template->set_var('simple_text',lang('Simple Text'));
	$GLOBALS['phpgw']->template->set_var('html_text',lang('Rich Text'));
	$GLOBALS['phpgw']->template->set_var('lang_config_signature',lang('Signature Configuration'));
	$GLOBALS['phpgw']->template->set_var('lang_type_signature',lang('Signature type'));
	$GLOBALS['phpgw']->template->set_var('big',lang('Big'));
    //$GLOBALS['phpgw']->template->set_var('lang_use_signature_digital_cripto',lang('Possibilitar <b>assinar/criptografar</b> digitalmente a mensagem?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_signature_digital_cripto',lang('Enable digitally sign/cipher the message?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_signature_digital',lang('Always sign message digitally?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_signature_cripto',lang('Always cipher message digitally?'));
	$GLOBALS['phpgw']->template->set_var('lang_use_signature',lang('Insert signature automatically in new messages?'));
	$GLOBALS['phpgw']->template->set_var('lang_signature',lang('Signature'));
	$GLOBALS['phpgw']->template->set_var('lang_Would_you_like_to_keep_archived_messages_?',lang('Would you like to keep archived messages?'));
	$GLOBALS['phpgw']->template->set_var('lang_Yes',lang('Yes'));
	$GLOBALS['phpgw']->template->set_var('lang_No',lang('No'));
	$GLOBALS['phpgw']->template->set_var('lang_Would_you_like_to_use_local_messages_?',lang('Would you like to use local messages?'));

	$GLOBALS['phpgw']->template->set_var('url_offline','offline.php');
	$GLOBALS['phpgw']->template->set_var('url_icon','templates/default/images/offline.png');
	$GLOBALS['phpgw']->template->set_var('user_uid',$GLOBALS['phpgw_info']['user']['account_id']);
	$GLOBALS['phpgw']->template->set_var('user_login',$GLOBALS['phpgw_info']['user']['account_lid']);
	$GLOBALS['phpgw']->template->set_var('lang_install_offline',lang('Install Offline'));
	$GLOBALS['phpgw']->template->set_var('lang_pass_offline',lang('Offline Pass'));
	$GLOBALS['phpgw']->template->set_var('lang_expresso_offline',lang('Expresso Offline'));
	$GLOBALS['phpgw']->template->set_var('lang_uninstall_offline',lang('Uninstall Offline'));
	$GLOBALS['phpgw']->template->set_var('lang_gears_redirect',lang('To use local messages you have to install google gears. Would you like to be redirected to gears installation page?'));
	$GLOBALS['phpgw']->template->set_var('lang_offline_installed',lang('Offline success installed'));
	$GLOBALS['phpgw']->template->set_var('lang_offline_uninstalled',lang('Offline success uninstalled'));
	$GLOBALS['phpgw']->template->set_var('lang_only_spaces_not_allowed',lang('The password cant have only spaces'));
	
	$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
	$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];
	$e_server = $_SESSION['phpgw_info']['expressomail']['email_server'];
 	$imap = CreateObject('expressoMail1_2.imap_functions');	
	$save_in_folder_selected = $GLOBALS['phpgw_info']['user']['preferences']['expressoMail']['save_in_folder'];	
	// Load Special Folders (Sent, Trash, Draft, Spam) from EmailAdmin (if exists, else get_lang)
	$specialFolders = array ("Trash" => lang("Trash"), "Drafts" => lang("Drafts"), "Spam" => lang("Spam"), "Sent" => lang("Sent"));	
	foreach ($specialFolders as $key => $value){
		if($e_server['imapDefault'.$key.'Folder'])
			$specialFolders[$key] = $e_server['imapDefault'.$key.'Folder'];
	}        
	// First access on ExpressoMail, load default preferences...
	if(!$GLOBALS['phpgw_info']['user']['preferences']['expressoMail']) {
		$GLOBALS['phpgw']->template->set_var('checked_save_deleted_msg','checked');
		$GLOBALS['phpgw']->template->set_var('checked_delete_and_show_previous_message','checked');
		$GLOBALS['phpgw']->template->set_var('checked_alert_new_msg','checked');
		$GLOBALS['phpgw']->template->set_var('checked_use_signature','checked');
		$GLOBALS['phpgw']->template->set_var('checked_mainscreen_showmail','checked');
		$save_in_folder_selected = "INBOX".$e_server['imapDelimiter'].$specialFolders["Sent"];
	}
	$o_folders = "<option value='-1' ".(!$save_in_folder_selected ? 'selected' : '' ).">".lang("Select on send")."</option>";	
	
	foreach($imap -> get_folders_list() as $id => $folder){
		// Ignores numeric indexes and shared folders....
		if(!is_numeric($id) || (strstr($folder['folder_id'],"user".$e_server['imapDelimiter'])))
			continue;
		// Translate INBOX (root folder)
		elseif (strtolower($folder['folder_name']) == "inbox") 
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
		$o_folders.= "<option value='".$folder['folder_id']."' ".($save_in_folder_selected == $folder['folder_id'] ? 'selected' : '' ).">".$ident.$folder['folder_name']."</option>";			
	}

	$GLOBALS['phpgw']->template->set_var('value_save_in_folder',$o_folders);
	$GLOBALS['phpgw']->template->set_var('lang_save',lang('Save'));
	$GLOBALS['phpgw']->template->set_var('lang_cancel',lang('Cancel'));
	
	$GLOBALS['phpgw']->template->set_var('save_action',$GLOBALS['phpgw']->link('/'.'expressoMail1_2'.'/preferences.php'));
	$GLOBALS['phpgw']->template->set_var('th_bg',$GLOBALS['phpgw_info']["theme"][th_bg]);

	$tr_color = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($tr_color);
	$GLOBALS['phpgw']->template->set_var('tr_color1',$GLOBALS['phpgw_info']['theme']['row_on']);
	$GLOBALS['phpgw']->template->set_var('tr_color2',$GLOBALS['phpgw_info']['theme']['row_off']);

	$GLOBALS['phpgw']->template->parse('out','expressoMail_prefs',True);
	$GLOBALS['phpgw']->template->p('out');
	// Com o Módulo do IM habilitado, ocorre um erro no IE
	//$GLOBALS['phpgw']->common->phpgw_footer();
