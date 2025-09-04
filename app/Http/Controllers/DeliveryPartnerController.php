<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/{dpID}', $queryParams);

        if ($response->successful()) {
            return response()->json([
                'message' => $response['message'],
                'products' => $response['data']['products'],
                'categories' => $response['data']['categories'],
                'createdAt' => $response['data']['createdAt'],
                'descriptionText' => $response['data']['descriptionText'] ?? null,
                'productDisplayName' => $response['data']['productDisplayName'] ?? null,
                'productName' => $response['data']['productName'] ?? null,
                'productOrigin' => $response['data']['productOrigin'] ?? null,
                'redemptionInstructions' => $response['data']['redemptionInstructions'] ?? null,
            ]);
            if ($queryParams == 'variantID') {
                return response()->json([
                    'message' => $response['message'],
                    'variants' => $response['data']['variants'],
                ]);

            }
        }

        if($queryParams == 'productID') {

            $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/product/{productID}');

            return response()->json([
                'data' => $response['data'],
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
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/{dpID}/{productID}');

        if ($response->successful()) {
            return response()->json([
                'data' => $response['data'],
            ]);
        }

        return response()->json(['error' => 'Failed to fetch product details'], $response->status());
    }
}
