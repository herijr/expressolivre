<?php

require_once dirname(__FILE__)."/../bbb/bbb-api.php";

class webconference
{
	private $bbb;

	var $public_functions = array( 'createMeeting'	=> True );

	public function __construct()
	{
		$this->bbb = new BigBlueButton();
	}

	private function convertChar($param)
	{
		$param = mb_convert_encoding( $param ,"UTF8", "ISO_8859-1" );

		$array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç"
		, "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
		
		$array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c"
		, "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
		
		return str_replace( $array1, $array2, $param);
	}

	public function createMeeting( $params )
	{
		$nameRoom 		= $this->convertChar($params['title']);
		$attendeePw 	= "ap".substr(strrev(mktime(date("H:i:s"))), 0, 5);
		$moderatorPw	= "mp-1".substr(strrev(mktime(date("H:i:s"))), 0, 5);
		$meetingId		= mktime(date("m.d.y - H:i:s"));

		$creationParams = array(
				'meetingId' 	=> $meetingId, 			
				'meetingName' 	=> "Sala - ". $nameRoom . " - " . date("d.m.y - H:i:s"), 	
				'attendeePw' 	=> $attendeePw, 		
				'moderatorPw' 	=> $moderatorPw, 		
				'welcomeMsg' 	=> '', 					
				'dialNumber' 	=> '', 					
				'voiceBridge' 	=> '', 					
				'webVoice' 		=> '', 					
				'logoutUrl' 	=> '', 					
				'maxParticipants' => '-1', 				
				'record' 		=> 'false', 			
				'duration' 		=> '0' 				
		);

		$isCreated = true;
		try{ $result = $this->bbb->createMeetingWithXmlResponseArray($creationParams); }
		catch( Exception $e ){ $isCreated = false; }

		if( $isCreated == true )
		{
			if( $result == null )
			{
				$this->_errorBBB( $params['participants'], $creationParams );
			}	
			else
			{ 
				if( $result['returncode'] == 'SUCCESS' )
				{
					$this->_successBBB( $params['participants'], $creationParams );
				}
				else
				{
					$this->_errorBBB( $params['participants'], $creationParams );
				}
			}
		}
		else
		{
			$this->_errorBBB( $params['participants'], $creationParams );
		}
	}

	private function _successBBB( $participants, $creationParams )
	{
		$userLdap = CreateObject("phpgwapi.accounts");

		foreach( $participants as $user => $type )
		{
			$_user = array
			(
				"mail" 		=> $userLdap->id2name( $user, "account_email" ),
				"name"		=> $userLdap->id2name( $user, "account_lastname"),
				"account"	=> ( $userLdap->id2name( $user, 'account_type' ) !== "u"  ? "group" : "user" ) 
			);

			switch( strtoupper($type) )
			{
				case "A" : 
					
					$_user["room"] 		= $creationParams['meetingName'];
					$_user["password"]	= $creationParams['moderatorPw'];

					$url = $this->getJoinMeetingURL($creationParams, true , $_user['name'] ); break;

				case "U" : 
					
					$_user["room"] 		= $creationParams['meetingName'];
					$_user["password"]	= $creationParams['attendeePw'];
					
					$url = $this->getJoinMeetingURL($creationParams, false, $_user['name'] ); break;
			}

			if( $_user['account'] === "user" ){ $this->_sendMail( $_user , $url ); }
		}
	}

	private function _errorBBB( $participants, $creationParams )
	{
		$userLdap = CreateObject("phpgwapi.accounts");

		foreach( $participants as $user => $type )
		{
			$_user = array
			(
				"mail" 		=> $userLdap->id2name( $user, "account_email" ),
				"name"		=> $userLdap->id2name( $user, "account_lastname"),
				"account"	=> ( $userLdap->id2name( $user, 'account_type' ) !== "u"  ? "group" : "user" ) 
			);

			switch( strtoupper($type) )
			{
				case "A" : 
					
					$_user["room"] 		= $creationParams['meetingName'];
					$_user["password"]	= $creationParams['moderatorPw'];
				
					if( $_user['account'] === "user" ){ $this->_sendMail( $_user , FALSE ); }
					
					break;
			}
		}
	}

