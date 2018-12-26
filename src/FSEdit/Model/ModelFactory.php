<?php

namespace FSEdit\Model;

trait ModelFactory
{
    protected function User($id = null)
    {
        return new User($this->getDatabase(), $id);
    }

    protected function Workspace($id = null)
    {
        return new Workspace($this->getDatabase(), $id);
    }

    protected abstract function getDatabase();
}