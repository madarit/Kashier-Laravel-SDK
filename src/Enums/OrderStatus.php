<?php

namespace Madarit\LaravelKashier\Enums;

enum OrderStatus: string
{
    case INITIATED = 'INITIATED';
    case PENDING = 'PENDING';
    case PENDING_ACTION = 'PENDING_ACTION';
    case SUCCESS = 'SUCCESS';
    case CAPTURED = 'CAPTURED';
    case FAILURE = 'FAILURE';
    case DECLINED = 'DECLINED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';

    /**
     * Get a human-readable description of the status.
     *
     * @return string
     */
    public function description(): string
    {
        return match($this) {
            self::INITIATED => 'Payment has been initiated',
            self::PENDING => 'Payment is pending',
            self::PENDING_ACTION => 'Payment is pending user action',
            self::SUCCESS => 'Payment completed successfully',
            self::CAPTURED => 'Payment has been captured',
            self::FAILURE => 'Payment failed',
            self::DECLINED => 'Payment was declined',
            self::CANCELLED => 'Payment was cancelled',
            self::REFUNDED => 'Payment has been refunded',
        };
    }

    /**
     * Get the display name for the status.
     *
     * @return string
     */
    public function displayName(): string
    {
        return match($this) {
            self::INITIATED => 'Initiated',
            self::PENDING => 'Pending',
            self::PENDING_ACTION => 'Pending Action',
            self::SUCCESS => 'Success',
            self::CAPTURED => 'Captured',
            self::FAILURE => 'Failed',
            self::DECLINED => 'Declined',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get the CSS class for status styling.
     *
     * @return string
     */
    public function styleClass(): string
    {
        return match($this) {
            self::SUCCESS, self::CAPTURED => 'status-success',
            self::INITIATED, self::PENDING, self::PENDING_ACTION => 'status-info',
            self::FAILURE, self::DECLINED, self::CANCELLED => 'status-danger',
            self::REFUNDED => 'status-warning',
        };
    }

    /**
     * Get the text color for the status.
     *
     * @return string
     */
    public function textColor(): string
    {
        return match($this) {
            self::SUCCESS, self::CAPTURED => 'text-green-600',
            self::INITIATED, self::PENDING, self::PENDING_ACTION => 'text-blue-600',
            self::FAILURE, self::DECLINED, self::CANCELLED => 'text-red-600',
            self::REFUNDED => 'text-yellow-600',
        };
    }

    /**
     * Check if the status is successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return in_array($this, [self::SUCCESS, self::CAPTURED]);
    }

    /**
     * Check if the status is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return in_array($this, [self::INITIATED, self::PENDING, self::PENDING_ACTION]);
    }

    /**
     * Check if the status is failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return in_array($this, [self::FAILURE, self::DECLINED, self::CANCELLED]);
    }

    /**
     * Check if the status is refunded.
     *
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    /**
     * Get status from string value (case-insensitive).
     *
     * @param string $value
     * @return self|null
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom(strtoupper($value));
    }
}
