<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles CCAvenue payment gateway webhook callbacks.
 * Verifies the payment, matches the amount, and updates the order status.
 */
class CCAvenuCallbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleGatewayCallback('ccavenue', $request->only(['encResp']));

        return $this->ok('Webhook processed.', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
