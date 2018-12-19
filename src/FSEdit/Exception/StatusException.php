<?php

namespace FSEdit\Exception;


class StatusException extends \RuntimeException
{
    private $status;

    /**
     * StatusException constructor.
     * @param int $status
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($status = 500, $message = '', $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}