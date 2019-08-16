<?php

//Conecta com o Servidor e o servico Sieve;
class SieveS{
		
	var $host;
	var $port;
	var $user;
	var $pass;
	var $proxy;
	
	var $implementation;
	var $saslmethods;
	var $extensions;
	var $starttls_avail;
	var $socket;
	var $socket_timeout;
	
	var $scriptlist;
	var $activescript;
	var $errstr;
	var $errnum;
	var $ScriptS;
	
	function SieveS(){
		
		$this->host = $_SESSION['phpgw_info']['expressomail']['email_server']['imapSieveServer'];
		$this->port = $_SESSION['phpgw_info']['expressomail']['email_server']['imapSievePort'];
		$this->user = $_SESSION['phpgw_info']['expressomail']['user']['account_lid'];	
		$this->pass = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
		$this->proxy = '';
		
		$this->socket_timeout = 5;
		$this->implementation = array('unknown');
		$this->saslmethods 	  = array('unknown');
		$this->extensions 	  = array('unknown');
		$this->starttls_avail = false;
		$this->scriptlist     = array();
		$this->activescript   = '';
		$this->errstr 		  = '';
		$this->errnum 		  = ''; 
		
	}	
	
	function start(){
		// Cria a conexao;
		if(!isset($this->socket)){
			$this->socket = fsockopen($this->host, $this->port, $this->errnum, $this->errstr, "60");

		}
		// Verifica a conexao;
		if(!$this->socket){
			return "nao conectado";
		}
		
		$said = $this->read();
		if (!preg_match("/timsieved/i",$said)) {
		    $this->close();
		    $this->errstr = "start: bad response from $this->host: $said";
		    return false;
		}
		
		if (preg_match("/IMPLEMENTATION/",$said)){
		  while (!preg_match("/^OK/",$said)) {
		    if (preg_match("/^\"IMPLEMENTATION\" +\"(.*)\"/",$said,$bits)){
				$this->implementation = $bits[1];
		    }
		    elseif (preg_match("/^\"SASL\" +\"(.*)\"/",$said,$bits)) {
				$auth_types = $bits[1];
				$this->saslmethods = explode(" ", $auth_types);
		    }
		    elseif (preg_match("/^\"SIEVE\" +\"(.*)\"/",$said,$bits)) {
				$extensions = $bits[1];
				$this->extensions = explode(" ", $extensions);
		    }
	        elseif (preg_match("/^\"STARTTLS\"/",$said)){
	           $this->starttls_avail = true;
	        }
		    $said = $this->read();
		  }
		}
		else
		{
		    // assume cyrus v1.
		    if (preg_match("/\"(.+)\" +\"(.+)\"/",$said,$bits)) {
				$this->implementation = $bits[1];
				$sasl_str = $bits[2];  // should look like: SASL={PLAIN,...}
		    }
			if (preg_match("/SASL=\{(.+)\}/",$sasl_str,$morebits)) {
			    $auth_types = $morebits[1];
			    $this->saslmethods = explode(", ", $auth_types);
			}else {
				// a bit desperate if we get here.
				$this->implementation = $said;
				$this->saslmethods = $said;
		    }
		}
		
		$authstr = $this->proxy . "\x00" . $this->user . "\x00" . $this->pass;
		$encoded = base64_encode($authstr);		
		$len = strlen($encoded);

		//fputs($this->socket,"AUTHENTICATE \"PLAIN\" \{$len+}\r\n");
		//fputs($this->socket,"$encoded\r\n");

		fwrite($this->socket, 'AUTHENTICATE "PLAIN" {' . $len . '+}' . "\r\n");
		fwrite($this->socket,"$encoded\r\n");
		
		$said = $this->read();
	
		if (preg_match("/NO/",$said)) {
		    $this->close();
		    $this->errstr = "start: authentication failure connecting to $this->host";
		    return false;
		}
		elseif (!preg_match("/OK/",$said)) {
		    $this->close();
		    $this->errstr = "start: bad authentication response from $this->host: $said";
		    return false;
		}
	
		return true;
		
	}
	
	function close(){
	
		if(!$this->socket){
			return true;	
		}	
		fputs($this->socket,"LOGOUT\r\n");
		$rc = fclose($this->socket);
		if($rc != 1){
			$this->errstr = "close: failed closing socket to $this->server";
			return false;
		}
		return true;
	}
	
