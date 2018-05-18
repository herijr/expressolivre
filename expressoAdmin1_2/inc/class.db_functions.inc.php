<?php
defined('PHPGW_INCLUDE_ROOT') || define('PHPGW_INCLUDE_ROOT','../');	
defined('PHPGW_API_INC') || define('PHPGW_API_INC','../phpgwapi/inc');
include_once(PHPGW_API_INC.'/class.db_egw.inc.php');

class db_functions
{	
	var $db;
	var $user_id;
	
	function db_functions()
	{
		/*
		if (!ini_get('session.auto_start'))
		{
			session_name('sessionid');
			session_start();
		}*/

		if (is_array($_SESSION['phpgw_info']['expresso']['server']))
			$GLOBALS['phpgw_info']['server'] = $_SESSION['phpgw_info']['expresso']['server'];
		else
			$_SESSION['phpgw_info']['expresso']['server'] = $GLOBALS['phpgw_info']['server'];
		
		$this->db = new db_egw();
		$this->db->Halt_On_Error = 'no';
		$this->db->connect(
				$_SESSION['phpgw_info']['expresso']['server']['db_name'], 
				$_SESSION['phpgw_info']['expresso']['server']['db_host'],
				$_SESSION['phpgw_info']['expresso']['server']['db_port'],
				$_SESSION['phpgw_info']['expresso']['server']['db_user'],
				$_SESSION['phpgw_info']['expresso']['server']['db_pass'],
				$_SESSION['phpgw_info']['expresso']['server']['db_type']
		);		
		$this->user_id = $_SESSION['phpgw_info']['expresso']['user']['account_id'];	
	}
	
	function get_suggested_domains( $params ){
		
		$result = array();
		if ( !( isset( $params['dn'] ) && $params['dn'] !== '' ) ) {
			
			$result['error'] = 'invalid dn';
			
		} else {
			
			$DNs = array();
			$DN =  strtolower( $params['dn'] );
			do {
				$DNs[] = $DN;
				if ( $DN == $GLOBALS['phpgw_info']['server']['ldap_context'] ) $DN = '';
				else $DN = substr( $DN, strpos( $DN, ',' ) + 1 );
			} while ( strpos( $DN, ',' ) !== false );
			
			foreach ( $DNs as $DN ) {
				$query = 'SELECT domain FROM phpgw_emailadmin_domains WHERE organization_units LIKE \'%"'.addslashes( $DN ).'"%\'';
				$this->db->query( $query );
				while( $this->db->next_record() ) {
					$row = $this->db->row();
					$result[] = $row['domain'];
				}
			}
		}
		
		header( 'Content-Type: application/json;' );
		echo json_encode( $result );
		exit;
	}
	
	// BEGIN of functions.
	function read_acl($account_lid)
	{
		$query = "SELECT * FROM phpgw_expressoadmin WHERE manager_lid = '" . $account_lid . "'"; 
		$this->db->query($query);
		while($this->db->next_record())
			$result[] = $this->db->row();
		return $result;
	}

