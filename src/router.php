<?php

$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware($app, [
    (new \Whoops\Handler\JsonResponseHandler())->setJsonApi(true)
]));

$app->add(new \FSEdit\DatabaseMiddleware($app));

$app->get('/users/login', FSEdit\UserController::class . ':login');
$app->get('/users/register', FSEdit\UserController::class . ':register');
$app->get('/users/logout', FSEdit\UserController::class . ':logout');
