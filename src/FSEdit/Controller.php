<?php

namespace FSEdit;

use FSEdit\Exception\UnauthorizedException;
use Medoo\Medoo;
use Slim\Container;
use Slim\Http\Response;

class Controller
{
    /** @var Container */
    protected $container;
    /** @var Medoo */
    protected $database = null;

    protected $config;

    /** @var User|null */
    protected $user = null;

    /**
     * Controller constructor.
     * @param Container $container
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        if ($container->has('database')) {
            $this->database = $this->container->get('database');
        }
        if ($container->has('user')) {
            $this->user = $this->container->get('user');
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

    /**
     * @return User
     */
    protected function requireUser()
    {
        if (!$this->user) {
            throw new UnauthorizedException();
        }
        return $this->user;
    }

}