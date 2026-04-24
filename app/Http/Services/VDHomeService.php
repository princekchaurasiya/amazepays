<?php

namespace App\Http\Services;

use App\Services\Voucher\Distributor\ValueDesign\ValueDesignApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VDHomeService
{
    protected $cacheDuration = 3600; // 1 hour cache
    public function __construct(private readonly ValueDesignApiClient $client) {}

    public function getVDToken()
    {
        try {
            return $this->client->generateToken()['token'];
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

            if (! $token) {
                Log::error('Failed to get VD token for home brands');

                return [];
            }

            $brandsArray = $this->client->getBrands($token);
            $formattedBrands = $this->formatBrandsForHome($brandsArray);
            Cache::put($cacheKey, $formattedBrands, $this->cacheDuration);

            return $formattedBrands;
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
            if (! empty($brand['Images']) && ($brand['StockAvailable'] ?? false)) {
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
        usort($formattedBrands, function ($a, $b) {
            return ($b['discount'] ?? 0) - ($a['discount'] ?? 0);
        });

        // Return only first 12 brands for home page
        return array_slice($formattedBrands, 0, 12);
    }
}
