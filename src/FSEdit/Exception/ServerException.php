<?php

namespace FSEdit\Exception;

class ServerException extends StatusException
{
    /**
     * ServerException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(500, $message, $previous);
    }
}