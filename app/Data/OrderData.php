<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO carrying validated order-creation input.
 * Contains only what the user is allowed to send -- no monetary values.
 */
readonly class OrderData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $denomination,
        public string $paymentMethod,
        public ?string $offerCode = null,
        public string $giftOption = 'buy_for_self',
        public ?string $receiverName = null,
        public ?string $receiverEmail = null,
        public ?string $receiverMobile = null,
        public ?string $receiverMessage = null,
        public ?string $vdBrandCode = null,
    ) {}

    /**
     * Build from a validated request array (only call with $request->validated()).
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            productId: $validated['product_id'],
            quantity: $validated['quantity'] ?? 1,
            denomination: (float) $validated['denomination'],
            paymentMethod: $validated['payment_method'],
            offerCode: $validated['offer_code'] ?? null,
            giftOption: $validated['gift_send_option'] ?? 'buy_for_self',
            receiverName: $validated['receiver_name'] ?? null,
            receiverEmail: $validated['receiver_email'] ?? null,
            receiverMobile: $validated['receiver_mobile'] ?? null,
            receiverMessage: $validated['receiver_msg'] ?? null,
            vdBrandCode: $validated['vd_brand_code'] ?? null,
        );
    }
}
