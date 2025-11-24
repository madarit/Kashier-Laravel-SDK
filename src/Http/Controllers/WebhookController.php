<?php

namespace Madarit\LaravelKashier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Madarit\LaravelKashier\Events\WebhookReceived;
use Madarit\LaravelKashier\Events\PaymentSuccessful;
use Madarit\LaravelKashier\Events\PaymentFailed;

class WebhookController
{
    /**
     * Handle incoming webhook from Kashier.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        // Log webhook for debugging
        if (config('kashier.logging.enabled', true)) {
            Log::info('Kashier Webhook Received', [
                'payload' => $payload,
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        // Dispatch webhook received event
        event(new WebhookReceived($payload));

        // Handle based on payment status
        if (isset($payload['status']) || isset($payload['paymentStatus'])) {
            $status = $payload['status'] ?? $payload['paymentStatus'] ?? null;
            
            if (in_array(strtoupper($status), ['SUCCESS', 'CAPTURED'])) {
                event(new PaymentSuccessful($payload));
            } elseif (in_array(strtoupper($status), ['FAILURE', 'DECLINED', 'CANCELLED'])) {
                event(new PaymentFailed($payload));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully'
        ], 200);
    }
}
