<?php

header ( "Access-Control-Allow-Origin: *" );

require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

$app = new \Slim\Slim();

// Middleware Authenticate
$app->add( new \App\Middleware\AuthMiddleware() );

// Requests
$app->container->set('Request', function(){  return new App\Request(); });

// Response
$app->container->set('Response', function(){ return new App\Response(); });

// Init
$app->get('/',function() use($app){ $app->Response->write( $app , '.api.expresso.' ); });

require_once dirname( __FILE__ ) . '/../routes/admin.php';

require_once dirname( __FILE__ ) . '/../routes/calendar.php';

require_once dirname( __FILE__ ) . '/../routes/catalog.php';

require_once dirname( __FILE__ ) . '/../routes/core.php';

require_once dirname( __FILE__ ) . '/../routes/mail.php';

require_once dirname( __FILE__ ) . '/../routes/preferences.php';

require_once dirname( __FILE__ ) . '/../routes/services.php';

require_once dirname( __FILE__ ) . '/../routes/sms.php';

$app->notFound(function(){ echo "--| NOT FOUND |--"; });

$app->run();
