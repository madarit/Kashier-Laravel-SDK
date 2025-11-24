<?php

namespace Madarit\LaravelKashier;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Madarit\LaravelKashier\Exceptions\KashierConnectionException;
use Madarit\LaravelKashier\Exceptions\KashierRefundException;
use Madarit\LaravelKashier\Exceptions\KashierConfigurationException;
use Madarit\LaravelKashier\Events\RefundProcessed;

class KashierService
{
    private $config;
    private $mode;

    public function __construct()
    {
        $this->mode = config('kashier.mode');
        $this->config = config("kashier.{$this->mode}");
    }

    /**
     * Generate order hash for Kashier payment
     *
     * @param string $orderId
     * @param string $amount
     * @param string $currency
     * @param string|null $customerReference
     * @return string
     */
    public function generateOrderHash($orderId, $amount, $currency, $customerReference = null)
    {
        $mid = $this->config['mid'];
        $secret = $this->config['api_key'];
        
        $path = "/?payment={$mid}.{$orderId}.{$amount}.{$currency}";
        if ($customerReference) {
            $path .= ".{$customerReference}";
        }
        
        return hash_hmac('sha256', $path, $secret, false);
    }

    /**
     * Validate callback signature
     *
     * @param array $queryParams
     * @return bool
     */
    public function validateSignature($queryParams)
    {
        $queryString = '';
        $secret = $this->config['api_key'];
        
        foreach ($queryParams as $key => $value) {
            if ($key === 'signature' || $key === 'mode') {
                continue;
            }
            $queryString .= "&{$key}={$value}";
        }
        
        $queryString = ltrim($queryString, '&');
        $signature = hash_hmac('sha256', $queryString, $secret, false);
        
        return $signature === ($queryParams['signature'] ?? '');
    }

    /**
     * Generate Hosted Payment Page URL
     *
     * @param string $orderId
     * @param string $amount
     * @param string $currency
     * @param string $callbackUrl
     * @param string $allowedMethods
     * @return string
     */
    public function getHppUrl($orderId, $amount, $currency, $callbackUrl, $allowedMethods = 'card,wallet,bank_installments')
    {
        $hash = $this->generateOrderHash($orderId, $amount, $currency);
        $mid = $this->config['mid'];
        $baseUrl = $this->config['base_url'];
        $encodedCallback = urlencode($callbackUrl);
        
        return "{$baseUrl}?merchantId={$mid}&orderId={$orderId}&mode={$this->mode}" .
               "&amount={$amount}&currency={$currency}&hash={$hash}" .
               "&merchantRedirect={$encodedCallback}&allowedMethods={$allowedMethods}&display=en";
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get current mode (test/live)
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Get merchant ID
     *
     * @return string
     */
    public function getMid()
    {
        return $this->config['mid'];
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->config['base_url'];
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public function getApiBaseUrl()
    {
        return $this->config['api_url'] ?? ($this->mode === 'live' 
            ? 'https://api.kashier.io' 
            : 'https://test-api.kashier.io');
    }

    /**
     * Process a refund for a transaction
     *
     * @param string $orderId
     * @param string $transactionId
     * @param float|null $amount Optional partial refund amount
     * @param string|null $reason Optional refund reason
     * @return array
     * @throws KashierConfigurationException
     * @throws KashierConnectionException
     * @throws KashierRefundException
     */
    public function refund($orderId, $transactionId, $amount = null, $reason = null)
    {
        if (!$this->config['api_key']) {
            throw new KashierConfigurationException('API key is not configured');
        }

        $apiUrl = $this->getApiBaseUrl();
        $url = "{$apiUrl}/orders/{$orderId}/transactions/{$transactionId}?operation=refund";

        $payload = [];
        if ($amount !== null) {
            $payload['amount'] = $amount;
        }
        if ($reason !== null) {
            $payload['reason'] = $reason;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->put($url, $payload);

            if ($response->failed()) {
                $errorMessage = $response->json()['message'] ?? 'Refund request failed';
                throw new KashierRefundException($errorMessage, $response->status());
            }

            $result = $response->json();

            // Log refund
            if (config('kashier.logging.enabled', true)) {
                Log::info('Kashier Refund Processed', [
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'status' => $result['status'] ?? 'unknown',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Dispatch event
            event(new RefundProcessed($result));

            return $result;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new KashierConnectionException('Failed to connect to Kashier API: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            if ($e instanceof KashierRefundException || $e instanceof KashierConnectionException) {
                throw $e;
            }
            throw new KashierRefundException('Unexpected error during refund: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get refund status
     *
     * @param string $orderId
     * @param string $transactionId
     * @return array
     * @throws KashierConfigurationException
     * @throws KashierConnectionException
     */
    public function getRefundStatus($orderId, $transactionId)
    {
        if (!$this->config['api_key']) {
            throw new KashierConfigurationException('API key is not configured');
        }

        $apiUrl = $this->getApiBaseUrl();
        $url = "{$apiUrl}/orders/{$orderId}/transactions/{$transactionId}";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                throw new KashierConnectionException('Failed to get refund status', $response->status());
            }

            return $response->json();

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new KashierConnectionException('Failed to connect to Kashier API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a full refund (convenience method)
     *
     * @param string $orderId
     * @param string $transactionId
     * @param string|null $reason
     * @return array
     */
    public function fullRefund($orderId, $transactionId, $reason = null)
    {
        return $this->refund($orderId, $transactionId, null, $reason);
    }

    /**
     * Create a partial refund (convenience method)
     *
     * @param string $orderId
     * @param string $transactionId
     * @param float $amount
     * @param string|null $reason
     * @return array
     */
    public function partialRefund($orderId, $transactionId, $amount, $reason = null)
    {
        return $this->refund($orderId, $transactionId, $amount, $reason);
    }
}
