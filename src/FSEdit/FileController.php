<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
use FSEdit\Exception\ConflictException;
use FSEdit\Exception\NotFoundException;
use FSEdit\Exception\SqlException;
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
        $name = $this->requireParam($req, 'name');
        $wHash = $this->requireParam($req, 'workspace');

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

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
                $parent = $tree->addNodeChild($parent, ['name' => $folder]);
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
            $tree->addNodeChild($parent, ['name' => $filename, 'file' => $hash]);
        }

        if (!$this->completeUpload($file, $hash)) {
            throw new \Exception('cannot complete file upload');
        }

        $path = $this->getFilePath($hash);
        if (strpos(\mime_content_type($path), 'text/') === 0) {
            Utils::convertFileToUTF8($path);
        }

        return $this->json($res, [
            'parent' => $parent !== $rootId ? (int)$parent : null,
            'file' => $hash,
            'name' => $filename
        ]);
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

        $workspace = $this->Workspace($node['workspace_id']);
        $workspace->canReadEx();

        $path = $this->getFilePath($hash);
        if (!file_exists($path)) {
            throw new NotFoundException();
        }

        $mime = \mime_content_type($path);

        if ($mime === 'inode/x-empty') {
            $mime = 'text/plain';
        }

        if ($req->getHeaderLine('Accept') === 'text/plain' || $req->getQueryParam('plaintext') === 'true') {
            if (strpos($mime, 'text/') !== 0) {
                return $res
                    ->withHeader('Content-Type', $mime)
                    ->withStatus(406);
            }
        }

        $eTag = md5_file($path);

        $presentETag = $req->getHeader('If-None-Match');
        if ($presentETag && $presentETag[0] === $eTag) {
            return $res->withStatus(304);
        }

        if ($req->getQueryParam('download')) {
            $res = $res->withHeader('Content-Disposition', 'attachment; filename="' . Utils::safeFilename($node['name']) . '"');
        }

        $fh = fopen($path, 'rb');
        return $res
            ->withHeader('ETag', $eTag)
            ->withHeader('Content-Type', $mime)
            ->withBody(new Stream($fh));
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
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function create(Request $req, Response $res)
    {
        $name = $this->requireParam($req, 'name');
        $wHash = $this->requireParam($req, 'workspace');
        $parent = $req->getParam('parent', null);
        $isFolder = $req->getParam('folder', false) === 'true';

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

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
        $existing = $this->database->get('file_tree', ['id'], [
            'workspace_id' => $workspace->getId(),
            'parent_id' => $parent,
            'name' => $name
        ]);
        if ($existing) {
            throw new ConflictException('item name already exists under this parent');
        }

        if ($isFolder) {
            $id = $tree->addNodeChild($parent, ['name' => $name]);
            return $this->json($res, [
                'id' => (int)$id,
                'name' => $name,
                'file' => null
            ]);
        }

        $hash = Utils::randomSha1();

        $id = $tree->addNodeChild($parent, ['name' => $name, 'file' => $hash]);
        if (!$this->completeUpload(null, $hash)) {
            throw new \Exception('cannot complete file upload');
        }

        return $this->json($res, [
            'parent' => $parent !== $rootId ? (int)$parent : null,
            'id' => (int)$id,
            'file' => $hash,
            'name' => $name
        ]);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function move(Request $req, Response $res)
    {
        $itemId = (int)$req->getParam('id');
        if ($itemId <= 0) {
            throw new BadRequestException('invalid item id');
        }
        $parentId = (int)$req->getParam('parent', 0);
        if ($parentId < 0) {
            throw new BadRequestException('invalid parent id');
        }
        $wHash = $this->requireParam($req, 'workspace');

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

        $tree = $workspace->getFileTree();

        $item = $this->database->get('file_tree', ['id', 'name'], [
            'id' => $itemId,
            'workspace_id' => $workspace->getId()
        ]);
        if (!$item) {
            throw new NotFoundException('item not found');
        }

        $parent = null;
        if ($parentId) {
            $parent = $this->database->get('file_tree', ['id'], [
                'id' => $parentId,
                'workspace_id' => $workspace->getId(),
                'file' => null //must be folder
            ]);
        } else {
            $parent = $workspace->getRootNode();
        }

        if (!$parent) {
            throw new NotFoundException('parent folder node not found');
        }

        $tree->moveNodeChild((int)$item['id'], (int)$parent['id']);
        return $this->json($res, []);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function rename(Request $req, Response $res)
    {
        $itemId = (int)$req->getParam('id');
        if ($itemId <= 0) {
            throw new BadRequestException('invalid item id');
        }
        $name = $this->requireParam($req, 'name');
        $wHash = $this->requireParam($req, 'workspace');

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

        try {
            $this->database->update('file_tree', ['name' => $name], [
                'id' => $itemId,
                'workspace_id' => $workspace->getId(),
                'LIMIT' => 1
            ]);
        } catch (SqlException $e) {
            if ($e->isDuplicateError()) {
                throw new ConflictException('item name already exists under this parent', $e);
            }
            throw $e;
        }

        return $this->json($res, []);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function delete(Request $req, Response $res)
    {
        $itemId = (int)$req->getParam('id');
        if ($itemId <= 0) {
            throw new BadRequestException('invalid item id');
        }

        $wHash = $this->requireParam($req, 'workspace');

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

        $item = $this->database->get('file_tree', ['id', 'name'], [
            'id' => $itemId,
            'workspace_id' => $workspace->getId()
        ]);
        if (!$item) {
            throw new NotFoundException('item not found');
        }

        $tree = $workspace->getFileTree();
        $items = $tree->getDescendants($itemId);

        $tree->deleteBranch($itemId);

        foreach ($items as $i) {
            if ($i['file']) {
                $path = $this->getFilePath($i['file']);
                unlink($path);
            }
        }

        return $this->json($res, []);
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
        $wHash = $this->requireParam($req, 'workspace');

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canWriteEx($this->user, $req->getParam('edit'));

        $file = $this->database->get('file_tree', ['id', 'workspace_id', 'file'], [
            'id' => $fileId,
            'workspace_id' => $workspace->getId(),
            'file[!]' => null
        ]);
        if (!$file) {
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
}