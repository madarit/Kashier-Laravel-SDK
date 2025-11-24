<?php

namespace Madarit\LaravelKashier\Exceptions;

use Exception;

class KashierException extends Exception
{
    /**
     * Create a new Kashier exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
