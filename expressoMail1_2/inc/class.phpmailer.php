<?php
////////////////////////////////////////////////////
// PHPMailer - PHP email class
//
// Class for sending email using either
// sendmail, PHP mail(), or SMTP.  Methods are
// based upon the standard AspEmail(tm) classes.
//
// Copyright (C) 2001 - 2003  Brent R. Matzelle
//
// License: LGPL, see LICENSE
////////////////////////////////////////////////////

/**
 * PHPMailer - PHP email transport class
 * @package PHPMailer
 * @author Brent R. Matzelle
 * @copyright 2001 - 2003 Brent R. Matzelle
 */
class PHPMailer
{
    /////////////////////////////////////////////////
    // PUBLIC VARIABLES
    /////////////////////////////////////////////////

    /**
     * Email priority (1 = High, 3 = Normal, 5 = low).
     * @var int
     */
    var $Priority          = 3;
	/**
	 * Email Importance.
	 */
	var $Importance        = "";

    /**
     * Sets the CharSet of the message.
     * @var string
     */
    var $CharSet           = "iso-8859-1";

    /**
     * Sets the Content-type of the message.
     * @var string
     */
    var $ContentType        = "text/plain";

    /**
     * Sets the Encoding of the message. Options for this are "8bit",
     * "7bit", "binary", "base64", and "quoted-printable".
     * @var string
     */
    var $Encoding          = "quoted-printable";

    /**
     * Holds the most recent mailer error message.
     * @var string
     */
    var $ErrorInfo         = "";

    /**
     * Sets the From email address for the message.
     * @var string
     */
    var $From               = "root@localhost";

    /**
     * Sets the From name of the message.
     * @var string
     */
    var $FromName           = "Root User";

    /**
     * Sets the Sender email (Return-Path) of the message.  If not empty,
     * will be sent via -f to sendmail or as 'MAIL FROM' in smtp mode.
     * @var string
     */
    var $Sender            = "";
	var $SenderName		= "";
    /**
     * Sets the Subject of the message.
     * @var string
     */
    var $Subject           = "teste";

    /**
     * Sets the Body of the message.  This can be either an HTML or text body.
     * If HTML then run IsHTML(true).
     * @var string
     */
    var $Body               = "";

    /**
     * Sets the text-only body of the message.  This automatically sets the
     * email to multipart/alternative.  This body can be read by mail
     * clients that do not have HTML email capability such as mutt. Clients
     * that can read HTML will view the normal Body.
     * @var string
     */
    var $AltBody           = "";

    /**
     * Sets the signed body of the message.  This automatically sets the
     * email to multipart/signed.
     * @var string
     */
    var $SignedBody           = false;
    var $SMIME          	= false;
    var $Certs_crypt		= array();
    /**
     * Sets the encrypted body of the message.  This automatically sets the
     * email to multipart/encript.
     * @var string
     */
    var $CryptedBody           = "";

    /**
     * Sets word wrapping on the body of the message to a given number of 
     * characters.
     * @var int
     */
    var $WordWrap          = 0;

    /**
     * Method to send mail: ("mail", "sendmail", or "smtp").
     * @var string
     */
    var $Mailer            = "mail";

    /**
     * Sets the path of the sendmail program.
     * @var string
     */
    var $Sendmail          = "/usr/sbin/sendmail";
    
    /**
     * Path to PHPMailer plugins.  This is now only useful if the SMTP class 
     * is in a different directory than the PHP include path.  
     * @var string
     */
    var $PluginDir         = "";

    /**
     *  Holds PHPMailer version.
     *  @var string
     */
    var $Version           = "1.2.1";

    /**
     * Sets the email address that a reading confirmation will be sent.
     * @var string
     */
    var $ConfirmReadingTo  = "";

    /**
     *  Sets the hostname to use in Message-Id and Received headers
     *  and as default HELO string. If empty, the value returned
     *  by SERVER_NAME is used or 'localhost.localdomain'.
     *  @var string
     */
    var $Hostname          = "";
	
	var $SaveMessageInFolder = "";
	var $SaveMessageAsDraft = "";

    var $xMailer           = "";
    
    var $RefreshFolders = false;
    
    /////////////////////////////////////////////////
    // SMTP VARIABLES
    /////////////////////////////////////////////////

    /**
     *  Sets the SMTP hosts.  All hosts must be separated by a
     *  semicolon.  You can also specify a different port
     *  for each host by using this format: [hostname:port]
     *  (e.g. "smtp1.example.com:25;smtp2.example.com").
     *  Hosts will be tried in order.
     *  @var string
     */
    var $Host        = "localhost";

    /**
     *  Sets the default SMTP server port.
     *  @var int
     */
    var $Port        = 25;

    /**
     *  Sets the SMTP HELO of the message (Default is $Hostname).
     *  @var string
     */
    var $Helo        = "";

    /**
     *  Sets SMTP authentication. Utilizes the Username and Password variables.
     *  @var bool
     */
    var $SMTPAuth     = false;

    /**
     *  Sets SMTP username.
     *  @var string
     */
    var $Username     = "";

    /**
     *  Sets SMTP password.
     *  @var string
     */
    var $Password     = "";

    /**
     *  Sets the SMTP server timeout in seconds. This function will not 
     *  work with the win32 version.
     *  @var int
     */
    var $Timeout      = 300;

    /**
     *  Sets SMTP class debugging on or off.
     *  @var bool
     */
    var $SMTPDebug    = false;

    /**
     * Prevents the SMTP connection from being closed after each mail 
     * sending.  If this is set to true then to close the connection 
     * requires an explicit call to SmtpClose(). 
     * @var bool
     */
    var $SMTPKeepAlive = false;

    /**#@+
     * @access private
     */
    var $smtp            = NULL;
    var $to              = array();
    var $cc              = array();
    var $bcc             = array();
    var $ReplyTo         = array();
    var $attachment      = array();
    var $CustomHeader    = array();
    var $message_type    = "";
    var $boundary        = array();
    var $language        = array();
    var $error_count     = 0;
    var $LE              = "\r\n";
    var $SPW             = " ";
    
