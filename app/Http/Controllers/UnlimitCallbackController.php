<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;
use Exception;

class UnlimitCallbackController extends Controller
{
    /**
     * 🔹 UNLIMIT CALLBACK HANDLER
     * This is server-to-server callback (not browser return URL)
     */
    public function handle(Request $request)
    {
        Log::info('🟢 Unlimit callback received', [
            'headers'  => $request->headers->all(),
            'payload'  => $request->all()
        ]);

        try {
            $payload = $request->all();

            // Extract safe values
            $merchantOrderId = $payload['merchant_order']['id'] ?? null;
            $paymentId       = $payload['payment_data']['id'] ?? null;
            $rawStatus       = $payload['payment_data']['status'] ?? null;
            $currency        = $payload['payment_data']['currency'] ?? 'INR';

            Log::info("Raw status from callback", [
                'merchant_order_id' => $merchantOrderId,
                'payment_id'        => $paymentId,
                'raw_status'        => $rawStatus,
            ]);

            if (!$merchantOrderId || !$paymentId) {
                Log::error("❌ Missing merchant_order_id or payment_id", $payload);
                return response()->json(['error' => 'Invalid callback'], 400);
            }

            // Normalize status
            $status = strtolower($rawStatus);
            if ($status === 'completed') {
                $status = 'success';
            }

            // Fetch QsOrder (used later in Woohoo processing)
            $order = QsOrder::where('merchant_order_id', $merchantOrderId)->first();

            if (!$order) {
                Log::warning("⚠ No QsOrder found for merchant_order_id", [
                    'merchant_order_id' => $merchantOrderId,
                ]);
            }

            /**
             * ====================================================
             *   UPDATE OR CREATE PAYMENT ENTRY (NO DUPLICATES)
             * ====================================================
             */

            $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

            if ($payment) {

                // Update ONLY these fields (no user_id, no order_id overwrite)
                $payment->tracking_id     = $paymentId;
                $payment->payment_status  = $status;
                $payment->status_message  = json_encode($payload);
                $payment->currency        = $currency;
                $payment->updated_at      = now();
                

                $payment->save();

                Log::info("💾 Payment updated", [
                    'id' => $payment->id,
                    'merchant_order_id' => $merchantOrderId
                ]);

            } else {

            $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

            if (!$payment) {
                Log::error("No existing payment found for merchant_order_id in callback", [
                    'merchant_order_id' => $merchantOrderId
                ]);
                return response()->json(['error' => 'Payment mismatch'], 400);
            }

            $woohooAmount = $order->denomination;

            $payment->update([
                'tracking_id'    => $paymentId,
                'currency'       => $currency,
                'amount'         => $woohooAmount,
                'payment_status' => $status,
                'status_message' => json_encode($payload),
            ]);

            }

            /**
             * ====================================================
             *   IF PAYMENT SUCCESS → Prepare Woohoo Order
             * ====================================================
             */
            if ($status === 'success' && $order) {

                // mark order as paid
                $order->order_status = 'success';
                $order->save();

                Log::info("🎉 Order marked as PAID", [
                    'order_id' => $order->id,
                    'merchant_order_id' => $merchantOrderId
                ]);
            }

            return response()->json(['status' => 'OK'], 200);

        } catch (Exception $e) {
            Log::error("❌ Failed to process Unlimit callback", [
                'error'              => $e->getMessage(),
                'merchant_order_id'  => $request->input('merchant_order.id')
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}

