<?php

$app = \Slim\Slim::getInstance();

// Admin/Resources
$app->container->set('CreateUserResource', function(){ return new App\Modules\Admin\CreateUserResource(); });
$app->container->set('DeleteUserResource', function(){ return new App\Modules\Admin\DeleteUserResource(); });
$app->container->set('GetUsersResource', function(){ return new App\Modules\Admin\GetUsersResource(); });
$app->container->set('SearchLdapResource', function(){ return new App\Modules\Admin\SearchLdapResource(); });
$app->container->set('RenameUserResource', function(){ return new App\Modules\Admin\RenameUserResource(); });
$app->container->set('UpdateUserResource', function(){ return new App\Modules\Admin\UpdateUserResource(); });

// Admin routes
$app->group('/Admin', function() use($app){

    $app->post('/CreateUser', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->CreateUserResource->post($params)); 
    });

    $app->post('/DeleteUser', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->DeleteUserResource->post($params)); 
    });

    $app->post('/GetUsers', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->GetUsersResource->post($params)); 
    });

    $app->post('/SearchLdap', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SearchLdapResource->post($params)); 
    });

    $app->post('/RenameUser', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->RenameUserResource->post($params)); 
    });

    $app->post('/UpdateUser', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->UpdateUserResource->post($params)); 
    });

});
