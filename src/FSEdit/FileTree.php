<?php

namespace FSEdit;

use FSEdit\NestedSet\Adapter\Pdo;
use \StefanoTree\NestedSet;


class FileTree extends NestedSet
{
    public function __construct($pdo)
    {
        $options = new NestedSet\Options([
            'tableName' => 'file_tree',
            'idColumnName' => 'id',
            'scopeColumnName' => 'workspace_id',
        ]);
        parent::__construct(new Pdo($options, $pdo));
    }
}