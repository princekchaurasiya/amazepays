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
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/'. env('dpID'));

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
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/' . env('dpID') . $productID);

        if ($response->successful()) {
            return response()->json([
                'products' => $response['products'],
                /*'productID' => $response['data']['productID'],
                 'categories' => $response['data']['categories'],
                'descriptionText' => $response['data']['descriptionText'] ?? null,
                'productDisplayName' => $response['data']['productDisplayName'] ?? null,
                'productName' => $response['data']['productName'],
                'redemptionInstructions' => $response['data']['redemptionInstructions'] ?? null,
                'termsAndConditions' => $response['data']['termsAndConditions'] ?? null,
                'variants' => $response['data']['variants'],*/
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
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/'. env('dpID'));

        if ($response->successful()) {
            return view('kgen.products', [
                'products' => $response['products'] ?? [],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch products']);
    }
}