	private function _sendMail( $user, $url )
	{
		//Seta o email usando phpmailer
		define('PHPGW_INCLUDE_ROOT','../');	
		define('PHPGW_API_INC','../phpgwapi/inc');	
		include_once(PHPGW_API_INC.'/class.phpmailer.inc.php');
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$emailadmin = CreateObject('emailadmin.bo')->getProfile();
		$mail->Host = $emailadmin['smtpServer'];
		$mail->Port = $emailadmin['smtpPort'];
		$mail->From = "no-reply@celepar.pr.gov.br";
		$mail->FromName = "Sistema WEB Conference";
		$mail->IsHTML(true);
		$mail->AddAddress($user["mail"]);
		$mail->Subject = " IMPORTANTE - WEB CONFERENCE ";

		$body = "<div style='font-family: Verdana, arial, sans'>".	
				"<img src='http://www.celepar.pr.gov.br/themes/celepar/images/logo_cia_celepar.png'/>";

		if( $url )
		{	
			$body .= "<div>".
					"<p>Voc&ecirc; est&aacute; recebendo um link para acessar o sistema de WEB Conference da CELEPAR</p>".
					"<a href='".$url."' style='font-weight:bold;'>CLIQUE AQUI</a>".
					"</div>".
					"<div>".
					"<p>Se voc&ecirc; n&atilde;o conseguir utilizar o link que est&aacute; na mensagem, acesse <a href='http://webconf.pr.gov.br'>http://webconf.pr.gov.br</a> e informe os seguintes dados :</p>". 
					"<div> <label style='font-weight:bold;'>Seu nome : </label>".$user['name']." ou um apelido</div>".
					"<div> <label style='font-weight:bold;'>Sala : </label>".$user['room']."</div>".
					"<div> <label style='font-weight:bold;'>Senha : </label>".$user['password']."</div>".
					"<p> E clique no bot&atilde;o <label style='font-weight:bold;'>'Acessar'</label>.</p>".
					"</div>";
		}
		else
		{
			$body .= "<div>".
					 "<p>O sistema de WEB Conference da CELEPAR retornou um erro ao tentar criar a sala para o evento ".$user['room'].".</p>" .
					 "<p>O evento continua marcado na agenda do Expresso, mas a sala de confer&ecirc;ncia n&atilde;o foi aberta.</p>" .
					 "<p>Tente criar a sala manualmente no endere&ccedil;o <a href='http://webconf.pr.gov.br'>webconf.pr.gov.br</a></p>" .
				 	 "</div>";
		}

		$body .= "<div style='background:#E8E8E8; color:red; border:1px dashed #cecece; margin: 50px 0;text-align:center;font-size:12px;'>".
				 "<p>".
				 "Por favor n&atilde;o responda essa mensagem. Esse &eacute; um e-mail autom&aacute;tico do Expresso.". 
				 "</p>".
				 "</div>".
				 "</div>";
		
		$mail->Body = $body;
			
		$mail->Send();
	}

	private function getJoinMeetingURL( $params, $moderator, $user )
	{
		$joinParams = array(
			'meetingId' 	=> $params['meetingId'],
			'username' 		=> $user,
			'password' 		=> ( $moderator ? $params['moderatorPw'] : $params['attendeePw'] ),
			'createTime' 	=> '',
			'userId' 		=> '',
			'webVoiceConf' 	=> ''
		);

		// Get the URL to join meeting:
		$isJoin = true;
		try { $result = $this->bbb->getJoinMeetingURL($joinParams); }
		catch (Exception $e) { $isJoin = false; }

		return ( $isJoin == true ) ? $result : false;
	}
}