<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StorefrontBrand;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StorefrontBrandController extends Controller
{
    public function show(string $slug)
    {
        $brand = StorefrontBrand::where('slug', $slug)->first();
        $allBrands = StorefrontBrand::orderBy('order')->get();

        if (! $brand) {
            Log::warning('Brand not found:', ['slug' => $slug]);

            return abort(404, 'Brand not found');
        }

        Log::info('Fetched brand:', ['slug' => $slug, 'brand_id' => $brand->id]);

        $products = Product::query()
            ->where('brand_id', $brand->id)
            ->where('show_product', true)
            ->get();

        Log::info('Fetched products for brand:', ['brand_id' => $brand->id, 'products_count' => $products->count()]);

        foreach ($products as $product) {
            $product->images = json_decode($product->images, true);
            $product->currency = json_decode($product->currency, true);
        }

        return Inertia::render('Storefront/Brand', [
            'brand' => $brand,
            'products' => $products,
        ]);
    }
}
