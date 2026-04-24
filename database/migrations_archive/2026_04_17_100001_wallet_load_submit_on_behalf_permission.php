<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $submit = Permission::firstOrCreate(
            ['name' => 'wallets.load_requests.submit_on_behalf', 'guard_name' => 'web']
        );

        foreach (['super-admin', 'admin', 'finance'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($submit);
            }
        }

        $credit = Permission::query()->where('name', 'wallets.credit')->where('guard_name', 'web')->first();
        if ($credit) {
            foreach (['admin', 'finance'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->revokePermissionTo($credit);
                }
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $submit = Permission::query()->where('name', 'wallets.load_requests.submit_on_behalf')->where('guard_name', 'web')->first();
        if ($submit) {
            foreach (['super-admin', 'admin', 'finance'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->revokePermissionTo($submit);
                }
            }
            $submit->delete();
        }

        $credit = Permission::query()->where('name', 'wallets.credit')->where('guard_name', 'web')->first();
        if ($credit) {
            foreach (['admin', 'finance'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->givePermissionTo($credit);
                }
            }
        }
    }
};
