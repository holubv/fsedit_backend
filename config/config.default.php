<?php

$config = [

    'debug' => true,
    'displayErrorDetails' => true,
    'display_errors' => 1,
    'error_reporting' => E_ALL, //E_ALL & ~(E_DEPRECATED | E_NOTICE | E_STRICT),

    'database' => [
        'database_type' => 'mysql',
        'charset' => 'utf8',
        //'prefix' => 'fse_',

        'server' => 'localhost',
        'database_name' => 'fsedit',
        'username' => 'root',
        'password' => 'root',
    ]
];