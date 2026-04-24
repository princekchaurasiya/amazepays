<?php

namespace Tests\Feature\Wallet;

use App\Exceptions\WalletFrozenException;
use App\Models\Product;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WalletFrozenFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function minimalProduct(string $sku): Product
    {
        return Product::query()->create([
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
            'catalog_audience' => Product::CATALOG_AUDIENCE_B2C,
        ]);
    }

    public function test_freeze_persists_to_database(): void
    {
        $role = Role::query()->create(['name' => 'wallet-freeze-tester', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'wallets.view', 'wallets.freeze']);

        $admin = User::factory()->create();
        $admin->assignRole($role);

        $owner = User::factory()->create();
        $wallet = $owner->wallet;

        $this->actingAs($admin)
            ->post(route('panel.wallets.freeze', $wallet))
            ->assertRedirect();

        $wallet->refresh();
        $this->assertTrue($wallet->is_frozen);
        $this->assertSame('Admin action', $wallet->frozen_reason);
    }

    public function test_debit_blocked_when_wallet_frozen(): void
    {
        $user = User::factory()->create();
        $user->wallet->update(['balance' => 1000, 'is_frozen' => true, 'frozen_reason' => 'Hold']);

        $this->expectException(WalletFrozenException::class);

        app(WalletService::class)->debit(
            $user,
            100.0,
            'test',
            'test-debit-'.uniqid(),
            null,
            null,
        );
    }

    public function test_credit_allowed_when_wallet_frozen(): void
    {
        $user = User::factory()->create();
        $user->wallet->update(['balance' => 0, 'is_frozen' => true, 'frozen_reason' => 'Hold']);

        app(WalletService::class)->credit(
            $user,
            250.0,
            'admin top-up',
            'test-credit-'.uniqid(),
            null,
            null,
        );

        $this->assertEquals(250.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_api_wallet_order_fails_when_wallet_frozen(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->wallet->update(['balance' => 5000, 'is_frozen' => true, 'frozen_reason' => 'Compliance']);

        $product = $this->minimalProduct('WALLET-FROZEN-SKU');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', [
            'product_id' => $product->id,
            'quantity' => 1,
            'denomination' => 100,
            'payment_method' => 'wallet',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'WALLET_FROZEN');
        $response->assertJsonPath('success', false);
    }
}
