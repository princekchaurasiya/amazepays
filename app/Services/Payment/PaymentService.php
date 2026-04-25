<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Jobs\FulfillPaidOrderJob;
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
        $amount = 0.0;
        if ($order->grand_total_minor !== null) {
            $amount = ((int) $order->grand_total_minor) / 100;
        } else {
            $amount = (float) ($order->amount_payable_after_discount ?? $order->grand_total ?? $order->grand_payable_amount ?? $order->amount ?? 0);
        }
        $payload = array_merge([
            'order_id' => (string) ($order->order_number ?? $order->id),
            'amount' => $amount,
            'currency' => $order->currency ?? 'INR',
            'product_name' => $order->product_name ?? 'Order',
            'customer_email' => $user->email ?? '',
            'customer_name' => $user->name ?? '',
            'customer_phone' => $user->mobile ?? '',
        ], $meta);

        $result = $gw->initiatePayment($payload);

        // Persist to consolidated payments table (best-effort; legacy flows still exist).
        try {
            $amountMinor = (int) round($amount * 100);
            $merchantOrderId = (string) ($order->order_number ?? $order->id);

            $payment = Payment::query()
                ->where('gateway', $gateway)
                ->where('merchant_order_id', $merchantOrderId)
                ->first();

            $payment ??= new Payment();
            $payment->fill([
                'tenant_id' => (int) $order->tenant_id,
                'order_id' => (int) $order->id,
                'user_id' => (int) $user->id,
                'gateway' => $gateway,
                'environment' => config('app.env') === 'production' ? 'production' : 'sandbox',
                'merchant_order_id' => $merchantOrderId,
                'gateway_payment_id' => $result->paymentToken,
                'gateway_reference' => $result->gatewayOrderId,
                'status' => $result->success ? 'initiated' : 'failed',
                'method_category' => $meta['method_category'] ?? null,
                'method_detail' => $meta['payment_method'] ?? null,
                'amount_minor' => $amountMinor,
                'currency' => (string) ($order->currency ?? 'INR'),
                'fee_minor' => 0,
                'tax_on_fee_minor' => 0,
                'settlement_amount_minor' => $amountMinor,
                'idempotency_key' => $meta['idempotency_key'] ?? null,
                'initiated_at' => now(),
                'failed_at' => $result->success ? null : now(),
                'failure_reason' => $result->success ? null : $result->error,
            ]);
            $payment->save();

            PaymentEvent::create([
                'payment_id' => (int) $payment->id,
                'source' => 'initiate',
                'event_type' => 'initiate',
                'gateway_status' => $payment->status,
                'raw_payload' => json_encode($result->raw),
                'signature_header' => null,
                'signature_verified' => false,
                'received_from_ip' => null,
                'dispatch_id' => null,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Payment persistence (initiate) failed', [
                'gateway' => $gateway,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    public function handleGatewayCallback(string $gateway, array $payload): PaymentCallbackResult
    {
        $gw = PaymentGatewayFactory::make($gateway);
        $result = $gw->handleCallback($payload);
        $this->persistCallbackEventBestEffort($gateway, $payload, $result);
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

    /**
     * Legacy entrypoint for non-Order payment flows (KGen/VD, etc.).
     *
     * @param  array<string, mixed>  $payload
     */
    public function initiateByPayload(string $gateway, array $payload): PaymentInitiateResult
    {
        /** @var PaymentGatewayInterface $gw */
        $gw = PaymentGatewayFactory::make($gateway);

        return $gw->initiatePayment($payload);
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
            FulfillPaidOrderJob::dispatch($order->id)->onQueue('fulfillment');
        } elseif (in_array($result->status, ['failed', 'cancelled'], true)) {
            $order->update([
                'status' => $result->status,
                'order_status' => 'FAILED',
            ]);
        }
    }

    private function persistCallbackEventBestEffort(string $gateway, array $payload, PaymentCallbackResult $result): void
    {
        try {
            $order = $this->resolveOrderFromGatewayPayload($gateway, $payload, $result);

            if (! $order) {
                return;
            }

            $merchantOrderId = (string) ($order->order_number ?? $order->id);

            $payment = Payment::query()
                ->where('gateway', $gateway)
                ->where('merchant_order_id', $merchantOrderId)
                ->first();

            if (! $payment) {
                // Create if missing (legacy flows may not have initiated through this service)
                $amountMinor = (int) round(((float) ($result->amount ?? 0)) * 100);
                $payment = Payment::create([
                    'tenant_id' => (int) $order->tenant_id,
                    'order_id' => (int) $order->id,
                    'user_id' => (int) ($order->user_id ?? null),
                    'gateway' => $gateway,
                    'environment' => config('app.env') === 'production' ? 'production' : 'sandbox',
                    'merchant_order_id' => $merchantOrderId,
                    'gateway_payment_id' => $result->transactionId,
                    'gateway_reference' => $result->gatewayOrderId,
                    'status' => 'initiated',
                    'amount_minor' => $amountMinor > 0 ? $amountMinor : (int) round(((float) ($order->grand_total_minor ?? 0)) * 1),
                    'currency' => (string) ($order->currency ?? 'INR'),
                    'fee_minor' => 0,
                    'tax_on_fee_minor' => 0,
                    'settlement_amount_minor' => 0,
                    'initiated_at' => now(),
                ]);
            }

            $newStatus = match ($result->status) {
                'paid' => 'captured',
                'failed' => 'failed',
                'cancelled' => 'cancelled',
                default => 'pending',
            };

            $payment->fill([
                'gateway_payment_id' => $payment->gateway_payment_id ?: $result->transactionId,
                'gateway_reference' => $payment->gateway_reference ?: $result->gatewayOrderId,
                'status' => $newStatus,
                'captured_at' => $newStatus === 'captured' ? now() : $payment->captured_at,
                'failed_at' => $newStatus === 'failed' ? now() : $payment->failed_at,
                'failure_reason' => $newStatus === 'failed' ? $result->error : $payment->failure_reason,
            ]);
            $payment->save();

            PaymentEvent::create([
                'payment_id' => (int) $payment->id,
                'source' => 'webhook',
                'event_type' => $gateway.'.callback',
                'gateway_status' => $result->status,
                'raw_payload' => json_encode($payload),
                'signature_header' => (string) (request()?->attributes->get('signature_header') ?? ''),
                'signature_verified' => (bool) (request()?->attributes->get('signature_verified') ?? false),
                'received_from_ip' => request()?->ip(),
                'dispatch_id' => null,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Payment persistence (callback) failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveOrderFromGatewayPayload(string $gateway, array $payload, PaymentCallbackResult $result): ?Order
    {
        // 1) Unlimit: merchant_order.id is our order_number
        if ($gateway === 'unlimit') {
            $ref = $payload['merchant_order']['id'] ?? $result->gatewayOrderId ?? null;
            if (is_string($ref) && $ref !== '') {
                return Order::query()->where('order_number', $ref)->first();
            }
        }

        // 2) CCAvenue: decrypted callback yields order_id = our order_number (in gateway raw)
        if ($gateway === 'ccavenue') {
            $ref = $result->gatewayOrderId ?? ($result->raw['order_id'] ?? null);
            if (is_string($ref) && $ref !== '') {
                return Order::query()->where('order_number', $ref)->first();
            }
        }

        // 3) Razorpay: webhook order.id is Razorpay order id; our order_number is stored as receipt
        if ($gateway === 'razorpay') {
            $receipt = $payload['payload']['order']['entity']['receipt'] ?? null;
            if (is_string($receipt) && $receipt !== '') {
                return Order::query()->where('order_number', $receipt)->first();
            }
        }

        // Generic fallback
        $ref = $result->gatewayOrderId
            ?? ($result->raw['merchant_order']['id'] ?? null)
            ?? ($result->raw['order_id'] ?? null);
        if (is_string($ref) && $ref !== '') {
            $order = Order::query()->where('order_number', $ref)->first();
            if ($order) {
                return $order;
            }
        }

        $merchantOrderId = (string) ($payload['merchant_order_id'] ?? '');
        if ($merchantOrderId !== '') {
            return Order::query()->where('merchant_order_id', $merchantOrderId)->first();
        }

        return null;
    }
}
