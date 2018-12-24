<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\ConflictException;
use FSEdit\Exception\NotFoundException;
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
            throw new BadRequestException('filename is missing');
        }
        $wHash = $req->getParam('workspace', null);
        if (!$wHash) {
            throw new BadRequestException('workspace is missing');
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

        Utils::convertFileToUTF8($this->getFilePath($hash)); //todo handle images

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
                throw new BadRequestException('file code is missing');
            }
        }

        $node = $this->database->get('file_tree', '*', ['file' => $hash]);
        if (!$node) {
            throw new NotFoundException();
        }

        $workspace = Workspace::getById($this->database, $node['workspace_id']);
        $workspace->canReadEx();

        $path = $this->getFilePath($hash);
        if (!file_exists($path)) {
            throw new NotFoundException();
        }

        $eTag = md5_file($path);

        $presentETag = $req->getHeader('If-None-Match');
        if ($presentETag && $presentETag[0] === $eTag) {
            return $res->withStatus(304);
        }

        $fh = fopen($path, 'rb');
        $stream = new Stream($fh);
        return $res
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('ETag', $eTag)
            //->withHeader('Content-Type', 'image/jpeg') //todo recognize images
            ->withBody($stream);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function create(Request $req, Response $res)
    {
        $name = $req->getParam('name', 'unnamed');
        $wHash = $req->getParam('workspace', null);
        if (!$wHash) {
            throw new BadRequestException('workspace is missing');
        }
        $parent = $req->getParam('parent', null);
        $isFolder = $req->getParam('folder', false) === 'true';

        $workspace = Workspace::getByHash($this->database, $wHash);
        $workspace->canWriteEx();

        $tree = $workspace->getFileTree();
        $rootId = (int)$workspace->getRootNode()['id'];
        if (!$parent) {
            $parent = $rootId;
        }

        $parentNode = $tree->getNode($parent);
        if (!$parentNode || ((int)$parentNode['workspace_id']) !== $workspace->getId()) {
            //parent id is not from this workspace, prevent editing different ws
            throw new BadRequestException('invalid parent id');
        }

        //check name duplicity
        $existing = $this->database->get('file_tree', 'id', [
            'workspace_id' => $workspace->getId(),
            'parent_id' => $parent,
            'name' => $name
        ]);
        if ($existing) {
            throw new ConflictException('item name already exists under this parent');
        }

        if ($isFolder) {
            $id = $tree->addNodePlacementChildBottom($parent, ['name' => $name]);
            return $this->json($res, [
                'id' => $id,
                'name' => $name,
                'file' => null
            ]);
        }

        $hash = Utils::randomSha1();

        $id = $tree->addNodePlacementChildBottom($parent, ['name' => $name, 'file' => $hash]);
        if (!$this->completeUpload(null, $hash)) {
            throw new \Exception('cannot complete file upload');
        }

        return $this->json($res, [
            'parent' => $parent !== $rootId ? $parent : null,
            'id' => $id,
            'file' => $hash,
            'name' => $name
        ]);
    }

    public function move(Request $req, Response $res)
    {

    }

    public function rename(Request $req, Response $res)
    {

    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function edit(Request $req, Response $res)
    {
        $fileId = (int)$req->getParam('file');
        if ($fileId <= 0) {
            throw new BadRequestException('invalid file id');
        }
        $wHash = $req->getParam('workspace');
        if (!$wHash) {
            throw new BadRequestException('workspace is missing');
        }

        $workspace = Workspace::getByHash($this->database, $wHash);
        $workspace->canWriteEx();

        $file = $this->database->get('file_tree', ['id', 'workspace_id', 'file'], [
            'id' => $fileId,
            'file[!]' => null
        ]);
        if (!$file || $file['workspace_id'] != $workspace->getId()) {
            throw new NotFoundException('file not found');
        }
        $hash = $file['file'];
        $path = $this->getFilePath($hash);

        //todo check file size

        if (file_put_contents($path, $req->getBody()) === false) {
            throw new \Exception('cannot write to file');
        }

        return $this->json($res, []);
    }

    /**
     * @param string $hash
     * @return string
     */
    private function getFilePath($hash)
    {
        $pre = substr($hash, 0, 8);
        $pre = chunk_split($pre, 2, '/');
        $hash = substr($hash, 8);
        return ROOT . $this->config->uploadsDir . '/' . $pre . $hash;
    }

    /**
     * @param UploadedFileInterface|null $file
     * @param $hash
     * @return bool
     */
    private function completeUpload($file, $hash)
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

        if ($file) {
            try {
                $file->moveTo($path . $hash);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            $file = fopen($path . $hash, 'w');
            if ($file !== false) {
                fclose($file);
            } else {
                return false;
            }
        }

        return true;
    }
}