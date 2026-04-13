<?php

namespace App\Http\Controllers;

use App\Helpers\ContentFormatter;
use App\Models\Product;
use App\Services\Catalog\ProductContentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProductSlugController extends Controller
{
    public function getProductBySlug(Request $request)
    {
        try {
            $product = Product::where('url', $request->slug)->first();
            // dd($product);

            if (! $product) {
                return Inertia::render('Error', [
                    'status' => 404,
                    'message' => 'Product not found.',
                ])->toResponse($request)->setStatusCode(404);
            }

            // Access attributes first to trigger model accessors (auto-decode JSON fields)
            // Then convert to array - this ensures price, currency, images are properly decoded
            $productDetails = $product->toArray();

            // Override with accessor values to ensure JSON fields are decoded
            $productDetails['price'] = $product->price;
            $productDetails['images'] = $product->images;
            $productDetails['currency'] = $product->currency;
            $productDetails['display_image_url'] = $product->display_image_url;

            $content = app(ProductContentService::class);

            // Decode howToUse safely from `cpg` (fallback when no admin content)
            $decodedHowToUse = ! empty($productDetails['how_to_redeem'])
                ? $productDetails['how_to_redeem']
                : (! empty($productDetails['cpg'])
                    ? $this->parseHowToUse($productDetails['cpg'])
                    : 'No how to redeem available.');

            $descriptionData = $content->resolveDescription($product);
            $formattedTncData = $content->resolveTnc($product);
            $resolvedHow = $content->resolveHowToRedeem($product);
            $formatteddecodedHowToUse = $resolvedHow
                ? $resolvedHow
                : ContentFormatter::extractHowToRedeem($decodedHowToUse);

            return Inertia::render('Storefront/Product', [
                'productDetails' => $productDetails,
                'formattedTncData' => $formattedTncData,
                'descriptionData' => $descriptionData,
                'formatteddecodedHowToUse' => $formatteddecodedHowToUse,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching product by slug: '.$e->getMessage());

            return Inertia::render('Error', [
                'status' => 500,
                'message' => 'Something went wrong. Please try again later.',
            ])->toResponse($request)->setStatusCode(500);
        }
    }

    private function parseHowToUse($cpgString)
    {
        try {
            if (! $cpgString) {
                return null;
            }

            $pattern = '/s:8:"howToUse";s:\d+:"([^"]+)"/';
            if (preg_match($pattern, $cpgString, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
