<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StorefrontBrand;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public B2B marketing landing page (links into authenticated /panel/b2b).
 */
class StorefrontBusinessController extends Controller
{
    public function show(): Response
    {
        $categories = Category::query()->orderBy('order')->get();
        $brands = StorefrontBrand::query()->orderBy('order')->get();

        $brandMaxDiscounts = Product::query()
            ->where('show_product', true)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
            ->groupBy('brand_id')
            ->pluck('max_discount', 'brand_id');

        $featuredProducts = Product::query()
            ->where('show_product', true)
            ->orderByRaw('IFNULL(priority, 999999) ASC')
            ->orderByRaw('IFNULL(display_order, 999999) ASC')
            ->limit(12)
            ->get();

        $savingsDisplay = config('storefront.business_savings_display', 'Rs. 500');

        return Inertia::render('Storefront/Business', [
            'categories' => $categories,
            'brands' => $brands,
            'brandMaxDiscounts' => $brandMaxDiscounts,
            'featuredProducts' => $featuredProducts,
            'savingsDisplay' => $savingsDisplay,
        ]);
    }
}
