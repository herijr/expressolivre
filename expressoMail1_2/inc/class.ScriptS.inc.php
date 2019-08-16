<?php

//Inclui o arquivo contendo a classe SieveS;
include('class.SieveS.inc.php');

class ScriptS {

    //Declaracao de Variaveis;
    var $SieveS;   // Var para criacao do objeto;
    var $reply;  // Var para resposta;
    var $scriptfile;  // Nome do script;
    var $username;   // Nome do usuario;
    var $rules;  // Regras do sieve;
    var $errstr;   // Erros retornados;
    var $size;  // Tamanho;
    var $so;  // Verifica se a regra foi criada por outro tipo servico de filtros;
    var $continuebit;
    var $sizebit;
    var $allofbit;
    var $keepbit;
    var $regexbit;
    var $newrules = array();
    var $newout;
    var $teste;
    var $EmailVoip;
    var $EmailExpresso;

    function ScriptS() {

        //Cria o objeto;
        $this->SieveS = new SieveS();

        //$this->scriptfile = $GLOBALS['HTTP_SESSION_VARS']['phpgw_info']['expressomail']['user']['account_lid'];
        $this->scriptfile = $_SESSION['phpgw_info']['expressomail']['user']['account_lid'];
        $this->username = $this->scriptfile;

        $this->reply    = "";
        $this->rules    = "";
        $this->errstr   = "";
        $this->size     = "";

        $this->continuebit  = 1;
        $this->sizebit      = 2;
        $this->allofbit     = 4;
        $this->keepbit      = 8;
        $this->regexbit     = 128;

        $this->EmailVoip = trim($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['voip_email_redirect']);
        $this->EmailExpresso = trim($_SESSION['phpgw_info']['expressomail']['user']['email']);
    }

    function init_a() {

        //Abre a conexao
        $this->SieveS->start();

        $this->reply = $this->SieveS->getscript();

        if (!$this->reply) {

            $aux = $this->SieveS->putscript($this->scriptfile, $this->createScript());
            if (!$aux) {
                // Caso de erro, grava dentro da variavel errstr;
                $this->errstr = "Error: file not created";
                return $this->errstr;
            }
            // Mata a variavel;
            unset($aux);
            // Ativa o script;
            $aux = $this->SieveS->activatescript($this->scriptfile);

            if (!$aux) {
                // Caso de erro, grava dentro da variavel errstr;
                $this->errstr = "Error: error to activate file";
                return $this->errstr;
            } else {
                $this->reply = $this->SieveS->getscript();
                $this->rules = $this->readScript($this->reply);
            }
        } else {
            $this->rules = $this->readScript($this->reply);
        }

        //Fecha a conexao
        $this->SieveS->close();

        if ($this->rules)
            return $this->rules;
    }

