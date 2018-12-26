<?php

namespace FSEdit;

use Medoo\Medoo;

class Workspace extends Model
{
    protected static $tableName = 'workspaces';
    /**
     * @var int $user_id
     */
    private $user_id;
    /**
     * @var string $hash
     */
    private $hash;
    /**
     * @var \DateTime $created
     */
    private $created;
    /**
     * @var bool $private
     */
    private $private;
    /**
     * @var string $edit_token
     */
    private $edit_token;

    /**
     * @var FileTree $__tree
     */
    private $__tree = null;

    /**
     * @param Medoo $database
     * @return Workspace
     * @throws \Exception
     */
    public static function create($database)
    {
        $hash = Utils::randomStr(14);
        $editToken = Utils::randomSha1();

        $database->insert('workspaces', [
            'hash' => $hash,
            'edit_token' => $editToken
        ]);

        if ($database->error()[0] != 0) {
            throw new \Exception('database error');
        }

        $workspace = new Workspace($database, $database->id());
        $workspace->getFileTree()->createRootNode([], $workspace->id);
        return $workspace;
    }

    /**
     * @param string $hash
     * @return $this
     */
    public function loadByHash($hash)
    {
        if (!$hash) {
            throw new \InvalidArgumentException('no workspace hash is specified');
        }
        return $this->load(['hash' => $hash]);
    }

    public function getStructure($rootId = null)
    {
        if ($rootId === null) {
            $rootId = (int)$this->getRootNode()['id'];
        }
        return $this->getFileTree()->getDescendants($rootId);
    }

    public function getRootNode()
    {
        return $this->getFileTree()->getRootNode($this->id);
    }

    public function getFileTree()
    {
        if (!$this->__tree) {
            $this->__tree = new FileTree($this->database->pdo);
        }
        return $this->__tree;
    }

    public function hasUser()
    {
        return !!$this->user_id;
    }

    public function getUser()
    {
        return new User($this->database, $this->user_id);
    }

    /**
     * @throws \Exception
     */
    public function canReadEx()
    {
        if (!$this->canRead()) {
            throw new \Exception('cannot read this workspace');
        }
    }

    /**
     * @return bool
     */
    public function canRead()
    {
        return true; //todo check permissions
    }

    /**
     * @throws \Exception
     */
    public function canWriteEx()
    {
        if (!$this->canWrite()) {
            throw new \Exception('cannot write to this workspace');
        }
    }

    /**
     * @return bool
     */
    public function canWrite()
    {
        return true; //todo check permissions
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getEditToken()
    {
        return $this->edit_token;
    }

    protected function mapFields($result)
    {
        $this->id = (int)$result['id'];
        $this->user_id = (int)$result['user_id'];
        $this->hash = $result['hash'];
        $this->created = $result['created'];
        $this->private = !!$result['private'];
        $this->edit_token = $result['edit_token'];
    }
}