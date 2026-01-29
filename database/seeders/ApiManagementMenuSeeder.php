<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;

/**
 * @deprecated Use AdminMenuSeeder instead
 * This seeder is kept for backward compatibility but will be removed in future versions.
 * All menu management is now handled by AdminMenuSeeder.
 */
class ApiManagementMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Delegate to AdminMenuSeeder for comprehensive menu management
        $this->call(AdminMenuSeeder::class);
    }
}
