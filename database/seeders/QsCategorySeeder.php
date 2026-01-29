<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QsCategory;

class QsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding QS Categories...');

        // Create or update the API SANDBOX B2B category
        $category = QsCategory::firstOrNew(['id' => 121]);
        $category->name = 'API SANDBOX B2B';
        $category->url = '/api-sandbox-b2b';
        $category->description = null;
        $category->images = json_encode(['image' => null, 'thumbnail' => null]);
        $category->subcategoriesCount = 0;
        $category->subcategories = '[]';
        $category->save();

        $this->command->info('✅ QS Category seeded successfully!');
        $this->command->info('   - ID: 121, Name: API SANDBOX B2B');
    }
}
