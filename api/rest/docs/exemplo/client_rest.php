<?php

	$serverUrl	= $_REQUEST['serverUrl'];
	$methodType	= $_REQUEST['methodType'];
	$params 	= $_REQUEST['params'];
	$id 		= ( $_REQUEST['id'] ) ? $_REQUEST['id'] : time();

	$data = "id=".$id."&params=" . stripslashes($params);

	function callJSONRPC($url, $data, $method)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if($method == "POST"){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));

		$result 	= curl_exec($ch);
		$errorCode 	= curl_getinfo($ch,CURLINFO_HTTP_CODE);
		$lastURL 	= curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
		
		curl_close($ch);

		switch($errorCode)
		{
			case 200:
				break;
			case 404:
				$result = json_encode(array("error" => array("code" => 404, "message" => "RECURSO NAO ENCONTRADO => $lastURL")));
				break;
			case 500:
				$result = json_encode(array("error" => array("code" => 500, "message" => "ERRO INTERNO. CONSULTE O LOG DO SERVIDOR")));
				break;
			default:
				$result = json_encode(array("error" => array("code" => -1, "message" => "ERRO DESCONHECIDO. CONSULTE O LOG DO SERVIDOR")));
				break;
		}

		return $result;
	}

	$result = callJSONRPC($serverUrl, $data, $methodType);

	echo $result;
?>