<?php

namespace App\Services\Checkout;

use App\Models\Order;

class CheckoutOrderPayloadFactory
{
    /**
     * Expose minimal, server-authoritative checkout order fields to the UI.
     *
     * @return array{id: int|null, amount_payable_after_discount: float, grand_payable_amount: float, quantity: int, denomination: float|null}
     */
    public function make(Order $order): array
    {
        return [
            'id' => $order->id,
            'amount_payable_after_discount' => (float) ($order->amount_payable_after_discount ?? 0),
            'grand_payable_amount' => (float) ($order->grand_payable_amount ?? 0),
            'quantity' => (int) ($order->quantity ?? 1),
            'denomination' => $order->denomination !== null ? (float) $order->denomination : null,
        ];
    }
}
