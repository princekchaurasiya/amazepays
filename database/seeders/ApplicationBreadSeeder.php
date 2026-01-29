<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;

class ApplicationBreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu = Menu::where('name', 'admin')->firstOrFail();

        // Define all application tables with their BREAD configuration
        $tables = [
            [
                'name' => 'qs_products',
                'display_name_singular' => 'Product',
                'display_name_plural' => 'Products',
                'icon' => 'voyager-bag',
                'model' => 'App\\Models\\QsProduct',
                'menu_order' => 15,
            ],
            [
                'name' => 'amazepay_categories',
                'display_name_singular' => 'Category',
                'display_name_plural' => 'Categories',
                'icon' => 'voyager-categories',
                'model' => 'App\\Models\\AmazepayCategory',
                'menu_order' => 16,
            ],
            [
                'name' => 'amazepay_available_brands',
                'display_name_singular' => 'Brand',
                'display_name_plural' => 'Brands',
                'icon' => 'voyager-star',
                'model' => 'App\\Models\\AmazepayBrand',
                'menu_order' => 17,
            ],
            [
                'name' => 'slides',
                'display_name_singular' => 'Slide',
                'display_name_plural' => 'Slides',
                'icon' => 'voyager-images',
                'model' => 'App\\Models\\Slide',
                'menu_order' => 18,
            ],
            [
                'name' => 'home',
                'display_name_singular' => 'Home Setting',
                'display_name_plural' => 'Home Settings',
                'icon' => 'voyager-home',
                'model' => 'App\\Models\\Home',
                'menu_order' => 19,
            ],
            [
                'name' => 'unlimit_payment',
                'display_name_singular' => 'Unlimit Payment',
                'display_name_plural' => 'Unlimit Payments',
                'icon' => 'voyager-credit-card',
                'model' => 'App\\Models\\UnlimitPayment',
                'menu_order' => 21,
            ],
            [
                'name' => 'order_summary',
                'display_name_singular' => 'Order Summary',
                'display_name_plural' => 'Order Summaries',
                'icon' => 'voyager-receipt',
                'model' => 'App\\Models\\OrderSummary',
                'menu_order' => 22,
            ],
            [
                'name' => 'payments',
                'display_name_singular' => 'Payment',
                'display_name_plural' => 'Payments',
                'icon' => 'voyager-wallet',
                'model' => 'App\\Models\\Payment',
                'menu_order' => 23,
            ],
            [
                'name' => 'wallets',
                'display_name_singular' => 'Wallet',
                'display_name_plural' => 'Wallets',
                'icon' => 'voyager-wallet',
                'model' => 'App\\Models\\Wallet',
                'menu_order' => 24,
            ],
            [
                'name' => 'wallet_transactions',
                'display_name_singular' => 'Wallet Transaction',
                'display_name_plural' => 'Wallet Transactions',
                'icon' => 'voyager-list',
                'model' => 'App\\Models\\WalletTransaction',
                'menu_order' => 25,
            ],
            [
                'name' => 'k_gen_orders',
                'display_name_singular' => 'KGen Order',
                'display_name_plural' => 'KGen Orders',
                'icon' => 'voyager-shopping-bag',
                'model' => 'App\\Models\\KGenOrder',
                'menu_order' => 26,
            ],
            [
                'name' => 'kgen_products',
                'display_name_singular' => 'KGen Product',
                'display_name_plural' => 'KGen Products',
                'icon' => 'voyager-bag',
                'model' => 'App\\Models\\KgenProduct',
                'menu_order' => 27,
            ],
            [
                'name' => 'billings',
                'display_name_singular' => 'Billing',
                'display_name_plural' => 'Billings',
                'icon' => 'voyager-file-text',
                'model' => 'App\\Models\\Billing',
                'menu_order' => 28,
            ],
            [
                'name' => 'invoices',
                'display_name_singular' => 'Invoice',
                'display_name_plural' => 'Invoices',
                'icon' => 'voyager-file-text',
                'model' => 'App\\Models\\Invoice',
                'menu_order' => 29,
            ],
            [
                'name' => 'api_tokens',
                'display_name_singular' => 'API Token',
                'display_name_plural' => 'API Tokens',
                'icon' => 'voyager-key',
                'model' => 'App\\Models\\ApiToken',
                'menu_order' => 30,
            ],
            [
                'name' => 'contact_us',
                'display_name_singular' => 'Contact',
                'display_name_plural' => 'Contacts',
                'icon' => 'voyager-mail',
                'model' => 'App\\Models\\ContactUs',
                'menu_order' => 31,
            ],
            [
                'name' => 'otps',
                'display_name_singular' => 'OTP',
                'display_name_plural' => 'OTPs',
                'icon' => 'voyager-lock',
                'model' => 'App\\Models\\Otp',
                'menu_order' => 32,
            ],
            [
                'name' => 'user_ips',
                'display_name_singular' => 'User IP',
                'display_name_plural' => 'User IPs',
                'icon' => 'voyager-world',
                'model' => 'App\\Models\\UserIp',
                'menu_order' => 33,
            ],
            [
                'name' => 'transaction_reports',
                'display_name_singular' => 'Transaction Report',
                'display_name_plural' => 'Transaction Reports',
                'icon' => 'voyager-bar-chart',
                'model' => 'App\\Models\\TransactionReport',
                'menu_order' => 34,
            ],
        ];

        foreach ($tables as $table) {
            // Create DataType (BREAD)
            $dataType = DataType::firstOrNew(['name' => $table['name']]);
            if (!$dataType->exists) {
                $dataType->fill([
                    'slug' => str_replace('_', '-', $table['name']),
                    'display_name_singular' => $table['display_name_singular'],
                    'display_name_plural' => $table['display_name_plural'],
                    'icon' => $table['icon'],
                    'model_name' => $table['model'],
                    'policy_name' => null,
                    'controller' => '',
                    'description' => '',
                    'generate_permissions' => 1,
                    'server_side' => 0,
                ])->save();

                $this->command->info("✓ Created BREAD for {$table['name']}");
                
                // Generate permissions
                $permissions = ['browse', 'read', 'edit', 'add', 'delete'];
                foreach ($permissions as $action) {
                    Permission::firstOrCreate([
                        'key' => $action . '_' . $table['name'],
                        'table_name' => $table['name'],
                    ]);
                }
                $this->command->info("✓ Generated permissions for {$table['name']}");
            }

            // Create Menu Item
            $slug = str_replace('_', '-', $table['name']);
            $menuItem = MenuItem::firstOrNew([
                'menu_id' => $menu->id,
                'title' => $table['display_name_plural'],
            ]);

            if (!$menuItem->exists || !$menuItem->route) {
                $menuItem->fill([
                    'url' => '',
                    'route' => "voyager.{$slug}.index",
                    'target' => '_self',
                    'icon_class' => $table['icon'],
                    'color' => null,
                    'parent_id' => null,
                    'order' => $table['menu_order'],
                ])->save();

                $this->command->info("✓ Created menu item for {$table['display_name_plural']}");
            }
        }

        // Assign permissions to admin role
        $adminRole = \TCG\Voyager\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $permissions = Permission::all();
            $adminRole->permissions()->sync($permissions->pluck('id')->all());
            $this->command->info("✓ Assigned all permissions to admin role");
        }
    }
}
