<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Initiate a new payment and return the redirect URL or payment token.
     */
    public function initiatePayment(array $order): PaymentInitiateResult;

    /**
     * Verify and process a payment callback/webhook.
     */
    public function handleCallback(array $payload): PaymentCallbackResult;

    /**
     * Process a refund for a given transaction.
     */
    public function refund(string $transactionId, float $amount, string $reason): RefundResult;

    /**
     * Query the current status of a transaction.
     */
    public function queryStatus(string $transactionId): PaymentStatusResult;

    /**
     * Get the gateway identifier name.
     */
    public function getName(): string;

    /**
     * Whether this gateway supports recurring/subscription payments.
     */
    public function supportsRecurring(): bool;
}

/**
 * Result value objects for payment operations.
 */
class PaymentInitiateResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $paymentToken = null,
        public readonly ?string $gatewayOrderId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}
}

class PaymentCallbackResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,           // pending | paid | failed | cancelled
        public readonly ?string $transactionId = null,
        public readonly ?string $gatewayOrderId = null,
        public readonly ?float $amount = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $refundId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}
}

class PaymentStatusResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?float $amount = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}
}
