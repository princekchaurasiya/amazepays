<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderDateFilterTest extends TestCase
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

    private function userWithOrdersAccess(): User
    {
        $role = Role::query()->create(['name' => 'order-date-filter-tester', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'orders.view', 'orders.view_all']);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_orders_index_filters_by_created_at_dates(): void
    {
        $user = $this->userWithOrdersAccess();

        $old = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now()->subDays(10),
        ]);
        $recent = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $day = now()->toDateString();

        $response = $this->actingAs($user)->get(
            "/panel/orders?date_from={$day}&date_to={$day}",
            $this->inertiaHeaders(),
        );

        $response->assertOk();
        $data = $response->json();
        $rows = $data['props']['orders']['data'] ?? [];
        $ids = collect($rows)->pluck('id')->all();
        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_dashboard_accepts_chart_days_query(): void
    {
        $user = $this->userWithOrdersAccess();

        $response = $this->actingAs($user)->get('/panel?chart_days=7', $this->inertiaHeaders());

        $response->assertOk();
        $data = $response->json();
        $this->assertSame(7, $data['props']['chartDays'] ?? null);
    }
}
