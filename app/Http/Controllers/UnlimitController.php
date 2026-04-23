<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Product;
use App\Models\UnlimitPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UnlimitController extends Controller
{
    /**
     * Step 1: Checkout → Create Order & Payment
     * SECURITY: All amounts validated against product/database
     */
    public function checkout(Request $request)
    {
        $allowed = [
            'billing_name',
            'billing_email',
            'billing_tel',
            'billing_address',
            'billing_city',
            'billing_state',
            'billing_zip',
            'billing_country',
            'denomination',
            'sku',
            'order_id',
        ];
        $unknown = array_values(array_diff(array_keys($request->all()), $allowed));
        if ($unknown !== []) {
            return back()->withErrors([
                'unexpected_fields' => 'Unexpected input fields detected: '.implode(', ', $unknown),
            ])->withInput();
        }

        $validated = $request->validate([
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
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $denomination = (float) $validated['denomination'];
        $amountToPay = $denomination;
        $order = null;

        if (! empty($validated['order_id'])) {
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $request->user()->id ?? null)
                ->first();

            if (! $order) {
                return back()->with('error', 'Order not found or unauthorized.');
            }

            if (abs($order->denomination - $denomination) > 0.01) {
                return back()->with('error', 'Denomination mismatch. Please refresh and try again.');
            }

            if ($order->amount_payable_after_discount !== null) {
                $amountToPay = (float) $order->amount_payable_after_discount;
            } elseif ($order->grand_payable_amount !== null) {
                $amountToPay = (float) $order->grand_payable_amount;
            } else {
                $quantity = (int) ($order->quantity ?? 1);
                $amountToPay = (float) ($order->denomination ?? 0) * $quantity;
            }
        } else {
            if ($validated['sku']) {
                $product = Product::where('sku', $validated['sku'])->first();

                if ($product) {
                    $product->price = json_decode($product->price);
                    $priceData = (array) $product->price;
                    $priceType = $priceData['type'] ?? 'RANGE';

                    if ($priceType === 'SLAB') {
                        $denominations = $priceData['denominations'] ?? [];
                        if (! in_array((string) $denomination, $denominations)) {
                            return back()->withErrors([
                                'denomination' => 'Invalid denomination value. Allowed values are: '.implode(', ', $denominations),
                            ])->withInput();
                        }
                    } elseif ($priceType === 'RANGE') {
                        $minPrice = $priceData['min'] ?? $product->minPrice ?? 0;
                        $maxPrice = $priceData['max'] ?? $product->maxPrice ?? 999999;

                        if ($denomination < $minPrice || $denomination > $maxPrice) {
                            return back()->withErrors([
                                'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.",
                            ])->withInput();
                        }
                    }
                }
            }

            $order = Order::create([
                'user_id' => $request->user()->id ?? null,
                'woohoo_order_id' => 'WH-'.Str::upper(Str::random(6)),
                'order_status' => 'INITIATED',
                'denomination' => $denomination,
                'sender_first_name' => $validated['billing_name'],
                'sender_email' => $validated['billing_email'],
                'sender_phone_no' => $validated['billing_tel'],
                'sender_address_1' => $validated['billing_address'],
                'sender_city' => $validated['billing_city'],
                'sender_state' => $validated['billing_state'],
                'sender_post_code' => $validated['billing_zip'],
                'sku' => $validated['sku'],
                'grand_payable_amount' => $denomination,
                'amount_payable_after_discount' => $denomination,
                'currency' => 'INR',
            ]);
        }

        $merchantOrderId = $order->merchant_order_id ?? (string) Str::uuid();

        if (! $order->merchant_order_id) {
            $order->merchant_order_id = $merchantOrderId;
            $order->save();
        }

        $payment = UnlimitPayment::where('order_id', $order->id)->first();

        if ($payment) {
            $payment->merchant_order_id = $merchantOrderId;
            $payment->amount = $amountToPay;
            $payment->billing_name = $validated['billing_name'];
            $payment->billing_email = $validated['billing_email'];
            $payment->billing_tel = $validated['billing_tel'];
            $payment->billing_address = $validated['billing_address'];
            $payment->billing_city = $validated['billing_city'];
            $payment->billing_state = $validated['billing_state'];
            $payment->billing_zip = $validated['billing_zip'];
            $payment->billing_country = $validated['billing_country'];
            $payment->save();
        } else {
            UnlimitPayment::create([
                'user_id' => $request->user()->id ?? null,
                'order_id' => $order->id,
                'merchant_order_id' => $merchantOrderId,
                'amount' => $amountToPay,
                'currency' => 'INR',
                'billing_name' => $validated['billing_name'],
                'billing_email' => $validated['billing_email'],
                'billing_tel' => $validated['billing_tel'],
                'billing_address' => $validated['billing_address'],
                'billing_city' => $validated['billing_city'],
                'billing_state' => $validated['billing_state'],
                'billing_zip' => $validated['billing_zip'],
                'billing_country' => $validated['billing_country'],
            ]);
        }

        $apiBaseUrl = config('unlimit.api_base_url', 'https://sandbox.in.unlimit.com');
        $paymentUrl = rtrim($apiBaseUrl, '/').'/payment/request';

        $response = Http::post($paymentUrl, [
            'merchant_order' => [
                'id' => $merchantOrderId,
                'description' => 'Unlimit transaction',
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => $amountToPay,
                'currency' => 'INR',
            ],
            'return_urls' => [
                'success_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'status' => '{status}']),
                'decline_url' => route('unlimit.return', ['merchant_order_id' => $merchantOrderId, 'payment_id' => '{payment_id}', 'status' => '{status}']),
            ],
            'callback_url' => route('unlimit.callback'),
        ]);

        $redirectUrl = $response->json('redirect_url');

        if (! $redirectUrl) {
            return back()->with('error', 'Payment gateway error. Please try again.');
        }

        return redirect($redirectUrl);
    }

    /**
     * Step 2: Unlimit Callback
     */
    public function callback(Request $request)
    {
        $payload = [
            'merchant_order' => $request->input('merchant_order'),
            'payment_data' => $request->input('payment_data'),
        ];

        $merchantOrderId = data_get($payload, 'merchant_order.id');
        $status = data_get($payload, 'payment_data.status');
        $amount = data_get($payload, 'payment_data.amount');

        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();
        if ($payment) {
            $payment->update([
                'payment_status' => $status,
                'amount' => $amount,
                'raw_callback' => json_encode($payload),
            ]);
        }

        $order = Order::where('merchant_order_id', $merchantOrderId)->first();
        if ($order && $status === 'COMPLETED') {
            if (! in_array($order->order_status, ['COMPLETE', 'FAILED'])) {
                $order->update(['order_status' => 'PENDING']);

                $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                if ($orderSummary) {
                    $orderSummary->order_status = 'PENDING';
                    $orderSummary->save();
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Step 3: Return URL after Payment
     */
    public function return(Request $request)
    {
        $merchantOrderId = $request->query('merchant_order_id') ?? $request->input('merchant_order_id');
        $status = $request->query('status') ?? $request->input('status', 'unknown');

        if (! $merchantOrderId) {
            abort(404, 'Merchant order ID missing');
        }

        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->latest()->first();
        $order = Order::where('merchant_order_id', $merchantOrderId)->first();

        if (! $payment || ! $order) {
            abort(404, 'Payment or order not found');
        }

        $amount = $order->grand_payable_amount ?? 0;

        return Inertia::render('Checkout/Processing', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'status' => strtoupper($status),
            'merchant_order_id' => $merchantOrderId ?? $payment->merchant_order_id ?? '',
        ]);
    }

    /**
     * Step 4: Check payment status
     */
    public function checkTransactionStatus($merchantOrderId)
    {
        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (! $payment) {
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
