<?php

namespace Tests\Feature\B2b;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class B2bPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        $version = (new HandleInertiaRequests)->version(Request::create('/'));

        return array_filter([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ]);
    }

    public function test_guest_cannot_access_b2b_shop(): void
    {
        $this->get('/panel/b2b/shop')->assertRedirect();
    }

    public function test_b2b_shop_loads_for_operator_with_tenant(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $this->actingAs($user)
            ->get('/panel/b2b/shop')
            ->assertOk();
    }

    public function test_b2b_shop_reports_no_assignments_when_tenant_has_no_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $response = $this->actingAs($user)->get('/panel/b2b/shop', $this->inertiaHeaders());

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('Admin/B2B/Shop', $data['component'] ?? null);
        $this->assertSame([], $data['props']['products'] ?? null);
        $this->assertSame(0, $data['props']['catalogMeta']['assignedActiveCount'] ?? null);
        $this->assertSame(0, $data['props']['catalogMeta']['b2bEligibleCount'] ?? null);
    }

    public function test_b2b_company_catalog_page_loads_when_user_has_assign_products_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');
        $user->givePermissionTo('tenants.assign_products');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'is_primary' => true]);

        $response = $this->actingAs($user)->get('/panel/b2b/catalog', $this->inertiaHeaders());

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('Admin/B2B/CatalogManage', $data['component'] ?? null);
        $this->assertTrue($data['props']['canManage'] ?? false);
    }

    public function test_b2b_company_catalog_forbidden_without_assign_products_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'operator', 'is_primary' => true]);

        $this->actingAs($user)->get('/panel/b2b/catalog')->assertForbidden();
    }

    public function test_b2b_shop_lists_nothing_when_assigned_products_are_b2c_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $product = Product::query()->create([
            'name' => 'Storefront only',
            'product_name' => 'Storefront only',
            'sku' => 'B2C-ONLY-1',
            'price' => json_encode(['type' => 'RANGE', 'min' => 100, 'max' => 500]),
            'gst_rate' => 0,
            'discount_percentage' => 0,
            'show_product' => true,
            'product_currency_code' => 'INR',
            'source_provider' => null,
            'catalog_audience' => Product::CATALOG_AUDIENCE_B2C,
        ]);
        $tenant->products()->attach($product->id, ['is_active' => true]);

        $response = $this->actingAs($user)->get('/panel/b2b/shop', $this->inertiaHeaders());

        $response->assertOk();
        $data = $response->json();
        $this->assertSame([], $data['props']['products'] ?? null);
        $this->assertSame(1, $data['props']['catalogMeta']['assignedActiveCount'] ?? null);
        $this->assertSame(0, $data['props']['catalogMeta']['b2bEligibleCount'] ?? null);
    }

    public function test_b2b_order_uses_tenant_margin_when_assigned(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'margin_percentage' => 12.5,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $product = Product::query()->create([
            'name' => 'B2B Test SKU',
            'product_name' => 'B2B Test Product',
            'sku' => 'B2B-TEST-1',
            'price' => json_encode(['type' => 'RANGE', 'min' => 100, 'max' => 500]),
            'gst_rate' => 0,
            'discount_percentage' => 3,
            'show_product' => true,
            'product_currency_code' => 'INR',
            'source_provider' => null,
        ]);

        $tenant->products()->attach($product->id, ['is_active' => true]);

        app(WalletService::class)->credit($user, 50_000, 'test credit');

        $this->actingAs($user)->post('/panel/b2b/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
            'denomination' => 100,
            'payment_method' => 'wallet',
        ])->assertRedirect();

        $order = Order::query()->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame($tenant->id, $order->tenant_id);
        $this->assertSame($product->id, $order->product_id);
        $this->assertEquals(12.5, (float) $order->discount_percentage);
    }

    public function test_b2b_order_rejects_unassigned_product(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $product = Product::query()->create([
            'name' => 'Other',
            'product_name' => 'Other',
            'sku' => 'OTHER',
            'price' => json_encode(['type' => 'RANGE', 'min' => 50, 'max' => 200]),
            'gst_rate' => 0,
            'show_product' => true,
            'product_currency_code' => 'INR',
            'source_provider' => null,
        ]);

        app(WalletService::class)->credit($user, 10_000, 'test credit');

        $this->actingAs($user)->post('/panel/b2b/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
            'denomination' => 50,
            'payment_method' => 'wallet',
        ])->assertSessionHasErrors('order');
    }

    public function test_b2b_order_rejects_b2c_audience_when_product_is_assigned(): void
    {
        $user = User::factory()->create();
        $user->assignRole('b2b-operator');

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_primary' => true]);

        $product = Product::query()->create([
            'name' => 'B2C only',
            'product_name' => 'B2C only',
            'sku' => 'B2C-ORD-1',
            'price' => json_encode(['type' => 'RANGE', 'min' => 50, 'max' => 200]),
            'gst_rate' => 0,
            'discount_percentage' => 0,
            'show_product' => true,
            'product_currency_code' => 'INR',
            'source_provider' => null,
            'catalog_audience' => Product::CATALOG_AUDIENCE_B2C,
        ]);
        $tenant->products()->attach($product->id, ['is_active' => true]);

        app(WalletService::class)->credit($user, 10_000, 'test credit');

        $this->actingAs($user)->post('/panel/b2b/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
            'denomination' => 50,
            'payment_method' => 'wallet',
        ])->assertSessionHasErrors('order');
    }
}
