<?php

namespace FSEdit\Exception;

class ForbiddenException extends StatusException
{
    /**
     * ForbiddenException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}