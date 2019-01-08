<?php

namespace FSEdit\NestedSet\Adapter;

use FSEdit\Exception\SqlException;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;

/**
 * NestedSet Pdo adapter<br>
 * Ported from StefanoTree/NestedSet (for php 7) to support php 5
 * @package FSEdit\NestedSet\Adapter
 *
 * @see https://github.com/bartko-s/stefano-tree
 * @see https://github.com/bartko-s/stefano-tree/blob/4.0.1/src/StefanoTree/NestedSet/Adapter/Pdo.php
 * @see https://github.com/bartko-s/stefano-tree/blob/4.0.1/src/StefanoTree/NestedSet/Manipulator/Manipulator.php
 *
 * @license https://github.com/bartko-s/stefano-tree/blob/4.0.1/LICENSE.md
 */
class Pdo implements AdapterInterface
{
    private $connection;
    private $options;

    /**
     * @param Options $options
     * @param \PDO $connection
     */
    public function __construct(Options $options, \PDO $connection)
    {
        $this->connection = $connection;
        $this->options = $options;
    }

    public function beginTransaction()
    {
        $this->getConnection()
            ->beginTransaction();
    }

    /**
     * @return \PDO
     */
    private function getConnection()
    {
        return $this->connection;
    }

    public function commitTransaction()
    {
        $this->getConnection()
            ->commit();
    }

    public function rollbackTransaction()
    {
        $this->getConnection()
            ->rollBack();
    }


    /**
     * @return bool
     */
    public function isInTransaction()
    {
        return $this->getConnection()
            ->inTransaction();
    }

    /**
     * @return bool
     */
    public function canHandleNestedTransaction()
    {
        return false;
    }

    /**
     * Lock tree for update. This prevent race condition issue
     *
     * @return void
     */
    public function lockTree()
    {
        $options = $this->getOptions();
        $sql = 'SELECT ' . $options->getIdColumnName()
            . ' FROM ' . $options->getTableName()
            . ' FOR UPDATE';
        $this->executeSQL($sql);
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $sql
     * @param array $params
     */
    public function executeSQL($sql, $params = [])
    {
        $stm = $this->getConnection()
            ->prepare($sql);
        if (!$stm->execute($params)) {
            throw new SqlException($stm->errorInfo());
        }
    }

    /**
     * Update node data. Function must sanitize data from keys like level, leftIndex, ...
     *
     * @param int $nodeId
     * @param array $data
     * @return void
     */
    public function update($nodeId, array $data)
    {
        $options = $this->getOptions();
        $data = $this->cleanData($data);
        $setPart = array_map(function ($item) {
            return $item . ' = :' . $item;
        }, array_keys($data));
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET ' . implode(', ', $setPart)
            . ' WHERE ' . $options->getIdColumnName() . ' = :__nodeID';
        $data['__nodeID'] = $nodeId;
        $this->executeSQL($sql, $data);
    }

    /**
     * Data cannot contain keys like idColumnName, levelColumnName, ...
     *
     * @param array $data
     *
     * @return array
     */
    protected function cleanData($data)
    {
        $options = $this->getOptions();
        $disallowedDataKeys = [
            $options->getIdColumnName(),
            $options->getLeftColumnName(),
            $options->getRightColumnName(),
            $options->getLevelColumnName(),
            $options->getParentIdColumnName(),
        ];
        if (null !== $options->getScopeColumnName()) {
            $disallowedDataKeys[] = $options->getScopeColumnName();
        }
        return array_diff_key($data, array_flip($disallowedDataKeys));
    }

    /**
     * @param NodeInfo $nodeInfo
     * @param array $data
     * @return int Last ID
     */
    public function insert(NodeInfo $nodeInfo, array $data)
    {
        $options = $this->getOptions();
        $data[$options->getParentIdColumnName()] = $nodeInfo->getParentId();
        $data[$options->getLevelColumnName()] = $nodeInfo->getLevel();
        $data[$options->getLeftColumnName()] = $nodeInfo->getLeft();
        $data[$options->getRightColumnName()] = $nodeInfo->getRight();
        if ($options->getScopeColumnName()) {
            $data[$options->getScopeColumnName()] = $nodeInfo->getScope();
        }
        $columns = array_map(function ($item) {
            return $item;
        }, array_keys($data));
        $values = array_map(function ($item) {
            return ':' . $item;
        }, array_keys($data));
        $sql = 'INSERT INTO ' . $options->getTableName()
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES(' . implode(', ', $values) . ')';
        return $this->executeInsertSQL($sql, $data);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return mixed|string
     */
    public function executeInsertSQL($sql, $params = [])
    {
        $options = $this->getOptions();
        $this->executeSQL($sql, $params);
        if (array_key_exists($options->getIdColumnName(), $params)) {
            return $params[$options->getIdColumnName()];
        } else {
            if ('' != $options->getSequenceName()) {
                $lastGeneratedValue = $this->getConnection()
                    ->lastInsertId($options->getSequenceName());
            } else {
                $lastGeneratedValue = $this->getConnection()
                    ->lastInsertId();
            }
            return $lastGeneratedValue;
        }
    }

    /**
     * Delete branch
     *
     * @param int $nodeId
     * @return void
     */
    public function delete($nodeId)
    {
        $options = $this->getOptions();
        $sql = 'DELETE FROM ' . $options->getTableName()
            . ' WHERE ' . $options->getIdColumnName() . ' = :__nodeID';
        $params = [
            '__nodeID' => $nodeId,
        ];
        $this->executeSQL($sql, $params);
    }

    /**
     * @param int $fromIndex Left index is greater than
     * @param int $shift
     * @param null|string|int $scope null if scope is not used
     * @return void
     */
    public function moveLeftIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();
        if (0 == $shift) {
            return;
        }
        $params = [
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        ];
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET '
            . $options->getLeftColumnName() . ' = '
            . $options->getLeftColumnName() . ' + :shift'
            . ' WHERE '
            . $options->getLeftColumnName() . ' > :fromIndex';
        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $options->getScopeColumnName() . ' = :__scope';
            $params['__scope'] = $scope;
        }
        $this->executeSQL($sql, $params);
    }

