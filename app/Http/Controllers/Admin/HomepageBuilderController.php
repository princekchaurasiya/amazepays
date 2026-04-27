<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Content\Models\ContentSection;
use App\Domains\Homepage\Cache\HomepageCacheInvalidator;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HomepageBuilderController extends Controller
{
    public function __construct(
        private readonly HomepageCacheInvalidator $homepageCacheInvalidator,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('settings.view');

        $tenantId = $this->resolveTenantId($request);

        $sectionsQuery = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sectionIds = $sectionsQuery->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
        $countsBySectionId = collect();
        if ($sectionIds !== []) {
            $countsBySectionId = DB::table('content_section_items')
                ->selectRaw(
                    'content_section_id, COUNT(*) as items_count, SUM(CASE WHEN product_id IS NULL THEN 0 ELSE 1 END) as products_count'
                )
                ->where('tenant_id', $tenantId)
                ->whereIn('content_section_id', $sectionIds)
                ->whereNull('deleted_at')
                ->groupBy('content_section_id')
                ->get()
                ->keyBy('content_section_id');
        }

        $sections = $sectionsQuery
            ->map(function (ContentSection $s) use ($countsBySectionId) {
                $counts = $countsBySectionId->get($s->id);

                return [
                    'id' => $s->id,
                    'slug' => $s->slug,
                    'type' => $s->type,
                    'title' => $s->title,
                    'is_enabled' => (bool) $s->is_enabled,
                    'sort_order' => (int) $s->sort_order,
                    'platform' => $s->platform,
                    'start_at' => optional($s->start_at)->toISOString(),
                    'end_at' => optional($s->end_at)->toISOString(),
                    'priority' => (int) $s->priority,
                    'metadata' => $s->metadata ?? [],
                    'items_count' => (int) ($counts?->items_count ?? 0),
                    'products_count' => (int) ($counts?->products_count ?? 0),
                ];
            })
            ->values()
            ->all();

        $types = [
            'banner_single',
            'banner_dual',
            'banner_square',
            'carousel',
            'voucher_slider',
            'grid_2',
            'grid_3',
            'featured_products',
            'custom',
        ];

        $products = Product::query()
            ->forStorefrontCatalog()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->limit(800)
            ->get()
            ->map(fn (Product $p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'sku' => (string) $p->sku,
            ])
            ->values()
            ->all();

        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(800)
            ->get()
            ->map(fn (Brand $b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
            ->values()
            ->all();

        $categories = Category::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(800)
            ->get()
            ->map(fn (Category $c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
            ->values()
            ->all();

        return Inertia::render('Admin/HomepageBuilder/Index', [
            'sections' => $sections,
            'sectionTypes' => array_map(fn (string $t) => ['value' => $t, 'label' => str_replace('_', ' ', ucwords($t, '_'))], $types),
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9_-]+$/', Rule::unique('content_sections', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('surface', 'storefront_home'))],
            'type' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'platform' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'metadata' => ['nullable', 'array'],
        ]);

        $maxSort = (int) ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->max('sort_order');

        ContentSection::create([
            'tenant_id' => $tenantId,
            'surface' => 'storefront_home',
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'status' => 'active',
            'is_enabled' => $validated['is_enabled'] ?? true,
            'sort_order' => $validated['sort_order'] ?? ($maxSort + 1),
            'platform' => $validated['platform'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'title' => $validated['title'] ?? null,
            'subtitle' => null,
            'background_color' => null,
            'text_color' => null,
            'metadata' => $validated['metadata'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Section created.');
    }

    public function update(Request $request, ContentSection $section): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'platform' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'metadata' => ['nullable', 'array'],
        ]);

        $section->update([
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? $section->is_enabled,
            'sort_order' => $validated['sort_order'] ?? $section->sort_order,
            'platform' => $validated['platform'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Request $request, ContentSection $section): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('content_sections', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('surface', 'storefront_home'))],
        ]);

        foreach ($validated['ids'] as $index => $id) {
            ContentSection::where('tenant_id', $tenantId)->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Order saved.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $ids = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->orderByDesc('priority')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $last = (int) DB::table('homepage_layout_versions')->where('tenant_id', $tenantId)->max('version');
        $version = $last > 0 ? $last + 1 : 1;

        DB::table('homepage_layout_versions')->insert([
            'tenant_id' => $tenantId,
            'version' => $version,
            'published_at' => now(),
            'layout_snapshot' => json_encode(['surface' => 'storefront_home', 'section_ids' => $ids], JSON_THROW_ON_ERROR),
            'experiment_key' => null,
            'created_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->homepageCacheInvalidator->invalidate($tenantId);

        return back()->with('success', "Homepage layout published (v{$version}).");
    }

    public function bulkAddWoohooProducts(Request $request, ContentSection $section): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $products = Product::query()
            ->forStorefrontCatalog()
            ->where('tenant_id', $tenantId)
            ->where('source_provider', 'woohoo')
            ->orderBy('id')
            ->get(['id']);

        DB::transaction(function () use ($tenantId, $section, $products) {
            // Replace existing product links for this section.
            DB::table('content_section_items')
                ->where('tenant_id', $tenantId)
                ->where('content_section_id', $section->id)
                ->whereNotNull('product_id')
                ->delete();

            $now = now();
            $rows = [];
            $order = 1;
            foreach ($products as $p) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'content_section_id' => $section->id,
                    'sort_order' => $order++,
                    'is_enabled' => true,
                    'start_at' => null,
                    'end_at' => null,
                    'priority' => 0,
                    'title' => null,
                    'subtitle' => null,
                    'web_media_asset_id' => null,
                    'mobile_media_asset_id' => null,
                    'cta_text' => null,
                    'cta_type' => 'product',
                    'cta_value' => null,
                    'deeplink' => null,
                    'redirect_url' => null,
                    'product_id' => (int) $p->id,
                    'category_id' => null,
                    'brand_id' => null,
                    'metadata' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            if ($rows !== []) {
                DB::table('content_section_items')->insert($rows);
            }
        });

        return back()->with('success', "Added {$products->count()} Woohoo products to {$section->slug}.");
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

        if (! Schema::hasTable('tenants')) {
            return 1;
        }

        return (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
    }
}

