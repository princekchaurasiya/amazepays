<?php

namespace App\Http\Controllers;

use App\Helpers\KgenPricingHelper;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Slide;
use App\Models\StorefrontBrand;
use App\Services\Storefront\SlidePresentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HomePageController extends Controller
{
    public function __construct(
        private SlidePresentationService $slidePresentation,
    ) {}

    /**
     * JSON list of all visible products (legacy storefront API).
     */
    public function viewAllProduct()
    {
        Log::info('viewAllProduct method called');
        $viewProds = Product::query()->forStorefrontCatalog()->get();
        Log::info('Fetched all visible products', ['count' => $viewProds->count()]);

        return $viewProds;
    }

    public function homePage(Request $request)
    {
        try {
            // Fetch categories and brands
            $categories = Category::orderBy('order')->get();
            $brands = StorefrontBrand::orderBy('order')->get();

            $allProducts = Product::query()
                ->forStorefrontCatalog()
                ->orderByRaw('IFNULL(hot_deal_rank, 999999) ASC')
                ->orderByRaw('IFNULL(display_order, 999999) ASC')
                ->get();

            $sections = HomepageSection::query()
                ->where('status', true)
                ->ordered()
                ->get();

            $hotDealsSection = $sections->firstWhere('section_type', 'hot_deals');
            $hotDealProductLimit = max(1, (int) data_get($hotDealsSection?->config, 'priority_product_count', 10));

            // Hot deals: non-null `hot_deal_rank` (ordering); capped by homepage section `priority_product_count`.
            $hotDealProducts = $allProducts->filter(function ($product) {
                return ! is_null($product->hot_deal_rank);
            })->take($hotDealProductLimit);

            // Other deals: `hot_deal_rank` null; list order uses `display_order` among this set.
            $otherDealProducts = $allProducts->filter(function ($product) {
                return is_null($product->hot_deal_rank);
            })->sortBy(function ($product) {
                return $product->display_order ?? 999999;
            });

            $slides = $this->slidePresentation->homepageSlides();
            $this->slidePresentation->attachSlugs($slides);

            // Return the view with the fetched data
            // Fetch KGen products asynchronously or with shorter timeout to avoid blocking
            $kgenProductsRaw = $this->fetchKgenProducts();
            $kgenProducts = array_map(
                static fn (array $product): array => KgenPricingHelper::enrichKgenProductForHomepage($product),
                $kgenProductsRaw
            );

            $brandMaxDiscounts = Product::query()
                ->forStorefrontCatalog()
                ->whereNotNull('brand_id')
                ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
                ->groupBy('brand_id')
                ->pluck('max_discount', 'brand_id');

            $kgenSection = $sections->firstWhere('section_type', 'kgen');
            $kgenSectionTitle = filled($kgenSection?->title) ? (string) $kgenSection->title : 'KGen Technology';
            $showKgenSection = $kgenSection !== null;

            $homeSettings = $this->buildLegacyHomeSettings($sections);
            $heroSection = HomepageSection::query()->where('section_name', 'hero')->first();

            $slidesPayload = $slides
                ->map(fn (Slide $slide) => $this->slidePresentation->toPublicArray($slide))
                ->values()
                ->all();

            return Inertia::render('Storefront/Home', [
                'slides' => $slidesPayload,
                'sections' => $sections,
                'allProducts' => $allProducts,
                'categories' => $categories,
                'brands' => $brands,
                'hotDealProducts' => $hotDealProducts,
                'otherDealProducts' => $otherDealProducts,
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

            return response('Something went wrong loading the homepage.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
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
