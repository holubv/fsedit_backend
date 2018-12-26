<?php

namespace FSEdit\Model;

class User extends Model
{
    protected static $tableName = 'users';

    private $email;
    private $password;

    private $session = false;

    /**
     *
     */
    public function markAsSession()
    {
        $this->session = true;
    }

    /**
     * @return bool
     */
    public function isSession()
    {
        return $this->session;
    }

    protected function mapFields($result)
    {
        $this->id = (int)$result['id'];
        $this->email = $result['email'];
        $this->password = $result['password'];
    }
}