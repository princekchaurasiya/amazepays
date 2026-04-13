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

        $this->command->info('Seeding storefront navigation categories...');

        $defaults = [
            ['order' => 1, 'name' => 'New Brands'],
            ['order' => 2, 'name' => 'Food'],
            ['order' => 3, 'name' => 'Grocery'],
            ['order' => 4, 'name' => 'One Stop Shop'],
            ['order' => 5, 'name' => 'Hot Deals'],
            ['order' => 6, 'name' => 'Travel'],
            ['order' => 7, 'name' => 'Hotels'],
            ['order' => 8, 'name' => 'Fashion'],
            ['order' => 9, 'name' => 'Beauty'],
            ['order' => 10, 'name' => 'Gaming'],
            ['order' => 11, 'name' => 'Watches'],
            ['order' => 12, 'name' => 'Electronics'],
            ['order' => 13, 'name' => 'Entertainment'],
            ['order' => 14, 'name' => 'Health & Wellness'],
            ['order' => 15, 'name' => 'Jewellery'],
            ['order' => 16, 'name' => 'Kids'],
            ['order' => 17, 'name' => 'Home & Living'],
            ['order' => 18, 'name' => 'Fitness'],
            ['order' => 19, 'name' => 'E-commerce'],
            ['order' => 20, 'name' => 'Dining'],
        ];

        foreach ($defaults as $row) {
            Category::firstOrCreate(
                ['name' => $row['name']],
                ['order' => $row['order']]
            );
        }

        $this->command->info('Storefront categories seeded: '.count($defaults).' rows (firstOrCreate by name).');
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
