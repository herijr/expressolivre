<?php

header( 'Content-Type: application/json; charset=utf-8;' );

$fconfig = dirname( __FILE__ ).'/../../../config/profileHomeServer.ini';
if ( !file_exists( $fconfig ) ) {
	echo json_encode( array( 'error' => array( 'code' => 500, 'message' => 'VERIFIQUE A CONFIGURACAO' ) ) );
	exit;
}
$config = parse_ini_file( $fconfig, true);
if ( !isset( $config['documentation']['BASE_URL'] ) ) {
	echo json_encode( array( 'error' => array( 'code' => 500, 'message' => 'VERIFIQUE A CONFIGURACAO' ) ) );
	exit;
}

			$baseURL = $config['documentation']['BASE_URL'];

$baseURL    = ( substr( $baseURL, -1 ) === '/' )? substr( $baseURL, 0, strlen( $baseURL ) - 1 ) : $baseURL;

			$serverUrl = $baseURL . $_REQUEST['serverUrl'];
			$methodType = $_REQUEST['methodType'];
$params     = isset( $_REQUEST['params'] )? $_REQUEST['params'] : '';
			$id = ($_REQUEST['id']) ? $_REQUEST['id'] : time();
$data       = 'id='.$id.'&params='.stripslashes( $params );

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

if ( $methodType === 'POST' ) {
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				}

				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt( $ch, CURLOPT_URL, $serverUrl );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded', 'Expect:', 'Accept: application/json, text/javascript, */*; q=0.01' ) );

				$result = curl_exec($ch);
				$errorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$lastURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

				curl_close($ch);

				switch ($errorCode) {
	case 200: break;
	case 404: $result = json_encode( array( 'error' => array( 'code' => 404, 'message' => 'RECURSO NAO ENCONTRADO => '.$lastURL ) ) ); break;
	case 500: $result = json_encode( array( 'error' => array( 'code' => 500, 'message' => 'ERRO INTERNO. CONSULTE O LOG DO SERVIDOR' ) ) ); break;
	default:  $result = json_encode( array( 'error' => array( 'code' =>  -1, 'message' => 'ERRO DESCONHECIDO. CONSULTE O LOG DO SERVIDOR' ) ) ); break;
				}


			echo $result;
exit;
