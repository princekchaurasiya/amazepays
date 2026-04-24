<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_see_other_tenants_orders(): void
    {
        [$tenant1, $user1] = $this->createTenantWithUser();
        [$tenant2, $user2] = $this->createTenantWithUser();

        // Create an order for tenant1
        $order = Order::factory()->create([
            'user_id' => $user1->id,
            'tenant_id' => $tenant1->id,
        ]);

        // User from tenant2 should not see tenant1's order
        $this->actingAs($user2)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_user_can_see_their_own_tenants_orders(): void
    {
        [$tenant, $user] = $this->createTenantWithUser();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('order.id', $order->id);
    }

    public function test_admin_can_see_all_tenants_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$tenant1, $user1] = $this->createTenantWithUser();

        $order = Order::factory()->create([
            'user_id' => $user1->id,
            'tenant_id' => $tenant1->id,
        ]);

        $this->actingAs($admin)
            ->getJson("/panel/orders/{$order->id}")
            ->assertStatus(200);
    }

    private function createTenantWithUser(): array
    {
        $tenant = Tenant::factory()->create(['status' => 'active', 'type' => 'b2b_partner']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'account_type' => 'partner']);
        $user->assignRole('b2b-client');

        return [$tenant, $user];
    }
}
