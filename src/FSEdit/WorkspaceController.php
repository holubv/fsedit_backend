<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;

class WorkspaceController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function structure(Request $req, Response $res)
    {
        $wHash = $req->getParam('workspace', null);
        if (!$wHash) {
            throw new \Exception('workspace hash is missing');
        }

        $workspace = Workspace::getByHash($this->database, $wHash);
        $workspace->canReadEx();

        return $this->json($res, $workspace->getStructure());
    }
}