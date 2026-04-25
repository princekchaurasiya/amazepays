<?php

declare(strict_types=1);

namespace Tests\Guards;

use App\Contracts\VoucherOrderResult;
use App\Models\GiftCard;
use App\Models\GiftCardEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Order\WoohooGiftCardPersister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WoohooGiftCardPersistTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function woohoo_gift_card_persister_creates_card_and_issued_event_once(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $now = now();
        $brandId = DB::table('brands')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Brand',
            'slug' => 'test-brand-'.$tenant->id,
            'status' => 'active',
            'is_featured' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'brand_id' => $brandId,
            'sku' => 'SKU-'.$tenant->id,
            'name' => 'Product',
            'slug' => 'product-'.$tenant->id,
            'currency' => 'INR',
            'status' => 'active',
            'type' => 'gift_card',
            'delivery_mode' => 'digital',
            'is_featured' => false,
            'is_b2b_only' => false,
            'is_b2c_only' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'grand_total_minor' => 50_000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU-1',
            'name_snapshot' => 'Product',
            'quantity' => 1,
            'unit_amount_minor' => 50_000,
            'line_subtotal_minor' => 50_000,
            'line_discount_minor' => 0,
            'line_tax_minor' => 0,
            'line_total_minor' => 50_000,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        $persister = app(WoohooGiftCardPersister::class);
        $payload = [
            [
                'cardNumber' => '6038931234567890',
                'cardPin' => '1234',
                'amount' => 500.0,
            ],
        ];

        $persister->syncWoohooCards($order->fresh(), $payload);

        $this->assertSame(1, GiftCard::query()->count());
        $this->assertSame(1, GiftCardEvent::query()->count());
        $this->assertSame('issued', (string) GiftCardEvent::query()->value('event_type'));

        $persister->syncWoohooCards($order->fresh(), $payload);

        $this->assertSame(1, GiftCard::query()->count());
        $this->assertSame(1, GiftCardEvent::query()->count());
    }

    #[Test]
    public function display_cards_for_order_returns_storefront_card_shape(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $now = now();
        $brandId = DB::table('brands')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'B2',
            'slug' => 'b2-'.$tenant->id,
            'status' => 'active',
            'is_featured' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'brand_id' => $brandId,
            'sku' => 'SKU2-'.$tenant->id,
            'name' => 'P2',
            'slug' => 'p2-'.$tenant->id,
            'currency' => 'INR',
            'status' => 'active',
            'type' => 'gift_card',
            'delivery_mode' => 'digital',
            'is_featured' => false,
            'is_b2b_only' => false,
            'is_b2c_only' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU2',
            'name_snapshot' => 'P2',
            'quantity' => 1,
            'unit_amount_minor' => 100_00,
            'line_subtotal_minor' => 100_00,
            'line_discount_minor' => 0,
            'line_tax_minor' => 0,
            'line_total_minor' => 100_00,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        $persister = app(WoohooGiftCardPersister::class);
        $persister->syncWoohooCards($order->fresh(), [
            ['cardNumber' => '4111111111111111', 'cardPin' => '9999', 'amount' => 100.0],
        ]);

        $display = $persister->displayCardsForOrder($order->fresh(['giftCards']));
        $this->assertCount(1, $display);
        $this->assertSame('4111111111111111', $display[0]['cardNumber'] ?? null);
        $this->assertSame('9999', $display[0]['cardPin'] ?? null);
        $this->assertSame(100.0, (float) ($display[0]['amount'] ?? 0));
    }

    #[Test]
    public function api_summaries_for_order_exclude_sensitive_fields(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $now = now();
        $brandId = DB::table('brands')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'B3',
            'slug' => 'b3-'.$tenant->id,
            'status' => 'active',
            'is_featured' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'brand_id' => $brandId,
            'sku' => 'SKU3-'.$tenant->id,
            'name' => 'P3',
            'slug' => 'p3-'.$tenant->id,
            'currency' => 'INR',
            'status' => 'active',
            'type' => 'gift_card',
            'delivery_mode' => 'digital',
            'is_featured' => false,
            'is_b2b_only' => false,
            'is_b2c_only' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU3',
            'name_snapshot' => 'P3',
            'quantity' => 1,
            'unit_amount_minor' => 100_00,
            'line_subtotal_minor' => 100_00,
            'line_discount_minor' => 0,
            'line_tax_minor' => 0,
            'line_total_minor' => 100_00,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        $persister = app(WoohooGiftCardPersister::class);
        $persister->syncWoohooCards($order->fresh(), [
            ['cardNumber' => '4111111111111111', 'cardPin' => '9999', 'amount' => 100.0],
        ]);

        $summaries = $persister->apiSummariesForOrder($order->fresh(['giftCards']));
        $this->assertCount(1, $summaries);
        $row = $summaries[0];
        $this->assertArrayNotHasKey('card_number_encrypted', $row);
        $this->assertArrayNotHasKey('card_pin_encrypted', $row);
        $this->assertSame('1111', $row['card_last4'] ?? null);
        $this->assertSame(100.0, (float) ($row['face_value'] ?? 0));
        $this->assertSame('woohoo', (string) ($row['provider'] ?? ''));
    }

    #[Test]
    public function voucher_order_result_persists_non_woohoo_provider_gift_card(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $now = now();
        $brandId = DB::table('brands')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'B4',
            'slug' => 'b4-'.$tenant->id,
            'status' => 'active',
            'is_featured' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'tenant_id' => $tenant->id,
            'brand_id' => $brandId,
            'sku' => 'SKU4-'.$tenant->id,
            'name' => 'P4',
            'slug' => 'p4-'.$tenant->id,
            'currency' => 'INR',
            'status' => 'active',
            'type' => 'gift_card',
            'delivery_mode' => 'digital',
            'is_featured' => false,
            'is_b2b_only' => false,
            'is_b2c_only' => false,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'grand_total_minor' => 250_00,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU4',
            'name_snapshot' => 'P4',
            'quantity' => 1,
            'unit_amount_minor' => 250_00,
            'line_subtotal_minor' => 250_00,
            'line_discount_minor' => 0,
            'line_tax_minor' => 0,
            'line_total_minor' => 250_00,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        $result = new VoucherOrderResult(
            success: true,
            status: 'fulfilled',
            providerOrderId: 'PO-1',
            voucherCode: '9999111122223333',
            pin: '7788',
            expiryDate: '2030-12-31',
            voucherCodes: [
                ['code' => '9999111122223333', 'pin' => '7788', 'expiry' => '2030-12-31'],
            ],
        );

        app(WoohooGiftCardPersister::class)->persistFromVoucherOrderResult($order->fresh(), $result, 'vouchagram_pull');

        $this->assertSame(1, GiftCard::query()->count());
        $gc = GiftCard::query()->first();
        $this->assertSame('vouchagram_pull', (string) $gc->provider);
        $this->assertSame('3333', (string) $gc->card_last4);
        $this->assertSame('9999111122223333', (string) $gc->card_number_encrypted);
    }
}
