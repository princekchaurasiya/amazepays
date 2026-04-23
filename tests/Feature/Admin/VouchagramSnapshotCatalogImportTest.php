<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VouchagramCatalogSnapshot;
use App\Models\VouchagramCatalogSnapshotItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VouchagramSnapshotCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sync_from_snapshot_creates_products_without_live_provider(): void
    {
        $role = Role::query()->create(['name' => 'vg-catalog-sync-tester', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'providers.view', 'providers.sync']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $snapshot = VouchagramCatalogSnapshot::query()->create([
            'mode' => 'send',
            'brand_product_code_filter' => null,
            'item_count' => 1,
            'fetched_at' => now(),
            'created_by' => $user->id,
        ]);

        VouchagramCatalogSnapshotItem::query()->create([
            'snapshot_id' => $snapshot->id,
            'brand_product_code' => 'VG-SNAP-1',
            'api_mode' => 'send',
            'payload' => [
                'BrandProductCode' => 'VG-SNAP-1',
                'BrandName' => 'Snapshot Brand',
                'DenomType' => 'F',
                'denominationList' => '250',
                'BrandImage' => null,
            ],
        ]);

        $this->actingAs($user)
            ->postJson('/panel/vouchagram/sync-catalog-from-snapshot', [
                'snapshot_id' => $snapshot->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.created', 1)
            ->assertJsonPath('mode', 'send');

        $this->assertDatabaseHas('products', [
            'sku' => 'VG-SNAP-1',
            'source_provider' => 'vouchagram_send',
            'catalog_audience' => 'b2c',
        ]);

        $priceJson = DB::table('products')
            ->where('sku', 'VG-SNAP-1')
            ->value('price');
        $this->assertIsString($priceJson);
        $price = json_decode($priceJson, true);
        $this->assertIsArray($price);
        $this->assertSame('SLAB', $price['type'] ?? null);
        $this->assertSame([250.0], array_map('floatval', $price['denominations'] ?? []));
    }

    public function test_sync_from_snapshot_requires_providers_sync(): void
    {
        $role = Role::query()->create(['name' => 'vg-view-only', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'providers.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $snapshot = VouchagramCatalogSnapshot::query()->create([
            'mode' => 'send',
            'brand_product_code_filter' => null,
            'item_count' => 0,
            'fetched_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson('/panel/vouchagram/sync-catalog-from-snapshot', [
                'snapshot_id' => $snapshot->id,
            ])
            ->assertForbidden();
    }
}
