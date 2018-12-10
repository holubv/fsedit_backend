<?php

namespace FSEdit;

use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;

class FileController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function upload(Request $req, Response $res)
    {
        if ($req->getMethod() !== 'POST') {
            return $res;
        }

        /** @var UploadedFileInterface $file */
        $file = $req->getUploadedFiles()['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \Exception('upload error');
        }
        $name = $req->getParam('name', null);
        if (!$name) {
            throw new \Exception('filename is missing');
        }
        $wHash = $req->getParam('workspace', null);
        $wHash = 'bbmte6u2uo0lq3'; //todo remove this
        if (!$wHash) {
            throw new \Exception('workspace is missing');
        }

        $workspace = Workspace::getByHash($this->database, $wHash);
        $workspace->canWriteEx();

        $hash = Utils::randomSha1();

        $path = explode('/', $name);
        $filename = array_pop($path);

        $tree = $workspace->getFileTree();
        $rootId = (int)$workspace->getRootNode()['id'];
        $structure = $workspace->getStructure($rootId);

        $lvl = 1;
        $parent = $rootId;
        $skipScan = false;
        foreach ($path as $folder) {
            $found = false;
            if (!$skipScan) {
                foreach ($structure as $node) {
                    if ($node['level'] == $lvl && $node['name'] == $folder && !isset($node['file'])) {
                        $lvl++;
                        $parent = (int)$node['id'];
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $skipScan = true; //path do not exists (or only part of it exists), create non-existing folders
                $parent = $tree->addNodePlacementChildBottom($parent, ['name' => $folder]);
            }
        }

        $fileOverride = false;
        foreach ($structure as $node) {
            if ($node['level'] == $lvl && $node['parent_id'] == $parent && $node['name'] == $filename && isset($node['file'])) {
                $hash = $node['file'];
                $fileOverride = true;
                break;
            }
        }
        if (!$fileOverride) {
            $tree->addNodePlacementChildBottom($parent, ['name' => $filename, 'file' => $hash]);
        }

        if (!$this->completeUpload($file, $hash)) {
            throw new \Exception('cannot complete file upload');
        }

        return $this->json($res, [
            'parent' => $parent !== $rootId ? $parent : null,
            'file' => $hash,
            'name' => $filename
        ]);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws \Exception
     */
    public function readFile(Request $req, Response $res, $args)
    {
        $hash = $req->getParam('file', null);
        if (!$hash) {
            $hash = isset($args['file']) ? $args['file'] : null;
            if (!$hash) {
                throw new \Exception('file code is missing');
            }
        }

        $node = $this->database->get('file_tree', '*', ['file' => $hash]);
        if (!$node) {
            return $res->withStatus(404);
        }

        $workspace = Workspace::getById($this->database, $node['workspace_id']);
        $workspace->canReadEx();

        $path = $this->getFilePath($hash);
        if (!file_exists($path)) {
            return $res->withStatus(404);
        }

        $fh = fopen($path, 'rb');
        $stream = new Stream($fh);
        return $res
            ->withHeader('Content-Type', 'text/plain')
            //->withHeader('Content-Type', 'image/jpeg') //todo recognize images
            ->withBody($stream);
    }

    /**
     * @param string $hash
     * @return string
     */
    public function getFilePath($hash)
    {
        $pre = substr($hash, 0, 8);
        $pre = chunk_split($pre, 2, '/');
        $hash = substr($hash, 8);
        return ROOT . $this->config->uploadsDir . '/' . $pre . $hash;
    }

    private function completeUpload(UploadedFileInterface $file, $hash)
    {
        $pre = substr($hash, 0, 8);
        $pre = chunk_split($pre, 2, '/');
        $hash = substr($hash, 8);

        $directory = ROOT . $this->config->uploadsDir;

        $path = $directory . '/' . $pre;
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                return false;
            }
        }

        try {
            $file->moveTo($path . $hash);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}