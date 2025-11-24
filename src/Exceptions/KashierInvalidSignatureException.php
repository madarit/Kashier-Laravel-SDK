<?php

namespace Madarit\LaravelKashier\Exceptions;

class KashierInvalidSignatureException extends KashierException
{
    /**
     * Create a new invalid signature exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Invalid signature received from Kashier", $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
