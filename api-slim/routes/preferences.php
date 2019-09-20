<?php

$app = \Slim\Slim::getInstance();

// Preferences/Resources
$app->container->set('ChangePasswordResource', function(){ return new App\Modules\Preferences\ChangePasswordResource(); });
$app->container->set('ChangeUserPreferencesResource', function(){ return new App\Modules\Preferences\ChangeUserPreferencesResource(); });
$app->container->set('UserPreferencesResource', function(){ return new App\Modules\Preferences\UserPreferencesResource(); });

// Preferences routes
$app->group('/Preferences', function() use($app){

    $app->post('/ChangePassword', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ChangePasswordResource->post($params)); 
    });

    $app->post('/ChangeUserPreferences', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ChangeUserPreferencesResource->post($params));
    });

    $app->post('/UserPreferences', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->UserPreferencesResource->post($params)); 
    });
});
