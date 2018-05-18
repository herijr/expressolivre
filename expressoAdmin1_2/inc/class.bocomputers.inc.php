<?php
	/**************************************************************************************\
	* Expresso Administração                 								              *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	  *
	* ------------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			  *
	*  under the terms of the GNU General Public License as published by the			  *
	*  Free Software Foundation; either version 2 of the License, or (at your			  *
	*  option) any later version.														  *
	\**************************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

	class bocomputers
	{
		var $public_functions = array(
			'create_computer'	=> True,
			'save_computer'		=> True,
			'delete_computer'	=> True
		);
	
		var $so;
		var $db_functions;
		var $functions;

		function bocomputers()
		{
			$this->so = CreateObject('expressoAdmin1_2.socomputers');
			$this->functions = $this->so->functions;
			$this->db_functions = CreateObject('expressoAdmin1_2.db_functions');
		}

		function create_computer()
		{
			if (!$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_ADD_COMPUTERS ))
			{
				return false;
			}
		
			$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c->read_repository();
			$current_config = $c->config_data;
			// Leio o ID a ser usado na criação do objecto.
			// Esta funcao ja incrementa o ID
			$next_id = $this->db_functions->get_next_id();
			if ((!is_numeric($next_id['id'])) || (!$next_id['status']))
			{
				$return['status'] = false;
				$return['msg'] = lang('problems getting user id') . ".\n" . $id['msg'];
				return $return;
			}
			else
			{
				$id = $next_id['id'];
			}	
			
			// Cria array para incluir no LDAP
			$dn = 'uid=' . $_POST['computer_cn'] . '$,' . $_POST['sector_context'];
			$computer_info = array();
			$computer_info['uid']					= $_POST['computer_cn'] . '$';
			$computer_info['cn']					= $_POST['computer_cn'];
			$computer_info['uidnumber']				= $id;
			$computer_info['gidNumber']				= $current_config['expressoAdmin_sambaGIDcomputers']; /* nas configurações globais */
			$computer_info['homeDirectory']			= '/dev/null';
			$computer_info['objectClass'][0]		= 'posixAccount';
			$computer_info['objectClass'][1]		= 'account';
			$computer_info['objectClass'][2]		= 'sambaSamAccount';
			$computer_info['objectClass'][3]		= 'top';
			$computer_info['sambaAcctFlags']		= $_POST['sambaAcctFlags'];
			$computer_info['sambaPwdCanChange']		= strtotime("now");
			$computer_info['sambaPwdLastSet']		= strtotime("now");
			$computer_info['sambaPwdMustChange']	= '2147483647';
			$computer_info['sambasid'] 				= $_POST['sambasid'] . '-' . ((2 * (int)$id)+1000);
			
			/* Trust Account */
			if ($_POST['sambaAcctFlags'] == '[I          ]')
			{
				if (!is_file('/home/expressolivre/mkntpwd'))
				{
					$_POST['error_messages'] = 
						lang("the binary file /home/expressolivre/mkntpwd does not exist") . ".\\n" .
						lang("it is needed to create samba passwords") . ".\\n" . 
						lang("alert your administrator about this") . ".";
					
					ExecMethod('expressoAdmin1_2.uiaccounts.add_computer');
					return false;
				}
				$computer_info['sambaLMPassword']	= exec('/home/expressolivre/mkntpwd -L "' . $_POST['computer_password']. '"');
				$computer_info['sambaNTPassword']	= exec('/home/expressolivre/mkntpwd -N "' . $_POST['computer_password']. '"');
			}
			
			if ($_POST['computer_description'] != '')
				$computer_info['description'] = utf8_encode($_POST['computer_description']);
			
			// Chama funcao para escrever no OpenLDAP, case de erro, volta com msg de erro.
			if (!$this->so->write_ldap($dn, $computer_info))
			{
				$_POST['error_messages'] = lang('Error in OpenLDAP recording computer.');
				ExecMethod('expressoAdmin1_2.uicomputers.add_computer');
				return false;
			}
			
			// Volta para o ListGroups
			$url = ($GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uicomputers.list_computers'));
			$GLOBALS['phpgw']->redirect($url);
		}
		
		function save_computer()
		{
			$old_dn 		= $_POST['old_computer_dn'];
			$new_rdn 		= 'uid=' . $_POST['computer_cn'] . '$';
			$new_context	= $_POST['sector_context']; 
			$new_dn 		= $new_rdn . ',' . $new_context;

			//Verifica se a descrição do computador foi alterada.
			if (($_POST['computer_description'] == '') && ($_POST['old_computer_description'] == '')){}
			elseif ($_POST['computer_description'] == $_POST['old_computer_description']){}
			elseif (($_POST['old_computer_description'] != '') && ($_POST['computer_description'] == ''))
			{
				$computer_mod_remove['descriptions'] = $_POST['old_computer_description'];
			}
			elseif (($_POST['old_computer_description'] == '') && ($_POST['computer_description'] != ''))
			{
				$computer_mod_add['description'] = utf8_encode($_POST['computer_description']);
			}
			elseif ($_POST['computer_description'] != $_POST['old_computer_description'])
			{
				$computer_mod_replace['description'] = utf8_encode($_POST['computer_description']);
			}


			if ($_POST['sambaAcctFlags'] != $_POST['old_computer_sambaAcctFlags'])
			{
				$computer_mod_replace['sambaAcctFlags'] = $_POST['sambaAcctFlags'];
			}
			
			if ($_POST['computer_password'] != '')
			{
				$computer_mod_replace['sambaLMPassword']	= exec('/home/expressolivre/mkntpwd -L "'.$_POST['computer_password'] . '"');
				$computer_mod_replace['sambaNTPassword']	= exec('/home/expressolivre/mkntpwd -N "'.$_POST['computer_password'] . '"');
			}

			if ($_POST['sambasid'] != $_POST['old_sambasid'])
			{
				$computer_mod_replace['sambasid'] = $_POST['sambasid'] . '-' . ((2 * (int)$_POST['uidnumber'])+1000);
			}

             // Chama funcao para renomar no OpenLDAP, case de erro, volta com msg de erro.
            if (($_POST['old_computer_cn'] != $_POST['computer_cn']) || ($_POST['old_computer_context'] != $_POST['sector_context']))
            {
				$computer_mod_replace['cn'] = utf8_encode($_POST['computer_cn']);
				$rename_ldap = TRUE;
			}

            if (count($computer_mod_add) != 0)
                $this->so->ldap_add_attribute($computer_mod_add, $old_dn);

            if (count($computer_mod_remove) != 0)
                $this->so->ldap_remove_attribute($computer_mod_remove, $old_dn);

            if (count($computer_mod_replace) != 0)
                $this->so->ldap_replace_attribute($computer_mod_replace, $old_dn);


			if ($rename_ldap)
                if (!$this->so->rename_ldap($old_dn, $new_rdn, $new_context))
                {
                    $_POST['error_messages'] = lang('Error in OpenLDAP rename Computer');
                    ExecMethod('expressoAdmin1_2.uicomputers.edit_computer');
                    return false;
                }

			// Volta para o ListGroups
			$url = ($GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uicomputers.list_computers'));
			$GLOBALS['phpgw']->redirect($url);
		}
		
		function delete_computer()
		{
			//Delete from ldap.
			$result_ldap = $this->delete( array( 'dn' => $_POST['computer_dn'] ) );
			if ( !$result_ldap['status'] ) {
				$_POST['error_messages'] = $result_ldap['msg'];
				ExecMethod('expressoAdmin1_2.uicomputers.list_computers');
				return false;
			}
			
			// Volta para o ListGroups
			$url = ($GLOBALS['phpgw']->link('/index.php','menuaction=expressoAdmin1_2.uicomputers.list_computers'));
			$GLOBALS['phpgw']->redirect($url);
		}
		
		function delete( $params )
		{
			$return['status'] = true;
			
			if ( !$this->functions->check_acl( $_SESSION['phpgw_session']['session_lid'], ACL_Managers::ACL_DEL_COMPUTERS ) ) {
				$return['status'] = false;
				$return['msg'] = lang( 'You do not have access to delete computers' ) . '.';
				return $return;
			}
			
			//LDAP
			if ( !$this->so->delete_computer_ldap( $params['dn'] ) ) {
				$return['status'] = false;
				$return['msg'] .= lang('Error deleting Computer in OpenLDAP.');
				return $return;
			}
			
			$this->db_functions->write_log( 'deleted computer', $params['dn'] );
			return $return;
		}
	}
?>