    /////////////////////////////////////////////////
    // VARIABLE METHODS
    /////////////////////////////////////////////////

	function isImportant() {
		$this->Importance = "High";
	}
	
    /**
     * Sets message type to HTML.  
     * @param bool $bool
     * @return void
     */
    function IsHTML($bool) {
        if($bool == true)
            $this->ContentType = "text/html";
        else
            $this->ContentType = "text/plain";
    }

    /**
     * Sets Mailer to send message using SMTP.
     * @return void
     */
    function IsSMTP() {
        $this->Mailer = "smtp";
    }

    /**
     * Sets Mailer to send message using PHP mail() function.
     * @return void
     */
    function IsMail() {
        $this->Mailer = "mail";
    }

    /**
     * Sets Mailer to send message using the $Sendmail program.
     * @return void
     */
    function IsSendmail() {
        $this->Mailer = "sendmail";
    }

    /**
     * Sets Mailer to send message using the qmail MTA. 
     * @return void
     */
    function IsQmail() {
        $this->Sendmail = "/var/qmail/bin/sendmail";
        $this->Mailer = "sendmail";
    }


    /////////////////////////////////////////////////
    // RECIPIENT METHODS
    /////////////////////////////////////////////////

	public function GetAddresses( $type = 'to' )
	{
		return array_map( array( $this, 'AddrFormat'), $this->$type );
	}

    /**
     * Adds a "To" address.  
     * @param string $address
     * @param string $name
     * @return void
     */
    function AddAddress($address, $name = "") {
        $cur = count($this->to);
        $this->to[$cur][0] = trim($address);
        $this->to[$cur][1] = $name;
    }

    /**
     * Adds a "Cc" address. Note: this function works
     * with the SMTP mailer on win32, not with the "mail"
     * mailer.  
     * @param string $address
     * @param string $name
     * @return void
    */
    function AddCC($address, $name = "") {
        $cur = count($this->cc);
        $this->cc[$cur][0] = trim($address);
        $this->cc[$cur][1] = $name;
    }

    /**
     * Adds a "Bcc" address. Note: this function works
     * with the SMTP mailer on win32, not with the "mail"
     * mailer.  
     * @param string $address
     * @param string $name
     * @return void
     */
    function AddBCC($address, $name = "") {
        $cur = count($this->bcc);
        $this->bcc[$cur][0] = trim($address);
        $this->bcc[$cur][1] = $name;
    }

    /**
     * Adds a "Reply-to" address.  
     * @param string $address
     * @param string $name
     * @return void
     */
    function AddReplyTo($address, $name = "") {
        $cur = count($this->ReplyTo);
        $this->ReplyTo[$cur][0] = trim($address);
        $this->ReplyTo[$cur][1] = $name;
    }


    /////////////////////////////////////////////////
    // MAIL SENDING METHODS
    /////////////////////////////////////////////////

    /**
     * Creates message and assigns Mailer. If the message is
     * not sent successfully then it returns false.  Use the ErrorInfo
     * variable to view description of the error.  
     * @return bool
     */
    function Send() {
    	
    
        $header = "";
        $body = "";
        $result = true;
		
        if(((count($this->to) + count($this->cc) + count($this->bcc)) < 1) && (!$this->SaveMessageAsDraft))
        {
            $this->SetError($this->Lang("provide_address"));            
            return false;
        }
		
        // Set whether the message is multipart/alternative
        if(!empty($this->AltBody))
            $this->ContentType = "multipart/alternative";

        $this->error_count = 0; // reset errors
        $this->SetMessageType();
        $header .= $this->CreateHeader();

        if ($this->SMIME == false)
        {
            $body = $this->CreateBody();
            if($body == "")
            {
                return false;
            }
        }
		
        // Choose the mailer
        switch($this->Mailer)
        {
            // Usado para processar o email e retornar para a applet
        	case "smime":
                $retorno['body'] = $header.$this->LE.$body;
                $retorno['type'] =  $this->write_message_type();
        		return $retorno;
            case "sendmail":
                $result = $this->SendmailSend($header, $body);
                break;
            case "mail":
                $result = $this->MailSend($header, $body);
                break;
            case "smtp":
                $result = $this->SmtpSend($header, $body);
                break;
            default:
	            $this->SetError($this->Mailer . $this->Lang("mailer_not_supported"));
                $result = false;
                break;
        }

        return $result;
    }
    
    /**
     * Sends mail using the $Sendmail program.  
     * @access private
     * @return bool
     */
    function SendmailSend($header, $body) {
        if ($this->Sender != "")
            $sendmail = sprintf("%s -oi -f %s -t", $this->Sendmail, $this->Sender);
        else
            $sendmail = sprintf("%s -oi -t", $this->Sendmail);

        if(!@$mail = popen($sendmail, "w"))
        {
            $this->SetError($this->Lang("execute") . $this->Sendmail);
            return false;
        }

        fputs($mail, $header);
        fputs($mail, $body);
        
        $result = pclose($mail) >> 8 & 0xFF;
        if($result != 0)
        {
            $this->SetError($this->Lang("execute") . $this->Sendmail);
            return false;
        }

        return true;
    }

    /**
     * Sends mail using the PHP mail() function.  
     * @access private
     * @return bool
     */
    function MailSend($header, $body) {
        $to = "";
        for($i = 0; $i < count($this->to); $i++)
        {
            if($i != 0) { $to .= ", "; }
            $to .= $this->to[$i][0];
        }

        if ($this->Sender != "" && strlen(ini_get("safe_mode"))< 1)
        {
            $old_from = ini_get("sendmail_from");
            ini_set("sendmail_from", $this->Sender);
            $params = sprintf("-oi -f %s", $this->Sender);
            $rt = @mail($to, $this->_mimeEncode( $this->Subject ), $body, 
                        $header, $params);
        }
        else
            $rt = @mail($to, $this->_mimeEncode( $this->Subject ), $body, $header);

        if (isset($old_from))
            ini_set("sendmail_from", $old_from);

        if(!$rt)
        {
            $this->SetError($this->Lang("instantiate"));
            return false;
        }

        return true;
    }

