<?php

namespace FSEdit;

use Medoo\Medoo;
use Slim\App;

class DatabaseMiddleware
{
    public function __construct(App $app)
    {
        $container = $app->getContainer();
        $container['database'] = function () use ($container) {
            return new Medoo($container->get('config')->database);
        };
    }

    public function __invoke($request, $response, $next)
    {
        return $next($request, $response);
    }

}