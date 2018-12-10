<?php

namespace FSEdit;

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
}