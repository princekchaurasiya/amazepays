<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;

class UnlimitCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Raw JSON body (needed for signature validation already performed by middleware)
        $rawBody = $request->getContent();
        $payload = [];
        try {
            $payload = $rawBody ? json_decode($rawBody, true) ?: [] : [];
        } catch (\Throwable $e) {
            $payload = [];
        }

        Log::info('Unlimit transaction processed callback', [
            'headers' => $request->headers->all(),
            'payload' => $payload,
        ]);

        // Unlimit request format reference: https://integration.unlimit.com/api-reference/98kz48r47q7rc-request-format
        // Typical keys: result/status, payment.id, merchant_order.id, payment_data.amount/currency, transactions[0].status

        $merchantOrderId = $payload['merchant_order']['id']
            ?? ($payload['merchantOrder']['id'] ?? $request->input('merchant_order_id'));
        $paymentId = $payload['payment']['id']
            ?? ($payload['paymentId'] ?? $request->input('payment_id'));

        // Prefer explicit result/status fields if present; otherwise look into first transaction
        $status = $payload['result']
            ?? ($payload['status'] ?? ($payload['transaction']['status'] ?? null));
        if (!$status && !empty($payload['transactions']) && is_array($payload['transactions'])) {
            $status = $payload['transactions'][0]['status'] ?? null;
        }

        // Log the raw status for debugging
        Log::info('Raw status from Unlimit callback', [
            'raw_status' => $status,
            'merchant_order_id' => $merchantOrderId,
            'payment_id' => $paymentId
        ]);

        // Normalize status to a small set used internally
        $normalizedStatus = match (strtolower((string) $status)) {
            'success', 'approved', 'processed', 'completed' => 'completed',
            'declined', 'failed', 'error' => 'declined',
            'pending', 'in_progress', 'processing' => 'pending',
            default => (empty($status) ? 'pending' : strtolower($status)),
        };

        if (empty($merchantOrderId)) {
            Log::warning('Unlimit callback missing merchant_order.id', ['payload' => $payload]);
            return response()->json(['error' => 'merchant_order.id missing'], 400);
        }

        try {
            DB::transaction(function () use ($merchantOrderId, $paymentId, $normalizedStatus, $payload) {
                // Update UnlimitPayment by our order_id (we stored merchant_order.id there when initiating)
                $payment = UnlimitPayment::where('order_id', $merchantOrderId)->first();
                if ($payment) {
                    // Map to legacy columns if present
                    if (isset($payment->order_status)) {
                        $payment->order_status = $normalizedStatus;
                    }
                    if (isset($payment->status)) {
                        $payment->status = $normalizedStatus;
                    }
                    if ($paymentId && isset($payment->tracking_id)) {
                        $payment->tracking_id = $paymentId;
                    }
                    if (isset($payment->currency) && empty($payment->currency)) {
                        $payment->currency = $payload['payment_data']['currency']
                            ?? ($payload['payment']['currency'] ?? $payment->currency);
                    }
                    if (isset($payment->amount) && empty($payment->amount)) {
                        $payment->amount = $payload['payment_data']['amount']
                            ?? ($payload['payment']['amount'] ?? $payment->amount);
                    }
                    // Best-effort store raw payload if such column exists
                    if (property_exists($payment, 'unlimit_response')) {
                        $payment->unlimit_response = json_encode($payload);
                    }
                    $payment->save();
                }

                // Update related order if we track it by merchant_order_id
                $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
                if ($qsOrder) {
                    if (isset($qsOrder->order_status)) {
                        $qsOrder->order_status = in_array($normalizedStatus, ['approved', 'completed']) ? 'Success' : ($normalizedStatus === 'declined' ? 'Failed' : 'Pending');
                    }
                    $qsOrder->save();
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to process Unlimit callback', [
                'error' => $e->getMessage(),
                'merchant_order_id' => $merchantOrderId,
            ]);
            return response()->json(['error' => 'internal'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
