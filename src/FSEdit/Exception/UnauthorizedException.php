<?php

namespace FSEdit\Exception;

class UnauthorizedException extends StatusException
{
    /**
     * UnauthorizedException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(401, $message, $previous);
    }
}