<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductContentRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\Catalog\ProductContentService;
use App\Services\Catalog\ProductMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductMediaService $productMedia,
        private ProductContentService $productContent,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('products.view');

        $query = Product::with(['brand', 'productMedia']);

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

        $paginator = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $items = collect($paginator->items())->map(function (Product $p) {
            $thumb = $this->productContent->resolvePrimaryImageUrl($p);

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'product_name' => $p->display_name,
                'source_provider' => $p->source_provider,
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
            'filters' => $request->only(['search', 'source_provider', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('products.create');

        return Inertia::render('Admin/Products/Form', [
            'product' => null,
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

        return redirect()->route('admin.products.index')
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
            'url' => $m->url(),
            'collection' => $m->collection,
            'sort_order' => $m->sort_order,
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

        return redirect()->route('admin.products.index')
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
}
