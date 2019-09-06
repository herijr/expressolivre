<?php

require_once dirname( __FILE__ ) .'/../../api/vendor/autoload.php';

include_once("class.functions.inc.php");
include_once("class.ldap_functions.inc.php");
include_once("class.exporteml.inc.php");
include_once("class.db_functions.inc.php");

use Sabre\VObject;

class imap_functions
{
	var $public_functions = array
	(
		'get_range_msgs'				=> True,
		'get_info_msg'					=> True,
		'get_info_msgs'					=> True,
		'get_folders_list'				=> True,
		'import_msgs'					=> True,
	);

	var $ldap;
	var $mbox;
	var $imap_port;
	var $has_cid;
	var $imap_options = '';
	var $functions;
	var $prefs;
	var $foldersLimit;
	var $imap_sentfolder;
	var $fullNameUser;

	public function __construct(){
		$this->foldersLimit     = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['imap_max_folders'] ?  $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['imap_max_folders'] : 20000; //Limit of folders (mailboxes) user can see
		$this->username         = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
		$this->password         = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
		$this->imap_server      = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
		$this->imap_port        = $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
		$this->imap_delimiter   = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDelimiter'];
		$this->functions        = new functions();
		$this->imap_sentfolder  = $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder']   ? $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder']   : str_replace("*","", $this->functions->getLang("Sent"));
		$this->has_cid          = false;
		$this->prefs            = $_SESSION['phpgw_info']['user']['preferences']['expressoMail'];
		$this->mbox							= false;
		$this->imap_options     = '/novalidate-cert'.(
			($_SESSION['phpgw_info']['expressomail']['email_server']['imapTLSEncryption'] == 'yes')? '/tls' : '/notls'
		);
		
		// Conf full name for display on sending email
		$this->fullNameUser = $_SESSION['phpgw_info']['expressomail']['user']['fullname'];

