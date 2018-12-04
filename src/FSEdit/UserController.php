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

        return $this->json($res, ['hash' => Utils::random_str(64)]);
    }
}