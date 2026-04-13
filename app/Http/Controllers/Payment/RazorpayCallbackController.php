<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Razorpay payment gateway webhook callbacks.
 */
class RazorpayCallbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->has('event')
            ? $request->only(['event', 'payload'])
            : $request->only(['razorpay_signature', 'razorpay_payment_id', 'razorpay_order_id']);

        $result = $this->paymentService->handleGatewayCallback('razorpay', $payload);

        return $this->ok('Webhook processed.', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
