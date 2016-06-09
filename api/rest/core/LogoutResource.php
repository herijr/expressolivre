<?php
class LogoutResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","Logout","Desloga o usuário, invalidando a chave de autenticação.",array("POST"));
		$this->setIsMobile(true);

		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		if($this-> isLoggedIn())
		{	if ($_SESSION['phpgw_session']['session_id'] && file_exists($GLOBALS['phpgw_info']['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']))	
			{
				$dh = opendir($GLOBALS['phpgw_info']['server']['temp_dir']. SEP . $_SESSION['phpgw_session']['session_id']);
				while ($file = readdir($dh)) 
				{
					if ($file != '.' && $file != '..') 
					{
						unlink($GLOBALS['phpgw_info']['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id'].SEP.$file);
					}
				}
				rmdir($GLOBALS['phpgw_info']['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']);
			}
			$GLOBALS['phpgw']->hooks->process('logout');
			$GLOBALS['phpgw']->session->destroy($_SESSION['phpgw_session']['session_id'], $GLOBALS['kp3']);
			$this->setResult(true);
		}
		
		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}	
}