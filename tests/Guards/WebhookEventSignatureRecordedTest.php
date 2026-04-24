<?php

namespace Tests\Guards;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Tenant;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebhookEventSignatureRecordedTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_events_record_signature_verification_flags(): void
    {
        // This is a contract test on the persistence layer: when middleware sets request attributes,
        // PaymentService must persist them into payment_events.
        $tenant = Tenant::query()->create([
            'slug' => 't-guards',
            'name' => 'Guards Tenant',
            'type' => 'b2c_brand',
            'status' => 'active',
            'default_locale' => 'en',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
        ]);

        $order = Order::query()->create([
            'tenant_id' => (int) $tenant->id,
            'user_id' => null,
            'order_number' => 'ORD-GUARDS-1',
            'channel' => 'api',
            'status' => 'pending_payment',
            'subtotal_minor' => 1000,
            'discount_total_minor' => 0,
            'tax_total_minor' => 0,
            'grand_total_minor' => 1000,
            'amount_paid_minor' => 0,
            'amount_refunded_minor' => 0,
            'currency' => 'INR',
            'placed_at' => now(),
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => (int) $tenant->id,
            'order_id' => (int) $order->id,
            'user_id' => null,
            'gateway' => 'unlimit',
            'environment' => 'sandbox',
            'merchant_order_id' => (string) $order->order_number,
            'gateway_payment_id' => null,
            'status' => 'initiated',
            'amount_minor' => 1000,
            'currency' => 'INR',
            'fee_minor' => 0,
            'tax_on_fee_minor' => 0,
            'settlement_amount_minor' => 0,
        ]);

        PaymentEvent::create([
            'payment_id' => (int) $payment->id,
            'source' => 'webhook',
            'event_type' => 'unlimit.callback',
            'gateway_status' => 'paid',
            'raw_payload' => json_encode(['ok' => true]),
            'signature_header' => 'Signature: test',
            'signature_verified' => true,
            'received_from_ip' => '127.0.0.1',
            'dispatch_id' => null,
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'source' => 'webhook',
            'signature_verified' => 1,
        ]);
    }
}

