<?php

namespace App\Services\Pricing;

use App\Data\PricingResult;
use App\Exceptions\OrderCreationException;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

/**
 * Single source of truth for all order monetary calculations.
 *
 * NEVER accepts pre-calculated amounts. Every value is derived from
 * the product record in the database, the applicable discount, and the GST rate.
 */
class PricingService
{
    public function __construct(
        private DiscountResolver $discountResolver,
    ) {}

    /**
     * Calculate all monetary values for an order from database records only.
     *
     * @throws OrderCreationException if denomination is invalid
     */
    public function calculate(
        Product $product,
        int $quantity,
        float $denomination,
        ?string $offerCode = null,
        ?User $user = null,
        ?Tenant $tenant = null,
    ): PricingResult {
        $this->validateDenomination($product, $denomination);

        $discount = $this->discountResolver->resolve($product, $offerCode, $user, $tenant);

        $gstRate = $this->resolveGstRate($product);

        $subtotal = round($denomination * $quantity, 2);
        $discountAmount = round($subtotal * ($discount['percentage'] / 100), 2);
        $taxableAmount = $subtotal - $discountAmount;
        $gstAmount = round($taxableAmount * ($gstRate / 100), 2);
        $grandTotal = round($taxableAmount + $gstAmount, 2);

        return new PricingResult(
            unitPrice: $denomination,
            quantity: $quantity,
            subtotal: $subtotal,
            discountPercentage: $discount['percentage'],
            discountAmount: $discountAmount,
            gstPercentage: $gstRate,
            gstAmount: $gstAmount,
            grandTotal: $grandTotal,
            offerApplied: $discount['offer_code'],
            discountSource: $discount['source'],
        );
    }

    /**
     * Validate that the requested denomination is allowed for this product.
     *
     * @throws OrderCreationException
     */
    private function validateDenomination(Product $product, float $denomination): void
    {
        $priceData = is_string($product->price)
            ? json_decode($product->price, true)
            : (array) $product->price;

        $priceType = $priceData['type'] ?? 'RANGE';

        if ($priceType === 'SLAB') {
            $allowed = $priceData['denominations'] ?? [];
            if (! in_array((string) $denomination, $allowed, true)) {
                throw new OrderCreationException(
                    "Invalid denomination ₹{$denomination}. Allowed: ".implode(', ', $allowed)
                );
            }

            return;
        }

        $min = (float) ($priceData['min'] ?? $product->minPrice ?? 0);
        $max = (float) ($priceData['max'] ?? $product->maxPrice ?? 999999);

        if ($denomination < $min || $denomination > $max) {
            throw new OrderCreationException(
                "Denomination ₹{$denomination} is outside the allowed range ₹{$min}–₹{$max}."
            );
        }
    }

    private function resolveGstRate(Product $product): float
    {
        return (float) ($product->gst_rate ?? config('app.default_gst_rate', 0));
    }
}
