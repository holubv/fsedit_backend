<?php

namespace FSEdit;

use Medoo\Medoo;
use Slim\Container;
use Slim\Http\Response;
use Slim\Http\Request;

class Controller
{
    protected $container;
    /** @var  Medoo */
    protected $database = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        //$this->view = $this->container->get('view');
        if ($container->has('database')) {
            $this->database = $this->container->get('database');
        }
    }

    protected function json(Response $res, $data, $status = null)
    {
        return $res->withJson($data, $status, JSON_PRETTY_PRINT);
    }

}