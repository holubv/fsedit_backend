<?php

namespace FSEdit\Model;

use FSEdit\Exception\ForbiddenException;
use FSEdit\FileTree;
use FSEdit\Utils;

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
     * @param int|null $userId
     * @return $this
     * @throws \StefanoTree\Exception\RootNodeAlreadyExistException
     */
    public function create($userId = null)
    {
        $this->database->insert('workspaces', [
            'user_id' => $userId,
            'hash' => Utils::randomStr(14),
            'edit_token' => $userId ? null : Utils::randomSha1()
        ]);

        $this->loadById($this->database->id());
        $this->getFileTree()->createRootNode([], $this->id);
        return $this;
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

    /**
     * @return bool
     */
    public function hasUser()
    {
        return !!$this->user_id;
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        if ($this->user_id) {
            return $this->user_id;
        }
        return null;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return new User($this->database, $this->user_id);
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function isOwner($user)
    {
        if ($user && $user->getId() === $this->getUserId()) {
            return true;
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    public function canReadEx()
    {
        if (!$this->canRead()) {
            throw new ForbiddenException('cannot read this workspace');
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
     * @param User|null $user
     * @param string|null $editToken
     * @throws \Exception
     */
    public function canWriteEx($user, $editToken = null)
    {
        if (!$this->canWrite($user, $editToken)) {
            throw new ForbiddenException('cannot write to this workspace');
        }
    }

    /**
     * @param User|null $user
     * @param string|null $editToken
     * @return bool
     */
    public function canWrite($user, $editToken = null)
    {
        if ($this->isOwner($user)) {
            return true; //workspace owner
        }
        if ($editToken && $editToken === $this->getEditToken()) {
            return true; //has edit token
        }
        return false;
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