	function read(){
	
		$buffer = '';
		
		// Verifca a conexao;
		if(!$this->socket){
			return $buffer;
		}
		
		//Funcoes do php
		socket_set_timeout($this->socket,$this->socket_timeout);
		socket_set_blocking($this->socket,true);
		
		//Le um caracter de cada vez e o adiciona na variavel buffer;
		while ( is_resource( $this -> socket ) && ( ! feof( $this -> socket ) ) )
		{
			$char = fread($this->socket,1);
			
			$status = socket_get_status($this->socket);
			if($status['timed_out'])
				return $buffer;
			
			if(($char == "\n") || ($char == "\r")){
				if($char == "\r")
					fread($this->socket,1);
				return $buffer;
			}
			$buffer .= $char;
		}
		return $buffer;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Manipulacao dos scripts
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	function listscripts(){
		
		$bits = '';

		//Verifica a conexao
		if(!is_resource($this->socket)){ 
			$this->errstr = "listscripts: sem conexao para o servidor $this->host";
			return false;
		}

		$scripts = array();
		fputs($this->socket,"LISTSCRIPTS\r\n");
	
		$said = $this->read();
		while (is_resource($this->socket) && (!preg_match("/^OK/",$said) && !preg_match("/^NO/",$said))) { 
	
		    // Cyrus v1 script lines look like '"script*"' with the 
		    // asterisk denoting the active script. Cyrus v2 script 
		    // lines will look like '"script" ACTIVE' if active.
	
		    if (preg_match("/^\"(.+)\"\s*(.+)*$/m",$said,$bits)) {
			if (preg_match("/\*$/",$bits[1])){
			    $bits[1] = preg_replace("/\*$/","",$bits[1]);
			    $this->activescript = $bits[1];
			}
			if (isset($bits[2]) && $bits[2] == 'ACTIVE')
			    $this->activescript = $bits[1];
			array_push($scripts,$bits[1]);
		    }
		    $said = $this->read();
		}
	
		if (preg_match("/^OK/",$said)) {
		    $this->scriptlist = $scripts;
            return $this->scriptlist;
        }
		
	}
	
	// Pega o conteudo do script no servidor	
	function getscript(){

		$scriptfile = $this->listscripts();
		
		// verifica se existe o script;
		if($scriptfile == ""){
			return "Falta o script";
		}
		
		if(!$this->socket){
			return "Falha na conexao";	
		}
		
		$script = '';
		
		fputs($this->socket,"GETSCRIPT \"$scriptfile[0]\"\r\n");
		$said = $this->read();
		while ((!preg_match("/^OK/",$said)) && (!preg_match("/^NO/",$said))) {
		    // replace newlines which read() removed
		    if (!preg_match("/\n$/",$said)) $said .= "\n";
		    $script .= $said;
		    $said = $this->read();
		}
		
		if($said == "OK"){
			return $script;	
		}else{
			return false;
		}
	}

	//envia para o servidor o nome do script($scriptfile) e seu conteudo($script)
	function putscript ($scriptfile,$script) {
		if (!isset($scriptfile)) {
	            $this->errstr = "Nao foi possivel enviar o script para o servidor";
	            return false;
	    }
		if (!isset($script)) {
	            $this->errstr = "Nao foi possivel enviar o script para o servidor";
	            return false;
	    }
		if (!$this->socket) {
	            $this->errstr = "Sem conexao com o servidor $this->server";
	            return false;
	    }
	
		$len = strlen($script);

		//fputs($this->socket,"PUTSCRIPT \"$scriptfile\" \{$len+}\r\n");
		//fputs($this->socket,"$script\r\n");
	
		fwrite($this->socket, 'PUTSCRIPT "'.$scriptfile.'" {' . $len . '+}' . "\r\n");	
		fwrite($this->socket,"$script\r\n");
	
		$said = '';
		while ($said == '') {
		    $said = $this->read();
		}
	 
	    if (preg_match("/^OK/",$said)) {
		    return true;
		}
	
	    $this->errstr = "Nao foi possivel enviar o $scriptfile: $said";
	    return false;
    }
    
    // Ativa o script para o servico sieve;
    function activatescript ($scriptfile) {
		if (!isset($scriptfile)) {
	            $this->errstr = "activatescript: no script file specified";
	            return false;
	    }
	
	    if (!$this->socket) {
	            $this->errstr = "activatescript: no connection open to $this->server";
	            return false;
	    }
	
		fputs($this->socket,"SETACTIVE \"$scriptfile\"\r\n");
	
		$said = $this->read();
	
		if (preg_match("/^OK/",$said)) {
	            return true;
	    }
	
		$this->errstr = "activatescript: could not activate script $scriptfile: $said";
	    return false;
    }

    // Deleta o script do servico sieve;
    function deletescript ($scriptName) {
    	if(!isset($scriptName)){
    		$this->errstr = "deletescript: no script file specified";
    		return false;
    	}
    	
    	// Verifica a conexao;
    	if(!$this->socket){
    		$this->errstr = "deletescript : no connection open to $this->server";
    		return false;
    	}
    
    	fputs($this->socket,"DELETESCRIPT \"$scriptName\"\r\n");
    	
    	$said = $this->read();
    	
    	if(preg_match("/^OK/",$said)) {
    		return true;	
    	}
    	
    	$this->errstr = "deletescript: could not delete script $scriptName: $said";
    	return false;
    }
	public function getExtensions() {
		return $this->extensions;
	}
	public function hasExtension( $name ) {
		return in_array( $name, $this->getExtensions() );
	}
}
?>
