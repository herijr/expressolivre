<?php

class GetUsersResource extends AdminAdapter
{

	public function setDocumentation() {

		$this->setResource("Admin","Admin/GetUsers","Faz a busca do(s) usuário(s) no Expresso, necessário ter a permissão no Módulo ExpressoAdmin",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("accountUidNumber","string",false,"UIDNumber do usuário para edição");
		$this->addResourceParam("accountSearchUser","string",false,"Procurar usuários por nome, email, uid ( igual ao módulo admin do expresso )");
		$this->addResourceParam("accountSearchUserLID","string",false,"Procurar usuários somente por uid( igual ao módulo admin do expresso )");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			// Permission
			$permission = array();
			$permission['apps'] = $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();

			$uidUser 	= $this->getParam('accountUidNumber');
			$searchUser = $this->getParam('accountSearchUser');
			$searchUserLiteral= $this->getParam('accountSearchUserLID');

			//Validate Fields
			$uidUser = str_replace("*","",$uidUser);
			$uidUser = str_replace("%","",$uidUser);

			$searchUser = str_replace("*","", $searchUser);
			$searchUser = str_replace("%","", $searchUser);

			$searchUserLiteral = str_replace("*","", $searchUserLiteral);
			$searchUserLiteral = str_replace("%","", $searchUserLiteral);

			if( trim($uidUser) != "" && isset($uidUser) )
			{
				$permission['action'] = 'edit_users';

				if( $this->validatePermission($permission) )
				{
					// Get User
					$fields = $this->editUser($uidUser);

					if( $fields != false )
					{
						// Return fields
						$return = array();
						$return[] = array(
							'accountUidnumber'		=> $fields['uidnumber'],
							'accountLogin'			=> $fields['uid'],
							'accountEmail'			=> $fields['mail'],
							'accountName'			=> $fields['givenname']." ".$fields['sn'],
							'accountPhone'			=> $fields['telephonenumber'],
							'accountCpf'			=> $fields['corporative_information_cpf'],
							'accountRg'				=> $fields['corporative_information_rg'],
							'accountRgUf'			=> $fields['corporative_information_rguf'],
							'accountDescription'	=> $fields['corporative_information_description'],
							'accountMailQuota'		=> $fields['mailquota']
						);

						$this->setResult( array( "users" => $return ));
					}
					else
						Errors::runException( "ADMIN_USER_NOT_FOUND" );
				}
				else
				{
					Errors::runException( "ACCESS_NOT_PERMITTED" );
				}
			}
			else
			{
				$permission['action'] = 'list_users';

				if( $this->validatePermission($permission) )
				{
					// Return list
					$return = array();

					if( trim($searchUser) != "" && isset($searchUser) )
					{
						$list = $this->listUsers( $searchUser );

						foreach( $list as $key => $users )
						{
							$return[] = array(
								'accountId' 	=> $users['account_id'],
								'accountLid'	=> $users['account_lid'],
								'accountCn'		=> $users['account_cn'],
								'accountMail'	=> $users['account_mail']
							);
						}

						if( count($return) > 0 )
						{
							$this->setResult( array( "users" => $return ) );
						}
						else
						{
							Errors::runException( "ADMIN_USERS_NOT_FOUND" );
						}
					}
					else
					{
						$user = $this->listUsersLiteral($searchUserLiteral);

						if ( $user )
						{
							$return[] = array(
								'accountId' 	=> $user['account_id'],
								'accountLid'	=> $user['account_lid'],
								'accountCn'		=> $user['account_cn'],
								'accountMail'	=> $user['account_mail']
							);

							$this->setResult( array( "users" => $return ) );
						}
						else
							Errors::runException( "ADMIN_USER_NOT_FOUND" );
					}

				}
				else
				{
					Errors::runException( "ACCESS_NOT_PERMITTED" );
				}
			}
		}

		return $this->getResponse();
	}
}

?>
