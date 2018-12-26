<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\UnauthorizedException;
use FSEdit\Model\ModelFactory;
use FSEdit\Model\User;
use Medoo\Medoo;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class Controller
{
    use ModelFactory;

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
     * @param Request $req
     * @param string $key
     * @param bool $allowEmpty
     * @return string
     */
    protected function requireParam(Request $req, $key, $allowEmpty = false)
    {
        $val = $req->getParam($key);
        if ($val === null) {
            throw new BadRequestException('parameter "' . $key . '" is missing');
        }
        if (!$allowEmpty && !$val) {
            throw new BadRequestException('parameter "' . $key . '" is empty');
        }
        return $val;
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

    protected function getDatabase()
    {
        return $this->database;
    }
}