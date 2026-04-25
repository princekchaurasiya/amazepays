<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Inertia\Inertia;

class StorefrontBrandController extends Controller
{
    public function show(string $slug)
    {
        $brand = Brand::query()->where('slug', $slug)->first();
        if (! $brand) {
            return abort(404, 'Brand not found');
        }

        $products = Product::query()
            ->where('brand_id', $brand->id)
            ->forStorefrontCatalog()
            ->get();

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
