<?php

namespace App\Http\Controllers;

use App\Models\Slide; // Import the Slide model
use App\Models\QsProduct; // Import the QsProduct model
use App\Models\Home; // Import the Home model
use App\Models\AmazepayCategory;
use App\Models\AmazepayBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    public function homePage()
    {
        try {
            // Fetch categories and brands
            $categories = AmazepayCategory::orderBy('order')->get();
            $brands = AmazepayBrand::orderBy('order')->get();

            // Fetch all products and decode necessary fields
            $allProducts = QsProduct::orderByRaw('IFNULL(priority, 999999) ASC') // Sort by priority first (NULL treated as highest)
                ->orderByRaw('IFNULL(secondary_priority, 999999) ASC') // Then by secondary_priority (NULL treated as highest as well)
                ->get();

            // No need to manually decode - model accessors handle it automatically
            // price, currency, and images are automatically decoded by QsProduct model accessors

            // Fetch home settings including priority_product_to_show
            $homeSettings = Home::first();
            
            // If no home settings exist, create default settings or use defaults
            if (!$homeSettings) {
                $homeSettings = new Home();
                $homeSettings->section_banner_status = false;
                $homeSettings->section_brand_status = false;
                $homeSettings->section_hot_deal_status = false;
                $homeSettings->section_category_status = false;
                $homeSettings->section_other_deal_status = false;
                $homeSettings->section_brand_title = 'Popular Brands';
                $homeSettings->section_hot_deal_title = 'Hot Deals';
                $homeSettings->section_category_title = 'Categories';
                $homeSettings->section_other_deal_title = 'Other Deals';
                $homeSettings->priority_product_to_show = 10;
            }
            
            $priorityProductLimit = $homeSettings->priority_product_to_show ?? 10; // Default to 10 if not set

            // Split the products into two groups: priority and no priority
            $priorityProducts = $allProducts->filter(function ($product) {
                return !is_null($product->priority); // Products with priority
            })->take($priorityProductLimit); // Limit the number of priority products

            // Include secondary_priority sorting for noPriorityProducts
            $noPriorityProducts = $allProducts->filter(function ($product) {
                return is_null($product->priority); // Products with no priority
            })->sortBy(function ($product) {
                // Sort by secondary_priority where NULL is treated as the highest (999999)
                return $product->secondary_priority ?? 999999;
            }); // Sort based on the new secondary priority

            // Fetch all slides
            $slides = Slide::where('status', 1) // Ensure the status is active
                ->where('display_on_page', 'homepage') // Filter based on display location
                ->orderBy('priority', 'asc') // Sort by priority
                ->orderByRaw('priority IS NULL ASC') // Ensure null priorities are last
                ->get();

            // Retrieve slugs for slides (optimized to avoid N+1 queries)
            $this->loadSlugsForSlides($slides);

            // Return the view with the fetched data
            // Fetch KGen products asynchronously or with shorter timeout to avoid blocking
            $kgenProducts = $this->fetchKgenProducts();

            return view('layouts/homepage.index', compact(
                'slides',
                'homeSettings',
                'allProducts',
                'categories',
                'brands',
                'priorityProducts',
                'noPriorityProducts',
                'kgenProducts'
            )); // Pass data to the view
        } catch (\Exception $e) {
            Log::error('Error fetching data in homePage method', ['error' => $e->getMessage()]);
            // Handle the exception as needed, e.g., return an error view or message
            return response()->view('errors.500', [], 500);
        }
    }

    // Optimized method to load slugs for all slides at once (prevents N+1 queries)
    private function loadSlugsForSlides($slides)
    {
        // Collect all IDs
        $productIds = [];
        $categoryIds = [];
        $brandIds = [];
        
        foreach ($slides as $slide) {
            if ($slide->product_id) {
                $productIds[] = $slide->product_id;
            }
            if ($slide->category_id) {
                $categoryIds[] = $slide->category_id;
            }
            if ($slide->brand_id) {
                $brandIds[] = $slide->brand_id;
            }
        }
        
        // Fetch all at once
        $products = !empty($productIds) ? QsProduct::whereIn('id', array_unique($productIds))->pluck('slug', 'id')->toArray() : [];
        $categories = !empty($categoryIds) ? AmazepayCategory::whereIn('id', array_unique($categoryIds))->pluck('slug', 'id')->toArray() : [];
        $brands = !empty($brandIds) ? AmazepayBrand::whereIn('id', array_unique($brandIds))->pluck('slug', 'id')->toArray() : [];
        
        // Assign slugs
        foreach ($slides as $slide) {
            if ($slide->product_id && isset($products[$slide->product_id])) {
                $slide->slug = $products[$slide->product_id];
            } elseif ($slide->category_id && isset($categories[$slide->category_id])) {
                $slide->slug = $categories[$slide->category_id];
            } elseif ($slide->brand_id && isset($brands[$slide->brand_id])) {
                $slide->slug = $brands[$slide->brand_id];
            } else {
                $slide->slug = null;
            }
        }
    }

    /**
     * Fetch a curated list of KGen products for the homepage card view.
     */
    private function fetchKgenProducts(int $limit = 8): array
    {
        try {
            $baseUrl = env('EXLR8_BASE_URL');
            $partnerId = env('dpID');
            $clientId = env('EXLR8_USER_ID');
            $clientSecret = env('EXLR8_USER_SECRET');

            if (!$baseUrl || !$partnerId || !$clientId || !$clientSecret) {
                // KGen credentials not configured - silently return empty array
                return [];
            }

            $response = Http::timeout(3) // Reduced timeout to prevent long waits
                ->withHeaders([
                    'x-client-id' => $clientId,
                    'x-client-secret' => $clientSecret,
                ])
                ->get(rtrim($baseUrl, '/') . '/products/delivery-partners/' . $partnerId);

            if (!$response->successful()) {
                Log::error('Failed to fetch KGen products for homepage', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $products = collect($response->json('products', []));
            if ($products->isEmpty()) {
                return [];
            }

            $discountMap = QsProduct::whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0)
                ->pluck('discount_percentage', 'name')
                ->mapWithKeys(function ($discount, $name) {
                    $normalized = strtolower(trim((string) $name));
                    return $normalized !== '' ? [$normalized => (float) $discount] : [];
                });

            return $products
                ->map(function ($product) use ($discountMap) {
                    $nameCandidates = [
                        strtolower(trim((string) ($product['productDisplayName'] ?? ''))),
                        strtolower(trim((string) ($product['productName'] ?? ''))),
                    ];

                    $discount = 0;
                    foreach ($nameCandidates as $candidate) {
                        if ($candidate !== '' && $discountMap->has($candidate)) {
                            $discount = $discountMap->get($candidate);
                            break;
                        }
                    }

                    $product['discount_percentage'] = $discount;

                    return $product;
                })
                ->take($limit)
                ->values()
                ->all();
        } catch (\Throwable $th) {
            Log::error('Unexpected error fetching KGen products', [
                'error' => $th->getMessage(),
            ]);
            return [];
        }
    }
}
