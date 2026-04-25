<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Services\VDHomeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ValueDesignStorefrontController extends Controller
{
    public function __construct(private readonly VDHomeService $vdHomeService) {}

    /**
     * Get Value Design brands for home page.
     */
    public function brandsForHome()
    {
        try {
            $brands = $this->vdHomeService->getVDBrandsForHome();

            return response()->json([
                'success' => true,
                'brands' => $brands,
                'count' => count($brands),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Value Design brands for home', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands',
                'brands' => [],
            ], 500);
        }
    }

    public function clearBrandsCache()
    {
        try {
            Cache::forget('vd_brands_home');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing Value Design brands cache', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
            ], 500);
        }
    }

    public function testConnection()
    {
        try {
            $token = $this->vdHomeService->getVDToken();

            if ($token) {
                return response()->json([
                    'success' => true,
                    'message' => 'Value Design API connection successful',
                    'token' => '[redacted]',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Value Design API connection failed',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error testing Value Design connection', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Value Design API connection failed: '.$e->getMessage(),
            ], 500);
        }
    }
}

