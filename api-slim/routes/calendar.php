<?php

$app = \Slim\Slim::getInstance();

// Calendar/Resources
$app->container->set('AddEventResource', function(){ return new App\Modules\Calendar\AddEventResource(); });
$app->container->set('EventResource', function(){ return new App\Modules\Calendar\EventResource(); });
$app->container->set('EventsResource', function(){ return new App\Modules\Calendar\EventsResource(); });
$app->container->set('DelEventResource', function(){ return new App\Modules\Calendar\DelEventResource(); });
$app->container->set('EventCategoriesResource', function(){ return new App\Modules\Calendar\EventCategoriesResource(); });
$app->container->set('EventImportResource', function(){ return new App\Modules\Calendar\EventImportResource(); });

// Calendar routes
$app->group('/Calendar', function() use($app){

    $app->post('/AddEvent', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->AddEventResource->post($params));
    });

    $app->post('/Event', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->EventResource->post($params));
    });

    $app->post('/Events', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->EventsResource->post($params));
    });

    $app->post('/DelEvent', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->DelEventResource->post($params));
    });

    $app->post('/EventCategories', function() use($app){
        $params = $app->Request->getParams( $app->request );
        $app->Response->write( $app, $app->EventCategoriesResource->post($params)); 
    });

});

// VCalendar
$app->get('/vcalendar/import', function() use($app){
    $params = $app->Request->getParams( $app->request );
    $app->Response->write( $app, $app->EventImportResource->get($params));
});