    /**
     * Sends mail via SMTP using PhpSMTP (Author:
     * Chris Ryan).  Returns bool.  Returns false if there is a
     * bad MAIL FROM, RCPT, or DATA input.
     * @access private
     * @return bool
     */
    function SmtpSend($header, $body) {
        include_once($this->PluginDir . "class.smtp.php");
        $error = "";

        $bad_rcpt = array();
        $errorx = '';
        if(!$this->SmtpConnect())
            return false;

        if($this->SMIME)
        {
            $header='';
            $body = $this->Body;
        }

        $smtp_from = ($this->Sender == "") ? $this->From : $this->Sender;
        if(!$this->smtp->Mail($smtp_from))
        {
            $error = $this->Lang("from_failed") . $smtp_from;
            $this->SetError($error);
            $this->smtp->Reset();
            return false;
        }

        // Attempt to send attach all recipients
        for($i = 0; $i < count($this->to); $i++)
        {
		if(!$this->smtp->Recipient($this->to[$i][0]))
			$bad_rcpt[] = $this->to[$i][0];
        }
        for($i = 0; $i < count($this->cc); $i++)
        {
		if(!$this->smtp->Recipient($this->cc[$i][0]))
			$bad_rcpt[] = $this->cc[$i][0];
        }
        for($i = 0; $i < count($this->bcc); $i++)
        {
                if(!$this->smtp->Recipient($this->bcc[$i][0]))
			$bad_rcpt[] = $this->bcc[$i][0];      
        }
        if($errorx != '')
		{
			$error = $errorx;
			$error = $this->Lang("recipients_failed")  . '  ' . $errorx;
			$this->SetError($error);
			$this->smtp->Reset();
			return false;
		}

        if(count($bad_rcpt) > 0) // Create error message
        {
            //Postfix version 2.3.8-2
            $smtp_code_error = substr($this->smtp->error['smtp_msg'], 0, 5);
            //Postfix version 2.1.5-9
            $array_error = explode(":", $this->smtp->error['smtp_msg']);
            
            for($i = 0; $i < count($bad_rcpt); $i++)
            {
                if($i != 0) { $error .= ", "; }
                $error .= $bad_rcpt[$i];
            }
            if (($smtp_code_error == '5.7.1') || (trim($array_error[2]) == 'Access denied'))
            	$error = $this->Lang("not_allowed") . $error;
            else
            	$error = $this->Lang("recipients_failed") . $error;
            $this->SetError($error);
            $this->smtp->Reset();
            return false;
        }

        // Vai verificar se deve cifrar a msg ......
        if(count($this->Certs_crypt) > 0)
		{
            // Vai cifrar a msg antes de enviar ......
			include_once("../security/classes/CertificadoB.php");

            $teste1 = array();
            $aux_cifra1 = $header . $body;

            // Inicio relocacao dos headers
            // Esta relocacao dos headers podem causar problemas.

            $match = 0;
            $pattern = '/^Disposition\-Notification\-To:.*\n/m';
            $match = preg_match($pattern, $aux_cifra1, $teste1);

            if (!empty($match)){
                $aux_cifra1 = preg_replace($pattern, '', $aux_cifra1, 1); // retira o Disposition-Notification-To

                $match = 0;
                $teste2 = array();
                $pattern = '/^MIME\-Version:.*\n/m';
                $match = preg_match($pattern, $aux_cifra1, $teste2);
                $aux_cifra1 = preg_replace($pattern, $teste1[0].$teste2[0], $aux_cifra1, 1); // Adiciona Disposition-Notification-To logo acima de MIME-Version

            }
            // Fim relocacao dos headers

           // Vai partir em duas partes a msg.  A primeira parte he a dos headers, e a segunda vai ser criptografada ...
            $pos_content_type = strpos($aux_cifra1,'Content-Type:');
            $pos_MIME_Version = strpos($aux_cifra1,'MIME-Version: 1.0' . chr(0x0D) . chr(0x0A));
            $valx_len = 19;
            if($pos_MIME_Version === False)
		    {
                $pos_MIME_Version = strpos($aux_cifra1,'MIME-Version: 1.0' . chr(0x0A));
                $valx_len = 18;
		    }

            if($pos_MIME_Version >= $pos_content_type)
            {
                // nao deve enviar a msg..... O header MIME-Version com posicao invalida ......
                $this->SetError('Formato dos headers da msg estao invalidos.(CD-17) - A');
                $this->smtp->Reset();
                return false;
            }

            $aux_cifra2 = array();
            $aux_cifra2[] = substr($aux_cifra1,0,$pos_MIME_Version - 1);
            $aux_cifra2[] = substr($aux_cifra1,$pos_MIME_Version + $valx_len);

               /*
			// este explode pode ser fonte de problemas .......
			$aux_cifra2 = explode('MIME-Version: 1.0' . chr(0x0A), $aux_cifra1);
			// Pode ocorrer um erro se nao tiver o header MIME-Version .....
			if(count($aux_cifra2)  != 2 )
				{
					$aux_cifra2 = explode('MIME-Version: 1.0' . chr(0x0D) . chr(0x0A), $aux_cifra1);
					if(count($aux_cifra2)  != 2 )
						{
							// nao deve enviar a msg..... nao tem o header MIME-Version ......
							$this->SetError('Formato dos headers da msg estao invalidos.(CD-17) - ' . count($aux_cifra2));
							$this->smtp->Reset();
							return false;
						}
				}
                */
			$certificado = new certificadoB();
			$h = array();
			$aux_body = $certificado->encriptar($aux_cifra2[1], $this->Certs_crypt, $h);
			if(!$aux_body)
            {
                $this->SetError('Ocorreu um erro. A msg nao foi enviada. (CD-18)');
                $this->smtp->Reset();
                return false;
            }
			// salvar sem cifra......
			//$smtpSent = $this->smtp->Data($aux_cifra2[0] . $aux_body);

			// salva a msg sifrada. neste caso deve ter sido adicionado o certificado do autor da msg......
			$header = $aux_cifra2[0];
			$body = $aux_body;
        $smtpSent = $this->smtp->Data($header . $body);
        }
        else
		{
			$smtpSent = $this->smtp->Data($header . $body);
		}

        if(!$smtpSent)
        {
            $this->SetError($this->Lang("data_not_accepted") .' '. $this->smtp->error['error'] .','. $this->smtp->error['smtp_code'].','. $this->smtp->error['smtp_msg']);
            $this->smtp->Reset();
            return false;
        }
        if($this->SMTPKeepAlive == true)
            $this->smtp->Reset();
        else
            $this->SmtpClose();

		if ($this->SaveMessageInFolder)
		{
			$username				= $_SESSION['phpgw_info']['expressomail']['user']['userid'];
			$password				= $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
			$imap_server			= $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
			$imap_port				= $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
			$imapDefaultSentFolder	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'];
			$imapDelimiter			= $_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'];
			
			if ($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes')
			{
				$imap_options = '/tls/novalidate-cert';
			}
			else
			{
				$imap_options = '/notls/novalidate-cert';
			}
			
			$mbox_stream = imap_open("{".$imap_server.":".$imap_port.$imap_options."}".$this->SaveMessageInFolder, $username, $password);
			
			if ( ($arr_imap_error = imap_errors()) && in_array( 'Mailbox does not exist', $arr_imap_error ) )
			{
				$this->SaveMessageInFolder = 'INBOX'.$imapDelimiter.$imapDefaultSentFolder;
				
				imap_reopen($mbox_stream, imap_utf7_encode("{".$imap_server.":".$imap_port.$imap_options."}".$this->SaveMessageInFolder));
				
				if ( ($arr_imap_error = imap_errors()) && in_array( 'Mailbox does not exist', $arr_imap_error ) )
				{
					imap_createmailbox($mbox_stream, imap_utf7_encode("{".$imap_server.":".$imap_port.$imap_options."}".$this->SaveMessageInFolder));
					imap_reopen($mbox_stream, imap_utf7_encode("{".$imap_server.":".$imap_port.$imap_options."}".$this->SaveMessageInFolder));
					$this->RefreshFolders = true;
					$arr_imap_error = imap_errors();
				}
			}

			##
			# @AUTHOR Rodrigo Souza dos Santos
			# @DATE 2008/09/11
			# @BRIEF Adding arbitrarily the BCC field. You may need to
			#        check if this field already exists in the header.
			##
			if ( count($this->bcc) > 0 )
			{
				$target = stripos($header, 'subject');
				$header = substr($header, 0, $target) . $this->AddrAppend("Bcc", $this->bcc) . substr($header, $target);
			}
            $new_headerx = str_replace(chr(0x0A),chr(0x0D).chr(0x0A), $header);
			$new_bodyx = str_replace(chr(0x0A),chr(0x0D).chr(0x0A), $body);
			$new_header = str_replace(chr(0x0D).chr(0x0D).chr(0x0A), chr(0x0D).chr(0x0A),$new_headerx);
			$new_body = str_replace(chr(0x0D).chr(0x0D).chr(0x0A), chr(0x0D).chr(0x0A), $new_bodyx);
			
			if ($this->SaveMessageAsDraft){
				imap_append($mbox_stream, "{".$imap_server.":".$imap_port."}".$this->SaveMessageInFolder, $new_header . $new_body, "\\Seen \\Draft");
				return true;
			}
			else
				imap_append($mbox_stream, "{".$imap_server.":".$imap_port."}".$this->SaveMessageInFolder, $new_header . $new_body, "\\Seen");
    	}    	
        
        return $smtpSent;
    }


    /**
     * Initiates a connection to an SMTP server.  Returns false if the 
     * operation failed.
     * @access private
     * @return bool
     */
    function SmtpConnect() {
        if($this->smtp == NULL) { $this->smtp = new SMTP(); }

        $this->smtp->do_debug = $this->SMTPDebug;
        $hosts = explode(";", $this->Host);
        $index = 0;
        $connection = ($this->smtp->Connected()); 

        // Retry while there is no connection
        while($index < count($hosts) && $connection == false)
        {
            if(strstr($hosts[$index], ":"))
                list($host, $port) = explode(":", $hosts[$index]);
            else
            {
                $host = $hosts[$index];
                $port = $this->Port;
            }

            if($this->smtp->Connect($host, $port, $this->Timeout))
            {
                if ($this->Helo != '')
                    $this->smtp->Hello($this->Helo);
                else
                    $this->smtp->Hello($this->ServerHostname());
        
                if($this->SMTPAuth)
                {
                    if(!$this->smtp->Authenticate($this->Username, 
                                                  $this->Password))
                    {
                        $this->SetError($this->Lang("authenticate"));
                        $this->smtp->Reset();
                        $connection = false;
                    }
                }
                $connection = true;
            }
            $index++;
        }
        if(!$connection)
            $this->SetError($this->Lang("connect_host"));

        return $connection;
    }

    /**
     * Closes the active SMTP session if one exists.
     * @return void
     */
    function SmtpClose() {
        if($this->smtp != NULL)
        {
            if($this->smtp->Connected())
            {
                $this->smtp->Quit();
                $this->smtp->Close();
            }
        }
    }

    /**
     * Sets the language for all class error messages.  Returns false 
     * if it cannot load the language file.  The default language type
     * is English.
     * @param string $lang_type Type of language (e.g. Portuguese: "br")
     * @param string $lang_path Path to the language file directory
     * @access public
     * @return bool
     */
    function SetLanguage($lang_type, $lang_path = "setup/") {
        if(file_exists($lang_path.'phpmailer.lang-'.$lang_type.'.php'))
            include($lang_path.'phpmailer.lang-'.$lang_type.'.php');
        else if(file_exists($lang_path.'phpmailer.lang-en.php'))
            include($lang_path.'phpmailer.lang-en.php');
        else
        {
            $this->SetError("Could not load language file");
            return false;
        }
        $this->language = $PHPMAILER_LANG;
    
        return true;
    }

    /////////////////////////////////////////////////
    // MESSAGE CREATION METHODS
    /////////////////////////////////////////////////

    /**
     * Wraps message for use with mailers that do not
     * automatically perform wrapping and for quoted-printable.
     * Original written by philippe.  
     * @access private
     * @return string
     */
    function WrapText($message, $length, $qp_mode = false) {
        $soft_break = ($qp_mode) ? sprintf(" =%s", $this->LE) : $this->LE;

        $message = $this->_fixEOL($message);
        if (substr($message, -1) == $this->LE)
            $message = substr($message, 0, -1);

        $line = explode($this->LE, $message);
        $message = "";
        for ($i=0 ;$i < count($line); $i++)
        {
          $line_part = explode(" ", $line[$i]);
          $buf = "";
          for ($e = 0; $e<count($line_part); $e++)
          {
              $word = $line_part[$e];
              if ($qp_mode and (strlen($word) > $length))
              {
                $space_left = $length - strlen($buf) - 1;
                if ($e != 0)
                {
                    if ($space_left > 20)
                    {
                        $len = $space_left;
                        if (substr($word, $len - 1, 1) == "=")
                          $len--;
                        elseif (substr($word, $len - 2, 1) == "=")
                          $len -= 2;
                        $part = substr($word, 0, $len);
                        $word = substr($word, $len);
                        $buf .= " " . $part;
                        $message .= $buf . sprintf("=%s", $this->LE);
                    }
                    else
                    {
                        $message .= $buf . $soft_break;
                    }
                    $buf = "";
                }
                while (strlen($word) > 0)
                {
                    $len = $length;
                    if (substr($word, $len - 1, 1) == "=")
                        $len--;
                    elseif (substr($word, $len - 2, 1) == "=")
                        $len -= 2;
                    $part = substr($word, 0, $len);
                    $word = substr($word, $len);

                    if (strlen($word) > 0)
                        $message .= $part . sprintf("=%s", $this->LE);
                    else
                        $buf = $part;
                }
              }
              else
              {
                $buf_o = $buf;
                $buf .= ($e == 0) ? $word : (" " . $word); 

                if (strlen($buf) > $length and $buf_o != "")
                {
                    $message .= $buf_o . $soft_break;
                    $buf = $word;
                }
              }
          }
          $message .= $buf . $this->LE;
        }

        return $message;
    }
    
    /**
     * Set the body wrapping.
     * @access private
     * @return void
     */
    function SetWordWrap() {
        if($this->WordWrap < 1)
            return;
            
        switch($this->message_type)
        {
           case "alt":
              // fall through
           case "alt_attachments":
              $this->AltBody = $this->WrapText($this->AltBody, $this->WordWrap);
              break;
           default:
              $this->Body = $this->WrapText($this->Body, $this->WordWrap);
              break;
        }
    }

	/**
	 * Assembles message header.  
	 * @access private
	 * @return string
	 */
	function CreateHeader() {
		$result = '';

		// Set the boundaries
		$uniq_id           = md5( uniqid( time() ) );
		$this->boundary[1] = 'b1_'.$uniq_id;
		$this->boundary[2] = 'b2_'.$uniq_id;

		$result .= $this->HeaderLine( 'MIME-Version', '1.0' );
		$result .= $this->Received();
		$result .= $this->HeaderLine( 'Message-ID', '<'.$uniq_id.'@'.$this->ServerHostname().'>' );
		$result .= $this->HeaderLine( 'Date', $this->RFCDate() );
		$result .= $this->HeaderLine( 'Return-Path', ( trim( $this->Sender ) == '' )? $this->From : $this->Sender );
		$result .= $this->HeaderLine( 'Importance', $this->Importance );
		$result .= ( $this->Mailer != 'mail' )? $this->HeaderLine( 'Subject', $this->Subject ) : '';
		$result .= ( $this->ConfirmReadingTo !== '' )? $this->HeaderLine( 'Disposition-Notification-To', '<'.trim( $this->ConfirmReadingTo ).'>' ) : '';


		$result .= $this->AddrAppend( 'From', array( array( $this->From, $this->FromName ) ) );

		// To be created automatically by mail()
		if ( $this->Mailer != 'mail' ) {
			if ( count( $this->to ) == 0 && count( $this->cc ) == 0 && !$this->SaveMessageAsDraft )
				$result .= $this->HeaderLine( 'To', $this->Lang( 'undisclosed-recipient' ) );
			$result .= $this->AddrAppend( 'To', $this->to );
			$result .= $this->AddrAppend( 'Cc', $this->cc );
		}

		// sendmail and mail() extract Bcc from the header before sending
		$result .= ( $this->Mailer == 'sendmail' || $this->Mailer == 'mail' )? $this->AddrAppend( 'Bcc', $this->bcc ) : '';
		$result .= ( $this->SaveMessageAsDraft )? $this->AddrAppend( 'Bcc', $this->bcc ) : '';
		$result .= $this->AddrAppend( 'Reply-to', $this->ReplyTo );
		$result .= $this->write_message_type();

		// X headers
		$result .= $this->HeaderLine( 'X-Mailer', 'ExpressoMail [version '.$this->Version.']' );
		$result .= $this->HeaderLine( 'X-Priority', $this->Priority );
		if ( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_x_origin'] )
			$result .= $this->HeaderLine( 'X-Origin', isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
		
		// Add custom headers
		foreach ( (array)$this->CustomHeader as $header ) $result .= $this->HeaderLine( $header[0], $header[1] );

		// Empty new line defines end of header section
		if ( $this->Mailer !== 'mail' ) $result .= $this->LE;

		return $result;
	}

	function write_message_type()
	{
		switch ( $this->message_type ) {
			case 'plain':
				return $this->HeaderLine( 'Content-Transfer-Encoding', $this->Encoding )
					.$this->HeaderLine( 'Content-Type', $this->ContentType.'; charset="'.$this->CharSet.'"' );
			case 'attachments': case 'alt_attachments':
				return $this->HeaderLine( 'Content-Type', $this->InlineImageExists()? 'multipart/related; type="text/html";' : 'multipart/mixed;' )
					.$this->SPW.'boundary="'.$this->boundary[1].'"'.$this->LE;
			case 'alt':
				return $this->HeaderLine( 'Content-Type', 'multipart/alternative;' )
					.$this->SPW.'boundary="'.$this->boundary[1].'"'.$this->LE;
		}
		return '';
	}

    /**
     * Assembles the message body.  Returns an empty string on failure.
     * @access private
     * @return string
     */
    function CreateBody() {
        $result = "";

        $this->SetWordWrap();
        switch($this->message_type)
        {
            case "alt":
                $result .= $this->GetBoundary($this->boundary[1], "", 
                                              "text/plain", "");
                $result .= $this->_encodeString($this->AltBody, $this->Encoding);
                $result .= $this->LE.$this->LE;
                $result .= $this->GetBoundary($this->boundary[1], "", 
                                              "text/html", "");
                
                $result .= $this->_encodeString($this->Body, $this->Encoding);
                $result .= $this->LE.$this->LE;
    
                $result .= $this->EndBoundary($this->boundary[1]);
                break;
            case "plain":
            	$result .= $this->_encodeString($this->Body, $this->Encoding);
                break;
            case "attachments":
                $result .= $this->GetBoundary($this->boundary[1], "", "", "");
                $result .= $this->_encodeString($this->Body, $this->Encoding);
                $result .= $this->LE;
     
                $result .= $this->_attachAll();
                break;
            case "alt_attachments":
                $result .= sprintf("--%s%s", $this->boundary[1], $this->LE);
                $result .= sprintf("Content-Type: %s;%s" .
                                   "\tboundary=\"%s\"%s",
                                   "multipart/alternative", $this->LE, 
                                   $this->boundary[2], $this->LE.$this->LE);
    
                // Create text body
                $result .= $this->GetBoundary($this->boundary[2], "", 
                                              "text/plain", "") . $this->LE;

                $result .= $this->_encodeString($this->AltBody, $this->Encoding);
                $result .= $this->LE.$this->LE;
    
                // Create the HTML body
                $result .= $this->GetBoundary($this->boundary[2], "", 
                                              "text/html", "") . $this->LE;
    
                $result .= $this->_encodeString($this->Body, $this->Encoding);
                $result .= $this->LE.$this->LE;

                $result .= $this->EndBoundary($this->boundary[2]);
                
                $result .= $this->_attachAll();
                break;
        }
        if($this->_hasError())
            $result = "";

        return $result;
    }

    /**
     * Returns the start of a message boundary.
     * @access private
     */
    function GetBoundary($boundary, $charSet, $contentType, $encoding) {
        $result = "";
        if($charSet == "") { $charSet = $this->CharSet; }
        if($contentType == "") { $contentType = $this->ContentType; }
        if($encoding == "") { $encoding = $this->Encoding; }

        $result .= "--" . $boundary. $this->LE;
        $result .= sprintf("Content-Type: %s; charset = \"%s\"", 
                            $contentType, $charSet);
        $result .= $this->LE;
        $result .= $this->HeaderLine("Content-Transfer-Encoding", $encoding);
        $result .= $this->LE;
       
        return $result;
    }
    
    /**
     * Returns the end of a message boundary.
     * @access private
     */
    function EndBoundary($boundary) {
        return $this->LE . "--" . $boundary . "--" . $this->LE; 
    }
    
    /**
     * Sets the message type.
     * @access private
     * @return void
     */
    function SetMessageType() {
        if(count($this->attachment) < 1 && strlen($this->AltBody) < 1)
            $this->message_type = "plain";
        else
        {
            if(count($this->attachment) > 0)
                $this->message_type = "attachments";
            if(strlen($this->AltBody) > 0 && count($this->attachment) < 1)
                $this->message_type = "alt";
            if(strlen($this->AltBody) > 0 && count($this->attachment) > 0)
                $this->message_type = "alt_attachments";
        }
    }

    /////////////////////////////////////////////////
    // ATTACHMENT METHODS
    /////////////////////////////////////////////////

    /**
     * Adds an attachment from a path on the filesystem.
     * Returns false if the file could not be found
     * or accessed.
     * @param string $path Path to the attachment.
     * @param string $name Overrides the attachment name.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @return bool
     */
    function AddAttachment($path, $name = "", $encoding = "base64", 
                           $type = "application/octet-stream") {
        if(!@is_file($path))
        {
            $this->SetError($this->Lang("file_access") . $path);
            return false;
        }

        $filename = basename($path);
        if($name == "")
            $name = $filename;

        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $path;
        $this->attachment[$cur][1] = $filename;
        $this->attachment[$cur][2] = $name;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = false; // isStringAttachment
        $this->attachment[$cur][6] = "attachment";
        $this->attachment[$cur][7] = 0;

        return true;
    }

	/**
	 * Attaches all fs, string, and binary attachments to the message.
	 * Returns an empty string on failure.
	 * @access private
	 * @return string
	 */
	private function _attachAll()
	{
		$buf = '';
		foreach ( $this->attachment as $attachment ) {
			list( $data, $filename, $name, $encoding, $type, $isString, $disposition, $cid ) = $attachment;

			$buf .= '--'.$this->boundary[1].$this->LE;
			$buf .= $this->_getAttachmentHeader( $type, $encoding, $disposition, $name, $cid, $name );
			$buf .= $this->LE;
			$buf .= $this->_encodeString( $isString? $data : file_get_contents( $data ), $encoding );
			$buf .= $this->LE.$this->LE;

			if ( $this->_hasError() ) return '';
		}
		$buf .= '--'.$this->boundary[1].$this->LE;

		return $buf;
	}

	private function _getAttachmentHeader( $type, $encoding, $disposition, $filename = false , $cid = false, $name = false )
	{
		$buf  = 'Content-Type: '.$type.';'.$this->LE;
		$buf .= $this->HeaderLine( 'name', $name?: $filename, true );
		$buf .= 'Content-Disposition: '.$disposition.';'.$this->LE;
		$buf .= $this->HeaderLine( 'filename', $filename, true );
		$buf .= 'Content-Transfer-Encoding: '.$encoding.$this->LE;
		$buf .= ( $cid )? 'Content-ID: <'.$cid.'>'.$this->LE : '';
		return $buf;
	}

	private function _encodeString( $str, $encoding = 'base64' )
	{
		switch ( strtolower( $encoding ) ) {
			case 'binary': return $str;
			case 'base64': return chunk_split( base64_encode( $str ), 76, $this->LE );
			case 'quoted-printable': return quoted_printable_encode( $str );
			case '7bit': case '8bit': {
				$encoded = $this->_fixEOL( $str );
				if ( substr( $encoded, -( strlen( $this->LE ) ) ) != $this->LE ) $encoded .= $this->LE;
				return $encoded;
			}
		}
		$this->SetError( $this->Lang( 'encoding' ).$encoding );
		return '';
	}

	private function _mimeEncode( $str )
	{
		$str = $this->_str_decode( trim( $str ) );
		if ( mb_detect_encoding( $str, 'ASCII', true ) ) return $str;
		$qpe = str_replace( "=\r\n", '', quoted_printable_encode( $str ) );
		$enc = ( ceil( 4*strlen( $str )/3 ) < strlen( $qpe ) )? 'B' : 'Q';
		return '=?UTF-8?'.$enc.'?'.( $enc=='Q'? $qpe : base64_encode( $str ) ).'?=';
	}

	private function HeaderLine( $name, $value, $isField = false )
	{
        $int_encoding = mb_internal_encoding();
        mb_internal_encoding("UTF-8");
		$name  = preg_replace( '/[^\x21-\x7E:]/','', trim( $name ) );
		$name  = $isField? $this->SPW.$name.'="' : ucfirst( $name ).': ';
		$eof   = $isField? '"' : '';
        $value = $this->_str_decode( $value );
		if ( mb_detect_encoding( $value, 'ASCII', true ) ) return rtrim( chunk_split( $name.$value.$eof, 76, $this->LE.$this->SPW ), $this->SPW );
		$B = mb_encode_mimeheader( $value, 'UTF-8', 'B', $this->LE, strlen( $name ) );
		$Q = mb_encode_mimeheader( $value, 'UTF-8', 'Q', $this->LE, strlen( $name ) );
        mb_internal_encoding($int_encoding);
        return $name.( ( strlen( $B ) < strlen( $Q ) )? $B : $Q ).$eof.$this->LE;
	}

	private function _str_decode( $str, $charset = false )
	{
		if ( preg_match( '/=\?[\w-#]+\?[BQ]\?[^?]*\?=/', $str ) ) $str = mb_decode_mimeheader( $str );
		return $this->_toUTF8( $str, $charset );
	}
	
	private function _toUTF8( $str, $charset = false, $to = 'UTF-8' )
	{
		return mb_convert_encoding( $str, $to, ( $charset === false? mb_detect_encoding( $str, 'UTF-8, ISO-8859-1', true ) : $charset ) );
	}

	private function AddrAppend( $name, $addrs )
	{
        if ( count( $addrs ) === 0 ) return '';
        $int_encoding = mb_internal_encoding();
        mb_internal_encoding("UTF-8");
		$str = ucfirst( preg_replace( '/[^\x21-\x7E:]/','', trim( $name ) ) ).': ';
		$skip_first = true;
		foreach ( $addrs as $addr ) {
			if ( $skip_first ) $skip_first = false;
			else $str .= $this->_str_split( ', ', $str );
			$label  = $this->_str_decode( $addr[1] );
			$email  = empty( $label )? trim( $addr[0] ) : '<'.trim( $addr[0] ).'>';
			if ( !empty( $label ) ) {
				$B = mb_encode_mimeheader( $label, 'UTF-8', 'B', $this->LE, $this->_str_indent( $str ) );
				$Q = mb_encode_mimeheader( $label, 'UTF-8', 'Q', $this->LE, $this->_str_indent( $str ) );
				$str .= ( strlen( $B ) < strlen( $Q ) )? $B : $Q;
				$str .= $this->_str_split( ' ', $str );
			}
			$str .= $this->_str_split( $email, $str );
        }
        mb_internal_encoding($int_encoding);
		return $str.$this->LE;
		
	}

	private function _str_split( $str, &$buffer )
	{
		$indent = $this->_str_indent( $buffer );
		return substr( mb_substr( chunk_split( str_repeat( ' ', $indent ).$str, 76, $this->LE.$this->SPW ), 0, -mb_strlen( $this->LE.$this->SPW ) ), $indent );
	}

	private function _str_indent( &$buffer )
	{
		if ( ( $indent = mb_strlen( preg_replace( '/.*\n/', '', $buffer ) ) ) >= 76 ) {
			$buffer .= $this->LE.$this->SPW;
			return 0;
		}
		return $indent;
	}

    /**
     * Adds a string or binary attachment (non-filesystem) to the list.
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     * @param string $string String attachment data.
     * @param string $filename Name of the attachment.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @return void
     */
    function AddStringAttachment($string, $filename, $encoding = "base64", 
                                 $type = "application/octet-stream", $name = false ) {
        // Append to $attachment array
        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $string;
        $this->attachment[$cur][1] = $filename;
        $this->attachment[$cur][2] = $name?: $filename;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = true; // isString
        $this->attachment[$cur][6] = "attachment";
        $this->attachment[$cur][7] = 0;
    }
    
    /**
     * Adds an embedded attachment.  This can include images, sounds, and 
     * just about any other document.  Make sure to set the $type to an 
     * image type.  For JPEG images use "image/jpeg" and for GIF images 
     * use "image/gif".
     * @param string $path Path to the attachment.
     * @param string $cid Content ID of the attachment.  Use this to identify 
     *        the Id for accessing the image in an HTML form.
     * @param string $name Overrides the attachment name.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.  
     * @return bool
     */
    function AddEmbeddedImage($path, $cid, $name = "", $encoding = "base64", 
                              $type = "application/octet-stream") {
    
        if(!@is_file($path))
        {
            $this->SetError($this->Lang("file_access") . $path);
            return false;
        }

        $filename = basename($path);
        if($name == "")
            $name = $filename;

        // Append to $attachment array
        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $path;
        $this->attachment[$cur][1] = $filename;
        $this->attachment[$cur][2] = $name;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = false; // isStringAttachment
        $this->attachment[$cur][6] = "inline";
        $this->attachment[$cur][7] = $cid;
    
        return true;
    }
    
    function AddStringEmbeddedImage( $string, $cid, $name = "", $encoding = "base64", $type = "application/octet-stream", $filename = false )
    {
        // Append to $attachment array
        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $string;
        $this->attachment[$cur][1] = $filename?: $name;
        $this->attachment[$cur][2] = $name;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = true; // isStringAttachment
        $this->attachment[$cur][6] = "inline";
        $this->attachment[$cur][7] = $cid;
        
        return true;
    }
    
    /**
     * Returns true if an inline attachment is present.
     * @access private
     * @return bool
     */
    function InlineImageExists() {
        $result = false;
        for($i = 0; $i < count($this->attachment); $i++)
        {
            if($this->attachment[$i][6] == "inline")
            {
                $result = true;
                break;
            }
        }
        
        return $result;
    }

    /////////////////////////////////////////////////
    // MESSAGE RESET METHODS
    /////////////////////////////////////////////////

    /**
     * Clears all recipients assigned in the TO array.  Returns void.
     * @return void
     */
    function ClearAddresses() {
        $this->to = array();
    }

    /**
     * Clears all recipients assigned in the CC array.  Returns void.
     * @return void
     */
    function ClearCCs() {
        $this->cc = array();
    }

    /**
     * Clears all recipients assigned in the BCC array.  Returns void.
     * @return void
     */
    function ClearBCCs() {
        $this->bcc = array();
    }

    /**
     * Clears all recipients assigned in the ReplyTo array.  Returns void.
     * @return void
     */
    function ClearReplyTos() {
        $this->ReplyTo = array();
    }

    /**
     * Clears all recipients assigned in the TO, CC and BCC
     * array.  Returns void.
     * @return void
     */
    function ClearAllRecipients() {
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
    }

    /**
     * Clears all previously set filesystem, string, and binary
     * attachments.  Returns void.
     * @return void
     */
    function ClearAttachments() {
        $this->attachment = array();
    }

    /**
     * Clears all custom headers.  Returns void.
     * @return void
     */
    function ClearCustomHeaders() {
        $this->CustomHeader = array();
    }


    /////////////////////////////////////////////////
    // MISCELLANEOUS METHODS
    /////////////////////////////////////////////////

    /**
     * Adds the error message to the error container.
     * Returns void.
     * @access private
     * @return void
     */
    function SetError($msg) {
        
        if( !$this->SaveMessageAsDraft ){
            $this->error_count++;
            $this->ErrorInfo = $msg;
        }
    }

    /**
     * Returns the proper RFC 822 formatted date. 
     * @access private
     * @return string
     */
    function RFCDate() {
        $tz = date("Z");
        $tzs = ($tz < 0) ? "-" : "+";
        $tz = abs($tz);
        $tz = ($tz/3600)*100 + ($tz%3600)/60;
        $result = sprintf("%s %s%04d", date("D, j M Y H:i:s"), $tzs, $tz);

        return $result;
    }
    
    /**
     * Returns Received header for message tracing.
     * @access private
     * @return string
     */
    function Received() {
    	if ($this->ServerVar('SERVER_NAME') != '')
    	{
    		$protocol = ($this->ServerVar('HTTPS') == 'on') ? 'HTTPS' : 'HTTP';
    		$remote = $this->ServerVar('REMOTE_HOST');
    		if($remote == "")
    			$remote = 'phpmailer';
    			$remote .= ' (['.$this->ServerVar('REMOTE_ADDR').'])';
    	}
    	else
    	{
    		$protocol = 'local';
    		$remote = $this->ServerVar('USER');
    		if($remote == '')
    			$remote = 'phpmailer';
    	}
    	return $this->HeaderLine('Received', 'from '.$remote.' by '.$this->ServerHostname().' with '.$protocol.' (PHPMailer); '.$this->RFCDate() );
    }
    
    /**
     * Returns the appropriate server variable.  Should work with both 
     * PHP 4.1.0+ as well as older versions.  Returns an empty string 
     * if nothing is found.
     * @access private
     * @return mixed
     */
    function ServerVar($varName) {

			if(!isset($_SERVER))
			{
				if(!isset($_SERVER["REMOTE_ADDR"]))
					$_SERVER = $_ENV; // must be Apache
			}

			if(isset($_SERVER[$varName]))
				return $_SERVER[$varName];
			else
				return "";
    }

    /**
     * Returns the server hostname or 'localhost.localdomain' if unknown.
     * @access private
     * @return string
     */
    function ServerHostname() {
        if ($this->Hostname != "")
            $result = $this->Hostname;
        elseif ($this->ServerVar('SERVER_NAME') != "")
            $result = $this->ServerVar('SERVER_NAME');
        else
            $result = "localhost.localdomain";

        return $result;
    }

    /**
     * Returns a message in the appropriate language.
     * @access private
     * @return string
     */
    function Lang($key) {
        if(count($this->language) < 1)
            $this->SetLanguage("br"); // set the default language
    
        if(isset($this->language[$key]))
            return $this->language[$key];
        else
            return "Language string failed to load: " . $key;
    }

	/**
	 * Returns true if an error occurred.
	 * @return bool
	 */
	private function _hasError()
	{
		return ($this->error_count > 0);
	}

	/**
	 * Changes every end of line from CR or LF to CRLF.  
	 * @access private
	 * @return string
	 */
	private function _fixEOL( $str )
	{
		return
			str_replace( "\n", $this->LE,
			str_replace( "\r", "\n",
			str_replace( "\r\n", "\n", $str ) ) );
	}

	/**
	 * Adds a custom header. 
	 * @return void
	 */
	function AddCustomHeader($custom_header)
	{
		$this->CustomHeader[] = explode(":", $custom_header, 2);
	}
}
