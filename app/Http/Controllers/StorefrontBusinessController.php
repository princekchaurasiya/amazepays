<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public B2B marketing landing page (links into authenticated /panel/b2b).
 */
class StorefrontBusinessController extends Controller
{
    public function show(): Response
    {
        $categories = Category::query()->orderBy('display_order')->get();
        $brands = Brand::query()->orderBy('display_order')->get();

        $brandMaxDiscounts = Product::query()
            ->forStorefrontCatalog()
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
            ->groupBy('brand_id')
            ->pluck('max_discount', 'brand_id');

        $featuredProducts = Product::query()
            ->forStorefrontCatalog()
            ->orderByRaw('IFNULL(hot_deal_rank, 999999) ASC')
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
