<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductContentService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

/**
 * API v1 -- Product catalog & category browsing.
 */
class CatalogController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProductContentService $productContent,
    ) {}

    /** List visible products with filters, sorting, and pagination. */
    public function index(Request $request): ResponsePayload
    {
        $query = Product::query()->forStorefrontCatalog()->with(['productMedia', 'categories']);

        if ($request->filled('search')) {
            $query->where('product_name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $catId));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('source_provider')) {
            $query->where('source_provider', $request->source_provider);
        }

        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', $request->max_price);
        }

        $sortBy = $request->input('sort_by', 'display_order');
        if ($sortBy === 'priority') {
            $sortBy = 'display_order';
        }

        $allowedSort = ['display_order', 'selling_price', 'product_name', 'id', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'display_order';
        }

        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $products = $query
            ->orderBy($sortBy, $sortOrder)
            ->paginate($request->input('per_page', 20));

        $products->getCollection()->transform(function (Product $p) {
            $base = $p->toArray();
            $base['resolved_image_url'] = $this->productContent->resolvePrimaryImageUrl($p);

            return $base;
        });

        return $this->paginated($products);
    }

    /** Show a single product by ID. */
    public function show(Product $product): ResponsePayload
    {
        if (! $product->isListedOnConsumerStorefront()) {
            return $this->notFound();
        }

        $product->load(['brand', 'productMedia']);
        $payload = $product->toArray();
        $payload['resolved'] = [
            'image_url' => $this->productContent->resolvePrimaryImageUrl($product),
            'gallery_urls' => $this->productContent->resolveGalleryUrls($product),
            'description' => $this->productContent->resolveDescription($product),
            'terms' => $this->productContent->resolveTnc($product),
            'how_to_redeem' => $this->productContent->resolveHowToRedeem($product),
        ];

        return $this->ok('Product retrieved.', ['product' => $payload]);
    }

    /** List storefront navigation categories. */
    public function categories(): ResponsePayload
    {
        $categories = Category::orderBy('display_order')->get(['id', 'name', 'slug', 'icon_url', 'banner_url']);

        $mapped = $categories->map(function (Category $category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'thumbnail' => $category->icon_url,
                'accent_color' => null,
            ];
        });

        return $this->ok('Categories retrieved.', ['categories' => $mapped->toArray()]);
    }
}
