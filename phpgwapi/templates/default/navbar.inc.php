<?php
/**************************************************************************\
* phpGroupWare                                                             *
* http://www.phpgroupware.org                                              *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

function parse_navbar($force = False)
{
	$GLOBALS['celepar_tpl'] = createobject('phpgwapi.Template',PHPGW_TEMPLATE_DIR);
	$GLOBALS['celepar_tpl']->set_file(array('navbar' => 'navbar.tpl'));
	
	$GLOBALS['celepar_tpl']->set_block('navbar','navbar_header','navbar_header');
	$GLOBALS['celepar_tpl']->set_block('navbar','extra_blocks_header','extra_block_header');
	$GLOBALS['celepar_tpl']->set_block('navbar','extra_block_row','extra_block_row');
	$GLOBALS['celepar_tpl']->set_block('navbar','extra_block_spacer','extra_block_spacer');
	$GLOBALS['celepar_tpl']->set_block('navbar','extra_blocks_footer','extra_blocks_footer');
	$GLOBALS['celepar_tpl']->set_block('navbar','sidebox_hide_header','sidebox_hide_header');
	$GLOBALS['celepar_tpl']->set_block('navbar','sidebox_hide_footer','sidebox_hide_footer');
	$GLOBALS['celepar_tpl']->set_block('navbar','appbox','appbox');
	$GLOBALS['celepar_tpl']->set_block('navbar','navbar_footer','navbar_footer');
	
	$GLOBALS['celepar_tpl']->set_var('my_preferences', lang("My Preferences"));
	$GLOBALS['celepar_tpl']->set_var('title_my_preferences', lang("Click here to change your Expresso password and other preferences"));
	$GLOBALS['celepar_tpl']->set_var('title_suggestions', lang("Send your critics, doubts or suggestions"));
	$GLOBALS['celepar_tpl']->set_var('suggestions', lang("Suggestions"));
	$GLOBALS['celepar_tpl']->set_var('help', lang("Help"));
	$GLOBALS['celepar_tpl']->set_var('title_help', lang("Click here for help"));
	$GLOBALS['celepar_tpl']->set_var('template',$GLOBALS['phpgw_info']['server']['template_set']);
	
	$var['img_root'] = $GLOBALS['phpgw_info']['server']['webserver_url'] . '/phpgwapi/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images';
	$var['dir_root'] = $GLOBALS['phpgw_info']['server']['webserver_url'];
	$var['table_bg_color'] = $GLOBALS['phpgw_info']['theme']['navbar_bg'];
	
	if ($GLOBALS['phpgw_info']['user']['preferences']['common']['click_or_onmouseover']=='onmouseover')
		$show_menu_event = 'onMouseOver';
	else
		$show_menu_event = 'onClick';
	
	$applications = '';
	
	$max_icons=$GLOBALS['phpgw_info']['user']['preferences']['common']['max_icons'];
	
	if (!$max_icons) $max_icons=200;
	
	$i = 0;
	
	foreach ($GLOBALS['phpgw_info']['navbar'] as $app => $app_data) {
		
		if( array_search( intval($_SERVER['SERVER_PORT']), array( 80, 443 ) ) !== false ){
			$app_data['url'] = ( ( $GLOBALS['phpgw_info']['server']['use_https'] == 2 ) ? "https" : "http" )."://".$_SERVER['SERVER_NAME'].$app_data['url'];
		} else {
			$app_data['url'] = ( ( $GLOBALS['phpgw_info']['server']['use_https'] == 2 ) ? "https" : "http" )."://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'] . $app_data['url'];
		}

		$current_app = False;
		if ($app != 'preferences' && $app != 'about' && $app != 'logout') {
			
			$icon = '<a href="'.$app_data['url'].'">';
			$title = $GLOBALS['phpgw_info']['apps'][$app]['title'];
			if ($app == 'home') $title = lang('Home');
			if ($GLOBALS['phpgw_info']['flags']['currentapp'] == $app) {
				
				$icon .= '<img id="'.str_replace('_','',$app).'id" src="' . $app_data['icon'] . '" alt="' . $title . '" title="'. $title . '" border="0" width="35" height="35" nowrap="nowrap"/></a>';
				$current_app = True;
				
			} else $icon .= '<img id="'.str_replace('_','',$app).'id" src="' . $app_data['icon'] . '" alt="' . $title . '" title="'. $title . '" border="0" width="24" height="24" nowrap="nowrap"/></a>';
			
			if ($app=='home' && $GLOBALS['phpgw_info']['user']['preferences']['common']['start_and_logout_icons']=='no')
				continue;
			
			// Verifica qual o formato da Barra de navega��o: 
			// Icone ou Texto, ou Icone e Texto
			
			if ($GLOBALS['phpgw_info']['user']['preferences']['common']['navbar_format']=='icons') {
				
				$title_extra = $title;
				$title = '';
				
			} else if ($GLOBALS['phpgw_info']['user']['preferences']['common']['navbar_format']=='text')
				$icon = '';
			
			if ($i< $max_icons) {
				
				if ($GLOBALS['phpgw_info']['user']['preferences']['common']['start_and_logout_icons']=='no')
					$tdwidth = 100/($max_icons);
				else
					$tdwidth = 100/($max_icons+2);
				
				$app_icons .= '<td nowrap '.
					'onmouseover="javascript:'.($current_app?'return true;':'').'this.className=\'navbar_butOver\'; zoom_in(this.firstChild.firstChild.id)" '.
					'onmouseout="javascript:'.($current_app?'return true;':'').'this.className=\'navbar_but\'; '.($current_app?'':'zoom_out(this.firstChild.firstChild.id)').'" '.
					'class="'.($current_app?'navbar_butOut':'navbar_but').'">';
				
				if (isset($GLOBALS['phpgw_info']['flags']['navbar_target']) && $GLOBALS['phpgw_info']['flags']['navbar_target'])
					$app_icons .= ' target="' . $GLOBALS['phpgw_info']['flags']['navbar_target'] . '"';
				
				$app_icons .=  $icon;
				
			}
			
			$icon = '<img align="center" src="' . $app_data['icon'] . '" alt="' . $title . '" width="16" title="'. $title . '" border="0" />';
			
			$app_extra_icons .= '<tr>';
			
			if ($GLOBALS['phpgw_info']['user']['preferences']['common']['navbar_format']!='text') {
				
				$app_extra_icons .= '<td class="extraIconsRow"><a href="' . $app_data['url'] . '"';
				
				if (isset($GLOBALS['phpgw_info']['flags']['navbar_target']) && $GLOBALS['phpgw_info']['flags']['navbar_target'])
					$app_extra_icons .= ' target="' . $GLOBALS['phpgw_info']['flags']['navbar_target'] . '"';
				
				$app_extra_icons .= ' >' . $icon . '</a></td>';
				
			}
			
			$app_extra_icons .= '<td align="left" class="extraIconsRow" style=""><a href="'.$app_data['url'] . '"';
			
			if (isset($GLOBALS['phpgw_info']['flags']['navbar_target']) && $GLOBALS['phpgw_info']['flags']['navbar_target'])
				$app_extra_icons .= ' target="' . $GLOBALS['phpgw_info']['flags']['navbar_target'] . '"';
			
			$app_extra_icons .= '>' . $title_extra . '</a></td></tr>';
			
			unset($icon);
			unset($title);
			$i++;
		}
	}
	
	if ($GLOBALS['phpgw_info']['user']['preferences']['common']['start_and_logout_icons']!='no') {
		
		$hint = $GLOBALS['phpgw_info']['navbar']['logout']['title'];
		$icon = '<img id="logout_id" width="24" height="24" src="'.$GLOBALS['phpgw_info']['navbar']['logout']['icon'].'"  alt="'.$hint.'" title="'.$hint.'">';
		
		if ($GLOBALS['phpgw_info']['user']['preferences']['common']['navbar_format']=='icons')
			$title = '';
		else if ($GLOBALS['phpgw_info']['user']['preferences']['common']['navbar_format']=='text')
			$icon = '';
		
		$app_icons .= '<td nowrap '.
			'onmouseover="javascript:this.className=\'navbar_butOver\'; zoom_in(this.firstChild.firstChild.id)" '.
			'onmouseout="javascript:this.className=\'navbar_but\'; zoom_out(this.firstChild.firstChild.id)" '.
			'class="navbar_but"><a onclick="location.href=\''.$GLOBALS['phpgw_info']['navbar']['logout']['url'].'\'">'.$icon.'</td>';
	}
	
	$var['app_icons'] = $app_icons;
	
	$hint = $GLOBALS['phpgw_info']['navbar']['logout']['title'];
	$icon = '<img src="'.$GLOBALS['phpgw_info']['navbar']['logout']['icon'].'"  alt="'.$hint.'" title="'.$hint.'">';
	$title = $GLOBALS['phpgw_info']['navbar']['logout']['title'];
	
	$app_extra_icons .= '<td  class="extraIconsRow">'.
		'<a href="'.$GLOBALS['phpgw_info']['navbar']['logout']['url'].'">'.$icon.'</a></td>';
	
	$app_extra_icons .= '<td class="extraIconsRow">'.
		'<a href="'.$GLOBALS['phpgw_info']['navbar']['logout']['url'].'">'.$title.'</a></td>';
	
	$app_extra_icons_div = '
		<script language="javascript">
			new ypSlideOutMenu("menu1", "down", 5, 24, 160, 200,\'left\')
		</script>
		<div id="menu1Container">
			<div id="menu1Content" style="position: relative; left: 0; text-align: left;">
				<div id="extraIcons">
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td colspan="2" nowrap="nowrap" align="right" style="background-color:#dddddd;padding:1px;">
								<a href="#" '.$show_menu_event.'="ypSlideOutMenu.hide(\'menu1\')" title="'.lang('close').'">
									<img style="" border="0" src="'.$var['img_root'].'/close.png"/>
								</a>
							</td>
						</tr>
						<tr>
							<td colspan="1" valign="top" nowrap="nowrap" align="left" style="background-color:#dddddd;padding:1px;">
								<a href="#" onClick="javascript:ypSlideOutMenu.hide(\'menu1\');showBar()" title="'.lang('Expand Menu').'">
									<img src = "'.$var['img_root'].'/down.button.png">
								</a>
							</td>
							<td colspan="1" valign="top" nowrap="nowrap" align="left" style="background-color:#dddddd;padding:1px;">
								<a href="#" onClick="javascript:ypSlideOutMenu.hide(\'menu1\');showBar()" title="'.lang('Expand Menu').'">
									<b>'.lang('Expand Menu').'</b>
								</a>
							</td>
						</tr>
						'.$app_extra_icons.'
					</table>
				</div>
			</div>
		</div>
	';
	
	$var['app_extra_icons_div']= $app_extra_icons_div;
	$var['app_extra_icons_icon']= '<td width="26" valign="top" align="right" style="zIndex:10000;padding-right:3px;padding-top:10px;"><a title="'.lang('show_more_apps').'" href="#"  '.$show_menu_event.'="ypSlideOutMenu.showMenu(\'menu1\')"><img src="'.$var['img_root'].'/extra_icons.png" border="0" /></a></td>';
	
	if (isset($GLOBALS['phpgw_info']['flags']['app_header']))
		$var['current_app_title'] = $GLOBALS['phpgw_info']['flags']['app_header'];
	else
		$var['current_app_title']=$GLOBALS['phpgw_info']['navbar'][$GLOBALS['phpgw_info']['flags']['currentapp']]['title'];
	
	if (isset($GLOBALS['phpgw_info']['navbar']['admin']) && $GLOBALS['phpgw_info']['user']['preferences']['common']['show_currentusers']) {
		$var['current_users'] = '<a href="'
			. $GLOBALS['phpgw']->link('/index.php','menuaction=admin.uicurrentsessions.list_sessions') . '">'
			. lang('Current users') . ': ' . $GLOBALS['phpgw']->session->total() . '</a>';
	}
	
	$now = time();
	
	$var['user_info'] = '<b>'.$GLOBALS['phpgw']->common->display_fullname() .'</b>'. ' - '
		. lang($GLOBALS['phpgw']->common->show_date($now,'l')) . ' '
		. $GLOBALS['phpgw']->common->show_date($now,$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
	
	if ($GLOBALS['phpgw_info']['server']['use_frontend_name'])
		$var['frontend_name'] = " - ".$GLOBALS['phpgw_info']['server']['use_frontend_name'];
	
	/*
	 *	For�ar termo de aceite por parte do usu�rio
	 */
	if (($GLOBALS['phpgw_info']['user']['agree_terms'] != 1) && ($GLOBALS['phpgw_info']['server']['use_agree_term']=='True')) {
		
		$agreeterm_url = $_SERVER['HTTP_HOST'] . $GLOBALS['phpgw_info']['server']['webserver_url'] . '/preferences/termo_aceite.php';
		
		if ($GLOBALS['phpgw_info']['server']['use_https'] == 2)
			$agreeterm_url = 'https://' . $agreeterm_url;
		else
			$agreeterm_url = 'http://' . $agreeterm_url;
		
		echo '<script>' .
				'if (location.href.indexOf("termo_aceite.php") == -1){' .
					'location.href = "' . $agreeterm_url . '"' .
				'}' .
			'</script>';
	}
	
	$pwd_age = ( $GLOBALS['phpgw_info']['server']['max_pwd_age'] && $GLOBALS['phpgw_info']['server']['max_pwd_age'] > 0 )?
		time() - ( 86400 * $GLOBALS['phpgw_info']['server']['max_pwd_age'] ) : 0;
	
	if (
		$GLOBALS['phpgw_info']['user']['lastpasswd_change'] < $pwd_age || (
		$GLOBALS['phpgw_info']['user']['lastpasswd_change'] == '0' &&
			( $GLOBALS['phpgw_info']['user']['agree_terms'] == 1 || $GLOBALS['phpgw_info']['server']['use_agree_term'] != 'True' )
		)
	) {
		
		$changepasswd_url = nearest_to_me(). $GLOBALS['phpgw_info']['server']['webserver_url'].'/preferences/changepassword.php?cd=1';
		if ($GLOBALS['phpgw_info']['server']['use_https'] > 0)
			$changepasswd_url = 'https://' . $changepasswd_url;
		else
			$changepasswd_url = 'http://' . $changepasswd_url;
		
		echo '<script>' .
				'if (location.href.indexOf("changepassword.php") == -1){' .
					'location.href = "' . $changepasswd_url . '"' .
				'}' .
			 '</script>';
		/*
		$api_messages = lang('You are required to change your password during your first login')
		. '<br> Click this image on the navbar: <img src="'
		. $GLOBALS['phpgw']->common->image('preferences','navbar.gif').'">';
		*/
	}
	/*
	elseif ($GLOBALS['phpgw_info']['user']['lastpasswd_change'] < time() - (86400*30))
		$api_messages = lang('it has been more then %1 days since you changed your password',30);
	*/
	/*
	// This is gonna change
	if (isset($cd))
		$var['messages'] = $api_messages . '<br>' . checkcode($cd);
	*/
	
	$var['logo_file'] = $GLOBALS['phpgw']->common->image('phpgwapi',$GLOBALS['phpgw_info']['server']['login_logo_file']?$GLOBALS['phpgw_info']['server']['login_logo_file']:'logo');
	$var['logo_url'] = $GLOBALS['phpgw_info']['server']['login_logo_url']?$GLOBALS['phpgw_info']['server']['login_logo_url']:'http://www.eGroupWare.org';
	$var['logo_title'] = $GLOBALS['phpgw_info']['server']['login_logo_title']?$GLOBALS['phpgw_info']['server']['login_logo_title']:'www.eGroupWare.org';
	$var['hide_bar_txt'] = lang("Hide header and toolbar");
	$var['show_bar_txt'] = lang("Show header and toolbar");
	$GLOBALS['celepar_tpl']->set_var($var);
	$GLOBALS['celepar_tpl']->pfp('out','navbar_header');
	
	/******************************************************\
	* The sidebox menu's                                   *
	\******************************************************/
	
	$menu_title = lang('General Menu');
	
	$file['Home'] = $GLOBALS['phpgw_info']['navbar']['home']['url'];
	if ($GLOBALS['phpgw_info']['user']['apps']['preferences'])
		$file['Preferences'] = $GLOBALS['phpgw_info']['navbar']['preferences']['url'];
	
	$file += array(
		array(
			'text'		=> lang('About %1',$GLOBALS['phpgw_info']['apps'][$GLOBALS['phpgw_info']['flags']['currentapp']]['title']),
			'no_lang'	=> True,
			'link'		=> $GLOBALS['phpgw_info']['navbar']['about']['url']
		),
		'Logout'=>$GLOBALS['phpgw_info']['navbar']['logout']['url']
	);
	
	if ($GLOBALS['phpgw_info']['user']['preferences']['common']['auto_hide_sidebox']==1) {
		
		$GLOBALS['celepar_tpl']->set_var('show_menu_event',$show_menu_event);
		$GLOBALS['celepar_tpl']->set_var('lang_show_menu',lang('show menu'));
		$GLOBALS['celepar_tpl']->pparse('out','sidebox_hide_header');
		
		display_sidebox('',$menu_title,$file);
		$GLOBALS['phpgw']->hooks->single('sidebox_menu',$GLOBALS['phpgw_info']['flags']['currentapp']);
		
		$GLOBALS['celepar_tpl']->pparse('out','sidebox_hide_footer');
		
		$var['sideboxcolstart']='';
		
		$GLOBALS['celepar_tpl']->set_var($var);
		$GLOBALS['celepar_tpl']->pparse('out','appbox');
		$var['remove_padding'] = 'style="padding-left:0px;"';
		$var['sideboxcolend'] = '';
		
	} else {
		
		$var['menu_link'] = '';
		$var['sideboxcolstart'] = '<td id="tdSidebox" valign="top">';
		$var['remove_padding'] = '';
		$GLOBALS['celepar_tpl']->set_var($var);
		$GLOBALS['celepar_tpl']->pparse('out','appbox');
		
		display_sidebox('',$menu_title,$file);
		$GLOBALS['phpgw']->hooks->single('sidebox_menu',$GLOBALS['phpgw_info']['flags']['currentapp']);
		
		$var['sideboxcolend'] = '</td>';
		
	}
	
	$GLOBALS['celepar_tpl']->set_var($var);
	$GLOBALS['celepar_tpl']->pparse('out','navbar_footer');
	
	// If the application has a header include, we now include it
	if (!@$GLOBALS['phpgw_info']['flags']['noappheader'] && @isset($_GET['menuaction'])) {
		
		list($app,$class,$method) = explode('.',$_GET['menuaction']);
		if (is_array($GLOBALS[$class]->public_functions) && $GLOBALS[$class]->public_functions['header'])
			$GLOBALS[$class]->header();
		
	}
	$GLOBALS['phpgw']->hooks->process('after_navbar');
	return;
}

