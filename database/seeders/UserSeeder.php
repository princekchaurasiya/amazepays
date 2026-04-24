<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Legacy seeder replaced by BaselineTenantAndUsersSeeder for the new normalized schema.
        // Kept to avoid breaking older deployments that still reference this class.
        $this->command?->warn('UserSeeder is legacy. Use BaselineTenantAndUsersSeeder instead.');
    }
}
