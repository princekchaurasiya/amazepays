<?php

namespace App\Http\Controllers;

use App\Models\QsOrder;
use App\Models\UnlimitPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UnlimitController extends Controller
{
    /**
     * Step 1: Checkout → Create Order & Payment
     * SECURITY: All amounts validated against product/database
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
            'denomination' => 'required|numeric|min:0.01|max:999999',
            'sku' => 'required|string',
            'order_id' => 'nullable|integer|exists:qs_orders,id', // Optional: if order already exists
        ]);

        $denomination = (float) $request->denomination;
        $amountToPay = $denomination;
        $order = null;

        // SECURITY: If order_id provided, validate and use existing order
        if ($request->has('order_id') && $request->order_id) {
            $order = QsOrder::where('id', $request->order_id)
                ->where('user_id', $request->user()->id ?? null)
                ->first();

            if (!$order) {
                Log::error('❌ Unlimit checkout: Order not found or unauthorized', [
                    'order_id' => $request->order_id,
                    'user_id' => $request->user()->id ?? null,
                    'ip_address' => $request->ip()
                ]);
                return back()->with('error', 'Order not found or unauthorized.');
            }

            // SECURITY: Validate denomination matches order
            if (abs($order->denomination - $denomination) > 0.01) {
                Log::error('❌ Unlimit checkout: Denomination mismatch', [
                    'order_id' => $order->id,
                    'order_denomination' => $order->denomination,
                    'request_denomination' => $denomination,
                    'ip_address' => $request->ip()
                ]);
                return back()->with('error', 'Denomination mismatch. Please refresh and try again.');
            }

            // SECURITY: Use amount from database, not request
            // CRITICAL: Never fall back to denomination alone - it's only unit price, not total
            // If both amounts are null, calculate from denomination * quantity
            if ($order->amount_payable_after_discount !== null) {
                $amountToPay = (float) $order->amount_payable_after_discount;
            } elseif ($order->grand_payable_amount !== null) {
                $amountToPay = (float) $order->grand_payable_amount;
            } else {
                // Fallback: calculate from denomination * quantity (never use denomination alone)
                $quantity = (int) ($order->quantity ?? 1);
                $amountToPay = (float) ($order->denomination ?? 0) * $quantity;
                
                Log::warning('⚠️ Unlimit checkout: Using calculated amount from denomination * quantity', [
                    'order_id' => $order->id,
                    'denomination' => $order->denomination,
                    'quantity' => $quantity,
                    'calculated_amount' => $amountToPay
                ]);
            }
        } else {
            // SECURITY: Validate denomination against product if SKU provided
            if ($request->sku) {
                $product = \App\Models\QsProduct::where('sku', $request->sku)->first();
                
                if ($product) {
                    // Decode product price
                    $product->price = json_decode($product->price);
                    $priceData = (array) $product->price;
                    $priceType = $priceData['type'] ?? 'RANGE';

                    if ($priceType === 'SLAB') {
                        $denominations = $priceData['denominations'] ?? [];
                        if (!in_array((string) $denomination, $denominations)) {
                            Log::warning('Invalid SLAB denomination in Unlimit checkout', [
                                'denomination' => $denomination,
                                'allowed' => $denominations,
                                'sku' => $request->sku
                            ]);
                            return back()->withErrors([
                                'denomination' => 'Invalid denomination value. Allowed values are: ' . implode(', ', $denominations)
                            ])->withInput();
                        }
                    } elseif ($priceType === 'RANGE') {
                        $minPrice = $priceData['min'] ?? $product->minPrice ?? 0;
                        $maxPrice = $priceData['max'] ?? $product->maxPrice ?? 999999;
                        
                        if ($denomination < $minPrice || $denomination > $maxPrice) {
                            Log::warning('Denomination out of range in Unlimit checkout', [
                                'denomination' => $denomination,
                                'min' => $minPrice,
                                'max' => $maxPrice,
                                'sku' => $request->sku
                            ]);
                            return back()->withErrors([
                                'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}."
                            ])->withInput();
                        }
                    }
                }
            }

            // Create new QsOrder
            $order = QsOrder::create([
                'user_id' => $request->user()->id ?? null,
                'woohoo_order_id' => 'WH-' . Str::upper(Str::random(6)),
                'order_status' => 'INITIATED',
                'denomination' => $denomination,
                'sender_first_name' => $request->billing_name,
                'sender_email' => $request->billing_email,
                'sender_phone_no' => $request->billing_tel,
                'sender_address_1' => $request->billing_address,
                'sender_city' => $request->billing_city,
                'sender_state' => $request->billing_state,
                'sender_post_code' => $request->billing_zip,
                'sku' => $request->sku,
                'grand_payable_amount' => $denomination,
                'amount_payable_after_discount' => $denomination, // no discount applied
                'currency' => 'INR',
            ]);
        }

        $merchantOrderId = $order->merchant_order_id ?? (string) Str::uuid();
        
        // Update order with merchant_order_id if not set
        if (!$order->merchant_order_id) {
            $order->merchant_order_id = $merchantOrderId;
            $order->save();
        }

        // Create or update UnlimitPayment record
        $payment = UnlimitPayment::where('order_id', $order->id)->first();
        
        if ($payment) {
            // Update existing payment
            $payment->merchant_order_id = $merchantOrderId;
            $payment->amount = $amountToPay; // Use validated amount
            $payment->billing_name = $request->billing_name;
            $payment->billing_email = $request->billing_email;
            $payment->billing_tel = $request->billing_tel;
            $payment->billing_address = $request->billing_address;
            $payment->billing_city = $request->billing_city;
            $payment->billing_state = $request->billing_state;
            $payment->billing_zip = $request->billing_zip;
            $payment->billing_country = $request->billing_country;
            $payment->save();
        } else {
            // Create new payment
            $payment = UnlimitPayment::create([
                'user_id' => $request->user()->id ?? null,
                'order_id' => $order->id,
                'merchant_order_id' => $merchantOrderId,
                'amount' => $amountToPay, // Use validated amount
                'currency' => 'INR',
                'billing_name' => $request->billing_name,
                'billing_email' => $request->billing_email,
                'billing_tel' => $request->billing_tel,
                'billing_address' => $request->billing_address,
                'billing_city' => $request->billing_city,
                'billing_state' => $request->billing_state,
                'billing_zip' => $request->billing_zip,
                'billing_country' => $request->billing_country,
            ]);
        }

        Log::info("✅ Unlimit checkout validated", [
            'merchant_order_id' => $merchantOrderId,
            'order_id' => $order->id,
            'amount' => $amountToPay,
            'denomination' => $denomination
        ]);

        // SECURITY: Use environment-aware API URL
        $apiBaseUrl = config('unlimit.api_base_url', 'https://sandbox.in.unlimit.com');
        $paymentUrl = rtrim($apiBaseUrl, '/') . '/payment/request';

        // Send request to Unlimit with validated amount
        $response = Http::post($paymentUrl, [
            'merchant_order' => [
                'id' => $merchantOrderId,
                'description' => 'Unlimit transaction'
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => $amountToPay, // Use validated amount from database
                'currency' => 'INR',
            ],
            'return_urls' => [
                'success_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'status' => '{status}']),
                'decline_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'payment_id' => '{payment_id}', 'status' => '{status}']),
            ],
            'callback_url' => route('unlimit.callback'),
        ]);

        Log::info("UNLIMIT API RESPONSE", $response->json());

        $redirectUrl = $response->json('redirect_url');

        if (!$redirectUrl) {
            Log::error('❌ No redirect URL from Unlimit API', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            return back()->with('error', 'Payment gateway error. Please try again.');
        }

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
            // Standardized status flow: PENDING -> COMPLETE or FAILED
            // Keep as PENDING until Woohoo order is created (will be updated to COMPLETE or FAILED)
            if (!in_array($order->order_status, ['COMPLETE', 'FAILED'])) {
                $order->update(['order_status' => 'PENDING']);
                
                // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                if ($orderSummary) {
                    $orderSummary->order_status = 'PENDING';
                    $orderSummary->save();
                }
                
                Log::info("✅ QsOrder status updated to PENDING (payment successful, awaiting Woohoo order creation)", [
                    'order_id' => $order->id
                ]);
            }
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
        'merchant_order_id' => $merchantOrderId ?? $payment->merchant_order_id ?? '',
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