    function rec_rules($params) {

        $newr1 = array();
        $newr2 = array();
        $newr3 = array();

        $var_decode = rawurldecode($params['arfilter']);
        $var_decode = preg_replace('/\n\./', '.', $var_decode);

        $narray = explode("_end_", $var_decode);

        foreach ($narray as $key => $tmp) {
            if ($tmp != "") {
                $newr1[] = $tmp;
            }
        }
        unset($key);
        unset($tmp);
        foreach ($newr1 as $key => $tmp) {
            $tmp2 = explode("_begin_##", $tmp);
            foreach ($tmp2 as $tmp3) {
                if ($tmp3 != "") {
                    $newr2[] = trim($tmp3);
                }
            }
        }

        unset($tmp);
        unset($tmp2);
        unset($tmp3);

        foreach ($newr2 as $tmp) {
            if (trim($tmp) != "") {
                $tmp2 = explode("##", $tmp);
                foreach ($tmp2 as $tmp3) {
                    $tmp4 .= trim($tmp3) . "&&";
                }
                $newr3[] = substr($tmp4, 0, (strlen($tmp4) - 4));
                unset($tmp2);
                unset($tmp3);
                unset($tmp4);
            }
        }

        $tmp = $newr3[count($newr3) - 1];

        if (substr($tmp, 0, 9) == "#vacation") {
            $this->newout = array_pop($newr3);
            foreach ($newr3 as $key => $tmp) {
                $this->newrules[] = $tmp;
            }
        } else {
            foreach ($newr3 as $tmp) {
                $this->newrules[] = $tmp;
            }
        }

        unset($tmp);
        $tmp = explode("&&", $this->newout);
        $tmp1 = explode(",", $tmp[2]);
        foreach ($tmp1 as $key => $tmp2) {
            $tmp3 .= stripslashes(trim($tmp2)) . ", ";
        }
        $tmp3 = substr($tmp3, 0, (strlen($tmp3) - 2));

        unset($tmp);
        unset($tmp1);
        unset($tmp2);
        unset($key);
        $tmp = explode("&&", $this->newout);
        foreach ($tmp as $key => $tmp1) {
            if ($key == 2) {
                $tmp2 .= trim($tmp3) . "&&";
            } else {
                $tmp2 .= trim($tmp1) . "&&";
            }
        }
        unset($this->newout);
        $this->newout = substr($tmp2, 0, (strlen($tmp2) - 2));

        //Abre a conexao
        $this->SieveS->start();
        $this->errstr = "";

        // Escreve a nova regra;
        $this->reply = $this->SieveS->getscript();
        /*
          if($this->reply){
          $this->errstr = $this->SieveS->deletescript($this->scriptfile);
          }
         */

        $error_log_file = "/home/expressolivre/sieve_error.log";
        //Escreve a(s) nova(s) regra(s);
        $newrule = $this->write_rule();
        if (strlen($newrule) > 0)
            $this->errstr = $this->SieveS->putscript($this->scriptfile, $newrule);
        else {
            if ($_SESSION['phpgw_info']['server']['expressomail']['expressoMail_enable_log_messages'] == "True")
                error_log(date("D M j G:i:s T Y") . ": SieveError, Invalid rule for "
                        . $_SESSION['phpgw_info']['expressomail']['user']['userid'] . "=>"
                        . $this->teste . "\nRule:"
                        . $var_decode . "\n", 3, $error_log_file);
            return "Invalid rule\n" . $this->teste;
        }

        //Ativa o script;
        $this->errstr = $this->SieveS->activatescript($this->scriptfile);

        //Fecha a conexao
        $this->SieveS->close();

        if ($this->errstr) {
            return "Ok";
        } else {
            if ($_SESSION['phpgw_info']['server']['expressomail']['expressoMail_enable_log_messages'] == "True")
                error_log(date("D M j G:i:s T Y")
                        . ": SieveError, Problem for "
                        . $_SESSION['phpgw_info']['expressomail']['user']['userid'] . "=>"
                        . " "
                        . $this->SieveS->errstr . "\n", 3, $error_log_file);
            return "Problemas na criacao do arquivo!\n" . $this->teste;
        }
    }

    function convert_specialchar($input) {
        $special_char = false;
        for ($i = 0; $i < strlen($input); $i++) {
            if (preg_match('/[^a-z0-9.@~_+=&\'*^\ \-\[\]]/i', $input[$i])) {
                $special_char = true;
                $input = preg_replace('/\\' . $input[$i] . '/', '=' . bin2hex($input[$i]), $input);
            }
        }

        if ($special_char) {
            /*             *
             * When using arrays with pattern and replacement, 
             * the keys are processed in the order they appear in the array. 
             * See preg_replace in php.net/preg_replace 
             * */
            $patterns[0] = '/=c3/i';
            $patterns[1] = '/ /';
            $replacements[1] = '';
            $replacements[0] = '_';
            $input = preg_replace($patterns, $replacements, $input);
        }

        return($input);
    }

