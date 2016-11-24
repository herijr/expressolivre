<?php
/***********************************************************************************\
* Expresso Administração															*
* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	*
* modified by Valmir Andre de Sena valmirse@gmail.com valmir.sena@ati.pe.gov.br
* ----------------------------------------------------------------------------------*
*  This program is free software; you can redistribute it and/or modify it			*
*  under the terms of the GNU General Public License as published by the			*
*  Free Software Foundation; either version 2 of the License, or (at your			*
*  option) any later version.														*
\***********************************************************************************/

include_once( PHPGW_API_INC.'/class.aclmanagers.inc.php' );

class uishared_accounts
{
	var $public_functions = array
	(
		'index' => True
	);
	var $functions;
    var $current_config;

	function uishared_accounts()
	{			
		if (function_exists('CreateObject'))
		{
			$this->functions = CreateObject('expressoAdmin1_2.functions');
			
			if(!@is_object($GLOBALS['phpgw']->js))
			{
				$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			}
			$GLOBALS['phpgw']->js->validate_file('jscode','connector','expressoAdmin1_2');#diretorio, arquivo.js, aplicacao
			$GLOBALS['phpgw']->js->validate_file('jscode','sharedAccounts','expressoAdmin1_2');
			$GLOBALS['phpgw']->js->validate_file('jscode','expressoadmin','expressoAdmin1_2');
			$GLOBALS['phpgw']->js->validate_file('modal','modal','expressoAdmin1_2');
            $c = CreateObject('phpgwapi.config','expressoAdmin1_2');
            $c->read_repository();
            $this->current_config = $c->config_data;
		}
	}

	function index()
	{
		/* Begin:  Check manager access */
		$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
		$acl = $this->functions->read_acl($account_lid);
		$contexts = $acl['contexts'];
                    
		foreach ($acl['contexts_display'] as $index=>$tmp_context) {
			$context_display .= '<br>'.$tmp_context;
		}
		
		if (!$this->functions->check_acl($account_lid, ACL_Managers::GRP_VIEW_SHARED_ACCOUNTS )) {
			$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/expressoAdmin1_2/inc/access_denied.php'));
		}
		/* End: Check manager access */

		unset($GLOBALS['phpgw_info']['flags']['noheader']);
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('shared accounts');
		$GLOBALS['phpgw']->common->phpgw_header();

		$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);

		$p->set_file(
				array(
						'shared_accounts'		=> 'shared_accounts.tpl',
						'shared_accounts_modal'	=>'shared_accounts_modal.tpl'
					)
				);
		$p->set_block('shared_accounts','body');
		
		/* dinamic load lang */                        
		$p->set_var($this->functions->make_dinamic_lang($p, 'body'));
		$p->set_var($this->functions->make_dinamic_lang($p, 'shared_accounts_modal'));


		/* Begin: set modal */
		$functions = CreateObject('expressoAdmin1_2.functions');

		foreach ($contexts as $index=>$context)
		{
			$combo_manager_org .= $this->functions->get_organizations($context);
		}
		
		$combo_all_orgs = $this->functions->get_organizations($GLOBALS['phpgw_info']['server']['ldap_context']);
		
		$p->set_var('manager_organizations', $combo_manager_org);
		$p->set_var('all_organizations', $combo_all_orgs);
		
		$modal_id = 'shared_accounts_modal';
		$p->set_var('modal_id', $modal_id);
                    
        $var = array(
            'mailquota' =>  $this->current_config['expressoAdmin_defaultUserQuota'],
            'changequote_disabled' => $this->functions->check_acl($account_lid, ACL_Managers::ACL_MOD_SHARED_ACCOUNTS_QUOTA) ? '' : 'readonly',
            'disabled_empty_inbox' => $this->functions->check_acl($account_lid, ACL_Managers::ACL_SET_SHARED_ACCOUNTS_ACL_EMPTY ) ? '' : 'disabled',
            'display_quota_used' => 'hidden'
        );
        $p->set_var($var);
		$shared_accounts_modal_tpl = $p->fp('out','shared_accounts_modal');
        /* End: set modal */
		
		$var = array(
			'th_bg'						=> $GLOBALS['phpgw_info']['theme']['th_bg'],
			'back_url'					=> $GLOBALS['phpgw']->link('/expressoAdmin1_2/index.php'),
			'context_display'			=> $context_display,
			'shared_accounts_modal' 		=> $shared_accounts_modal_tpl,                                
			'onclick_create_shared_account' => "modal(\"$modal_id\",\"create\")"
		);
		$p->set_var($var);
		$p->pfp('out','body');
	}
}
