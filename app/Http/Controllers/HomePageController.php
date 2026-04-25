<?php

namespace App\Http\Controllers;

use App\Domains\Homepage\Services\HomepageQueryService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class HomePageController extends Controller
{
    public function __construct(
        private HomepageQueryService $homepageQuery,
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
            $tenantId = $this->resolveTenantId($request);

            // Fetch categories and brands
            $categories = Category::orderBy('display_order')->get();
            $brands = Brand::orderBy('display_order')->get();

            $productsQuery = Product::query()->forStorefrontCatalog();

            // Phase-3 schema: no hot_deal_rank. Prefer featured products, then display order.
            if (Schema::hasColumn('products', 'is_featured')) {
                $productsQuery->orderByDesc('is_featured');
            }
            if (Schema::hasColumn('products', 'display_order')) {
                $productsQuery->orderBy('display_order');
            }
            $productsQuery->orderByDesc('id');

            $allProducts = $productsQuery->get();

            $document = $this->homepageQuery->homepageDocument($tenantId, 'web', 'storefront_home');

            $hotDealProductLimit = 10;

            // Hot deals (Phase-3): featured products.
            $hotDealProducts = $allProducts
                ->filter(fn ($product) => (bool) ($product->is_featured ?? false))
                ->take($hotDealProductLimit);

            // Other deals: everything else (already ordered by display_order / id).
            $otherDealProducts = $allProducts->filter(fn ($product) => ! (bool) ($product->is_featured ?? false));

            $slidesPayload = collect($document['hero_banners'] ?? [])
                ->map(function (array $item, int $idx): array {
                    return [
                        'id' => $idx + 1,
                        'desktop_image' => $item['web_image_url'] ?? $item['image_url'] ?? null,
                        'image_mobile' => $item['mobile_image_url'] ?? null,
                        'slug' => null,
                        'product_id' => null,
                        'category_id' => null,
                        'brand_id' => null,
                        'is_linked' => filled($item['redirect_url'] ?? null) || filled($item['deeplink'] ?? null),
                        'img_alt_tag' => null,
                        'cta_link' => $item['redirect_url'] ?? null,
                        'custom_url' => $item['redirect_url'] ?? null,
                        'link_type' => $item['cta_type'] ?? null,
                    ];
                })
                ->values()
                ->all();

            $kgenProducts = [];

            $brandMaxDiscounts = Product::query()
                ->forStorefrontCatalog()
                ->whereNotNull('brand_id')
                ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
                ->groupBy('brand_id')
                ->pluck('max_discount', 'brand_id');

            $homeSettings = (object) [
                'section_banner_status' => ! empty($slidesPayload),
                'section_brand_status' => $brands->isNotEmpty(),
                'section_hot_deal_status' => $hotDealProducts->isNotEmpty(),
                'section_category_status' => $categories->isNotEmpty(),
                'section_other_deal_status' => $otherDealProducts->isNotEmpty(),
                'section_brand_title' => 'Popular Brands',
                'section_hot_deal_title' => 'Hot Deals',
                'section_category_title' => 'Categories',
                'section_other_deal_title' => 'Other Deals',
            ];

            $kgenSectionTitle = 'KGen Technology';
            $showKgenSection = false;

            return Inertia::render('Storefront/Home', [
                'slides' => $slidesPayload,
                'categories' => $categories,
                'brands' => $brands->map(fn (Brand $b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'logo' => $b->logo_url,
                ])->values()->all(),
                'hotDealProducts' => $hotDealProducts,
                'otherDealProducts' => $otherDealProducts,
                'kgenProducts' => $kgenProducts,
                'brandMaxDiscounts' => $brandMaxDiscounts,
                'kgenSectionTitle' => $kgenSectionTitle,
                'showKgenSection' => $showKgenSection,
                'homeSettings' => $homeSettings,
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
    private function resolveTenantId(Request $request): int
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        $id = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        if (is_int($id) && $id > 0) {
            return $id;
        }

        return 1;
    }

}
