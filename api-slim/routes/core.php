<?php

$app = \Slim\Slim::getInstance();

// Core/Resources
$app->container->set('LoginResource', function(){ return new App\Modules\Core\LoginResource(); });
$app->container->set('LogoutResource', function(){ return new App\Modules\Core\LogoutResource(); });
$app->container->set('UserApiResource', function(){ return new App\Modules\Core\UserApiResource(); });
$app->container->set('ExpressoVersionResource', function(){ return new App\Modules\Core\ExpressoVersionResource(); });

// Core
$app->post('/Login',function() use($app){
    $params = $app->Request->getParams( $app->request );
    $app->Response->write( $app , $app->LoginResource->post( $params ));
})->name('Login');

$app->post('/Logout',function() use($app){
    $params = $app->Request->getParams( $app->request );
    $app->Response->write( $app, $app->LogoutResource->post( $params )); 
})->name('Logout');

$app->post('/UserApi', function() use($app){
    $params = $app->Request->getParams( $app->request );
    $app->Response->write( $app, $app->UserApiResource->post( $params )); 
});

$app->map('/ExpressoVersion', function() use($app){
    $params = $app->Request->getParams( $app->request );
    $app->Response->write( $app, $app->ExpressoVersionResource->any( $params )); 
})->via('GET','POST');
