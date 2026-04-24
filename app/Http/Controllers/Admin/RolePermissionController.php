<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    /**
     * @var array<string, list<string>>
     */
    public const PERMISSION_GROUPS = [
        'Dashboard' => ['dashboard.view'],
        'Products' => [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.publish', 'products.sync',
            'products.catalog.storefront', 'products.catalog.business', 'products.catalog.all',
        ],
        'Categories' => ['categories.manage'],
        'Orders' => [
            'orders.view', 'orders.view_all', 'orders.cancel', 'orders.refund',
            'orders.approve', 'orders.export',
        ],
        'Users' => [
            'users.view', 'users.create', 'users.update', 'users.block',
            'users.delete', 'users.impersonate', 'users.assign_roles',
        ],
        'Tenants / B2B' => [
            'tenants.view', 'tenants.create', 'tenants.update', 'tenants.suspend',
            'tenants.delete', 'tenants.assign_products', 'tenants.set_pricing',
        ],
        'Wallets' => [
            'wallets.view', 'wallets.debit', 'wallets.freeze',
            'wallets.load_requests.view', 'wallets.load_requests.submit_on_behalf',
            'wallets.load_requests.approve', 'wallets.load_requests.reject',
        ],
        'Offers' => ['offers.view', 'offers.create', 'offers.update', 'offers.delete', 'offers.toggle'],
        'Payment gateways' => [
            'gateways.view', 'gateways.create', 'gateways.update', 'gateways.delete', 'gateways.test',
        ],
        'Providers' => ['providers.view', 'providers.configure', 'providers.sync'],
        'API keys' => ['api_keys.view', 'api_keys.create', 'api_keys.revoke'],
        'Reports' => ['reports.view', 'reports.export'],
        'Audit logs' => ['audit_logs.view', 'audit_logs.export'],
        'Security' => [
            'security.view', 'security.block_ip', 'security.unblock_ip',
            'security.block_mobile', 'security.unblock_mobile',
            'security.resolve_event', 'security.view_fraud_queue',
        ],
        'Tickets' => ['tickets.view', 'tickets.create', 'tickets.update', 'tickets.reply', 'tickets.resolve'],
        'Settings' => ['settings.view', 'settings.update', 'settings.roles.manage'],
        'B2B portal' => [
            'b2b.place_order', 'b2b.bulk_order', 'b2b.view_orders',
            'b2b.manage_team', 'b2b.manage_api_keys',
            'b2b.wallet.view', 'b2b.wallet.load', 'b2b.wallet.request_load',
            'b2b.shop.view', 'b2b.price_list.view', 'b2b.finance.view',
        ],
    ];

    /**
     * Human-readable labels for the roles UI (permission `name` stays the system key).
     *
     * @var array<string, string>
     */
    public const PERMISSION_LABELS = [
        'dashboard.view' => 'View dashboard',

        'products.view' => 'View products',
        'products.create' => 'Create products',
        'products.update' => 'Edit products',
        'products.delete' => 'Delete products',
        'products.publish' => 'Publish / unpublish products',
        'products.sync' => 'Sync products from voucher providers',
        'products.catalog.storefront' => 'Catalog scope: storefront (B2C) products',
        'products.catalog.business' => 'Catalog scope: business (B2B) products',
        'products.catalog.all' => 'Catalog scope: all products (any audience)',

        'categories.manage' => 'Manage storefront categories',

        'orders.view' => 'View orders',
        'orders.view_all' => 'View all orders (every tenant)',
        'orders.cancel' => 'Cancel orders',
        'orders.refund' => 'Refund orders',
        'orders.approve' => 'Approve orders',
        'orders.export' => 'Export orders',

        'users.view' => 'View users',
        'users.create' => 'Create users',
        'users.update' => 'Edit users',
        'users.block' => 'Block or unblock users',
        'users.delete' => 'Delete users',
        'users.impersonate' => 'Log in as another user (impersonate)',
        'users.assign_roles' => 'Assign roles and permissions to users',

        'tenants.view' => 'View B2B tenants',
        'tenants.create' => 'Create tenants',
        'tenants.update' => 'Edit tenants',
        'tenants.suspend' => 'Suspend or restore tenants',
        'tenants.delete' => 'Delete tenants',
        'tenants.assign_products' => 'Assign catalog products to tenants',
        'tenants.set_pricing' => 'Set tenant pricing overrides',

        'wallets.view' => 'View wallets',
        'wallets.debit' => 'Debit wallet balances',
        'wallets.freeze' => 'Freeze or unfreeze wallets',
        'wallets.load_requests.view' => 'View wallet top-up requests',
        'wallets.load_requests.submit_on_behalf' => 'Submit wallet top-up requests on behalf of a user',
        'wallets.load_requests.approve' => 'Approve wallet top-up requests',
        'wallets.load_requests.reject' => 'Reject wallet top-up requests',

        'offers.view' => 'View offers',
        'offers.create' => 'Create offers',
        'offers.update' => 'Edit offers',
        'offers.delete' => 'Delete offers',
        'offers.toggle' => 'Enable or disable offers',

        'gateways.view' => 'View payment gateways',
        'gateways.create' => 'Create payment gateways',
        'gateways.update' => 'Edit payment gateways',
        'gateways.delete' => 'Delete payment gateways',
        'gateways.test' => 'Test payment gateway connections',

        'providers.view' => 'View voucher providers',
        'providers.configure' => 'Configure voucher providers',
        'providers.sync' => 'Run provider catalog sync',

        'api_keys.view' => 'View API keys',
        'api_keys.create' => 'Create API keys',
        'api_keys.revoke' => 'Revoke API keys',

        'reports.view' => 'View reports',
        'reports.export' => 'Export reports',

        'audit_logs.view' => 'View audit log',
        'audit_logs.export' => 'Export audit log',

        'security.view' => 'Open security dashboard',
        'security.block_ip' => 'Block IP addresses',
        'security.unblock_ip' => 'Unblock IP addresses',
        'security.block_mobile' => 'Block mobile numbers',
        'security.unblock_mobile' => 'Unblock mobile numbers',
        'security.resolve_event' => 'Resolve security events',
        'security.view_fraud_queue' => 'View fraud review queue',

        'tickets.view' => 'View support tickets',
        'tickets.create' => 'Create support tickets',
        'tickets.update' => 'Edit support tickets',
        'tickets.reply' => 'Reply on tickets',
        'tickets.resolve' => 'Resolve or close tickets',

        'settings.view' => 'View settings',
        'settings.update' => 'Change settings',
        'settings.roles.manage' => 'Manage roles and permissions',

        'b2b.place_order' => 'B2B portal: place orders',
        'b2b.bulk_order' => 'B2B portal: bulk orders',
        'b2b.view_orders' => 'B2B portal: view own orders',
        'b2b.manage_team' => 'B2B portal: manage company team',
        'b2b.manage_api_keys' => 'B2B portal: manage API keys',
        'b2b.wallet.view' => 'B2B portal: view wallet',
        'b2b.wallet.load' => 'B2B portal: load wallet (credit)',
        'b2b.wallet.request_load' => 'B2B portal: request wallet top-up',
        'b2b.shop.view' => 'B2B portal: open shop / catalog',
        'b2b.price_list.view' => 'B2B portal: view price list',
        'b2b.finance.view' => 'B2B portal: view financial activity',
    ];

    public static function permissionLabel(string $name): string
    {
        if (isset(self::PERMISSION_LABELS[$name])) {
            return self::PERMISSION_LABELS[$name];
        }

        $base = str_replace(['.', '_'], ' ', $name);

        return Str::headline($base);
    }

    public function index(): Response
    {
        Gate::authorize('settings.roles.manage');

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions_count' => $r->permissions_count,
            ]);

        return Inertia::render('Admin/Settings/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): Response
    {
        Gate::authorize('settings.roles.manage');

        if ($role->guard_name !== 'web') {
            abort(404);
        }

        $assigned = $role->permissions()->pluck('name')->all();
        $allPermissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

        $grouped = [];
        $used = [];

        foreach (self::PERMISSION_GROUPS as $title => $names) {
            $items = [];
            foreach ($names as $name) {
                if (in_array($name, $allPermissions, true)) {
                    $items[] = [
                        'name' => $name,
                        'label' => self::permissionLabel($name),
                        'assigned' => in_array($name, $assigned, true),
                    ];
                    $used[] = $name;
                }
            }
            if ($items !== []) {
                $grouped[] = ['title' => $title, 'permissions' => $items];
            }
        }

        $other = array_values(array_diff($allPermissions, $used));
        if ($other !== []) {
            $grouped[] = [
                'title' => 'Other',
                'permissions' => array_map(fn ($name) => [
                    'name' => $name,
                    'label' => self::permissionLabel($name),
                    'assigned' => in_array($name, $assigned, true),
                ], $other),
            ];
        }

        return Inertia::render('Admin/Settings/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'groupedPermissions' => $grouped,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('settings.roles.manage');

        if ($role->guard_name !== 'web') {
            abort(404);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $names = $validated['permissions'] ?? [];
        $old = $role->permissions()->pluck('name')->sort()->values()->all();

        if ($role->name === 'super-admin') {
            $required = ['settings.roles.manage'];
            foreach ($required as $req) {
                if (! in_array($req, $names, true)) {
                    return back()->withErrors(['permissions' => 'The super-admin role must keep access to manage roles.']);
                }
            }
        }

        $role->syncPermissions($names);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $new = collect($names)->sort()->values()->all();
        audit('role.permissions_updated', $role, ['permissions' => $old], ['permissions' => $new]);

        return redirect()->route('panel.settings.roles.edit', $role)->with('success', 'Permissions saved.');
    }
}
