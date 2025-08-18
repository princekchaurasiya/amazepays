<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Services\VDWebApiService;
use App\Models\Brand;

class StoreBrandsController extends Controller
{
    protected string $baseUrl = 'http://cards.vdwebapi.com/distributor/';
    protected $vdWebApiService;

    public function __construct(VDWebApiService $vdWebApiService)
    {
        $this->vdWebApiService = $vdWebApiService;
    }

public function getAndStoreBrands()
{
    //$token = 'P1QEQXWQ90A7BJHS9H78C6N3SLJITXA94QNT3P6MEWHX1IB48D1JYWEXR7F1NT1L';
    $token = $this->vdWebApiService->getToken();
    $brandCode = '';
    if (!$token) {
        return response()->json(['error' => 'Token is required'], 400);
    }

    try {
        $response = Http::withHeaders([
            'token' => $token,
        ])->post($this->baseUrl . 'api-getbrand/', [
            'BrandCode' => $brandCode,
        ]);

        if ($response->successful()) {
            $brands = $response->json();
            $encryptedBrandData = $brands['data'] ?? null;

            if (!$encryptedBrandData) {
                throw new \Exception('No data field in response.');
            }

            $decryptedBrandData = $this->vdWebApiService->decryptAES($encryptedBrandData);
            $brandsArray = json_decode($decryptedBrandData, true);

            if (!is_array($brandsArray)) {
                throw new \Exception('Invalid JSON data for brands.');
            }

            foreach ($brandsArray as $brandData) {
                Brand::updateOrCreate(
                    ['brand_code' => $brandData['BrandCode']],
                    [
                        'brand_name' => $brandData['BrandName'] ?? null,
                        'brand_type' => $brandData['Brandtype'] ?? null,
                        'discount' => $brandData['Discount'] ?? null,
                        'min_price' => $brandData['minPrice'] ?? null,
                        'max_price' => $brandData['maxPrice'] ?? null,
                        'denomination_list' => $brandData['DenominationList'] ?? null,
                        'stock_available' => $brandData['StockAvailable'] ?? null,
                        'category' => $brandData['Category'] ?? null,
                        'description' => $brandData['Description'] ?? null,
                        'images' => json_decode($brandData['Images'], true) ?: null,
                        'tnc' => $brandData['TnC'] ?? null,
                        'important_instruction' => $brandData['ImportantInstruction'] ?? null,
                        'redeem_steps' => $brandData['RedeemSteps'] ?? null,
                    ]
                );
            }

            return response()->json([
                'message' => 'Brands successfully retrieved and stored.',
                'brands_count' => count($brandsArray),
            ]);
        }

        Log::error('Get brands failed', ['response' => $response->body()]);
        return response()->json(['error' => 'Failed to fetch brands.'], 500);
    } catch (\Exception $e) {
        Log::error('Get brands exception', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Exception occurred: ' . $e->getMessage()], 500);
    }
}

}
