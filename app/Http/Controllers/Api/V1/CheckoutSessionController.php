<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateCheckoutSessionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Services\Checkout\CheckoutWriteService;
use App\Services\Checkout\GateOrderOnKyc;
use App\Services\Checkout\ApplyLoyaltyRedemption;
use App\Services\Checkout\ResolveTax;
use App\Support\Http\ResponsePayload;

/**
 * API v1 — Checkout session creation (draft order).
 *
 * Phase 3: this replaces legacy "storePayNow" style endpoints.
 */
final class CheckoutSessionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CheckoutWriteService $checkoutWrite,
        private readonly ApplyLoyaltyRedemption $applyLoyaltyRedemption,
        private readonly ResolveTax $resolveTax,
        private readonly GateOrderOnKyc $gateOrderOnKyc,
    ) {}

    public function create(CreateCheckoutSessionRequest $request): ResponsePayload
    {
        $user = $request->user();
        $validated = $request->validated();

        $product = Product::query()->whereKey((int) $validated['product_id'])->firstOrFail();
        $order = $this->checkoutWrite->createOrRefreshDraftOrder($product, (int) $user->id, $validated);

        // Phase 3: optional loyalty redemption (applies additional discount + ledger entry).
        $this->applyLoyaltyRedemption->apply(
            $order,
            (int) $user->id,
            (int) ($validated['loyalty_points_to_redeem'] ?? 0),
        );

        // Phase 3: resolve and persist item-level tax breakdowns.
        $this->resolveTax->resolveAndPersist($order);

        // Phase 3: gate checkout on KYC policy thresholds.
        $this->gateOrderOnKyc->evaluateOrThrow($order, (int) $user->id);

        return $this->created('checkout.session_created', [
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) ($order->status ?? ''),
            'currency' => (string) ($order->currency ?? 'INR'),
            'totals' => [
                'subtotal_minor' => (int) ($order->subtotal_minor ?? 0),
                'discount_total_minor' => (int) ($order->discount_total_minor ?? 0),
                'tax_total_minor' => (int) ($order->tax_total_minor ?? 0),
                'grand_total_minor' => (int) ($order->grand_total_minor ?? 0),
            ],
        ]);
    }
}

