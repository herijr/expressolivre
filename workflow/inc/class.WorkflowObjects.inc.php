<?php
/**************************************************************************\
* eGroupWare                                                               *
* http://www.egroupware.org                                                *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/**
 * Prov� objetos de multiprop�sito do Workflow
 * @author Sidnei Augusto Drovetto Jr. - drovetto@gmail.com
 * @version 1.0
 * @package Workflow
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */
class WorkflowObjects
{
	/**
	 * @var array $cache Cache de objetos
	 * @access private
	 */
	private $cache;

	/**
	 * Construtor da classe
	 * @return object
	 * @access public
	 */
	function WorkflowObjects()
	{
		$this->cache = array();
	}

	/**
	 * Monta o ambiente requerido pelos m�todos (somente se for necess�rio)
	 * @param bool $requireGalaxia Indica que os m�todos da engine Galaxia s�o necess�rios
	 * @return void
	 * @access public
	 */
	private function assureEnvironment($requireGalaxia = true)
	{
		if (!defined('PHPGW_API_INC'))
			define('PHPGW_API_INC', dirname(__FILE__) . '/../../phpgwapi/inc');

		if (!defined('WF_SERVER_ROOT'))
			define('WF_SERVER_ROOT', dirname(dirname(__FILE__)) . '/');

		if ($requireGalaxia)
		{
			if (!function_exists('galaxia_get_config_values'))
			{
				require_once WF_SERVER_ROOT . 'inc/engine/config.ajax.inc.php' ;
				require_once WF_SERVER_ROOT . 'inc/engine/class.ajax_config.inc.php' ;
			}
		}
	}

	/**
	 * Retorna uma conex�o com o banco de dados do Galaxia (normalmente associado ao banco de dados do Expresso (eGroupWare))
	 * @return object O objeto de acesso a banco de dados, j� conectado
	 * @access public
	 */
	function &getDBGalaxia()
	{
		if (!isset($this->cache['DBGalaxia']))
		{
			/* make sure the environment is set */
			$this->assureEnvironment();

			/* load the configuration required to establish a connection to the Galaxia database */
			$dbConfigValues = galaxia_get_config_values(array(
				'workflow_database_name' => '',
				'workflow_database_host' => '',
				'workflow_database_port' => '',
				'workflow_database_user' => '',
				'workflow_database_password' => '',
				'workflow_database_type' => ''
			));

			/* check if all configuration is OK */
			$dedicatedDB = true;
			foreach ($dbConfigValues as $configName => $configValue)
				if (empty($configValue) && ($configName != 'workflow_database_password'))
					$dedicatedDB = false;

			if ($dedicatedDB)
			{
				/* connect to the database */
				$this->cache['DBGalaxia'] = Factory::newInstance('WorkflowWatcher', Factory::newInstance('db_egw'));
				$this->cache['DBGalaxia']->disconnect(); /* for some reason it won't connect to the desired database unless we disconnect it first */
				$this->cache['DBGalaxia']->Halt_On_Error = 'no';
				$this->cache['DBGalaxia']->connect(
					$dbConfigValues['workflow_database_name'],
					$dbConfigValues['workflow_database_host'],
					$dbConfigValues['workflow_database_port'],
					$dbConfigValues['workflow_database_user'],
					$dbConfigValues['workflow_database_password'],
					$dbConfigValues['workflow_database_type']
				);
				Factory::getInstance('WorkflowSecurity')->removeSensitiveInformationFromDatabaseObject($this->cache['DBGalaxia']);
				$this->cache['DBGalaxia']->Link_ID = Factory::newInstance('WorkflowWatcher', $this->cache['DBGalaxia']->Link_ID);
			}
			else
				$this->cache['DBGalaxia'] = &$this->getDBExpresso();
		}

		return $this->cache['DBGalaxia'];
	}

