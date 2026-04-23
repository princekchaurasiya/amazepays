<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SyncedCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSyncedCategorySandbox();

        $this->command->info('Seeding storefront navigation categories (≥20, with accent colors for tiles)...');

        /** @var list<array{name: string, order: int, accent_color: string}> $defaults */
        $defaults = [
            ['name' => 'New Brands', 'order' => 1, 'accent_color' => '#f97316'],
            ['name' => 'Food', 'order' => 2, 'accent_color' => '#ef4444'],
            ['name' => 'Grocery', 'order' => 3, 'accent_color' => '#84cc16'],
            ['name' => 'One Stop Shop', 'order' => 4, 'accent_color' => '#8b5cf6'],
            ['name' => 'Hot Deals', 'order' => 5, 'accent_color' => '#dc2626'],
            ['name' => 'Travel', 'order' => 6, 'accent_color' => '#0ea5e9'],
            ['name' => 'Hotels', 'order' => 7, 'accent_color' => '#6366f1'],
            ['name' => 'Fashion', 'order' => 8, 'accent_color' => '#ec4899'],
            ['name' => 'Beauty', 'order' => 9, 'accent_color' => '#d946ef'],
            ['name' => 'Gaming', 'order' => 10, 'accent_color' => '#22c55e'],
            ['name' => 'Watches', 'order' => 11, 'accent_color' => '#78716b'],
            ['name' => 'Electronics', 'order' => 12, 'accent_color' => '#3b82f6'],
            ['name' => 'Entertainment', 'order' => 13, 'accent_color' => '#a855f7'],
            ['name' => 'Health & Wellness', 'order' => 14, 'accent_color' => '#14b8a6'],
            ['name' => 'Jewellery', 'order' => 15, 'accent_color' => '#ca8a04'],
            ['name' => 'Kids', 'order' => 16, 'accent_color' => '#fb923c'],
            ['name' => 'Home & Living', 'order' => 17, 'accent_color' => '#64748b'],
            ['name' => 'Fitness', 'order' => 18, 'accent_color' => '#10b981'],
            ['name' => 'E-commerce', 'order' => 19, 'accent_color' => '#06b6d4'],
            ['name' => 'Dining', 'order' => 20, 'accent_color' => '#f43f5e'],
            ['name' => 'Books & Learning', 'order' => 21, 'accent_color' => '#0f7669'],
            ['name' => 'Auto & Fuel', 'order' => 22, 'accent_color' => '#475569'],
        ];

        foreach ($defaults as $row) {
            Category::updateOrCreate(
                ['name' => $row['name']],
                [
                    'order' => $row['order'],
                    'accent_color' => $row['accent_color'],
                ]
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

        $category = SyncedCategory::firstOrNew(['id' => 121]);
        $category->name = 'API SANDBOX B2B';
        $category->url = '/api-sandbox-b2b';
        $category->description = null;
        $category->images = json_encode(['image' => null, 'thumbnail' => null]);
        $category->subcategoriesCount = 0;
        $category->subcategories = '[]';
        $category->save();

        $this->command->info('Synced category seeded: ID 121, API SANDBOX B2B');
    }
}
