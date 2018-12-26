<?php

namespace FSEdit;

use FSEdit\Exception\NotFoundException;
use Medoo\Medoo;

abstract class Model
{
    protected static $tableName = null;
    protected static $primaryKey = 'id';

    /**
     * @var Medoo $database
     */
    protected $database;

    /**
     * @var int $id
     */
    protected $id;

    /**
     * Model constructor.
     * @param Medoo $database
     * @param int|null $id
     */
    public function __construct($database, $id = null)
    {
        $this->database = $database;
        if ($id !== null) {
            $this->id = (int)$id;
            $this->loadById($id);
        }
    }

    /**
     * @param int $id
     * @return $this
     */
    public function loadById($id)
    {
        $this->load([static::$primaryKey => (int)$id]);
        return $this;
    }

    /**
     * @param array $where
     * @return $this
     */
    public function load($where)
    {
        if (static::$tableName === null) {
            throw new \InvalidArgumentException('table name is not defined');
        }
        if (!$where) {
            throw new \InvalidArgumentException('no where clause is specified');
        }
        $result = $this->database->get(static::$tableName, '*', $where);
        if (!$result) {
            throw new NotFoundException();
        }
        $this->mapFields($result);
        return $this;
    }

    protected abstract function mapFields($result);
}