<?php

namespace Tests\Guards;

use App\Models\LoyaltyAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Services\Checkout\ApplyLoyaltyRedemption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LoyaltyRedemptionContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_loyalty_redemption_writes_ledger_and_discount_rows(): void
    {
        $platformTenantId = (int) Tenant::query()->create([
            'slug' => 'platform',
            'name' => 'AmazePays Platform',
            'display_name' => 'AmazePays',
            'type' => 'platform',
            'status' => 'active',
            'default_locale' => 'en',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
        ])->id;

        $programId = (int) DB::table('loyalty_programs')->insertGetId([
            'tenant_id' => $platformTenantId,
            'name' => 'AmazePays Rewards',
            'slug' => 'amazepays_rewards',
            'point_currency_label' => 'Points',
            'point_to_currency_rate' => 1,
            'currency' => 'INR',
            'status' => 'active',
            'points_expiry_days' => 365,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $platformTenantId,
            'display_name' => 'B2C Customer',
            'account_type' => 'customer',
            'status' => 'active',
            'is_super_admin' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $account = LoyaltyAccount::query()->create([
            'program_id' => $programId,
            'user_id' => $userId,
            'current_tier_id' => null,
            'points_balance' => 1000,
            'points_lifetime_earned' => 1000,
            'points_lifetime_redeemed' => 0,
            'points_pending' => 0,
            'tier_expires_at' => null,
        ]);

        $brandId = (int) DB::table('brands')->insertGetId([
            'tenant_id' => $platformTenantId,
            'name' => 'Brand',
            'slug' => 'brand',
            'source_provider' => null,
            'source_brand_id' => null,
            'logo_url' => null,
            'hero_image_url' => null,
            'status' => 'active',
            'is_featured' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $productId = (int) DB::table('products')->insertGetId([
            'tenant_id' => $platformTenantId,
            'brand_id' => $brandId,
            'sku' => 'SKU-LOY-1',
            'name' => 'Loyalty Product',
            'slug' => 'loyalty-product',
            'source_provider' => null,
            'source_product_id' => null,
            'currency' => 'INR',
            'status' => 'active',
            'type' => 'gift_card',
            'delivery_mode' => 'digital',
            'is_featured' => 0,
            'is_b2b_only' => 0,
            'is_b2c_only' => 0,
            'display_order' => 0,
            'hsn_sac_code_id' => null,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $platformTenantId,
            'user_id' => $userId,
            'order_number' => 'ORD-LOY-1',
            'channel' => 'storefront',
            'status' => 'created',
            'subtotal_minor' => 10000,
            'discount_total_minor' => 0,
            'tax_total_minor' => 0,
            'grand_total_minor' => 10000,
            'amount_paid_minor' => 0,
            'amount_refunded_minor' => 0,
            'currency' => 'INR',
            'placed_at' => now(),
        ]);

        $item = OrderItem::query()->create([
            'order_id' => (int) $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU-LOY-1',
            'name_snapshot' => 'Loyalty Product',
            'quantity' => 1,
            'unit_amount_minor' => 10000,
            'line_subtotal_minor' => 10000,
            'line_discount_minor' => 0,
            'line_tax_minor' => 0,
            'line_total_minor' => 10000,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        $svc = app(ApplyLoyaltyRedemption::class);
        $svc->apply($order->fresh(), $userId, 100);

        // program rate is 1 point = ₹1, so 100 points => ₹100 => 10,000 minor
        $this->assertSame(0, (int) $order->fresh()->grand_total_minor);
        $this->assertSame(10000, (int) $order->fresh()->discount_total_minor);
        $this->assertSame(10000, (int) $item->fresh()->line_discount_minor);

        $this->assertDatabaseHas('order_discounts', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'source_type' => 'loyalty_redemption',
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'account_id' => $account->id,
            'direction' => 'debit',
            'reason' => 'order_redeem',
            'points' => 100,
            'running_balance' => 900,
        ]);

        $this->assertDatabaseHas('loyalty_redemptions', [
            'account_id' => $account->id,
            'order_id' => $order->id,
            'points_redeemed' => 100,
            'discount_amount_minor' => 10000,
            'status' => 'applied',
        ]);
    }
}

