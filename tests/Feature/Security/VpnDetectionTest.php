<?php

namespace Tests\Feature\Security;

use App\Services\VpnDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class VpnDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_vpn_is_blocked_for_order_routes(): void
    {
        Config::set('security.vpn_detection.enabled', true);
        Config::set('security.vpn_detection.action', 'block');

        $this->mockVpnDetector(['is_vpn' => true, 'is_proxy' => false, 'is_tor' => false]);

        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(403)
            ->assertJson(['error' => 'ACCESS_DENIED']);
    }

    public function test_vpn_is_allowed_for_catalog_routes(): void
    {
        Config::set('security.vpn_detection.enabled', true);
        Config::set('security.vpn_detection.route_groups', ['api.catalog' => 'log_only']);

        $this->mockVpnDetector(['is_vpn' => true]);

        $response = $this->getJson('/api/v1/catalog');

        // Catalog should still return 200 (log_only for VPN)
        $response->assertStatus(200);
    }

    public function test_legitimate_ip_passes_through(): void
    {
        $this->mockVpnDetector(['is_vpn' => false, 'is_proxy' => false, 'is_tor' => false]);

        $response = $this->getJson('/api/v1/catalog');

        $response->assertStatus(200);
    }

    public function test_private_ip_bypasses_vpn_check(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)->assertJson(['status' => 'ok']);
    }

    public function test_vpn_detection_disabled_allows_all(): void
    {
        Config::set('security.vpn_detection.enabled', false);

        $this->mockVpnDetector(['is_vpn' => true]);

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
    }

    private function mockVpnDetector(array $result): void
    {
        $mock = Mockery::mock(VpnDetectionService::class);
        $mock->shouldReceive('check')->andReturn(array_merge(
            ['is_vpn' => false, 'is_proxy' => false, 'is_tor' => false],
            $result
        ));

        $this->app->instance(VpnDetectionService::class, $mock);
    }
}
