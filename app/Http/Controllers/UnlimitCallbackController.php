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
        $paymentId = $payload['payment_data']['id']
            ?? ($payload['payment']['id']
            ?? ($payload['paymentId'] ?? $request->input('payment_id')));

        // Extract status from multiple possible locations in Unlimit callback
        $status = $payload['payment_data']['status']
            ?? $payload['result']
            ?? $payload['status'] 
            ?? $payload['transaction']['status'] 
            ?? $payload['payment']['status']
            ?? $payload['payment_status']
            ?? $payload['transaction_status']
            ?? null;
            
        // If still no status, check transactions array
        if (!$status && !empty($payload['transactions']) && is_array($payload['transactions'])) {
            $status = $payload['transactions'][0]['status'] ?? null;
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
                // First find the QsOrder by merchant_order_id
                $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
                
                if ($qsOrder) {
                    Log::info('Found QsOrder for callback', [
                        'qs_order_id' => $qsOrder->id,
                        'merchant_order_id' => $merchantOrderId,
                        'current_status' => $qsOrder->order_status
                    ]);
                    
                    // Update UnlimitPayment using QsOrder's internal ID
                    $payment = UnlimitPayment::where('order_id', $qsOrder->id)->first();
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
                        
                        Log::info('Updated UnlimitPayment with callback data', [
                            'payment_id' => $payment->id,
                            'status' => $normalizedStatus,
                            'tracking_id' => $paymentId
                        ]);
                    } else {
                        Log::warning('No UnlimitPayment found for QsOrder', [
                            'qs_order_id' => $qsOrder->id
                        ]);
                    }
                    
                    // Update QsOrder status
                    if (isset($qsOrder->order_status)) {
                        $qsOrder->order_status = in_array($normalizedStatus, ['approved', 'completed']) ? 'Success' : ($normalizedStatus === 'declined' ? 'Failed' : 'Pending');
                    }
                    $qsOrder->save();
                    
                    Log::info('Updated QsOrder status', [
                        'qs_order_id' => $qsOrder->id,
                        'new_status' => $qsOrder->order_status
                    ]);
                } else {
                    Log::warning('No QsOrder found for merchant_order_id', [
                        'merchant_order_id' => $merchantOrderId
                    ]);
                }
            });
            
            // After transaction commits, check if we need to create Woohoo order
            try {
                if (in_array($normalizedStatus, ['approved', 'completed'])) {
                    $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
                    if ($qsOrder && !$qsOrder->woohoo_order_id) {
                        Log::info('Payment completed, triggering Woohoo order creation', [
                            'merchant_order_id' => $merchantOrderId,
                            'qs_order_id' => $qsOrder->id,
                            'current_refno' => $qsOrder->refno
                        ]);
                        
                        // Ensure refno is set (needed by Woohoo order creation)
                        if (empty($qsOrder->refno)) {
                            $qsOrder->refno = 'Amz' . $qsOrder->id;
                            $qsOrder->save();
                            Log::info('Set refno for QsOrder', [
                                'qs_order_id' => $qsOrder->id,
                                'refno' => $qsOrder->refno
                            ]);
                        }
                        
                        $woohooController = new WoohooOrderController();
                        $woohooResult = $woohooController->createWoohooOrderRequest($qsOrder);
                        
                        if ($woohooResult && isset($woohooResult['success']) && $woohooResult['success']) {
                            Log::info('Woohoo order created successfully via callback', [
                                'merchant_order_id' => $merchantOrderId,
                                'woohoo_response' => $woohooResult
                            ]);
                        } else {
                            Log::warning('Woohoo order creation returned unsuccessful result', [
                                'merchant_order_id' => $merchantOrderId,
                                'woohoo_result' => $woohooResult
                            ]);
                        }
                    } else {
                        Log::info('Woohoo order already exists or payment not completed', [
                            'qs_order_exists' => $qsOrder ? 'yes' : 'no',
                            'woohoo_order_id' => $qsOrder ? $qsOrder->woohoo_order_id : 'N/A'
                        ]);
                    }
                }
            } catch (\Throwable $woohooError) {
                Log::error('Failed to create Woohoo order via callback', [
                    'merchant_order_id' => $merchantOrderId,
                    'error' => $woohooError->getMessage(),
                    'trace' => $woohooError->getTraceAsString()
                ]);
                // Don't throw - we've logged the payment as successful
            }
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
