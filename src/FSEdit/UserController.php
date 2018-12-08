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
            'token' => Utils::randomStr(64),
            'hash' => Utils::randomStr(14),
            'file' => Utils::randomSha1(),
        ]);
    }
}