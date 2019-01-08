<?php

namespace FSEdit;

use FSEdit\Exception\SqlException;
use Medoo\Medoo;
use PDOStatement;

class DatabaseAdapter extends Medoo
{
    /**
     * @param string $table
     * @param mixed $join
     * @param mixed $columns
     * @param mixed $where
     * @return array|false
     */
    public function select($table, $join, $columns = null, $where = null)
    {
        $r = parent::select($table, $join, $columns, $where);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $datas
     * @return PDOStatement|false
     */
    public function insert($table, $datas)
    {
        $r = parent::insert($table, $datas);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $data
     * @param mixed $where
     * @return PDOStatement|false
     */
    public function update($table, $data, $where = null)
    {
        $r = parent::update($table, $data, $where);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $where
     * @return PDOStatement|false
     */
    public function delete($table, $where)
    {
        $r = parent::delete($table, $where);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $join
     * @param mixed $columns
     * @param mixed $where
     * @return array|null
     */
    public function get($table, $join = null, $columns = null, $where = null)
    {
        $r = parent::get($table, $join, $columns, $where);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $query
     * @param array $map
     * @return PDOStatement|false
     */
    public function query($query, $map = [])
    {
        $r = parent::query($query, $map);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $columns
     * @param mixed $where
     * @return PDOStatement|false
     */
    public function replace($table, $columns, $where = null)
    {
        $r = parent::replace($table, $columns, $where);
        return $this->checkErrorsAndReturn($r);
    }

    /**
     * @param string $table
     * @param mixed $join
     * @param mixed $where
     * @return bool
     */
    public function has($table, $join, $where = null)
    {
        $r = parent::has($table, $join, $where);
        return $this->checkErrorsAndReturn($r);
    }


    /**
     * @param mixed $result
     * @return mixed
     */
    private function checkErrorsAndReturn($result)
    {
        if ($this->error() && $this->error()[0] != 0) {
            throw new SqlException($this->error());
        }
        return $result;
    }
}