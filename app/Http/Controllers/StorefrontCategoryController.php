<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/** Public storefront: category listing page by slug (table: categories). */
class StorefrontCategoryController extends Controller
{
    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)->first();
        $allCategories = Category::orderBy('order')->get();

        if (! $category) {
            Log::warning('Category not found:', ['slug' => $slug]);

            return abort(404, 'Category not found');
        }

        Log::info('Fetched category:', ['slug' => $slug, 'category_id' => $category->id]);

        $relationships = CategoryProduct::where('category_id', $category->id)->get();

        Log::info('Fetched relationships for category:', ['category_id' => $category->id, 'relationships_count' => $relationships->count()]);

        if ($relationships->isEmpty()) {
            Log::info('No relationships found for category:', ['category_id' => $category->id]);

            return Inertia::render('Storefront/Category', [
                'category' => $category,
                'products' => collect(),
            ]);
        }

        $productIds = $relationships->pluck('product_id');

        Log::info('Extracted product IDs:', ['product_ids' => $productIds->toArray()]);

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('show_product', true)
            ->get();

        foreach ($products as $product) {
            $product->images = json_decode($product->getRawOriginal('images') ?? 'null', true);
            $product->currency = json_decode($product->getRawOriginal('currency') ?? 'null', true);
        }

        Log::info('Fetched products:', ['products_count' => $products->count()]);

        return Inertia::render('Storefront/Category', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
