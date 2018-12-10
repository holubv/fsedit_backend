<?php

namespace FSEdit;

use Medoo\Medoo;
use Slim\Container;
use Slim\Http\Response;

class Controller
{
    protected $container;
    /** @var  Medoo */
    protected $database = null;

    protected $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        //$this->view = $this->container->get('view');
        if ($container->has('database')) {
            $this->database = $this->container->get('database');
        }

        $this->config = $this->container->get('config');
    }

    /**
     * @param Response $res
     * @param array|object $data
     * @param int|null $status
     * @return Response
     */
    protected function json(Response $res, $data, $status = null)
    {
        return $res->withJson($data, $status, JSON_PRETTY_PRINT);
    }

}