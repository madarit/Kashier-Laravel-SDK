<?php

namespace Madarit\LaravelKashier\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Madarit\LaravelKashier\Exceptions\KashierInvalidSignatureException;

class VerifyWebhookSignature
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
        $signature = $request->header('X-Kashier-Signature');
        
        if (!$signature) {
            throw new KashierInvalidSignatureException('Missing signature header');
        }

        $mode = config('kashier.mode', 'test');
        $apiKey = config("kashier.{$mode}.api_key");

        if (!$apiKey) {
            throw new KashierInvalidSignatureException('Kashier API key not configured');
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $apiKey);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new KashierInvalidSignatureException('Invalid webhook signature');
        }

        return $next($request);
    }
}
