<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;
use StefanoTree\Exception\RootNodeAlreadyExistException;

class UserController extends Controller
{
    public function login(Request $req, Response $res)
    {
        $pdo = $this->database->pdo;

        $tree = new FileTree($pdo);

        return $this->json($res, [
            'token' => Utils::random_str(64),
            'hash' => Utils::random_str(14),
            'file' => sha1(microtime(true) . mt_rand(10000, 90000)),
        ]);
    }
}