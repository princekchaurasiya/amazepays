<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // Voyager Core Seeders
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            DataTypesTableSeeder::class,
            DataRowsTableSeeder::class,
            MenusTableSeeder::class,
            MenuItemsTableSeeder::class,
            PermissionRoleTableSeeder::class,
            SettingsTableSeeder::class,
            
            // API Settings (from backup)
            ApiSettingsSeeder::class,
            
            // Application BREAD and Menus
            ApplicationBreadSeeder::class,
            AdminMenuSeeder::class,
            
            // Users
            UserSeeder::class,
            
            // QS Categories (for API integration)
            QsCategorySeeder::class,
        ]);
    }
}
