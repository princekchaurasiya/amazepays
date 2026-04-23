<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutWriteService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function createOrderForCheckout(Product $product, int $userId, array $validated): Order
    {
        return DB::transaction(function () use ($product, $userId, $validated): Order {
            $productLocked = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->first();

            if (! $productLocked) {
                throw ValidationException::withMessages([
                    'message' => __('payments.product_not_found_refresh'),
                ]);
            }

            if ($productLocked->isExcludedFromConsumerStorefront()) {
                throw ValidationException::withMessages([
                    'message' => __('payments.product_not_available_storefront'),
                ]);
            }

            $denomination = (float) $validated['denomination'];
            $quantity = (int) $validated['quantity'];
            $this->assertDenominationValidForProduct($productLocked, $denomination);

            $grandPayableAmount = $quantity * $denomination;
            $this->assertSkuMonthlyLimit($productLocked, $userId, $grandPayableAmount);

            $discountPercentage = (float) ($productLocked->discount_percentage ?? 0);
            if ($discountPercentage < 0 || $discountPercentage > 100) {
                throw ValidationException::withMessages([
                    'message' => __('responses.UNKNOWN_ERROR'),
                ]);
            }

            $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
            $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
            if ($totalPayableAmountAfterDiscount < 0) {
                throw ValidationException::withMessages([
                    'message' => __('responses.UNKNOWN_ERROR'),
                ]);
            }

            $recentOrder = Order::query()
                ->where('user_id', $userId)
                ->where('sku', $productLocked->sku)
                ->where('denomination', $denomination)
                ->where('quantity', $quantity)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->where('order_status', 'Pending')
                ->lockForUpdate()
                ->first();

            if ($recentOrder) {
                throw ValidationException::withMessages([
                    'message' => __('payments.order_processing_exists'),
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $userId,
                'sku' => $productLocked->sku,
                'product_name' => $productLocked->name,
                'product_id' => $productLocked->id,
                'denomination' => $denomination,
                'quantity' => $quantity,
                'grand_payable_amount' => $grandPayableAmount,
                'discounted_amount_value' => $discountAmount,
                'amount_payable_after_discount' => $totalPayableAmountAfterDiscount,
                'gift_send_option' => $validated['gift_send_option'],
                'delivery_mode' => 'both',
                'gift_theme_id' => isset($validated['gift_theme_id']) ? (int) $validated['gift_theme_id'] : null,
                'gift_message_title' => $validated['gift_message_title'] ?? null,
                'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                'gift_delivery_at' => (($validated['gift_delivery_option'] ?? null) === 'send_later') ? ($validated['gift_delivery_at'] ?? null) : null,
                'sender_first_name' => $validated['sender_first_name'] ?? null,
                'receiver_name' => $validated['receiver_name'] ?? null,
                'receiver_email' => $validated['receiver_email'] ?? null,
                'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                'receiver_msg' => $validated['receiver_msg'] ?? null,
                'order_status' => 'Pending',
            ]);

            $order->refno = 'Amz'.now()->format('Ymd').str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
            $order->save();

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createOrRefreshDraftOrder(Product $product, int $userId, array $validated): Order
    {
        return DB::transaction(function () use ($product, $userId, $validated): Order {
            $productLocked = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->first();

            if (! $productLocked) {
                throw ValidationException::withMessages([
                    'message' => __('payments.product_not_found_refresh'),
                ]);
            }

            if ($productLocked->isExcludedFromConsumerStorefront()) {
                throw ValidationException::withMessages([
                    'message' => __('payments.product_not_available_storefront'),
                ]);
            }

            $denomination = (float) $validated['denomination'];
            $quantity = (int) $validated['quantity'];
            $this->assertDenominationValidForProduct($productLocked, $denomination);

            $grandPayableAmount = $quantity * $denomination;
            $this->assertSkuMonthlyLimit($productLocked, $userId, $grandPayableAmount);

            $discountPercentage = (float) ($productLocked->discount_percentage ?? 0);
            if ($discountPercentage < 0 || $discountPercentage > 100) {
                throw ValidationException::withMessages([
                    'message' => __('responses.UNKNOWN_ERROR'),
                ]);
            }

            $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
            $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
            if ($totalPayableAmountAfterDiscount < 0) {
                throw ValidationException::withMessages([
                    'message' => __('responses.UNKNOWN_ERROR'),
                ]);
            }

            $order = Order::query()
                ->where('user_id', $userId)
                ->where('product_id', $productLocked->id)
                ->where('order_status', 'Pending')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($order) {
                $order->fill([
                    'sku' => $productLocked->sku,
                    'product_name' => $productLocked->name,
                    'denomination' => $denomination,
                    'quantity' => $quantity,
                    'grand_payable_amount' => $grandPayableAmount,
                    'discounted_amount_value' => $discountAmount,
                    'amount_payable_after_discount' => $totalPayableAmountAfterDiscount,
                    'gift_send_option' => $validated['gift_send_option'],
                    'delivery_mode' => 'both',
                    'gift_theme_id' => isset($validated['gift_theme_id']) ? (int) $validated['gift_theme_id'] : null,
                    'gift_message_title' => $validated['gift_message_title'] ?? null,
                    'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                    'gift_delivery_at' => (($validated['gift_delivery_option'] ?? null) === 'send_later') ? ($validated['gift_delivery_at'] ?? null) : null,
                    'sender_first_name' => $validated['sender_first_name'] ?? null,
                    'receiver_name' => $validated['receiver_name'] ?? null,
                    'receiver_email' => $validated['receiver_email'] ?? null,
                    'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                    'receiver_msg' => $validated['receiver_msg'] ?? null,
                ]);
                $order->save();

                return $order;
            }

            return $this->createOrderForCheckout($productLocked, $userId, $validated);
        });
    }

    private function assertDenominationValidForProduct(Product $product, float $denomination): void
    {
        $priceData = (array) $product->price;
        $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

        if ($priceType === 'SLAB') {
            $denominations = array_map(static fn ($value): string => (string) $value, (array) ($priceData['denominations'] ?? []));
            if (! in_array((string) $denomination, $denominations, true)) {
                throw ValidationException::withMessages([
                    'denomination' => 'Invalid denomination value. Allowed values are: '.implode(', ', $denominations),
                ]);
            }

            return;
        }

        $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : (float) ($product->minPrice ?? 0);
        $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : (float) ($product->maxPrice ?? 0);
        if ($denomination < $minPrice || $denomination > $maxPrice) {
            throw ValidationException::withMessages([
                'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.",
            ]);
        }
    }

    private function assertSkuMonthlyLimit(Product $product, int $userId, float $orderAmount): void
    {
        $monthlyPurchaseLimit = $product->sku_limits;
        if ($monthlyPurchaseLimit === null || $monthlyPurchaseLimit === '') {
            return;
        }

        $lockedOrders = Order::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('sku', $product->sku)
            ->where('order_status', 'COMPLETE')
            ->lockForUpdate()
            ->get();

        $totalPurchasesThisMonth = (float) $lockedOrders->sum('grand_payable_amount');
        $remainingLimit = (float) $monthlyPurchaseLimit - $totalPurchasesThisMonth;

        if ($remainingLimit <= 0) {
            throw ValidationException::withMessages([
                'message' => "You have exceeded your monthly purchase limit of ₹{$monthlyPurchaseLimit}.",
            ]);
        }

        if ($orderAmount > $remainingLimit) {
            throw ValidationException::withMessages([
                'message' => "The remaining purchase limit is ₹{$remainingLimit}, but your order total is ₹{$orderAmount}.",
            ]);
        }
    }
}

