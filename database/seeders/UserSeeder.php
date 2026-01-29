<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use TCG\Voyager\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        // Ensure user role exists
        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            ['display_name' => 'Normal User']
        );

        // Create or update Prince Kumar user (normal user)
        $prince = User::firstOrNew(['mobile' => '8097291952']);
        $prince->name = 'Prince Kumar';
        $prince->email = 'prince.kumar@example.com';
        $prince->password = Hash::make('password');
        $prince->email_verified_at = now();
        $prince->role_id = $userRole->id; // Assign user role
        $prince->save();

        $this->command->info('✓ Prince Kumar user created/updated (Mobile: 8097291952, Password: password, Role: User)');

        // Create or update Admin user (admin role)
        $admin = User::firstOrNew(['email' => 'admin@admin.com']);
        $admin->name = 'Admin';
        $admin->mobile = '9999999999';
        $admin->password = Hash::make('password');
        $admin->email_verified_at = now();
        $admin->role_id = $adminRole->id; // Assign admin role - this will redirect to /admin
        $admin->save();

        $this->command->info('✓ Admin user created/updated (Email: admin@admin.com, Password: password, Role: Admin)');
        $this->command->info('  → Admin will be redirected to /admin (Voyager admin panel)');
    }
}
