<?php

namespace Madarit\LaravelKashier\Exceptions;

class KashierConnectionException extends KashierException
{
    /**
     * Create a new connection exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Failed to connect to Kashier API", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
