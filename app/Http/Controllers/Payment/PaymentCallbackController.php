<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

/**
 * Single webhook controller for all payment gateways.
 *
 * URLs remain unchanged; only controller wiring is consolidated.
 */
final class PaymentCallbackController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PaymentService $payments) {}

    public function unlimit(Request $request): ResponsePayload
    {
        $payload = $request->json()->all() ?: $request->input();
        $result = $this->payments->handleGatewayCallback('unlimit', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'unlimit');

        return $this->ok('payments.webhook_received', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }

    public function ccavenue(Request $request): ResponsePayload
    {
        $payload = $request->only(['encResp', 'orderNo']);
        $result = $this->payments->handleGatewayCallback('ccavenue', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'ccavenue');

        return $this->ok('payments.webhook_received', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }

    public function razorpay(Request $request): ResponsePayload
    {
        $payload = $request->json()->all() ?: $request->input();
        $result = $this->payments->handleGatewayCallback('razorpay', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'razorpay');

        return $this->ok('payments.webhook_received', [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }

    public function upi(Request $request): ResponsePayload
    {
        // UPI flows initiated via Unlimit share the same signature scheme.
        return $this->unlimit($request);
    }
}

