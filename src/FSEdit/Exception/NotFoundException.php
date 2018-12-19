<?php

namespace FSEdit\Exception;

class NotFoundException extends StatusException
{
    /**
     * NotFoundException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}