		if( trim($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['display_user_email']) != "" )
		{
			$this->fullNameUser = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['display_user_email'];
		}	
	}

	// BEGIN of functions.
	function open_mbox($folder = false , $force_die = true)
	{
		$mailbox = $this->_toMaibox( $folder );
		if (is_resource($this->mbox))
        	{
			if ($force_die)
			{
				@imap_reopen($this->mbox, "{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$mailbox) or die(serialize(array('imap_error' => $this->parse_error(imap_last_error()))));
			} else {
				@imap_reopen($this->mbox, "{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$mailbox);
			}
        	} else {

			if($force_die)
			{
				$this->mbox = @imap_open("{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$mailbox, $this->username, $this->password) or die(serialize(array('imap_error' => $this->parse_error(imap_last_error()))));
			} else {
				$this->mbox = @imap_open("{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$mailbox, $this->username, $this->password);
			}
		}

		$errors = imap_errors();
		if(is_array($errors)){
			if( preg_match('/SECURITY PROBLEM: insecure server advertised AUTH=PLAIN/i', $errors[0]) === false){
			  throw new Exception('IMAP error detected');
			}
		}

		return $this->mbox;
	 }
	 
	private function close_mbox( $conn, $expunge = false )
	{
		if( is_resource($conn) ){
			$errors = imap_errors();
			if(is_array($errors)){
				if( preg_match('/SECURITY PROBLEM: insecure server advertised AUTH=PLAIN/i', $errors[0]) === false){
				  throw new Exception('IMAP error detected');
				}
			}
			if( !$expunge ){ 
				imap_close( $conn ); 
			} else {
				imap_close( $conn, $expunge ); 
			}
		}
	}

	function parse_error($error){
		// This error is returned from Imap.
		if(strstr($error,'Connection refused')) {
			return str_replace("%1", $this->functions->getLang("Mail"), $this->functions->getLang("Connection failed with %1 Server. Try later."));
		}
		// This error is returned from Postfix.
		elseif(strstr($error,'message file too big')) {
			return str_replace("%1",$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['max_attachment_size'],$this->functions->getLang('The size of this message has exceeded  the limit (%1B).'));
		}
		elseif(strstr($error,'virus')) {
			return str_replace("%1", $this->functions->getLang("Mail"), $this->functions->getLang("Your message was rejected by antivirus. Perhaps your attachment has been infected."));
		}
		// This condition verifies if SESSION is expired.
		elseif(!count($_SESSION))
			return "nosession";

		return $error;
	}

	function get_range_msgs2($params)
	{
		// Verify migrate MB
		$db = new db_functions();

		if ( $db->getMigrateMailBox() ) return false;

		// Free others requests 
		session_write_close();

		$folder           = $this->_toUTF8( $params['folder'] );
		$msg_range_begin  = $params['msg_range_begin'];
		$msg_range_end    = $params['msg_range_end'];
		$sort_box_type    = $params['sort_box_type'];
		$sort_box_reverse = $params['sort_box_reverse'];
		$search_box_type  = $params['search_box_type'] != "ALL" && $params['search_box_type'] != "" ? $params['search_box_type'] : false;

		if ( !$this->mbox || !is_resource( $this->mbox ) ) $this->mbox = $this->open_mbox( $folder );

		$return = array();

		$return['folder'] = $folder;

		//Para enviar o offset entre o timezone definido pelo usuario e GMT
		$return['offsetToGMT'] = $this->functions->CalculateDateOffset();

		if ( !$search_box_type || $search_box_type === 'UNSEEN' || $search_box_type === 'SEEN' ) {
			$msgs_info            = imap_status( $this->mbox,'{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}'.$this->_toMaibox( $folder ), SA_ALL );
			$return['tot_unseen'] = $search_box_type === 'SEEN'? 0 : ( isset( $msgs_info->unseen )? $msgs_info->unseen : false );
			$sort_array_msg       = $this->get_msgs( $folder, $sort_box_type, $search_box_type, $sort_box_reverse, $msg_range_begin, $msg_range_end );
			$num_msgs             = ( isset( $msgs_info->unseen ) && isset( $msgs_info->messages ) )? (
				$search_box_type === 'UNSEEN'? $msgs_info->unseen : (
					$search_box_type === 'SEEN'? ( $msgs_info->messages - $msgs_info->unseen ) : $msgs_info->messages
				)
			) : 0;

			$i = 0;
			if ( is_array( $sort_array_msg ) ) {
				foreach ( $sort_array_msg as $msg_number => $value ) {
					$temp = $this->get_info_head_msg( $msg_number );
					$temp['msg_sample'] = $this->get_msg_sample( $msg_number, $folder );
					if ( !$temp ) return false;
					$return[$i] = $temp;
					$i++;
				}
			}
			$return['num_msgs'] = $num_msgs;

		} else {

			$return['tot_unseen'] = 0;
			$num_msgs             = imap_num_msg( $this->mbox );
			$sort_array_msg       = $this->get_msgs( $folder, $sort_box_type, $search_box_type, $sort_box_reverse, $msg_range_begin, $num_msgs );

			$i = 0;
			if ( is_array( $sort_array_msg ) ) {
				foreach ( $sort_array_msg as $msg_number => $value ) {
					$temp = $this->get_info_head_msg( $msg_number );
					if ( !$temp ) return false;
					if ( $temp['Unseen'] === 'U' || $temp['Recent'] === 'N' ) $return['tot_unseen']++;
					if ( $i <= ( $msg_range_end - $msg_range_begin ) ) $return[$i] = $temp;
					$i++;
				}
			}
			$return['num_msgs'] = count( $sort_array_msg ) + ( $msg_range_begin - 1 );

		}
		return $return;
	}

	function get_info_head_msg($msg_number)
	{
		include_once("class.imap_attachment.inc.php");
		
		$head_array = array();
		
		$imap_attachment = new imap_attachment();

		$tempHeader = imap_fetchheader($this->mbox, imap_msgno($this->mbox, $msg_number));
		$flag = preg_match('/importance *: *(.*)\r/i', $tempHeader, $importance);
		$head_array['ContentType'] = $this->getMessageType($msg_number, $tempHeader);
		$head_array['Importance'] = $flag==0?"Normal":$importance[1];

		$header = $this->get_header($msg_number);
		
		if (!is_object($header))
			return false;
		
		$head_array['Recent'] = $header->Recent;
		$head_array['Unseen'] = $header->Unseen;
		
		if($header->Answered =='A' && $header->Draft == 'X'){
			$head_array['Forwarded'] = 'F';
		} else {
			$head_array['Answered']	= $header->Answered;
			$head_array['Draft']	= $header->Draft;
		}

		$head_array['Deleted'] = $header->Deleted;
		$head_array['Flagged'] = $header->Flagged;
		$head_array['msg_number'] = $msg_number;
		$head_array['udate'] = $header->udate;
		$head_array['offsetToGMT'] = $this->functions->CalculateDateOffset();

		$msgTimestamp = $header->udate + $head_array['offsetToGMT'];
		$head_array['timestamp'] = $msgTimestamp;
		
		$date_msg = gmdate("d/m/Y",$msgTimestamp);

		$head_array['smalldate'] = (date("d/m/Y") == $date_msg) ? gmdate("H:i",$msgTimestamp) : gmdate("d/m/Y",$msgTimestamp);

		$head_array['from']    = (array)$this->mk_addr( isset( $header->from ) && is_array( $header->from )? reset( $header->from ) : false );
		$head_array['to']      = (array)$this->mk_addr( isset( $header->to ) && is_array( $header->to ) && !( isset( $header->to[1]->host ) && $header->to[1]->host === '.SYNTAX-ERROR.' )? reset( $header->to   ) : false );

		if ( empty( $head_array['to']['email'] ) && isset( $header->cc  ) && is_array( $header->cc  ) ) $head_array['to'] = (array)$this->mk_addr( reset( $header->cc ) );
		if ( empty( $head_array['to']['email'] ) && isset( $header->bcc ) && is_array( $header->bcc ) ) $head_array['to'] = (array)$this->mk_addr( reset( $header->bcc ) );
		if ( empty( $head_array['to']['email'] ) ) $head_array['to']['name'] = $head_array['to']['email'] = null;

		$head_array['subject'] = ( isset( $header->fetchsubject ) ) ? trim($this->decode_string($header->fetchsubject)) : '';
		$head_array['subject'] = ( strstr( $head_array['subject'], ' ' ) === false )? str_replace( '_', ' ', $head_array['subject'] ) : $head_array['subject'];		
		$head_array['Size'] = $header->Size;

		$head_array['attachment'] = array();
		$head_array['attachment'] = $imap_attachment->get_attachment_headerinfo($this->mbox, $msg_number);

		return $head_array;
	}

	function decode_string($string)
	{

		if ((strpos(strtolower($string), '=?iso-8859-1') !== false) || (strpos(strtolower($string), '=?windows-1252') !== false))
		{
			$return = '';
			$tmp = imap_mime_header_decode($string);
			foreach ($tmp as $tmp1)
				$return .= $this->htmlspecialchars_encode($tmp1->text);

			$return = str_replace("\t", "", $return); 
			return $return;
		}
		else if (strpos(strtolower($string), '=?utf-8') !== false)
		{
			$elements = imap_mime_header_decode($string);
			$decoded = "";
			
  			for($i = 0;$i < count($elements);$i++)
  			{
   				$charset = strtolower($elements[$i]->charset);
   				$text = $elements[$i]->text;

   				if(!strcasecmp($charset, "utf-8") || !strcasecmp($charset, "utf-7"))
   				{
       				$decoded .= $this->functions->utf8_to_ncr($text);
       			}
  				else
  				{
					if( strcasecmp($charset,"default") )
						$decoded .= $this->htmlspecialchars_encode(iconv($charset, "iso-8859-1", $text));
					else
						$decoded .= $this->htmlspecialchars_encode($text);
  				}
  			}
	  		return $decoded;
		}
		else
			return $this->htmlspecialchars_encode($string);
	}

	protected function fileUploadErrors( $error )
	{
		switch ( $error ) {
			case 0: return false;
			case 1: return 'The uploaded file exceeds the upload max filesize allowed';
			case 2: return 'The uploaded file exceeds the upload max filesize allowed by the form';
			case 3: return 'The uploaded file was only partially uploaded';
			case 4: return 'No file was uploaded';
			case 6: return 'Missing a temporary folder';
			case 7: return 'Failed to write file to disk.';
			case 8: return 'A PHP extension stopped the file upload.';
		}
		return 'An unknown error occurred';
	}

	/**
	* Funcao que importa arquivos .eml exportados pelo expresso para a caixa do usuario. Testado apenas
	* com .emls gerados pelo expresso, e o arquivo pode ser um zip contendo varios emls ou um .eml.
	*/
	function import_msgs( $params )
	{
		if ( !$this->mbox ) $this->mbox = $this->open_mbox();

		$mailbox = $this->_toMaibox( $params['folder'] );
		$file    = isset( $params['FILES'] )? current( $params['FILES'] ) : current( $_FILES );
		$quota   = @imap_get_quotaroot( $this->mbox, $mailbox );
		$errors  = array();

		if ( isset($file['error']) && $file['error'] != 0 )
			return array( 'error' => $this->functions->getLang( 'fail in import:' ).' '. $this->functions->getLang( $this->fileUploadErrors( $file['error'] ) ) );
		
		if ( ( ( $quota['limit'] - $quota['usage'] ) * 1024 ) <= $file['size'] ){
			return array( 'error' => $this->functions->getLang( 'fail in import:' ).' '.$this->functions->getLang( 'Over quota' ) );
		}

		if ( preg_match( '/\.zip$/i', $file['name'] ) ) {
			if ( $zip = zip_open( $file['tmp_name'] ) ) {
				while ( $zip_entry = zip_read( $zip ) ) {
					if ( preg_match( '/\.eml$/i', zip_entry_name( $zip_entry ) ) && zip_entry_open( $zip, $zip_entry, 'r' ) ) {
						$email  = zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
						$status = @imap_append( $this->mbox, '{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}'.$mailbox, preg_replace( "/(?<=[^\r]|^)\n/", "\r\n", $email ) );
						if ( !$status ) array_push( $errors, zip_entry_name( $zip_entry ) );
						zip_entry_close( $zip_entry );
					}
				}
				zip_close( $zip );
			}
		} else if ( preg_match( '/\.eml$/i', $file['name'] ) ) {
			$email  = implode( '',file( $file['tmp_name'] ) );
			$status = @imap_append( $this->mbox, '{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}'.$mailbox, preg_replace( "/(?<=[^\r]|^)\n/", "\r\n", $email ) );
			if ( !$status ) array_push( $errors, $file['tmp_name'] );
		} else {
			return array( 'error' => $this->functions->getLang( 'wrong file format' ) );
		}

		if ( count( $errors ) ) return array( 'error' => $this->functions->getLang( 'fail in import:' ).PHP_EOL.implode( PHP_EOL, $errors ) );

		return $this->functions->getLang( 'The import was executed successfully.' );
	}

	/*
		Remove os anexos de uma mensagem. A estrategia para isso e criar uma mensagem nova sem os anexos, mantendo apenas
		a primeira parte do e-mail, que e o texto, sem anexos.
		O metodo considera que o email e multpart.
	*/
	function remove_attachments($params) {
		include_once("class.message_components.inc.php");
		if(!$this->mbox || !is_resource($this->mbox))
			$this->mbox = $this->open_mbox($params["folder"]);
		$return["status"] = true;
		$header = "";

		$headertemp = explode("\n",imap_fetchheader($this->mbox, imap_msgno($this->mbox, $params["msg_num"])));
		foreach($headertemp as $head) {//Se eu colocar todo o header do email da pau no append, entao procuro apenas o que interessa.
			$head1 = explode(":",$head);
			if ( (strtoupper($head1[0]) == "TO") ||
					(strtoupper($head1[0]) == "FROM") ||
					(strtoupper($head1[0]) == "SUBJECT") ||
					(strtoupper($head1[0]) == "DATE") )
				$header .= $head."\r\n";
		}

		$msg = new message_components($this->mbox);
		$msg->fetch_structure($params["msg_num"]);/* O fetchbody tava trazendo o email com problemas na acentuacao.
							     Entao uso essa classe para verificar a codificacao e o charset,
							     para que o metodo decodeBody do expresso possa trazer tudo certinho*/

		$all_body_type = strtolower($msg->file_type[$params["msg_num"]][0]);
		$all_body_encoding = $msg->encoding[$params["msg_num"]][0];
		$all_body_charset = $msg->charset[$params["msg_num"]][0];
		
		if($all_body_type=='multipart/alternative') {
			if(strtolower($msg->file_type[$params["msg_num"]][2]=='text/html') &&
					$msg->pid[$params["msg_num"]][2] == '1.2') {
				$body_part_to_show = '1.2';
				$all_body_type = strtolower($msg->file_type[$params["msg_num"]][2]);
				$all_body_encoding = $msg->encoding[$params["msg_num"]][2];
				$all_body_charset = $msg->charset[$params["msg_num"]][2];
			}
			else {
				$body_part_to_show = '1.1';
				$all_body_type = strtolower($msg->file_type[$params["msg_num"]][1]);
				$all_body_encoding = $msg->encoding[$params["msg_num"]][1];
				$all_body_charset = $msg->charset[$params["msg_num"]][1];
			}
		}
		else
			$body_part_to_show = '1';

		if (($all_body_charset == "utf-8") && ($all_body_encoding == "base64")){
			$status = imap_append($this->mbox,
					"{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$params["folder"],
						$header.
						"Content-Type: ".$all_body_type."; charset = \"iso-8859-1\"".
						"\r\n".
						"Content-Transfer-Encoding: quoted-printable".
						"\r\n".
						"\r\n".
						str_replace("\n","\r\n",$this->decodeBody(
								imap_fetchbody($this->mbox,imap_msgno($this->mbox, $params["msg_num"]),$body_part_to_show),
								$all_body_encoding, $all_body_charset
								)
						)
						, "\\Seen"); //Append do novo email, so com header e conteudo sem anexos.			
		}else{	
			$status = imap_append($this->mbox,
					"{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$params["folder"],
						$header.
						"Content-Type: ".$all_body_type."; charset = \"".$all_body_charset."\"".
						"\r\n".
						"Content-Transfer-Encoding: ".$all_body_encoding.
						"\r\n".
						"\r\n".
						str_replace("\n","\r\n",$this->decodeBody(
								imap_fetchbody($this->mbox,imap_msgno($this->mbox, $params["msg_num"]),$body_part_to_show),
								$all_body_encoding, $all_body_charset
								)
						)
						, "\\Seen"); //Append do novo email, so com header e conteudo sem anexos.
		}

		if(!$status)
		{
			$return["status"] = false;
			$return["msg"] = lang("error appending mail on delete attachments");
		}
		else
		{
			$status = imap_status($this->mbox, "{".$this->imap_server.":".$this->imap_port."}".$params['folder'], SA_UIDNEXT);
			$return['msg_no'] = $status->uidnext - 1;
			imap_delete($this->mbox, imap_msgno($this->mbox, $params["msg_num"]));
			imap_expunge($this->mbox);
		}

		return $return;

	}

	/**
	 *
	 * @return
	 * @param $params Object
	 */
	function get_info_msgs($params) {
		include_once("class.exporteml.inc.php");
		$return = array();
		$new_params = array();
		$attach_params = array();
		$new_params["msg_folder"]=$params["folder"];
		$attach_params["folder"] = $params["folder"];
		$msgs = explode(",",$params["msgs_number"]);
		$exporteml = new ExportEml();
		$unseen_msgs = array();
		foreach($msgs as $msg_number) {
			$new_params["msg_number"] = $msg_number;
			//ini_set("display_errors","1");
			$msg_info = $this->get_info_msg($new_params);

			$this->mbox = $this->open_mbox($params['folder']); //Nao sei porque, mas se nao abrir de novo a caixa da erro.
			$msg_info['header'] = $this->get_info_head_msg($msg_number);

			$attach_params["num_msg"] = $msg_number;
			$this->close_mbox($this->mbox);
			$this->mbox=false;
			array_push($return,serialize($msg_info));

			if($msg_info['Unseen'] == "U" || $msg_info['Recent'] == "N"){
					array_push($unseen_msgs,$msg_number);
			}
		}
		if($unseen_msgs){
			$msgs_list = implode(",",$unseen_msgs);
			$array_msgs = array('folder' => $new_params["msg_folder"], "msgs_to_set" => $msgs_list, "flag" => "unseen");
			$this->set_messages_flag($array_msgs);
		}

		return $return;
	}

	function get_info_msg($params)
	{
		include_once( 'class.message_reader.inc.php' );
		$mail_reader = new MessageReader();

		$return = array();
		$msg_number = $params['msg_number'];
		if(preg_match('/(.+)(_[a-zA-Z0-9]+)/',$msg_number,$matches)) { //Verifies if it comes from a tab diferent of the main one.
			$msg_number = $matches[1];
			$plus_id = $matches[2];
		}
		else {
			$plus_id = '';
		}
		$msg_folder = urldecode($params['msg_folder']);

		if ( !$this->mbox || !is_resource( $this->mbox ) ) $this->mbox = $this->open_mbox( $msg_folder );

		if ( !( $header = $this->get_header( $msg_number ) ) ) return array( 'status_get_msg_info' => 'false' );

		$header_src = imap_fetchheader( $this->mbox, $msg_number, FT_UID );

		$mail_reader->setMessage( $this->mbox, $msg_folder, $msg_number );

		
		if ( $mail_reader->isCripted() ) {
			$return['source']       = $header_src."\r\n\r\n".imap_body( $this->mbox, $msg_number, FT_UID | FT_PEEK );
			$return['body']         = '';

		} else {
			
			$body_struct            = $mail_reader->getBody();
			$return['body']         = $body_struct->body;
			$return['type']         = $body_struct->type;
			$return['attachments']  = $mail_reader->getAttachInfo();
			if ( isset( $body_struct->body_alternative ) ) $return['body_alternative'] = $body_struct->body_alternative;

			// TODO: Change security code for client (javascript)
			if ( $return['type'] === 'html' ) $return['body'] = $this->replace_special_characters( $return['body'] );

			$return['thumbs'] = $mail_reader->getThumbs();

			if ( $mail_reader->getHashCalendar() ) $return['hash_vcalendar'] = $mail_reader->getHashCalendar();

			$return_get_body = $this->get_body_msg( $msg_number, $msg_folder );
			$return['signature'] = $return_get_body['signature'];
		}

		// Force message with flag Seen (imap_fetchbody not works correctly)
		$this->set_messages_flag( array( 'folder' => $msg_folder, 'msgs_to_set' => $msg_number, 'flag' => 'seen' ) );

		$flag = preg_match('/importance *: *(.*)\r/i', $header_src, $importance);
		$return['Importance'] = ($flag == 0) ? "Normal" : $importance[1];

		$pattern = '/^[ \t]*Disposition-Notification-To:[ ]*<?[[:alnum:]\._-]+@[[:alnum:]_-]+[\.[:alnum:]]+>?/sm';
		if (preg_match($pattern, $header_src, $fields))
		{
			if(preg_match('/[[:alnum:]\._\-]+@[[:alnum:]_\-\.]+/',$fields[0], $matches)){ 
				$return['DispositionNotificationTo'] = "<".$matches[0].">"; 
			} 
		}

		$return['Recent']	= $header->Recent;
		$return['Unseen']	= $header->Unseen;
		$return['Deleted']	= $header->Deleted;
		$return['Flagged']	= $header->Flagged;

		if($header->Answered =='A' && $header->Draft == 'X'){
			$return['Forwarded'] = 'F';
		} else {
			$return['Answered']	= $header->Answered;
			$return['Draft']	= $header->Draft;
		}

		$return['msg_number'] = $msg_number.$plus_id;
		$return['msg_folder'] = $msg_folder;

		$offset = $this->functions->CalculateDateOffset( date( 'D, d M Y H:i:s O', $header->udate ) );
		$msgTimestamp = $header->udate + $offset;

		$date_msg = gmdate("d/m/Y",$msgTimestamp);

		$return['udate'] = $header->udate;
		$return['msg_day'] = $date_msg;
		$return['msg_hour'] = gmdate("H:i",$msgTimestamp);

		if (date("d/m/Y") == $date_msg) //no dia
		{
			$return['fulldate'] = gmdate("d/m/Y H:i",$msgTimestamp);
			$return['smalldate'] = gmdate("H:i",$msgTimestamp);

			$timestamp_now = strtotime("now") + $offset;
			$timestamp_msg_time = $msgTimestamp;
			// $timestamp_now and $timestamp_msg_time are GMT.
			// The variable $timestamp_diff is calculated without MailDate TZ.
			$timestamp_diff = $timestamp_now - $timestamp_msg_time;
			
			if (gmdate("H",$timestamp_diff) > 0)
			{
				$return['fulldate'] .= " (" . gmdate("H:i", $timestamp_diff) . ' ' . $this->functions->getLang('hours ago') . ')';
			}
			else
			{
				if (gmdate("i",$timestamp_diff) == 0){
					$return['fulldate'] .= ' ('. $this->functions->getLang('now').')';
				}
				elseif (gmdate("i",$timestamp_diff) == 1){
					$return['fulldate'] .= ' (1 '. $this->functions->getLang('minute ago').')';
				}
				else{
					$return['fulldate'] .= " (" . gmdate("i",$timestamp_diff) .' '. $this->functions->getLang('minutes ago') . ')';
				}
			}
		}
		else{
			$return['fulldate'] = gmdate("d/m/Y H:i",$msgTimestamp);
			$return['smalldate'] = gmdate("d/m/Y",$msgTimestamp);
		}

		$return['from']     = (array)$this->mk_addr( isset( $header->from     ) && is_array( $header->from     )? reset( $header->from     ) : false );
		$return['sender']   = (array)$this->mk_addr( isset( $header->sender   ) && is_array( $header->sender   )? reset( $header->sender   ) : false );
		$return['reply_to'] = (array)$this->mk_addr( isset( $header->reply_to ) && is_array( $header->reply_to )? reset( $header->reply_to ) : false );

		if ( $return['from']['full']  === $return['sender']['full']    ) unset( $return['sender']   );
		if ( $return['from']['email'] === $return['reply_to']['email'] ) unset( $return['reply_to'] );
		else $return['reply_to'] = $return['reply_to']['full'];
		// Sender attribute
		$return['toaddress2']      = $this->mk_addr_list( isset( $header->to  )? $header->to  : false );
		$return['cc']              = $this->mk_addr_list( isset( $header->cc  )? $header->cc  : false );
		$return['bcc']             = $this->mk_addr_list( isset( $header->bcc )? $header->bcc : false );
		$return['reply_toaddress'] = $header->reply_toaddress;
		$return['Size']            = $header->Size;

		$return['subject'] = ( isset( $header->subject ) )? trim($this->decode_string($header->fetchsubject)) : '';
		$return['subject'] = ( strstr( $return['subject'], ' ' ) === false )? trim( str_replace( '_', ' ', $return['subject'] ) ) : $return['subject'];

		//All this is to help in local messages
		//$return['timestamp'] = $header->udate;
		$return['timestamp'] = $header->udate;
		$return['login'] = $_SESSION['phpgw_info']['expressomail']['user']['account_id'];//$GLOBALS['phpgw_info']['user']['account_id'];

		if($return_get_body['body']=='isSigned'){
			$this->close_mbox($this->mbox);
			$new_mail              = $this->show_decript($return_get_body,$dec = 1);
			$return['body']        = $new_mail['body'];
			$return['attachments'] = $new_mail['attachments'];
			$return['thumbs']      = $new_mail['thumbs'];
			$return['folder']      = $return['msg_folder'];
			$return['original_ID'] = $return['msg_number'];
			$return['msg_folder']  = 'INBOX'.$this->imap_delimiter.'decifradas';
			$return['msg_number']  = $new_mail['msg_no'];
			$return['type']        = isset( $new_mail['type'] )? $new_mail['type'] : $return['type'];
		}

		// compatibility of names
		$return['uid']     = $return['msg_number'];
		$return['folder']  = $return['msg_folder'];
		$return['attachs'] = $return['attachments'];
		ksort( $return );
		return $return;
	}

	function mk_addr( $addr )
	{
		$personal = $this->decode_personal( $addr );
		$email    = $this->decode_email( $addr );
		$email    = preg_replace( "/[[:space:]]/", "", $email );
		$full     = ( empty( $personal ) )? $email : '"'.$personal.'" <'.$email.'>';
		return (object) array( 'name' => empty( $personal )? $email : $personal, 'email' => $email, 'full' => $full );
	}

	function mk_addr_list( $addrs )
	{
		if ( !is_array( $addrs ) ) return '';

		$objClass = $this;

		return implode( ', ', array_filter( array_map( function( $addr ) use($objClass) {
			$addr = $objClass->mk_addr( $addr );
			return $addr->full;
		}, $addrs ) ) );
	}

	function decode_personal( $addr )
	{
		$pers = ( isset( $addr->personal ) && !empty( $addr->personal ) )? trim( $this->_str_decode( $addr->personal ) ) : '';
		return trim( ( strstr( $pers, ' ' ) === false )? trim( str_replace( '_', ' ', $pers ) ) : $pers, " \t\n\r\0\x0B\'\"" );
	}

	function decode_email( $addr )
	{
		return ( isset( $addr->mailbox )? trim( $addr->mailbox ) : '' ).( ( isset( $addr->host ) && trim( strtolower( $addr->host ) ) !== 'unspecified-domain' )? '@'.trim( $addr->host ): '' );
	}

	function get_msg_sample( $msg_number, $folder )
	{

		if (
			( !isset( $this->prefs['preview_msg_subject'] ) || ( $this->prefs['preview_msg_subject'] != '1' ) ) &&
			( !isset( $this->prefs['preview_msg_tip']     ) || ( $this->prefs['preview_msg_tip']     != '1' ) )
		) return array( 'body' => '' );

		include_once 'class.message_reader.inc.php';
		$mail_reader = new MessageReader();
		$this->open_mbox( $folder );
		$mail_reader->setMessage( $this->mbox, $folder, $msg_number );
		$msg_body = $mail_reader->peekBody();

		$content = isset( $msg_body->body_alternative )? $msg_body->body_alternative : $msg_body->body;
		if ( $msg_body->type === 'html' ) {
		
		    // Remove data URI scheme (RFC 2397)
		    $content = preg_replace( '/data:[^, \'\"]*,([^ \'\"]*)/', '', $content );
		
		    $content = $this->replace_special_characters($content);
		    $content = str_replace( array( '<br>', '<br/>', '<br />' ), ' ', $content );
		    $content = strip_tags($content);
		    $content = str_replace( array( '{', '}', '&nbsp;' ), ' ', $content );
		    $content = html_entity_decode( $content );
		}
		$content = trim( mb_substr( trim( $content ), 0, 300 ) );

		return array( 'body' => (empty( $content )? '' : ' - ' ).$content );
	}

	function vCalImport ( $vcalendar ){

		$cal = Sabre\VObject\Reader::read($vcalendar,0, 'ISO-8859-1');

		$event = current( $cal->select('VEVENT') );

		$content = '<div style="padding:2px 3px; margin:6px 2px 10px 2px; width:80%;font-family:Arial,Sans-serif;background-color:#fff;font-size:12px">';
		$content .= '<div style="margin:10px 0px">';
		$content .= '<span style="font-weight:bold;">'.$event->SUMMARY.'</span>';
		$content .= '</div>';

		if(isset($event->ORGANIZER)){
			$content .= "<div style='margin:5px 0px'>";
			$content .= "<span style='font-weight:bold'>".$this->functions->getLang("organizer")." </span> " . ( isset($event->ORGANIZER['CN']) ? $event->ORGANIZER['CN'] : "") . " ( " . preg_replace("/mailto:/i", "",$event->ORGANIZER ) . " )";
			$content .= "</div>";
		}
	
		if(isset($event->ATTENDEE)){
			$content .= '<div style="margin:10px 0px;font-size:13px">';
			$content .= "<span style='font-weight:bold'>".$this->functions->getLang("attendees")."</span><br>";
			foreach($event->ATTENDEE as $attendee ){
				if(isset($attendee['CN']))
					$content .= " - " .$attendee['CN'] . " ( " . preg_replace("/mailto:/i", "", $attendee) . " ) <br>";
			}
			$content .= "</div>";
		}
	
		$dtStart = null;
		$dtEnd = null;
		
		$dateStart = $event->DTSTART.'';//get date from ical
		if( preg_match('/Z/',$dateStart) ){
			$dateStart = str_replace('T', '', $dateStart);//remove T
			$dateStart = str_replace('Z', '', $dateStart);//remove Z
			$d    = date('d', strtotime($dateStart));//get date day
			$m    = date('m', strtotime($dateStart));//get date month
			$y    = date('Y', strtotime($dateStart));//get date year
			$now = date('Y-m-d G:i:s');//current date and time
			$eventdate = date('Y-m-d G:i:s', strtotime($dateStart));//user friendly date
			$dtStart = new DateTime( $eventdate );
			$dtStart->sub(new DateInterval('PT3H'));
		} else {
			$dateStart = str_replace('T', '', $dateStart);//remove T
			$d    = date('d', strtotime($dateStart));//get date day
			$m    = date('m', strtotime($dateStart));//get date month
			$y    = date('Y', strtotime($dateStart));//get date year
			$now = date('Y-m-d G:i:s');//current date and time
			$eventdate = date('Y-m-d G:i:s', strtotime($dateStart));//user friendly date
			$dtStart = new DateTime( $eventdate );
		}

		$dateEnd = $event->DTEND.'';//get date from ical
		if( preg_match('/Z/',$dateEnd) ){
			$dateEnd = str_replace('T', '', $dateEnd);//remove T
			$dateEnd = str_replace('Z', '', $dateEnd);//remove Z
			$d    = date('d', strtotime($dateEnd));//get date day
			$m    = date('m', strtotime($dateEnd));//get date month
			$y    = date('Y', strtotime($dateEnd));//get date year
			$now = date('Y-m-d G:i:s');//current date and time
			$eventdate = date('Y-m-d G:i:s', strtotime($dateEnd));//user friendly date
			$dtEnd = new DateTime( $eventdate );
			$dtEnd->sub(new DateInterval('PT3H'));
		} else {
			$dateEnd = str_replace('T', '', $dateEnd);//remove T
			$d    = date('d', strtotime($dateEnd));//get date day
			$m    = date('m', strtotime($dateEnd));//get date month
			$y    = date('Y', strtotime($dateEnd));//get date year
			$now = date('Y-m-d G:i:s');//current date and time
			$eventdate = date('Y-m-d G:i:s', strtotime($dateEnd));//user friendly date
			$dtEnd = new DateTime( $eventdate );
		}

		if( isset($event->LOCATION) && trim($event->LOCATION) !== "" ){
			$content .= '<div style="padding:5px 2px;"><span style="font-weight:bold;">'.$this->functions->getLang("location").' :</b> ' . $event->LOCATION . '</div>';
		}
		$content .= '<div style="padding:5px 2px;;"><span style="font-weight:bold;">'.$this->functions->getLang("start time").' </b> : ' . date("d/m/Y - H:i:s", $dtStart->getTimestamp() ) . '</div>';
		$content .= '<div style="padding:5px 2px;;"><span style="font-weight:bold">'.$this->functions->getLang("end time").' </b> : ' . date("d/m/Y - H:i:s", $dtEnd->getTimestamp() ) . "</div>";
		$content .= "</div>";

		return $content;
	}

	function get_body_msg($msg_number, $msg_folder)
	{
		include_once("class.message_components.inc.php");
		$db = new db_functions();
		$msg = new message_components($this->mbox);
		$msg->fetch_structure($msg_number);
		$vCalImported = false;
		$return = array();
		$return['attachments'] = $this->download_attachment($msg,$msg_number);

		if( is_array($return['attachments']) && count($return['attachments']) > 0 ){
			foreach($return['attachments'] as $attached ){
				if( isset($attached['name']) ){
					if( preg_match( "/([^\s]+(\.(?i)(ics|vcard))$)/i", $attached['name'] ) ){
						$fileContent = base64_decode(imap_fetchbody($this->mbox, $msg_number, $attached['pid'], FT_UID));
						if( !$vCalImported ){
							$return['hash_vcalendar'] = $db->import_vcard( $fileContent, $msg_number );
							$vCalImported = true;
						}
					}
				}
			}
		}
		
		if(!$this->has_cid)
		{
			$return['thumbs']  = $this->get_thumbs($msg,$msg_number,$msg_folder);
			$return['signature'] = $this->get_signature($msg,$msg_number,$msg_folder);
		}

		if(!isset($msg->structure[$msg_number]->parts)) //Simple message, only 1 piece
		{
			$is_pkcs7 = preg_match( '/^(?:x-|)pkcs7-mime$/', strtolower( $msg->structure[$msg_number]->subtype ) );
			
			if( $is_pkcs7 && (count($return['signature']) == 0 ) ){
				$return['body']='isCripted';
				return $return;
			}
			
			$attachment = array(); //No attachments
			
			if ( $is_pkcs7 )
			{
				$return['body']='isSigned';
				$headers = imap_fetchheader($this->mbox, imap_msgno($this->mbox, $msg_number));
				$header_body_array = explode('MIME-Version: 1.0', $headers);
				$show_pkcs7 = $header_body_array[0] . 'MIME-Version: 1.0' . chr(0x0D) .chr(0x0A). $return['signature'][0];
				$return['source']=$show_pkcs7;
				array_shift($return['signature']);
				$return['signature'];
				//$return['signature'] = $this->get_signature($msg,$msg_number,$msg_folder);
				$this->close_mbox($this->mbox);
				return $return;
			}

			$content = ''; 
			$return['type'] = strtolower($msg->structure[$msg_number]->subtype);
			
			// If simple message is subtype 'html' or 'plain' or 'calendar', then get content body. 
			switch (strtolower($msg->structure[$msg_number]->subtype)) {
				case "calendar":
				case "html":
				case "plain":
					$content = $this->decodeBody(
						imap_body($this->mbox, $msg_number, FT_UID),
						$msg->encoding[$msg_number][0],
						$msg->charset[$msg_number][0]
					);
					break;
			}

			if( strtolower($msg->structure[$msg_number]->subtype) == 'calendar' )
			{
				if( !$vCalImported ){

					$vcalendar = $content;

					$content = $this->vCalImport( $content );
					
					$return['hash_vcalendar'] = $db->import_vcard( $vcalendar, $msg_number );
					
					$vCalImported = true;
				}
			}
		
		} else {

			//Complicated message, multiple parts
			$html_body = '';
			$content = '';
			$has_multipart = true;
			$is_alternative = false;
			$this->has_cid = false;
			$alternative_content = '';
			array_shift($return['signature']);
			if (strtolower($msg->structure[$msg_number]->subtype) == "related"){
				$this->has_cid = true;
			}

			$show_only_html = false;
			if (strtolower($msg->structure[$msg_number]->subtype) == "alternative") {
				$is_alternative = true;
				foreach($msg->pid[$msg_number] as $values => $msg_part) {
					$file_type = strtolower($msg->file_type[$msg_number][$values]);
					if($file_type == "text/html"){ 
						$show_only_html = true; 
					}
				}
			}

			foreach($msg->pid[$msg_number] as $values => $msg_part)
			{
				$file_type = strtolower($msg->file_type[$msg_number][$values]);

				if($file_type == "message/rfc822" || $file_type == "multipart/alternative") 
				{ 
					// Show only 'text/html' part, when message/rfc822 format contains 'text/plain' alternative part. 
					if(array_key_exists($values+1, $msg->file_type[$msg_number]) && 
						strtolower($msg->file_type[$msg_number][$values+1]) == 'text/plain' && 
						array_key_exists($values+2, $msg->file_type[$msg_number]) && 
						strtolower($msg->file_type[$msg_number][$values+2]) == 'text/html') { 
							$has_multipart = false; 
						} 
				} 	

				if( $file_type !== 'attachment')
				{
					$max_size = ($this->prefs['max_msg_size'] == "1") ? 1048576 : 102400;
					if($file_type == "text/plain" && !$show_only_html && $has_multipart)
					{
						// if TXT file size > 100kb, then it will not expand.
						if(!($file_type == "text/plain" && $msg->fsize[$msg_number][$values] > $max_size)) {
							$content .= htmlentities($this->decodeBody(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID), $msg->encoding[$msg_number][$values], $msg->charset[$msg_number][$values])); 
							$content = '<pre>' . $content . '</pre>';
						}
					}
					// if HTML attachment file size > 300kb, then it will not expand.
					else if($file_type == "text/html"  && $msg->fsize[$msg_number][$values] < $max_size * 3 )
					{
						if( $is_alternative ){
							$alternative_content .= $this->decodeBody(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID), $msg->encoding[$msg_number][$values], $msg->charset[$msg_number][$values]);
						} else {
							$content .= $this->decodeBody(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID), $msg->encoding[$msg_number][$values], $msg->charset[$msg_number][$values]);
							$show_only_html = true;
						}
					} 

					if( $file_type == "text/calendar" && $msg->fsize[$msg_number][$values] < $max_size * 3 ){ 

						if( !$vCalImported ){

							$vcalendar = $this->decodeBody(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID), $msg->encoding[$msg_number][$values], $msg->charset[$msg_number][$values]);
		
							$content = $this->vCalImport( $vcalendar );
							
							$return['hash_vcalendar'] = $db->import_vcard( $vcalendar, $msg_number );
							
							$vCalImported = true;
						}
					}

				} else if($file_type == "message/delivery-status" || $file_type == "message/feedback-report") { 
					
					$content .= "<hr align='left' width='95%' style='border:1px solid #DCDCDC'>";
					$content .= $this->decodeBody(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID), $msg->encoding[$msg_number][$values], $msg->charset[$msg_number][$values]); 
          			$content = '<pre>' . $content . '</pre>'; 
					
				} else if($file_type == "message/rfc822" || $file_type == "text/rfc822-headers") {

					include_once("class.imap_attachment.inc.php");
					$att = new imap_attachment();
					$attachments =  $att->get_attachment_info($this->mbox,$msg_number);
					if($attachments['number_attachments'] > 0) { 
						foreach($attachments ['attachment'] as $index => $attachment) 
						{ 
							if ( in_array( strtolower( $attachment[ 'type' ] ), array( 'delivery-status', 'rfc822', 'rfc822-headers', 'plain' ) ) ) 
							{ 
								$obj = imap_rfc822_parse_headers( imap_fetchbody( $this -> mbox, $msg_number, $msg_part, FT_UID ), $msg -> encoding[ $msg_number ][ $values ] ); 

								$content .= '<hr align="left" width="95%" style="border:1px solid #DCDCDC">'; 
								$content .= '<br><table  style="margin:2px;border:1px solid black;background:#EAEAEA">'; 

								$content .= '<tr><td><b>' . $this->functions->getLang("Subject") 
									. ':</b></td><td>' .$this->decode_string($obj->subject) . '</td></tr>'; 

								$content .= '<tr><td><b>' . $this -> functions -> getLang( 'From' ) . ':</b></td><td>' 
									. $this -> replace_links( $this -> decode_string( $obj -> from[ 0 ] -> mailbox . '@' . $obj -> from[ 0 ] -> host) ) 
									. '</td></tr>'; 

								$content .= '<tr><td><b>' . $this->functions->getLang("Date") . ':</b></td><td>' . $obj->date . '</td></tr>'; 

								$content .= '<tr><td><b>' . $this -> functions -> getLang( 'TO' ) . ':</b></td><td>' 
									. $this -> replace_links( $this -> decode_string( $obj -> to[ 0 ] -> mailbox . '@' . $obj -> to[ 0 ] -> host ) ) 
									. '</td></tr>'; 

								if ( isset( $obj->cc ) && $obj->cc ) {
									$content .= '<tr><td><b>' . $this -> functions -> getLang( 'CC' ) . ':</b></td><td>' 
										. $this -> replace_links( $this -> decode_string( $obj -> cc[ 0 ] -> mailbox . '@' . $obj -> cc[ 0 ] -> host ) ) 
										. '</td></tr>';
								}
										 
								$content .= '</table><br>'; 

								$id = ( ( strtolower( $attachment[ 'type' ] ) == 'delivery-status' ) ? false : true ); 
								$is_plain = isset( $msg->structure[$msg_number]->parts[1]->parts[0]->subtype ) &&
									strtolower( $msg->structure[$msg_number]->parts[1]->parts[0]->subtype ) == 'plain';
								if ( $is_plain )
								{ 
									$id = !$id; 
									if ( $msg->structure[$msg_number]->parts[1]->parts[0]->encoding == 4 ) 
										$msg->encoding[ $msg_number ][ $values ] = 'quoted-printable'; 
								} 

								$body = $this->decodeBody( 
									imap_fetchbody(
										$this->mbox, 
										$msg_number, 
										( $attachment['part_in_msg'] + ( ( int ) $id ) ) . ".1", 
										FT_UID 
									), 
									$msg->encoding[ $msg_number ][ $values ], 
									$msg->charset[ $msg_number ][ $values ] 
								); 

								if ( $is_plain )  { 
									$body = str_replace( array( '<', '>' ), array( ' #$<$# ', ' #$>$# ' ), $body ); 
									$body = htmlentities( $body ); 
									$body = $this -> replace_links( $body ); 
									$body = str_replace( array( ' #$&lt;$# ', ' #$&gt;$# ' ), array( '&lt;', '&gt;' ), $body ); 
									$body = '<pre>' . $body . '</pre>'; 
								}
								$content .= $body; 
								break; 
							}
						}
					}
				}
			}
			if ($is_alternative && !empty($alternative_content))
			{
				$content .= $alternative_content;
			}
			if($file_type == "text/plain" && ($show_only_html &&  $msg_part == 1) ||  (!$show_only_html &&  $msg_part == 3)){
				if(strtolower($msg->structure[$msg_number]->subtype) == "mixed" &&  $msg_part == 1)
					$content .= nl2br(imap_base64(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID)));
				else if(!strtolower($msg->structure[$msg_number]->subtype) == "mixed")
					$content .= nl2br(imap_fetchbody($this->mbox, $msg_number, $msg_part, FT_UID));
			}
			$return['type'] = strtolower($file_type);
		}
		// Force message with flag Seen (imap_fetchbody not works correctly)
		$params = array('folder' => $msg_folder, "msgs_to_set" => $msg_number, "flag" => "seen");
		$this->set_messages_flag($params);
		$content = $this->process_embedded_images($msg,$msg_number,$content, $msg_folder);
		$content = $this->replace_special_characters($content);

		$return['body'] = $this->_str_decode( $content );

		return $return;
	}

	function htmlfilter($body)
	{
		require_once('htmlfilter.inc');

		$tag_list = Array(
				false,
				'blink',
				'object',
				'meta',
				'html',
				'link',
				'frame',
				'iframe',
				'layer',
				'ilayer',
				'plaintext'
		);

		/**
		* A very exclusive set:
		*/
		// $tag_list = Array(true, "b", "a", "i", "img", "strong", "em", "p");
		$rm_tags_with_content = Array(
				'script',
				'style',
				'applet',
				'embed',
				'head',
				'frameset',
				'xml',
				'xmp'
		);

		$self_closing_tags =  Array(
				'img',
				'br',
				'hr',
				'input'
		);

		$force_tag_closing = true;

		$rm_attnames = Array(
    			'/.*/' =>
				Array(
					'/target/i',
					//'/^on.*/i', -> onClick, dos compromissos da agenda.
					'/^dynsrc/i',
					'/^datasrc/i',
					'/^data.*/i',
					'/^lowsrc/i'
				)
		);

		/**
		 * Yeah-yeah, so this looks horrible. Check out htmlfilter.inc for
		 * some idea of what's going on here. :)
		 */

		$bad_attvals = Array(
    		'/.*/' =>
	    	Array(
	    	      '/.*/' =>
		    	      Array(
	    	    	        Array(
            	    	          '/^([\'\"])\s*\S+\s*script\s*:*(.*)([\'\"])/si',
                		          //'/^([\'\"])\s*https*\s*:(.*)([\'\"])/si', -> doclinks notes
                        		  '/^([\'\"])\s*mocha\s*:*(.*)([\'\"])/si',
	                        	  '/^([\'\"])\s*about\s*:(.*)([\'\"])/si'
	    	                      ),
    	    	            Array(
        		   	              '\\1oddjob:\\2\\1',
                		          //'\\1uucp:\\2\\1', -> doclinks notes
                    		      '\\1amaretto:\\2\\1',
                        		  '\\1round:\\2\\1'
                          		)
		                    ),

		          '/^style/i' =>
    		              Array(
        		                Array(
            		                  '/expression/i',
                		              '/behaviou*r/i',
                    		          '/binding/i',
                        		      '/include-source/i',
                            		  '/url\s*\(\s*([\'\"]*)\s*https*:.*([\'\"]*)\s*\)/si',
		                              '/url\s*\(\s*([\'\"]*)\s*\S+\s*script:.*([\'\"]*)\s*\)/si'
    		                         ),
        		                Array(
            		                  'idiocy',
                		              'idiocy',
                    		          'idiocy',
                        		      'idiocy',
                            		  'url(\\1http://securityfocus.com/\\1)',
	                            	  'url(\\1http://securityfocus.com/\\1)'
	    	                         )
    	    	                )
        	    	  )
		    );

		$add_attr_to_tag = Array(
				'/^a$/i' => Array('target' => '"_new"')
		);


		$trusted_body = sanitize($body,
				$tag_list,
				$rm_tags_with_content,
				$self_closing_tags,
				$force_tag_closing,
				$rm_attnames,
				$bad_attvals,
				$add_attr_to_tag
		);

	    return $trusted_body;
	}

	function decodeBody($body, $encoding, $charset = null)
	{
		$body = ( $encoding === 'quoted-printable' )? quoted_printable_decode( $body ) : (
				( $encoding === 'base64' )? base64_decode( $body ): $body );
		
		return $this->_toUTF8( $body, $charset );
	}

	function process_embedded_images($msg, $msgno, $body, $msg_folder)
	{
		if (count($msg->inline_id[$msgno]) > 0)
		{
			foreach ($msg->inline_id[$msgno] as $index => $cid)
			{
				$cid = preg_replace("/</i", "", $cid);
				$cid = preg_replace("/>/i", "", $cid);
				$msg_part = $msg->pid[$msgno][$index];
				//$body = preg_replace("/alt=\"\"/i", "", $body);
				$body = preg_replace("/<br\/>/i", "", $body);
				$body = str_replace("src=\"cid:".$cid."\"", " src=\"./inc/show_img.php?msg_folder=$msg_folder&msg_num=$msgno&msg_part=$msg_part\" ", $body);
				$body = str_replace("src='cid:".$cid."'", " src=\"./inc/show_img.php?msg_folder=$msg_folder&msg_num=$msgno&msg_part=$msg_part\" ", $body);
				$body = str_replace("src=cid:".$cid, " src=\"./inc/show_img.php?msg_folder=$msg_folder&msg_num=$msgno&msg_part=$msg_part\" ", $body);
			}
		}

		return $body;
	}

	function replace_special_characters($body)
	{
		// Suspected TAGS!
		/*$tag_list = Array(
			'blink','object','meta',
			'html','link','frame',
			'iframe','layer','ilayer',
			'plaintext','script','style','img',
			'applet','embed','head',
			'frameset','xml','xmp');
		*/

		// Layout problem: Change html elements
		// with absolute position to relate position, CASE INSENSITIVE.
                $body = str_replace("\x00", '', $body);
                $body = @mb_eregi_replace("POSITION: ABSOLUTE;","",$body);

		$tag_list = Array('head','blink','object','frame',
			'iframe','layer','ilayer','plaintext','script','base',
			'applet','embed','frameset','xml','xmp','style');

		$blocked_tags = array();
		foreach($tag_list as $index => $tag) {
			$new_body = @mb_eregi_replace("<$tag", "<!--$tag", $body);
			if($body != $new_body) {
				$blocked_tags[] = $tag;
			}
			$body = @mb_eregi_replace("</$tag>", "</$tag-->", $new_body);
		}
		// Malicious Code Remove
		$dirtyCodePattern = "/(<([\w]+[\w0-9]*)(.*)on(mouse(move|over|down|up)|load|blur|change|error|click|dblclick|focus|key(down|up|press)|select)([\n\ ]*)=([\n\ ]*)[\"'][^>\"']*[\"']([^>]*)>)(.*)(<\/\\2>)?/misU";
		preg_match_all($dirtyCodePattern,$body,$rest,PREG_PATTERN_ORDER);
		foreach($rest[0] as $i => $val)
			if (!(preg_match("/javascript:window\.open\(\"([^'\"]*)\/index\.php\?menuaction=calendar\.uicalendar\.set_action\&cal_id=([^;'\"]+);?['\"]/i",$rest[1][$i]) && strtoupper($rest[4][$i]) == "CLICK" )) //Calendar events
				$body = str_replace($rest[1][$i],"<".$rest[2][$i].$rest[3][$i].$rest[7][$i].">",$body);

		$body = $this-> replace_links($body);

		//Remocao de tags <span></span> para correcao de erro no firefox 
		$body = mb_eregi_replace("<span><span>","",$body); 
		$body = mb_eregi_replace("</span></span>","",$body); 
		//Correcao para compatibilizacao com Outlook, ao visualizar a mensagem 
		$body = mb_ereg_replace('<!--\[','<!-- [',$body); 
		$body = mb_ereg_replace('&lt;!\[endif\]--&gt;', '<![endif]-->', $body);
		$body = str_replace("\x00", '', $body);
		
		return $body;
	}

	function replace_links( $body )
	{
  		// Domains and IPs addresses found in the text and which is not a link yet should be replaced by one. 
		// See more informations in www.iana.org 
		$octets = array( 
			'first' => '(2[0-3][0-9]|1[0-9]{2}|[1-9][0-9]?)', 
			'middle' => '(25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})', 
			'last' => '(25[0-4]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)' 
		); 

		$ip = "\b{$octets[ 'first' ]}\.({$octets[ 'middle' ]}\.){2}{$octets[ 'last' ]}\b"; 

		$top_level_domains = '(\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|as|asia|at|au|aw|ax|az|' 
			. 'ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bl|bm|bn|bo|br|bs|bt|bv|bw|by|bz|' 
			. 'ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cx|cy|cz|' 
			. 'de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|' 
			. 'ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|' 
			. 'hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|' 
			. 'ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|' 
			. 'ma|mc|md|me|mf|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|' 
			. 'mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|' 
			. 'pa|pe|pf|pg|ph|pk|pl|pm|pn|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|' 
			. 'sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|' 
			. 'tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|' 
			. 'ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw))+\b'; 

		$path = '(?>\/[\w\d\/\.\'\(\)\-\+~?!&#@$%|:;,*=_]+)?'; 
		$port = '(?>:\d{2,5})?'; 
		$domain = '(?>[\w\d_\-]+)'; 
		$subdomain = "(?>{$domain}\.)*"; 
		$protocol = '(?>(http|ftp)(s)?:\/\/)?'; 
		$url = "(?>{$protocol}((?>{$subdomain}{$domain}{$top_level_domains}|{$ip}){$port}{$path}))"; 

		$pattern = "/(<\w[^>]+|[\/\"'@=])?{$url}/";
		$limit = strlen($body).strlen($body);
		ini_set( 'pcre.backtrack_limit', $limit ); 
		

		/* 
		// PHP 5.3 
		$replace = function( $matches ) 
		{ 
			if ( $matches[ 1 ] ) 
				return $matches[ 0 ]; 

			$url = ( $matches[ 2 ] ) ? $matches[ 2 ] : 'http'; 
			$url .= "{$matches[ 3 ]}://{$matches[ 4 ]}"; 
			return "<a href=\"{$url}\" target=\"_blank\">{$matches[ 4 ]}</a>"; 
		}; 
		$body = preg_replace_callback( $pattern, $replace, $body ); 
		 */ 

		// PHP 5.2.x - Remover assim que possivel 
		$body = preg_replace_callback( $pattern, 
			create_function( 
				'$matches', 
				'if ( $matches[ 1 ] ) return $matches[ 0 ];' 
				. '$url = ( $matches[ 2 ] ) ? $matches[ 2 ] : "http";'
				. '$url .= "{$matches[ 3 ]}://{$matches[ 4 ]}";'
				. 'return "<a href=\"{$url}\" target=\"_blank\">{$matches[ 4 ]}</a>";'
			), $body
		);
		ini_set( 'pcre.backtrack_limit', 100000 ); 
		// E-mail address in the text should create a new e-mail on ExpressoMail
		$pattern = '/( |<|&lt;|>)([A-Za-z0-9\.~?\/_=#\-]*@[A-Za-z0-9\.~?\/_=#\-]*)( |>|&gt;|<)/im'; 
		$replacement = '$1<a href="mailto:$2">$2</a>$3';
		$body = preg_replace( $pattern, $replacement, $body );

		return $body;
	}

	function get_signature($msg, $msg_number, $msg_folder)
	{
            include_once(dirname( __FILE__ ) ."/../../security/classes/CertificadoB.php");
            include_once("class.db_functions.inc.php");
            foreach ($msg->file_type[$msg_number] as $index => $file_type)
	    {
                $sign = array();
                $temp = $this->get_info_head_msg($msg_number);
                if($temp['ContentType'] =='normal') return $sign;
                $file_type = strtolower($file_type);
                if(strtolower($msg->encoding[$msg_number][$index]) == 'base64')
		{
                    if ($temp['ContentType'] == 'signature')
                    {
                        if(!$this->mbox || !is_resource($this->mbox))
                        $this->mbox = $this->open_mbox($msg_folder);

                        $header = @imap_headerinfo($this->mbox, imap_msgno($this->mbox, $msg_number), 80, 255);
                        $header = $this->array_map_recursive( array( $this, '_str_decode' ), $header );

                        $imap_msg	 	= @imap_fetchheader($this->mbox, $msg_number, FT_UID);
                        $imap_msg		.= @imap_body($this->mbox, $msg_number, FT_UID);

                        $certificado = new certificadoB();
                        $validade = $certificado->verificar($imap_msg);
                                        $sign[] = $certificado->msg_sem_assinatura;
                        if ($certificado->apresentado)
			{
                            $from = $header->from;
                            foreach ($from as $id => $object)
                            {
				$fromname = $object->personal;
				$fromaddress = $object->mailbox . "@" . $object->host;
                            }
                            foreach ($certificado->erros_ssl as $item)
                            {
                                $sign[] = $item . "#@#";
                            }

                            if (count($certificado->erros_ssl) < 1)
                            {
                                $check_msg = 'Message untouched';
                                if(strtoupper($fromaddress) == strtoupper($certificado->dados['EMAIL']))
                                {
                                    $check_msg .= ' and authentic###';
                                }
                                else
                                {
                                    $check_msg .= ' with signer different from sender#@#';
                                }
                                $sign[] = $check_msg;
                            }
                                                
                            $sign[] = 'Message signed by: ###' . $certificado->dados['NOME'];
                            $sign[] = 'Certificate email: ###' . $certificado->dados['EMAIL'];
                            $sign[] = 'Mail from: ###' . $fromaddress;
                            $sign[] = 'Certificate Authority: ###' . $certificado->dados['EMISSOR'];
                            $sign[] = 'Validity of certificate: ###' . gmdate('r',openssl_to_timestamp($certificado->dados['FIM_VALIDADE']));
                            $sign[] = 'Message date: ###' . $header->Date;

                            $cert = openssl_x509_parse($certificado->cert_assinante);

                            $sign_alert = array();
                            $sign_alert[] = 'Certificate Owner###:\n';
                            $sign_alert[] = 'Common Name (CN)###  ' . $cert[subject]['CN'] .  '\n';
                            $X = substr($certificado->dados['NASCIMENTO'] ,0,2) . '-' . substr($certificado->dados['NASCIMENTO'] ,2,2) . '-'  . substr($certificado->dados['NASCIMENTO'] ,4,4);
                            $sign_alert[]= 'Organization (O)###  ' . $cert[subject]['O'] .  '\n';
                            $sign_alert[]= 'Organizational Unit (OU)### ' . $cert[subject]['OU'][0] .  '\n';
                            //$sign_alert[] = 'Serial Number### ' . $cert['serialNumber'] . '\n';
                            $sign_alert[] = 'Personal Data###:' . '\n';
                            $sign_alert[] = 'Birthday### ' . $X .  '\n';
                            $sign_alert[]= 'Fiscal Id### ' . $certificado->dados['CPF'] .  '\n';
                            $sign_alert[]= 'Identification### ' . $certificado->dados['RG'] .  '\n\n';
                            $sign_alert[]= 'Certificate Issuer###:\n';
                            $sign_alert[]= 'Common Name (CN)###  ' . $cert[issuer]['CN'] . '\n';
                            $sign_alert[]= 'Organization (O)###  ' . $cert[issuer]['O'] .  '\n';
                            $sign_alert[]= 'Organizational Unit (OU)### ' . $cert[issuer]['OU'][0] .  '\n\n';
                            $sign_alert[]= 'Validity###:\n';
                            $H = data_hora($cert[validFrom]);
                            $X = substr($H,6,2) . '-' . substr($H,4,2) . '-'  . substr($H,0,4);
                            $sign_alert[]= 'Valid From### ' . $X .  '\n';
                            $H = data_hora($cert[validTo]);
                            $X = substr($H,6,2) . '-' . substr($H,4,2) . '-'  . substr($H,0,4);
                            $sign_alert[]= 'Valid Until### ' . $X;
                            $sign[] = $sign_alert;

                            $this->db = new db_functions();
                            
                            // TODO: testar se existe um certificado no banco e verificar qual e o mais atual.
                            if(!$certificado->dados['EXPIRADO'] && !$certificado->dados['REVOGADO'] && count($certificado->erros_ssl) < 1)
                                $this->db->insert_certificate(strtolower($certificado->dados['EMAIL']), $certificado->cert_assinante, $certificado->dados['SERIALNUMBER'], $certificado->dados['AUTHORITYKEYIDENTIFIER']);
			}
                        else
                        {
                            $sign[] = "<span style=color:red>" . $this->functions->getLang('Invalid signature') . "</span>";
                            foreach($certificado->erros_ssl as $item)
                                $sign[] = "<span style=color:red>" . $this->functions->getLang($item) . "</span>";
                        }
                    }
		}
            }
            return $sign;
	}

	function get_thumbs($msg, $msg_number, $msg_folder)
	{
		$url_msg_folder = urlencode($msg_folder);
		$thumbs_array = array();
		$i = 0;
    	foreach ($msg->file_type[$msg_number] as $index => $file_type)
    	{
    		$file_type = strtolower($file_type);
    		if(strtolower($msg->encoding[$msg_number][$index]) == 'base64') {
	    		if (($file_type == 'image/jpeg') || ($file_type == 'image/pjpeg') || ($file_type == 'image/gif') || ($file_type == 'image/png')) {
	    			$img = "<IMG id='".$url_msg_folder.";;".$msg_number.";;".$i.";;".$msg->pid[$msg_number][$index].";;".$msg->encoding[$msg_number][$index]."' style='border:2px solid #fde7bc;padding:5px' title='".$this->functions->getLang("Click here do view (+)")."'src=./inc/show_img.php?msg_num=".$msg_number."&msg_folder=".$msg_folder."&msg_part=".$msg->pid[$msg_number][$index]."&thumb=true&file_type=".$file_type.">";
	    			$href = "<a onMouseDown=\"save_image(event,this,'".$msg_folder."','".$msg_number."','".$msg->pid[$msg_number][$index]."')\" href='#".$url_msg_folder.";;".$msg_number.";;".$i.";;".$msg->pid[$msg_number][$index].";;".$msg->encoding[$msg_number][$index]."' onClick=\"window.open('./inc/show_img.php?msg_num=".$msg_number."&msg_folder=".$msg_folder."&msg_part=".$msg->pid[$msg_number][$index]."','mywindow','width=700,height=600,scrollbars=yes');\">". $img ."</a>";
					$thumbs_array[] = $href;
	    		}
    			$i++;
    		}
    	}
    	return $thumbs_array;
	}

	function delete_msgs($params)
	{
		$mailbox = $this->_toMaibox( $params['folder'] );
		$msgs_number = explode(",",$params['msgs_number']);
		$border_ID = (isset($params['border_ID']) ? $params['border_ID'] : false );
		$return = array();

		if( isset($params['get_previous_msg'])){
			$return['previous_msg'] = $this->get_info_previous_msg($params);
			// Fix problem in unserialize function JS.
			if(isset($return['previous_msg']['body'])){
				$return['previous_msg']['body'] = str_replace(array('{','}'), array('&#123;','&#125;'), $return['previous_msg']['body']);
			}
		}

		$mbox_stream = @imap_open("{".$this->imap_server.":".$this->imap_port.$this->imap_options."}".$mailbox, $this->username, $this->password) or die(serialize(array('imap_error' => $this->parse_error(imap_last_error()))));

		foreach ($msgs_number as $msg_number)
		{
			imap_delete($mbox_stream, $msg_number, FT_UID );
			
			$return['msgs_number'][] = $msg_number;
		}
		
		$return['error'] = imap_last_error();
		
		$return['error'] = ( preg_match( '/SECURITY PROBLEM: insecure server advertised AUTH=PLAIN/i', $return['error'] ) ? '' : $return['error'] );

		if( $mbox_stream ){ $this->close_mbox($mbox_stream, CL_EXPUNGE); }
		
		$return['folder'] = $params['folder'];
		
		$return['border_ID'] = $border_ID;

		return $return;
	}

	function get_num_recent( $cur_folder, $rw = true )
	{
		$result = array( 'sum' => 0, 'info' => array() );
		$alert_pref = (int)$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['alert_new_msg'];
		if ( $alert_pref === 0 || ( $socket = fsockopen( $this->imap_server, $this->imap_port ) ) === false ) return $result;
		fgets( $socket );
		fputs( $socket, 'c0 AUTHENTICATE PLAIN '.base64_encode( $this->username.chr(0).$this->username.chr(0).$this->password ).PHP_EOL );
		fgets( $socket );
		$imapDelimiter = $this->imap_delimiter;
		$rem_array = function( $arr, $folder ) use( $imapDelimiter ) {
			if ( !isset( $_SESSION['phpgw_info']['expressomail']['email_server'][$folder] ) ) return $arr;
			return array_diff( $arr, array( 'INBOX'.$imapDelimiter.$_SESSION['phpgw_info']['expressomail']['email_server'][$folder] ) );
		};
		$folders = array();
		switch ( $alert_pref ) {
			case 1: $folders[] = imap_utf7_encode( $cur_folder ); break;
			case 2: $folders[] = 'INBOX'; break;
			case 3:
				fputs( $socket, 'c1 LIST INBOX *'.PHP_EOL );
				while ( ( $line = fgets( $socket ) ) && ord( $line[0] ) === 42 )
					$folders[] = rtrim( array_pop( explode( ' ', $line, 5 ) ) );
				$folders = $rem_array( $folders, 'imapDefaultTrashFolder' );
				$folders = $rem_array( $folders, 'imapDefaultSpam' );
				break;
		}
		foreach ( $folders as $folder ) {
			fputs( $socket, 'c2 '.( $rw? 'SELECT' : 'EXAMINE' ).' '.$folder.PHP_EOL );
			while ( ( $line = fgets( $socket ) ) && ord( $line[0] ) === 42 ) {
				if ( ( $rec = explode( ' ', rtrim( $line ), 3 ) ) && $rec[2] === 'RECENT' && ((int)$rec[1]) > 0 ) {
					$result['sum'] += $rec[1];
					$result['info'][$folder] = $rec[1];
				}
			}
		}
		fputs( $socket, 'c3 LOGOUT'.PHP_EOL);
		fgets( $socket );
		return $result;
	}

	function refresh($params)
	{
		$result             = array();
		$folder             = $params['folder'];
		$msg_range_begin    = $params['msg_range_begin'];
		$msg_range_end      = $params['msg_range_end'];
		$msgs_existent      = $params['msgs_existent'];
		$sort_box_type      = $params['sort_box_type'];
		$sort_box_reverse   = $params['sort_box_reverse'];
		$msgs_in_the_server = array();
		
		$result['new_msgs'] = $this->get_num_recent( $folder );
		$search_box_type    = $params['search_box_type'] != "ALL" && $params['search_box_type'] != "" ? $params['search_box_type'] : false;
		$msgs_in_the_server = $this->get_msgs($folder, $sort_box_type, $search_box_type, $sort_box_reverse,$msg_range_begin,$msg_range_end);
		$msgs_in_the_server = array_keys($msgs_in_the_server);
		if ( !count($msgs_in_the_server)) return $result;

		$msgs_in_the_client = explode( ",", $msgs_existent );
		$msg_to_insert      = array_diff($msgs_in_the_server, $msgs_in_the_client);
		$msg_to_delete      = array_diff($msgs_in_the_client, $msgs_in_the_server);

		$msgs_to_exec = array();
		foreach($msg_to_insert as $msg_number)
			$msgs_to_exec[] = $msg_number;
		sort($msgs_to_exec);

		$i = 0;
		foreach($msgs_to_exec as $msg_number)
		{
			/*A funcao imap_headerinfo nao traz o cabecalho completo, e sim alguns
			* atributos do cabecalho. Como eu preciso do atributo Importance
			* para saber se o email e importante ou nao, uso abaixo a funcao
			* imap_fetchheader e busco o atributo importance nela para passar
			* para as funcoes ajax. Isso faz com que eu acesse o cabecalho
			* duas vezes e de duas formas diferentes, mas em contrapartida, eu
			* nao preciso reimplementar o metodo utilizando o fetchheader.
			*/
    
			$tempHeader = @imap_fetchheader($this->mbox, imap_msgno($this->mbox, $msg_number));
			$flag = preg_match('/importance *: *(.*)\r/i', $tempHeader, $importance);
			$result[$i]['Importance'] = $flag==0?"Normal":$importance[1];

			$msg_sample = $this->get_msg_sample( $msg_number, $folder );
			$result[$i]['msg_sample'] = $msg_sample;

			$header = $this->get_header($msg_number);
			if (!is_object($header))
				continue;

			$result[$i]['msg_number'] = $msg_number;
			
			//get the next msg number to append this msg in the view in a correct place
			$msg_key_position = array_search($msg_number, $msgs_in_the_server);
			
			if($msg_key_position !== false && array_key_exists($msg_key_position + 1, $msgs_in_the_server) !== false)
				$result[$i]['next_msg_number'] = $msgs_in_the_server[$msg_key_position + 1];

			$result[$i]['msg_folder']	= $folder;
			// Atribui o tipo (normal, signature ou cipher) ao campo Content-Type
			$result[$i]['ContentType']  = $this->getMessageType($msg_number, $tempHeader);
			$result[$i]['Recent']		= $header->Recent;
			$result[$i]['Unseen']		= $header->Unseen;
			$result[$i]['Answered']		= $header->Answered;
			$result[$i]['Deleted']		= $header->Deleted;
			$result[$i]['Draft']		= $header->Draft;
			$result[$i]['Flagged']		= $header->Flagged;

			$result[$i]['udate'] = $header->udate;
		
			$from = $header->from;
			$result[$i]['from'] = array();
			$tmp = imap_mime_header_decode($from[0]->personal);
			$result[$i]['from']['name'] = $tmp[0]->text;
			$result[$i]['from']['email'] = $from[0]->mailbox . "@" . $from[0]->host;
			if(!$result[$i]['from']['name']){
				$result[$i]['from']['name'] = $result[$i]['from']['email'];
			}

			$to = ( isset($header->to) ? $header->to : false );
			$result[$i]['to'] = array();
			$tmp = ( isset($to[0]->personal) ? imap_mime_header_decode($to[0]->personal) : false );
			$result[$i]['to']['name'] = ( isset($tmp[0]->text) ? $tmp[0]->text : false );
			$result[$i]['to']['email'] = ( isset($to[0]->mailbox) && isset($to[0]->host) ? $to[0]->mailbox . "@" . $to[0]->host : false );
			$result[$i]['to']['full'] = '"' . $result[$i]['to']['name'] . '" ' . '<' . $result[$i]['to']['email'] . '>';
			$cc = ( isset($header->cc) ? $header->cc : false );
			if ( ($cc) && (!$result[$i]['to']['name']) ){
				$result[$i]['to']['name'] =  $cc[0]->personal;
				$result[$i]['to']['email'] = $cc[0]->mailbox . "@" . $cc[0]->host;
			}
			
			$result[$i]['subject'] = ( isset( $header->fetchsubject ) ) ? trim($this->decode_string($header->fetchsubject)) : '';
			$result[$i]['subject'] = ( strstr( $result[$i]['subject'], ' ' ) === false )? str_replace( '_', ' ', $result[$i]['subject'] ) : $result[$i]['subject'];					

			$result[$i]['Size'] = $header->Size;
			$result[$i]['reply_toaddress'] = $this->decode_string($header->reply_toaddress);

			$result[$i]['attachment'] = array();
			if (!isset($imap_attachment))
			{
				include_once("class.imap_attachment.inc.php");
				$imap_attachment = new imap_attachment();
			}
			$result[$i]['attachment'] = $imap_attachment->get_attachment_headerinfo($this->mbox, $msg_number);
			$i++;
		}
		$result['quota'] = $this->get_quota(array('folder_id' => $folder));
		$result['sort_box_type'] = $params['sort_box_type'];
		if( !$this->mbox || !is_resource($this->mbox) ){
			$this->open_mbox($folder);
		}

		$result['msgs_to_delete'] = $msg_to_delete;
		$result['offsetToGMT'] = $this->functions->CalculateDateOffset();
		
		if($this->mbox && is_resource($this->mbox))
			$this->close_mbox($this->mbox);

		return $result;
	}

	/**
	 * Metodo que faz a verificacao do Content-Type do e-mail e verifica se e um e-mail normal,
	 * assinado ou cifrado.
	 * @author Mario Cesar Kolling <mario.kolling@serpro.gov.br>
	 * @param $headers Uma String contendo os Headers do e-mail retornados pela funcao imap_imap_fetchheader
	 * @param $msg_number O numero da mesagem
	 * @return Retorna o tipo da mensagem (normal, signature, cipher).
	 */
	function getMessageType( $msg_number, $headers = false )
	{
		include_once( dirname( __FILE__ ).'/../../security/classes/CertificadoB.php' );
		
		if ( !$headers ) $headers = imap_fetchheader( $this->mbox, $msg_number, FT_UID );
		
		if ( preg_match( '/pkcs7-signature/i', $headers ) == 1 )
			return 'signature';
		
		if ( preg_match( '/pkcs7-mime/i', $headers ) == 1 )
			return testa_p7m( imap_body( $this->mbox, $msg_number, FT_UID | FT_PEEK ) );
		
		return 'normal';
	}
	
	 /**
     * Metodo que retorna todas as pastas do usuario logado.
     * @param $params array opcional para repassar os argumentos ao metodo.
     * Se usar $params['noSharedFolders'] = true, ira retornar todas as pastas do usuario logado,
     * excluindo as compartilhadas para ele.
     * Se usar $params['folderType'] = "default" ira retornar somente as pastas defaults
     * Se usar $params['folderType'] = "personal" ira retornar somente as pastas pessoais
     * Se usar $params['folderType'] = null ira retornar todas as pastas
     * @return Retorna um array contendo as seguintes informacoes de cada pasta: folder_unseen,
     * folder_id, folder_name, folder_parent e folder_hasChildren.
     */
	function get_folders_list($params = null)
	{
		// Verify migrate MB
		$db = new db_functions();

		$migrateMB = $db->getMigrateMailBox();

		if ($migrateMB) {
			return array("migrate_execution" => "true", "migrate_status" => $migrateMB['status'], "migrate_queue" => $migrateMB['queue']);
		}

		$mbox_stream = $this->open_mbox();

		if (isset($params['onload']) && $params['onload']) {
			if (isset($_SESSION['phpgw_info']['expressomail']['server']['certificado'])) {
				if ($_SESSION['phpgw_info']['expressomail']['server']['certificado'] == 1) {

					$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
					$mailbox = $this->_toMaibox( "INBOX" . $this->imap_delimiter . "decifradas" );
					imap_deletemailbox($mbox_stream, "{".$imap_server."}$mailbox");
				}
			}
		}

		$inbox = 'INBOX';
		$trash = $inbox . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'];
		$drafts = $inbox . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'];
		$spam = $inbox . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'];
		$sent = $inbox . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'];
		$uid2cn = isset($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['uid2cn']) ?
		$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['uid2cn'] : false;

		// Free others requests
		session_write_close();

		$serverString = "{" . $this->imap_server . ":" . $this->imap_port . $this->imap_options . "}";

		if ($params && isset($params['noSharedFolders']) && $params['noSharedFolders']) {
			$folders_list = imap_getmailboxes($mbox_stream, $serverString, 'INBOX*');
		} else {
			$folders_list = imap_getmailboxes($mbox_stream, $serverString, '*');
		}

		$tmp = array();
		$resultMine = array();
		$resultDefault = array();

		if (is_array($folders_list)) {
			$folders_list = array_slice($folders_list, 0, $this->foldersLimit);

			reset($folders_list);

			$this->ldap = new ldap_functions();

			$i = 0;

			$ignoreFolder = '}DELETED' . $this->imap_delimiter;
			$ignoreFolder .= 'user' . $this->imap_delimiter;
			$ignoreFolder .= $this->username . $this->imap_delimiter;
			$ignoreFolder = preg_quote($ignoreFolder, '/');

			$folderParents = array();

			foreach ($folders_list as $key => $folder) {
				
				if (preg_match('/' . $ignoreFolder . '/', $folder->name)) { continue; }

				$status = imap_status($mbox_stream, $folder->name, SA_UNSEEN);

				$tmp_folder_id = explode("}", $folder->name);
				$tmp_folder_id[1] = $this->_toUTF8( $tmp_folder_id[1], 'UTF7-IMAP' );

				if ($tmp_folder_id[1] == 'INBOX' . $this->imap_delimiter . 'decifradas') { continue; }

				$result[$i]['folder_unseen'] = (isset($status->unseen) ? $status->unseen : false);

				$folder_id = $tmp_folder_id[1];

				$result[$i]['folder_id'] = $folder_id;

				$tmp_folder_parent = explode($this->imap_delimiter, $folder_id);

				$result[$i]['folder_name'] = array_pop($tmp_folder_parent);

				$result[$i]['folder_name'] = $result[$i]['folder_name'] == 'INBOX' ? 'Inbox' : $result[$i]['folder_name'];

				// SharedBox
				$sharedBox = strtolower(substr($folder_id, 0, 4));

				if ($uid2cn && $sharedBox === "user") {
					$sharedBoxName = $folder_id;

					if (substr_count($sharedBoxName, $this->imap_delimiter) == 1) {
						if ($cn = $this->ldap->uid2cn($result[$i]['folder_name'])) {
							$result[$i]['folder_name'] = $cn;
						}
					}
				}

				$tmp_folder_parent = implode($this->imap_delimiter, $tmp_folder_parent);

				$result[$i]['folder_parent'] = $tmp_folder_parent == 'INBOX' ? '' : $tmp_folder_parent;

				if (intval($folder->attributes) == 32 && strtoupper($result[$i]['folder_name']) != 'INBOX') {
					$result[$i]['folder_hasChildren'] = 1;
					$folderParents[] = $result[$i]['folder_id'];
				} else {
					$result[$i]['folder_hasChildren'] = 0;
				}

				switch ($tmp_folder_id[1]) {
					case $inbox:
					case $sent:
					case $drafts:
					case $spam:
					case $trash:
						$resultDefault[] = $result[$i];
						break;
					default:
						$resultMine[] = $result[$i];
				}

				$i++;
			}
		}

		if ($params && !(isset($params['noQuotaInfo']) && $params['noQuotaInfo'])) {
			//Get quota info of current folder
			$arr_quota_info = $this->get_quota(array(
				'folder_id' => (isset($params['folder']) && $params['folder']) ? $params['folder'] : 'INBOX',
			));
		} else {
			$arr_quota_info = array();
		}

		// Sorting resultMine
		$array_tmp = array();
		foreach ($resultMine as $folder_info) {
			$array_tmp[] = $folder_info['folder_id'];
		}

		natcasesort($array_tmp);

		$result2 = array();

		foreach ($array_tmp as $key => $folder_id) {
			$result2[] = $resultMine[$key];
		}

		// Sorting resultDefault
		$resultDefault2 = array();
		foreach ($resultDefault as $key => $folder_id) {
			switch ($resultDefault[$key]['folder_id']) {
				case $inbox:
					$resultDefault2[0] = $resultDefault[$key];
					break;

				case $sent:
					$resultDefault2[1] = $resultDefault[$key];
					break;

				case $drafts:
					$resultDefault2[2] = $resultDefault[$key];
					break;

				case $spam:
					$resultDefault2[3] = $resultDefault[$key];
					break;

				case $trash:
					$resultDefault2[4] = $resultDefault[$key];
					break;
			}
		}

		if ($params && isset($params['folderType']) && $params['folderType'] && $params['folderType'] == 'default') {
			return array_merge($resultDefault2, $arr_quota_info);
		}

		if ($params && isset($params['folderType']) && $params['folderType'] && $params['folderType'] == 'personal') {
			return array_merge($result2, $arr_quota_info);
		}

		// Merge default folders and personal
		$result2 = array_merge($resultDefault2, $result2);

		foreach ($result2 as $key => $mailbox) {
			if (trim($mailbox['folder_parent']) !== "") {
				if (array_search($mailbox['folder_parent'], $folderParents) === false) {
					if (preg_match('/^user/i', $mailbox['folder_parent'])) {
						$result2[$key]['folder_parent'] = 'user';
					} else {
						$result2[$key]['folder_parent'] = '';
					}
				}
			}
		}
		return array_merge($result2, $arr_quota_info);
	}


	function create_mailbox($arr)
	{
		$mailbox     = $this->_toMaibox( $arr['newp'] );
		$mbox_stream = $this->open_mbox();
		$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];

		if( preg_match("/^user\/.*?\//i", $mailbox, $matches)) {
			$userSharedFolder = $matches[0];
			$userSharedFolder = trim($userSharedFolder);
			$userSharedFolder = substr($userSharedFolder, 0, strlen($userSharedFolder) - 1);
			$foldersDefault = array(
				$userSharedFolder,
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']
			);
			
			if (array_search($mailbox, $foldersDefault)) {
				return array("status" => false, "error" => "You don't have permission for this operation!");
			}
		}

		$result['status'] = true;

		if(!imap_createmailbox($mbox_stream,"{".$imap_server."}$mailbox")){
			$result = array(
				"status" => false,
				"error" => implode("<br />\n", imap_errors())
			);
		}

		if($mbox_stream){ $this->close_mbox($mbox_stream); }

		return $result;
	}

	function create_extra_mailbox($arr)
	{
		$nameboxs = explode(";",$arr['nw_folders']);
		$result = "";
		$mbox_stream = $this->open_mbox();
		$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
		foreach($nameboxs as $key=>$tmp){
			if($tmp != ""){
				$to_create_array = explode($this->imap_delimiter, $tmp);
				array_pop($to_create_array);
				$folder = array();
				foreach($to_create_array as $k=>$to_create){
					$folder[] = $to_create;
					if($to_create != 'INBOX') {
						$tmp = implode($this->imap_delimiter, $folder);
						if(!imap_createmailbox($mbox_stream,imap_utf7_encode("{".$imap_server."}$tmp"))){
							$result = implode("<br />\n", imap_errors());
							if("Mailbox already exists" != $result) {
								$this->close_mbox($mbox_stream);
								return $result;
							}
						}
					}
				}
			}
		}
		if($mbox_stream)
			$this->close_mbox($mbox_stream);
		return true;
	}

	function delete_mailbox($arr)
	{
		$mailbox     = $this->_toMaibox( $arr['del_past'] );
		$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
		$mbox_stream = $this->mbox ? $this->mbox : $this->open_mbox();

		if( preg_match("/^user\/.*?\//i", $mailbox, $matches)) {
			$userSharedFolder = $matches[0];
			$userSharedFolder = trim($userSharedFolder);
			$userSharedFolder = substr($userSharedFolder, 0, strlen($userSharedFolder) - 1);
			$foldersDefault = array(
				$userSharedFolder,
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']
			);
			
			if (array_search($mailbox, $foldersDefault)) {
				return array("status" => false, "error" => "You don't have permission for this operation!");
			}
		}
		
		$result['status'] = true;

		if (!imap_deletemailbox($mbox_stream,"{".$imap_server."}$mailbox")) {
			$result = array(
				"status" => false,
				"error" => imap_last_error()
			);
		}

		if($mbox_stream){ $this->close_mbox($mbox_stream); }

		return $result;
	}

	function ren_mailbox($arr)
	{
		$mailbox_old = $this->_toMaibox( $arr['current'] );
		$mailbox_new = $this->_toMaibox( $arr['rename'] );
		$imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
		$mbox_stream = $this->open_mbox();

		if( preg_match("/^user\/.*?\//i", $mailbox_old, $matches)) {
			$userSharedFolder = $matches[0];
			$userSharedFolder = trim($userSharedFolder);
			$userSharedFolder = substr($userSharedFolder, 0, strlen($userSharedFolder) - 1);
			$foldersDefault = array(
				$userSharedFolder,
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultDraftsFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultTrashFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSentFolder'],
				$userSharedFolder . $this->imap_delimiter . $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder']
			);
			
			if (array_search($mailbox_old, $foldersDefault)) {
				return array("status" => false, "error" => "You don't have permission for this operation!");
			}
		}
		
		$result['status'] = true;
		
		if( !imap_renamemailbox($mbox_stream,"{".$imap_server."}$mailbox_old","{".$imap_server."}$mailbox_new"))
		{
			$result = array(
				"status" => false,
				"error" => imap_last_error()
			);
		}

		if($mbox_stream){ $this->close_mbox($mbox_stream); }
		
		return $result;
	}

	function get_num_msgs($params)
	{
		$folder = $params['folder'];
		if(!$this->mbox || !is_resource($this->mbox)) {
			$this->mbox = $this->open_mbox($folder);
			if(!$this->mbox || !is_resource($this->mbox))
			return imap_last_error();
		}
		$num_msgs = imap_num_msg($this->mbox);
		if($this->mbox && is_resource($this->mbox))
			$this->close_mbox($this->mbox);

		return $num_msgs;
	}

	public function folder_exists( $folder )
	{
		return is_array( imap_getmailboxes(
			$this->open_mbox(),
			'{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}',
			$this->_toMaibox( $folder )
		) );
	}

	function add_recipients_cert($full_address)
	{
		$result = "";
		$parse_address = imap_rfc822_parse_adrlist($full_address, "");
		foreach ($parse_address as $val)
		{
			if ($val->mailbox == "INVALID_ADDRESS")
				continue;
				
			if ($val->mailbox == "UNEXPECTED_DATA_AFTER_ADDRESS")
				continue;
			
			$result .= $val->mailbox."@".$val->host . ",";
		}

		return substr($result,0,-1);
	}

	function add_recipients($recipient_type, $full_address, &$mail)
	{
		//remove a comma if is given two unexpected commas
		$full_address = preg_replace("/, ?,/",",",$full_address);
	
		$parse_address = imap_rfc822_parse_adrlist($full_address, "");
		
		foreach ($parse_address as $key => $val)
		{
			if( !$mail->SaveMessageAsDraft && preg_match('(UNEXPECTED_DATA_AFTER_ADDRESS|SYNTAX-ERROR)', $val->mailbox ) ){
				
				$mail->SetError( $parse_address[$key-1]->mailbox );
				
				return false;
			}
			
			if( !$mail->SaveMessageAsDraft && preg_match('(UNEXPECTED_DATA_AFTER_ADDRESS|SYNTAX-ERROR)', $val->host ) ){
				
				$mail->SetError( $parse_address[$key-1]->host );
				
				return false;
			}

			if( !empty($val->mailbox) && !empty($val->host))
			{
				switch($recipient_type)
				{
					case "to":
						$mail->AddAddress($val->mailbox."@".$val->host, ( empty($val->personal) ? "" : $val->personal));
						break;
					case "cc":
						$mail->AddCC($val->mailbox."@".$val->host, ( empty($val->personal) ? "" : $val->personal));
						break;
					case "cco":
						$mail->AddBCC($val->mailbox."@".$val->host, ( empty($val->personal) ? "" : $val->personal));
						break;
				}
			} else if ( !$mail->SaveMessageAsDraft ) {
				
				$mail->SetError( $val->mailbox );
				
				return false;
			}
		}

		return true;
	}

	function get_forwarding_attachment($msg_folder, $msg_number, $msg_part, $encoding)
	{
		$mbox_stream = $this->open_mbox(utf8_decode(urldecode($msg_folder)));
		$fileContent = imap_fetchbody($mbox_stream, $msg_number, $msg_part, FT_UID);
		if($encoding == 'base64')
			# The function imap_base64 adds a new line
			# at ASCII text, with CRLF line terminators.
			# So is being exchanged for base64_decode.
			#
			#$fileContent = imap_base64($fileContent);
			$fileContent = base64_decode($fileContent);
		else if($encoding == 'quoted-printable')
			$fileContent = quoted_printable_decode($fileContent);
		return $fileContent;
	}

	function del_last_two_caracters($string)
	{
		$string = substr($string,0,(strlen($string) - 2));
		return $string;
	}

	function messages_sort($sort_box_type,$sort_box_reverse, $search_box_type,$offsetBegin,$offsetEnd)
	{
		$sort = array();
		if ($sort_box_type != "SORTFROM" && $search_box_type!= "FLAGGED"){
			$imapsort = imap_sort($this->mbox,constant($sort_box_type),$sort_box_reverse,SE_UID,$search_box_type);
			foreach($imapsort as $iuid)
				$sort[$iuid] = "";
			
			if ($offsetBegin == -1 && $offsetEnd ==-1 )
				$slice_array = false;
			else
				$slice_array = true;
		}
		else
		{
			$sort = array();
			if ($offsetBegin > $offsetEnd) {$temp=$offsetEnd; $offsetEnd=$offsetBegin; $offsetBegin=$temp;}
			$num_msgs = imap_num_msg($this->mbox);
			if ($offsetEnd >  $num_msgs) {$offsetEnd = $num_msgs;}
			$slice_array = true;

			for ($i=$num_msgs; $i>0; $i--)
			{
				if ($sort_box_type == "SORTARRIVAL" && $sort_box_reverse && count($sort) >= $offsetEnd)
					break;
				$iuid = @imap_uid($this->mbox,$i);
				$header = $this->get_header($iuid);
				// List UNSEEN messages.
				if($search_box_type == "UNSEEN" &&  (!trim($header->Recent) && !trim($header->Unseen))){
					continue;
				}
				// List SEEN messages.
				elseif($search_box_type == "SEEN" && (trim($header->Recent) || trim($header->Unseen))){
					continue;
				}
				// List ANSWERED messages.
				elseif($search_box_type == "ANSWERED" && !trim($header->Answered)){
					continue;
				}
				// List FLAGGED messages.
				elseif($search_box_type == "FLAGGED" && !trim($header->Flagged)){
					continue;
				}

				if($sort_box_type=='SORTFROM') {
					if (($header->from[0]->mailbox . "@" . $header->from[0]->host) == $_SESSION['phpgw_info']['expressomail']['user']['email'])
						$from = $header->to;
					else
						$from = $header->from;

					$tmp = imap_mime_header_decode($from[0]->personal);

					if ($tmp[0]->text != "")
						$sort[$iuid] = $tmp[0]->text;
					else
						$sort[$iuid] = $from[0]->mailbox . "@" . $from[0]->host;
				}
				else if($sort_box_type=='SORTSUBJECT') {
					$sort[$iuid] = $header->subject;
				}
				else if($sort_box_type=='SORTSIZE') {
					$sort[$iuid] = $header->Size;
				}
				else {
					$sort[$iuid] = $header->udate;
				}

			}
			natcasesort($sort);

			if ($sort_box_reverse)
				$sort = array_reverse($sort,true);
		}

		if(!is_array($sort))
			$sort = array();


		if ($slice_array)
			$sort = array_slice($sort,$offsetBegin-1,$offsetEnd-($offsetBegin-1),true);


		return $sort;

	}

	function move_search_messages($params){
		$params['selected_messages'] = urldecode($params['selected_messages']);
		$params['new_folder'] = urldecode($params['new_folder']);
		$params['new_folder_name'] = urldecode($params['new_folder_name']);
		$sel_msgs = explode(",", $params['selected_messages']);
		@reset($sel_msgs);
		$sorted_msgs = array();
		foreach($sel_msgs as $idx => $sel_msg) {
			$sel_msg = explode(";", $sel_msg);
			 if(array_key_exists($sel_msg[0], $sorted_msgs)){
			 	$sorted_msgs[$sel_msg[0]] .= ",".$sel_msg[1];
			 }
			 else {
				$sorted_msgs[$sel_msg[0]] = $sel_msg[1];
			 }
		}
		@ksort($sorted_msgs);
		$last_return = false;
		foreach($sorted_msgs as $folder => $msgs_number) {
			$params['msgs_number'] = $msgs_number;
			$params['folder'] = $folder;
			if($params['new_folder'] && $folder != $params['new_folder']){
				$last_return = $this -> move_messages($params);
			}
			elseif(!$params['new_folder'] || $params['delete'] ){
				$last_return = $this -> delete_msgs($params);
				$last_return['deleted'] = true;
			}
		}
		return $last_return;
	}

	function move_messages($params)
	{
		$folder          = $this->_toUTF8( $params['folder'] );
		$msgs_number     = $params['msgs_number'];
		$mailbox_new     = $this->_toMaibox( $params['new_folder'] );
		$new_folder_name = $params['new_folder_name'];

		$mbox_stream = $this->open_mbox($folder);

		// Status has been added to validate ACL permissions
		$return = array(
			'folder'          => $folder,
			'msgs_number'     => $msgs_number,
			'new_folder_name' => $new_folder_name,
			'border_ID'       => $params['border_ID'],
			'status'          => true,
		); 

		// This block has the purpose of transforming the uid(login) of shared folders into common name
		if(isset($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['uid2cn'])){
			if (substr($new_folder_name,0, 4)  == 'user'){
				$this->ldap = new ldap_functions();

				$tmpFolderName = explode( $this->imap_delimiter, $new_folder_name );

				if( count($tmpFolderName) > 1 && isset($tmpFolderName[1]) ){
					$tmpFolderName[1] = $this->ldap->uid2cn( $tmpFolderName[1] );
				}

				$return['new_folder_name'] = ( count($tmpFolderName) > 1  ) ?
					implode( " / " , array_slice( $tmpFolderName, 1 ) ) :
					$return['new_folder_name'];
				}
		}

		// Caso estejamos no box principal, nao eh necessario pegar a informacao da mensagem anterior.
		if (($params['get_previous_msg']) && ($params['border_ID'] != 'null') && ($params['border_ID'] != ''))
		{
			$return['previous_msg'] = $this->get_info_previous_msg($params);
			// Fix problem in unserialize function JS.
			if(isset($return['previous_msg']['body'])){
				$return['previous_msg']['body'] = str_replace(array('{','}'), array('&#123;','&#125;'), $return['previous_msg']['body']);
			}
		}

		$mbox_stream = $this->open_mbox($folder);

		if(imap_mail_move($mbox_stream, $msgs_number, $mailbox_new, CP_UID)) {
			
			imap_expunge($mbox_stream);

			$imapLastError = imap_last_error();

			$imapLastError = trim($imapLastError) !== "" ? $imapLastError : false;
			
			if( $mbox_stream ){ $this->close_mbox($mbox_stream); }

			if( $imapLastError ){
				$return['error'] = trim($imapLastError);
				$return['status'] = false;
			}

			return $return;

		} else {
			
			$imapLastError = imap_last_error();

			if( strstr( $imapLastError ,'Over quota')) {
				$accountID	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapAdminUsername'];
				$pass		= $_SESSION['phpgw_info']['expressomail']['email_server']['imapAdminPW'];
				$userID 	= $_SESSION['phpgw_info']['expressomail']['user']['userid'];
				$server 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
				$mbox		= @imap_open("{".$this->imap_server.":".$this->imap_port.$this->imap_options."}INBOX", $accountID, $pass) or die(serialize(array('imap_error' => $this->parse_error(imap_last_error()))));
				
				if( !$mbox ){
					return array( 'status' => false, 'error' => imap_last_error() );
				}
				
				$quota 	= @imap_get_quotaroot($mbox_stream, "INBOX");

				if(!imap_set_quota($mbox, "user".$this->imap_delimiter.$userID, 2.1 * $quota['usage'])) {

					if( $mbox_stream ){ $this->close_mbox($mbox_stream); }

					if( $mbox ){ $this->close_mbox($mbox); }
					
					return array( 
						"status" => false,
						"error" => "move_messages(): Error setting quota for MOVE or DELETE!! ". "user".$this->imap_delimiter.$userID." line ".__LINE__."\n"
					);
				}

				if(imap_mail_move($mbox_stream, $msgs_number, $mailbox_new, CP_UID)) {

					imap_expunge($mbox_stream);

					if($mbox_stream){ $this->close_mbox($mbox_stream); }

					// return to original quota limit.
					if(!imap_set_quota($mbox, "user".$this->imap_delimiter.$userID, $quota['limit'])) {

						if( $mbox ){ $this->close_mbox($mbox); }
		
						return array(
							"status" => false,
							"error" => "move_messages(): Error setting quota for MOVE or DELETE!! line ".__LINE__."\n"
						);
					}
					
					return $return;

				} else {
					
					if($mbox_stream){ $this->close_mbox($mbox_stream); }

					if(!imap_set_quota($mbox, "user".$this->imap_delimiter.$userID, $quota['limit'])) {

						if($mbox){ $this->close_mbox($mbox); }

						return array(
							"status" => false,
							"error" => "move_messages(): Error setting quota for MOVE or DELETE!! line ".__LINE__."\n"
						);
					}

					return array(
						"status" => false,
						"error" => "move_messages : " . imap_last_error(),
					);
				}

			} else {
				
				if( $mbox_stream){ $this->close_mbox($mbox_stream); }
				
				return array( 
					'status' => false,	
					'error' => "move_messages : " . $imapLastError, 
					'folder' => $params['new_folder'],
				);
			}
		}
	}

	public function send_mail( $params )
	{
		if ( !$this->has_permission_to_send( $params ) ) return lang("The server denied your request to send a mail, you cannot use this mail address.");
		if ( ( $msg = $this->check_Attachments( $params ) ) !== true ) return $msg;

		$mail = $this->compose_msg( $params, false );
		
		if( isset($mail->error_count) && $mail->error_count > 0 ){
			return array( 'success' => false, 'error' => $mail->ErrorInfo );	
		}

		$signed = isset($params['input_return_digital'])? $params['input_return_digital'] : false;

		if ( !( $sent = $mail->Send() ) ) $this->parse_error( $mail->ErrorInfo );

		if ( $signed && !$params['smime'] ) return $sent;

		if (
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['number_of_contacts'] &&
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_dynamic_contacts']
		) {
			$contacts     = new dynamic_contacts();
			$new_contacts = $contacts->add_dynamic_contacts( implode( ',', array_merge( $mail->GetAddresses( 'to' ), $mail->GetAddresses( 'cc' ), $mail->GetAddresses( 'bcc' ) ) ) );
			return array("success" => true, "new_contacts" => $new_contacts, 'refresh_folders' => $mail->RefreshFolders );
		}

		return array( 'success' => true, 'refresh_folders' => $mail->RefreshFolders );
	}

	public function save_msg( $params )
	{
		if ( ( $msg = $this->check_Attachments( $params ) ) !== true ) return $msg;

		include_once 'class.message_reader.inc.php';

		$msg_uid     = ( isset( $params['msg_id'                 ] ) && $params['msg_id'                       ] > 0      )? $params['msg_id'] : false;

		$mail        = $this->compose_msg( $params, true );
		
		$mail_reader = new MessageReader();

		$folder      = $this->_toUTF8( $mail->SaveMessageInFolder, 'UTF7-IMAP' );
		$this->open_mbox( $folder );

		if ( !imap_append( $this->mbox, '{'.$this->imap_server.':'.$this->imap_port.'}'.$mail->SaveMessageInFolder, $mail->CreateHeader(). $mail->CreateBody(), '\Seen \Draft' ) ){
			return array( 'status' => false, 'error' => imap_last_error() );
		}

		$sa_uidnext  = imap_status( $this->mbox, '{'.$this->imap_server.':'.$this->imap_port.'}'.$mail->SaveMessageInFolder, SA_UIDNEXT );
		$mail_reader->setMessage( $this->mbox, $folder, ( $sa_uidnext->uidnext-1 ) );

		$return          = $mail_reader->getInfo();
		$return->subject = $mail->Subject;
		$return->status  = true;

		if ( $msg_uid !== false ) {
			imap_delete( $this->mbox, $msg_uid, FT_UID );
			$this->close_mbox( $this->mbox, CL_EXPUNGE );
		} else $this->close_mbox( $this->mbox );

		return $return;
	}

	protected function check_Attachments( $params )
	{
		if ( isset( $params['count_files'] ) && isset( $_FILES ) && ( (int)$params['count_files'] ) !== count( $_FILES ) )
			return array( 'error' => lang( 'max file uploads exceeds' ) );

		if ( ( isset( $_FILES )? count( $_FILES ) : 0 )+( isset( $params['forwarding_attachments'] )? count( $params['forwarding_attachments'] ) : 0 ) > ini_get( 'max_file_uploads' ) )
			return array( 'error' => lang( 'max file uploads exceeds' ) );

		if ( isset( $_FILES ) ) foreach ( $_FILES as $file ) if ( isset( $file['error'] ) && $file['error'] > 0 )
			return array( 'error' => $this->functions->getLang( $this->fileUploadErrors( $file['error'] ) ) );

		if ( count( $_POST ) === 0 && count( $_FILES ) === 0 )
			return array( 'error' => $this->functions->getLang( 'Value exceeds the PHP upload limit for this server' ).
				' '.( $this->formatBytes( isset( $_SERVER['CONTENT_LENGTH'] )? $_SERVER['CONTENT_LENGTH'] : false ) ).' / '.$this->formatBytes( ini_get( 'post_max_size' ) ) );

		return true;
	}

	protected function formatBytes( $size, $precision = 2 )
	{
		if ( $size === false ) return '?';
		if ( preg_match('/[KMGT]$/', strtoupper( trim( $size ) ), $m ) ) {
			$f = floatval( $size );
			switch ( $m[0] ) {
				case 'T': $f *= 1024;
				case 'G': $f *= 1024;
				case 'M': $f *= 1024;
				case 'K': $f *= 1024;
			}
			$size = (int)$f;
		}
		$base = log( $size, 1024 );
		$sufx = array( '', 'K', 'M', 'G', 'T' );
		return round( pow( 1024, $base - floor( $base ) ), $precision ).$sufx[floor( $base )];
	}

	protected function compose_msg( $params, $saveMessageAsDraft = false )
	{
		include_once 'class.phpmailer.php';
		include_once 'class.message_reader.inc.php';
		include_once 'class.db_functions.inc.php';

		$mail           = new PHPMailer();
		$mail_reader    = new MessageReader();
		$db             = new db_functions();

		$body           = isset( $params['body'] )? $params['body'] : '';
		$folder         = ( isset( $params['folder']                  ) && trim( $params['folder']                  ) !== '' )? trim( $params['folder']        ) : false;
		$subject        = ( isset( $params['input_subject']           ) && trim( $params['input_subject']           ) !== '' )? trim( $params['input_subject'] ) : false;
		$fromaddress    = ( isset( $params['input_from']              ) && trim( $params['input_from']              ) !== '' )? explode( ';', trim( $params['input_from'] ) ) : false;
		$toaddress      = ( isset( $params['input_to']                ) && trim( $params['input_to']                ) !== '' )? trim( $params['input_to']      ) : false;
		$ccaddress      = ( isset( $params['input_cc']                ) && trim( $params['input_cc']                ) !== '' )? trim( $params['input_cc']      ) : false;
		$ccoaddress     = ( isset( $params['input_cco']               ) && trim( $params['input_cco']               ) !== '' )? trim( $params['input_cco']     ) : false;
		$replytoaddress = ( isset( $params['input_replyto']           ) && trim( $params['input_replyto']           ) !== '' )? trim( $params['input_replyto'] ) : false;
		$smime          = ( isset( $params['smime']                   ) && trim( $params['smime']                   ) !== '' )? trim( $params['smime']         ) : false;
		$encrypt        = ( isset( $params['input_return_cripto']     ) && trim( $params['input_return_cripto']     ) !== '' );
		$signed         = ( isset( $params['input_return_digital']    ) && trim( $params['input_return_digital']    ) !== '' );
		$return_receipt = ( isset( $params['input_return_receipt']    ) && trim( $params['input_return_receipt']    ) !== '' );
		$is_important   = ( isset( $params['input_important_message'] ) && trim( $params['input_important_message'] ) !== '' );
		$is_html        = !( isset( $params['type']                   ) && trim( $params['type']                    ) === 'plain' );
		$fwrd_attachs   = isset( $params['forwarding_attachments'] )? array_map( 'json_decode', $params['forwarding_attachments'] ) : array();
		$attachments    = isset( $_FILES )? $_FILES : array();

		//Test if must be saved in shared folder and change if necessary
		if( isset($fromaddress[2]) && $fromaddress[2] == 'y' ){
			//build shared folder path
			$newfolder = "user".$this->imap_delimiter.$fromaddress[3].$this->imap_delimiter.$this->imap_sentfolder;
			if( $this->folder_exists($newfolder) ) $folder = $newfolder;
		}

		if ( $smime ) {
			$error = $this->check_smime( $mail, $smime );
			if( count( $error ) ) foreach ( $error as $msg ) $mail->SetError( $msg );
		} else $body = mb_ereg_replace( '<!--\[', '<!-- [', $body ); //Compatibilizacao com Outlook, ao encaminhar a mensagem

		if ( $signed && !$smime ) {
			$mail->Mailer          = 'smime';
			$mail->SignedBody      = true;
		} else $mail->IsSMTP();

		// Save message as draft
		$mail->SaveMessageAsDraft = ( $saveMessageAsDraft ) ? true : false ;
		
		// To,CC and CCo empty
		if( !$toaddress && (!$ccaddress && !$ccoaddress)){
			$mail->SetError(lang('message without receiver'));
		}
		
		// Email(s) To
		if ( $toaddress){
			if( !$this->add_recipients( 'to',  implode( ',', $db->getAddrs( explode( ',', $toaddress  ) ) ), $mail )){
				$mail->SetError( lang("Error") . " : " . lang("The address %1 in the To field was not recognized. Verify that all addresses are correct.", $mail->ErrorInfo ) );
			}
		}
		
		// Email(s) CC
		if ( $ccaddress){
			if( !$this->add_recipients( 'cc',  implode( ',', $db->getAddrs( explode( ',', $ccaddress  ) ) ), $mail )){
				$mail->SetError( lang("Error") . " : " . lang("The address %1 in the CC field was not recognized. Verify that all addresses are correct.", $mail->ErrorInfo ) );
			}
		}
		
		// Email(s) CCO
		if($ccoaddress){
			if( !$this->add_recipients( 'cco', implode( ',', $db->getAddrs( explode( ',', $ccoaddress ) ) ), $mail )) {
				$mail->SetError( lang("Error") . " : " . lang("The address %1 in the CCo field was not recognized. Verify that all addresses are correct.", $mail->ErrorInfo ) );
			}
		}

		if ( ( $encrypt && $signed && $smime ) || ( $encrypt && !$signed ) ) {
			$error = $this->check_smime( $mail, $db );
			if( count( $error ) ) foreach ( $error as $msg ) $mail->SetError( $msg );
		}

		$mail->SMTPDebug           = false;
		$mail->Host                = $_SESSION['phpgw_info']['expressomail']['email_server']['smtpServer'];
		$mail->Port                = $_SESSION['phpgw_info']['expressomail']['email_server']['smtpPort'];
		$mail->From                = $_SESSION['phpgw_info']['expressomail']['user']['email'];
		$mail->FromName            = $this->fullNameUser;
		$mail->Sender              = $mail->From;
		$mail->SenderName          = $mail->FromName;

		if( $fromaddress ) {
			$mail->FromName = $fromaddress[0];
			$mail->From = $fromaddress[1];
		}

		$mail->Subject             = ( $subject === false )? '' : $subject;
		$mail->Body                = $body;
		$mail->CharSet             = mb_detect_encoding( $body, 'UTF-8, ISO-8859-1' );
		$mail->IsHTML( $is_html );

		if ( $replytoaddress ) $mail->AddReplyTo( $replytoaddress );
		if ( $is_important   ) $mail->isImportant();
		if ( $return_receipt ) $mail->ConfirmReadingTo = $_SESSION['phpgw_info']['expressomail']['user']['email'];
		if ( $folder         ) {
			// Fix problem with cyrus delimiter changes.
			// Dots in names: enabled/disabled.
			$folder = preg_replace( '#INBOX/#i', 'INBOX'.$this->imap_delimiter, $folder );
			$folder = preg_replace( '#INBOX.#i', 'INBOX'.$this->imap_delimiter, $folder );
			$mail->SaveMessageInFolder = $this->_toMaibox( $folder );
		}

		if ( $is_html ) $this->buildEmbeddedImages( $mail, $mail_reader );

		// Forwarding Attachments
		foreach( $fwrd_attachs as $msg ) {
			$this->open_mbox( $msg->folder );
			$info = $mail_reader->setMessage( $this->mbox, $msg->folder, $msg->msg_no )->getAttach( $msg->section );
			$mail->AddStringAttachment( $info->data, $info->filename, $info->encoding, $info->type, ( isset( $info->params->name )? $info->params->name : false ) );
		}

		// Uploading New Attachments
		foreach ( $attachments as $fid => $attach ) {
			if ( preg_match( '/^cid:(.*)$/', $fid, $cid ) ) {
				$fname = pathinfo( $attach['name'], PATHINFO_FILENAME ).'.jpg';
				$mail->AddStringEmbeddedImage( $this->resize_upload_image( $attach ), $cid[1], $fname, 'base64', $this->get_file_type( $fname ) );
				$mail->Body = preg_replace( '/<img[^>]*'.$cid[1].'[^>]*>/', '<img cid="'.$cid[1].'" src="cid:'.$cid[1].'">', $mail->Body );
			} else {
				$mail->AddAttachment( ( isset( $attach['isbase64'] ) && $attach['isbase64'] )? base64_decode( $attach['source'] ) : $attach['tmp_name'], $attach['name'], 'base64', $this->get_file_type( $attach['name'] ) );
			}
		}

		if( !empty( $mail->AltBody ) ) $mail->ContentType = 'multipart/alternative';
		$mail->SetMessageType();

		return $mail;
	}

	protected function buildEmbeddedImages( &$mail_writer, &$mail_reader )
	{
		$transpose = function( $a ) { array_unshift( $a, null); return call_user_func_array( 'array_map', $a ); };
		$pattern   = '/src="([^"]*?show_img.php\?msg_folder=(.+)?&(?:amp;)?msg_num=(.+)?&(?:amp;)?msg_part=(.+)?)"/isU';
		$cids      = array();
		if ( !preg_match_all( $pattern, $mail_writer->Body, $cid_imgs, PREG_PATTERN_ORDER ) ) return true;

		$mail_writer->Body = preg_replace('/ *cid="[^"]*" */', ' ', $mail_writer->Body );

		foreach ( $transpose( $cid_imgs ) as $cid_img ) {
			list( $cid_str, $cid_src, $cid_folder, $cid_msg_no, $cid_section ) = $cid_img;
			if ( isset( $cids[$cid_str] ) ) continue;

			$this->open_mbox( $cid_folder );
			$info = $mail_reader->setMessage( $this->mbox, $cid_folder, $cid_msg_no )->getAttach( $cid_section );

			if ( !$info->cid ) continue;
			$cids[$cid_str] = $info->cid;

			$mail_writer->AddStringEmbeddedImage( $info->data, $cids[$cid_str], ( isset( $info->params->name )? $info->params->name : false ), $info->encoding, $info->type, $info->filename );
			$mail_writer->Body = str_replace( $cid_str, 'cid="'.$cids[$cid_str].'" src="cid:'.$cids[$cid_str].'"', $mail_writer->Body );
		}
		return true;
	}

	protected function build_smime( &$mail, &$db )
	{
		$emails = explode( ',', $this->add_recipients_cert( implode( ',', array_merge( $mail->GetAddresses( 'to' ), $mail->GetAddresses( 'cc' ), $mail->GetAddresses( 'bcc' ) ) ) ) );
		// Deve ser testado se foram obtidos os certificados de todos os destinatarios.
		// Deve ser verificado um numero limite de destinatarios.
		// Deve ser verificado se os certificados sao validos.
		// Se uma das verificacoes falhar, nao enviar o e-mail e avisar o usuario.
		// O array $mail->Certs_crypt soh deve ser preenchido se os certificados passarem nas verificacoes.

		// Este valor dever ser configurado pelo administrador do site ....
		if ( count( $emails ) > $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['num_max_certs_to_cipher'] )
			return array( 'Excedido o numero maximo ('.$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['num_max_certs_to_cipher'].') de destinatarios para uma msg cifrada...' );

		// adiciona o email do remetente. eh para cifrar a msg para ele tambem. Assim vai poder visualizar a msg na pasta enviados..
		$emails[] = $_SESSION['phpgw_info']['expressomail']['user']['email'];
		
		$pub_keys  = array();
		$mail_list = array();
		$error     = array();
		foreach( $emails as $email ) {

			if ( !( $certificate = $db->get_certificate( strtolower( $email ) ) ) ) return array( 'Chamada com parametro invalido. e-Mail nao pode ser vazio.' );
			if ( array_key_exists( 'dberr1', $certificate ) ) return array( 'Ocorreu um erro quando pesquisava certificados dos destinatarios para cifrar a msg.' );
			if ( array_key_exists( 'dberr2', $certificate ) ) $error[] = 'Nao pode cifrar a msg. Certificado nao localizado.';

			include_once dirname( __FILE__ ).'/../../security/classes/CertificadoB.php';

			foreach ( $certificate['certs'] as $registro ) {
				$c1 = new certificadoB();
				$c1->certificado( $registro['chave_publica'] );
				if ( $c1->apresentado ) {
					$c2 = new Verifica_Certificado( $c1->dados, $registro['chave_publica'] );
					if ( !$c1->dados['EXPIRADO'] && !$c2->revogado && $c2->status ) {
						$pub_keys[] = $registro['chave_publica'];
						$mail_list[] = strtolower( $email );
					} else {
						if ( $c1->dados['EXPIRADO'] || $c2->revogado )
							$db->update_certificate( $c1->dados['SERIALNUMBER'], $c1->dados['EMAIL'], $c1->dados['AUTHORITYKEYIDENTIFIER'], $c1->dados['EXPIRADO'], $c2->revogado );

						$error[] = $email.': '.$c2->msgerro;
						$error = array_merge( $error, $c2->erros_ssl );
						$error[] = 'Emissor: '.$c1->dados['EMISSOR'];
						$error[] = $c1->dados['CRLDISTRIBUTIONPOINTS'];
					}
				} else $error[] = $email.': Nao pode cifrar a msg. Certificado invalido.';
			}
			if ( count( $error ) || !in_array( strtolower( $email ), $mail_list ) )  return $error;
		}

		$mail->Certs_crypt = $pub_keys;

		return array();
	}

	protected function check_smime( &$mail, $smime )
	{
		include_once dirname( __FILE__ ).'/../../security/classes/CertificadoB.php';
		$mail->SMIME = true;
		// A MSG assinada deve ser testada neste ponto.
		// Testar o certificado e a integridade da msg....
		$cert = new certificadoB();
		if ( !$cert->verificar( $smime ) ) return $cert->erros_ssl;

		// Testa o CERTIFICADO: se o CPF  he o do usuario logado, se  pode assinar msgs e se  nao esta expirado...
		$error = array();
		if ( $cert->apresentado ) {
			if ( $cert->dados['EXPIRADO'] ) $error[] = 'Certificado expirado.';
			if ( $cert->dados['CPF'] != $this->username ) $error[] = 'CPF no certificado diferente do logado no expresso.';
			if ( !( $cert->dados['KEYUSAGE']['digitalSignature'] && $cert->dados['EXTKEYUSAGE']['emailProtection'] ) ) $error[] = 'Certificado nao permite assinar mensagens.';
		}
		return $error;
	}

	protected function has_permission_to_send( $params )
	{
		##
		# @AUTHOR Rodrigo Souza dos Santos
		# @DATE 2008/09/17$fileName
		# @BRIEF Checks if the user has permission to send an email with the email address used.
		##
		$fromaddress = ( isset($params['input_from']) ? explode(';',$params['input_from']) : "" );

		if ( is_array($fromaddress) && ( $fromaddress[1] !== $_SESSION['phpgw_info']['expressomail']['user']['email']) )
		{
			$deny = false;

			$shared_mailboxes = array();
			
			if ( isset($_SESSION['phpgw_info']['expressomail']['user']['shared_mailboxes']) ) {
				$shared_mailboxes = $_SESSION['phpgw_info']['expressomail']['user']['shared_mailboxes'];
				
			} else {
				$this->ldap = new ldap_functions();

				$mbox_stream = $this->open_mbox();
				
				$folders_list = imap_getmailboxes(
						$mbox_stream,
						'{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}',
						'user'.$this->imap_delimiter.'%'
						);
				
				$uids = array_map( function( $val ) {
					return substr( $val->name, strrpos( $val->name, $val->delimiter ) + 1 );
				}, $folders_list );

				$shared_mailboxes = $this->ldap->getSharedUsersFrom( array( 'uids' => implode( ';', $uids ) ) );
			}

			foreach ( $shared_mailboxes as $val ) {
				if ( isset( $val['mail'] ) && trim($val['mail']) === trim($fromaddress[1]) ) {
					$deny = true;
					break;
				}
			}
			return $deny;
		}
		
		return true;
	}

	protected function resize_upload_image( $attach )
	{
		list( $width, $height, $image_type ) = getimagesize( $attach['tmp_name'] );
		$rsize      =  max( $width, $height ) / (int)( $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['image_size']?: 65536 );
		$new_width  = $width * $rsize;
		$new_height = $height * $rsize;
		$image_new  = imagecreatetruecolor( $new_width, $new_height );

		switch ( $image_type ) {
			// Do not corrupt animated gif
			//case 1: $image_big = imagecreatefromgif( $attach['tmp_name'] );break;
			case 2: $image_big = imagecreatefromjpeg( $attach['tmp_name'] );  break;
			case 3: $image_big = imagecreatefrompng( $attach['tmp_name'] ); break;
			case 6:
				require_once("gd_functions.php");
				$image_big = imagecreatefrombmp( $attach['tmp_name'] ); break;
			default: $image_big = file_get_contents( $attach['tmp_name'] );
		}

		imagecopyresampled( $image_new, $image_big, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
		ob_start();
		imagejpeg( $image_new, null, 85 );
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	function set_messages_flag($params)
	{
		$folder = $params['folder'];
		$msgs_to_set = ( isset($params['msgs_to_set']) ? $params['msgs_to_set']: false );
		$flag = $params['flag'];
		$return = array();
		$return["msgs_to_set"] = $msgs_to_set;
		$return["flag"] = $flag;

		$this->mbox = $this->open_mbox($folder);

		if ($flag == "unseen")
			$return["status"] = imap_clearflag_full($this->mbox, $msgs_to_set, "\\Seen", ST_UID);
		elseif ($flag == "seen")
			$return["status"] = imap_setflag_full($this->mbox, $msgs_to_set, "\\Seen", ST_UID);
		elseif ($flag == "answered"){
			$return["status"] = imap_setflag_full($this->mbox, $msgs_to_set, "\\Answered", ST_UID);
			imap_clearflag_full($this->mbox, $msgs_to_set, "\\Draft", ST_UID);
		}
		elseif ($flag == "forwarded")
			$return["status"] = imap_setflag_full($this->mbox, $msgs_to_set, "\\Answered \\Draft", ST_UID);
		elseif ($flag == "flagged")
			$return["status"] = imap_setflag_full($this->mbox, $msgs_to_set, "\\Flagged", ST_UID);
		elseif ($flag == "unflagged") {
			$flag_importance = false;
			$msgs_number = explode(",",$msgs_to_set);
			$unflagged_msgs = "";
			foreach($msgs_number as $msg_number) {
				preg_match('/importance *: *(.*)\r/i',
					imap_fetchheader($this->mbox, imap_msgno($this->mbox, $msg_number))
					,$importance);
				if(strtolower($importance[1])=="high" && $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_important_flag']) {
					$flag_importance=true;
				}
				else {
					$unflagged_msgs.=$msg_number.",";
				}
			}

			if($unflagged_msgs!="") {
				imap_clearflag_full($this->mbox,substr($unflagged_msgs,0,strlen($unflagged_msgs)-1), "\\Flagged", ST_UID);
				$return["msgs_unflageds"] = substr($unflagged_msgs,0,strlen($unflagged_msgs)-1);
			}
			else {
				$return["msgs_unflageds"] = false;
			}

			if($flag_importance && $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_important_flag']) {
				$return["status"] = false;
				$return["msg"] = $this->functions->getLang("At least one of selected message cant be marked as normal");
			}
			else {
				$return["status"] = true;
			}
		}

		if($this->mbox && is_resource($this->mbox))
			$this->close_mbox($this->mbox);
		return $return;
	}
	
	private function get_file_type( $fname )
	{
		switch ( strtolower( substr( $fname, strrpos( $fname, '.' ) ) ) ) {
			case '.eml': return 'text/plain';
			case '.asf': return 'video/x-ms-asf';
			case '.avi': return 'video/avi';
			case '.doc': return 'application/msword';
			case '.zip': return 'application/zip';
			case '.xls': return 'application/vnd.ms-excel';
			case '.gif': return 'image/gif';
			case '.png': return 'image/png';
			case '.jpg': case 'jpeg': return 'image/jpeg';
			case '.wav': return 'audio/wav';
			case '.mp3': return 'audio/mpeg3';
			case '.mpg': case 'mpeg': return 'video/mpeg';
			case '.rtf': return 'application/rtf';
			case '.htm': case 'html': return 'text/html';
			case '.xml': return 'text/xml';
			case '.xsl': return 'text/xsl';
			case '.css': return 'text/css';
			case '.php': return 'text/php';
			case '.asp': return 'text/asp';
			case '.pdf': return 'application/pdf';
			case '.txt': return 'text/plain';
			case '.log': return 'text/plain';
			case '.wmv': return 'video/x-ms-wmv';
			case '.sxc': return 'application/vnd.sun.xml.calc';
			case '.odt': return 'application/vnd.oasis.opendocument.text';
			case '.stc': return 'application/vnd.sun.xml.calc.template';
			case '.sxd': return 'application/vnd.sun.xml.draw';
			case '.std': return 'application/vnd.sun.xml.draw.template';
			case '.sxi': return 'application/vnd.sun.xml.impress';
			case '.sti': return 'application/vnd.sun.xml.impress.template';
			case '.sxm': return 'application/vnd.sun.xml.math';
			case '.sxw': return 'application/vnd.sun.xml.writer';
			case '.sxq': return 'application/vnd.sun.xml.writer.global';
			case '.stw': return 'application/vnd.sun.xml.writer.template';
			case '.ps':  return 'application/postscript';
			case '.pps': return 'application/vnd.ms-powerpoint';
			case '.odt': return 'application/vnd.oasis.opendocument.text';
			case '.ott': return 'application/vnd.oasis.opendocument.text-template';
			case '.oth': return 'application/vnd.oasis.opendocument.text-web';
			case '.odm': return 'application/vnd.oasis.opendocument.text-master';
			case '.odg': return 'application/vnd.oasis.opendocument.graphics';
			case '.otg': return 'application/vnd.oasis.opendocument.graphics-template';
			case '.odp': return 'application/vnd.oasis.opendocument.presentation';
			case '.otp': return 'application/vnd.oasis.opendocument.presentation-template';
			case '.ods': return 'application/vnd.oasis.opendocument.spreadsheet';
			case '.ots': return 'application/vnd.oasis.opendocument.spreadsheet-template';
			case '.odc': return 'application/vnd.oasis.opendocument.chart';
			case '.odf': return 'application/vnd.oasis.opendocument.formula';
			case '.odi': return 'application/vnd.oasis.opendocument.image';
			case '.ndl': return 'application/vnd.lotus-notes';
		}
		return 'application/octet-stream';
	}

	function htmlspecialchars_encode($str)
	{
		return  str_replace( array('&', '"','\'','<','>','{','}'), array('&amp;','&quot;','&#039;','&lt;','&gt;','&#123;','&#125;'), $str);
	}
	function htmlspecialchars_decode($str)
	{
		return  str_replace( array('&amp;','&quot;','&#039;','&lt;','&gt;','&#123;','&#125;'), array('&', '"','\'','<','>','{','}'), $str);
	}

	function get_msgs($folder, $sort_box_type, $search_box_type, $sort_box_reverse,$offsetBegin = 0,$offsetEnd = 0)
	{
		if(!$this->mbox || !is_resource($this->mbox))
			$this->mbox = $this->open_mbox($folder);

		return $this->messages_sort($sort_box_type,$sort_box_reverse, $search_box_type,$offsetBegin,$offsetEnd);
	}

	function get_info_next_msg($params)
	{
		$msg_number = $params['msg_number'];
		$folder = $params['msg_folder'];
		$sort_box_type = $params['sort_box_type'];
		$sort_box_reverse = $params['sort_box_reverse'];
		$reuse_border = $params['reuse_border'];
		$search_box_type = $params['search_box_type'] != "ALL" && $params['search_box_type'] != "" ? $params['search_box_type'] : false;
		$sort_array_msg = $this -> get_msgs($folder, $sort_box_type, $search_box_type, $sort_box_reverse);

		$success = false;
		if (is_array($sort_array_msg))
		{
			foreach ($sort_array_msg as $i => $value){
				if ($value == $msg_number)
				{
					$success = true;
					break;
				}
			}
		}

		if (! $success || $i >= sizeof($sort_array_msg)-1)
		{
			$params['status'] = 'false';
			$params['command_to_exec'] = "delete_border('". $reuse_border ."');";
			return $params;
		}

		$params = array();
		$params['msg_number'] = $sort_array_msg[($i+1)];
		$params['msg_folder'] = $folder;

		$return = $this->get_info_msg($params);
		$return["reuse_border"] = $reuse_border;
		return $return;
	}

	function get_info_previous_msg($params)
	{
		$msg_number = $params['msgs_number'];
		$folder = $params['folder'];
		$sort_box_type = $params['sort_box_type'];
		$sort_box_reverse = $params['sort_box_reverse'];
		$reuse_border = $params['reuse_border'];
		$search_box_type = $params['search_box_type'] != "ALL" && $params['search_box_type'] != "" ? $params['search_box_type'] : false;
		$sort_array_msg = $this -> get_msgs($folder, $sort_box_type, $search_box_type, $sort_box_reverse);

		$success = false;
		if (is_array($sort_array_msg))
		{
			foreach ($sort_array_msg as $i => $value){
				if ($value == $msg_number)
				{
					$success = true;
					break;
				}
			}
		}
		if (! $success || $i == 0)
		{
			$params['status'] = 'false';
			$params['command_to_exec'] = "delete_border('". $reuse_border ."');";
			return $params;
		}

		$params = array();
		$params['msg_number'] = $sort_array_msg[($i-1)];
		$params['msg_folder'] = $folder;

		$return = $this->get_info_msg($params);
		$return["reuse_border"] = $reuse_border;
		return $return;
	}

	// This function updates the values: quota, paging and new messages menu.
	function get_menu_values($params){
		$return_array = array();
		$return_array = $this->get_quota($params);

		$mbox_stream = $this->open_mbox($params['folder']);
		$return_array['num_msgs'] = imap_num_msg($mbox_stream);
		if($mbox_stream)
			$this->close_mbox($mbox_stream);

		return $return_array;
	}

	function get_quota($params){
		// folder_id = user/{uid} for shared folders
		if(substr($params['folder_id'],0,5) != 'INBOX' && preg_match('/user\\'.$this->imap_delimiter.'/i', $params['folder_id'])){
			$array_folder =  explode($this->imap_delimiter,$params['folder_id']);
			$folder_id = "user".$this->imap_delimiter.$array_folder[1];
		}else{
			$folder_id = "INBOX";
		}

		if( !is_resource($this->mbox)){
			$this->mbox = $this->open_mbox();
		}

		$quota = @imap_get_quotaroot( $this->mbox, $folder_id );
		if ( $this->mbox && is_resource( $this->mbox ) ) $this->close_mbox( $this->mbox );
		
		// Auto raise to default user quota, configured in expressoAdmin
		if (
			( isset($_SESSION['phpgw_info']['expresso']['expressoAdmin']['expressoAdmin_autoRaiseQuota']) && 
				$_SESSION['phpgw_info']['expresso']['expressoAdmin']['expressoAdmin_autoRaiseQuota'] === 'true' ) &&
			( isset( $quota['limit'] ) ) &&
			( $quota['limit'] < (
				$def_quota = $_SESSION['phpgw_info']['expressomail']['email_server']['defaultUserQuota'] * 1024
			) ) &&
			( $def_quota > 0 )
		) {
			$userID = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
			$mailbox = imap_open(
				'{'.$this->imap_server.':'.$this->imap_port.'/novalidate-cert}',
				$_SESSION['phpgw_info']['expressomail']['email_server']['imapAdminUsername'],
				$_SESSION['phpgw_info']['expressomail']['email_server']['imapAdminPW'],
				OP_HALFOPEN
			);
			
			if( imap_set_quota( $mailbox, "user".$this->imap_delimiter.$userID , $def_quota ) ) {
				
				// Update variable
				$quota['limit'] = $def_quota;
				
				// Log writter
				include_once('class.db_functions.inc.php');
				$this->db = new db_functions();
				$this->db->write_log( 'Aumento de Quota automatico ', $userID.' - '.$def_quota, 'expresso-admin' );
				
			}
			$this->close_mbox( $mailbox );
		}

		if (!$quota){
			return array(
				'quota_percent' => 0,
				'quota_used' => 0,
				'quota_limit' =>  0
			);
		}

		if(count($quota) && $quota['limit']) {
			$quota_limit = $quota['limit'];
			$quota_used  = $quota['usage'];
			if($quota_used >= $quota_limit)
			{
				$quotaPercent = 100;
			}
			else
			{
			$quotaPercent = ($quota_used / $quota_limit)*100;
			$quotaPercent = (($quotaPercent)* 100 + .5 )* .01;
			}
			return array(
				'quota_percent' => floor($quotaPercent),
				'quota_used' => $quota_used,
				'quota_limit' =>  $quota_limit
			);
		}
		else
			return array();
	}

	function send_notification($params){
		
		include("../header.inc.php");
		
		require_once("class.phpmailer.php");
		
		$mail = new PHPMailer();

		$toaddress = $params['notificationto'];
		$body  = lang("Your message: %1",$this->_toUTF8( $params['subject'] )) . '<br>';
		$body .= lang("Received in: %1",date("d/m/Y H:i",$params['date'])) . '<br>';
		$body .= lang("Has been read by: %1 &lt; %2 &gt; at %3", $this->fullNameUser , $_SESSION['phpgw_info']['expressomail']['user']['email'], date("d/m/Y H:i"));
		$mail->SMTPDebug = false;
		$mail->IsSMTP();
		$mail->Host = $_SESSION['phpgw_info']['expressomail']['email_server']['smtpServer'];
		$mail->Port = $_SESSION['phpgw_info']['expressomail']['email_server']['smtpPort'];
		$mail->From = $_SESSION['phpgw_info']['expressomail']['user']['email'];
		$mail->FromName = $this->fullNameUser;
		$mail->AddAddress($toaddress);
		$mail->Subject = $this->_toUTF8( lang("Read receipt: %1", "" ) ) . $this->_toUTF8( $params['subject'] );
		$mail->IsHTML(true);
		$mail->CharSet = mb_detect_encoding( $body, 'UTF-8, ISO-8859-1' );
		$mail->Body = $body;

		return ( (!$mail->Send()) ? $mail->ErrorInfo : true );
	}

	function search($params)
	{
		include("class.imap_attachment.inc.php");
		$imap_attachment = new imap_attachment();
		$criteria = $params['criteria'];
		$return = array();
		$folders = $this->get_folders_list();

		$j = 0;
		foreach($folders as $folder)
		{
			$mbox_stream = $this->open_mbox($folder);
			$messages = imap_search($mbox_stream, $criteria, SE_UID);

			if ($messages == '')
				continue;

			$i = 0;
			$return[$j] = array();
			$return[$j]['folder_name'] = $folder['name'];

			foreach($messages as $msg_number)
			{
				$header = $this->get_header($msg_number);
				if (!is_object($header))
					return false;

				$return[$j][$i]['msg_folder']	= $folder['name'];
				$return[$j][$i]['msg_number']	= $msg_number;
				$return[$j][$i]['Recent']		= $header->Recent;
				$return[$j][$i]['Unseen']		= $header->Unseen;
				$return[$j][$i]['Answered']	= $header->Answered;
				$return[$j][$i]['Deleted']		= $header->Deleted;
				$return[$j][$i]['Draft']		= $header->Draft;
				$return[$j][$i]['Flagged']		= $header->Flagged;

				$date_msg = gmdate("d/m/Y",$header->udate);
				if (gmdate("d/m/Y") == $date_msg)
					$return[$j][$i]['udate'] = gmdate("H:i",$header->udate);
				else
					$return[$j][$i]['udate'] = $date_msg;

				$fromaddress = imap_mime_header_decode($header->fromaddress);
				$return[$j][$i]['fromaddress'] = '';
				foreach ($fromaddress as $tmp)
					$return[$j][$i]['fromaddress'] .= $this->replace_maior_menor($tmp->text);

				$from = $header->from;
				$return[$j][$i]['from'] = array();
				$tmp = imap_mime_header_decode($from[0]->personal);
				$return[$j][$i]['from']['name'] = $tmp[0]->text;
				$return[$j][$i]['from']['email'] = $from[0]->mailbox . "@" . $from[0]->host;
				$return[$j][$i]['from']['full'] ='"' . $return[$j][$i]['from']['name'] . '" ' . '<' . $return[$j][$i]['from']['email'] . '>';

				$to = $header->to;
				$return[$j][$i]['to'] = array();
				$tmp = imap_mime_header_decode($to[0]->personal);
				$return[$j][$i]['to']['name'] = $tmp[0]->text;
				$return[$j][$i]['to']['email'] = $to[0]->mailbox . "@" . $to[0]->host;
				$return[$j][$i]['to']['full'] ='"' . $return[$i]['to']['name'] . '" ' . '<' . $return[$i]['to']['email'] . '>';

				$subject = imap_mime_header_decode($header->fetchsubject);
				$return[$j][$i]['subject'] = '';
				foreach ($subject as $tmp)
					$return[$j][$i]['subject'] .= $tmp->text;

				$return[$j][$i]['Size'] = $header->Size;
				$return[$j][$i]['reply_toaddress'] = $this->decode_string($header->reply_toaddress);

				$return[$j][$i]['attachment'] = array();
				$return[$j][$i]['attachment'] = $imap_attachment->get_attachment_headerinfo($mbox_stream, $msg_number);

				$i++;
			}
			$j++;
			if($mbox_stream)
				$this->close_mbox($mbox_stream);
		}

		return $return;
	}


	function mobile_search($params)
	{
		include("class.imap_attachment.inc.php");
		$imap_attachment = new imap_attachment();
		$criterias = array ("TO","SUBJECT","FROM","CC");
		$return = array();
		if(!isset($params['folder'])) {
			$folder_params = array("noSharedFolders"=>1);
			if(isset($params['folderType']))
				$folder_params['folderType'] = $params['folderType'];
			$folders = $this->get_folders_list($folder_params);
		}
		else
			$folders = array(0=>array('folder_id'=>$params['folder']));
		$num_msgs = 0;
		$max_msgs = $params['max_msgs'] + 1; //get one more because mobile paginate
		$return["msgs"] = array();
		
		//get max_msgs of each folder order by date and later order all messages together and retur only max_msgs msgs
		foreach($folders as $id =>$folder)
		{
			if(strpos($folder['folder_id'],'user')===false && is_array($folder)) {
				foreach($criterias as $criteria_fixed)
				{
					$_filter = $criteria_fixed . ' "'.$params['filter'].'"';

					$mbox_stream = $this->open_mbox($folder['folder_id']);

					$messages = imap_sort($mbox_stream,SORTARRIVAL,1,SE_UID,$_filter);
					
					if ($messages == ''){
						if($mbox_stream)
							$this->close_mbox($mbox_stream);
						continue;	
					}
					
					foreach($messages as $msg_number)
					{
						$temp = $this->get_info_head_msg($msg_number);
						if(!$temp)
							return false;
						$temp['msg_folder'] = $folder['folder_id'];
						$return["msgs"][$num_msgs] = $temp;
						$num_msgs++;
					}

					if($mbox_stream)
						$this->close_mbox($mbox_stream);
				}
			}
		}

		if(!function_exists("cmp_date")) {
			function cmp_date($obj1, $obj2){
		    if($obj1['timestamp'] == $obj2['timestamp']) return 0;
		    return ($obj1['timestamp'] < $obj2['timestamp']) ? 1 : -1;
			}
		}
		usort($return["msgs"], "cmp_date");
		$return["has_more_msg"] = (sizeof($return["msgs"]) > $max_msgs);
		$return["msgs"] = array_slice($return["msgs"], 0, $max_msgs);
		$return["msgs"]['num_msgs'] = $num_msgs;
		
		return $return;
	}

	function delete_and_show_previous_message($params)
	{
		$return = $this->get_info_previous_msg($params);

		$params_tmp1 = array();
		$params_tmp1['msgs_to_delete'] = $params['msg_number'];
		$params_tmp1['folder'] = $params['msg_folder'];
		$return_tmp1 = $this->delete_msg($params_tmp1);

		$return['msg_number_deleted'] = $return_tmp1;

		return $return;
	}
	
	function clean_folder( $params )
	{
		$error_msg = function( &$that ) {
			return array(
				'status' => false,
				'message' => utf8_encode( $that->functions->getLang( 'An unknown error occurred. The operation could not be completed.' ) )
			);
		};
		
		// Verify migrate MB
		$db = new db_functions();
		if( $db->getMigrateMailBox() ) return $error_msg( $this );
		
		// Check profile loaded
		if ( strlen( $this->imap_delimiter ) !== 1 ) return $error_msg( $this );
		
		// Check type parameter
		if ( ( !isset( $params['type'] ) || ( $params['type'] !== 'trash' && $params['type'] !== 'spam' ) ) ) return $error_msg( $this );
		
		// Check days parameter
		if ( isset( $params['days'] ) && ( (int)$params['days'] ) < 1 ) return $error_msg( $this );
		
		// Check folder name exists in session
		$field ='imapDefault'.ucfirst($params['type']).'Folder';
		if ( !isset( $_SESSION['phpgw_info']['expressomail']['email_server'][$field] ) ) return $error_msg( $this );
		
		// Check minimum folder name
		$folder = trim( $_SESSION['phpgw_info']['expressomail']['email_server'][$field] );
		if ( strlen( $folder ) < 1 ) return $error_msg( $this );
		
		// Get full mailbox string path
		$mailbox = '{'.$this->imap_server.':'.$this->imap_port.$this->imap_options.'}INBOX'.$this->imap_delimiter.$folder;
		$result = array( 'status' => false );
		
		// Open mailbox
		$mbox = $this->open_mbox( 'INBOX'.$this->imap_delimiter.$folder );
		
		// Check open mailbox errors
		if ( ( $error = imap_errors() ) && ( $error = array_filter( $error, function( $v ){ return !preg_match( '/^SECURITY PROBLEM/i', $v ); } ) ) ) {
			foreach ( $error as $key => $value ) {
				if ( preg_match( '/Mailbox does not exist/i', $value ) ) {
					unset( $error[$key] );
					imap_createmailbox ( $mbox, $mailbox );
				}
			}
			if ( count( $error ) ) return $error_msg( $this );
		}
		
		// Free others requests
		session_write_close();
		
		// Get total messages
		$before = imap_status( $mbox, $mailbox, SA_MESSAGES );
		if ( $before->messages == 0 ) {
			if ( !isset( $params['days'] ) ) $result['message'] = utf8_encode( $this->functions->getLang( 'No messages' ) );
			return $result;
		}
		
		// Select messages to delete
		if ( !isset( $params['days'] ) ) imap_delete( $mbox, '1:*' );
		else {
			$messages = imap_search( $mbox, 'BEFORE "'.date( 'm/d/Y', strtotime( '-'.((int)$params['days']).' day' ) ).'"', SE_UID );
			if ( is_array( $messages ) ) {
				foreach ( $messages as $msg_number )
					imap_delete( $mbox, $msg_number, FT_UID );
			}
		}
		
		// Expunge messages
		imap_expunge( $mbox );
		
		$after = imap_status( $mbox, $mailbox, SA_MESSAGES | SA_UNSEEN );
		
		// close mailbox
		if ( $mbox ) $this->close_mbox( $mbox );
		$deleted = ( $before->messages - $after->messages );
		$result['status'] = ( $deleted > 0 );
		if ( $deleted > 0 ) {
			$sg = ($deleted == 1);
			$result['message'] = utf8_encode( ( $sg? $this->functions->getLang( 'One' ) : $deleted ).' '.$this->functions->getLang( 'message'.($sg?' was':'s were').' deleted from the '.$params['type'].' folder' ) );
			$result['folder']  = 'INBOX'.$this->imap_delimiter.$folder;
			$result['tot_m']   = $after->messages;
			$result['new_m']   = $after->unseen;
		} else if ( !isset( $params['days'] ) ) $result['message'] = utf8_encode( $this->functions->getLang( 'No messages' ) );
		return $result;
	}

	// Fix the search problem with special characters!!!!
	function remove_accents( $string )
	{
		return str_replace( '?', '', iconv( mb_detect_encoding( $string, 'UTF-8, ISO-8859-1', true ), 'ASCII//TRANSLIT//IGNORE', $string ) );
	}

	function make_search_date($date){

		//TODO: Adaptar a data de acordo com o locale do sistema.
		list($day,$month,$year) = explode("/", $date);
		$before?$day=(int)$day+1:$day=(int)$day;
		$timestamp = mktime(0,0,0,(int)$month,$day,(int)$year);
		$search_date = date('d-M-Y',$timestamp);
		return $search_date;

	}

	function search_msg( $params = false )
	{
		$mbox_stream = "";
		$retorno = array();
		
		if(strpos($params['condition'],"#")===false)
		{ //local messages
			$search=false;
		}
		else
		{
			$search = explode(",",$params['condition']);
		}
		
		$params['page'] = $params['page'] * 1;

	    if( is_array($search) )
	    {
			$search = array_unique($search); // Remove duplicated folders
			$search_criteria = '';
			$search_result_number = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['search_result_number'];
			foreach($search as $tmp)
			{
				$tmp1 = explode("##",$tmp);
				$sum = 0;
				$folder = $this->_toUTF8( $tmp1[0] );
				unset($filter);
				foreach($tmp1 as $index => $criteria)
				{
					if ($index != 0 && strlen($criteria) != 0)
					{
						$filter_array = explode("<=>",$criteria);
						if ( !isset( $filter ) ) $filter = '';
						$filter .= " ".$filter_array[0];
						if (strlen($filter_array[1]) != 0)
						{
							if ( trim($filter_array[0]) != 'BEFORE' &&
								 trim($filter_array[0]) != 'SINCE' &&
								 trim($filter_array[0]) != 'ON')
							{
							    $filter .= '"'.$filter_array[1].'"';
							}else if(trim($filter_array[0]) == 'BEFORE' ){
			                                    $filter .= '"'.$this->make_search_date($filter_array[1],true).'"';
							}else{
								$filter .= '"'.$this->make_search_date($filter_array[1]).'"';
							}
						}
					}
				}
				
				$mailbox = $this->_toMaibox( $folder );
				$filter = $this->remove_accents($filter);

				//Este bloco tem a finalidade de transformar o login (quando numerico) das pastas compartilhadas em common name
				if ($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['uid2cn'] && substr($mailbox,0,4) == 'user')
				{
					$folder_name = explode($this->imap_delimiter,$mailbox);
					$this->ldap = new ldap_functions();
					
					if ($cn = $this->ldap->uid2cn($folder_name[1]))
					{
						$folder_name[1] = $cn;
					}
					$mailbox = implode($this->imap_delimiter,$folder_name);
				}
				
				$mbox_stream = $this->open_mbox( $folder );
				
				if (preg_match("/^.?\bALL\b/", $filter))
				{ 
					// Quick Search, note: this ALL isn't the same ALL from imap_search
					$all_criterias = array ("TO","SUBJECT","FROM","CC");
					    
					foreach($all_criterias as $criteria_fixed)
					{
						$_filter = $criteria_fixed . substr($filter,4);
						
						$search_criteria = imap_search($mbox_stream, $_filter, SE_UID);
						
						if(is_array($search_criteria))
						{
							foreach($search_criteria as $new_search)
							{
								$elem = $this->get_msg_detail($new_search,$mailbox,$mbox_stream); 
								$elem['boxname'] = $folder;
								$elem['uid'] = $new_search;
								/* compare dates in ordering */
								$elem['udatecomp'] = substr ($elem['udate'], -4) ."-". substr ($elem['udate'], 3, 2) ."-". substr ($elem['udate'], 0, 2);
								$retorno[] = $elem; 
							}
						}
					}
				}				
				else {
                	$search_criteria = imap_search($mbox_stream, $filter, SE_UID);
					if($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_important_flag'])
					{
                    	if((!strpos($filter,"FLAGGED") === false) || (!strpos($filter,"UNFLAGGED") === false))
						{
                    		$num_msgs = imap_num_msg($mbox_stream);
							$flagged_msgs = array();
							for ($i=$num_msgs; $i>0; $i--)
							{
								$iuid = @imap_uid($this->mbox,$i);
								$header = $this->get_header($iuid);								
								if(trim($header->Flagged))
								{
									$flagged_msgs[$i] = $iuid;
								}
							}
							if((count($flagged_msgs) >0) && (strpos($filter,"UNFLAGGED") === false))
							{
								$arry_diff = is_array($search_criteria) ? array_diff($flagged_msgs,$search_criteria):$flagged_msgs;
								foreach($arry_diff as $msg)
								{
									$search_criteria[] = $msg;
								}
							}
							else if((count($flagged_msgs) >0) && (is_array($search_criteria)) && (!strpos($filter,"UNFLAGGED") === false))
							{
								$search_criteria = array_diff($search_criteria,$flagged_msgs);
							}
                    	}
					}
                    if( is_array( $search_criteria) )
					{
						foreach($search_criteria as $new_search)
						{
							$elem = $this->get_msg_detail($new_search,$mailbox,$mbox_stream); 
							$elem['boxname'] = $folder;
							$elem['uid'] = $new_search;
							/* compare dates in ordering */
							$elem['udatecomp'] = substr ($elem['udate'], -4) ."-". substr ($elem['udate'], 3, 2) ."-". substr ($elem['udate'], 0, 2);
							$retorno[] = $elem;
						}
					}
                }
			}
		}
		
		if($mbox_stream)
		{
			$this->close_mbox($mbox_stream);
	    }
	    
	    $num_msgs = count($retorno);

	    /* Comparison functions, descendent is ascendent with parms inverted */
	    function SORTDATE($a, $b){ return ($a['udatecomp'] < $b['udatecomp']); }
	    function SORTDATE_REVERSE($b, $a) { return SORTDATE($a,$b); }

	    function SORTWHO($a, $b) { return (strtoupper($a['from']) > strtoupper($b['from'])); }
	    function SORTWHO_REVERSE($b, $a) { return SORTWHO($a,$b); }

	    function SORTSUBJECT($a, $b) { return (strtoupper($a['subject']) > strtoupper($b['subject'])); }
	    function SORTSUBJECT_REVERSE($b, $a) { return SORTSUBJECT($a,$b); }

	    function SORTBOX($a, $b) { return ($a['boxname'] > $b['boxname']); }
	    function SORTBOX_REVERSE($b, $a) { return SORTBOX($a,$b); }

	    function SORTSIZE($a, $b) { return ($a['size'] > $b['size']); }
	    function SORTSIZE_REVERSE($b, $a) { return SORTSIZE($a,$b); }

	    usort( $retorno, $params['sort_type']);
	    $pageret = array_slice( $retorno, $params['page'] * $this->prefs['max_email_per_page'], $this->prefs['max_email_per_page']);

		$arrayRetorno['num_msgs'] = $num_msgs;
		$arrayRetorno['data']     = $pageret;

		return $pageret? $arrayRetorno : 'none';
	}

	function get_msg_detail($uid_msg,$name_box, $mbox_stream )
	{
		$header = $this->get_header($uid_msg);
		require_once("class.imap_attachment.inc.php");
		$imap_attachment = new imap_attachment();
		$attachments =  $imap_attachment->get_attachment_headerinfo($mbox_stream, $uid_msg);
		$attachments = $attachments['number_attachments'] > 0?"T".$attachments['number_attachments']:"";
		$flag = $header->Unseen
			.$header->Recent
			.$header->Flagged
			.$header->Draft
			.$header->Answered
			.$header->Deleted
			.$attachments;


		$subject = $this->decode_string($header->fetchsubject);
		$from = $header->from[0]->mailbox;
		if($header->from[0]->personal != "")
			$from = $header->from[0]->personal;
		$ret_msg['from'] 	= $this->decode_string($from); 
		$ret_msg['subject']	= $subject; 
		$ret_msg['udate'] 	= gmdate("d/m/Y",$header->udate + $this->functions->CalculateDateOffset()); 
		$ret_msg['size'] 	= $header->Size; 
		$ret_msg['flag'] 	= $flag; 
		return $ret_msg;
	}


	function size_msg($size){
		$var = floor($size/1024);
		if($var >= 1){
			return $var." kb";
		}else{
			return $size ." b";
		}
	}
	
	function ob_array($the_object)
	{
	   $the_array=array();
	   if(!is_scalar($the_object))
	   {
	       foreach($the_object as $id => $object)
	       {
	           if(is_scalar($object))
	           {
	               $the_array[$id]=$object;
	           }
	           else
	           {
	               $the_array[$id]=$this->ob_array($object);
	           }
	       }
	       return $the_array;
	   }
	   else
	   {
	       return $the_object;
	   }
	}

	function getacl()
	{
		$this->ldap = new ldap_functions();

		$return = array();
		$mbox_stream = $this->open_mbox();

		if( is_resource($mbox_stream) ){
			$mbox_acl = @imap_getacl($mbox_stream, 'INBOX');
			$i = 0;
			foreach ($mbox_acl as $user => $acl)
			{
				if ($user != $this->username)
				{
					$return[$i]['uid'] = $user;
					$return[$i]['cn'] = $this->ldap->uid2cn($user);
				}
				$i++;
			}
		}
		return $return;
	}

	function setacl($params)
	{
		$old_users = $this->getacl();
		if (!count($old_users))
			$old_users = array();

		$tmp_array = array();
		foreach ($old_users as $index => $user_info)
		{
			$tmp_array[$index] = $user_info['uid'];
		}
		$old_users = $tmp_array;

		$users = unserialize($params['users']);
		if (!count($users))
			$users = array();

		//$add_share = array_diff($users, $old_users);
		$remove_share = array_diff($old_users, $users);

		$mbox_stream = $this->open_mbox();

		$serverString = "{".$this->imap_server.":".$this->imap_port.$this->imap_options."}";
		$mailboxes_list = imap_getmailboxes($mbox_stream, $serverString, "user".$this->imap_delimiter.$this->username."*");

    if (count($remove_share))
    {
        foreach ($remove_share as $index=>$uid)
        {
            if (is_array($mailboxes_list))
            {
                foreach ($mailboxes_list as $key => $val)
                {
                    $folder = str_replace($serverString, "", $val->name);
                    imap_setacl ($mbox_stream, $folder, "$uid", "");
                }
            }
        }
    }

		return true;
	}

	function getaclfromuser($params)
	{
		$useracl = $params['user'];

		$return = array();
		$return[$useracl] = 'false';
		$mbox_stream = $this->open_mbox();
		if( is_resource($mbox_stream) ){
			$mbox_acl = @imap_getacl($mbox_stream, 'INBOX');
			foreach ($mbox_acl as $user => $acl)
			{
				if (($user != $this->username) && ($user == $useracl)){ $return[$user] = $acl; }
			}
		}
		return $return;
	}

	function getacltouser($user)
	{
		$mbox_stream = $this->open_mbox();
		//Alterado, antes era 'imap_getacl($mbox_stream, 'user'.$this->imap_delimiter.$user);
		//Afim de tratar as pastas compartilhadas, verificandos as permissoes de operacao sobre as mesmas
		//No caso de se tratar da caixa do proprio usuario logado, utiliza a sintaxe abaixo
		$mbox_acl = array();
		if( is_resource($mbox_stream)){
			$mbox_acl = ( (substr($user,0,4) != 'user') ? 
											@imap_getacl($mbox_stream, 'user'.$this->imap_delimiter.$user) :
												 @imap_getacl($mbox_stream, $user) );
		}
		return ( isset($mbox_acl[$this->username]) ? $mbox_acl[$this->username] : false );
	}


	function setaclfromuser($params)
	{
		$user = trim($params['user']);
		$acl = trim($params['acl']);
		$acl = ( $acl === "none" ? "" : $acl );

		$mbox_stream = $this->open_mbox();

		$serverString = "{".$this->imap_server.":".$this->imap_port.$this->imap_options."}";
		$mailboxes_list = imap_getmailboxes($mbox_stream, $serverString, "user".$this->imap_delimiter.$this->username."*");

		if (is_array($mailboxes_list))
		{
			foreach ($mailboxes_list as $key => $val)
			{
				$folder = str_replace($serverString, "", imap_utf7_encode($val->name));
				$folder = str_replace("&-", "&", $folder);
				if (!imap_setacl ($mbox_stream, $folder, $user, $acl))
				{
					$return = imap_last_error();
				}
			}
		}
		if (isset($return))
			return $return;
		else
			return true;
	}

	function download_attachment($msg,$msgno)
	{
		$array_parts_attachments = array();
		//$array_parts_attachments['names'] = '';
		include_once("class.imap_attachment.inc.php");
		$imap_attachment = new imap_attachment();

		if (count($msg->fname[$msgno]) > 0)
		{
			$i = 0;
			foreach ($msg->fname[$msgno] as $index=>$fname)
			{
				$array_parts_attachments[$i]['pid'] = $msg->pid[$msgno][$index];
				$array_parts_attachments[$i]['name'] = $imap_attachment->flat_mime_decode($this->decode_string($fname));
				$array_parts_attachments[$i]['name'] = $array_parts_attachments[$i]['name'] ? $array_parts_attachments[$i]['name'] : "attachment.bin";
				$array_parts_attachments[$i]['encoding'] = $msg->encoding[$msgno][$index];
				//$array_parts_attachments['names'] .= $array_parts_attachments[$i]['name'] . ', ';
				$array_parts_attachments[$i]['fsize'] = $msg->fsize[$msgno][$index];
				$i++;
			}
		}
		//$array_parts_attachments['names'] = substr($array_parts_attachments['names'],0,(strlen($array_parts_attachments['names']) - 2));
		return $array_parts_attachments;
	}

	function spam( $params )
	{
		$result = array( 'status' => true );
		try {
			if ( !( isset( $params['folders'] ) && isset( $params['spam'] ) ) )
				throw new Exception( 'parameters not found' );
			
			$url = isset( $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_url'] )?
				$_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_url'] : false;
			if ( !$url ) throw new Exception( 'span url not found' );
			
			$def_spam = isset( $_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'] )?
				$_SESSION['phpgw_info']['expressomail']['email_server']['imapDefaultSpamFolder'] : false;
			if ( !$def_spam ) throw new Exception( 'span default folder not found' );
			
			$fields      = isset( $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_fields'] )?
				explode( ':', $_SESSION['phpgw_info']['server']['expressomail']['expressoMail_spam_fields'] ) : array();
			
			$folders    = (array)$params['folders'];
			$is_spam    = $params['spam'] === 'true';
			$def_folder = $is_spam? 'INBOX'.$this->imap_delimiter.$def_spam : 'INBOX';
			
			if ( ( !$is_spam ) && count( $folders ) != 1  && !isset( $folders['INBOX'.$this->imap_delimiter.$def_spam] ) )
				throw new Exception( 'action not allow' );
			
			foreach ( (array)$params['folders'] as $mbox => $msg_numbers ) {
				
				// Clear previous errors buffer
				imap_errors();
				
				$mbox_stream = $this->open_mbox( $mbox );
				if ( ( $error = imap_errors() ) && ( $error = array_filter( $error, function( $v ){ return !preg_match( '/^SECURITY PROBLEM/i', $v ); } ) ) )
					throw new Exception( implode( "\n", $error ) );
				
				foreach ( (array)$msg_numbers as $msg_number ) {
					
					// Clear previous errors buffer
					imap_errors();
					
					// Get message number
					$msgno = imap_msgno( $mbox_stream, $msg_number );
					if ( ( $error = imap_errors() ) && ( $error = array_filter( $error, function( $v ){ return !preg_match( '/^SECURITY PROBLEM/i', $v ); } ) ) )
						throw new Exception( implode( "\n", $error ) );
					
					// Parse header mail
					$hdr = imap_fetchheader( $mbox_stream, $msgno );
					$header = array_change_key_case( iconv_mime_decode_headers( $hdr, 0, 'ISO-8859-1' ) );
					
					// Defaults fields from system
					$post = array(
						'email'    => $_SESSION['phpgw_info']['expressomail']['user']['email'],
						'username' => $this->username,
						'is_spam'  => $is_spam? 'spam' : 'innocent',
						'md5'      => md5( $hdr ),
					);
					if ( $is_spam && $mbox !== $def_folder ) $post['folder'] = $mbox;
					
					// User fiels from mail
					foreach ( $fields as $field ) {
						$field = strtolower( trim( $field ) );
						if ( isset( $header[$field] ) ) {
							if ( is_array( $header[$field] ) ) $header[$field] = implode( "\n", $header[$field] );
							switch ( $field ) {
								case 'date':
									$post[$field] = preg_replace( '/ *\(.*/', '', $header[$field] );
									break;
								default: $post[$field] = $header[$field];
							}
							if ( strlen( $post[$field] ) == 0 ) unset( $post[$field] );
						}
					}
					
					// Call external url
					$ch = curl_init();
					curl_setopt_array( $ch, array(
						CURLOPT_URL            => $url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_SSL_VERIFYHOST => 0,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_POST           => true,
						CURLOPT_POSTFIELDS     => http_build_query( $post ),
					) );
					$response = json_decode( curl_exec( $ch ) );
					if ( curl_errno( $ch ) ) throw new Exception( curl_error( $ch ) );
					curl_close( $ch );
					
					if ( !( isset( $response->status ) && $response->status ) )
						throw new Exception( isset( $response->message )? $response->message : 'dspam error' );
					
					$dest = ( ( !$is_spam ) && isset( $response->folder ) )? $response->folder : $def_folder;
					
					if ( !isset( $result['spam_result'][$dest] ) ) {
						$name = array_pop( explode( $this->imap_delimiter, $dest ) );
						$result['spam_result'][$dest]['name'] = ( ( $name == 'INBOX' )? $this->functions->getLang( 'Inbox' ) : $name );
					}
					
					if ( !isset( $result['spam_result'][$dest]['orig'][$mbox] ) )
						$result['spam_result'][$dest]['orig'][$mbox] = $msg_number;
					else $result['spam_result'][$dest]['orig'][$mbox] .= ','.$msg_number;
				}
				$this->close_mbox( $mbox_stream );
			}
		} catch ( Exception $e ) {
			if ( isset( $ch ) && $ch ) curl_close( $ch );
			if ( isset( $mbox_stream ) && $mbox_stream ) $this->close_mbox( $mbox_stream );
			$result['status'] = false;
			$result['message'] = $e->getMessage();
		}
		return $result;
	}
	function get_header( $msg_number )
	{
		$header = @imap_headerinfo( $this->mbox, imap_msgno( $this->mbox, $msg_number ), 80, 255 );
		if ( !is_object( $header ) ) return false;
		$header = $this->array_map_recursive( array( $this, '_str_decode' ), $header );
		
		$use_important_flag =
			isset($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_important_flag']) &&
			$_SESSION['phpgw_info']['user']['preferences']['expressoMail']['use_important_flag'];
		
		if ( $header->Flagged != 'F' && $use_important_flag ) {
			$flag = preg_match(
				'/importance *: *(.*)\r/i',
				imap_fetchheader( $this->mbox, imap_msgno( $this->mbox, $msg_number ) ),
				$importance
			);
			$header->Flagged = ( $flag == 0)? false : ( ( strtolower( $importance[1] ) == 'high' )? 'F' : false );
		}
		return $header;
	}

	private function array_map_recursive( $callback, $obj )
	{
		switch ( gettype( $obj ) ) {
			case 'array' : foreach ( $obj as $key => $value ) $obj[$key]   = $this->array_map_recursive( $callback, $value ); return $obj;
			case 'object': foreach ( $obj as $key => $value ) $obj->{$key} = $this->array_map_recursive( $callback, $value ); return $obj;
		}
		return call_user_func( $callback, $obj );
	}

//Por Bruno Costa(bruno.vieira-costa@serpro.gov.br - Insere emails no imap a partir do fonte do mesmo. Se o argumento timestamp for passado ele utiliza do script python
///expressoMail1_2/imap.py para inserir uma msg com o horario correto pois isso nao e porssivel com a funcao imap_append do php.

    function insert_email($source,$folder,$timestamp,$flags){
        $username = $_SESSION['phpgw_info']['expressomail']['user']['userid'];
        $password = $_SESSION['phpgw_info']['expressomail']['user']['passwd'];
        $imap_server = $_SESSION['phpgw_info']['expressomail']['email_server']['imapServer'];
        $imap_port 	= $_SESSION['phpgw_info']['expressomail']['email_server']['imapPort'];
        $imap_options = '/notls/novalidate-cert';
        $mbox_stream = imap_open("{".$imap_server.":".$imap_port.$imap_options."}".$folder, $username, $password);
        if(imap_last_error())
        {
            imap_createmailbox($mbox_stream,imap_utf7_encode("{".$imap_server."}".$folder));
       	}
        if($timestamp){
			$temp_dir = empty($GLOBALS['phpgw_info']['server']['temp_dir'])? '/tmp' : $GLOBALS['phpgw_info']['server']['temp_dir'];
            $file = $temp_dir."/imap_".$_SESSION[ 'phpgw_session' ][ 'session_id' ];
    		$f = fopen($file,"w");
        	fputs($f,base64_encode($source));
            fclose($f);
            $command = "python ".$_SERVER['DOCUMENT_ROOT']."/expressoMail1_2/imap.py ".escapeshellarg($imap_server)." ".escapeshellarg($imap_port)." ".escapeshellarg($username)." ".escapeshellarg($password)." ".escapeshellarg($timestamp)." ".escapeshellarg($folder)." ".escapeshellarg($file);
            $return['command']=exec(escapeshellcmd($command));
        }else{
            $return['append'] = imap_append($mbox_stream, "{".$imap_server.":".$imap_port."}".$folder, $source, "\\Seen");
        }
        $status = imap_status($mbox_stream, "{".$this->imap_server.":".$this->imap_port."}".$folder, SA_UIDNEXT);
			
        $return['msg_no'] = $status->uidnext - 1;
	$return['error'] = imap_last_error();
	if(!$return['error'] && $flags != '' ){

                  $flags_array=explode(':',$flags);
                  //"Answered","Draft","Flagged","Unseen"
                  $flags_fixed = "";
                  if($flags_array[0] == 'A')
                        $flags_fixed.="\\Answered ";
                  if($flags_array[1] == 'X')
                        $flags_fixed.="\\Draft ";
                  if($flags_array[2] == 'F')
                        $flags_fixed.="\\Flagged ";
                  if($flags_array[3] != 'U')
                        $flags_fixed.="\\Seen ";

                  imap_setflag_full($mbox_stream, $return['msg_no'], $flags_fixed, ST_UID);
                }
        if($mbox_stream)
            $this->close_mbox($mbox_stream);
        return $return;
    }

    function show_decript($params){
        $source = $params['source'];
        $source = str_replace(" ", "+", $source,$i);

        if (version_compare(PHP_VERSION, '5.2.0', '>=')){
            if(!$source = base64_decode($source,true))
                return "error ".$source."Espacos ".$i;

        }
        else {
            if(!$source = base64_decode($source))
                return "error ".$source."Espacos ".$i;
        }

        $insert = $this->insert_email($source,'INBOX'.$this->imap_delimiter.'decifradas');

		$get['msg_number'] = $insert['msg_no'];
		$get['msg_folder'] = 'INBOX'.$this->imap_delimiter.'decifradas';
		$return = $this->get_info_msg($get);
		$get['msg_number'] = $params['ID'];
		$get['msg_folder'] = $params['folder'];
		$tmp = $this->get_info_msg($get);
		if(!$tmp['status_get_msg_info'])
		{
			$return['msg_day']=$tmp['msg_day'];
			$return['msg_hour']=$tmp['msg_hour'];
			$return['fulldate']=$tmp['fulldate'];
			$return['smalldate']=$tmp['smalldate'];
		}
		else
		{
			$return['msg_day']='';
			$return['msg_hour']='';
			$return['fulldate']='';
			$return['smalldate']='';
		}
        $return['msg_no'] =$insert['msg_no'];
        $return['error'] = $insert['error'];
        $return['folder'] = $params['folder'];
        //$return['acls'] = $insert['acls'];
        $return['original_ID'] =  $params['ID'];

        return $return;

    }

//Por Bruno Costa(bruno.vieira-costa@serpro.gov.br - Trata fontes de emails enviados via POST para o servidor por um xmlhttprequest, as partes codificados com
//Base64 os "+" sao substituidos por " " no envio e essa funcao arruma esse efeito.

    function treat_base64_from_post($source){
            $offset = 0;
            do
            {
                    if($inicio = strpos($source, 'Content-Transfer-Encoding: base64', $offset))
                    {
                            $inicio = strpos($source, "\n\r", $inicio);
                            $fim = strpos($source, '--', $inicio);
                            if(!$fim)
                                    $fim = strpos($source,"\n\r", $inicio);
                            $length = $fim-$inicio;
                            $parte = substr( $source,$inicio,$length-1);
                            $parte = str_replace(" ", "+", $parte);
                            $source = substr_replace($source, $parte, $inicio, $length-1);
                    }
                    if($offset > $inicio)
                    $offset=FALSE;
                    else
                    $offset = $inicio;
            }
            while($offset);
            return $source;
    }

	public function __destruct()
	{
		$this->close_mbox( $this->mbox );
	}

	private function _str_decode( $str, $charset = false )
	{
		$int_encoding = mb_internal_encoding();
		
		mb_internal_encoding("UTF-8");

		if ( preg_match( '/=\?[\w-#]+\?[BQ]\?[^?]*\?=/', $str ) ) $str = mb_decode_mimeheader( $str );

		mb_internal_encoding($int_encoding);

		return $this->_toUTF8( $str, $charset );
	}

	private function _toMaibox( $folder )
	{
		return $this->_toUTF8( $folder, false, 'UTF7-IMAP' );
	}

	private function _toUTF8( $str, $charset = false, $to = 'UTF-8' )
	{
		return mb_convert_encoding( $str, $to, ( $charset === false? mb_detect_encoding( $str, 'UTF-8, ISO-8859-1', true ) : $charset ) );
	}
}
