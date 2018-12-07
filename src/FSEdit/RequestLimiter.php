<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;

class RequestLimiter
{
    /** @var \Medoo\Medoo $database */
    private $database;

    private $key;
    private $timeFrame;
    private $maxRequests;
    private $perIp;

    /**
     * RequestLimiter constructor.
     * @param \Slim\App $app
     * @param string|null $key
     * @param int $timeFrame
     * @param int $maxRequests
     * @param bool $perIp
     */
    public function __construct($app, $key = null, $timeFrame = 600, $maxRequests = 100, $perIp = false)
    {
        $this->database = $app->getContainer()->get('database');
        $this->key = $key;
        $this->timeFrame = $timeFrame;
        $this->maxRequests = $maxRequests;
        $this->perIp = $perIp;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $ip = $request->getServerParam('REMOTE_ADDR', null);
        $w = [
            'key' => $this->key,
            'time[>=]' => time() - $this->timeFrame
        ];
        if ($this->perIp) {
            $w['ip'] = $ip;
        }
        $count = $this->database->count('x_request_limiter', $w);

        if ($count >= $this->maxRequests) {
            return $response->withStatus(429);
        }

        $this->database->insert('x_request_limiter', [
            'key' => $this->key,
            'ip' => $ip
        ]);

        return $next($request, $response);
    }
}