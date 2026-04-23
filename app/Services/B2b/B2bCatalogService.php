<?php

namespace App\Services\B2b;

use App\Helpers\ProductHelper;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tenant-scoped catalog rows with indicative pricing (qty=1 at representative denomination).
 *
 * Representative denomination: minimum allowed face value from product price JSON (RANGE/SLAB/cpg).
 */
class B2bCatalogService
{
    public function __construct(
        private PricingService $pricingService,
    ) {}

    /**
     * @return array{assigned_active_count: int, b2b_eligible_count: int}
     */
    public function tenantAssignmentStats(Tenant $tenant): array
    {
        return [
            'assigned_active_count' => $tenant->products()->wherePivot('is_active', true)->count(),
            'b2b_eligible_count' => $tenant->products()->wherePivot('is_active', true)->forB2bCatalog()->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public function categoriesForTenant(Tenant $tenant): array
    {
        $products = $tenant->products()
            ->wherePivot('is_active', true)
            ->forB2bCatalog()
            ->with('categories:id,name,slug,thumbnail,accent_color')
            ->get();

        return $products
            ->pluck('categories')
            ->flatten()
            ->filter(fn ($c) => $c instanceof Category)
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'thumbnail' => $c->thumbnail,
                'accent_color' => $c->accent_color,
            ])
            ->all();
    }

    /**
     * @param  array{q?: string, category_id?: int|null, currency?: string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function catalogRows(Tenant $tenant, User $user, array $filters = []): Collection
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $currency = isset($filters['currency']) ? trim((string) $filters['currency']) : null;

        $query = $tenant->products()
            ->wherePivot('is_active', true)
            ->forB2bCatalog()
            ->with(['categories:id,name,slug', 'brand:id,name', 'productMedia'])
            ->when($q !== '', function (Builder $builder) use ($q) {
                $builder->where(function (Builder $inner) use ($q) {
                    $inner->where('products.name', 'like', '%'.$q.'%')
                        ->orWhere('products.product_name', 'like', '%'.$q.'%')
                        ->orWhere('products.sku', 'like', '%'.$q.'%')
                        ->orWhere('products.brandName', 'like', '%'.$q.'%');
                });
            })
            ->when($categoryId, function (Builder $builder) use ($categoryId) {
                $builder->whereHas('categories', fn (Builder $c) => $c->where('categories.id', $categoryId));
            })
            ->when($currency, function (Builder $builder) use ($currency) {
                $builder->where(function (Builder $inner) use ($currency) {
                    $inner->where('products.product_currency_code', $currency)
                        ->orWhere('products.currency', 'like', '%"code":"'.$currency.'"%');
                });
            })
            ->orderBy('products.name');

        return $query->get()->flatMap(function (Product $product) use ($tenant, $user) {
            try {
                return [$this->serializeProduct($tenant, $user, $product)];
            } catch (Throwable $e) {
                Log::warning('b2b_catalog.serialize_failed', [
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'message' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Distinct currency codes for filter dropdown.
     *
     * @return array<int, string>
     */
    public function currencyOptions(Tenant $tenant): array
    {
        $codes = $tenant->products()
            ->wherePivot('is_active', true)
            ->forB2bCatalog()
            ->pluck('products.product_currency_code')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values(array_filter($codes, fn ($c) => $c !== null && $c !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeProduct(Tenant $tenant, User $user, Product $product): array
    {
        $denom = $this->representativeDenomination($product);
        $pricing = $this->pricingService->calculate(
            product: $product,
            quantity: 1,
            denomination: $denom,
            offerCode: null,
            user: $user,
            tenant: $tenant,
        );

        $currencyCode = $product->product_currency_code
            ?: (is_array($product->currency) ? ($product->currency['code'] ?? 'INR') : ($product->currency ?: 'INR'));

        $categories = $product->categories ?? collect();

        return [
            'id' => $product->id,
            'name' => $product->display_name,
            'sku' => $product->sku,
            'image_url' => $product->display_image_url,
            'currency_code' => $currencyCode,
            'price_range_label' => $product->getFormattedPriceRange(
                $currencyCode === 'INR' ? "\u{20B9}" : $currencyCode.' '
            ),
            'representative_denomination' => $denom,
            'discount_percentage' => $pricing->discountPercentage,
            'discount_source' => $pricing->discountSource,
            'sample_grand_total' => $pricing->grandTotal,
            'category_ids' => $categories->pluck('id')->all(),
            'categories' => $categories
                ->filter(fn ($c) => $c instanceof Category)
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->all(),
        ];
    }

    private function representativeDenomination(Product $product): float
    {
        $min = $product->getMinPrice();
        if ($min > 0) {
            return (float) $min;
        }

        $raw = $product->getAttributes()['price'] ?? null;
        $priceData = ProductHelper::normalizePriceArray(
            ProductHelper::decodePrice($raw) ?? []
        );
        if ($priceData === []) {
            return max(1.0, (float) ($product->denomination ?? $product->selling_price ?? 1));
        }

        $type = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

        if ($type === 'SLAB' && ! empty($priceData['denominations'])) {
            $first = $priceData['denominations'][0];

            return (float) $first;
        }

        if (isset($priceData['cpg']) && is_array($priceData['cpg'])) {
            foreach ($priceData['cpg'] as $cpg) {
                if (is_array($cpg) && isset($cpg['min'])) {
                    return (float) $cpg['min'];
                }
            }
        }

        $minR = (float) ($priceData['min'] ?? 1);

        return $minR > 0 ? $minR : 1.0;
    }
}
