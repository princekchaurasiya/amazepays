<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;
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

    public function handle(Request $request): ResponsePayload
    {
        // Preserve full raw payload for forensics (webhooks are not user input).
        // Still avoid passing framework-added fields like files.
        $payload = $request->json()->all() ?: $request->input();

        $result = $this->paymentService->handleGatewayCallback('unlimit', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'unlimit');

        return $this->ok('payments.webhook_received', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
