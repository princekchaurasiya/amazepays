<?php

namespace App\Http\Controllers;

use App\Domains\Homepage\Services\HomepageQueryService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomePageController extends Controller
{
    public function __construct(
        private HomepageQueryService $homepageQuery,
    ) {}

    public function homePage(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);

        // Fetch categories and brands
        $allCategories = Category::orderBy('display_order')->get();
        $categories = $allCategories
            ->map(function (Category $c) {
                $palette = [
                    '#0ea5e9', // sky
                    '#22c55e', // green
                    '#f97316', // orange
                    '#ef4444', // red
                    '#a855f7', // purple
                    '#14b8a6', // teal
                    '#3b82f6', // blue
                    '#ec4899', // pink
                    '#84cc16', // lime
                    '#f59e0b', // amber
                ];

                $hash = crc32(mb_strtolower((string) $c->name));
                $idx = (int) ($hash % count($palette));

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug ?: Str::slug((string) $c->name),
                    // legacy field used by Home.tsx
                    'thumbnail' => $c->icon_url,
                    // if DB column exists (legacy), Eloquent will have it; else computed
                    'accent_color' => $c->getAttribute('accent_color') ?: $palette[$idx],
                ];
            })
            ->values();
        $allBrands = Brand::orderBy('display_order')->get();

        $document = $this->homepageQuery->homepageDocument($tenantId, 'web', 'storefront_home');
        $sections = collect($document['sections'] ?? [])->values();

        // Option B: any homepage section items with product_id drive product grids.
        $allItemRefs = $sections
            ->flatMap(fn ($s) => is_array($s) ? (array) ($s['items'] ?? []) : [])
            ->values();

        $productItemRefs = $allItemRefs->filter(fn ($it) => is_array($it) && (int) ($it['product_id'] ?? 0) > 0)->values();
        $brandItemRefs = $allItemRefs->filter(fn ($it) => is_array($it) && (int) ($it['brand_id'] ?? 0) > 0)->values();
        $categoryItemRefs = $allItemRefs->filter(fn ($it) => is_array($it) && (int) ($it['category_id'] ?? 0) > 0)->values();

        $productIdsInOrder = $productItemRefs->pluck('product_id')->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values()->all();
        $brandIdsInOrder = $brandItemRefs->pluck('brand_id')->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values()->all();
        $categoryIdsInOrder = $categoryItemRefs->pluck('category_id')->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values()->all();

        // Load products once; do not fallback to catalog query if homepage has none.
        $productsById = collect();
        if ($productIdsInOrder !== []) {
            $productsById = Product::query()
                ->forStorefrontCatalog()
                ->whereIn('id', array_values(array_unique($productIdsInOrder)))
                ->get()
                ->keyBy('id');
        }

        $brandsById = collect();
        if ($brandIdsInOrder !== []) {
            $brandsById = $allBrands
                ->whereIn('id', array_values(array_unique($brandIdsInOrder)))
                ->keyBy('id');
        }

        $categoriesById = collect();
        if ($categoryIdsInOrder !== []) {
            $categoriesById = $allCategories
                ->whereIn('id', array_values(array_unique($categoryIdsInOrder)))
                ->keyBy('id');
        }

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

        $brandMaxDiscounts = collect();
        if (Schema::hasColumn('products', 'discount_percentage')) {
            $brandMaxDiscounts = Product::query()
                ->forStorefrontCatalog()
                ->whereNotNull('brand_id')
                ->selectRaw('brand_id, MAX(COALESCE(discount_percentage, 0)) as max_discount')
                ->groupBy('brand_id')
                ->pluck('max_discount', 'brand_id');
        }

        $homeSettings = (object) [
            'section_banner_status' => ! empty($slidesPayload),
            'section_brand_status' => $brandItemRefs->isNotEmpty(),
            'section_category_status' => $categoryItemRefs->isNotEmpty(),
            'section_other_deal_status' => $productItemRefs->isNotEmpty(),
        ];

        $kgenSectionTitle = 'KGen Technology';
        $showKgenSection = false;

        return Inertia::render('Storefront/Home', [
            'slides' => $slidesPayload,
            // Brand & category grids are driven by homepage document sections/items (Option B).
            'categories' => [],
            'brands' => [],
            // Option B: product grids driven by homepage document sections/items.
            'sections' => $sections->values()->all(),
            'sectionProductsById' => $productsById->map(function (Product $p) {
                return [
                    'id' => (int) $p->id,
                    'slug' => $p->slug,
                    'url' => $p->getAttribute('url') ?? $p->slug,
                    'name' => $p->name,
                    'display_name' => $p->display_name,
                    // Uses ProductImageHelper → custom upload overrides provider images automatically.
                    'display_image_url' => $p->display_image_url,
                    'discount_percentage' => $p->getAttribute('discount_percentage'),
                    'out_of_stock' => $p->getAttribute('out_of_stock'),
                ];
            })->all(),
            'sectionBrandsById' => $brandsById->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'logo' => $b->logo_url,
            ])->all(),
            'sectionCategoriesById' => $categoriesById
                ->map(function (Category $c) {
                    $palette = [
                        '#0ea5e9', // sky
                        '#22c55e', // green
                        '#f97316', // orange
                        '#ef4444', // red
                        '#a855f7', // purple
                        '#14b8a6', // teal
                        '#3b82f6', // blue
                        '#ec4899', // pink
                        '#84cc16', // lime
                        '#f59e0b', // amber
                    ];

                    $hash = crc32(mb_strtolower((string) $c->name));
                    $idx = (int) ($hash % count($palette));

                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug ?: Str::slug((string) $c->name),
                        'thumbnail' => $c->icon_url,
                        'accent_color' => $c->getAttribute('accent_color') ?: $palette[$idx],
                    ];
                })
                ->all(),
            'kgenProducts' => $kgenProducts,
            'brandMaxDiscounts' => $brandMaxDiscounts,
            'kgenSectionTitle' => $kgenSectionTitle,
            'showKgenSection' => $showKgenSection,
            'homeSettings' => $homeSettings,
        ]);
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
