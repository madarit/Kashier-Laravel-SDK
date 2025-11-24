<?php

namespace Madarit\LaravelKashier\Exceptions;

class KashierConfigurationException extends KashierException
{
    /**
     * Create a new configuration exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Kashier configuration is invalid or missing", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
