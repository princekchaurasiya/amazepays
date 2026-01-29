<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;

class UnlimitPaymentBreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create or update DataType for unlimit_payment
        $dataType = DataType::firstOrNew(['name' => 'unlimit_payment']);
        if (!$dataType->exists) {
            $dataType->fill([
                'slug' => 'unlimit-payment',
                'display_name_singular' => 'Unlimit Payment',
                'display_name_plural' => 'Unlimit Payments',
                'icon' => 'voyager-credit-card',
                'model_name' => 'App\\Models\\UnlimitPayment',
                'controller' => 'App\\Http\\Controllers\\Voyager\\UnlimitPaymentController',
                'policy_name' => null,
                'description' => '',
                'generate_permissions' => 1,
                'server_side' => 1,
                'order_column' => 'id',
                'order_display_column' => 'id',
                'order_direction' => 'desc',
                'default_search_key' => 'id',
            ])->save();
        }

        // Create menu item
        $menu = Menu::where('name', 'admin')->first();
        if ($menu) {
            $menuItem = MenuItem::firstOrNew([
                'menu_id' => $menu->id,
                'title' => 'Unlimit Payments',
                'url' => '',
                'route' => 'voyager.unlimit-payment.index',
            ]);
            
            if (!$menuItem->exists) {
                $menuItem->fill([
                    'target' => '_self',
                    'icon_class' => 'voyager-credit-card',
                    'color' => null,
                    'parent_id' => null,
                    'order' => 15,
                ])->save();
            }
        }

        // Generate permissions
        Permission::generateFor('unlimit_payment');
    }
}
