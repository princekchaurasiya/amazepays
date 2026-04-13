<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayGateway implements PaymentGatewayInterface
{
    private Api $api;

    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'key_id' => config('services.razorpay.key_id'),
            'key_secret' => config('services.razorpay.key_secret'),
            'webhook_secret' => config('services.razorpay.webhook_secret'),
        ] : $credentials;

        $this->api = new Api(
            $this->config['key_id'],
            $this->config['key_secret'],
        );
    }

    public function getName(): string
    {
        return 'razorpay';
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function initiatePayment(array $order): PaymentInitiateResult
    {
        try {
            $razorOrder = $this->api->order->create([
                'amount' => (int) ($order['amount'] * 100),  // Razorpay expects paise
                'currency' => $order['currency'] ?? 'INR',
                'receipt' => $order['order_id'],
                'notes' => [
                    'user_id' => $order['user_id'] ?? null,
                    'product_id' => $order['product_id'] ?? null,
                ],
                'payment_capture' => 1,
            ]);

            return new PaymentInitiateResult(
                success: true,
                gatewayOrderId: $razorOrder->id,
                paymentToken: $razorOrder->id,
                raw: $razorOrder->toArray(),
            );
        } catch (\Exception $e) {
            Log::error('Razorpay initiate payment failed', ['error' => $e->getMessage()]);

            return new PaymentInitiateResult(success: false, error: $e->getMessage());
        }
    }

    public function handleCallback(array $payload): PaymentCallbackResult
    {
        try {
            // Webhook event handling
            if (isset($payload['event'])) {
                return $this->handleWebhook($payload);
            }

            // Frontend payment verification
            $signature = $payload['razorpay_signature'] ?? '';
            $paymentId = $payload['razorpay_payment_id'] ?? '';
            $orderId = $payload['razorpay_order_id'] ?? '';

            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            $payment = $this->api->payment->fetch($paymentId);

            $status = match ($payment->status) {
                'captured', 'authorized' => 'paid',
                'failed' => 'failed',
                default => 'pending',
            };

            return new PaymentCallbackResult(
                success: $status === 'paid',
                status: $status,
                transactionId: $paymentId,
                gatewayOrderId: $orderId,
                amount: $payment->amount / 100,
                paymentMethod: $payment->method,
                raw: $payment->toArray(),
            );
        } catch (\Exception $e) {
            Log::error('Razorpay callback failed', ['error' => $e->getMessage()]);

            return new PaymentCallbackResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount, string $reason): RefundResult
    {
        try {
            $refund = $this->api->payment->fetch($transactionId)->refund([
                'amount' => (int) ($amount * 100),
                'notes' => ['reason' => $reason],
            ]);

            return new RefundResult(
                success: true,
                refundId: $refund->id,
                raw: $refund->toArray(),
            );
        } catch (\Exception $e) {
            Log::error('Razorpay refund failed', ['error' => $e->getMessage()]);

            return new RefundResult(success: false, error: $e->getMessage());
        }
    }

    public function queryStatus(string $transactionId): PaymentStatusResult
    {
        try {
            $payment = $this->api->payment->fetch($transactionId);

            $status = match ($payment->status) {
                'captured', 'authorized' => 'paid',
                'failed' => 'failed',
                default => 'pending',
            };

            return new PaymentStatusResult(
                success: true,
                status: $status,
                amount: $payment->amount / 100,
                raw: $payment->toArray(),
            );
        } catch (\Exception $e) {
            return new PaymentStatusResult(success: false, status: 'unknown', error: $e->getMessage());
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config['webhook_secret'];

        return hash_hmac('sha256', $payload, $secret) === $signature;
    }

    private function handleWebhook(array $payload): PaymentCallbackResult
    {
        $event = $payload['event'] ?? '';
        $payment = $payload['payload']['payment']['entity'] ?? [];

        $status = match ($event) {
            'payment.captured' => 'paid',
            'payment.failed' => 'failed',
            default => 'pending',
        };

        return new PaymentCallbackResult(
            success: $status === 'paid',
            status: $status,
            transactionId: $payment['id'] ?? null,
            gatewayOrderId: $payment['order_id'] ?? null,
            amount: isset($payment['amount']) ? $payment['amount'] / 100 : null,
            paymentMethod: $payment['method'] ?? null,
            raw: $payload,
        );
    }
}
