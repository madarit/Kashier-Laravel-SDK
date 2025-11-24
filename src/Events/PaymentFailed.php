<?php

namespace Madarit\LaravelKashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
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
     * Get the failure reason.
     *
     * @return string|null
     */
    public function getFailureReason(): ?string
    {
        return $this->payment['failureReason'] ?? $this->payment['message'] ?? null;
    }

    /**
     * Get the payment status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->payment['status'] ?? $this->payment['paymentStatus'] ?? null;
    }
}
