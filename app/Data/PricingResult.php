<?php

namespace App\Data;

/**
 * Immutable value object holding all server-calculated monetary values for an order.
 * Every field is computed from database records -- never from frontend input.
 */
readonly class PricingResult
{
    public function __construct(
        public float $unitPrice,
        public int $quantity,
        public float $subtotal,
        public float $discountPercentage,
        public float $discountAmount,
        public float $gstPercentage,
        public float $gstAmount,
        public float $grandTotal,
        public ?string $offerApplied = null,
        public ?string $discountSource = null,
    ) {}

    public function toArray(): array
    {
        return [
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
            'discount_percentage' => $this->discountPercentage,
            'discount_amount' => $this->discountAmount,
            'gst_percentage' => $this->gstPercentage,
            'gst_amount' => $this->gstAmount,
            'grand_total' => $this->grandTotal,
            'offer_applied' => $this->offerApplied,
        ];
    }
}
