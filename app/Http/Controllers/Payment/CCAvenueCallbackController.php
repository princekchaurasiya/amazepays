<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

/**
 * Handles CCAvenue payment gateway webhook callbacks.
 * Verifies the payment, matches the amount, and updates the order status.
 */
class CCAvenueCallbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function handle(Request $request): ResponsePayload
    {
        // Preserve full raw payload for forensics (includes encResp).
        $payload = $request->only(['encResp', 'orderNo']);
        $result = $this->paymentService->handleGatewayCallback('ccavenue', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'ccavenue');

        return $this->ok('payments.webhook_received', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
