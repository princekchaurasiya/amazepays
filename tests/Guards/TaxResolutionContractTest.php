<?php

namespace Tests\Guards;

use App\Models\Order;
use App\Models\OrderBillingSnapshot;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Services\Checkout\ResolveTax;
use Database\Seeders\TaxReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TaxResolutionContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_tax_persists_breakdowns_and_updates_totals(): void
    {
        (new TaxReferenceSeeder())->run();

        $tenant = Tenant::query()->create([
            'slug' => 't-tax-guards',
            'name' => 'Tax Guards Tenant',
            'type' => 'b2c_brand',
            'status' => 'active',
            'default_locale' => 'en',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
        ]);

        $brandId = (int) DB::table('brands')->insertGetId([
            'tenant_id' => (int) $tenant->id,
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
            'tenant_id' => (int) $tenant->id,
            'brand_id' => $brandId,
            'sku' => 'SKU-TAX-1',
            'name' => 'Tax Product',
            'slug' => 'tax-product',
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

        $jurisdictionId = (int) DB::table('tax_jurisdictions')->where('code', 'IN')->value('id');
        $hsnId = (int) DB::table('hsn_sac_codes')->where('code', '999799')->value('id');

        DB::table('product_tax_assignments')->insert([
            'product_id' => $productId,
            'hsn_sac_code_id' => $hsnId,
            'jurisdiction_id' => null,
            'override_rate_percent' => null,
            'is_exempt' => 0,
            'effective_from' => now()->toDateString(),
            'effective_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::query()->create([
            'tenant_id' => (int) $tenant->id,
            'user_id' => null,
            'order_number' => 'ORD-TAX-1',
            'channel' => 'api',
            'status' => 'created',
            'subtotal_minor' => 10000, // ₹100.00
            'discount_total_minor' => 1000, // ₹10.00
            'tax_total_minor' => 0,
            'grand_total_minor' => 9000,
            'amount_paid_minor' => 0,
            'amount_refunded_minor' => 0,
            'currency' => 'INR',
            'placed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => (int) $order->id,
            'product_id' => $productId,
            'denomination_id' => null,
            'sku_snapshot' => 'SKU-TAX-1',
            'name_snapshot' => 'Tax Product',
            'quantity' => 1,
            'unit_amount_minor' => 10000,
            'line_subtotal_minor' => 10000,
            'line_discount_minor' => 1000,
            'line_tax_minor' => 0,
            'line_total_minor' => 9000,
            'currency' => 'INR',
            'fulfilment_status' => 'pending',
        ]);

        OrderBillingSnapshot::query()->create([
            'order_id' => (int) $order->id,
            'user_address_id' => null,
            'full_name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '9000000000',
            'line1' => 'Line 1',
            'line2' => '',
            'city' => 'City',
            'state' => 'KA',
            'postal_code' => '560001',
            'country' => 'IN',
            'gst_number' => null,
        ]);

        $resolver = app(ResolveTax::class);
        $resolver->resolveAndPersist($order->fresh());

        // TaxReferenceSeeder creates 18% IGST, effective from "today".
        $expectedTaxable = 9000;
        $expectedTaxMinor = (int) round($expectedTaxable * 0.18, 0, PHP_ROUND_HALF_UP); // 1620

        $this->assertDatabaseHas('order_tax_breakdowns', [
            'tax_component' => 'igst',
            'taxable_amount_minor' => $expectedTaxable,
            'tax_amount_minor' => $expectedTaxMinor,
            'currency' => 'INR',
        ]);

        $this->assertSame($expectedTaxMinor, (int) $order->fresh()->tax_total_minor);
        $this->assertSame($expectedTaxable + $expectedTaxMinor, (int) $order->fresh()->grand_total_minor);

        // Ensure the jurisdiction exists (used for lookup) — sanity check.
        $this->assertGreaterThan(0, $jurisdictionId);
    }
}

