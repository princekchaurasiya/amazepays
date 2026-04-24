<?php

namespace App\Http\Controllers;

use App\Enums\ResponseCode;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

class UnlimitCallbackController extends Controller
{
    public function __construct(
        private PaymentService $payments,
    ) {}

    /**
     * 🔹 UNLIMIT CALLBACK HANDLER
     * This is server-to-server callback (not browser return URL)
     */
    public function handle(Request $request)
    {
        $payload = [
            'merchant_order' => $request->input('merchant_order'),
            'payment_data' => $request->input('payment_data'),
        ];

        $merchantOrderId = (string) (data_get($payload, 'merchant_order.id') ?? '');
        if ($merchantOrderId === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'payments.order_id_required', httpStatus: 400);
        }

        $result = $this->payments->handleGatewayCallback('unlimit', $payload);

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'unlimit');

        return ResponsePayload::ok(null, [
            'payment_status' => $result->status,
            'success' => $result->success,
        ]);
    }
}
