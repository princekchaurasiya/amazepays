<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductCatalogScopeTest extends TestCase
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

    private function minimalProductPayload(string $sku, string $catalogAudience): array
    {
        return [
            'name' => "Product {$sku}",
            'product_name' => "Product {$sku}",
            'sku' => $sku,
            'price' => json_encode(['type' => 'RANGE', 'min' => 100, 'max' => 500]),
            'selling_price' => 100,
            'gst_rate' => 0,
            'discount_percentage' => 0,
            'show_product' => true,
            'product_currency_code' => 'INR',
            'source_provider' => null,
            'catalog_audience' => $catalogAudience,
        ];
    }

    public function test_business_catalog_lists_only_b2b_and_both(): void
    {
        $b2c = Product::query()->create($this->minimalProductPayload('SCOPE-B2C', Product::CATALOG_AUDIENCE_B2C));
        $b2b = Product::query()->create($this->minimalProductPayload('SCOPE-B2B', Product::CATALOG_AUDIENCE_B2B));
        $both = Product::query()->create($this->minimalProductPayload('SCOPE-BOTH', Product::CATALOG_AUDIENCE_BOTH));

        $role = Role::query()->create(['name' => 'catalog-business-only', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.business',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/panel/products?catalog_scope=business', $this->inertiaHeaders());

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('Admin/Products/Index', $data['component'] ?? null);
        $this->assertArrayHasKey('canPublish', $data['props'] ?? []);
        $this->assertArrayHasKey('canUpdate', $data['props'] ?? []);
        $rows = $data['props']['products']['data'] ?? [];
        $ids = collect($rows)->pluck('id')->all();
        $this->assertContains($b2b->id, $ids);
        $this->assertContains($both->id, $ids);
        $this->assertNotContains($b2c->id, $ids);
    }

    public function test_storefront_catalog_lists_only_b2c_and_both(): void
    {
        $b2c = Product::query()->create($this->minimalProductPayload('SF-B2C', Product::CATALOG_AUDIENCE_B2C));
        $b2b = Product::query()->create($this->minimalProductPayload('SF-B2B', Product::CATALOG_AUDIENCE_B2B));
        $both = Product::query()->create($this->minimalProductPayload('SF-BOTH', Product::CATALOG_AUDIENCE_BOTH));

        $role = Role::query()->create(['name' => 'catalog-storefront-only', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.storefront',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/panel/products?catalog_scope=storefront', $this->inertiaHeaders());

        $response->assertOk();
        $rows = $response->json('props.products.data');
        $ids = collect($rows)->pluck('id')->all();
        $this->assertContains($b2c->id, $ids);
        $this->assertContains($both->id, $ids);
        $this->assertNotContains($b2b->id, $ids);
    }

    public function test_user_with_only_business_catalog_gets_403_on_storefront_scope(): void
    {
        $role = Role::query()->create(['name' => 'catalog-biz-no-store', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.business',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/panel/products?catalog_scope=storefront', $this->inertiaHeaders())
            ->assertForbidden();
    }

    public function test_settings_roles_index_requires_permission(): void
    {
        $role = Role::query()->create(['name' => 'no-rbac-ui', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'settings.view',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/panel/settings/roles', $this->inertiaHeaders())
            ->assertForbidden();
    }

    public function test_all_catalog_filters_by_catalog_audience_query(): void
    {
        $b2c = Product::query()->create($this->minimalProductPayload('AUD-B2C', Product::CATALOG_AUDIENCE_B2C));
        $b2b = Product::query()->create($this->minimalProductPayload('AUD-B2B', Product::CATALOG_AUDIENCE_B2B));

        $role = Role::query()->create(['name' => 'catalog-all-audience', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.all',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(
            '/panel/products?catalog_scope=all&catalog_audience=b2c',
            $this->inertiaHeaders(),
        );

        $response->assertOk();
        $rows = $response->json('props.products.data');
        $ids = collect($rows)->pluck('id')->all();
        $this->assertContains($b2c->id, $ids);
        $this->assertNotContains($b2b->id, $ids);
        $this->assertSame('b2c', $response->json('props.filters.catalog_audience'));
    }

    public function test_per_page_limits_page_size(): void
    {
        $role = Role::query()->create(['name' => 'catalog-all-per-page', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.all',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        for ($i = 0; $i < 15; $i++) {
            Product::query()->create($this->minimalProductPayload("PP-{$i}", Product::CATALOG_AUDIENCE_BOTH));
        }

        $response = $this->actingAs($user)->get(
            '/panel/products?catalog_scope=all&per_page=10',
            $this->inertiaHeaders(),
        );

        $response->assertOk();
        $this->assertCount(10, $response->json('props.products.data'));
        $this->assertSame(15, $response->json('props.products.total'));
        $this->assertSame(10, $response->json('props.filters.per_page'));
    }

    public function test_source_provider_options_reflects_scoped_products(): void
    {
        Product::query()->create(array_merge(
            $this->minimalProductPayload('OPT-1', Product::CATALOG_AUDIENCE_BOTH),
            ['source_provider' => 'vouchagram_pull'],
        ));

        $role = Role::query()->create(['name' => 'catalog-all-providers', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.catalog.all',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/panel/products?catalog_scope=all', $this->inertiaHeaders());
        $response->assertOk();
        $opts = $response->json('props.source_provider_options');
        $this->assertIsArray($opts);
        $this->assertContains('vouchagram_pull', $opts);
    }

    public function test_bulk_visibility_updates_in_scope_products(): void
    {
        $a = Product::query()->create($this->minimalProductPayload('BULK-V1', Product::CATALOG_AUDIENCE_B2C));
        $b = Product::query()->create($this->minimalProductPayload('BULK-V2', Product::CATALOG_AUDIENCE_B2C));
        $b->update(['show_product' => false]);

        $role = Role::query()->create(['name' => 'catalog-publish-bulk', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.publish',
            'products.catalog.storefront',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->patch('/panel/products/bulk-update', [
            'ids' => [$a->id, $b->id],
            'catalog_scope' => 'storefront',
            'apply_visibility' => true,
            'show_product' => true,
        ])->assertRedirect();

        $b->refresh();
        $this->assertTrue((bool) $b->show_product);
    }

    public function test_bulk_visibility_rejects_out_of_scope_ids(): void
    {
        $b2c = Product::query()->create($this->minimalProductPayload('BULK-OOS-B2C', Product::CATALOG_AUDIENCE_B2C));
        $b2b = Product::query()->create($this->minimalProductPayload('BULK-OOS-B2B', Product::CATALOG_AUDIENCE_B2B));

        $role = Role::query()->create(['name' => 'catalog-sf-publish-bulk', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.publish',
            'products.catalog.storefront',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->patch('/panel/products/bulk-update', [
            'ids' => [$b2c->id, $b2b->id],
            'catalog_scope' => 'storefront',
            'apply_visibility' => true,
            'show_product' => false,
        ])->assertStatus(422);
    }

    public function test_bulk_update_absolute_selling_price(): void
    {
        $a = Product::query()->create($this->minimalProductPayload('BULK-ABS-1', Product::CATALOG_AUDIENCE_B2C));
        $b = Product::query()->create($this->minimalProductPayload('BULK-ABS-2', Product::CATALOG_AUDIENCE_B2C));

        $role = Role::query()->create(['name' => 'catalog-update-bulk', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.update',
            'products.catalog.storefront',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->patch('/panel/products/bulk-update', [
            'ids' => [$a->id, $b->id],
            'catalog_scope' => 'storefront',
            'price_mode' => 'absolute',
            'selling_price' => 250,
        ])->assertRedirect();

        $a->refresh();
        $b->refresh();
        $this->assertSame(250.0, (float) $a->selling_price);
        $this->assertSame(250.0, (float) $b->selling_price);
    }

    public function test_bulk_update_relative_percent_selling_price(): void
    {
        $a = Product::query()->create($this->minimalProductPayload('BULK-REL-1', Product::CATALOG_AUDIENCE_B2C));

        $role = Role::query()->create(['name' => 'catalog-update-bulk-rel', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.update',
            'products.catalog.storefront',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->patch('/panel/products/bulk-update', [
            'ids' => [$a->id],
            'catalog_scope' => 'storefront',
            'price_mode' => 'relative_percent',
            'price_relative_percent' => 10,
        ])->assertRedirect();

        $a->refresh();
        $this->assertSame(110.0, (float) $a->selling_price);
    }

    public function test_bulk_update_price_forbidden_without_products_update(): void
    {
        $a = Product::query()->create($this->minimalProductPayload('BULK-403', Product::CATALOG_AUDIENCE_B2C));

        $role = Role::query()->create(['name' => 'catalog-publish-only-bulk', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.publish',
            'products.catalog.storefront',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->patch('/panel/products/bulk-update', [
            'ids' => [$a->id],
            'catalog_scope' => 'storefront',
            'price_mode' => 'absolute',
            'selling_price' => 99,
        ])->assertForbidden();
    }
}
