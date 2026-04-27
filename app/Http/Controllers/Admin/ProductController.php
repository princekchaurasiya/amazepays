<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkUpdateProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductContentRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\Catalog\ProductContentService;
use App\Services\Catalog\ProductMediaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const CATALOG_SCOPE_STOREFRONT = 'storefront';

    private const CATALOG_SCOPE_BUSINESS = 'business';

    private const CATALOG_SCOPE_ALL = 'all';

    public function __construct(
        private ProductMediaService $productMedia,
        private ProductContentService $productContent,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $this->authorize('products.view');
        $scope = $this->resolveCatalogScope($request);
        $this->authorizeCatalogScopeForList($scope);

        if (! $request->filled('catalog_scope')) {
            return redirect()->route('panel.products.index', array_merge(
                $request->query(),
                ['catalog_scope' => $scope]
            ));
        }

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $catalogAudienceFilter = null;
        if ($request->filled('catalog_audience')) {
            $aud = (string) $request->input('catalog_audience');
            if (in_array($aud, [Product::CATALOG_AUDIENCE_B2B, Product::CATALOG_AUDIENCE_B2C, Product::CATALOG_AUDIENCE_BOTH], true)) {
                $catalogAudienceFilter = $aud;
            }
        }

        $query = Product::with(['brand', 'productMedia']);

        match ($scope) {
            self::CATALOG_SCOPE_STOREFRONT => $query->adminStorefrontCatalog(),
            self::CATALOG_SCOPE_BUSINESS => $query->adminBusinessCatalog(),
            default => null,
        };

        if ($catalogAudienceFilter !== null) {
            $query->where('catalog_audience', $catalogAudienceFilter);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('source_provider')) {
            $query->where('source_provider', $request->source_provider);
        }

        if ($request->filled('status')) {
            $query->where('show_product', $request->status === 'visible');
        }

        $sourceProviderOptions = $this->sourceProviderOptionsForScope($scope);

        $paginator = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        $items = collect($paginator->items())->map(function (Product $p) {
            $thumb = $this->productContent->resolvePrimaryImageUrl($p);

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'product_name' => $p->display_name,
                'source_provider' => $p->source_provider,
                'catalog_audience' => $p->catalog_audience,
                'show_product' => (bool) $p->show_product,
                'selling_price' => $p->selling_price,
                'price_display' => $this->resolvePriceDisplayForList($p),
                'thumbnail' => $thumb,
                'has_custom_content' => $p->hasCustomContent(),
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return Inertia::render('Admin/Products/Index', [
            'products' => [
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => array_merge($request->only(['search', 'source_provider', 'status']), [
                'catalog_scope' => $scope,
                'catalog_audience' => $catalogAudienceFilter,
                'per_page' => $perPage,
            ]),
            'source_provider_options' => $sourceProviderOptions,
            'canViewAllCatalog' => Gate::allows('products.catalog.all'),
            'canViewStorefrontCatalog' => Gate::allows('products.catalog.storefront') || Gate::allows('products.catalog.all'),
            'canViewBusinessCatalog' => Gate::allows('products.catalog.business') || Gate::allows('products.catalog.all'),
            'canPublish' => Gate::allows('products.publish'),
            'canUpdate' => Gate::allows('products.update'),
        ]);
    }

    public function bulkUpdate(BulkUpdateProductsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $scope = $validated['catalog_scope'];
        $this->authorizeCatalogScopeForList($scope);

        $hasVisibility = $request->boolean('apply_visibility');
        $hasPrice = filled($validated['price_mode'] ?? null);
        $hasContent = $request->boolean('apply_custom_description')
            || $request->boolean('apply_how_to_redeem')
            || $request->boolean('apply_terms_and_conditions');

        if ($hasVisibility) {
            $this->authorize('products.publish');
        }
        if ($hasPrice || $hasContent) {
            $this->authorize('products.update');
        }

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $query = Product::query()->whereIn('id', $ids);
        match ($scope) {
            self::CATALOG_SCOPE_STOREFRONT => $query->adminStorefrontCatalog(),
            self::CATALOG_SCOPE_BUSINESS => $query->adminBusinessCatalog(),
            default => null,
        };

        if ($query->count() !== count($ids)) {
            abort(422, 'Some selected products are not in this catalog.');
        }

        $visibilityChanges = 0;
        $fullUpdates = 0;
        $skippedRelativeNoSellingPrice = 0;

        DB::transaction(function () use (
            $request,
            $query,
            $validated,
            $hasVisibility,
            $hasPrice,
            $hasContent,
            &$visibilityChanges,
            &$fullUpdates,
            &$skippedRelativeNoSellingPrice,
        ): void {
            /** @var Collection<int, Product> $products */
            $products = (clone $query)->get();

            foreach ($products as $product) {
                $updates = [];

                if ($hasVisibility) {
                    $target = (bool) $validated['show_product'];
                    if ((bool) $product->show_product !== $target) {
                        $updates['show_product'] = $target;
                    }
                }

                if ($hasPrice) {
                    $mode = $validated['price_mode'];
                    if ($mode === 'absolute') {
                        if (array_key_exists('selling_price', $validated) && $validated['selling_price'] !== null) {
                            $updates['selling_price'] = $validated['selling_price'];
                        }
                        if (array_key_exists('mrp', $validated) && $validated['mrp'] !== null) {
                            $updates['mrp'] = $validated['mrp'];
                        }
                        if (array_key_exists('discount_percentage', $validated) && $validated['discount_percentage'] !== null) {
                            $updates['discount_percentage'] = $validated['discount_percentage'];
                        }
                    } elseif ($mode === 'relative_percent') {
                        $base = $product->getRawOriginal('selling_price') ?? $product->selling_price;
                        if ($base === null || $base === '') {
                            $skippedRelativeNoSellingPrice++;
                        } else {
                            $updates['selling_price'] = round((float) $base * (1 + (float) $validated['price_relative_percent'] / 100), 2);
                        }
                    } elseif ($mode === 'relative_fixed') {
                        $base = $product->getRawOriginal('selling_price') ?? $product->selling_price;
                        if ($base === null || $base === '') {
                            $skippedRelativeNoSellingPrice++;
                        } else {
                            $updates['selling_price'] = max(0, round((float) $base + (float) $validated['price_relative_amount'], 2));
                        }
                    }
                }

                if ($hasContent) {
                    if ($request->boolean('apply_custom_description')) {
                        $updates['custom_description'] = $validated['custom_description'] ?? null;
                    }
                    if ($request->boolean('apply_how_to_redeem')) {
                        $updates['how_to_redeem'] = $validated['how_to_redeem'] ?? null;
                    }
                    if ($request->boolean('apply_terms_and_conditions')) {
                        $updates['terms_and_conditions'] = $validated['terms_and_conditions'] ?? null;
                    }
                }

                if ($updates === []) {
                    continue;
                }

                $contentTouched = $hasContent && (
                    array_key_exists('custom_description', $updates)
                    || array_key_exists('how_to_redeem', $updates)
                    || array_key_exists('terms_and_conditions', $updates)
                );
                if ($contentTouched) {
                    $updates['content_customized_at'] = now();
                    $updates['content_customized_by'] = $request->user()->id;
                }

                $keys = array_keys($updates);
                $onlyVisibility = $keys === ['show_product'];

                if ($onlyVisibility) {
                    $old = ['show_product' => $product->show_product];
                    $product->update($updates);
                    audit('product.visibility_toggled', $product, $old, ['show_product' => $product->show_product]);
                    $visibilityChanges++;
                } else {
                    $old = $product->only($keys);
                    $product->update($updates);
                    audit('product.updated', $product, $old, $product->only($keys));
                    $fullUpdates++;
                }
            }
        });

        $parts = [];
        if ($visibilityChanges > 0) {
            $parts[] = "{$visibilityChanges} visibility";
        }
        if ($fullUpdates > 0) {
            $parts[] = "{$fullUpdates} product(s) updated (price/content)";
        }
        if ($skippedRelativeNoSellingPrice > 0) {
            $parts[] = "{$skippedRelativeNoSellingPrice} skipped (no selling price for relative change)";
        }

        $message = $parts !== []
            ? 'Bulk update: '.implode('; ', $parts).'.'
            : 'No changes applied.';

        return back()->with('success', $message);
    }

    public function create(Request $request): Response
    {
        $this->authorize('products.create');
        $scope = $request->input('catalog_scope');
        if (! is_string($scope) || ! in_array($scope, [self::CATALOG_SCOPE_STOREFRONT, self::CATALOG_SCOPE_BUSINESS, self::CATALOG_SCOPE_ALL], true)) {
            $scope = $this->resolveCatalogScope($request);
        }
        $this->authorizeCatalogScopeForList($scope);

        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'default_catalog_audience' => $this->defaultAudienceForScope($scope),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('products.create');

        $data = $request->validated();
        unset($data['custom_image']);

        $product = Product::create($data);

        if ($request->hasFile('custom_image')) {
            $path = $request->file('custom_image')->store('products/'.$product->id, 'public');
            $product->update(['custom_image' => $path]);
        }

        $hasTextContent = collect($request->only(['custom_description', 'how_to_redeem', 'terms_and_conditions']))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->isNotEmpty();
        if ($hasTextContent || $request->hasFile('custom_image')) {
            $product->update([
                'content_customized_at' => now(),
                'content_customized_by' => $request->user()->id,
            ]);
        }

        audit('product.created', $product, [], $request->validated());

        return redirect()->route('panel.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): Response
    {
        $this->authorize('products.update');

        $product->load(['brand', 'productMedia']);

        $productPayload = $product->toArray();
        $productPayload['display_name'] = $product->display_name;
        $uploadedMedia = $product->productMedia->map(fn (ProductMedia $m) => [
            'id' => $m->id,
            'url' => $m->url,
            'collection' => $m->type,
            'sort_order' => $m->display_order,
            'alt_text' => $m->alt_text,
            'source' => 'upload',
        ])->values()->all();
        $productPayload['media'] = array_merge(
            $this->supplierGalleryItems($product),
            $uploadedMedia,
        );
        unset($productPayload['product_media']);

        return Inertia::render('Admin/Products/Form', [
            'product' => $productPayload,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('products.update');

        $old = $product->toArray();

        $data = $request->validated();
        unset($data['custom_image']);

        $product->update($data);

        if ($request->hasFile('custom_image')) {
            $path = $request->file('custom_image')->store('products/'.$product->id, 'public');
            $product->update(['custom_image' => $path]);
            $product->update([
                'content_customized_at' => now(),
                'content_customized_by' => $request->user()->id,
            ]);
        }

        audit('product.updated', $product, $old, $request->validated());

        return back()->with('success', 'Product updated successfully.');
    }

    public function updateContent(UpdateProductContentRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('products.update');

        $product->update(array_merge($request->validated(), [
            'content_customized_at' => now(),
            'content_customized_by' => $request->user()->id,
        ]));

        audit('product.content_updated', $product, [], $request->validated());

        return back()->with('success', 'Product content saved.');
    }

    public function resetContent(Request $request, Product $product, string $field): RedirectResponse
    {
        $this->authorize('products.update');

        if (! in_array($field, ['custom_description', 'terms_and_conditions', 'how_to_redeem'], true)) {
            abort(404);
        }

        $product->update([
            $field => null,
            'content_customized_at' => now(),
            'content_customized_by' => $request->user()->id,
        ]);

        audit('product.content_reset', $product, [$field => 'cleared'], []);

        return back()->with('success', 'Field reset to provider default on next view (empty until you re-enter content).');
    }

    public function storeMedia(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('products.update');

        $request->validate([
            'file' => 'required|image|max:5120',
        ]);

        $this->productMedia->store($request->file('file'), $product, $request->user());

        $product->update([
            'content_customized_at' => now(),
            'content_customized_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Image uploaded.');
    }

    public function reorderMedia(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('products.update');

        $data = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|exists:product_media,id',
        ]);

        $this->productMedia->reorder($product, $data['ordered_ids']);

        return back()->with('success', 'Image order updated.');
    }

    public function setHeroMedia(Request $request, Product $product, ProductMedia $media): RedirectResponse
    {
        $this->authorize('products.update');

        if ((int) $media->product_id !== (int) $product->id) {
            abort(404);
        }

        $this->productMedia->setHero($product, $media);

        return back()->with('success', 'Hero image updated.');
    }

    public function destroyMedia(Request $request, Product $product, ProductMedia $media): RedirectResponse
    {
        $this->authorize('products.update');

        if ((int) $media->product_id !== (int) $product->id) {
            abort(404);
        }

        $this->productMedia->delete($media);

        return back()->with('success', 'Image removed.');
    }

    public function toggleVisibility(Product $product): RedirectResponse
    {
        $this->authorize('products.publish');

        $old = ['show_product' => $product->show_product];
        $product->update(['show_product' => ! $product->show_product]);

        audit('product.visibility_toggled', $product, $old, ['show_product' => $product->show_product]);

        return back()->with('success', 'Product visibility updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('products.delete');

        audit('product.deleted', $product, $product->toArray(), []);
        $product->delete();

        return redirect()->route('panel.products.index')
            ->with('success', 'Product deleted.');
    }

    /**
     * URLs from sync/API (products.images JSON, custom_image) as virtual gallery rows so admins see them before uploading overrides.
     *
     * @return list<array{id: int, url: string, collection: string, sort_order: int, alt_text: null, source: string}>
     */
    private function supplierGalleryItems(Product $product): array
    {
        $seen = [];
        $ordered = [];

        $images = $product->images;
        if (is_array($images)) {
            foreach ($images as $value) {
                if (! is_string($value) || $value === '' || $value === 'null' || $value === 'undefined') {
                    continue;
                }
                if (! isset($seen[$value])) {
                    $seen[$value] = true;
                    $ordered[] = $value;
                }
            }
        }

        $custom = $product->custom_image;
        if (! empty($custom) && $custom !== 'null') {
            $url = is_string($custom) && str_starts_with($custom, 'http')
                ? $custom
                : Storage::disk('public')->url($custom);
            if (! isset($seen[$url])) {
                $seen[$url] = true;
                $ordered[] = $url;
            }
        }

        $items = [];
        foreach ($ordered as $i => $url) {
            $items[] = [
                'id' => -1 - $i,
                'url' => $url,
                'collection' => 'supplier',
                'sort_order' => -1000 + $i,
                'alt_text' => null,
                'source' => 'supplier',
            ];
        }

        return $items;
    }

    /**
     * Human-readable price for admin list: JSON slab/range, then selling_price / denomination.
     */
    private function resolvePriceDisplayForList(Product $p): string
    {
        $fromHelper = $p->getFormattedPriceRange();
        if (is_string($fromHelper) && $fromHelper !== '') {
            return $fromHelper;
        }

        $price = $p->price;
        if (is_array($price)) {
            $type = strtoupper((string) ($price['type'] ?? ''));
            if ($type === 'SLAB' && ! empty($price['values']) && is_array($price['values'])) {
                $parts = array_map(
                    fn ($v) => "\u{20B9}".number_format((float) $v),
                    $price['values']
                );

                return implode(', ', $parts);
            }
            if (isset($price['min'], $price['max'])) {
                return "\u{20B9}".number_format((float) $price['min']).' - '."\u{20B9}".number_format((float) $price['max']);
            }
        }

        if ($p->selling_price !== null && $p->selling_price !== '') {
            return "\u{20B9}".number_format((float) $p->selling_price);
        }

        if ($p->denomination !== null && $p->denomination !== '') {
            return "\u{20B9}".number_format((float) $p->denomination);
        }

        return '';
    }

    private function resolveCatalogScope(Request $request): string
    {
        $raw = $request->input('catalog_scope');
        if (is_string($raw) && in_array($raw, [self::CATALOG_SCOPE_STOREFRONT, self::CATALOG_SCOPE_BUSINESS, self::CATALOG_SCOPE_ALL], true)) {
            return $raw;
        }
        if (Gate::allows('products.catalog.all')) {
            return self::CATALOG_SCOPE_ALL;
        }
        if (Gate::allows('products.catalog.storefront')) {
            return self::CATALOG_SCOPE_STOREFRONT;
        }
        if (Gate::allows('products.catalog.business')) {
            return self::CATALOG_SCOPE_BUSINESS;
        }

        abort(403, 'No product catalog permission is assigned to your role.');
    }

    private function authorizeCatalogScopeForList(string $scope): void
    {
        if (! Gate::allows('products.view')) {
            abort(403);
        }

        $ok = match ($scope) {
            self::CATALOG_SCOPE_STOREFRONT => Gate::allows('products.catalog.storefront') || Gate::allows('products.catalog.all'),
            self::CATALOG_SCOPE_BUSINESS => Gate::allows('products.catalog.business') || Gate::allows('products.catalog.all'),
            self::CATALOG_SCOPE_ALL => Gate::allows('products.catalog.all'),
            default => false,
        };

        if (! $ok) {
            abort(403);
        }
    }

    private function defaultAudienceForScope(string $scope): string
    {
        return match ($scope) {
            self::CATALOG_SCOPE_STOREFRONT => Product::CATALOG_AUDIENCE_B2C,
            self::CATALOG_SCOPE_BUSINESS => Product::CATALOG_AUDIENCE_B2B,
            default => Product::CATALOG_AUDIENCE_BOTH,
        };
    }

    /**
     * Distinct provider values visible under this catalog scope (ignores list filters).
     *
     * @return list<string>
     */
    private function sourceProviderOptionsForScope(string $scope): array
    {
        $optionsQuery = Product::query();
        match ($scope) {
            self::CATALOG_SCOPE_STOREFRONT => $optionsQuery->adminStorefrontCatalog(),
            self::CATALOG_SCOPE_BUSINESS => $optionsQuery->adminBusinessCatalog(),
            default => null,
        };

        return $optionsQuery
            ->whereNotNull('source_provider')
            ->where('source_provider', '!=', '')
            ->distinct()
            ->orderBy('source_provider')
            ->pluck('source_provider')
            ->values()
            ->all();
    }
}
