<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class VoyagerWoohooApiController extends Controller
{
    /**
     * Show the Woohoo API management page
     */
    public function index()
    {
        return view('admin.woohoo-api');
    }

    /**
     * Generate Bearer Token
     */
    public function generateBearerToken(Request $request)
    {
        try {
            Log::info('Admin: Generating Bearer Token via admin panel');
            $exitCode = Artisan::call('generate:bearerToken');
            
            $output = Artisan::output();
            
            // Check if command failed by looking for error indicators in output
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Authorization code verification failed') !== false ||
                       stripos($output, '403') !== false ||
                       stripos($output, '400') !== false;
            
            if ($hasError) {
                Log::error('Admin: Bearer Token generation failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate Bearer Token. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Bearer Token generated successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to generate Bearer Token', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Bearer Token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Category Data
     */
    public function fetchCategoryData(Request $request)
    {
        try {
            Log::info('Admin: Fetching Category Data via admin panel');
            $exitCode = Artisan::call('fetch:categoryData');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Something went wrong') !== false;
            
            if ($hasError) {
                Log::error('Admin: Category Data fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Category Data. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Category data fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Category Data', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Category Data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Product List
     */
    public function fetchProductList(Request $request)
    {
        try {
            Log::info('Admin: Fetching Product List via admin panel');
            $exitCode = Artisan::call('fetch:productList');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Something went wrong') !== false;
            
            if ($hasError) {
                Log::error('Admin: Product List fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Product List. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product list fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Product List', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Product List: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Product Data
     */
    public function fetchProductData(Request $request)
    {
        try {
            Log::info('Admin: Fetching Product Data via admin panel');
            $exitCode = Artisan::call('fetch:productData');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Something went wrong') !== false;
            
            if ($hasError) {
                Log::error('Admin: Product Data fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Product Data. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product data fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Product Data', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Product Data: ' . $e->getMessage()
            ], 500);
        }
    }
}
