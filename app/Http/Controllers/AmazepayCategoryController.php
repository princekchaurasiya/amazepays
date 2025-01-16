<?php

namespace App\Http\Controllers;

use App\Models\AmazepayCategory;
use App\Models\AmazepayCategoryProduct;
use App\Models\QsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AmazepayCategoryController extends Controller
{
    public function show($slug)
    {
        $category = AmazepayCategory::where('slug', $slug)->first();
        $allCategories = AmazepayCategory::orderBy('order')->get();

        if (!$category) {
            Log::warning('Category not found:', ['slug' => $slug]);
            return abort(404, 'Category not found');
        }

        Log::info('Fetched category:', ['slug' => $slug, 'category_id' => $category->id]);

        $relationships = AmazepayCategoryProduct::where('amazepay_category_id', $category->id)->get();

        Log::info('Fetched relationships for category:', ['category_id' => $category->id, 'relationships_count' => $relationships->count()]);

        if ($relationships->isEmpty()) {
            Log::info('No relationships found for category:', ['category_id' => $category->id]);
            return view('categories.show', compact('category'))->with('products', []);
        }

        $productIds = $relationships->pluck('qs_product_id');

        Log::info('Extracted product IDs:', ['product_ids' => $productIds]);

        $products = QsProduct::whereIn('id', $productIds)->get();

        // Decode JSON attributes if needed
        foreach ($products as $product) {
            $product->images = json_decode($product->images, true); // decode as associative array
            $product->currency = json_decode($product->currency, true);
            // Do this for any other JSON attributes
        }

        Log::info('Fetched products:', ['products_count' => $products->count()]);

        return view('categories.show', compact('category', 'products', 'allCategories'));
    }

}
