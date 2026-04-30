<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderBillingSnapshot;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
                    'message' => __('error.unknown'),
                ]);
            }

            $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
            $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
            if ($totalPayableAmountAfterDiscount < 0) {
                throw ValidationException::withMessages([
                    'message' => __('error.unknown'),
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

            $orderAttrs = [
                'tenant_id' => $this->resolveTenantId(),
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'channel' => 'storefront',
                'status' => 'created',
                'subtotal_minor' => $subtotalMinor,
                'discount_total_minor' => $discountMinor,
                'tax_total_minor' => 0,
                'grand_total_minor' => $grandTotalMinor,
                'currency' => 'INR',
            ];

            // Schema-safe fields (older DBs may not have these columns).
            foreach ([
                'product_id' => (int) $productLocked->id,
                'sku' => (string) ($productLocked->sku ?? ''),
                'quantity' => $quantity,
                'denomination' => $denomination,
                'gift_send_option' => (string) ($validated['gift_send_option'] ?? 'buy_for_self'),
                'receiver_name' => $validated['receiver_name'] ?? null,
                'receiver_email' => $validated['receiver_email'] ?? null,
                'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                'receiver_msg' => $validated['receiver_msg'] ?? null,
                'delivery_mode' => (string) ($validated['delivery_mode'] ?? 'both'),
                'gift_theme_id' => $validated['gift_theme_id'] ?? null,
                'gift_message_title' => $validated['gift_message_title'] ?? null,
                'sender_first_name' => $validated['sender_first_name'] ?? null,
                'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                'gift_delivery_at' => $validated['gift_delivery_at'] ?? null,
            ] as $col => $val) {
                if (Schema::hasColumn('orders', $col)) {
                    $orderAttrs[$col] = $val;
                }
            }

            $order = Order::query()->create($orderAttrs);

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
                'gift_theme_id' => $validated['gift_theme_id'] ?? null,
                'gift_send_option' => (string) ($validated['gift_send_option'] ?? 'buy_for_self'),
                'gift_message_title' => $validated['gift_message_title'] ?? null,
                'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                'gift_delivery_at' => $validated['gift_delivery_at'] ?? null,
                'sender_first_name' => $validated['sender_first_name'] ?? null,
                'receiver_name' => $validated['receiver_name'] ?? null,
                'receiver_email' => $validated['receiver_email'] ?? null,
                'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                'receiver_msg' => $validated['receiver_msg'] ?? null,
            ]);

            // Phase-3 schema: persist immutable billing snapshot (required for downstream providers like Woohoo).
            if (Schema::hasTable('order_billing_snapshots')) {
                OrderBillingSnapshot::query()->updateOrCreate(
                    ['order_id' => (int) $order->id],
                    [
                        'full_name' => (string) ($validated['billing_name'] ?? 'Customer'),
                        'email' => (string) ($validated['billing_email'] ?? ''),
                        'phone' => (string) ($validated['billing_tel'] ?? ''),
                        'line1' => (string) ($validated['billing_address'] ?? '-'),
                        'line2' => $validated['billing_address_two'] ?? null,
                        'city' => (string) ($validated['billing_city'] ?? '-'),
                        'state' => (string) ($validated['billing_state'] ?? '-'),
                        'postal_code' => (string) ($validated['billing_zip'] ?? '000000'),
                        'country' => (string) ($validated['billing_country'] ?? 'IN'),
                        'gst_number' => $validated['billing_gst_number'] ?? null,
                    ]
                );
            }

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
                    'message' => __('error.unknown'),
                ]);
            }

            $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
            $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
            if ($totalPayableAmountAfterDiscount < 0) {
                throw ValidationException::withMessages([
                    'message' => __('error.unknown'),
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
                $update = [
                    'subtotal_minor' => $subtotalMinor,
                    'discount_total_minor' => $discountMinor,
                    'tax_total_minor' => 0,
                    'grand_total_minor' => $grandTotalMinor,
                    'currency' => 'INR',
                ];

                foreach ([
                    'product_id' => (int) $productLocked->id,
                    'sku' => (string) ($productLocked->sku ?? ''),
                    'quantity' => $quantity,
                    'denomination' => $denomination,
                    'gift_send_option' => (string) ($validated['gift_send_option'] ?? 'buy_for_self'),
                    'receiver_name' => $validated['receiver_name'] ?? null,
                    'receiver_email' => $validated['receiver_email'] ?? null,
                    'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                    'receiver_msg' => $validated['receiver_msg'] ?? null,
                    'delivery_mode' => (string) ($validated['delivery_mode'] ?? 'both'),
                    'gift_theme_id' => $validated['gift_theme_id'] ?? null,
                    'gift_message_title' => $validated['gift_message_title'] ?? null,
                    'sender_first_name' => $validated['sender_first_name'] ?? null,
                    'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                    'gift_delivery_at' => $validated['gift_delivery_at'] ?? null,
                ] as $col => $val) {
                    if (Schema::hasColumn('orders', $col)) {
                        $update[$col] = $val;
                    }
                }

                $order->fill($update);
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
                    'gift_theme_id' => $validated['gift_theme_id'] ?? null,
                    'gift_send_option' => (string) ($validated['gift_send_option'] ?? 'buy_for_self'),
                    'gift_message_title' => $validated['gift_message_title'] ?? null,
                    'gift_delivery_option' => $validated['gift_delivery_option'] ?? null,
                    'gift_delivery_at' => $validated['gift_delivery_at'] ?? null,
                    'sender_first_name' => $validated['sender_first_name'] ?? null,
                    'receiver_name' => $validated['receiver_name'] ?? null,
                    'receiver_email' => $validated['receiver_email'] ?? null,
                    'receiver_mobile' => $validated['receiver_mobile'] ?? null,
                    'receiver_msg' => $validated['receiver_msg'] ?? null,
                ]);

                if (Schema::hasTable('order_billing_snapshots')) {
                    OrderBillingSnapshot::query()->updateOrCreate(
                        ['order_id' => (int) $order->id],
                        [
                            'full_name' => (string) ($validated['billing_name'] ?? 'Customer'),
                            'email' => (string) ($validated['billing_email'] ?? ''),
                            'phone' => (string) ($validated['billing_tel'] ?? ''),
                            'line1' => (string) ($validated['billing_address'] ?? '-'),
                            'line2' => $validated['billing_address_two'] ?? null,
                            'city' => (string) ($validated['billing_city'] ?? '-'),
                            'state' => (string) ($validated['billing_state'] ?? '-'),
                            'postal_code' => (string) ($validated['billing_zip'] ?? '000000'),
                            'country' => (string) ($validated['billing_country'] ?? 'IN'),
                            'gst_number' => $validated['billing_gst_number'] ?? null,
                        ]
                    );
                }

                return $order;
            }

            return $this->createOrderForCheckout($productLocked, $userId, $validated);
        });
    }

    private function assertDenominationValidForProduct(Product $product, float $denomination): void
    {
        $priceData = $product->resolveStorefrontPrice();
        $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

        if ($priceType === 'SLAB') {
            $denominations = array_map('floatval', (array) ($priceData['denominations'] ?? []));
            if (! in_array((float) $denomination, $denominations, true)) {
                throw ValidationException::withMessages([
                    'denomination' => 'Invalid denomination value. Allowed values are: '.implode(', ', array_map(fn ($d) => '₹'.(float) $d, $denominations)),
                ]);
            }

            return;
        }

        $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : 0.0;
        $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : 0.0;
        if ($minPrice <= 0 || $maxPrice <= 0 || $maxPrice < $minPrice) {
            throw ValidationException::withMessages([
                'denomination' => 'Price range not configured for this product.',
            ]);
        }
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

    private function resolveTenantId(): int
    {
        if (app()->bound('current_tenant_id')) {
            return (int) app('current_tenant_id');
        }

        $tenant = request()->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        if (Schema::hasTable('tenants')) {
            return (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
        }

        return 1;
    }
}
