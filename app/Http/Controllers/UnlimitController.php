<?php

namespace App\Http\Controllers;

use App\Models\QsOrder;
use App\Models\UnlimitPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnlimitController extends Controller
{
    /**
     * Step 1: Checkout → Create Order & Payment
     */
    public function checkout(Request $request)
    {
        // Validate input
        $request->validate([
            'billing_name' => 'required|string',
            'billing_email' => 'required|email',
            'billing_tel' => 'required|string',
            'billing_address' => 'required|string',
            'billing_city' => 'required|string',
            'billing_state' => 'required|string',
            'billing_zip' => 'required|string',
            'billing_country' => 'required|string',
            'denomination' => 'required|numeric',
            'sku' => 'required|string',
        ]);

        $merchantOrderId = (string) Str::uuid();

        // Create QsOrder
        $order = QsOrder::create([
            'woohoo_order_id' => 'WH-' . Str::upper(Str::random(6)),
            'order_status' => 'INITIATED',
            'denomination' => $request->denomination,
            'sender_first_name' => $request->billing_name,
            'sender_email' => $request->billing_email,
            'sender_phone_no' => $request->billing_tel,
            'sender_address_1' => $request->billing_address,
            'sender_city' => $request->billing_city,
            'sender_state' => $request->billing_state,
            'sender_post_code' => $request->billing_zip,
            'sku' => $request->sku,
            'grand_payable_amount' => $request->denomination,
            'amount_payable_after_discount' => $request->denomination, // no discount applied
            'currency' => 'INR',
            'merchant_order_id' => $merchantOrderId,
        ]);

        // Create UnlimitPayment record
        $payment = UnlimitPayment::create([
            'user_id' => $request->user()->id ?? null,
            'order_id' => $order->id,
            'amount' => $request->denomination,
            'currency' => 'INR',
            'billing_name' => $request->billing_name,
            'billing_email' => $request->billing_email,
            'billing_tel' => $request->billing_tel,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_zip' => $request->billing_zip,
            'billing_country' => $request->billing_country,
            'merchant_order_id' => $merchantOrderId,
        ]);

        Log::info("Checkout merchant_order_id", ['id' => $merchantOrderId]);

        // Send request to Unlimit
        $response = Http::post('https://sandbox.in.unlimit.com/payment/request', [
            'merchant_order' => [
                'id' => $merchantOrderId,
                'description' => 'Unlimit transaction'
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => $request->denomination,
                'currency' => 'INR',
            ],
            'return_urls' => [
                'success_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'status' => '{status}']),
                'decline_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'payment_id' => '{payment_id}', 'status' => '{status}']),
            ],
            'callback_url' => route('unlimit.callback'),
        ]);

        UnlimitPayment::create([
    'merchant_order_id' => $merchantOrderId,
    'user_id' => auth()->id(),
    'amount' => $payableAmount,
    'status' => 'pending'
        ]);

        Log::info("UNLIMIT API RESPONSE", $response->json());

        $redirectUrl = $response->json('redirect_url');

        return redirect($redirectUrl);
    }

    /**
     * Step 2: Unlimit Callback
     */
    public function callback(Request $request)
    {
        $payload = $request->all();
        Log::info('Unlimit callback received', ['payload' => $payload]);

        $merchantOrderId = data_get($payload, 'merchant_order.id');
        $paymentId = data_get($payload, 'payment_data.id');
        $status = data_get($payload, 'payment_data.status');
        $amount = data_get($payload, 'payment_data.amount');

        // Update Payment record
        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();
        if ($payment) {
            $payment->update([
                'payment_status' => $status,
                'amount' => $amount,
                'raw_callback' => json_encode($payload),
            ]);
        }

        // Update Order status if payment is completed
        $order = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
        if ($order && $status === 'COMPLETED') {
            $order->update(['order_status' => 'PAID']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Step 3: Return URL after Payment
     */
   public function return(Request $request)
{
    // Prefer query param but fallback to POST
    $merchantOrderId = $request->query('merchant_order_id') ?? $request->input('merchant_order_id');
    $status = $request->query('status') ?? $request->input('status', 'unknown');

    if (!$merchantOrderId) {
        abort(404, 'Merchant order ID missing');
    }

    $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->latest()->first();
    $order = QsOrder::where('merchant_order_id', $merchantOrderId)->first();

    if (!$payment || !$order) {
        abort(404, 'Payment or order not found');
    }

    $amount = $order->grand_payable_amount ?? 0;

    return view('woohoo.processing-woohoo', [
        'payment_id' => $payment->id,
        'order_id' => $order->id,
        'amount' => $amount,
        'status' => strtoupper($status),
    ]);
}

    /**
     * Step 4: Check Payment Status
     */
    public function checkTransactionStatus($merchantOrderId)
    {
        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (!$payment) {
            return ['status' => 'NOT_FOUND'];
        }

        return [
            'payment_id' => $payment->id,
            'merchant_order_id' => $payment->merchant_order_id,
            'status' => $payment->payment_status,
            'amount' => $payment->amount,
        ];
    }
}
