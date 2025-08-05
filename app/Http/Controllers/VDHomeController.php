<?php

namespace App\Http\Controllers;

use App\Http\Services\VDHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VDHomeController extends Controller
{
    protected $vdHomeService;

    public function __construct(VDHomeService $vdHomeService)
    {
        $this->vdHomeService = $vdHomeService;
    }

    /**
     * Get Value Design brands for home page
     */
    public function getVDBrandsForHome()
    {
        try {
            $brands = $this->vdHomeService->getVDBrandsForHome();
            
            return response()->json([
                'success' => true,
                'brands' => $brands,
                'count' => count($brands)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching VD brands for home', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands',
                'brands' => []
            ], 500);
        }
    }

    /**
     * Clear cache for VD brands
     */
    public function clearVDBrandsCache()
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('vd_brands_home');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing VD brands cache', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Test VD brands API connection
     */
    public function testVDConnection()
    {
        try {
            $token = $this->vdHomeService->getVDToken();
            
            if ($token) {
                return response()->json([
                    'success' => true,
                    'message' => 'VD API connection successful',
                    'token' => substr($token, 0, 10) . '...' // Show only first 10 chars for security
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'VD API connection failed'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error testing VD connection', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'VD API connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
} 