<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class VoyagerApiController extends Controller
{
    /**
     * Show the API management page
     */
    public function index()
    {
        return view('admin.api-management');
    }

    /**
     * Generate Bearer Token (Woohoo)
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
                       stripos($output, 'Missing required API credentials') !== false ||
                       stripos($output, 'Authorization code verification failed') !== false ||
                       stripos($output, '❌') !== false ||
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
     * Fetch Category Data (Woohoo)
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
     * Fetch Product List (Woohoo)
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
     * Fetch Product Data (Woohoo)
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

    /**
     * Fetch KGen Products
     */
    public function fetchKgenProducts(Request $request)
    {
        try {
            Log::info('Admin: Fetching KGen Products via admin panel');
            $exitCode = Artisan::call('fetch:kgenProducts');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Something went wrong') !== false ||
                       stripos($output, 'credentials are not configured') !== false;
            
            if ($hasError) {
                Log::error('Admin: KGen Products fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch KGen Products. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'KGen products fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch KGen Products', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch KGen Products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Featured KGen Products
     */
    public function fetchFeaturedKgenProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 8);
            Log::info('Admin: Fetching Featured KGen Products via admin panel', ['limit' => $limit]);
            $exitCode = Artisan::call('fetch:featuredKgenProducts', ['--limit' => $limit]);
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Something went wrong') !== false ||
                       stripos($output, 'credentials are not configured') !== false;
            
            if ($hasError) {
                Log::error('Admin: Featured KGen Products fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Featured KGen Products. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Featured KGen products fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Featured KGen Products', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Featured KGen Products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Value Design Brands
     */
    public function fetchVDBrands(Request $request)
    {
        try {
            Log::info('Admin: Fetching Value Design Brands via admin panel');
            $exitCode = Artisan::call('fetch:vdBrands');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false;
            
            if ($hasError) {
                Log::error('Admin: Value Design Brands fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Value Design Brands. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Value Design brands fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Value Design Brands', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Value Design Brands: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync Value Design Stores
     */
    public function syncVDStores(Request $request)
    {
        try {
            Log::info('Admin: Syncing Value Design Stores via admin panel');
            $exitCode = Artisan::call('sync:vdStores');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false;
            
            if ($hasError) {
                Log::error('Admin: Value Design Stores sync failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to sync Value Design Stores. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Value Design stores synced successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to sync Value Design Stores', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync Value Design Stores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Value Design Wallet Balance
     */
    public function getVDWalletBalance(Request $request)
    {
        try {
            Log::info('Admin: Getting Value Design Wallet Balance via admin panel');
            $exitCode = Artisan::call('get:vdWalletBalance');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false;
            
            if ($hasError) {
                Log::error('Admin: Value Design Wallet Balance fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get Value Design Wallet Balance. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Value Design wallet balance retrieved successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to get Value Design Wallet Balance', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get Value Design Wallet Balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch KGen Wallet Balance
     */
    public function fetchKGenWalletBalance(Request $request)
    {
        try {
            Log::info('Admin: Fetching KGen Wallet Balance via admin panel');
            $exitCode = Artisan::call('fetch:kgenWalletBalance');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false ||
                       stripos($output, 'credentials are not configured') !== false;
            
            if ($hasError) {
                Log::error('Admin: KGen Wallet Balance fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch KGen Wallet Balance. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'KGen wallet balance fetched and stored successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch KGen Wallet Balance', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch KGen Wallet Balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Lysto Gift Cards
     */
    public function fetchLystoGiftCards(Request $request)
    {
        try {
            Log::info('Admin: Fetching Lysto Gift Cards via admin panel');
            $exitCode = Artisan::call('fetch:lystoGiftCards');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false ||
                       stripos($output, 'credentials are not configured') !== false;
            
            if ($hasError) {
                Log::error('Admin: Lysto Gift Cards fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch Lysto Gift Cards. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Lysto gift cards fetched successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to fetch Lysto Gift Cards', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Lysto Gift Cards: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Lysto Wallet Balance
     */
    public function getLystoWalletBalance(Request $request)
    {
        try {
            Log::info('Admin: Getting Lysto Wallet Balance via admin panel');
            $exitCode = Artisan::call('get:lystoWalletBalance');
            
            $output = Artisan::output();
            
            // Check if command failed
            $hasError = $exitCode !== 0 || 
                       stripos($output, 'failed') !== false || 
                       stripos($output, 'error') !== false ||
                       stripos($output, 'Exception occurred') !== false ||
                       stripos($output, '❌') !== false ||
                       stripos($output, 'credentials are not configured') !== false;
            
            if ($hasError) {
                Log::error('Admin: Lysto Wallet Balance fetch failed', [
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get Lysto Wallet Balance. Please check the output for details.',
                    'output' => $output
                ], 500);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Lysto wallet balance retrieved successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Admin: Failed to get Lysto Wallet Balance', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get Lysto Wallet Balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export User Payment Details (Joined)
     */
    public function exportUserPaymentDetails(Request $request)
    {
        try {
            Log::info('Admin: Exporting User Payment Details (Joined) via admin panel');
            
            $export = new \App\Exports\UserPaymentDetailsExport();
            $fileName = 'user_payment_details_' . date('Y-m-d_His') . '.xlsx';
            
            return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
            
        } catch (\Exception $e) {
            Log::error('Admin: Failed to export User Payment Details', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export User Payment Details: ' . $e->getMessage()
            ], 500);
        }
    }
}
