<?php

namespace App\Middleware;

use App\Errors;
use App\Modules\Core\Authenticate;

class AuthMiddleware extends \Slim\Middleware
{
    private $auth;

    private $isResourcePublic;

    public function __construct()
    {
        $this->auth = new Authenticate();

        $this->isResourcePublic = array(
            '/',
            '/Login',
            '/UserApi',
            '/ExpressoVersion',
            '/teste',
            '/vcalendar/import',
        );
    }

    public function call()
    {
        $app = \Slim\Slim::getInstance();

        $resource = explode("/", trim($app->request->getResourceUri()), 2);

        $uri = "/" . (isset($resource[1]) ? $resource[1] : "");

        $isResourcePublic = array_search($uri, $this->isResourcePublic, true);

        if ($isResourcePublic === false) {
            $params = $app->Request->getParams($app->request);
            $result = $this->auth->isLoggedIn($params);
            if (!$result['status']) {
                die(json_encode(Errors::runException($result['msg'])));
            }
        }

        $this->next->call();
    }
}
