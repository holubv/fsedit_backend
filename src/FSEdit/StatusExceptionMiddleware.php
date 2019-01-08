<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\StatusException;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

class StatusExceptionMiddleware
{
    /**
     * @var bool $debug
     */
    private $debug;

    /**
     * StatusExceptionMiddleware constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->debug = $app->getContainer()->get('config')->debug;
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

        $data = [
            'message' => $ex->getMessage(),
            'status' => $ex->getStatus()
        ];

        if ($this->debug) {
            $data['trace'] = $ex->getTraceAsString();
        }

        return $res->withJson($data, $ex->getStatus(), JSON_PRETTY_PRINT);
    }

}