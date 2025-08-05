<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VDHomeService
{
    protected $baseUrl = 'http://cards.vdwebapi.com/distributor/';
    protected $cacheDuration = 3600; // 1 hour cache

    public function getVDToken()
    {
        $url = env('TOKEN_API_URL');
        $distributorId = env('DISTRIBUTOR_ID');

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'username' => env('API_USERNAME'),
                    'password' => env('API_PASSWORD'),
                ])->post($url, [
                    'distributor_id' => $distributorId,
                ]);

            if ($response->successful()) {
                $encryptedToken = $response->json('token');
                return $this->decryptAES($encryptedToken);
            }
        } catch (\Exception $e) {
            Log::error('VD Token generation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function getVDBrandsForHome()
    {
        // Try to get from cache first
        $cacheKey = 'vd_brands_home';
        $cachedBrands = Cache::get($cacheKey);
        
        if ($cachedBrands) {
            return $cachedBrands;
        }

        try {
            $token = $this->getVDToken();
            
            if (!$token) {
                Log::error('Failed to get VD token for home brands');
                return [];
            }

            $response = Http::withHeaders([
                'token' => $token,
            ])->post($this->baseUrl . 'api-getbrand/', [
                'BrandCode' => '', // Get all brands
            ]);

            if ($response->successful()) {
                $brands = $response->json();
                $encryptedBrandData = $brands['data'] ?? null;

                if ($encryptedBrandData) {
                    $decryptedBrandData = $this->decryptAES($encryptedBrandData);
                    $brandsArray = json_decode($decryptedBrandData, true);

                    if (is_array($brandsArray)) {
                        // Filter and format brands for home page
                        $formattedBrands = $this->formatBrandsForHome($brandsArray);
                        
                        // Cache the results
                        Cache::put($cacheKey, $formattedBrands, $this->cacheDuration);
                        
                        return $formattedBrands;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch VD brands for home', ['error' => $e->getMessage()]);
        }

        return [];
    }

    protected function formatBrandsForHome($brandsArray)
    {
        $formattedBrands = [];
        
        foreach ($brandsArray as $brand) {
            // Only include brands with images and stock available
            if (!empty($brand['Images']) && ($brand['StockAvailable'] ?? false)) {
                $images = json_decode(str_replace("'", '"', $brand['Images']), true);
                
                $formattedBrands[] = [
                    'brand_code' => $brand['BrandCode'] ?? '',
                    'brand_name' => $brand['BrandName'] ?? '',
                    'category' => $brand['Category'] ?? '',
                    'discount' => $brand['Discount'] ?? 0,
                    'min_price' => $brand['minPrice'] ?? 0,
                    'max_price' => $brand['maxPrice'] ?? 0,
                    'featured_image' => $images['featured'] ?? null,
                    'thumbnail_image' => $images['thumbnail'] ?? null,
                    'stock_available' => $brand['StockAvailable'] ?? false,
                    'denomination_list' => $brand['DenominationList'] ?? '',
                ];
            }
        }

        // Sort by discount percentage (highest first)
        usort($formattedBrands, function($a, $b) {
            return ($b['discount'] ?? 0) - ($a['discount'] ?? 0);
        });

        // Return only first 12 brands for home page
        return array_slice($formattedBrands, 0, 12);
    }

    protected function decryptAES($encryptedBase64)
    {
        $key = env('AES_SECRET_KEY');
        $iv = env('AES_IV');

        $ciphertext = base64_decode($encryptedBase64, true);
        if ($ciphertext === false) {
            return 'Base64 decode failed';
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted ?: 'Decryption failed';
    }
} 