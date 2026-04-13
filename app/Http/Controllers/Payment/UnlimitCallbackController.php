<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Unlimit payment gateway webhook callbacks.
 */
class UnlimitCallbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->only(['payment_data', 'merchant_order']);

        $result = $this->paymentService->handleGatewayCallback('unlimit', $payload);

        return $this->ok('Webhook processed.', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
