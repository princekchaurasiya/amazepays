<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;

class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder ensures all admin menu items have proper icons and removes duplicates.
     *
     * @return void
     */
    public function run()
    {
        $menu = Menu::where('name', 'admin')->firstOrFail();

        // Remove old/duplicate menu items
        $this->removeOldMenuItems($menu);

        // Define all menu items with their proper configuration
        $menuItems = [
            // User Blocking (ensure single entry with icon)
            [
                'title' => 'User Blocking',
                'route' => 'admin.user.blocking',
                'icon_class' => 'voyager-lock',
                'order' => 11,
            ],
            // Qs Products
            [
                'title' => 'Qs Products',
                'route' => 'voyager.qs-products.index',
                'icon_class' => 'voyager-bag',
                'order' => 15,
            ],
            // Amazepay Available Brands
            [
                'title' => 'Amazepay Available Brands',
                'route' => 'voyager.amazepay-available-brands.index',
                'icon_class' => 'voyager-star',
                'order' => 16,
            ],
            // Amazepay Categories
            [
                'title' => 'Amazepay Categories',
                'route' => 'voyager.amazepay-categories.index',
                'icon_class' => 'voyager-categories',
                'order' => 17,
            ],
            // Slides
            [
                'title' => 'Slides',
                'route' => 'voyager.slides.index',
                'icon_class' => 'voyager-images',
                'order' => 18,
            ],
            // Homes
            [
                'title' => 'Homes',
                'route' => 'voyager.home.index',
                'icon_class' => 'voyager-home',
                'order' => 19,
            ],
            // API Management
            [
                'title' => 'API Management',
                'route' => 'admin.api.index',
                'icon_class' => 'voyager-cloud-download',
                'order' => 20,
            ],
            // Unlimit Payments
            [
                'title' => 'Unlimit Payments',
                'route' => 'voyager.unlimit-payment.index',
                'icon_class' => 'voyager-credit-card',
                'order' => 21,
            ],
            // Order Summaries
            [
                'title' => 'Order Summaries',
                'route' => 'voyager.order-summary.index',
                'icon_class' => 'voyager-receipt',
                'order' => 22,
            ],
        ];

        // Create or update each menu item
        foreach ($menuItems as $item) {
            $menuItem = MenuItem::firstOrNew([
                'menu_id' => $menu->id,
                'title' => $item['title'],
            ]);

            // For API Management, use direct URL to ensure it always works
            // Other items can use routes as they're Voyager BREAD routes
            if ($item['route'] === 'admin.api.index') {
                $menuItem->fill([
                    'url' => '/admin/api-management',
                    'route' => null, // Use URL instead of route for reliability
                    'target' => '_self',
                    'icon_class' => $item['icon_class'],
                    'color' => null,
                    'parent_id' => null,
                    'order' => $item['order'],
                ])->save();
            } else {
                $menuItem->fill([
                    'url' => '',
                    'route' => $item['route'],
                    'target' => '_self',
                    'icon_class' => $item['icon_class'],
                    'color' => null,
                    'parent_id' => null,
                    'order' => $item['order'],
                ])->save();
            }
        }
    }

    /**
     * Remove old/duplicate menu items
     *
     * @param Menu $menu
     * @return void
     */
    private function removeOldMenuItems(Menu $menu)
    {
        // Remove old "Woohoo API Management" entry
        MenuItem::where('menu_id', $menu->id)
            ->where('title', 'Woohoo API Management')
            ->where(function($query) {
                $query->where('route', 'admin.woohoo.api')
                      ->orWhere('route', 'like', '%woohoo%');
            })
            ->delete();

        // Remove duplicate "API Management" entries (keep only one)
        $apiManagementItems = MenuItem::where('menu_id', $menu->id)
            ->where('title', 'API Management')
            ->get();

        if ($apiManagementItems->count() > 1) {
            // Keep the one with the correct route, delete the rest
            $keepItem = $apiManagementItems->firstWhere('route', 'admin.api.index') 
                     ?? $apiManagementItems->first();
            MenuItem::where('menu_id', $menu->id)
                ->where('title', 'API Management')
                ->where('id', '!=', $keepItem->id)
                ->delete();
        }

        // Remove duplicate "User Blocking" entries (keep the one with proper icon)
        $userBlockingItems = MenuItem::where('menu_id', $menu->id)
            ->where('title', 'User Blocking')
            ->get();

        if ($userBlockingItems->count() > 1) {
            // Keep the first one, delete the rest
            $keepItem = $userBlockingItems->first();
            MenuItem::where('menu_id', $menu->id)
                ->where('title', 'User Blocking')
                ->where('id', '!=', $keepItem->id)
                ->delete();
        }

        // Remove old export menu items (consolidated into API Management)
        MenuItem::where('menu_id', $menu->id)
            ->whereIn('title', [
                'Export User Data For Payment',
                'Export Payment Details Unlimit',
                'Excel Merge'
            ])
            ->delete();

        // Also remove by route/url patterns
        MenuItem::where('menu_id', $menu->id)
            ->where(function($query) {
                $query->where('route', 'export.users')
                      ->orWhere('route', 'payments.export')
                      ->orWhere('route', 'excel.form')
                      ->orWhere('route', 'excel.merge')
                      ->orWhere('url', 'like', '%export-users%')
                      ->orWhere('url', 'like', '%export-payments%')
                      ->orWhere('url', 'like', '%excel-merge%');
            })
            ->delete();
    }
}
