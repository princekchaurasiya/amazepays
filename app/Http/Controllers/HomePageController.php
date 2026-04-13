<?php

namespace App\Http\Controllers;

use App\Helpers\KgenPricingHelper;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Slide;
use App\Models\StorefrontBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomePageController extends Controller
{
    /**
     * JSON list of all visible products (legacy storefront API).
     */
    public function viewAllProduct()
    {
        Log::info('viewAllProduct method called');
        $viewProds = Product::where('show_product', true)->get();
        Log::info('Fetched all visible products', ['count' => $viewProds->count()]);

        return $viewProds;
    }

    public function homePage(Request $request)
    {
        try {
            // Fetch categories and brands
            $categories = Category::orderBy('order')->get();
            $brands = StorefrontBrand::orderBy('order')->get();

            $allProducts = Product::where('show_product', true)
                ->orderByRaw('IFNULL(priority, 999999) ASC')
                ->orderByRaw('IFNULL(display_order, 999999) ASC')
                ->get();

            $sections = HomepageSection::query()
                ->where('status', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $hotDealsSection = $sections->firstWhere('section_type', 'hot_deals');
            $priorityProductLimit = max(1, (int) data_get($hotDealsSection?->config, 'priority_product_count', 10));

            // Split the products into two groups: priority and no priority
            $priorityProducts = $allProducts->filter(function ($product) {
                return ! is_null($product->priority); // Products with priority
            })->take($priorityProductLimit); // Limit the number of priority products

            // Include display_order sorting for noPriorityProducts
            $noPriorityProducts = $allProducts->filter(function ($product) {
                return is_null($product->priority); // Products with no priority
            })->sortBy(function ($product) {
                return $product->display_order ?? 999999;
            });

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
            $kgenProductsRaw = $this->fetchKgenProducts();
            $kgenProducts = array_map(
                static fn (array $product): array => KgenPricingHelper::enrichKgenProductForHomepage($product),
                $kgenProductsRaw
            );

            $brandMaxDiscounts = Product::query()
                ->where('show_product', true)
                ->whereNotNull('brand_id')
                ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
                ->groupBy('brand_id')
                ->pluck('max_discount', 'brand_id');

            $kgenSection = $sections->firstWhere('section_type', 'kgen');
            $kgenSectionTitle = filled($kgenSection?->title) ? (string) $kgenSection->title : 'KGen Technology';
            $showKgenSection = $kgenSection !== null;

            $homeSettings = $this->buildLegacyHomeSettings($sections);
            $heroSection = HomepageSection::query()->where('section_name', 'hero')->first();

            $slidesPayload = $slides->map(function (Slide $slide) {
                return [
                    'id' => $slide->id,
                    'desktop_image' => $slide->desktop_image ? Storage::url($slide->desktop_image) : null,
                    'image_mobile' => $slide->image_mobile ? Storage::url($slide->image_mobile) : null,
                    'slug' => $slide->slug,
                    'product_id' => $slide->product_id,
                    'category_id' => $slide->category_id,
                    'brand_id' => $slide->brand_id,
                    'is_linked' => (bool) $slide->is_linked,
                    'img_alt_tag' => $slide->img_alt_tag,
                ];
            })->values()->all();

            return Inertia::render('Storefront/Home', [
                'slides' => $slidesPayload,
                'sections' => $sections,
                'allProducts' => $allProducts,
                'categories' => $categories,
                'brands' => $brands,
                'priorityProducts' => $priorityProducts,
                'noPriorityProducts' => $noPriorityProducts,
                'kgenProducts' => $kgenProducts,
                'brandMaxDiscounts' => $brandMaxDiscounts,
                'kgenSectionTitle' => $kgenSectionTitle,
                'showKgenSection' => $showKgenSection,
                'homeSettings' => $homeSettings,
                'heroSection' => $heroSection,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching data in homePage method', ['error' => $e->getMessage()]);

            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 500,
                    'message' => 'Something went wrong loading the homepage.',
                ])->toResponse($request)->setStatusCode(500);
            }

            return response()->view('errors.500', [], 500);
        }
    }

    /**
     * Blade still expects the old settings object; derive flags/titles from active homepage sections.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, HomepageSection>  $sections
     */
    private function buildLegacyHomeSettings(Collection $sections): object
    {
        $has = fn (string $type): bool => $sections->contains(fn (HomepageSection $s) => $s->section_type === $type);
        $title = function (string $type, string $default) use ($sections): string {
            $row = $sections->firstWhere('section_type', $type);

            return filled($row?->title) ? (string) $row->title : $default;
        };

        return (object) [
            'section_banner_status' => $has('banner'),
            'section_brand_status' => $has('brands'),
            'section_hot_deal_status' => $has('hot_deals'),
            'section_category_status' => $has('categories'),
            'section_other_deal_status' => $has('other_deals'),
            'section_brand_title' => $title('brands', 'Popular Brands'),
            'section_hot_deal_title' => $title('hot_deals', 'Hot Deals'),
            'section_category_title' => $title('categories', 'Categories'),
            'section_other_deal_title' => $title('other_deals', 'Other Deals'),
        ];
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
        $products = ! empty($productIds) ? Product::whereIn('id', array_unique($productIds))->pluck('slug', 'id')->toArray() : [];
        $categories = ! empty($categoryIds) ? Category::whereIn('id', array_unique($categoryIds))->pluck('slug', 'id')->toArray() : [];
        $brands = ! empty($brandIds) ? StorefrontBrand::whereIn('id', array_unique($brandIds))->pluck('slug', 'id')->toArray() : [];

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

            if (! $baseUrl || ! $partnerId || ! $clientId || ! $clientSecret) {
                // KGen credentials not configured - silently return empty array
                return [];
            }

            $response = Http::timeout(3) // Reduced timeout to prevent long waits
                ->withHeaders([
                    'x-client-id' => $clientId,
                    'x-client-secret' => $clientSecret,
                ])
                ->get(rtrim($baseUrl, '/').'/products/delivery-partners/'.$partnerId);

            if (! $response->successful()) {
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

            $discountMap = Product::whereNotNull('discount_percentage')
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
