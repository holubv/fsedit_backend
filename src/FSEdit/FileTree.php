<?php

namespace FSEdit;

use FSEdit\Exception\ConflictException;
use FSEdit\Exception\SqlException;
use FSEdit\NestedSet\Adapter\Pdo;
use StefanoTree\NestedSet;


class FileTree extends NestedSet
{

    protected $options;

    public function __construct($pdo)
    {
        $this->options = new NestedSet\Options([
            'tableName' => 'file_tree',
            'idColumnName' => 'id',
            'scopeColumnName' => 'workspace_id',
        ]);
        parent::__construct(new Pdo($this->options, $pdo));
    }

    /**
     * @param $nodeId
     * @param int $startLevel
     * @param int $excludeLastNLevels
     * @return array
     */
    public function getAncestors($nodeId, $startLevel = 0, $excludeLastNLevels = 0)
    {
        $options = $this->options;

        /** @var Pdo $adapter */
        $adapter = $this->getAdapter();

        $nodeInfo = $adapter->getNodeInfo($nodeId);
        if (!$nodeInfo) {
            return [];
        }
        $adapter = $this->getAdapter();
        $params = [
            '__leftIndex' => $nodeInfo->getLeft(),
            '__rightIndex' => $nodeInfo->getRight(),
        ];
        $sql = $adapter->getDefaultDbSelect();
        $sql .= ' WHERE ' . $adapter->addTableName($options->getLeftColumnName()) . ' <= :__leftIndex'
            . ' AND ' . $adapter->addTableName($options->getRightColumnName()) . ' >= :__rightIndex';
        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $adapter->addTableName($options->getScopeColumnName()) . ' = :__scope';
            $params['__scope'] = $nodeInfo->getScope();
        }
        if (0 < $startLevel) {
            $sql .= ' AND ' . $adapter->addTableName($options->getLevelColumnName()) . ' >= :__startLevel';
            $params['__startLevel'] = $startLevel;
        }
        if (0 < $excludeLastNLevels) {
            $sql .= ' AND ' . $adapter->addTableName($options->getLevelColumnName()) . ' <= :__excludeLastNLevels';
            $params['__excludeLastNLevels'] = $nodeInfo->getLevel() - $excludeLastNLevels;
        }
        $sql .= ' ORDER BY ' . $adapter->addTableName($options->getLeftColumnName()) . ' ASC';
        return $adapter->executeSelectSQL($sql, $params);
    }

    /**
     * @param int $target
     * @param array $data
     * @return int
     */
    public function addNodeChild($target, $data = [])
    {
        try {
            $id = $this->addNodePlacementChildBottom($target, $data);
        } catch (SqlException $e) {
            $this->handleSqlException($e);
        }
        if ($id === false) {
            throw new \RuntimeException('cannot create a new node child');
        }
        return $id;
    }

    /**
     * @param SqlException $e
     * @throws SqlException|ConflictException
     */
    private function handleSqlException($e)
    {
        if ($e->isDuplicateError()) {
            throw new ConflictException('item already exists under this parent', $e);
        }
        throw $e;
    }

    /**
     * @param int $source
     * @param int $target
     */
    public function moveNodeChild($source, $target)
    {
        try {
            $r = $this->moveNodePlacementChildBottom($source, $target);
        } catch (SqlException $e) {
            $this->handleSqlException($e);
        }
        if ($r === false) {
            throw new \RuntimeException('cannot move node child');
        }
    }
}