    /**
     * @param int $fromIndex Right index is greater than
     * @param int $shift
     * @param null|string|int $scope null if scope is not used
     * @return void
     */
    public function moveRightIndexes($fromIndex, $shift, $scope = null)
    {
        $options = $this->getOptions();
        if (0 == $shift) {
            return;
        }
        $params = [
            ':shift' => $shift,
            ':fromIndex' => $fromIndex,
        ];
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET '
            . $options->getRightColumnName() . ' = '
            . $options->getRightColumnName() . ' + :shift'
            . ' WHERE '
            . $options->getRightColumnName() . ' > :fromIndex';
        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $options->getScopeColumnName() . ' = :__scope';
            $params['__scope'] = $scope;
        }
        $this->executeSQL($sql, $params);
    }

    /**
     * @param int $nodeId
     * @param int $newParentId
     * @return void
     */
    public function updateParentId($nodeId, $newParentId)
    {
        $options = $this->getOptions();
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET ' . $options->getParentIdColumnName() . ' = :__parentId'
            . ' WHERE ' . $options->getIdColumnName() . ' = :__nodeId';
        $params = [
            '__parentId' => $newParentId,
            '__nodeId' => $nodeId,
        ];
        $this->executeSQL($sql, $params);
    }

    /**
     * @param int $leftIndexFrom from left index or equal
     * @param int $rightIndexTo to right index or equal
     * @param int $shift shift
     * @param null|string|int $scope null if scope is not used
     * @return void
     */
    public function updateLevels($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        $options = $this->getOptions();
        if (0 == $shift) {
            return;
        }
        $binds = [
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        ];
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET '
            . $options->getLevelColumnName() . ' = '
            . $options->getLevelColumnName() . ' + :shift'
            . ' WHERE '
            . $options->getLeftColumnName() . ' >= :leftFrom'
            . ' AND ' . $options->getRightColumnName() . ' <= :rightTo';
        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $options->getScopeColumnName() . ' = :__scope';
            $binds['__scope'] = $scope;
        }
        $this->executeSQL($sql, $binds);
    }

    /**
     * @param int $leftIndexFrom from left index
     * @param int $rightIndexTo to right index
     * @param int $shift
     * @param null|string|int $scope null if scope is not used
     * @return void
     */
    public function moveBranch($leftIndexFrom, $rightIndexTo, $shift, $scope = null)
    {
        if (0 == $shift) {
            return;
        }
        $options = $this->getOptions();
        $binds = [
            ':shift' => $shift,
            ':leftFrom' => $leftIndexFrom,
            ':rightTo' => $rightIndexTo,
        ];
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET '
            . $options->getLeftColumnName() . ' = '
            . $options->getLeftColumnName() . ' + :shift, '
            . $options->getRightColumnName() . ' = '
            . $options->getRightColumnName() . ' + :shift'
            . ' WHERE '
            . $options->getLeftColumnName() . ' >= :leftFrom'
            . ' AND ' . $options->getRightColumnName() . ' <= :rightTo';
        if ($options->getScopeColumnName()) {
            $sql .= ' AND ' . $options->getScopeColumnName() . ' = :__scope';
            $binds['__scope'] = $scope;
        }
        $this->executeSQL($sql, $binds);
    }

    /**
     * @param int $nodeId
     * @return null|array
     */
    public function getNode($nodeId)
    {
        $options = $this->getOptions();
        $nodeId = (int)$nodeId;
        $params = [
            '__nodeID' => $nodeId,
        ];
        $sql = $this->getDefaultDbSelect();
        $sql .= ' WHERE ' . $this->addTableName($options->getIdColumnName()) . ' = :__nodeID';
        $result = $this->executeSelectSQL($sql, $params);
        return (0 < count($result)) ? $result[0] : null;
    }

    /**
     * Return default db select.
     *
     * @return string
     */
    public function getDefaultDbSelect()
    {
        return $this->getBlankDbSelect();
    }

    /**
     * @return string
     */
    public function getBlankDbSelect()
    {
        return 'SELECT * FROM ' . $this->getOptions()->getTableName() . ' ';
    }

    public function addTableName($value)
    {
        return sprintf('%s.%s', $this->getOptions()->getTableName(), $value);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function executeSelectSQL($sql, $params = [])
    {
        $stm = $this->getConnection()
            ->prepare($sql);
        if (!$stm->execute($params)) {
            throw new SqlException($stm->errorInfo());
        }
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Children must be find by parent ID column and order by left index !!!
     *
     * @param $parentNodeId int
     * @return array
     */
    public function getChildrenNodeInfo($parentNodeId)
    {
        $options = $this->getOptions();
        $params = [
            '__parentID' => $parentNodeId,
        ];
        $sql = 'SELECT *'
            . ' FROM ' . $this->getOptions()->getTableName()
            . ' WHERE ' . $this->addTableName($options->getParentIdColumnName()) . ' = :__parentID'
            . ' ORDER BY ' . $this->addTableName($options->getLeftColumnName()) . ' ASC';
        $data = $this->executeSelectSQL($sql, $params);
        $result = [];
        foreach ($data as $nodeData) {
            $result[] = $this->_buildNodeInfoObject($nodeData);
        }
        return $result;
    }

    /**
     * @param array $data
     *
     * @return NodeInfo
     */
    protected function _buildNodeInfoObject($data)
    {
        $options = $this->getOptions();
        $id = $data[$options->getIdColumnName()];
        $parentId = $data[$options->getParentIdColumnName()];
        $level = (int)$data[$options->getLevelColumnName()];
        $left = (int)$data[$options->getLeftColumnName()];
        $right = (int)$data[$options->getRightColumnName()];
        if (isset($data[$options->getScopeColumnName()])) {
            $scope = $data[$options->getScopeColumnName()];
        } else {
            $scope = null;
        }
        return new NodeInfo($id, $parentId, $level, $left, $right, $scope);
    }

    /**
     * Update left index, right index, level. Other columns must be ignored.
     *
     * @param NodeInfo $nodeInfo
     * @return void
     */
    public function updateNodeMetadata(NodeInfo $nodeInfo)
    {
        $options = $this->getOptions();
        $data = [
            $options->getRightColumnName() => $nodeInfo->getRight(),
            $options->getLeftColumnName() => $nodeInfo->getLeft(),
            $options->getLevelColumnName() => $nodeInfo->getLevel(),
        ];
        $setPart = array_map(function ($item) {
            return $item . ' = :' . $item;
        }, array_keys($data));
        $sql = 'UPDATE ' . $options->getTableName()
            . ' SET ' . implode(', ', $setPart)
            . ' WHERE ' . $options->getIdColumnName() . ' = :__nodeID';
        $data['__nodeID'] = $nodeInfo->getId();
        $this->executeSQL($sql, $data);
    }

    /**
     * @param int $nodeId
     * @param int $startLevel 0 = include root
     * @param boolean $excludeLastNode
     * @return array
     */
    public function getPath($nodeId, $startLevel = 0, $excludeLastNode = false)
    {
        // TODO: Implement getPath() method.
        throw new \Error('not implemented');
    }

    /**
     * @param int $nodeId
     * @param int $startLevel Relative level from $nodeId. 1 = exclude $nodeId from result.
     *                        2 = exclude 2 levels from result
     * @param null|int $levels Number of levels in the results relative to $startLevel
     * @param null|int $excludeBranch Exclude defined branch(node id) from result
     * @return array
     */
    public function getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null)
    {
        $options = $this->getOptions();
        if (!$nodeInfo = $this->getNodeInfo($nodeId)) {
            return [];
        }
        $sql = $this->getDefaultDbSelect();
        $params = [];
        $wherePart = [];
        if ($options->getScopeColumnName()) {
            $wherePart[] = $this->addTableName($options->getScopeColumnName()) . ' = :__scope';
            $params['__scope'] = $nodeInfo->getScope();
        }
        if (0 != $startLevel) {
            $wherePart[] = $this->addTableName($options->getLevelColumnName()) . ' >= :__level';
            $params['__level'] = $nodeInfo->getLevel() + $startLevel;
        }
        if (null != $levels) {
            $wherePart[] = $this->addTableName($options->getLevelColumnName()) . ' < :__endLevel';
            $params['__endLevel'] = $nodeInfo->getLevel() + $startLevel + abs($levels);
        }
        if (null != $excludeBranch && null != ($excludeNodeInfo = $this->getNodeInfo($excludeBranch))) {
            $wherePart[] = '( '
                . $this->addTableName($options->getLeftColumnName()) . ' BETWEEN :__l1 AND :__p1'
                . ' OR '
                . $this->addTableName($options->getLeftColumnName()) . ' BETWEEN :__l2 AND :__p2'
                . ') AND ('
                . $this->addTableName($options->getRightColumnName()) . ' BETWEEN :__l3 AND :__p3'
                . ' OR '
                . $this->addTableName($options->getRightColumnName()) . ' BETWEEN :__l4 AND :__p4'
                . ')';
            $params['__l1'] = $nodeInfo->getLeft();
            $params['__p1'] = $excludeNodeInfo->getLeft() - 1;
            $params['__l2'] = $excludeNodeInfo->getRight() + 1;
            $params['__p2'] = $nodeInfo->getRight();
            $params['__l3'] = $excludeNodeInfo->getRight() + 1;
            $params['__p3'] = $nodeInfo->getRight();
            $params['__l4'] = $nodeInfo->getLeft();
            $params['__p4'] = $excludeNodeInfo->getLeft() - 1;
        } else {
            $wherePart[] = $this->addTableName($options->getLeftColumnName()) . ' >= :__left'
                . ' AND ' . $this->addTableName($options->getRightColumnName()) . ' <= :__right';
            $params['__left'] = $nodeInfo->getLeft();
            $params['__right'] = $nodeInfo->getRight();
        }
        $sql .= ' WHERE ' . implode(' AND ', $wherePart);
        $sql .= ' ORDER BY ' . $this->addTableName($options->getLeftColumnName()) . ' ASC';
        $result = $this->executeSelectSQL($sql, $params);
        return (0 < count($result)) ? $result : [];
    }

    /**
     * @param int $nodeId
     * @return NodeInfo|null
     */
    public function getNodeInfo($nodeId)
    {
        $options = $this->getOptions();
        $params = [
            '__nodeID' => $nodeId,
        ];
        $sql = $this->getBlankDbSelect();
        $sql .= ' WHERE ' . $this->addTableName($options->getIdColumnName()) . ' = :__nodeID';
        $array = $this->executeSelectSQL($sql, $params);
        $result = ($array) ? $this->_buildNodeInfoObject($array[0]) : null;
        return $result;
    }

    /**
     * @param null|string|int $scope null if scope is not used
     * @return array
     */
    public function getRoot($scope = null)
    {
        $roots = $this->getRoots($scope);
        return (0 < count($roots)) ? $roots[0] : [];
    }

    /**
     * @param null|string|int $scope if defined return root only for defined scope
     * @return array
     */
    public function getRoots($scope = null)
    {
        $options = $this->getOptions();
        $params = [];
        $sql = $this->getBlankDbSelect();
        $sql .= ' WHERE ' . $this->addTableName($options->getParentIdColumnName()) . ' IS NULL';
        if (null != $scope && $options->getScopeColumnName()) {
            $sql .= ' AND ' . $this->addTableName($options->getScopeColumnName()) . ' = :__scope';
            $params['__scope'] = $scope;
        }
        $sql .= ' ORDER BY ' . $this->addTableName($options->getIdColumnName()) . ' ASC';
        return $this->executeSelectSQL($sql, $params);
    }
}