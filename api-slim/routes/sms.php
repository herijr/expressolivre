<?php

$app = \Slim\Slim::getInstance();

// SMS/Resources
$app->container->set('InfoPersonalResource', function(){ return new App\Modules\Sms\InfoPersonalResource(); });
$app->container->set('SendCheckCodeResource', function(){ return new App\Modules\Sms\SendCheckCodeResource(); });
$app->container->set('SendMessageResource', function(){ return new App\Modules\Sms\SendMessageResource(); });
$app->container->set('SubmitPersonalFormResource', function(){ return new App\Modules\Sms\SubmitPersonalFormResource(); });

// SMS routes
$app->group('/SMS', function() use($app){

    $app->post('/InfoPersonal', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->InfoPersonalResource->post( $params ));
    });

    $app->post('/SendCheckCode', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SendCheckCodeResource->post( $params )); 
    });

    $app->post('/SendMessage', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SendMessageResource->post( $params )); 
    });

    $app->post('/SubmitPersonalForm', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SubmitPersonalFormResource->post( $params )); 
    });
});
