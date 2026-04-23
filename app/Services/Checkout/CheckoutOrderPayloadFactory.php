<?php

namespace App\Services\Checkout;

use App\Models\Order;

class CheckoutOrderPayloadFactory
{
    /**
     * Expose minimal, server-authoritative checkout order fields to the UI.
     *
     * @return array{id: int|null, amount_payable_after_discount: float, grand_payable_amount: float}
     */
    public function make(Order $order): array
    {
        return [
            'id' => $order->id,
            'amount_payable_after_discount' => (float) ($order->amount_payable_after_discount ?? 0),
            'grand_payable_amount' => (float) ($order->grand_payable_amount ?? 0),
        ];
    }
}

