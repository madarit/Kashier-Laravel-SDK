<?php

namespace Madarit\LaravelKashier\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Madarit\LaravelKashier\Exceptions\KashierInvalidSignatureException;

class VerifyCallbackSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws KashierInvalidSignatureException
     */
    public function handle(Request $request, Closure $next)
    {
        $queryParams = $request->query();
        
        if (!isset($queryParams['signature'])) {
            throw new KashierInvalidSignatureException('Missing signature parameter');
        }

        $mode = config('kashier.mode', 'test');
        $apiKey = config("kashier.{$mode}.api_key");

        if (!$apiKey) {
            throw new KashierInvalidSignatureException('Kashier API key not configured');
        }

        $receivedSignature = $queryParams['signature'];
        unset($queryParams['signature']);
        
        // Remove mode parameter if exists
        if (isset($queryParams['mode'])) {
            unset($queryParams['mode']);
        }

        // Build query string for signature validation
        $queryString = '';
        foreach ($queryParams as $key => $value) {
            $queryString .= "&{$key}={$value}";
        }
        $queryString = ltrim($queryString, '&');

        $expectedSignature = hash_hmac('sha256', $queryString, $apiKey);

        if (!hash_equals($expectedSignature, $receivedSignature)) {
            throw new KashierInvalidSignatureException('Invalid callback signature');
        }

        return $next($request);
    }
}
