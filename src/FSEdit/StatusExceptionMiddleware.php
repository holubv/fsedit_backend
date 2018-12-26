<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\StatusException;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class StatusExceptionMiddleware
{
    public function __construct(App $app)
    {
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param $next
     * @return Response
     */
    public function __invoke($req, $res, $next)
    {
        $ex = null;
        try {
            return $next($req, $res);
        } catch (StatusException $e) {
            $ex = $e;
        } catch (\InvalidArgumentException $e) {
            $ex = new BadRequestException($e->getMessage(), $e);
        }

        return $res->withJson([
            'message' => $ex->getMessage(),
            'status' => $ex->getStatus(),
            'trace' => $ex->getTraceAsString() //todo hide in production
        ], $ex->getStatus(), JSON_PRETTY_PRINT);
    }

}