<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAuthIdentity;
use App\Models\UserAuthSecret;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BaselineTenantAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        $platformTenant = Tenant::firstOrCreate(
            ['slug' => 'platform'],
            [
                'name' => 'AmazePays Platform',
                'display_name' => 'AmazePays',
                'type' => 'platform',
                'status' => 'active',
                'default_locale' => 'en',
                'default_currency' => 'INR',
                'default_timezone' => 'Asia/Kolkata',
            ]
        );

        $b2bTenant = Tenant::firstOrCreate(
            ['slug' => 'test-b2b'],
            [
                'name' => 'Test B2B Company',
                'display_name' => 'Test B2B',
                'type' => 'b2b_partner',
                'status' => 'active',
                'default_locale' => 'en',
                'default_currency' => 'INR',
                'default_timezone' => 'Asia/Kolkata',
            ]
        );

        $users = [
            [
                'role' => 'super-admin',
                'tenant_id' => null,
                'display_name' => 'Super Admin',
                'email' => 'superadmin@amazepays.in',
                'mobile' => '9000000001',
                'account_type' => 'system',
                'is_super_admin' => true,
            ],
            [
                'role' => 'admin',
                'tenant_id' => $platformTenant->id,
                'display_name' => 'Admin User',
                'email' => 'admin@amazepays.in',
                'mobile' => '9000000002',
                'account_type' => 'admin',
                'is_super_admin' => false,
            ],
            [
                'role' => 'finance',
                'tenant_id' => $platformTenant->id,
                'display_name' => 'Finance User',
                'email' => 'finance@amazepays.in',
                'mobile' => '9000000003',
                'account_type' => 'admin',
                'is_super_admin' => false,
            ],
            [
                'role' => 'b2b-client',
                'tenant_id' => $b2bTenant->id,
                'display_name' => 'B2B Client Owner',
                'email' => 'b2bclient@amazepays.in',
                'mobile' => '9000000004',
                'account_type' => 'partner',
                'is_super_admin' => false,
            ],
            [
                'role' => 'b2b-operator',
                'tenant_id' => $b2bTenant->id,
                'display_name' => 'B2B Operator',
                'email' => 'b2boperator@amazepays.in',
                'mobile' => '9000000005',
                'account_type' => 'partner',
                'is_super_admin' => false,
            ],
            [
                'role' => 'b2c-user',
                'tenant_id' => $platformTenant->id,
                'display_name' => 'B2C Customer',
                'email' => 'customer@amazepays.in',
                'mobile' => '9000000006',
                'account_type' => 'customer',
                'is_super_admin' => false,
            ],
            [
                'role' => 'reseller',
                'tenant_id' => $platformTenant->id,
                'display_name' => 'Reseller API',
                'email' => 'reseller@amazepays.in',
                'mobile' => '9000000007',
                'account_type' => 'partner',
                'is_super_admin' => false,
            ],
        ];

        foreach ($users as $row) {
            $user = User::firstOrCreate(
                ['tenant_id' => $row['tenant_id'], 'display_name' => $row['display_name']],
                [
                    'account_type' => $row['account_type'],
                    'status' => 'active',
                    'is_super_admin' => $row['is_super_admin'],
                ]
            );

            // Identities (email + mobile)
            $emailIdentity = UserAuthIdentity::firstOrCreate(
                ['provider' => 'email', 'identifier' => $row['email']],
                [
                    'user_id' => $user->id,
                    'display_identifier' => $row['email'],
                    'is_primary' => true,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );

            UserAuthIdentity::firstOrCreate(
                ['provider' => 'mobile', 'identifier' => $row['mobile']],
                [
                    'user_id' => $user->id,
                    'display_identifier' => $row['mobile'],
                    'is_primary' => false,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );

            // Password secret (one per identity_id)
            UserAuthSecret::firstOrCreate(
                ['identity_id' => $emailIdentity->id],
                [
                    'user_id' => $user->id,
                    'password_hash' => Hash::make('password'),
                    'failed_attempts' => 0,
                ]
            );

            $user->syncRoles([$row['role']]);

            // Wallet for tenant-scoped users (super-admin can remain tenantless)
            if ($row['tenant_id']) {
                $wallet = Wallet::firstOrCreate(
                    ['tenant_id' => $row['tenant_id'], 'user_id' => $user->id],
                    [
                        'status' => 'active',
                        'currency' => 'INR',
                        'available_balance_minor' => 0,
                        'held_balance_minor' => 0,
                    ]
                );

                // Give B2B users some test balance
                if (Str::startsWith($row['role'], 'b2b-')) {
                    $wallet->update([
                        'available_balance_minor' => 5_000_000,
                    ]);
                }
            }

            $this->command?->info("✓ {$row['display_name']} ({$row['mobile']}, {$row['role']})");
        }
    }
}

