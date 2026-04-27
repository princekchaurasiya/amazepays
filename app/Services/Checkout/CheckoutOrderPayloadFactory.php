<?php

namespace App\Services\Checkout;

use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class CheckoutOrderPayloadFactory
{
    /**
     * Expose minimal, server-authoritative checkout order fields to the UI.
     *
     * @return array{id: int|null, amount_payable_after_discount: float, grand_payable_amount: float, quantity: int, denomination: float|null}
     */
    public function make(Order $order): array
    {
        $subtotalMinor = (int) ($order->subtotal_minor ?? 0);
        $discountMinor = (int) ($order->discount_total_minor ?? 0);
        $grandTotalMinor = (int) ($order->grand_total_minor ?? max(0, $subtotalMinor - $discountMinor));

        // Old schemas may not have reliable denormalized order columns (denomination/quantity/amount fields).
        $firstItem = null;
        if ($order->id) {
            $firstItem = $order->relationLoaded('items')
                ? $order->items->first()
                : $order->items()->orderBy('id')->first();
        }

        $hasQuantity = Schema::hasColumn('orders', 'quantity');
        $hasDenomination = Schema::hasColumn('orders', 'denomination');
        $hasGrandPayable = Schema::hasColumn('orders', 'grand_payable_amount');
        $hasAfterDiscount = Schema::hasColumn('orders', 'amount_payable_after_discount');

        $quantity = $hasQuantity && $order->quantity !== null
            ? (int) $order->quantity
            : (int) ($firstItem?->quantity ?? 1);

        $denomination = null;
        if ($hasDenomination && $order->denomination !== null) {
            $denomination = (float) $order->denomination;
        } elseif ($firstItem && $firstItem->unit_amount_minor !== null) {
            $denomination = ((int) $firstItem->unit_amount_minor) / 100;
        }

        $grandPayable = $hasGrandPayable && $order->grand_payable_amount !== null
            ? (float) $order->grand_payable_amount
            : ($subtotalMinor / 100);

        $afterDiscount = $hasAfterDiscount && $order->amount_payable_after_discount !== null
            ? (float) $order->amount_payable_after_discount
            : ($grandTotalMinor / 100);

        return [
            'id' => $order->id,
            'amount_payable_after_discount' => (float) $afterDiscount,
            'grand_payable_amount' => (float) $grandPayable,
            'quantity' => $quantity,
            'denomination' => $denomination,
        ];
    }
}
