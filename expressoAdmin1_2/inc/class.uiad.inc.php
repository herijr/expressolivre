<?php
class uiad
{
	var $public_functions = array(
		'save'   => true,
		'config' => true,
		'edit'   => true,
	);
	
	protected $_so;
	
	function uiad()
	{
		$this->_so = CreateObject('expressoAdmin1_2.soad');
		if ( !@is_object( $GLOBALS['phpgw']->js ) ) $GLOBALS['phpgw']->js = CreateObject( 'phpgwapi.javascript' );
	}
	
	function save()
	{
		if ( !( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest' ) ) {
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/admin/index.php' ) );
			return false;
		}
		
		if ( !$GLOBALS['phpgw']->acl->check( 'run', 1, 'admin' ) ) $this->_setResponse( null, 401 );
		
		$result = $this->_so->setConf( $_POST );
		if ( $result === true ) $this->_setResponse( utf8_encode( lang( 'Configuration saved successfully' ) ) );
		else $this->_setResponse( $result, 400 );
	}
	
	function config()
	{
		if ( !( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest' ) ) {
			$GLOBALS['phpgw']->redirect( $GLOBALS['phpgw']->link( '/admin/index.php' ) );
			return false;
		}
		
		if ( !$GLOBALS['phpgw']->acl->check( 'run', 1, 'admin' ) ) $this->_setResponse( null, 401 );
		
		$this->_setResponse( $this->_so->getConfs() );
	}
	
	function _setResponse( $data, $code = null )
	{
		if ( !is_null($code) ) header( ':', true, (int)$code );
		header( 'Content-Type: application/json' );
		echo json_encode( (array)$data );
		exit;
	}
	
	function edit()
	{
		if ( !$GLOBALS['phpgw']->acl->check('run',1,'admin') ) $GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/admin/index.php'));
		
		$GLOBALS['phpgw']->js->add('file','./prototype/plugins/jquery/jquery-latest.min.js');
		$GLOBALS['phpgw']->js->add('file','./prototype/plugins/jquery/jquery-ui-latest.min.js');
		$GLOBALS['phpgw']->js->add('txt','var config_save_url = "/index.php?menuaction=expressoAdmin1_2.uiad.save";');
		$GLOBALS['phpgw']->js->validate_file('jscode','connector','expressoAdmin1_2');
		$GLOBALS['phpgw']->js->validate_file('jscode','lang','expressoAdmin1_2');
		$GLOBALS['phpgw']->js->validate_file('jscode','config','expressoAdmin1_2');
		$GLOBALS['phpgw']->css->validate_file('./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css');
		$GLOBALS['phpgw']->css->validate_file('expressoAdmin1_2/templates/default/css/custom.css');
		
		unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
		$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['expressoAdmin1_2']['title'].' - '.lang('Active Directory');
		$GLOBALS['phpgw']->common->phpgw_header();
		
		$confs = $this->_so->getConfs();
		$ou_list = '{ '.implode( ',', array_map( function( $k, $v ) {
			return '\''.str_replace( '\'', '\\\'', $k ).'\':\''.str_replace( '\'', '\\\'', $v ).'\'';
		},array_keys( $confs[ActiveDirectory::CONFIG_OU_LIST] ), $confs[ActiveDirectory::CONFIG_OU_LIST] ) ).' }';
		
		$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
		$p->set_file(array('template' => 'active_directory.tpl'));
		$p->set_block('template','body','body');
		$p->set_var(array(
			'lang_title'                    => lang('Active Directory Settings'),
			'lang_save'                     => lang('Save'),
			'lang_cancel'                   => lang('Cancel'),
			'lang_yes'                      => lang('Yes'),
			'lang_no'                       => lang('No'),
			'lang_enabled'                  => lang('Enabled'),
			'lang_disabled'                 => lang('Disabled'),
			'lang_add'                      => lang('Add'),
			
			'lang_enabled_ad'               => lang('Enable Active Directory'),
			'input_name_enabled'            => ActiveDirectory::CONFIG_ENABLED,
			'input_value_enabled'           => $confs[ActiveDirectory::CONFIG_ENABLED]? 'checked' : '',
			
			'lang_url'                      => lang('URL soap wsdl'),
			'lang_url_desc'                 => 'Ex.: http://domain?wsdl',
			'input_name_url'                => ActiveDirectory::CONFIG_URL,
			'input_value_url'               => $confs[ActiveDirectory::CONFIG_URL],
			
			'lang_admin'                    => lang('Administrator username'),
			'input_name_admin'              => ActiveDirectory::CONFIG_ADMIN,
			'input_value_admin'             => $confs[ActiveDirectory::CONFIG_ADMIN],
			
			'lang_passwd'                   => lang('Administrator password'),
			'input_name_passwd'             => ActiveDirectory::CONFIG_PASSWD,
			'input_value_passwd'            => $confs[ActiveDirectory::CONFIG_PASSWD],

			'lang_ad_ou_list'               => lang('Organization Unit List'),
			'lang_ou_list_name'             => lang('Organization name'),
			'lang_ou_list_dn'               => lang('Organization dn'),
			'js_oulist'                     => $ou_list,
		));
		
		$p->pparse('out','body');
	}
}
