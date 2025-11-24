<?php

namespace Madarit\LaravelKashier\Exceptions;

class KashierRefundException extends KashierException
{
    /**
     * Create a new refund exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Failed to process refund", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