    // build the rule
    function write_rule() {

        // Variaveis;
        $rule = array();
        $vacation = array();
        $newruletext = "";
        $activerules = 0;
        $regexused = 0;
        $rejectused = 0;
        $notify = 0;
        $flaggedused = 0;
        $newscriptbody = "";
        $continue = 1;
        $tmpSubject = "";

        // Recebe os valores das regras;
        foreach ($this->newrules as $tmp)
        {
            $tmp1 = explode("&&", $tmp);
            $rule['priority']   = $tmp1[1];
            $rule['status']     = $tmp1[2];
            $rule['from']       = $this->convert_specialchar($tmp1[3]);
            $rule['to']         = $this->convert_specialchar($tmp1[4]);
            $tmpSubject         = $tmp1[5];
            $rule['subject']    = $this->convert_specialchar($tmp1[5]);
            $rule['action']     = $tmp1[6];
            $rule['action_arg'] = utf8_encode(preg_replace("/\\r\\n/", "\r\n", $tmp1[7]));
            $rule['flg']        = $tmp1[8];
            $rule['field']      = $tmp1[9];
            $rule['field_val']  = $tmp1[10];
            $rule['size']       = trim($tmp1[11]);
            $rule['continue']   = ($tmp1[8] & $this->continuebit);
            $rule['gthan']      = ($tmp1[8] & $this->sizebit);
            $rule['allof']      = ($tmp1[8] & $this->allofbit);
            $rule['keep']       = ($tmp1[8] & $this->keepbit);
            $rule['regexp']     = ($tmp1[8] & $this->regexbit);
            $rule['unconditional'] = 0;

            if (!$rule['from'] && !$rule['to'] && !$rule['subject'] && !$rule['field'] && empty($rule['size']) && $rule['action']) {
                $rule['unconditional'] = 1;

                if ($rule['unconditional'] && ($rule['size'] == "0" || $rule['size'] == 0 ))
                    $rule['unconditional'] = 0;
            }
            
            unset($tmp1);

            // Monta as regras;
            if ($rule['status'] == 'ENABLED') 
            {
                $activerules = 1;

                // Condicoes para montagem das regras;
                $anyall = "anyof";
                if ($rule['allof'])
                    $anyall = "allof";
                if ($rule['regexp']) {
                    $regexused = 1;
                }
                $started = 0;

                if (!$rule['unconditional']) {
                    if (!$continue)
                        $newruletext .= "els";
                    $newruletext .= "if " . $anyall . " (";
                    if ($rule['from']) {
                        if (preg_match("/^\s*!/", $rule['from'])) {
                            $newruletext .= 'not ';
                            $rule['from'] = preg_replace("/^\s*!/", "", $rule['from']);
                        }
                        $match = ':contains';
                        if (preg_match("/\*|\?/", $rule['from']))
                            $match = ':matches';
                        if ($rule['regexp'])
                            $match = ':regex';
                        $newruletext .= "header " . $match . " [\"From\"]";
                        $newruletext .= " \"" . $rule['from'] . "\"";
                        $started = 1;
                    }
                    if ($rule['to']) {
                        if ($started)
                            $newruletext .= ", ";
                        if (preg_match("/^\s*!/", $rule['to'])) {
                            $newruletext .= 'not ';
                            $rule['to'] = preg_replace("/^\s*!/", "", $rule['to']);
                        }
                        $match = ':contains';
                        if (preg_match("/\*|\?/", $rule['to']))
                            $match = ':matches';
                        if ($rule['regexp'])
                            $match = ':regex';
                        $newruletext .= "address " . $match . " [\"To\",\"TO\",\"Cc\",\"CC\"]";
                        $newruletext .= " \"" . $rule['to'] . "\"";
                        $started = 1;
                    }
                    if ($rule['subject']) {
                        if ($started)
                            $newruletext .= ", ";
                        if (preg_match("/^\s*!/", $rule['subject'])) {
                            $newruletext .= 'not ';
                            $rule['subject'] = preg_replace("/^\s*!/", "", $rule['subject']);
                        }
                        $match = ':contains';
                        if (preg_match("/\*|\?/", $rule['subject']))
                            $match = ':matches';
                        if ($rule['regexp'])
                            $match = ':regex';
                        $newruletext .= "header " . $match . " \"subject\"";
                        $newruletext .= " \"" . $rule['subject'] . "\"";
                        $started = 1;
                    }
                    if ($rule['field'] && $rule['field_val']) {
                        if ($started)
                            $newruletext .= ", ";
                        if (preg_match("/^\s*!/", $rule['field_val'])) {
                            $newruletext .= 'not ';
                            $rule['field_val'] = preg_replace("/^\s*!/", "", $rule['field_val']);
                        }
                        $match = ':contains';
                        if (preg_match("/\*|\?/", $rule['field_val']))
                            $match = ':matches';
                        if ($rule['regexp'])
                            $match = ':regex';
                        $newruletext .= "header " . $match . " \"" . $rule['field'] . "\"";
                        $newruletext .= " \"" . $rule['field_val'] . "\"";
                        $started = 1;
                    }

                    if ($rule['size'] != '')
                    {
                        if ( $rule['size'] == 0 && $rule['gthan'] )
                        {
                            $xthan = " :over ";
                            
                            if ($started)
                                $newruletext .= ", ";

                            $newruletext .= "size " . $xthan . "0K";
                            $started = 1;
                        }

                        if ( $rule['size'] > 0 )
                        {
                            $xthan = " :under ";
                            
                            if ($rule['gthan'])
                                $xthan = " :over ";
                            if ($started)
                                $newruletext .= ", ";

                            $newruletext .= "size " . $xthan . $rule['size'] . "K";
                            $started = 1;
                        }
                    }
                }

                // Don't write half rule!
                if (strlen($newruletext) == 0)
                    return false;
                // Actions
                if (!$rule['unconditional'])
                    $newruletext .= ") {\n\t";

                if (preg_match("/folder/i", $rule['action'])) {
                    $newruletext .= "fileinto \"" . $rule['action_arg'] . "\";";
                }

                if (preg_match("/reject/i", $rule['action'])) {
                    $newruletext .= "reject text: \n" . $rule['action_arg'] . "\n.\n;";
                    $rejectused = 1;
                }
                if (preg_match("/flagged/i", $rule['action'])) {
                    $newruletext .= "addflag \"\\\\Flagged\";";
                    $flaggedused = 1;
                }
                if (preg_match("/address/i", $rule['action'])) {
                    $newruletext .= "redirect \"" . $rule['action_arg'] . "\";";
                }

                if (preg_match("/notify/i", $rule['action'])) {
                    $newruletext .= "notify :method \"mailto\" :options [\"" . $this->EmailVoip . "\"]:" .
                            "message \"<expressovoip><from>" . $this->EmailExpresso . "</from>" .
                            "<br/><Subject>" . utf8_encode($tmpSubject) . "</Subject></expressovoip>\";";
                    $notify = 1;
                }

                if (preg_match("/discard/i", $rule['action'])) {
                    $newruletext .= "discard;";
                }
                
                if ($rule['keep'] )
                {    
                    $newruletext .= "\n\tfileinto \"INBOX\";";
                }
                
                if (!$rule['unconditional'])
                {
                    $newruletext .= "\n}";
                }

                $continue = 0;
                if ($rule['continue'])
                    $continue = 1;
                if ($rule['unconditional'])
                    $continue = 1;

                $newscriptbody .= $newruletext . "\n\n";
                unset($newruletext);
            }
        }// Fim do Foreach;
        $this->teste = $newscriptbody;
        // Para a regras fora do escritorio;
        unset($tmp);
        if ($this->newout != "") {
            $aux = explode("&&", $this->newout);
            $vacation['days'] = $aux[1];
            $vacation['addresses'] = $aux[2];
            $vacation['text'] = preg_replace("/\\\\n/", "\r\n", $aux[3]);
            $vacation['status'] = $aux[4];
        }

        // Monta a regra para fora do escritorio;
        if ($vacation['status'] == 'on') {
            $newscriptbody .= "vacation :days " . $vacation['days'] . " :addresses [";
            $newscriptbody .= $vacation['addresses'];
            $newscriptbody .= "] text:\n" . utf8_encode($vacation['text']) . "\n.\n;\n\n";
        }

        // Cria o cabecalho do arquivo;
        $newscripthead = "";
        $newscripthead .= "#Mail filter rules for " . $this->username . "\n";
        $newscripthead .= '#Generated by ' . $this->username . ' using Expressomail ';
        $newscripthead .= "\n";

		$hasInclude = $this->SieveS->hasExtension( 'include' );

		$require = array();
		if ( $hasInclude ) $require[] = 'include';
		if ( $activerules ) {
			$require[] = 'fileinto';
			if ( $notify ) $require[] = 'notify';
			if ( $regexused ) $require[] = 'regex';
			if ( $rejectused ) $require[] = 'reject';
			if ( $flaggedused ) $require[] = 'imapflags';
			if ( $this->newout && $vacation['status'] == 'on' ) $require[] = 'vacation';
		} else {
			if ( $vacation && $vacation['status'] == 'on' ) $require[] = 'vacation';
		}
		if ( count( $require ) ) $newscripthead .= 'require ["' . implode( '","', $require ) . '"];' . PHP_EOL;
		if ( $hasInclude ) $newscripthead .= 'include :global "admin-filters";' . PHP_EOL;

        // Cria o rodape do arquivo;
        $newscriptfoot = "";
        $newscriptfoot .= "##PSEUDO script start\n";
        // Le as regras;
        foreach ($this->newrules as $tmp) {
            $newscriptfoot .= preg_replace("/[\\n\\r]/", " ", $tmp) . "\n";
        }
        // Le as regras fora do escritorio;
        if ($this->newout != "") {
            $newscriptfoot .= preg_replace("/[\\n\\r]/", " ", $this->newout) . "\n";
        }
        $newscriptfoot .= "#mode&&basic\n";

        $newscript = $newscripthead . $newscriptbody . $newscriptfoot;

        // Destroi as variaveis;
        unset($rule);
        unset($vacation);
        unset($activerules);
        unset($regexused);
        unset($rejectused);
        unset($flaggedused);
        unset($newscripthead);
        unset($newscriptbody);
        unset($newscriptfoot);
        unset($continue);
        unset($this->newrules);
        unset($this->newout);

        // Retorna o script construido;
        return $newscript;
    }

// Fim da Funcao
    // Cria o script sieve, caso nao possua;
    function createScript() {

        // Cria o cabecalho do arquivo;
        $newScriptHead = "";
        $newScriptHead .= "#Mail filter rules for " . $this->username . "\n";
        $newScriptHead .= '#Generated by ' . $this->username . ' using ExpressoMail ';

		if ( $this->SieveS->hasExtension( 'include' ) ) {
			$newscripthead .= 'require ["include"];' . PHP_EOL;
			$newscripthead .= 'include :global "admin-filters";' . PHP_EOL;
		}

        //Cria o rodape do arquivo;
        $newScriptFoot = "";
        $newScriptFoot .= "##PSEUDO Script Start\n";
        $newScriptFoot .= "#mode&&basic\n";

        //Para passar para o arquivo;
        $newScript = $newScriptHead . $newScriptFoot;

        return $newScript;
    }

