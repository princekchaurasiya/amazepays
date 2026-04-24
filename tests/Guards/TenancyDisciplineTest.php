<?php

namespace Tests\Guards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenancyDisciplineTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_tables_have_tenant_id_column(): void
    {
        $mustHaveTenantId = [
            'brands',
            'categories',
            'products',
            'carts',
            'orders',
            'payments',
            'wallets',
            'support_tickets',
            'audit_logs',
            'security_event_logs',
            'provider_connections',
            'promotion_campaigns',
            'offers',
            'bank_offers',
            'api_keys',
            'seo_metas',
            'url_redirects',
            'sitemap_entries',
        ];

        $driver = DB::connection()->getDriverName();

        foreach ($mustHaveTenantId as $table) {
            $columns = $driver === 'sqlite'
                ? collect(DB::select("PRAGMA table_info('$table')"))->pluck('name')->all()
                : collect(DB::select("SHOW COLUMNS FROM `$table`"))->pluck('Field')->all();

            $this->assertContains('tenant_id', $columns, "Table {$table} must have tenant_id");
        }
    }
}

