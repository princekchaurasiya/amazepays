<?php

namespace Tests\Guards;

use App\Domains\Content\Models\ContentSection;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomepageApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_endpoint_returns_contract_shape(): void
    {
        $tenant = Tenant::create([
            'slug' => 'acme',
            'name' => 'Acme',
            'display_name' => 'Acme',
            'type' => 'b2c_brand',
            'status' => 'active',
            'default_locale' => 'en',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'settings' => [],
        ]);

        ContentSection::create([
            'tenant_id' => $tenant->id,
            'surface' => 'storefront_home',
            'slug' => 'hero-banners',
            'type' => 'carousel',
            'status' => 'active',
            'is_enabled' => true,
            'sort_order' => 1,
            'platform' => 'both',
            'priority' => 0,
            'title' => 'Hero',
        ]);

        $res = $this->getJson('/api/v1/homepage?platform=mobile', [
            'X-Tenant-Slug' => 'acme',
        ]);

        $res->assertOk();
        $res->assertHeader('X-Homepage-Version');

        $res->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'hero_banners',
                'sections',
                'popup_offer',
                'categories',
                'versioning',
            ],
        ]);
    }
}

