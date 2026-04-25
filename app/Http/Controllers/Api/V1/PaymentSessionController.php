<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentSessionRequest;
use App\Http\Requests\Payment\VerifyPaymentSessionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

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

    public function show(Request $request, Payment $payment): ResponsePayload
    {
        if ((int) $payment->user_id !== (int) $request->user()->id) {
            return $this->notFound();
        }

        return $this->ok('payments.session_retrieved', [
            'payment' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'gateway_payment_id' => $payment->gateway_payment_id,
                'gateway_reference' => $payment->gateway_reference,
                'initiated_at' => optional($payment->initiated_at)->toISOString(),
                'captured_at' => optional($payment->captured_at)->toISOString(),
                'failed_at' => optional($payment->failed_at)->toISOString(),
                'failure_reason' => $payment->failure_reason,
            ],
        ]);
    }

    public function verify(VerifyPaymentSessionRequest $request, Payment $payment): ResponsePayload
    {
        if ((int) $payment->user_id !== (int) $request->user()->id) {
            return $this->notFound();
        }

        $validated = $request->validated();
        $tx = (string) ($validated['transaction_id']
            ?? $payment->gateway_payment_id
            ?? $payment->gateway_reference
            ?? '');

        if ($tx === '') {
            return $this->error('VALIDATION_FAILED', 'payments.missing_transaction_id', 422, [
                'transaction_id' => ['payments.missing_transaction_id'],
            ]);
        }

        $status = $this->payments->queryStatus((string) $payment->gateway, $tx);

        return $this->ok('payments.status_retrieved', [
            'payment_id' => $payment->id,
            'gateway' => $payment->gateway,
            'transaction_id' => $tx,
            'status' => $status->status,
            'paid' => $status->paid,
            'amount' => $status->amount,
            'raw' => $status->raw,
        ]);
    }
}
