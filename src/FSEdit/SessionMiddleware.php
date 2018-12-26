<?php

namespace FSEdit;

use FSEdit\Exception\UnauthorizedException;
use FSEdit\Model\User;
use Medoo\Medoo;
use Slim\App;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class SessionMiddleware
{
    const HEADER_NAME = 'X-Api-Token';
    /**
     * @var Container
     */
    private $container;

    /**
     * SessionMiddleware constructor.
     * @param App $app
     */
    public function __construct($app)
    {
        $this->container = $app->getContainer();
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param $next
     * @return Response
     */
    public function __invoke($req, $res, $next)
    {
        if (!$req->hasHeader(self::HEADER_NAME)) {
            return $next($req, $res);
        }

        $token = $req->getHeader(self::HEADER_NAME)[0];
        if (!$token) {
            return $next($req, $res);
        }

        /** @var Medoo $database */
        $database = $this->container['database'];

        $r = $database->get('sessions', 'user_id', [
            'token' => $token,
            'created[>]' => Medoo::raw('NOW() - INTERVAL 1 MONTH')
        ]);

        if ($r && $r['user_id']) {
            $id = (int)$r['user_id'];
        } else {
            throw new UnauthorizedException();
        }

        $user = new User($database, $id);
        $user->markAsSession();

        $this->container['user'] = $user;

        return $next($req, $res);
    }
}