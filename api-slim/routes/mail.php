<?php

$app = \Slim\Slim::getInstance();

// Mail/Resources
$app->container->set('FoldersResource', function(){ return new App\Modules\Mail\FoldersResource(); });
$app->container->set('AddFolderResource', function(){ return new App\Modules\Mail\AddFolderResource(); });
$app->container->set('RenameFolderResource', function(){ return new App\Modules\Mail\RenameFolderResource(); });
$app->container->set('DelFolderResource', function(){ return new App\Modules\Mail\DelFolderResource(); });
$app->container->set('FlagMessageResource', function(){ return new App\Modules\Mail\FlagMessageResource(); });
$app->container->set('DelMessageResource', function(){ return new App\Modules\Mail\DelMessageResource(); });
$app->container->set('MessagesResource', function(){ return new App\Modules\Mail\MessagesResource(); });
$app->container->set('CleanTrashResource', function(){ return new App\Modules\Mail\CleanTrashResource(); });
$app->container->set('MoveMessagesResource', function(){ return new App\Modules\Mail\MoveMessagesResource(); });
$app->container->set('SendResource', function(){ return new App\Modules\Mail\SendResource(); });
$app->container->set('SpamMessageResource', function(){ return new App\Modules\Mail\SpamMessageResource(); });
$app->container->set('SendSupportFeedbackResource', function(){ return new App\Modules\Mail\SendSupportFeedbackResource(); });

// Mail routes
$app->group('/Mail', function() use($app){

    $app->post('/Attachment', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $params); 
    });
    //ok
    $app->post('/Folders', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->FoldersResource->post( $params ) );
    });
    //ok
    $app->post('/AddFolder', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->AddFolderResource->post( $params ) );
    });
    //ok
    $app->post('/RenameFolder', function() use($app){
        $params = $app->Request->getParams( $app->request );        
        $app->Response->write( $app, $app->RenameFolderResource->post( $params ) );
    });
    //ok
    $app->post('/DelFolder', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->DelFolderResource->post( $params ) ); 
    });
    //ok
    $app->post('/FlagMessage', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->FlagMessageResource->post( $params ) ); 
    });
    //ok
    $app->post('/DelMessage', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->DelMessageResource->post( $params ) );  
    });
    //ok
    $app->post('/Messages', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->MessagesResource->post( $params ) );  
    });
    //ok
    $app->post('/CleanTrash', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->CleanTrashResource->post( $params ) );   
    });
    //ok
    $app->post('/MoveMessages', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->MoveMessagesResource->post( $params ) );
    });
    //ok
    $app->post('/Send', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SendResource->post( $params ) );
    });

    $app->post('/SpamMessage', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SpamMessageResource->post( $params ) );
    });

    $app->post('/SendSupportFeedback', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->SendSupportFeedbackResource->post( $params ) );
    });
});