	/**
	 * Retorna uma conex�o com o banco de dados do Expresso (eGroupWare)
	 * @return object O objeto de acesso a banco de dados, j� conectado
	 * @access public
	 */
	function &getDBExpresso()
	{
		if (!isset($this->cache['DBExpresso']))
		{
			/* make sure the environment is set */
			$this->assureEnvironment(false);

			/* check where the connection parameters are */
			$connectionInfo = (isset($GLOBALS['phpgw_info']['server']['db_name'])) ?
				$GLOBALS['phpgw_info']['server'] :
				$_SESSION['phpgw_info']['workflow']['server'];

			/* the information was not found. Try to load the environment */
			if (!isset($connectionInfo['db_name']))
			{
				Factory::getInstance('WorkflowMacro')->prepareEnvironment();
				if (isset($GLOBALS['phpgw_info']['server']))
					$connectionInfo = $GLOBALS['phpgw_info']['server'];
				else
					return false;
			}

			/* connect to the database */
			$this->cache['DBExpresso'] = Factory::newInstance('WorkflowWatcher', Factory::newInstance('db_egw'));
			$this->cache['DBExpresso']->disconnect(); /* for some reason it won't connect to the desired database unless we disconnect it first */
			$this->cache['DBExpresso']->Halt_On_Error = 'no';
			$this->cache['DBExpresso']->connect(
				$connectionInfo['db_name'],
				$connectionInfo['db_host'],
				$connectionInfo['db_port'],
				$connectionInfo['db_user'],
				$connectionInfo['db_pass'],
				$connectionInfo['db_type']
			);
			Factory::getInstance('WorkflowSecurity')->removeSensitiveInformationFromDatabaseObject($this->cache['DBExpresso']);
			$this->cache['DBExpresso']->Link_ID = Factory::newInstance('WorkflowWatcher', $this->cache['DBExpresso']->Link_ID);
		}

		return $this->cache['DBExpresso'];
	}

	/**
	 * Retorna uma conex�o com o banco de dados do Workflow
	 * @return object O objeto de acesso a banco de dados, j� conectado
	 * @access public
	 */
	function &getDBWorkflow()
	{
		if (!isset($this->cache['DBWorkflow']))
		{
			/* make sure the environment is set */
			$this->assureEnvironment();

			/* load the configuration required to establish a connection to the Galaxia database */
			$dbConfigValues = galaxia_get_config_values(array(
				'database_name' => '',
				'database_host' => '',
				'database_port' => '',
				'database_admin_user' => '',
				'database_admin_password' => '',
				'database_type' => ''
			));

			/* connect to the database */
			$this->cache['DBWorkflow'] = Factory::newInstance('WorkflowWatcher', Factory::newInstance('db_egw'));
			$this->cache['DBWorkflow']->disconnect(); /* for some reason it won't connect to the desired database unless we disconnect it first */
			$this->cache['DBWorkflow']->Halt_On_Error = 'no';
			$this->cache['DBWorkflow']->connect(
				$dbConfigValues['database_name'],
				$dbConfigValues['database_host'],
				$dbConfigValues['database_port'],
				$dbConfigValues['database_admin_user'],
				$dbConfigValues['database_admin_password'],
				$dbConfigValues['database_type']
			);
			Factory::getInstance('WorkflowSecurity')->removeSensitiveInformationFromDatabaseObject($this->cache['DBWorkflow']);
			$this->cache['DBWorkflow']->Link_ID = Factory::newInstance('WorkflowWatcher', $this->cache['DBWorkflow']->Link_ID);
		}

		return $this->cache['DBWorkflow'];
	}

