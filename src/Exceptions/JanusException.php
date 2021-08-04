<?php

namespace RTippin\Janus\Exceptions;

use Exception;

class JanusException extends Exception
{
    /**
     * JanusException constructor.
     *
     * @param string $message
     */
    public function __construct(string $message = 'Janus connection error.')
    {
        parent::__construct($message);
    }
}