function display_sidebox($appname,$menu_title,$file)
{
	
	if (!$appname || ($appname==$GLOBALS['phpgw_info']['flags']['currentapp'] && $file)) {
		
		$var['lang_title']=$menu_title;//$appname.' '.lang('Menu');
		$GLOBALS['celepar_tpl']->set_var($var);
		$GLOBALS['celepar_tpl']->pfp('out','extra_blocks_header');
		
		foreach($file as $text => $url)
			sidebox_menu_item($url,$text);
		
		$GLOBALS['celepar_tpl']->pparse('out','extra_blocks_footer');
		
	}
}

function sidebox_menu_item($item_link='',$item_text='')
{
	
	if ($item_text === '_NewLine_' || $item_link === '_NewLine_')
		$GLOBALS['celepar_tpl']->pparse('out','extra_block_spacer');
	else {
		
		$var['icon_or_star']='<img src="'.$GLOBALS['phpgw_info']['server']['webserver_url'] . '/phpgwapi/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images'.'/orange-ball.png" width="9" height="9" alt="ball"/>';
		$var['target'] = '';
		if (is_array($item_link)) {
			if (isset($item_link['icon'])) {
				
				$app = isset($item_link['app']) ? $item_link['app'] : $GLOBALS['phpgw_info']['flags']['currentapp'];
				$var['icon_or_star'] = '<img src="'.$GLOBALS['phpgw']->common->image($app,$item_link['icon']).'"/>';
				
			}
			
			$var['lang_item'] = isset($item_link['no_lang']) && $item_link['no_lang'] ? $item_link['text'] : lang($item_link['text']);
			$var['item_link'] = $item_link['link'];
			if ($item_link['target'])
				$var['target'] = ' target="' . $item_link['target'] . '"';
			
		} else {
			
			$var['lang_item'] = lang($item_text);
			$var['item_link'] = $item_link;
			
		}
		
		$GLOBALS['celepar_tpl']->set_var($var);
		$GLOBALS['celepar_tpl']->pparse('out','extra_block_row');
		
	}
}

