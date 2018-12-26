<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     */
    public function login($req, $res)
    {
        $email = $this->requireParam($req, 'email');
        $password = $this->requireParam($req, 'password');
    }

    /**
     * @param Request $req
     * @param Response $res
     */
    public function register($req, $res)
    {

    }
}