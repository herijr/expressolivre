<?php
  /**************************************************************************\
  * eGroupWare - Admin config                                                *
  * Written by Miles Lott <milosch@phpwhere.org>                             *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	class uiconfig
	{
		var $public_functions = array('index' => True);

		function index()
		{
			if ($GLOBALS['phpgw']->acl->check('site_config_access',1,'admin'))
			{
				$GLOBALS['phpgw']->redirect_link('/index.php');
			}

			if(get_magic_quotes_gpc() && is_array($_POST['newsettings']))
			{
				$_POST['newsettings'] = array_map("stripslashes", $_POST['newsettings']);
			}
			
			switch($_GET['appname'])
			{
				case 'admin':
				case 'addressbook':
				case 'calendar':
				case 'email':
				case 'nntp':
					/*
					Other special apps can go here for now, e.g.:
					case 'bogusappname':
					*/
					$appname = $_GET['appname'];
					$config_appname = 'phpgwapi';
					break;
				case 'phpgwapi':
				case '':
					/* This keeps the admin from getting into what is a setup-only config */
					$GLOBALS['phpgw']->redirect_link('/admin/index.php');
					break;
				default:
					$appname = $_GET['appname'];
					$config_appname = $appname;
					break;
			}

			$t = CreateObject('phpgwapi.Template',$GLOBALS['phpgw']->common->get_tpl_dir($appname));
			$t->set_unknowns('keep');
			$t->set_file(array('config' => 'config.tpl'));
			$t->set_block('config','header','header');
			$t->set_block('config','body','body');
			$t->set_block('config','footer','footer');

			$c = CreateObject('phpgwapi.config',$config_appname);
			$c->read_repository();

			if ($c->config_data)
			{
				$current_config = $c->config_data;
			}

			if ($_POST['cancel'] || $_POST['submit'] && $GLOBALS['phpgw']->acl->check('site_config_access',2,'admin'))
			{
				$GLOBALS['phpgw']->redirect_link('/admin/index.php');
			}

			if ($_POST['submit'])
			{
				/* Load hook file with functions to validate each config (one/none/all) */
				$GLOBALS['phpgw']->hooks->single('config_validate',$appname);
				
				if ( $appname === 'admin' && !isset($_POST['newsettings']['my_org_units']) ) $_POST['newsettings']['my_org_units'] = array();
				
				foreach($_POST['newsettings'] as $key => $config)
				{
					if ($config)
					{
						if($GLOBALS['phpgw_info']['server']['found_validation_hook'] && function_exists($key))
						{
							call_user_func($key,$config);
							if($GLOBALS['config_error'])
							{
								$errors .= lang($GLOBALS['config_error']) . '&nbsp;';
								$GLOBALS['config_error'] = False;
							}
							else
							{
								$c->config_data[$key] = $config;
							}
						}
						else
						{
							$c->config_data[$key] = $config;
						}
					}
					else
					{
						/* don't erase passwords, since we also don't print them */
						if(!preg_match('/passwd/',$key) && !preg_match('/password/',$key) && !preg_match('/root_pw/',$key))
						{
							unset($c->config_data[$key]);
						}
					}
				}
				if($GLOBALS['phpgw_info']['server']['found_validation_hook'] && function_exists('final_validation'))
				{
					final_validation($newsettings);
					if($GLOBALS['config_error'])
					{
						$errors .= lang($GLOBALS['config_error']) . '&nbsp;';
						$GLOBALS['config_error'] = False;
					}
					unset($GLOBALS['phpgw_info']['server']['found_validation_hook']);
				}

				$c->save_repository();

				if(!$errors)
				{
					$GLOBALS['phpgw']->redirect_link('/admin/index.php');
				}
			}

			if($errors)
			{
				$t->set_var('error',lang('Error') . ': ' . $errors);
				$t->set_var('th_err','#FF8888');
				unset($errors);
				unset($GLOBALS['config_error']);
			}
			else
			{
				$t->set_var('error','');
				$t->set_var('th_err',$GLOBALS['phpgw_info']['theme']['th_bg']);
			}

			if(!@is_object($GLOBALS['phpgw']->js))
			{
				$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			}
			$GLOBALS['phpgw']->js->add('src','./prototype/plugins/jquery/jquery-latest.min.js');
			$GLOBALS['phpgw']->js->add('src','./prototype/plugins/jquery/jquery-ui-latest.min.js');
			$GLOBALS['phpgw']->js->validate_file('jscode','openwindow','admin');
			$GLOBALS['phpgw']->js->validate_file('jscode','selectBox','admin');

			$GLOBALS['phpgw']->common->phpgw_header();
			echo parse_navbar();

			if($appname=="expressoAdmin1_2") {
				/* Varre a pasta inc do admin do expresso procurando scripts de geração de login automático
				   (classes com nomes iniciados pela string 'login', procedida da string '_' mais o nome
				   do algoritmo.
				*/
				
				$dir = $GLOBALS['phpgw']->common->get_app_dir($appname) . "/inc";
				$options = ' ';
				if (is_dir($dir))
				{
					if ($dh = opendir($dir))
					{
						while (($file = readdir($dh)) !== false)
						{
							$temp = explode(".",$file);
							if( (substr($temp[1],0,5) =='login') && ($temp[0] == 'class') )
							{
								$options .= "<option value='".$temp[1]."'";
								if($current_config['expressoAdmin_loginGenScript'] == $temp[1])
									$options .= " selected";
								$options .= ">" . ucwords(str_replace("_"," ",substr($temp[1],6))) . "</option>";
							}				
						}
						closedir($dh);
					}
				}
				
				$t->set_var('rows_login_generator',$options);
			}
			
			if($appname=="admin") {
				
				$ldap = CreateObject('admin.ldap_functions');
				$orgs_availables = $ldap->listOU();
				$orgs_selecteds = isset($current_config['my_org_units'])? (array)$current_config['my_org_units'] : array();
				foreach ( $orgs_selecteds as $key => $value) {
					if ( in_array($value, $orgs_availables) ) unset($orgs_availables[array_search($value, $orgs_availables)]);
					else unset($orgs_selecteds[$key]);
				}
				
				$t->set_var('orgs_availables',$this->_mkOptions($orgs_availables));
				$t->set_var('orgs_selecteds',$this->_mkOptions($orgs_selecteds));
				$t->set_var('orgs_selecteds_hidden',$this->_mkOptions($orgs_selecteds,$orgs_selecteds));
				
				/*
				 * FCK editor to agree term
				 */
				include_once("prototype/library/fckeditor/fckeditor.php");
				$oFCKeditor = new FCKeditor('newsettings[agree_term]');//CreateObject('news_admin.fckeditor','newsettings[agree_term]');
				$oFCKeditor->BasePath = 'prototype/library/fckeditor/'; 
				$oFCKeditor->ToolbarSet = 'Basic';
				$oFCKeditor->Value = isset($GLOBALS['phpgw_info']['server']['agree_term']) ? $GLOBALS['phpgw_info']['server']['agree_term'] : '';
				$t->set_var('agree_term_input',$oFCKeditor->Create());
			}
			$t->set_var('title',lang('Site Configuration'));
			$t->set_var('lang_Value_exceeds_the_PHP_upload_limit_for_this_server', lang('Value exceeds the PHP upload limit for this server'));
			$t->set_var('action_url',$GLOBALS['phpgw']->link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname));
			$t->set_var('th_bg',     $GLOBALS['phpgw_info']['theme']['th_bg']);
			$t->set_var('th_text',   $GLOBALS['phpgw_info']['theme']['th_text']);
			$t->set_var('row_on',    $GLOBALS['phpgw_info']['theme']['row_on']);
			$t->set_var('row_off',   $GLOBALS['phpgw_info']['theme']['row_off']);
			$t->set_var('php_upload_limit',str_replace('M','',ini_get('upload_max_filesize')));
			
			$t->pparse('out','header');

			$vars = $t->get_undefined('body');

			$GLOBALS['phpgw']->hooks->single('config',$appname);

			foreach($vars as $value)
			{
				$valarray = explode('_',$value);
				$type = array_shift($valarray);
				$newval = implode(' ',$valarray);
				switch ($type)
				{
					case 'lang':
						$t->set_var($value,lang($newval));
						break;
					case 'value':
						$newval = str_replace(' ','_',$newval);
						/* Don't show passwords in the form */
						if(preg_match('/passwd/',$value) || preg_match('/password/',$value) || preg_match('/root_pw/',$value))
						{
							$t->set_var($value,'');
						}
						else
						{
							$t->set_var($value,htmlspecialchars($current_config[$newval]));
						}
						break;
					/*
					case 'checked':
						$newval = str_replace(' ','_',$newval);
						if ($current_config[$newval])
						{
							$t->set_var($value,' checked');
						}
						else
						{
							$t->set_var($value,'');
						}
						break;
					*/
					case 'selected':
						$configs = array();
						$config  = '';
						$newvals = explode(' ',$newval);
						$setting = end($newvals);
						for ($i=0;$i<(count($newvals) - 1); $i++)
						{
							$configs[] = $newvals[$i];
						}
						$config = implode('_',$configs);
						/* echo $config . '=' . $current_config[$config]; */
						if ($current_config[$config] == $setting)
						{
							$t->set_var($value,' selected');
						}
						else
						{
							$t->set_var($value,'');
						}
						break;
					case 'hook':
						$newval = str_replace(' ','_',$newval);
						if(function_exists($newval))
						{
							$t->set_var($value,$newval($current_config));
						}
						else
						{
							$t->set_var($value,'');
						}
						break;
					default:
					$t->set_var($value,'');
					break;
				}
			}

			$t->pfp('out','body');

			$t->set_var('lang_submit', $GLOBALS['phpgw']->acl->check('site_config_access',2,'admin') ? lang('Cancel') : lang('Save'));
			$t->set_var('lang_cancel', lang('Cancel'));
			$t->pfp('out','footer');
		}
		
		function _mkOptions( $arr, $selected = array() )
		{
			$buf = '';
			sort($arr);
			foreach ( $arr as $value )
				$buf .= '<option value="'.$value.'"'.(in_array($value, $selected)?' selected="selected"':'').'>'.$value.'</option>';
			return $buf;
		}
	}
?>
