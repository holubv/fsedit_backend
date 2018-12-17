<?php

namespace FSEdit;

use Medoo\Medoo;

class Workspace
{
    /**
     * @var Medoo $database
     */
    private $database;

    /**
     * @var int $id
     */
    private $id;
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
     * Workspace constructor.
     * @param Medoo $database
     */
    private function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * @param Medoo $database
     * @param string $hash
     * @return Workspace
     */
    public static function getByHash($database, $hash)
    {
        if (!$hash) {
            throw new \InvalidArgumentException('no workspace hash is specified');
        }
        $w = new Workspace($database);
        $w->load(['hash' => $hash]);
        return $w;
    }

    /**
     * @param array $where
     */
    private function load($where)
    {
        if (!$where) {
            throw new \InvalidArgumentException('no where clause is specified');
        }
        $result = $this->database->get('workspaces', '*', $where);
        if (!$result) {
            throw new \RuntimeException('workspace not found');
        }

        $this->id = (int)$result['id'];
        $this->user_id = (int)$result['user_id'];
        $this->hash = $result['hash'];
        $this->created = $result['created'];
        $this->private = !!$result['private'];
        $this->edit_token = $result['edit_token'];
    }

    /**
     * @param Medoo $database
     * @param int $id
     * @return Workspace
     */
    public static function getById($database, $id)
    {
        $w = new Workspace($database);
        $w->load(['id' => (int)$id]);
        return $w;
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


}