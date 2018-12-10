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
//        foreach ($path as $folder) {
//
//        }

        //$tree->addNodePlacementChildBottom(7, ['name' => 'file3', 'file' => 'bf10567440d058413789469e7960d53ac6f3f9d6']);

        /*return $this->json($res, [
            'token' => Utils::randomStr(64),
            'hash' => Utils::randomStr(14),
            'file' => Utils::randomSha1(),
        ]);*/

        return $this->json($res, $tree->getDescendants(1));
        //var_dump($tree->getRootNode(1));

        //return $this->json($res, $tree->getAncestors(6));
    }
}