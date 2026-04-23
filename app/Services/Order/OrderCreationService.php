<?php

namespace App\Services\Order;

use App\Data\BillingData;
use App\Data\OrderData;
use App\Data\PricingResult;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderCreationException;
use App\Exceptions\WalletFrozenException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Pricing\PricingService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates order creation -- the single entry point for placing an order.
 *
 * 1. Loads product from DB
 * 2. Computes pricing via PricingService (never trusts frontend)
 * 3. Reserves wallet funds under lock
 * 4. Persists the order record
 *
 * Voucher fulfillment and notifications are handled asynchronously by jobs.
 */
class OrderCreationService
{
    public function __construct(
        private PricingService $pricingService,
        private WalletService $walletService,
        private OrderFulfillmentOrchestrator $fulfillmentOrchestrator,
    ) {}

    /**
     * Create an order for the given user.
     *
     * @throws OrderCreationException
     * @throws InsufficientBalanceException
     * @throws WalletFrozenException when wallet payment and wallet is frozen
     */
    public function create(
        User $user,
        OrderData $orderData,
        BillingData $billingData,
    ): Order {
        $product = Product::findOrFail($orderData->productId);

        $tenant = $this->resolveTenantForOrder();

        if ($tenant) {
            $assigned = $tenant->products()
                ->where('products.id', $product->id)
                ->wherePivot('is_active', true)
                ->exists();
            if (! $assigned) {
                throw new OrderCreationException('This product is not available for your B2B account.');
            }
            if (! in_array((string) ($product->catalog_audience ?? Product::CATALOG_AUDIENCE_BOTH), [Product::CATALOG_AUDIENCE_B2B, Product::CATALOG_AUDIENCE_BOTH], true)) {
                throw new OrderCreationException('This product is not available for B2B purchase.');
            }
        }

        $pricing = $this->pricingService->calculate(
            product: $product,
            quantity: $orderData->quantity,
            denomination: $orderData->denomination,
            offerCode: $orderData->offerCode,
            user: $user,
            tenant: $tenant,
        );

        return DB::transaction(function () use ($user, $product, $orderData, $billingData, $pricing) {
            $this->enforcePurchaseLimits($user, $product, $pricing);

            $idempotencyKey = 'order-'.Str::uuid();

            if ($orderData->paymentMethod === 'wallet') {
                $this->walletService->debit(
                    user: $user,
                    amount: $pricing->grandTotal,
                    description: "Purchase: {$product->product_name}",
                    idempotencyKey: $idempotencyKey,
                    referenceType: 'order',
                );
            }

            $displayName = $product->display_name;

            $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

            $order = Order::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product_name' => $displayName,
                'order_number' => $this->generateOrderNumber(),
                'quantity' => $orderData->quantity,
                'denomination' => $orderData->denomination,
                'unit_price' => $pricing->unitPrice,
                'subtotal' => $pricing->subtotal,
                'discount_percentage' => $pricing->discountPercentage,
                'discount_amount' => $pricing->discountAmount,
                'gst_percentage' => $pricing->gstPercentage,
                'gst_amount' => $pricing->gstAmount,
                'grand_total' => $pricing->grandTotal,
                'grand_payable_amount' => $pricing->grandTotal,
                'amount' => $pricing->grandTotal,
                'payment_method' => $orderData->paymentMethod,
                'order_payment' => $orderData->paymentMethod,
                'status' => 'pending',
                'order_status' => 'PENDING',
                'offer_code' => $pricing->offerApplied,
                'gift_option' => $orderData->giftOption,
                'gift_send_option' => $orderData->giftOption,
                'receiver_name' => $orderData->receiverName,
                'receiver_email' => $orderData->receiverEmail,
                'receiver_mobile' => $orderData->receiverMobile,
                'receiver_msg' => $orderData->receiverMessage,
                'idempotency_key' => $idempotencyKey,
                ...$billingData->toArray(),
            ]);

            Log::info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'grand_total' => $pricing->grandTotal,
                'user_id' => $user->id,
            ]);

            if ($orderData->paymentMethod === 'wallet') {
                $order->update([
                    'status' => 'paid',
                    'order_status' => 'PAID',
                ]);
                $this->fulfillmentOrchestrator->fulfillPaidOrder($order->fresh());
            }

            return $order;
        });
    }

    private function resolveTenantForOrder(): ?Tenant
    {
        if (app()->bound('current_tenant') && app('current_tenant') instanceof Tenant) {
            return app('current_tenant');
        }

        return null;
    }

    /**
     * Verify payment callback amount matches the order's server-calculated grand_total.
     *
     * @throws OrderCreationException if amounts don't match
     */
    public function verifyPaymentAmount(Order $order, float $paidAmount, float $tolerance = 0.01): void
    {
        if (abs($order->grand_total - $paidAmount) > $tolerance) {
            Log::warning('Payment amount mismatch', [
                'order_id' => $order->id,
                'expected' => $order->grand_total,
                'received' => $paidAmount,
            ]);

            throw new OrderCreationException(
                "Payment amount mismatch: expected ₹{$order->grand_total}, received ₹{$paidAmount}"
            );
        }
    }

    private function enforcePurchaseLimits(User $user, Product $product, PricingResult $pricing): void
    {
        $monthlySpent = Order::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where(function ($q) {
                $q->whereNull('status')->orWhereNotIn('status', ['cancelled', 'failed']);
            })
            ->where(function ($q) {
                $q->whereNull('order_status')->orWhereNotIn('order_status', ['FAILED', 'CANCELLED']);
            })
            ->lockForUpdate()
            ->sum(DB::raw('COALESCE(grand_total, grand_payable_amount, amount, 0)'));

        $monthlyLimit = config('app.monthly_purchase_limit', 200000);

        if (($monthlySpent + $pricing->grandTotal) > $monthlyLimit) {
            throw new OrderCreationException(
                'Monthly purchase limit of '.money($monthlyLimit).' would be exceeded.'
            );
        }
    }

    private function generateOrderNumber(): string
    {
        return 'AP-'.date('Ymd').'-'.strtoupper(Str::random(6));
    }
}
