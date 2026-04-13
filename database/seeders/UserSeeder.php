<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['role' => 'super-admin', 'name' => 'Super Admin', 'email' => 'superadmin@amazepays.in', 'mobile' => '9000000001'],
            ['role' => 'admin', 'name' => 'Admin User', 'email' => 'admin@amazepays.in', 'mobile' => '9000000002'],
            ['role' => 'finance', 'name' => 'Finance User', 'email' => 'finance@amazepays.in', 'mobile' => '9000000003'],
            ['role' => 'b2b-client', 'name' => 'B2B Client Owner', 'email' => 'b2bclient@amazepays.in', 'mobile' => '9000000004'],
            ['role' => 'b2b-operator', 'name' => 'B2B Operator', 'email' => 'b2boperator@amazepays.in', 'mobile' => '9000000005'],
            ['role' => 'b2c-user', 'name' => 'B2C Customer', 'email' => 'customer@amazepays.in', 'mobile' => '9000000006'],
            ['role' => 'reseller', 'name' => 'Reseller API', 'email' => 'reseller@amazepays.in', 'mobile' => '9000000007'],
        ];

        foreach ($users as $row) {
            $user = User::firstOrNew(['mobile' => $row['mobile']]);
            $user->name = $row['name'];
            $user->email = $row['email'];
            $user->password = Hash::make('password');
            $user->email_verified_at = now();
            $user->save();
            $user->syncRoles([$row['role']]);
            $this->command->info("✓ {$row['name']} ({$row['mobile']}, {$row['role']})");
        }

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'test-b2b'],
            [
                'name' => 'Test B2B Company',
                'type' => 'b2b',
                'status' => 'active',
            ]
        );

        $b2bClient = User::where('mobile', '9000000004')->first();
        $b2bOperator = User::where('mobile', '9000000005')->first();

        if ($b2bClient && $b2bOperator) {
            $tenant->users()->sync([
                $b2bClient->id => ['role' => 'owner', 'is_primary' => true],
                $b2bOperator->id => ['role' => 'operator', 'is_primary' => false],
            ]);
            foreach ([$b2bClient, $b2bOperator] as $u) {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $u->id],
                    ['balance' => 0]
                );
                $wallet->update(['balance' => 50000]);
            }
            $this->command->info('✓ B2B tenant test-b2b + wallets 50,000');
        }

        $prince = User::firstOrNew(['mobile' => '8097291952']);
        $prince->name = 'Prince Kumar';
        $prince->email = 'prince.kumar@example.com';
        $prince->password = Hash::make('password');
        $prince->email_verified_at = now();
        $prince->save();
        $prince->syncRoles(['b2c-user']);
        $this->command->info('✓ Prince Kumar (8097291952, b2c-user)');
    }
}
