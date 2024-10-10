<?php

namespace App\Http\Controllers;

use App\Models\AmazepayBrand;
use App\Models\QsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AmazepayBrandController extends Controller
{
    public function show($slug)
    {
        // Fetch the brand based on the slug
        $brand = AmazepayBrand::where('slug', $slug)->first();

        if (!$brand) {
            Log::warning('Brand not found:', ['slug' => $slug]);
            return abort(404, 'Brand not found');
        }

        Log::info('Fetched brand:', ['slug' => $slug, 'brand_id' => $brand->id]);

        // Fetch the products related to this brand (assuming a one-to-many relationship)
        $products = QsProduct::where('brand_id', $brand->id)->get();


        Log::info('Fetched products for brand:', ['brand_id' => $brand->id, 'products_count' => $products->count()]);

        // Decode JSON attributes for products if necessary
        foreach ($products as $product) {
            $product->images = json_decode($product->images, true); // decode as associative array
            $product->currency = json_decode($product->currency, true);
        }

        return view('brands.show', compact('brand', 'products'));
    }
}
