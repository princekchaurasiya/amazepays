<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;

final class UnlimitPaymentAttemptRecorder
{
    /**
     * Record/update a Unlimit payment attempt on the consolidated {@see Payment} table.
     *
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $extraAttributes
     */
    public function record(
        string|int $orderId,
        ?int $userId,
        string $merchantOrderId,
        float $amount,
        array $raw = [],
        ?string $gatewayPaymentId = null,
        string $paymentMethod = 'bankcard',
        array $extraAttributes = [],
    ): Payment {
        $order = Order::query()->whereKey((int) $orderId)->first();
        if (! $order) {
            // Some legacy flows (VD/KGen) still initiate payments without a row in the `orders` table.
            // The Phase 3 consolidated `payments` table requires an Order FK, so we skip persistence.
            return new Payment;
        }

        $payment = Payment::query()
            ->where('gateway', 'unlimit')
            ->where('merchant_order_id', $merchantOrderId)
            ->first();

        $amountMinor = (int) round($amount * 100);
        $status = ($extraAttributes['payment_status'] ?? null) === 'paid' ? 'captured' : 'initiated';

        $payment ??= new Payment;
        $payment->fill([
            'tenant_id' => (int) $order->tenant_id,
            'order_id' => (int) $order->id,
            'user_id' => $userId,
            'gateway' => 'unlimit',
            'environment' => (string) (config('app.env') === 'production' ? 'production' : 'sandbox'),
            'merchant_order_id' => $merchantOrderId,
            'gateway_payment_id' => $gatewayPaymentId,
            'status' => $status,
            'method_category' => 'card',
            'method_detail' => $paymentMethod,
            'amount_minor' => $amountMinor,
            'currency' => 'INR',
            'fee_minor' => 0,
            'tax_on_fee_minor' => 0,
            'settlement_amount_minor' => $amountMinor,
            'idempotency_key' => $extraAttributes['idempotency_key'] ?? ('unlimit-init-'.$merchantOrderId),
            'initiated_at' => $payment->initiated_at ?? now(),
            'captured_at' => $status === 'captured' ? now() : $payment->captured_at,
        ]);
        $payment->save();

        PaymentEvent::create([
            'payment_id' => (int) $payment->id,
            'source' => 'initiate',
            'event_type' => 'initiate',
            'gateway_status' => $payment->status,
            'raw_payload' => json_encode($raw),
            'signature_header' => null,
            'signature_verified' => false,
            'received_from_ip' => request()?->ip(),
            'dispatch_id' => null,
            'occurred_at' => now(),
        ]);

        return $payment;
    }
}
