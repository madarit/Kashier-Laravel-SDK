<?php

namespace Madarit\LaravelKashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    /**
     * The refund data.
     *
     * @var array
     */
    public $refund;

    /**
     * Create a new event instance.
     *
     * @param  array  $refund
     * @return void
     */
    public function __construct(array $refund)
    {
        $this->refund = $refund;
    }

    /**
     * Get the order ID from refund data.
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->refund['orderId'] ?? $this->refund['order_id'] ?? null;
    }

    /**
     * Get the transaction ID from refund data.
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->refund['transactionId'] ?? $this->refund['transaction_id'] ?? null;
    }

    /**
     * Get the refund amount.
     *
     * @return float|null
     */
    public function getRefundAmount(): ?float
    {
        return isset($this->refund['amount']) ? (float) $this->refund['amount'] : null;
    }

    /**
     * Check if refund was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        $status = strtoupper($this->refund['status'] ?? '');
        return in_array($status, ['SUCCESS', 'REFUNDED', 'COMPLETED']);
    }
}
