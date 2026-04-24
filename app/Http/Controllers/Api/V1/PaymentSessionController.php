<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentSessionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;

/**
 * API v1 — Single entrypoint to initiate a payment.
 *
 * Phase 3: replaces per-gateway "initiate" endpoints.
 */
final class PaymentSessionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PaymentService $payments) {}

    public function create(CreatePaymentSessionRequest $request): ResponsePayload
    {
        $validated = $request->validated();

        $order = Order::query()
            ->whereKey((int) $validated['order_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $order) {
            return $this->notFound();
        }

        $init = $this->payments->initiate($order, (string) $validated['gateway'], $request->user(), [
            'method_category' => $validated['method_category'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        return $this->ok('payments.initiated', [
            'success' => $init->success,
            'redirect_url' => $init->redirectUrl,
            'payment_token' => $init->paymentToken,
            'gateway_order_id' => $init->gatewayOrderId,
            'error' => $init->error,
        ]);
    }
}

