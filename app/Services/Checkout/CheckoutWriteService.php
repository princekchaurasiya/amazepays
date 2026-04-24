<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

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

            $subtotalMinor = (int) round($grandPayableAmount * 100);
            $discountMinor = (int) round($discountAmount * 100);
            $grandTotalMinor = max(0, $subtotalMinor - $discountMinor);

            $recentOrder = Order::query()
                ->where('user_id', $userId)
                ->where('status', 'created')
                ->where('created_at', '>=', now()->subSeconds(5))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($recentOrder) {
                throw ValidationException::withMessages([
                    'message' => __('payments.order_processing_exists'),
                ]);
            }

            $orderNumber = 'AMZ'.now()->format('YmdHis').Str::upper(Str::random(6));

            $order = Order::query()->create([
                'tenant_id' => 1,
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'channel' => 'storefront',
                'status' => 'created',
                'subtotal_minor' => $subtotalMinor,
                'discount_total_minor' => $discountMinor,
                'tax_total_minor' => 0,
                'grand_total_minor' => $grandTotalMinor,
                'currency' => 'INR',
            ]);

            OrderItem::query()->create([
                'order_id' => (int) $order->id,
                'product_id' => (int) $productLocked->id,
                'denomination_id' => null,
                'sku_snapshot' => (string) ($productLocked->sku ?? ''),
                'name_snapshot' => (string) ($productLocked->name ?? ''),
                'quantity' => $quantity,
                'unit_amount_minor' => (int) round($denomination * 100),
                'line_subtotal_minor' => $subtotalMinor,
                'line_discount_minor' => $discountMinor,
                'line_tax_minor' => 0,
                'line_total_minor' => $grandTotalMinor,
                'currency' => 'INR',
                'fulfilment_status' => 'pending',
            ]);

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

            $subtotalMinor = (int) round($grandPayableAmount * 100);
            $discountMinor = (int) round($discountAmount * 100);
            $grandTotalMinor = max(0, $subtotalMinor - $discountMinor);

            $order = Order::query()
                ->where('user_id', $userId)
                ->where('status', 'created')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($order) {
                $order->fill([
                    'subtotal_minor' => $subtotalMinor,
                    'discount_total_minor' => $discountMinor,
                    'tax_total_minor' => 0,
                    'grand_total_minor' => $grandTotalMinor,
                    'currency' => 'INR',
                ]);
                $order->save();

                // Replace order items (single-item checkout draft).
                $order->items()->delete();
                $order->items()->create([
                    'product_id' => (int) $productLocked->id,
                    'denomination_id' => null,
                    'sku_snapshot' => (string) ($productLocked->sku ?? ''),
                    'name_snapshot' => (string) ($productLocked->name ?? ''),
                    'quantity' => $quantity,
                    'unit_amount_minor' => (int) round($denomination * 100),
                    'line_subtotal_minor' => $subtotalMinor,
                    'line_discount_minor' => $discountMinor,
                    'line_tax_minor' => 0,
                    'line_total_minor' => $grandTotalMinor,
                    'currency' => 'INR',
                    'fulfilment_status' => 'pending',
                ]);

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
