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

        $perm = Permission::query()->where('name', 'wallets.freeze')->where('guard_name', 'web')->first();
        if (! $perm) {
            return;
        }

        foreach (['admin', 'finance'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::query()->where('name', 'wallets.freeze')->where('guard_name', 'web')->first();
        if (! $perm) {
            return;
        }

        foreach (['admin', 'finance'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $role->hasPermissionTo($perm)) {
                $role->revokePermissionTo($perm);
            }
        }
    }
};
