<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;
use App\Http\Controllers\WoohooOrderController;

class UnlimitCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $rawBody = $request->getContent();
        $payload = [];

        try {
            $payload = $rawBody ? json_decode($rawBody, true) ?: [] : [];
        } catch (\Throwable $e) {
            $payload = [];
        }

        Log::info('Unlimit callback received', [
            'headers' => $request->headers->all(),
            'payload' => $payload,
        ]);

        $merchantOrderId = data_get($payload, 'merchant_order.id')
            ?? data_get($payload, 'merchantOrder.id')
            ?? $request->input('merchant_order_id');

        $paymentId = data_get($payload, 'payment_data.id')
            ?? data_get($payload, 'payment.id')
            ?? data_get($payload, 'paymentId')
            ?? $request->input('payment_id');

        $status = data_get($payload, 'payment_data.status')
            ?? data_get($payload, 'result')
            ?? data_get($payload, 'status')
            ?? data_get($payload, 'transaction.status')
            ?? data_get($payload, 'payment.status')
            ?? data_get($payload, 'payment_status')
            ?? data_get($payload, 'transaction_status')
            ?? null;

        // Extract status from multiple possible locations in Unlimit callback
        $status = $payload['result']
            ?? $payload['status'] 
            ?? $payload['transaction']['status'] 
            ?? $payload['payment']['status']
            ?? $payload['payment_status']
            ?? $payload['transaction_status']
            ?? null;
            
        // If still no status, check transactions array
        if (!$status && !empty($payload['transactions']) && is_array($payload['transactions'])) {
            $firstTx = $payload['transactions'][0] ?? [];
            $status = is_array($firstTx) ? ($firstTx['status'] ?? null) : null;
        }

        if (!$status) {
            foreach ($payload as $value) {
                if (is_array($value) && isset($value['status'])) {
                    $status = $value['status'];
                    break;
                }
            }
        }

        if (empty($status)) {
            $successIndicators = ['success', 'approved', 'completed', 'processed'];
            $failureIndicators = ['declined', 'failed', 'error', 'cancelled'];
            foreach ($payload as $value) {
                if (is_string($value)) {
                    $lower = strtolower($value);
                    if (in_array($lower, $successIndicators) || in_array($lower, $failureIndicators)) {
                        $status = $lower;
                        break;
                    }
                }
            }
            if (empty($status)) {
                $status = 'pending';
                Log::warning('No status found in Unlimit callback, defaulting to pending', [
                    'merchant_order_id' => $merchantOrderId,
                    'payload' => $payload,
                ]);
            }
        }
        
        // If still no status, check for any nested status fields
        if (!$status) {
            foreach ($payload as $key => $value) {
                if (is_array($value) && isset($value['status'])) {
                    $status = $value['status'];
                    break;
                }
            }
        }

        // Log the raw status for debugging
        Log::info('Raw status from Unlimit callback', [
            'raw_status' => $status,
            'merchant_order_id' => $merchantOrderId,
            'payment_id' => $paymentId,
            'payload_keys' => array_keys($payload),
            'full_payload' => $payload
        ]);

        // If status is still null, try to infer from other fields
        if (empty($status)) {
            // Check if there are any success indicators in the payload
            $successIndicators = ['success', 'approved', 'completed', 'processed'];
            $failureIndicators = ['declined', 'failed', 'error', 'cancelled'];
            
            foreach ($payload as $key => $value) {
                if (is_string($value) && in_array(strtolower($value), $successIndicators)) {
                    $status = $value;
                    break;
                } elseif (is_string($value) && in_array(strtolower($value), $failureIndicators)) {
                    $status = $value;
                    break;
                }
            }
            
            // If still no status found, default to pending
            if (empty($status)) {
                $status = 'pending';
                Log::warning('No status found in Unlimit callback, defaulting to pending', [
                    'payload' => $payload,
                    'merchant_order_id' => $merchantOrderId
                ]);
            }
        }

        $normalizedStatus = match (strtolower((string) $status)) {
            'success', 'approved', 'processed', 'completed' => 'completed',
            'declined', 'failed', 'error' => 'declined',
            'pending', 'in_progress', 'processing' => 'pending',
            default => strtolower((string) $status),
        };

        if (empty($merchantOrderId)) {
            Log::warning('Unlimit callback missing merchant_order.id', ['payload' => $payload]);
            return response()->json(['error' => 'merchant_order.id missing'], 400);
        }

        try {
            DB::transaction(function () use ($merchantOrderId, $paymentId, $normalizedStatus, $payload) {
                $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();

                // Fallback: if order not yet linked with merchant_order_id, try via payment tracking_id
                if (!$qsOrder && $paymentId) {
                    $paymentByTrack = UnlimitPayment::where('tracking_id', $paymentId)->first();
                    if ($paymentByTrack) {
                        $qsOrder = QsOrder::find($paymentByTrack->order_id);
                        if ($qsOrder && empty($qsOrder->merchant_order_id)) {
                            $qsOrder->merchant_order_id = $merchantOrderId;
                            $qsOrder->save();

                            Log::info('Linked merchant_order_id to QsOrder via tracking_id', [
                                'qs_order_id' => $qsOrder->id,
                                'merchant_order_id' => $merchantOrderId,
                            ]);
                        }
                    }
                }

                if (!$qsOrder) {
                    Log::warning('No QsOrder found or linkable for merchant_order_id', ['merchant_order_id' => $merchantOrderId]);
                    return;
                }

                $merchantOrderId = $payload['merchant_order']['id'] ?? null;
                $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

                if ($payment) {
                    $payment->order_status = $normalizedStatus;
                    $payment->payment_status = $payload['payment_data']['status'] ?? 'unknown'; // ✅ Updated
                    $payment->tracking_id = $paymentId;
                    $payment->currency = $payment->currency ?: ($payload['payment_data']['currency'] ?? $payload['payment']['currency'] ?? null);
                    $payment->amount = $qsOrder->grand_payable_amount ?? ($payload['payment_data']['amount'] ?? null);
                    $payment->unlimit_response = json_encode($payload);
                    $payment->save();

                    Log::info('UnlimitPayment updated from callback', [
                        'payment_id' => $payment->id,
                        'payment_status' => $payment->payment_status,
                        'merchant_order_id' => $merchantOrderId,
                    ]);
                } else {
                    Log::warning('No UnlimitPayment found for QsOrder', ['qs_order_id' => $qsOrder->id]);
                }

                $qsOrder->order_status = match ($normalizedStatus) {
                    'completed' => 'Success',
                    'declined' => 'Failed',
                    default => 'Pending',
                };
                $qsOrder->save();

                Log::info('QsOrder status updated', [
                    'qs_order_id' => $qsOrder->id,
                    'new_status' => $qsOrder->order_status,
                ]);
            });

            // After transaction, re-fetch order and payment directly
            $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
            $payment = $qsOrder ? UnlimitPayment::where('order_id', $qsOrder->id)->first() : null;

 if ($payment) {
                $normalizedStatus = strtoupper(trim((string) $status));
    if (in_array($normalizedStatus, ['SUCCESS', 'APPROVED'])) {
        $normalizedStatus = 'COMPLETED';
    }

    $payment->payment_status = $normalizedStatus;
    $payment->save();

    Log::info('✅ Payment status updated', [
        'order_id' => $qsOrder->id ?? null,
        'merchant_order_id' => $merchantOrderId,
        'payment_status' => $normalizedStatus,
    ]);

    // ✅ Trigger Woohoo order only when payment COMPLETED
    if ($normalizedStatus === 'COMPLETED') {
        if ($qsOrder && !$qsOrder->woohoo_order_id) {

            // Ensure refno is generated if missing
            if (empty($qsOrder->refno)) {
                $qsOrder->refno = 'Amz' . $qsOrder->id;
                $qsOrder->save();
            }

            try {
                Log::info('🚀 Triggering Woohoo order creation', [
                    'qs_order_id' => $qsOrder->id,
                    'merchant_order_id' => $merchantOrderId,
                    'payment_status' => $normalizedStatus,
                ]);
                
                $woohooController = new WoohooOrderController();
                $woohooResult = $woohooController->createWoohooOrderRequest($qsOrder,$normalizedStatus);

                            if (!empty($woohooResult['success'])) {
                                Log::info('Woohoo order created successfully', [
                                    'merchant_order_id' => $merchantOrderId,
                                    'response' => $woohooResult,
                                ]);
                            } else {
                                Log::warning('Woohoo order creation failed', [
                                    'merchant_order_id' => $merchantOrderId,
                                    'response' => $woohooResult,
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('Exception during Woohoo order creation', [
                                'error' => $e->getMessage(),
                                'merchant_order_id' => $merchantOrderId,
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to process Unlimit callback', [
                'error' => $e->getMessage(),
                'merchant_order_id' => $merchantOrderId,
            ]);
            return response()->json(['status' => 'ok'], 200);
        }

        return response()->json(['status' => 'ok']);
    }
}
