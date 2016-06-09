<?php
  /***************************************************************************\
  * eGroupWare - Contacts Center                                              *
  * http://www.egroupware.org                                                 *
  * Written by:                                                               *
  *  - Raphael Derosso Pereira <raphaelpereira@users.sourceforge.net>         *
  *  sponsored by Thyamad - http://www.thyamad.com
  * ------------------------------------------------------------------------- *
  *  This program is free software; you can redistribute it and/or modify it  *
  *  under the terms of the GNU General Public License as published by the    *
  *  Free Software Foundation; either version 2 of the License, or (at your   *
  *  option) any later version.                                               *
  \***************************************************************************/


	class ui_preferences
	{
		var $public_functions = array(
			'index'           => true,
			'set_preferences' => true,
		);
		
		function index()
		{
			$GLOBALS['phpgw_info']['flags']['app_header'] = lang('ContactCenter').' - '.lang('Preferences');
			$GLOBALS['phpgw']->common->phpgw_header();
			echo parse_navbar();

			$GLOBALS['phpgw']->template->set_file(array('pref' => 'preferences.tpl'));

			/* Get Saved Preferences */
			$actual = $this->get_preferences();
			
			/* Get the catalog options */
			$pCatalog = CreateObject('contactcenter.bo_people_catalog');
			$types = $pCatalog->get_all_connections_types();
			
			if (is_array($types))
			{
				$options_email = '';
				foreach($types as $id => $name)
				{
					$options_email .= '<option value="'.$id.'"';
					
					if ($actual['personCardEmail'] == $id)
					{
						$options_email .= ' selected ';
					}
				
					$options_email .= '>'.$name."</option>\n";
				}
			
				$options_phone = '';
				foreach($types as $id => $name)
				{
					$options_phone .= '<option value="'.$id.'"';
					
					if ($actual['personCardPhone'] == $id)
					{
						$options_phone .= ' selected ';
					}
				
					$options_phone .= '>'.$name."</option>\n";
				}
			}
			else
			{
				$options_email = '';
				$options_phone = '';
			}
			
			if ($actual['displayConnector'] or !$actual['displayConnectorDefault'])
			{
				$GLOBALS['phpgw']->template->set_var('displayConnector', 'checked');
			}
			else
			{
				$GLOBALS['phpgw']->template->set_var('displayConnector', '');
			}
			
			if ($actual['empNum'])
			{
				$GLOBALS['phpgw']->template->set_var('empNum', 'checked');
			}
			else
			{
				$GLOBALS['phpgw']->template->set_var('empNum', '');
			}

			if ($actual['cell'])
			{
				$GLOBALS['phpgw']->template->set_var('cell', 'checked');
			}
			else
			{
				$GLOBALS['phpgw']->template->set_var('cell', '');
			}

			if ($actual['department'])
			{
				$GLOBALS['phpgw']->template->set_var('department', 'checked');
			}
			else
			{
				$GLOBALS['phpgw']->template->set_var('department', '');
			}

			$GLOBALS['phpgw']->template->set_var('personCardEmail', $options_email);
			$GLOBALS['phpgw']->template->set_var('personCardPhone', $options_phone);

			/* Translate the fields */
			$this->translate('pref');

			$GLOBALS['phpgw']->template->set_var('form_action', $GLOBALS['phpgw']->link('/index.php', 'menuaction=contactcenter.ui_preferences.set_preferences'));

			$GLOBALS['phpgw']->template->pparse('out', 'pref');
		}
		
		function translate($handle)
		{
			$vars = $GLOBALS['phpgw']->template->get_undefined($handle);
			foreach($vars as $name => $value)
			{
				if (preg_match('/^lang_/', $name) !== false)
				{
					$GLOBALS['phpgw']->template->set_var($name, lang(str_replace('_',' ',substr($name, 5))));
				}
			}
		}
		
		function set_preferences()
		{
			if ($_POST['save'])
			{
				$GLOBALS['phpgw']->preferences->read();
				
				$GLOBALS['phpgw']->preferences->delete('contactcenter', 'personCardEmail');
				$GLOBALS['phpgw']->preferences->delete('contactcenter', 'personCardPhone');

				$GLOBALS['phpgw']->preferences->delete('contactcenter', 'displayConnector');
				$GLOBALS['phpgw']->preferences->delete('contactcenter', 'displayConnectorDefault');
				
				$GLOBALS['phpgw']->preferences->add('contactcenter', 'personCardEmail', $_POST['personCardEmail']);
				$GLOBALS['phpgw']->preferences->add('contactcenter', 'personCardPhone', $_POST['personCardPhone']);
				
				$GLOBALS['phpgw']->preferences->add('contactcenter', 'displayConnectorDefault', '1');

				if($_POST['displayConnector'])
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'displayConnector', '1');
				}
				else
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'displayConnector', '0');
				}
				
				if($_POST['empNum'])
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'empNum', '1');
				}
				else
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'empNum', '0');
				}
				
				if($_POST['cell'])
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'cell', '1');
				}
				else
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'cell', '0');
				}
				
				if($_POST['department'])
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'department', '1');
				}
				else
				{
					$GLOBALS['phpgw']->preferences->add('contactcenter', 'department', '0');
				}

				$GLOBALS['phpgw']->preferences->save_repository();
			}

			header('Location: '.$GLOBALS['phpgw']->link('/preferences/index.php'));
		}

		function get_preferences()
		{
			$prefs = $GLOBALS['phpgw']->preferences->read();

			if (!$prefs['contactcenter']['displayConnectorDefault'] and !$prefs['contactcenter']['displayConnector'])
			{
				$prefs['contactcenter']['displayConnector'] = true;
			}
			
			return $prefs['contactcenter'];
		}
	}
?>
