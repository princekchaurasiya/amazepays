<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\QsOrder;
use App\Http\Controllers\WoohooOrderController;
use App\Models\UnlimitPayment;
use App\Models\Billing;

class WoohooProcessingController extends Controller
{

   public function handleReturn(Request $request)
{
    Log::info("🔄 Unlimit Return Hit", [
        'query'    => $request->query(),
        'full_url' => $request->fullUrl(),
    ]);

    $merchantOrderId = $request->query('merchant_order_id');
    $status          = $request->query('status');

    if (!$merchantOrderId) {
        Log::error("❌ Missing merchant_order_id in return URL");
        return "Invalid return data.";
    }

    // Fetch payment from DB
    $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

    if (!$payment) {
        Log::error("❌ No payment found for merchant_order_id (return)", [
            'merchant_order_id' => $merchantOrderId
        ]);
        return "Unable to verify payment. Please contact support.";
    }

    // Update payment status
    $payment->payment_status = $status;
    $payment->save();

    Log::info("✅ Payment updated via RETURN page", [
        'merchant_order_id' => $merchantOrderId,
        'status' => $status
    ]);

    // Redirect user to Woohoo/Success UI
    return view("woohoo.processing-woohoo", [
        "amount" => $payment->amount,
        "merchant_order_id" => $merchantOrderId,
        "status" => $status
    ]);
}
    /**
     * Process the Woohoo order creation after successful payment
     */
    public function createOrder(Request $request)
    {
        try {
            // 1️⃣ Find the latest Unlimit payment for this user
            $payment = UnlimitPayment::where('user_id', auth()->id())
                ->orderBy('id', 'DESC')
                ->first();

            if (!$payment) {
                return redirect()->route('my-order')
                    ->with('error', 'No payment found. Please contact support.');
            }

            $merchantOrderId = $payment->merchant_order_id;
            $status = strtolower($payment->payment_status);

            Log::info("Processing Woohoo order creation", [
                'merchant_order_id' => $merchantOrderId,
                'status'            => $status,
            ]);

            // 2️⃣ Fetch QsOrder
            $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)
                ->orderBy('id', 'ASC')
                ->first();

            if (!$qsOrder) {
                Log::error("QsOrder not found", ['merchant_order_id' => $merchantOrderId]);
                return redirect()->route('my-order')
                    ->with('error', 'Order not found. Please contact support.');
            }

            // 3️⃣ Check status
            if (!in_array($status, ['completed', 'approved', 'success'])) {
                return redirect()->route('my-order')
                    ->with('error', 'Payment not successful.');
            }

            Log::info("🔍 Checking woohoo_order_id before API call", [
                "woohoo_order_id" => $qsOrder->woohoo_order_id,
            ]);

            // 4️⃣ Enrich order with billing details (optional, used by WoohooOrderController)
            $billing = Billing::where('order_id', $qsOrder->id)->first();
            if ($billing) {
                $qsOrder->email        = $billing->billing_email ?? null;
                $qsOrder->mobile       = $billing->billing_tel ?? null;
                $qsOrder->billing_name = $billing->billing_name ?? null;
            }

            Log::info("🔥 Sending Woohoo order request", [
                'qs_order_id'       => $qsOrder->id,
                'merchant_order_id' => $qsOrder->merchant_order_id,
                'amount'            => $qsOrder->amount,
                'email'             => $qsOrder->email,
                'mobile'            => $qsOrder->mobile,
            ]);

            // 5️⃣ Create Woohoo order via dedicated method that returns an array with 'success'
            $woohoo = new WoohooOrderController();
            $result = $woohoo->createWoohooOrderRequest($qsOrder, $payment);

            // Ensure we don't trigger "Undefined array key 'success'"
            $isSuccess = is_array($result) && array_key_exists('success', $result) && $result['success'] === true;

            return view('woohoo.response', [
                'order'    => $qsOrder,
                'woohoo'   => $result,
                'vouchers' => [],
                'isSuccess'=> $isSuccess,
            ]);
        } catch (\Throwable $e) {
            Log::error("Woohoo processing error", [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('my-order')
                ->with('error', 'Unexpected error occurred.');
        }
    }


    /**
     * Show processing status page
     */
    public function showProcessing(Request $request)
    {
       /*$paymentReturnData = session('payment_return_data');
        
        if (!$paymentReturnData) {
            return redirect()->route('my-order')->with('error', 'No payment data found.');
       }

        return view('woohoo.processing-woohoo', [
            'order_id' => $paymentReturnData['order_id'] ?? 'N/A',
            'status' => $paymentReturnData['status'] ?? 'Processing',
            'amount' => $paymentReturnData['amount'] ?? 0
        ]);*/

            $merchantOrderId = $request->merchant_order_id;
            if (!$merchantOrderId) {
            return redirect()->route('my-order')->with('error', 'Missing payment reference.');
        }

        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (!$payment) {
            return redirect()->route('my-order')->with('error', 'Payment record not found.');
        }

        return view('woohoo.processing-woohoo', [
            'order_id' => $payment->order_id,
            'status' => $payment->payment_status ?? 'Processing',
            'amount' => $payment->amount ?? 0
        ]);
    }


}
