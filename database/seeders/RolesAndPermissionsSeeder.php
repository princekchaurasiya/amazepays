<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Products
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.publish', 'products.sync',
            'products.catalog.storefront', 'products.catalog.business', 'products.catalog.all',

            // Categories (storefront)
            'categories.manage',

            // Homepage slides / hero banners
            'slides.view', 'slides.create', 'slides.update', 'slides.delete',

            // Orders
            'orders.view', 'orders.view_all', 'orders.cancel', 'orders.refund',
            'orders.approve', 'orders.export',

            // Users
            'users.view', 'users.create', 'users.update', 'users.block',
            'users.delete', 'users.impersonate', 'users.assign_roles',

            // Tenants / B2B
            'tenants.view', 'tenants.create', 'tenants.update', 'tenants.suspend',
            'tenants.delete', 'tenants.assign_products', 'tenants.set_pricing',

            // Wallets
            'wallets.view', 'wallets.debit', 'wallets.freeze',
            'wallets.load_requests.view', 'wallets.load_requests.submit_on_behalf',
            'wallets.load_requests.approve', 'wallets.load_requests.reject',

            // Offers
            'offers.view', 'offers.create', 'offers.update', 'offers.delete', 'offers.toggle',

            // Payment Gateways
            'gateways.view', 'gateways.create', 'gateways.update', 'gateways.delete', 'gateways.test',

            // Voucher Providers
            'providers.view', 'providers.configure', 'providers.sync',

            // API Keys
            'api_keys.view', 'api_keys.create', 'api_keys.revoke',

            // Reports
            'reports.view', 'reports.export',

            // Audit Logs
            'audit_logs.view', 'audit_logs.export',

            // Security Dashboard
            'security.view', 'security.block_ip', 'security.unblock_ip',
            'security.block_mobile', 'security.unblock_mobile',
            'security.resolve_event', 'security.view_fraud_queue',

            // Support tickets
            'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply', 'tickets.resolve',

            // Settings
            'settings.view', 'settings.update', 'settings.roles.manage',

            // B2B Portal specific
            'b2b.place_order', 'b2b.bulk_order', 'b2b.view_orders',
            'b2b.manage_team', 'b2b.manage_api_keys',
            'b2b.wallet.view', 'b2b.wallet.load', 'b2b.wallet.request_load',
            'b2b.shop.view', 'b2b.price_list.view', 'b2b.finance.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin — most permissions except dangerous ones
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.publish', 'products.sync',
            'products.catalog.storefront', 'products.catalog.business', 'products.catalog.all',
            'categories.manage',
            'slides.view', 'slides.create', 'slides.update', 'slides.delete',
            'orders.view', 'orders.view_all', 'orders.cancel', 'orders.refund', 'orders.approve', 'orders.export',
            'users.view', 'users.update', 'users.block', 'users.assign_roles',
            'tenants.view', 'tenants.create', 'tenants.update', 'tenants.assign_products', 'tenants.set_pricing',
            'wallets.view', 'wallets.debit', 'wallets.freeze',
            'wallets.load_requests.view', 'wallets.load_requests.submit_on_behalf',
            'wallets.load_requests.approve', 'wallets.load_requests.reject',
            'offers.view', 'offers.create', 'offers.update', 'offers.delete', 'offers.toggle',
            'gateways.view', 'gateways.update', 'gateways.test',
            'providers.view', 'providers.configure', 'providers.sync',
            'api_keys.view', 'api_keys.create', 'api_keys.revoke',
            'reports.view', 'reports.export',
            'audit_logs.view', 'audit_logs.export',
            'security.view', 'security.block_ip', 'security.unblock_ip',
            'security.block_mobile', 'security.unblock_mobile', 'security.resolve_event',
            'tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply', 'tickets.resolve',
            'settings.view', 'settings.update', 'settings.roles.manage',
        ]);

        // Finance — wallet and order management
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $finance->givePermissionTo([
            'dashboard.view',
            'orders.view', 'orders.view_all', 'orders.refund', 'orders.export',
            'wallets.view', 'wallets.debit', 'wallets.freeze',
            'wallets.load_requests.view', 'wallets.load_requests.submit_on_behalf',
            'wallets.load_requests.approve', 'wallets.load_requests.reject',
            'reports.view', 'reports.export',
        ]);

        // B2B Client (owner) — manage their tenant
        $b2bClient = Role::firstOrCreate(['name' => 'b2b-client', 'guard_name' => 'web']);
        $b2bClient->givePermissionTo([
            'dashboard.view',
            'b2b.place_order', 'b2b.bulk_order', 'b2b.view_orders',
            'b2b.manage_team', 'b2b.manage_api_keys',
            'b2b.wallet.view', 'b2b.wallet.load', 'b2b.wallet.request_load',
            'b2b.shop.view', 'b2b.price_list.view', 'b2b.finance.view',
            'reports.view',
        ]);

        // B2B Operator — place orders only
        $b2bOperator = Role::firstOrCreate(['name' => 'b2b-operator', 'guard_name' => 'web']);
        $b2bOperator->givePermissionTo([
            'dashboard.view',
            'b2b.place_order', 'b2b.bulk_order', 'b2b.view_orders',
            'b2b.wallet.view',
            'b2b.shop.view', 'b2b.price_list.view',
        ]);

        // B2C User — regular customer
        $b2cUser = Role::firstOrCreate(['name' => 'b2c-user', 'guard_name' => 'web']);

        // Reseller — API access only
        $reseller = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
        $reseller->givePermissionTo(['b2b.place_order', 'b2b.view_orders']);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
