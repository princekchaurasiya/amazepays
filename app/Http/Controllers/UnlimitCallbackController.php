<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UnlimitPayment;
use Exception;
use Illuminate\Http\Request;

class UnlimitCallbackController extends Controller
{
    /**
     * 🔹 UNLIMIT CALLBACK HANDLER
     * This is server-to-server callback (not browser return URL)
     */
    public function handle(Request $request)
    {
        try {
            $payload = [
                'merchant_order' => $request->input('merchant_order'),
                'payment_data' => $request->input('payment_data'),
            ];

            $merchantOrderId = data_get($payload, 'merchant_order.id');
            $paymentId = data_get($payload, 'payment_data.id');
            $rawStatus = data_get($payload, 'payment_data.status');
            $currency = data_get($payload, 'payment_data.currency', 'INR');

            if (! $merchantOrderId || ! $paymentId) {
                return response()->json(['error' => 'Invalid callback'], 400);
            }

            $status = strtolower((string) $rawStatus);
            if ($status === 'completed') {
                $status = 'success';
            }

            $order = Order::where('merchant_order_id', $merchantOrderId)->first();

            $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

            if (! $payment) {
                return response()->json(['error' => 'Payment mismatch'], 400);
            }

            $payment->tracking_id = $paymentId;
            $payment->payment_status = $status;
            $payment->status_message = json_encode($payload);
            $payment->currency = $currency;
            $payment->updated_at = now();
            $payment->save();

            if ($status === 'success' && $order) {
                $order->order_status = 'success';
                $order->save();
            }

            return response()->json(['status' => 'OK'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
