<?php


namespace FSEdit\Exception;


class SqlException extends ServerException
{
    const SQL_INTEGRITY_VIOLATION = '23000';

    const MYSQL_DUPLICATE_ENTRY = 1062;

    /**
     * @var string $sqlState
     */
    private $sqlState;

    /**
     * @var int $sqlError
     */
    private $sqlError;

    /**
     * @var string $sqlMessage
     */
    private $sqlMessage;

    /**
     * SqlException constructor.
     * @param array $errorInfo - pdo errorInfo
     */
    public function __construct($errorInfo)
    {
        $this->sqlState = (string)$errorInfo[0];
        $this->sqlError = (int)$errorInfo[1];
        $this->sqlMessage = (string)$errorInfo[2];
        parent::__construct(json_encode($errorInfo));
    }

    /**
     * @return string
     */
    public function getSqlState()
    {
        return $this->sqlState;
    }

    /**
     * @return int
     */
    public function getSqlError()
    {
        return $this->sqlError;
    }

    /**
     * @return string
     */
    public function getSqlMessage()
    {
        return $this->sqlMessage;
    }

    /**
     * @return bool true if error is 1062
     */
    public function isDuplicateError()
    {
        return $this->sqlError === self::MYSQL_DUPLICATE_ENTRY;
    }
}