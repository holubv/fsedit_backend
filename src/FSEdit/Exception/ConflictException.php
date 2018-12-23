<?php

namespace FSEdit\Exception;

class ConflictException extends StatusException
{
    /**
     * ConflictException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(409, $message, $previous);
    }
}