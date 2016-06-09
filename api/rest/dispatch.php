<?php

$version = '1.1';

$context = ( $_SERVER['PHP_SELF'] == "/dispatch.php")? "/" : $_SERVER['PHP_SELF'];

if ( !defined( 'API_VERSION' ) ) {

	$req_uri = explode( '/', ltrim( current( explode( '?',$_SERVER['REQUEST_URI'] ) ), $context ) );
	$req_ver = preg_match( '/^[0-9\.]+$/', current($req_uri) )? array_shift($req_uri) : $version;
	$forward = ( $req_ver != $version );
	
	define( 'API_VERSION', $forward? $req_ver : $version );
	define( 'API_RESOURCE', '/'.implode( '/', $req_uri ) );
	define( 'API_EXPRESSO_PATH', realpath( dirname( __FILE__ ).'/../..' ) );
	define( 'API_DIRECTORY', API_EXPRESSO_PATH.'/api/'.( $forward? 'old_versions/'.$req_ver.'/': '' ).'rest' );
	
	unset($req_uri);
	unset($req_ver);
	
	if ( $forward ) {
		unset($forward);
		if ( is_file(API_DIRECTORY.'/dispatch.php') ) include_once( API_DIRECTORY.'/dispatch.php' );
		else echo json_encode( array( 'error' => array( 'code' => '1000', 'message' => 'Invalid Version') ) );
		return;
	} else unset($forward);
}

unset($version);


// load libraries
require_once(API_DIRECTORY.'/../library/tonic/lib/tonic.php'); 
require_once(API_DIRECTORY.'/../library/utils/Errors.php');

// load adapters
require_once(API_DIRECTORY."/../adapters/ExpressoAdapter.php");
require_once(API_DIRECTORY."/../adapters/MailAdapter.php");
require_once(API_DIRECTORY."/../adapters/CatalogAdapter.php");
require_once(API_DIRECTORY."/../adapters/CalendarAdapter.php");
require_once(API_DIRECTORY."/../adapters/AdminAdapter.php" );
require_once(API_DIRECTORY."/../adapters/PreferencesAdapter.php" );
require_once(API_DIRECTORY."/../adapters/SMSAdapter.php" );
require_once(API_DIRECTORY."/../adapters/ServicesAdapter.php" );


//Retrieveing the mapping of the URIs and his respectives classNames and classPath
$config 	= parse_ini_file( API_DIRECTORY . '/../config/Tonic.srv', true );
$autoload 	= array();
$classpath 	= array();

foreach( $config as $uri => $classFile )
{
	foreach( $classFile as $className => $filePath )
	{
		$autoload[ $uri ] = $className;
		$classpath[ $className ] = $filePath;
		require_once(API_DIRECTORY . $filePath);
	}
	
}

$request = new Request(array(
	'uri' => API_RESOURCE,
	'autoload' => $autoload
));

try {
	
	$resource = $request->loadResource();
	$response = $resource->exec($request);
	
} catch (ResponseException $e) {
	switch ($e->getCode()) {
		
		case Response::UNAUTHORIZED:
			$response = $e->response($request);
			$response->addHeader('WWW-Authenticate', 'Basic realm="Tonic"');
			break;
		default:
			
			$response = new Response($request);
			$response->code = Response::OK;
			$response->addHeader('content-type', 'application/json');
			if($request->id)
				$body['id']	= $request->id;
			
			$body['error'] = array("code" => "".$e->getCode(), "message" => utf8_encode($e->getMessage()));
			$response->body = json_encode($body);
	}
}

$response->output();
