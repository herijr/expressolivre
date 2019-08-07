<?php
class imap_attachment
{
	function get_attachment_info($mbox_stream, $msg_number) 
	{	
		$structure = imap_fetchstructure($mbox_stream,$msg_number,FT_UID);
		$contentParts = count($structure->parts);
	
		$msg_info['number_attachments'] = $contentParts -1;
	
		if($contentParts > 1)
		{
	 		for($i=1; $i<$contentParts; $i++)
			{
				$msg_info['attachment'][$i]['part_in_msg']	= ($i+1);
				$msg_info['attachment'][$i]['name']				= $structure->parts[$i]->ifdparameters? urlencode($structure->parts[$i]->dparameters[0]->value) : '';
				$msg_info['attachment'][$i]['type']				= $structure->parts[$i]->subtype;
				$msg_info['attachment'][$i]['bytes']			= $structure->parts[$i]->bytes;
   			}
  		}
  		return $msg_info;
	}

	function get_attachment_headerinfo($mbox, $msgno)
	{
		include_once 'class.message_reader.inc.php';
		$mail_reader = new MessageReader();
		$info        = $mail_reader->setMessage( $mbox, false, $msgno )->getAttachInfo();
		return array(
			'number_attachments' => count( $info ),
			'names' => implode( ', ', array_map( function( $obj ) { return $obj->filename; }, $info ) ),
		);
	}
	
	function download_attachment($mbox, $msgno)
	{
		include_once("class.message_components.inc.php");

		$msg = new message_components($mbox);
		$msg->fetch_structure($msgno);
		$array_parts_attachments = array();		
		//$array_parts_attachments['names'] = '';
		
		//print_r($msg->fname[$msgno]);
		
		if (count($msg->fname[$msgno]) > 0)
		{
			$i = 0;
			foreach ($msg->fname[$msgno] as $index=>$fname)
			{
				$array_parts_attachments[$i]['pid'] = $msg->pid[$msgno][$index];
				$array_parts_attachments[$i]['name'] = $this->decode_mimeheader($fname);
				//$array_parts_attachments[$i]['name'] = $this->flat_mime_decode($fname);
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
	
	function download_attachmentByPid( $imap_stream, $msg_number )
	{
		$result = array();
		foreach ( $this->download_attachment( $imap_stream, $msg_number ) as $attch )
			$result[$attch['pid']] = $attch;
		return $result;
	}
	
	function decode_mimeheader($string) {
        return mb_decode_mimeheader($string);
    }
	function flat_mime_decode($string) {
	   	$array = imap_mime_header_decode($string);
	   	$str = "";
	   	foreach ($array as $key => $part)
	   		$str .= @preg_replace("/\{/i", "[",@preg_replace("/\}/i", "]",$part->text));
	   	
   		return $str;
	}
}
