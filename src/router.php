<?php

$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware($app, [
    new \Whoops\Handler\JsonResponseHandler(),
    new \Whoops\Handler\PlainTextHandler(), //fixme json response handler
]));

$app->add(new \FSEdit\DatabaseMiddleware($app));
$app->add(new \FSEdit\StatusExceptionMiddleware($app));

$app->add(new \FSEdit\SessionMiddleware($app));

$app->add(function ($req, $res, $next) {
    return $next($req, $res
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Api-Token, X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control, If-None-Match')
        ->withHeader('Access-Control-Expose-Headers', 'ETag, Cache-Control, X-Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
    );
});

$app->map(['post', 'options'], '/files/upload', FSEdit\FileController::class . ':upload');
$app->put('/files/create', FSEdit\FileController::class . ':create');

$app->put('/files/move', FSEdit\FileController::class . ':move');
$app->put('/files/rename', FSEdit\FileController::class . ':rename');
$app->put('/files/edit', FSEdit\FileController::class . ':edit');
$app->post('/files/delete', FSEdit\FileController::class . ':delete');

$app->get('/file/{file:[0-9a-fA-F]+}', FSEdit\FileController::class . ':readFile');
$app->get('/file', FSEdit\FileController::class . ':readFile');

$app->post('/workspaces/create', FSEdit\WorkspaceController::class . ':create');
$app->get('/workspace/{workspace:[0-9a-zA-Z]+}', FSEdit\WorkspaceController::class . ':structure');

$app->post('/users/login', FSEdit\UserController::class . ':login');
$app->post('/users/register', FSEdit\UserController::class . ':register');
$app->post('/users/exists', FSEdit\UserController::class . ':exists');

$app->options('/{routes:.*}', function ($req, $res) {
    return $res;
});

//$app->map(['get', 'post', 'put', 'delete', 'patch'], '/{routes:.+}', function($req, $res) {
//    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
//    return $handler($req, $res);
//});