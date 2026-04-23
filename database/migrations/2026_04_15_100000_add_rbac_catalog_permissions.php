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

        $new = [
            'products.catalog.storefront',
            'products.catalog.business',
            'products.catalog.all',
            'settings.roles.manage',
            'users.assign_roles',
        ];

        foreach ($new as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $catalogPerms = [
            'products.catalog.storefront',
            'products.catalog.business',
            'products.catalog.all',
        ];

        foreach (Role::query()->cursor() as $role) {
            if ($role->hasPermissionTo('products.view')) {
                $role->givePermissionTo($catalogPerms);
            }
        }

        $super = Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($super) {
            $super->givePermissionTo(['settings.roles.manage', 'users.assign_roles']);
        }

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo(['settings.roles.manage', 'users.assign_roles']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $names = [
            'products.catalog.storefront',
            'products.catalog.business',
            'products.catalog.all',
            'settings.roles.manage',
            'users.assign_roles',
        ];

        foreach ($names as $name) {
            Permission::query()->where('name', $name)->where('guard_name', 'web')->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
