<?php

namespace FSEdit\Model;

class User extends Model
{
    protected static $tableName = 'users';

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $passwordHash
     */
    private $passwordHash;

    /**
     * @var bool $session is this user currently making request
     */
    private $session = false;

    /**
     * @param string $email
     * @return $this
     */
    public function loadByEmail($email)
    {
        return $this->load(['email' => $email]);
    }

    /**
     * @param string $email
     * @param string $password
     * @return $this
     */
    public function register($email, $password)
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new \Error();
        }
        $this->database->insert(static::$tableName, [
            'email' => $email,
            'password' => $hash
        ]);
        $this->id = $this->database->id();
        $this->email = $email;
        $this->passwordHash = $hash;
        return $this;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function comparePasswords($password)
    {
        return password_verify($password, $this->passwordHash);
    }

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

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    protected function mapFields($result)
    {
        $this->id = (int)$result['id'];
        $this->email = $result['email'];
        $this->passwordHash = $result['password'];
    }
}