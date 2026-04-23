<?php

namespace App\Services\Pricing;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

/**
 * Resolves the applicable discount for a product + user + tenant + offer code combination.
 * Always reads from the database -- never from request input.
 */
class DiscountResolver
{
    /**
     * Determine the best discount to apply.
     *
     * Priority: active offer code > tenant margin > product-level discount.
     *
     * @return array{percentage: float, offer_code: ?string, source: string}
     */
    public function resolve(
        Product $product,
        ?string $offerCode = null,
        ?User $user = null,
        ?Tenant $tenant = null,
    ): array {
        if ($offerCode) {
            $offer = $this->findValidOffer($offerCode, $product, $user, $tenant);
            if ($offer) {
                return [
                    'percentage' => (float) $offer->discount_percentage,
                    'offer_code' => $offer->code,
                    'source' => 'offer',
                ];
            }
        }

        if ($tenant) {
            $assignment = $tenant->products()
                ->where('products.id', $product->id)
                ->wherePivot('is_active', true)
                ->first();
            if ($assignment?->pivot && $assignment->pivot->margin_override !== null && $assignment->pivot->margin_override !== '') {
                return [
                    'percentage' => (float) $assignment->pivot->margin_override,
                    'offer_code' => null,
                    'source' => 'tenant_product_margin',
                ];
            }
        }

        if ($tenant && $tenant->margin_percentage) {
            return [
                'percentage' => (float) $tenant->margin_percentage,
                'offer_code' => null,
                'source' => 'tenant_margin',
            ];
        }

        return [
            'percentage' => (float) ($product->discount_percentage ?? 0),
            'offer_code' => null,
            'source' => 'product',
        ];
    }

    private function findValidOffer(
        string $code,
        Product $product,
        ?User $user,
        ?Tenant $tenant,
    ): ?Offer {
        $query = Offer::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });

        if ($tenant) {
            $query->where(function ($q) use ($tenant) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
            });
        } else {
            $query->whereNull('tenant_id');
        }

        $offer = $query->first();

        if (! $offer) {
            return null;
        }

        if ($offer->max_uses && $offer->usages()->count() >= $offer->max_uses) {
            return null;
        }

        if ($user && $offer->max_uses_per_user) {
            $userUsages = $offer->usages()->where('user_id', $user->id)->count();
            if ($userUsages >= $offer->max_uses_per_user) {
                return null;
            }
        }

        return $offer;
    }
}
