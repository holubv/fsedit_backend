<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__) . DS);
define('VENDOR', ROOT . 'vendor' . DS);
define('APP', ROOT . 'src' . DS);

require ROOT . 'config/config.php';
require VENDOR . 'autoload.php';

//session_start();

// Instantiate the app
$app = new \Slim\App([
    'settings' => $config,
]);

// Set up dependencies
$container = $app->getContainer();

$container['config'] = function ($container) use ($config) {
    return (object)$config;
};

$config = $container->get('config');
if ($config->error_reporting !== false) {
    error_reporting($config->error_reporting);
}
if ($config->display_errors) {
    ini_set('display_errors', $config->display_errors);
}

$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        /** @var \Slim\Http\Response $response */
        return $response->withStatus(404);
    };
};

$container['notAllowedHandler'] = function ($container) {
    return function ($request, $response, $methods) use ($container) {
        /** @var \Slim\Container $container */
        return $container['response']
            ->withHeader('Allow', implode(', ', $methods))
            ->withJson(['error' => 'method not allowed', 'allowed' => $methods, 'status' => 405], 405);
    };
};

if (!$container->get('settings')['displayErrorDetails']) {
    $container['errorHandler'] = function ($container) {
        return function ($request, $response, $exception) use ($container) {
            /** @var \Slim\Http\Request $request */
            /** @var \Slim\Http\Response $response */
            /** @var \Exception $exception */
            return $response->withJson([
                'error' => $exception->getMessage()
            ], 500, JSON_PRETTY_PRINT);
        };
    };
}

require APP . '/router.php';

$app->run();