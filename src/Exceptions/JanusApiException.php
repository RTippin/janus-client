<?php

namespace RTippin\Janus\Exceptions;

use Exception;
use Throwable;

class JanusApiException extends Exception
{
    /**
     * JanusApiException constructor.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  Throwable|null  $previous
     */
    public function __construct(string $message = 'Janus API Failed.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
