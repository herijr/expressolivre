<?php

$app = \Slim\Slim::getInstance();

// Services/Resources
$app->container->set('ChatResource', function(){ return new App\Modules\Services\ChatResource(); });

// Services routes
$app->group('/Services', function() use($app){

    $app->post('/Chat', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ChatResource->post()); 
    });

});
