<?php

namespace FSEdit;

use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

class FileController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     * @throws \Exception
     */
    public function upload(Request $req, Response $res)
    {
        if ($req->getMethod() !== 'POST') {
            return;
        }

        $directory = ROOT . 'uploads';
        /** @var UploadedFileInterface $file */
        $file = $req->getUploadedFiles()['file'];
        $name = $req->getParam('name', null);
        if (!$name) {
            throw new \Exception('filename is missing');
        }

        $hash = Utils::randomSha1();
        if (!$this->completeUpload($file, $hash)) {
            throw new \Exception('cannot complete file upload');
        }

    }

    private function completeUpload(UploadedFileInterface $file, $hash)
    {
        $pre = substr($hash, 0, 8);
        $pre = chunk_split($pre, 2, '/');
        $hash = substr($hash, 8);

        $directory = ROOT . $this->config->uploadsDir;
        $res = mkdir($directory . '/' . $pre, 0777, true);
        if (!$res) {
            return false;
        }

        try {
            echo $directory . '/' . $pre . $hash . '<br>';
            $file->moveTo($directory . '/' . $pre . $hash);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}