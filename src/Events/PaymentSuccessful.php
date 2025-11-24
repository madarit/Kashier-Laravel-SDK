<?php

namespace Madarit\LaravelKashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessful
{
    use Dispatchable, SerializesModels;

    /**
     * The payment data.
     *
     * @var array
     */
    public $payment;

    /**
     * Create a new event instance.
     *
     * @param  array  $payment
     * @return void
     */
    public function __construct(array $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the order ID from payment data.
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->payment['merchantOrderId'] ?? $this->payment['orderId'] ?? null;
    }

    /**
     * Get the transaction ID from payment data.
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->payment['transactionId'] ?? $this->payment['transaction_id'] ?? null;
    }

    /**
     * Get the payment amount.
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return isset($this->payment['amount']) ? (float) $this->payment['amount'] : null;
    }
}