    //Le o conteudo do script;
    function readScript($scriptName) {

        // Verifica se a conexao foi bem sucedida;
        if (!$scriptName) {
            $this->errstr = "Nao foi possivel conectar com o Servidor";
            return "false 2";
        }

        // Recebe o conteudo do array;
        $lines = array();
        $lines = preg_split("/\n/", $scriptName);

        // Pega o tamanho da regra na primeira do script;
        $size_rule = array_shift($lines);

        // Recebe o tamanho do script, pela primeira linha;
        $this->size = trim($size_rule);

        // Verifica a composicao do script;
        $line = array_shift($lines);
        if (!preg_match("/^# ?Mail(.*)rules for/", $line)) {
            $this->errstr = "Formato nao reconhecido";
            return false;
        }

        // Variaveis para a regra e o campo ferias;
        $regexps = array('^ *##PSEUDO', '^ *#rule', '^ *#vacation', '^ *#mode');
        $retorno['rule'] = array();
        $retorno['vacation'] = array();
        $retorno['mode'] = array();

        $line = array_shift($lines);
        while (isset($line)) {
            foreach ($regexps as $regp) {
                if (preg_match("/$regp/i", $line)) {
                    // Recebe todas as regras criadas no servidor;
                    if (preg_match("/^ *#rule&&/i", $line)) {
                        $retorno['rule'][] = $line . "\n";
                    }
                    if (preg_match("/^ *#vacation&&/i", $line)) {
                        $retorno['vacation'][] = $line . "\n";
                    }
                    if (preg_match("/^ *#mode&&(.*)/i", $line)) {
                        $retorno['mode'][] = $line . "\n";
                    }
                }
            }
            // Pega a proxima linha do sript;
            $line = array_shift($lines);
        }
        return $retorno;
    }

}

//Fim da Classe
?>
