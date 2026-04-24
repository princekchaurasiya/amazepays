<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use Illuminate\Support\Str;

/**
 * Mock gateway for local/testing when Razorpay credentials are unavailable.
 * Never enable in production.
 */
final class MockRazorpayGateway implements PaymentGatewayInterface
{
    public function __construct(private array $credentials = [])
    {
    }

    public function getName(): string
    {
        return 'mock_razorpay';
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    public function initiatePayment(array $order): PaymentInitiateResult
    {
        $merchantOrderId = (string) ($order['order_id'] ?? 'ORD-UNKNOWN');
        $token = 'mock_rzp_'.Str::random(24);

        return new PaymentInitiateResult(
            success: true,
            redirectUrl: route('mock.razorpay.pay', ['merchantOrderId' => $merchantOrderId, 'token' => $token]),
            paymentToken: $token,
            gatewayOrderId: $merchantOrderId,
            raw: ['mock' => true, 'merchant_order_id' => $merchantOrderId, 'token' => $token]
        );
    }

    public function handleCallback(array $payload): PaymentCallbackResult
    {
        $merchantOrderId = (string) ($payload['merchant_order_id'] ?? $payload['order_id'] ?? '');
        $status = (string) ($payload['mock_status'] ?? 'pending'); // paid|failed|cancelled|pending

        $mapped = match ($status) {
            'paid' => 'paid',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };

        return new PaymentCallbackResult(
            success: $mapped === 'paid',
            status: $mapped,
            transactionId: (string) ($payload['mock_payment_id'] ?? null),
            gatewayOrderId: $merchantOrderId !== '' ? $merchantOrderId : null,
            amount: isset($payload['amount']) && is_numeric($payload['amount']) ? (float) $payload['amount'] : null,
            paymentMethod: (string) ($payload['method'] ?? 'mock'),
            error: $mapped === 'failed' ? (string) ($payload['reason'] ?? 'mock_failed') : null,
            raw: $payload
        );
    }

    public function refund(string $transactionId, float $amount, string $reason): RefundResult
    {
        return new RefundResult(success: true, refundId: 'mock_ref_'.Str::random(12), raw: [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function queryStatus(string $transactionId): PaymentStatusResult
    {
        return new PaymentStatusResult(success: true, status: 'pending', amount: null, raw: ['transaction_id' => $transactionId]);
    }
}