function parse_navbar_end()
{
	$GLOBALS['celepar_tpl'] = createobject('phpgwapi.Template',PHPGW_TEMPLATE_DIR);
	$GLOBALS['celepar_tpl']->set_file(array('footer' => 'footer.tpl'));
	
	$var = Array(
		'img_root'			=> $GLOBALS['phpgw_info']['server']['webserver_url'] . '/phpgwapi/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images',
		'table_bg_color'	=> $GLOBALS['phpgw_info']['theme']['navbar_bg'],
		'version'			=> $GLOBALS['phpgw_info']['server']['versions']['phpgwapi']
	);
	
	$GLOBALS['phpgw']->hooks->process('navbar_end');
	
	if ($GLOBALS['phpgw_info']['user']['preferences']['common']['show_generation_time']) {
		
		$mtime = microtime(); 
		$mtime = explode(' ',$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$tend = $mtime; 
		$totaltime = ($tend - $GLOBALS['page_start_time']); 
		
		$var['page_generation_time'] = '<div id="divGenTime"><br/><span>'.lang('Page was generated in %1 seconds',$totaltime).'</span></div>';
		
	}
	
	$var['powered_by'] = lang('Powered by phpGroupWare version %1',$GLOBALS['phpgw_info']['server']['versions']['phpgwapi']);
	$GLOBALS['celepar_tpl']->set_var($var);
	$GLOBALS['celepar_tpl']->pfp('out','footer');
	
}
