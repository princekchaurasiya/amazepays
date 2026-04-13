<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shared KGen variant filtering and discount pricing for storefront / homepage views.
 */
final class KgenPricingHelper
{
    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return Collection<int, array<string, mixed>>
     */
    public static function filterAvailableVariants(array $variants): Collection
    {
        return collect($variants)->filter(function (mixed $variant) {
            if (! is_array($variant)) {
                return false;
            }

            $stockAvailable = $variant['stockAvailable'] ?? $variant['inStock'] ?? $variant['available'] ?? $variant['isAvailable'] ?? true;
            $stock = $variant['stock'] ?? $variant['quantity'] ?? null;

            if ($stock === 0 || $stockAvailable === false || $stockAvailable === 0) {
                return false;
            }

            if ($stockAvailable === true || ($stock !== null && $stock > 0)) {
                return true;
            }

            return true;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $variant
     */
    public static function calculateEffectivePrice(array $variant, float $discountPercentage): float
    {
        $variantMrp = (float) ($variant['mrp'] ?? 0);
        $variantPrice = (float) ($variant['price'] ?? $variantMrp);
        $priceSource = $variantMrp > 0 ? $variantMrp : $variantPrice;

        if ($discountPercentage > 0) {
            return round($priceSource * (1 - ($discountPercentage / 100)), 2);
        }

        return $variantPrice;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function enrichKgenProductForHomepage(array $product, int $descriptionLimit = 90): array
    {
        $discountPercentage = (float) ($product['discount_percentage'] ?? 0);
        $availableVariants = self::filterAvailableVariants($product['variants'] ?? [])->all();
        $primaryVariant = $availableVariants[0] ?? null;

        $variantMrp = 0.0;
        $variantPrice = 0.0;
        $effectivePrice = null;

        if (is_array($primaryVariant)) {
            $variantMrp = (float) ($primaryVariant['mrp'] ?? 0);
            $variantPrice = (float) ($primaryVariant['price'] ?? $variantMrp);
            $effectivePrice = self::calculateEffectivePrice($primaryVariant, $discountPercentage);
        }

        return array_merge($product, [
            'available_variants' => $availableVariants,
            'primary_variant' => $primaryVariant,
            'discount_percentage' => $discountPercentage,
            'variant_mrp' => $variantMrp,
            'variant_price' => $variantPrice,
            'effective_price' => $effectivePrice,
            'description_excerpt' => Str::limit((string) ($product['descriptionText'] ?? ''), $descriptionLimit),
        ]);
    }

    /**
     * Rows for list / card views (each in-stock variant with MRP and discounted price).
     *
     * @param  array<string, mixed>  $product
     * @return list<array{variant: array<string, mixed>, mrpValue: float, basePrice: float, effectivePrice: float}>
     */
    public static function variantDisplayRows(array $product): array
    {
        $discount = (float) ($product['discount_percentage'] ?? 0);
        $rows = [];

        foreach (self::filterAvailableVariants($product['variants'] ?? []) as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $mrpValue = (float) ($variant['mrp'] ?? 0);
            $basePrice = (float) ($variant['price'] ?? $mrpValue);

            $rows[] = [
                'variant' => $variant,
                'mrpValue' => $mrpValue,
                'basePrice' => $basePrice,
                'effectivePrice' => self::calculateEffectivePrice($variant, $discount),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function enrichKgenProductForListing(array $product, int $descriptionLimit = 100): array
    {
        $rows = self::variantDisplayRows($product);

        return array_merge($product, [
            'available_variants' => array_column($rows, 'variant'),
            'variant_display_rows' => $rows,
            'description_excerpt' => Str::limit((string) ($product['descriptionText'] ?? ''), $descriptionLimit),
        ]);
    }
}
