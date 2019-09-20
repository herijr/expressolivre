<?php

$app = \Slim\Slim::getInstance();

// Catalog/Resources
$app->container->set('ContactAddResource', function(){ return new App\Modules\Catalog\ContactAddResource(); });
$app->container->set('ContactsResource', function(){ return new App\Modules\Catalog\ContactsResource(); });
$app->container->set('ContactDeleteResource', function(){ return new App\Modules\Catalog\ContactDeleteResource(); });
$app->container->set('ContactPictureResource', function(){ return new App\Modules\Catalog\ContactPictureResource(); });
$app->container->set('ContactEmailPhotoResource', function(){ return new App\Modules\Catalog\ContactEmailPhotoResource(); });

// Catalog routes
$app->group('/Catalog', function() use($app){

    $app->post('/Contacts', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ContactsResource->post( $params )); 
    });

    $app->post('/ContactAdd', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ContactAddResource->post( $params));
    });

    $app->post('/ContactDelete', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ContactDeleteResource->post( $params)); 
    });

    $app->post('/ContactPicture', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ContactPictureResource->post( $params)); 
    });

    $app->post('/Photo', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->ContactEmailPhotoResource->post( $params)); 
    });

});
