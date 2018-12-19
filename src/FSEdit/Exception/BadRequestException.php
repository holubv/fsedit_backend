<?php

namespace FSEdit\Exception;


class BadRequestException extends StatusException
{
    /**
     * BadRequestException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(400, $message, $previous);
    }
}