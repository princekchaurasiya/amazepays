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
            RolesAndPermissionsSeeder::class,
            BaselineTenantAndUsersSeeder::class,
            CategorySeeder::class,
            BankIssuersAndInstrumentsSeeder::class,
            KycThresholdsSeeder::class,
            LoyaltyProgramSeeder::class,
            TaxReferenceSeeder::class,
        ]);
    }
}
