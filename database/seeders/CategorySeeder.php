<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SyncedCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSyncedCategorySandbox();

        $this->command->info('Seeding storefront navigation categories (≥20, with accent colors for tiles)...');

        $tenantId = (int) (Tenant::query()->min('id') ?? 1);

        /** @var list<array{name: string, display_order: int}> $defaults */
        $defaults = [
            ['name' => 'New Brands', 'display_order' => 1],
            ['name' => 'Food', 'display_order' => 2],
            ['name' => 'Grocery', 'display_order' => 3],
            ['name' => 'One Stop Shop', 'display_order' => 4],
            ['name' => 'Hot Deals', 'display_order' => 5],
            ['name' => 'Travel', 'display_order' => 6],
            ['name' => 'Hotels', 'display_order' => 7],
            ['name' => 'Fashion', 'display_order' => 8],
            ['name' => 'Beauty', 'display_order' => 9],
            ['name' => 'Gaming', 'display_order' => 10],
            ['name' => 'Watches', 'display_order' => 11],
            ['name' => 'Electronics', 'display_order' => 12],
            ['name' => 'Entertainment', 'display_order' => 13],
            ['name' => 'Health & Wellness', 'display_order' => 14],
            ['name' => 'Jewellery', 'display_order' => 15],
            ['name' => 'Kids', 'display_order' => 16],
            ['name' => 'Home & Living', 'display_order' => 17],
            ['name' => 'Fitness', 'display_order' => 18],
            ['name' => 'E-commerce', 'display_order' => 19],
            ['name' => 'Dining', 'display_order' => 20],
            ['name' => 'Books & Learning', 'display_order' => 21],
            ['name' => 'Auto & Fuel', 'display_order' => 22],
        ];

        $hasAccent = Schema::hasColumn('categories', 'accent_color');
        $palette = [
            '#0ea5e9',
            '#22c55e',
            '#f97316',
            '#ef4444',
            '#a855f7',
            '#14b8a6',
            '#3b82f6',
            '#ec4899',
            '#84cc16',
            '#f59e0b',
        ];

        foreach ($defaults as $row) {
            $data = [
                'name' => $row['name'],
                'display_order' => $row['display_order'],
                'status' => 'active',
                'is_featured' => false,
            ];

            if ($hasAccent) {
                $hash = crc32(mb_strtolower($row['name']));
                $data['accent_color'] = $palette[$hash % count($palette)];
            }

            Category::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug' => Str::slug($row['name']),
                ],
                $data
            );
        }

        $this->command->info('Storefront categories seeded: '.count($defaults).' rows (updateOrCreate by name — safe to re-run).');
    }

    /**
     * Legacy Woo/Vouchagram catalog taxonomy row used in sandbox flows.
     */
    private function seedSyncedCategorySandbox(): void
    {
        $this->command->info('Seeding synced category sandbox row...');

        $tenantId = (int) (\App\Models\Tenant::query()->min('id') ?? 1);

        $category = SyncedCategory::firstOrNew([
            'provider' => 'sandbox',
            'external_id' => 'api-sandbox-b2b',
        ]);

        $category->tenant_id = $tenantId;
        $category->external_parent_id = null;
        $category->local_category_id = null;
        $category->name = 'API SANDBOX B2B';
        $category->raw_payload = ['name' => 'API SANDBOX B2B'];
        $category->synced_at = now();
        $category->save();

        $this->command->info('Synced category seeded: sandbox/api-sandbox-b2b');
    }
}
