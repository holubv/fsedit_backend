<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
    public function login(Request $req, Response $res)
    {
        $pdo = $this->database->pdo;

        $tree = new FileTree($pdo);

        $root = $tree->getRootNode();


        return $this->json($res, $tree->getDescendants(1));
        //var_dump($tree->getRootNode(1));

        //return $this->json($res, $tree->getAncestors(6));
    }
}