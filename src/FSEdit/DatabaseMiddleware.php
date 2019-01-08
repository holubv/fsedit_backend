<?php

namespace FSEdit;

use Slim\App;

class DatabaseMiddleware
{
    public function __construct(App $app)
    {
        $container = $app->getContainer();
        $container['database'] = new DatabaseAdapter($container->get('config')->database);
    }

    public function __invoke($request, $response, $next)
    {
        return $next($request, $response);
    }

}