	function copy_manager($params)
	{
		$result['status'] = true;
		
		$manager = $params['manager'];
		$new_manager = $params['new_manager'];
		$manager_info = $this->read_acl($manager);

		$sql = "SELECT * FROM phpgw_expressoadmin WHERE manager_lid = '" . $new_manager . "'";
		$this->db->query($sql);
		while($this->db->next_record())
		{
			$manager_exist = true;
		}
		if ($manager_exist)
		{
			$result['status'] = false;
			$result['msg'] = 'manager already exist';
			return $result;
		}
		
		//Escrevre no Banco a ACL do gerente
		$sql = "INSERT INTO phpgw_expressoadmin (manager_lid, context, acl) "
		. "VALUES('" . $new_manager . "','" . $manager_info[0]['context'] . "','" . $manager_info[0]['acl'] . "')";
		
		if (!$this->db->query($sql))
		{
			$result['status'] = false;
			$result['msg'] = 'problems copying manager. ' . pg_last_error();
			return $result;
		}
		
		//Pesquisa no Banco e pega os valores dos apps.
		$sql = "SELECT * FROM phpgw_expressoadmin_apps WHERE manager_lid = '" . $manager_info[0]['manager_lid'] . "' AND context = '" . $manager_info[0]['context'] . "'";
		$this->db->query($sql);
		while($this->db->next_record())
		{
			$aplications[] = $this->db->row();
		}
		
		//Escrevre no Banco as aplicações que o gerente tem direito de disponibilizar aos seus usuarios.
		for ($i=0; $i<count($aplications); $i++)
		{
			$sql = "INSERT INTO phpgw_expressoadmin_apps (manager_lid, context, app) "
			. "VALUES('" . $new_manager . "','" . $manager_info[0]['context'] . "','" . $aplications[$i]['app'] . "')";
			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = 'error adding application to new manager. ' . pg_last_error();
				return $result;
			}
		}
		return $result;
	}

	function get_next_id()
	{
		$config = $_SESSION['phpgw_info']['expresso']['expressoAdmin'];
		if ( isset( $config['expressoAdmin_nextid_db_host'] ) && $config['expressoAdmin_nextid_db_host'] != '' ) {
			$db = new db_egw();
			$db->Halt_On_Error = 'no';
			$db->connect(
				$config['expressoAdmin_nextid_db_name'],
				$config['expressoAdmin_nextid_db_host'],
				$config['expressoAdmin_nextid_db_port'],
				$config['expressoAdmin_nextid_db_user'],
				$config['expressoAdmin_nextid_db_password'],
				'pgsql'
			);
		} else $db = $this->db;
		if ( !$db->query( 'SELECT nextval( \'"phpgw_uidNumber_seq"\' );' ) )
			return array( 'status' => false, 'msg' => lang('Problems running query on DB').'.' );
		return array( 'id' => $db->Query_ID->fields[0] );
	}
	
	function add_user2group($gidnumber, $uidnumber)
	{
		$query = "SELECT acl_location FROM phpgw_acl WHERE acl_appname = 'phpgw_group' AND acl_location = '" . $gidnumber . "' AND acl_account = '" . $uidnumber . "'";
		if (!$this->db->query($query))
		{
			$result['status'] = false;
			$result['msg'] = lang('Error on function') . " db_functions->add_user2group.\n" . lang('Server returns') . ': ' . pg_last_error();
			return $result;
		}
		$user_in_group = array();
		while($this->db->next_record())
			$user_in_group[] = $this->db->row();
		
		if (count($user_in_group) == 0)
		{
			$sql = "INSERT INTO phpgw_acl (acl_appname, acl_location, acl_account, acl_rights) "
			. "VALUES('phpgw_group','" . $gidnumber . "','" . $uidnumber . "','1')";
			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = lang('Error on function') . " db_functions->add_user2group.\n" . lang('Server returns') . ': ' . pg_last_error();
				return $result;
			}
		}
		$result['status'] = true;
		return $result;
	}

	function remove_user2group($gidnumber, $uidnumber)
	{
		$query = "SELECT acl_location FROM phpgw_acl WHERE acl_appname = 'phpgw_group' AND acl_location = '" . $gidnumber . "' AND acl_account = '" . $uidnumber . "'";
		if (!$this->db->query($query))
		{
			$result['status'] = false;
			$result['msg'] = lang('Error on function') . " db_functions->remove_user2group.\n" . lang('Server returns') . ': ' . pg_last_error();
			return $result;
		}
		while($this->db->next_record())
			$user_in_group[] = $this->db->row();
		
		if (count($user_in_group) > 0)
		{
			$sql = "DELETE FROM phpgw_acl WHERE acl_appname = 'phpgw_group' AND acl_location = '" . $gidnumber . "' AND acl_account = '".$uidnumber."'";
			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = lang('Error on function') . " db_functions->remove_user2group.\n" . lang('Server returns') . ': ' . pg_last_error();
				return $result;
			}
		}
		$result['status'] = true;
		return $result;
	}

	function add_pref_changepassword($uidnumber)
	{
		$query = "SELECT * FROM phpgw_acl WHERE acl_appname = 'preferences' AND acl_location = 'changepassword' AND acl_account = '" . $uidnumber . "'";
		if (!$this->db->query($query))
		{
			$result['status'] = false;
			$result['msg'] = lang('Error on function') . " db_functions->add_pref_changepassword.\n" . lang('Server returns') . ': ' . pg_last_error();
			return $result;
		}
		while($this->db->next_record())
			$user_pref_changepassword[] = $this->db->row();
		
		if (count($user_pref_changepassword) == 0)
		{
			$sql = "INSERT INTO phpgw_acl (acl_appname, acl_location, acl_account, acl_rights) "
			. "VALUES('preferences','changepassword','" . $uidnumber . "','1')";
			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = lang('Error on function') . " db_functions->add_pref_changepassword.\n" . lang('Server returns') . ': ' . pg_last_error();
				return $result;
			}
		}
		$result['status'] = true;
		return $result;
	}	

	function remove_pref_changepassword($uidnumber)
	{
		$query = "SELECT * FROM phpgw_acl WHERE acl_appname = 'preferences' AND acl_location = 'changepassword' AND acl_account = '" . $uidnumber . "'";
		if (!$this->db->query($query))
		{
			$result['status'] = false;
			$result['msg'] = lang('Error on function') . " db_functions->remove_pref_changepassword.\n" . lang('Server returns') . ': ' . pg_last_error();
			return $result;
		}
		while($this->db->next_record())
			$user_pref_changepassword[] = $this->db->row();
		
		if (count($user_pref_changepassword) != 0)
		{
			$sql = "DELETE FROM phpgw_acl WHERE acl_appname = 'preferences' AND acl_location = 'changepassword' AND acl_account = '".$uidnumber."'";
			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = lang('Error on function') . " db_functions->remove_pref_changepassword.\n" . lang('Server returns') . ': ' . pg_last_error();
				return $result;
			}
		}
		$result['status'] = true;
		return $result;
	}	
	
	function add_id2apps($id, $apps)
	{
		$result['status'] = true;
		
		if( $apps )
		{
			foreach( $apps as $app => $value )
			{
				$query = "SELECT * FROM phpgw_acl WHERE acl_appname = '".$app."' AND acl_location = 'run' AND acl_account = '" . $id . "'";
				
				if (!$this->db->query($query))
				{
					$result['status'] = false;
					$result['msg'] = lang('Error on function') . " db_functions->add_id2apps.\n" . lang('Server returns') . ': ' . pg_last_error();
					return $result;
				}
				
				$user_app = array();

				while( $this->db->next_record() )
				{
					$user_app[] = $this->db->row();
				}
					
				if( isset($user_app) && count($user_app) == 0 )
				{
					$sql = "INSERT INTO phpgw_acl (acl_appname, acl_location, acl_account, acl_rights) "
					. "VALUES('".$app."','run','" . $id . "','1')";
						
					if (!$this->db->query($sql))
					{
						$result['status'] = false;
						$result['msg'] = lang('Error on function') . " db_functions->add_id2apps.\n" . lang('Server returns') . ': ' . pg_last_error();
						return $result;
					}
					else
					{
						$this->write_log("Added application","$id:$app");	
					}
				}
			}
		}
		return $result;
	}

	function remove_id2apps($id, $apps)
	{
		$result['status'] = true;
		if ($apps)
		{
			foreach($apps as $app => $value)
			{
				$query = "SELECT acl_location FROM phpgw_acl WHERE acl_appname = '" . $app . "' AND acl_location = 'run' AND acl_account = '" . $id . "'";
				
				if (!$this->db->query($query))
				{
					$result['status'] = false;
					$result['msg'] = lang('Error on function') . " db_functions->remove_id2apps.\n" . lang('Server returns') . ': ' . pg_last_error();
					return $result;
				}
				while($this->db->next_record())
					$user_in_group[] = $this->db->row();
				
				if (count($user_in_group) > 0)
				{
					$sql = "DELETE FROM phpgw_acl WHERE acl_appname = '" . $app . "' AND acl_location = 'run' AND acl_account = '".$id."'";
					if (!$this->db->query($sql))
					{
						$result['status'] = false;
						$result['msg'] = lang('Error on function') . " db_functions->remove_id2apps.\n" . lang('Server returns') . ': ' . pg_last_error();
						return $result;
					}
					else
					{
						$this->write_log("Removed application from id","$id: $app");	
					}
				}
			}
		}
		return $result;
	}


	function get_user_info($uidnumber)
	{
		// Groups
		$query = "SELECT acl_location FROM phpgw_acl WHERE acl_appname = 'phpgw_group' AND acl_account = '".$uidnumber."'";
		$this->db->query($query);
		while($this->db->next_record())
			$user_app[] = $this->db->row();
		
		for ($i=0; $i<count($user_app); $i++)
			$return['groups'][] = $user_app[$i]['acl_location'];
		
		// ChangePassword
		$query = "SELECT acl_rights FROM phpgw_acl WHERE acl_appname = 'preferences' AND acl_location = 'changepassword' AND acl_account = '".$uidnumber."'";
		$this->db->query($query);
		while($this->db->next_record())
			$changepassword[] = $this->db->row();
		$return['changepassword'] = isset($changepassword[0]['acl_rights']) && $changepassword[0]['acl_rights'];
		
		// Apps
		$query = "SELECT acl_appname FROM phpgw_acl WHERE acl_account = '".$uidnumber."' AND acl_location = 'run'";
		$this->db->query($query);
		$user_apps = array();
		while($this->db->next_record())
			$user_apps[] = $this->db->row();
			
		if ( count( $user_apps ) )
		{
			foreach ($user_apps as $app)
			{
				$return['apps'][$app['acl_appname']] = '1';
			}
		}
		
		return $return;
	}
	
	function get_group_info($gidnumber)
	{
		// Apps
		$query = "SELECT acl_appname FROM phpgw_acl WHERE acl_account = '".$gidnumber."' AND acl_location = 'run'";
		$this->db->query($query);
		
		$group_apps = array();
		while( $this->db->next_record() ) $group_apps[] = $this->db->row();
		foreach ( $group_apps as $app ) $return['apps'][$app['acl_appname']] = '1';
		
		// Members
		$query = "SELECT acl_account FROM phpgw_acl WHERE acl_appname = 'phpgw_group' AND acl_location = '" . $gidnumber . "'";
		
		$this->db->query($query);
		while($this->db->next_record())
			$group_members[] = $this->db->row();

		if ($group_members)
		{
			foreach ($group_members as $member)
			{
				$return['members'][] = $member['acl_account'];
			}
		}
		else
			$return['members'] = array();

		//  ACL	Block Personal Data
		$query = "SELECT acl_rights FROM phpgw_acl WHERE acl_location = 'blockpersonaldata' AND acl_account = '" . $gidnumber . "'";
		$this->db->query($query);
		if($this->db->next_record()) {
			$block_personal_data = $this->db->row();
			$return['acl_block_personal_data'] = $block_personal_data['acl_rights'];
		}
		return $return;
	}
	
	function default_user_password_is_set($uid)
	{
		$query = "SELECT uid FROM phpgw_expressoadmin_passwords WHERE uid = '" . $uid . "'";

		$this->db->query($query);
		
		$userPassword = array();

		while( $this->db->next_record() )
		{
			$userPassword[] = $this->db->row();
		}
		
		if (isset($userPassword) && count($userPassword) == 0)
			return false;
		else
			return true;
	}
	
	function set_user_password($uid, $password)
	{
		$query = "SELECT uid FROM phpgw_expressoadmin_passwords WHERE uid = '" . $uid . "'";
		
		$this->db->query($query);
		
		$user = array();

		while($this->db->next_record())
		{
			$user[] = $this->db->row();
		}
		
		if( isset($user) && count($user) == 0 )
		{
			$sql = "INSERT INTO phpgw_expressoadmin_passwords (uid, password) VALUES('".$uid."','".$password."')";

			if (!$this->db->query($sql))
			{
				$result['status'] = false;
				$result['msg'] = lang('Error on function') . " db_functions->set_user_password.\n" . lang('Server returns') . ': ' . pg_last_error();
				return $result;
			}
		}
		
		return true;
	}
	
	function get_user_password($uid)
	{
		$query = "SELECT password FROM phpgw_expressoadmin_passwords WHERE uid = '" . $uid . "'";
		$this->db->query($query);
		while($this->db->next_record())
		{
			$userPassword[] = $this->db->row();
		}
		
		if (isset($userPassword) && count($userPassword) == 1)
		{
			$sql = "DELETE FROM phpgw_expressoadmin_passwords WHERE uid = '" . $uid . "'";
			$this->db->query($sql);
			return $userPassword[0]['password'];
		}
		else
			return false;
	}
	
	function delete_user($user_info)
	{
		// AGENDA
		$this->db->query('SELECT cal_id FROM phpgw_cal WHERE owner ='.$user_info['uidnumber']);
		while($this->db->next_record())
		{
			$ids[] = $this->db->row();
		}
		if (count($ids))
		{
			foreach($ids as $i => $id)
			{
				$this->db->query('DELETE FROM phpgw_cal WHERE cal_id='.$id['cal_id']);
				$this->db->query('DELETE FROM phpgw_cal_user WHERE cal_id='.$id['cal_id']);
				$this->db->query('DELETE FROM phpgw_cal_repeats WHERE cal_id='.$id['cal_id']);
				$this->db->query('DELETE FROM phpgw_cal_extra WHERE cal_id='.$id['cal_id']);
			}
		}
		
		// CONTATOS pessoais e grupos.
		$this->db->query('SELECT id_contact FROM phpgw_cc_contact WHERE id_owner ='.$user_info['uidnumber']);
		while($this->db->next_record())
		{
			$ids[] = $this->db->row();
		}

		if (count($ids))
		{
			foreach($ids as $i => $id_contact)
			{
				$this->db->query('SELECT id_connection FROM phpgw_cc_contact_conns WHERE id_contact='.$id_contact['id_contact']);
				while($this->db->next_record())
				{
					$id_conns[] = $this->db->row();
				}
				if (count($id_conns))
				{
					foreach($id_conns as $j => $id_conn)
					{
						$this->db->query('DELETE FROM phpgw_cc_connections WHERE id_connection='.$id_conn['id_connection']);
						$this->db->query('DELETE FROM phpgw_cc_contact_grps WHERE id_connection='.$id_conn['id_connection']);
					}
				}
					
				$this->db->query('SELECT id_address FROM phpgw_cc_contact_addrs WHERE id_contact='.$id_contact['id_contact']);
				while($this->db->next_record())
				{
					$id_addresses[] = $$this->db->row();
				}
				if (count($id_addresses))
				{
					foreach($id_addresses as $j => $id_addrs)
					{
						$this->db->query('DELETE FROM phpgw_cc_addresses WHERE id_address='.$id_addrs['id_address']);
					}
				}
				$this->db->query('DELETE FROM phpgw_cc_contact WHERE id_contact='.$id_contact['id_contact']);
				$this->db->query('DELETE FROM phpgw_cc_contact_conns WHERE id_contact='.$id_contact['id_contact']);
				$this->db->query('DELETE FROM phpgw_cc_contact_addrs WHERE id_contact='.$id_contact['id_contact']);
			}
		}
		$this->db->query('DELETE FROM phpgw_cc_groups WHERE owner='.$user_info['uidnumber']);
			
		// PREFERENCIAS
		$this->db->query('DELETE FROM phpgw_preferences WHERE preference_owner='.$user_info['uidnumber']);
			
		// ACL
		$this->db->query('DELETE FROM phpgw_acl WHERE acl_account='.$user_info['uidnumber']);
		
		// Corrigir
		$return['status'] = true;
		return $return;
	}

	function delete_group($gidnumber)
	{
		// ACL
		$this->db->query("DELETE FROM phpgw_acl WHERE acl_location='{$gidnumber}'");
		$this->db->query('DELETE FROM phpgw_acl WHERE acl_account='.$gidnumber);
		
		// Corrigir
		$return['status'] = true;
		return $return;
	}
	
	function write_log($action, $about)
	{
		if (
			isset( $_SESSION['phpgw_session']['session_lid'] ) && ( !empty( $_SESSION['phpgw_session']['session_lid'] ) )
		)	$manager = $_SESSION['phpgw_session']['session_lid'];
		else if (
			isset( $_SESSION['phpgw_info']['expresso']['user']['account_lid'] ) && ( !empty( $_SESSION['phpgw_info']['expresso']['user']['account_lid'] ) )
		)	$manager = $_SESSION['phpgw_info']['expresso']['user']['account_lid'];
		else
			$manager = 'unknown';
		
		$sql = "INSERT INTO phpgw_expressoadmin_log (date, manager, action, userinfo) "
		. "VALUES('now','" . $manager . "','" . strtolower($action) . "','" . strtolower($about) . "')";
		
		if (!$this->db->query($sql))
		{
			//echo pg_last_error();
			return false;
		}
		
		return true;
	}
	
	function get_sieve_info()
	{
		$this->db->query('SELECT profileID,imapenablesieve,imapsieveserver,imapsieveport FROM phpgw_emailadmin');
		
		$i=0;
		while($this->db->next_record())
		{
			$serverList[$i]['profileID']		= $this->db->f(0);
			$serverList[$i]['imapenablesieve']	= $this->db->f(1);
			$serverList[$i]['imapsieveserver']	= $this->db->f(2);
			$serverList[$i]['imapsieveport']	= $this->db->f(3);
			$i++;
		}
		
		return $serverList;
	}
	
	function get_apps($account_lid)
	{
		$this->db->query("SELECT * FROM phpgw_expressoadmin_apps WHERE manager_lid = '".$account_lid."'");
		
		while($this->db->next_record())
		{
			$tmp = $this->db->row();
			$availableApps[$tmp['app']] = 'run'; 
		}
			
		return $availableApps;
	}
	
	function get_sambadomains_list()
	{
		$query = "SELECT * FROM phpgw_expressoadmin_samba ORDER by samba_domain_name ASC"; 
		$this->db->query($query);
		while($this->db->next_record())
			$result[] = $this->db->row();
		return $result;
	}
	
	function exist_domain_name_sid($sambadomainname, $sambasid)
	{
		$query = "SELECT * FROM phpgw_expressoadmin_samba WHERE samba_domain_name='$sambadomainname' OR samba_domain_sid='$sambasid'"; 
		$this->db->query($query);
		while($this->db->next_record())
			$result[] = $this->db->row();
		
		if (count($result) > 0)
			return true;
		else
			return false;
	}
	
	function delete_sambadomain($sambadomainname)
	{
		$this->db->query("DELETE FROM phpgw_expressoadmin_samba WHERE samba_domain_name='$sambadomainname'");
		return;
	}
	
	function add_sambadomain($sambadomainname, $sambasid)
	{
		$sql = "INSERT INTO phpgw_expressoadmin_samba (samba_domain_name, samba_domain_sid) VALUES('$sambadomainname','$sambasid')";
		$this->db->query($sql);
		return;
	}
	
	function test_db_connection($params)
	{
		$host = $params['host'];
		$port = $params['port'];
		$name = $params['name'];
		$user = $params['user'];
		$pass = $params['pass'];

		$con_string = "host=$host port=$port dbname=$name user=$user password=$pass";
		if ($db = pg_connect($con_string))
		{
			pg_close($db);
			$result['status'] = true;
		}
		else
		{
			$result['status'] = false;
		}
			
		return $result;
	}
	
	function manager_lid_exist($manager_lid)
	{
		$query = "SELECT manager_lid FROM phpgw_expressoadmin WHERE manager_lid = '" . $manager_lid . "'";
		$this->db->query($query);
		while($this->db->next_record())
			$result[] = $this->db->row();
		if (count($result) > 0)
			return true;
		else
			return false;
	}
	
	function create_manager($params, $manager_acl)
	{
		//Escrevre no Banco a ACL do gerente
		$sql = "INSERT INTO phpgw_expressoadmin (manager_lid, context, acl) "
		. "VALUES('" . $params['ea_select_manager'] . "','" . $params['context'] . "','" . $manager_acl . "')";
		$this->db->query($sql);
			
		//Escrevre no Banco as aplicações que o gerente tem direito de disponibilizar aos seus usuarios.
		if (count($_POST['applications_list']))
		{
			foreach($_POST['applications_list'] as $app=>$value)
			{
				$sql = "INSERT INTO phpgw_expressoadmin_apps (manager_lid, context, app) "
				. "VALUES('" . $_POST['manager_lid'] . "','" . $_POST['context'] . "','" . $app . "')";
				$this->db->query($sql);
			}
		}
		
		return;
	}
	
	function save_manager($params, $manager_acl)
	{
		$params['manager_lid'] = $params['hidden_manager_lid'];
		
		//Executa update na tabela para atualizar ACL
		$sql = "UPDATE phpgw_expressoadmin SET context = '" . $params['context'] . "',acl = '" . $manager_acl
		. "' WHERE manager_lid = '" . $params['manager_lid'] ."'";
		$this->db->query($sql);
			
		//Deleta as aplicações e adiciona as novas.
		//Deleta
		$sql = "DELETE FROM phpgw_expressoadmin_apps WHERE manager_lid = '" . $params['manager_lid'] . "'";
		$this->db->query($sql);
					
		// Adiciona
		if (count($params['applications_list']))
		{
			foreach($params['applications_list'] as $app=>$value)
			{
				$sql = "INSERT INTO phpgw_expressoadmin_apps (manager_lid, context, app) "
				. "VALUES('" . $params['manager_lid'] . "','" . $params['context'] . "','" . $app . "')";
				$this->db->query($sql);
			}
		}
			
		return;
	}
	
	function delete_context_managers( $context )
	{
		if ( !$this->db->Link_ID->query(
			'UPDATE phpgw_expressoadmin '.
			'SET context = result.newcontext '.
			'FROM ( '.
				'SELECT subr.manager_lid, COALESCE( subl.newcontext, \'\' ) AS newcontext '.
				'FROM ( '.
					'SELECT sub.manager_lid, array_to_string( array_agg( contexts ), \'%\' ) AS newcontext '.
					'FROM ( '.
						'SELECT manager_lid, unnest( string_to_array( context, \'%\' ) ) AS contexts '.
						'FROM phpgw_expressoadmin '.
						'WHERE context LIKE ? '.
					') AS sub '.
					'WHERE sub.contexts NOT LIKE ? '.
					'GROUP BY sub.manager_lid '.
				') AS subl '.
				'RIGHT JOIN ( '.
					'SELECT manager_lid '.
					'FROM phpgw_expressoadmin '.
					'WHERE context LIKE ? '.
				') AS subr ON subr.manager_lid = subl.manager_lid '.
			') AS result '.
			'WHERE phpgw_expressoadmin.manager_lid = result.manager_lid;',
			array( '%'.$context.'%', '%'.$context, '%'.$context.'%' )
		) ) return false;
		
		if ( !$this->db->Link_ID->query(
			'UPDATE phpgw_expressoadmin_apps AS app '.
			'SET context = adm.context '.
			'FROM phpgw_expressoadmin AS adm '.
			'WHERE app.manager_lid = adm.manager_lid;'
		) ) return false;
		
		return true;
	}
	
	function save_acl_personal_data($gidnumber, $new_acl_personal_data, $op = '') {
		if($op == 'add')
			$sql = "INSERT INTO phpgw_acl (acl_appname, acl_location, acl_account, acl_rights) "
				. "VALUES('preferences','blockpersonaldata','$gidnumber',$new_acl_personal_data)";		
		elseif($op == 'remove')
			$sql = "DELETE FROM phpgw_acl WHERE acl_account = '$gidnumber' and acl_location = 'blockpersonaldata'";
		else
			$sql = "UPDATE phpgw_acl SET acl_rights = $new_acl_personal_data WHERE acl_account = '$gidnumber' "
				. "and acl_location = 'blockpersonaldata'";
		
		$this->db->query($sql);
		
		return;
	}

	function addMBoxMigrate( $mailBoxes )
	{
		if ( !count($mailBoxes) ) return false;
		
		$query = "INSERT INTO phpgw_emailadmin_mbox_migrate(uid, profileid_orig, profileid_dest, data) VALUES ";
		
		foreach( $mailBoxes as $value )
		{	
			$query .= "('".$this->db->db_addslashes($value['uid'])."',";
			$query .= "'".$this->db->db_addslashes($value['profileid_orig'])."',";
			$query .= "'".$this->db->db_addslashes($value['profileid_dest'])."',";
			$query .= "'".$this->db->db_addslashes($value['data'])."'),";
		}
		
		$query = substr($query, 0, strlen($query) -1 ) . " RETURNING mboxmigrateid;";
		
		if (!$this->db->query($query)) return false;
		
		$async = CreateObject('phpgwapi.asyncservice');
		
		foreach ($this->db->Query_ID->GetArray() as $row) {
			$async->add(
				'mbox:'.$row['mboxmigrateid'],
				time()+10,
				'expressoAdmin1_2.user.mbox_migrate',
				array('id' => $row['mboxmigrateid']),
				false,
				20
			);
		}
		return true;
	}
	
	function getMBoxMigrate( $id = false , $uid = false )
	{
		$query =
			'SELECT '.
				'def.uid, '.
				'def.profileid_orig, '.
				'def.profileid_dest, '.
				'def.data, '.
				'def.status, '.
				'( '.
					'SELECT count(cnt.mboxmigrateid) '.
					'FROM phpgw_emailadmin_mbox_migrate AS cnt '.
					'WHERE def.mboxmigrateid >= cnt.mboxmigrateid AND cnt.uid NOT IN ( '.
						'SELECT err.uid '.
						'FROM phpgw_emailadmin_mbox_migrate AS err '.
						'WHERE err.status = -1 '.
					') '.
				') AS queue, '.
				'array_to_string(array( '.
					'SELECT DISTINCT prev.status::text '.
					'FROM phpgw_emailadmin_mbox_migrate AS prev '.
					'WHERE def.uid = prev.uid AND def.mboxmigrateid > prev.mboxmigrateid '.
					'ORDER BY status '.
				'),\',\') AS previous_status '.
			'FROM phpgw_emailadmin_mbox_migrate AS def '.
			'WHERE '.($id? 'mboxmigrateid = '.((int)$id) : ($uid? 'uid = \''.$uid.'\'' : '1 = 1')).' '.
			'ORDER BY def.mboxmigrateid DESC '.
			'LIMIT 1';

		$this->db->query($query);

		return $this->db->next_record()? $this->db->row() : false;
	}
	
	function setMBoxMigrateStatus( $id, $status )
	{
		$query =
			($status == 'success'?
				'DELETE FROM phpgw_emailadmin_mbox_migrate ' :
				'UPDATE phpgw_emailadmin_mbox_migrate SET status = '.( $status == 'exec'? 1 : -1 ).' '
			). 'WHERE mboxmigrateid = '.((int)$id);
		
		$this->db->query($query);
	}

	function getProtectedFields( $uidNumber, $field )
	{
		$query = 'SELECT * FROM phpgw_expressoadmin_protected_fields ' .
					'WHERE uidnumber = \''.$uidNumber.'\' AND field = \''.$field.'\' ' .
					'ORDER BY date DESC LIMIT 1;';
		
		$this->db->query( $query );

		return ( $this->db->next_record() ? true : false );
	}

	function setProtectedField( $uidNumber, $field, $value )
	{
		$query = 'INSERT INTO phpgw_expressoadmin_protected_fields( uidnumber, field, value, date )' .
					'VALUES(\''.$uidNumber.'\', \''.$field.'\', \''.$value.'\', \''.date("Y-m-d H:i:s").'\');';

		return ( !$this->db->query( $query ) ? false :  true );
	}
}
?>
