<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;
use Exception;

class UnlimitPaymentController extends Controller
{
    /**
     * 🔹 Webhook from Unlimit (server-to-server)
     */
    public function webhook(Request $request)
    {
        Log::info('🟢 Unlimit webhook received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $paymentId = $request->input('payment_id');
            $orderId = $request->input('merchant_order_id');
            $status   = strtolower($request->input('status', ''));
            $amount   = $request->input('amount');

            if (!$paymentId || !$orderId) {
                Log::warning('⚠️ Webhook missing payment_id or order_id', [
                    'payment_id' => $paymentId,
                    'order_id' => $orderId
                ]);
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Update or insert payment info
            $this->updatePaymentStatus($paymentId, $orderId, $status, $amount);

            // Optionally, trigger Woohoo order automatically
            if (in_array($status, ['success', 'approved', 'completed'])) {
                Log::info('✅ Payment successful, triggering Woohoo order creation', [
                    'merchant_order_id' => $orderId,
                    'payment_id' => $paymentId
                ]);

                try {
                    $woohooProcessor = new \App\Http\Controllers\WoohooProcessingController();
                    $woohooProcessor->createOrderFromWebhook($orderId, $paymentId);
                } catch (Exception $ex) {
                    Log::error('❌ Woohoo order creation via webhook failed', [
                        'merchant_order_id' => $orderId,
                        'error' => $ex->getMessage()
                    ]);
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            Log::error('❌ Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * 🔹 Update payment status when webhook/return is received
     */
    private function updatePaymentStatus($paymentId, $orderId, $status, $amount = null)
    {
        try {
            $payment = UnlimitPayment::where('merchant_order_id', $orderId)
                ->orWhere('order_id', $orderId)
                ->first();

            if (!$payment) {
                Log::info('🆕 Creating new payment record via webhook', [
                    'merchant_order_id' => $orderId,
                    'status' => $status
                ]);

                $payment = new UnlimitPayment();
                $payment->merchant_order_id = $orderId;
                $payment->tracking_id = $paymentId;
                $payment->amount = $amount;
                $payment->currency = 'INR';
                $payment->payment_method = 'bankcard';
                $payment->created_at = now();
            }

            $normalizedStatus = strtolower($status);

            $payment->payment_status = $normalizedStatus;
            $payment->order_status = $normalizedStatus;
            $payment->status_message = 'Updated by Unlimit webhook';
            $payment->updated_at = now();
            $payment->save();

            Log::info('💾 Payment status updated', [
                'merchant_order_id' => $orderId,
                'status' => $normalizedStatus,
                'payment_id' => $payment->id
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to update payment status', [
                'merchant_order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔹 Optional manual processing after user redirect
     */
    public function process(Request $request)
    {
        $payableAmount = $request->input('payable_amount');
        return view('payment.success', ['amount' => $payableAmount]);
    }

    /**
     * 🔹 Helper to store initial payment info before redirecting
     * SECURITY: Validates amount against order database
     */
    private function storePaymentInfo(Request $request, $orderId, $responseData)
    {
        try {
            // SECURITY: If order_id is numeric, validate amount against order
            $amount = null;
            if (is_numeric($orderId)) {
                $order = QsOrder::where('id', $orderId)->first();
                if ($order) {
                    // Use amount from database, not request
                    $amount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);
                }
            }
            
            // Fallback to request amount if order not found (for UUID-based orders)
            if ($amount === null) {
                $amount = (float) ($request->input('payable_amount') ?? 0);
            }
            
            $payment = new UnlimitPayment();
            $payment->order_id = $orderId;
            $payment->merchant_order_id = $responseData['merchant_order_id'] ?? $orderId;
            $payment->amount = $amount; // Use validated amount
            $payment->currency = 'INR';
            $payment->payment_method = 'bankcard';
            $payment->payment_status = 'pending';
            $payment->unlimit_response = json_encode($responseData);
            $payment->created_at = now();
            $payment->save();

            Log::info('💾 Payment information stored', [
                'order_id' => $orderId,
                'amount' => $amount,
                'payment_id' => $payment->id
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to store payment information', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔹 Fetch Woohoo order data for order creation
     */
    public static function getWoohooOrderData($orderId)
{
    $payment = UnlimitPayment::where('order_id', $orderId)->first();
    $qsOrder = QsOrder::find($orderId);

    if (!$payment || !$qsOrder) {
        Log::error('Unable to fetch Woohoo order data', [
            'order_id' => $orderId,
            'payment_found' => (bool) $payment,
            'order_found' => (bool) $qsOrder,
        ]);
        return null;
    }

    // ✅ Always use QsOrder total amount, not UnlimitPayment’s
    return [
        'amount' => $qsOrder->price ?? $payment->amount,
        'currency' => $qsOrder->currency ?? 'INR',
        'sku' => $qsOrder->sku ?? null,
        'qty' => $qsOrder->quantity ?? 1,
    ];
}
}
