<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderCreationService;
use App\Services\Order\OrderFulfillmentOrchestrator;
use Illuminate\Support\Facades\Log;

/**
 * Single orchestration entry for payment gateways (strategy via PaymentGatewayInterface).
 */
class PaymentService
{
    public function __construct(
        private OrderCreationService $orderCreation,
        private OrderFulfillmentOrchestrator $fulfillmentOrchestrator,
    ) {}

    public function authorize(Order $order, User $user): void
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new \InvalidArgumentException('Order does not belong to this user.');
        }
        $apiStatus = strtolower((string) ($order->status ?? ''));
        $legacy = strtoupper((string) ($order->order_status ?? ''));
        $awaiting = $apiStatus === 'pending' || $legacy === 'PENDING' || $legacy === 'INITIATED'
            || ($apiStatus === '' && $legacy === '');
        if (! $awaiting) {
            throw new \InvalidArgumentException('Order is not awaiting payment.');
        }
    }

    public function initiate(Order $order, string $gateway, User $user, array $meta = []): PaymentInitiateResult
    {
        $this->authorize($order, $user);
        $gw = PaymentGatewayFactory::make($gateway);
        $amount = (float) ($order->grand_total ?? $order->grand_payable_amount ?? $order->amount ?? 0);
        $payload = array_merge([
            'order_id' => (string) ($order->order_number ?? $order->id),
            'amount' => $amount,
            'currency' => $order->currency ?? 'INR',
            'product_name' => $order->product_name ?? 'Order',
            'customer_email' => $user->email ?? '',
            'customer_name' => $user->name ?? '',
            'customer_phone' => $user->mobile ?? '',
        ], $meta);

        return $gw->initiatePayment($payload);
    }

    public function handleGatewayCallback(string $gateway, array $payload): PaymentCallbackResult
    {
        $gw = PaymentGatewayFactory::make($gateway);
        $result = $gw->handleCallback($payload);
        try {
            $this->syncOrderFromCallback($result);
        } catch (\Throwable $e) {
            Log::warning('Payment callback order sync failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    public function refund(Order $order, float $amount, string $reason, string $gateway, ?string $transactionId = null): RefundResult
    {
        $gw = PaymentGatewayFactory::make($gateway);
        $txId = $transactionId ?? (string) ($order->merchant_order_id ?? $order->refno ?? '');

        return $gw->refund($txId, $amount, $reason);
    }

    public function queryStatus(string $gateway, string $transactionId): PaymentStatusResult
    {
        return PaymentGatewayFactory::make($gateway)->queryStatus($transactionId);
    }

    private function syncOrderFromCallback(PaymentCallbackResult $result): void
    {
        $ref = $result->gatewayOrderId
            ?? ($result->raw['merchant_order']['id'] ?? null)
            ?? ($result->raw['order_id'] ?? null);
        if (! $ref) {
            return;
        }
        $order = Order::where('order_number', $ref)->first();
        if (! $order) {
            return;
        }
        if ($result->isPaid() && $result->amount !== null) {
            try {
                $this->orderCreation->verifyPaymentAmount($order, $result->amount);
            } catch (\Throwable $e) {
                Log::warning('Payment amount verification failed on callback', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $order->update([
                    'status' => 'failed',
                    'order_status' => 'FAILED',
                    'remarks' => trim((string) ($order->remarks ?? '').' | PAYMENT_AMOUNT_MISMATCH'),
                ]);

                return;
            }
        }
        if ($result->isPaid()) {
            $order->update([
                'status' => 'paid',
                'order_status' => $order->order_status ?: 'PENDING',
            ]);
            $order->refresh();
            $this->fulfillmentOrchestrator->fulfillPaidOrder($order);
        } elseif (in_array($result->status, ['failed', 'cancelled'], true)) {
            $order->update([
                'status' => $result->status,
                'order_status' => 'FAILED',
            ]);
        }
    }
}