	/**
	 * Retorna um recurso de LDAP
	 * @param bool $useCCParams Indica se deve usar os par�metros do Contact Center
	 * @return resource O recurso LDAP
	 * @access public
	 */
	function &getLDAP($useCCParams = false)
	{
		if (!isset($this->cache['ldap']))
		{
			/* make sure the environment is set */
			$this->assureEnvironment();

			if($useCCParams)
			{
				/* get the contact center's connection parameters */
				$ajaxConfig = &Factory::newInstance('ajax_config', 'contactcenter');
				$config = $ajaxConfig->read_repository();

				$ldapConfigValues['ldap_host'] = $config['cc_ldap_host0'];
				$ldapConfigValues['ldap_user'] = $config['cc_ldap_browse_dn0'];
				$ldapConfigValues['ldap_password'] = $config['cc_ldap_pw0'];
				$ldapConfigValues['ldap_follow_referrals'] = 1;
			}
			else
			{
				/* check where the connection parameters are */
				$connectionInfo = (isset($GLOBALS['phpgw_info']['server']['ldap_host'])) ?
					$GLOBALS['phpgw_info']['server'] :
					$_SESSION['phpgw_info']['workflow']['server'];

				/* load required information */
				$ldapConfigValues = galaxia_get_config_values(array('ldap_host' => '', 'ldap_user' => '', 'ldap_password'=> '', 'ldap_follow_referrals' => ''));
				if (empty($ldapConfigValues['ldap_host']))
					$ldapConfigValues['ldap_host'] = $connectionInfo['ldap_host'];
			}

			/* connect to the LDAP server */
			$this->cache['ldap'] = ldap_connect($ldapConfigValues['ldap_host']);

			/* configure the connection */
			ldap_set_option($this->cache['ldap'], LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->cache['ldap'], LDAP_OPT_REFERRALS, ($ldapConfigValues['ldap_follow_referrals'] == 1) ? 1 : 0);

			/* if  username and password are available, bind the connection */
			if ((!empty($ldapConfigValues['ldap_user'])) && (!empty($ldapConfigValues['ldap_password'])))
			{
				$lbind = ldap_bind($this->cache['ldap'], $ldapConfigValues['ldap_user'], $ldapConfigValues['ldap_password']);

				if(!$lbind)
				{
					// unbind & reconnect (trying ldap instead of ldaps) & rebind
					ldap_unbind($this->cache['ldap']);
					$this->cache['ldap'] = ldap_connect(str_replace('ldaps://', 'ldap://', $ldapConfigValues['ldap_host']));

					/* configure the connection */
					ldap_set_option($this->cache['ldap'], LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($this->cache['ldap'], LDAP_OPT_REFERRALS, ($ldapConfigValues['ldap_follow_referrals'] == 1) ? 1 : 0);

					$lbind = ldap_bind($this->cache['ldap'], $ldapConfigValues['ldap_user'], $ldapConfigValues['ldap_password']);
				}
			}
		}

		return $this->cache['ldap'];
	}
	
	/**
	 * Retorna um recurso de LDAP
	 * @param bool $useCCParams Indica se deve usar os par�metros do Contact Center
	 * @return resource O recurso LDAP
	 * @access public
	 */
	function getBaseDir()
	{
		// Get first cache level: instance
		if ( isset( $this->cache['basedir'] ) ) return $this->cache['basedir'];
		
		// Get second cache level: session
		if ( isset( $_SESSION['phpgw_info']['workflow']['basedir'] ) )
			return $this->cache['basedir'] = $_SESSION['phpgw_info']['workflow']['basedir'];
		
		// Sql select config
		$sql = 'SELECT config_value FROM phpgw_config WHERE config_app = ? AND config_name = ?';
		
		// Get third config level: database
		$path = Factory::getInstance('WorkflowObjects')->getDBExpresso()->Link_ID->query( $sql, array( 'workflow', 'basedir' ) )->fetchRow();
		$path = ( $path !== false ) ? $path['config_value'] : $GLOBALS['phpgw_info']['server']['files_dir'];
		
		// Check wildcard
		if ( strpos( $path, '%u' ) !== false ) {
			
			// Get login to wildcard replace
			$path = str_replace( '%u', ( isset( $_SESSION['phpgw_session']['session_lid'] )? $_SESSION['phpgw_session']['session_lid'] : '' ), $path );
			
			// Try create directory
			if ( !is_dir( $path ) ) {
				$old = umask( 0 );
				mkdir( $path, 02770, true );
				umask( $old );
			}
		}
		
		return $this->cache['basedir'] = $_SESSION['phpgw_info']['workflow']['basedir'] = $path;
		
		
	}
}
?>
