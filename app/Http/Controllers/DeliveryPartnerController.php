<?php

namespace App\Http\Controllers;

use App\Helpers\KgenPricingHelper;
use App\Models\KgenProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DeliveryPartnerController extends Controller
{
    // Authenticate Merchant
    /* public function authenticate()
     {
         $response = Http::post(env('EXLR8_BASE_URL') . '/api/v1/delivery-partners/authenticate', [
             'userId' => env('EXLR8_USER_ID'),
             'userSecret' => env('EXLR8_USER_SECRET'),
         ]);

         if ($response->successful()) {
             return response()->json([
                 'accessToken' => $response['accessToken'],
             ]);
         }

         return response()->json(['error' => 'Authentication failed'], 401);
     }*/

    public function store(Request $request)
    {
        if (! auth()->check()) {
            return kgenError('unauthenticated', 'UNAUTHORIZED');
        }

    }

    // Get Products
    public function getProducts(Request $request)
    {
        $queryParams = array_filter([
            'nextCursor' => $request->page ?? 1,
            'limit' => $request->limit ?? 10,
        ]);

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID'));

        if ($response->successful()) {
            return response()->json([
                'products' => $response['products'],
                /* 'categories' => $response['products']['categories'],
                'descriptionText' => $response['products']['descriptionText'] ?? null,
                'productDisplayName' => $response['products']['productDisplayName'] ?? null,
                'productName' => $response['products']['productName'] ?? null,
                'redemptionInstructions' => $response['products']['redemptionInstructions'] ?? null,
                'termsAndConditions' => $response['products']['termsAndConditions'] ?? null,
                'variants' => $response['products']['variants'],*/
            ]);
        }

        return response()->json(['error' => 'Failed to fetch products'], $response->status());
    }

    public function getproductsbyID(Request $request, $productID)
    {
        $queryParams = array_filter([
            'productID' => $productID,
        ]);
        //    'productID' => $request->productID,
        //  'variantID' => $request->variantID,

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID').'/'.$productID);

        if ($response->successful()) {
            return response()->json([
                'productID' => $response['productID'],
                'categories' => $response['categories'],
                'descriptionText' => $response['descriptionText'] ?? null,
                'productDisplayName' => $response['productDisplayName'] ?? null,
                'productName' => $response['productName'],
                'redemptionInstructions' => $response['redemptionInstructions'] ?? null,
                'termsAndConditions' => $response['termsAndConditions'] ?? null,
                'variants' => $response['variants'],
            ]);
        }

        return response()->json(['error' => 'Failed to fetch product details'], $response->status());
    }

    public function showproducts(Request $request)
    {
        $queryParams = array_filter([
            'nextCursor' => $request->page ?? 1,
            'limit' => $request->limit ?? 10,
        ]);

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID'));

        if ($response->successful()) {
            // ✅ Start with full product list from API
            $products = $response['products'] ?? [];

            // ✅ Apply search filter
            if ($request->filled('search')) {
                $search = strtolower($request->search);

                $products = collect($products)
                    ->filter(function ($product) use ($search) {
                        return str_contains(strtolower($product['productDisplayName'] ?? ''), $search)
                            || str_contains(strtolower($product['productID'] ?? ''), $search)
                            || str_contains(strtolower($product['productName'] ?? ''), $search)
                            || collect($product['categories'] ?? [])->contains(function ($category) use ($search) {
                                return str_contains(strtolower($category['categoryName'] ?? ''), $search);
                            });
                    })
                    ->values()
                    ->all();
            }

            // Enrich with catalog discount percentage by product name (from products table)
            $discountMap = Product::whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0)
                ->pluck('discount_percentage', 'name')
                ->mapWithKeys(function ($discount, $name) {
                    $normalized = strtolower(trim((string) $name));

                    return $normalized !== '' ? [$normalized => (float) $discount] : [];
                })
                ->all();

            $products = collect($products)->map(function ($product) use ($discountMap) {
                $nameKeys = [
                    strtolower(trim((string) ($product['productDisplayName'] ?? ''))),
                    strtolower(trim((string) ($product['productName'] ?? ''))),
                ];
                $discount = 0;
                foreach ($nameKeys as $key) {
                    if ($key !== '' && isset($discountMap[$key])) {
                        $discount = $discountMap[$key];
                        break;
                    }
                }
                $product['discount_percentage'] = $discount;

                return KgenPricingHelper::enrichKgenProductForListing($product);
            })->all();

            // ✅ Always return the correct (filtered or not) list
            return Inertia::render('Storefront/KGen/Products', [
                'products' => $products,
            ]);
        }

        // Log error if API fails
        \Log::error('Failed to fetch DP products', [
            'status' => $response->status(),
            'body' => $response->body(),
            'query' => $queryParams,
        ]);

        return Inertia::render('Storefront/KGen/Products', [
            'products' => [],
            'error' => 'Failed to fetch products from API. Check logs.',
        ]);
    }

    public function fetchAndStoreProducts()
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID'));

        if ($response->successful()) {
            $products = $response['products'];

            foreach ($products as $product) {
                KgenProduct::updateOrCreate(
                    ['productID' => $product['productID']], // check uniqueness
                    [
                        'productName' => $product['productName'],
                        'productDisplayName' => $product['productDisplayName'],
                        'descriptionText' => $product['descriptionText'],
                        'redemptionInstructions' => $product['redemptionInstructions'],
                        'termsAndConditions' => $product['termsAndConditions'],
                        'attachments' => json_encode($product['attachments']),
                        'categories' => json_encode($product['categories']),
                        'variants' => json_encode($product['variants']),
                    ]
                );
            }

            return response()->json(['message' => 'Products stored successfully']);
        }

        return response()->json(['message' => 'Failed to fetch products'], 500);
    }
}
