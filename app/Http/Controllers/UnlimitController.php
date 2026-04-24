<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Product;
use App\Services\Payment\PaymentService;
use App\Services\Payment\UnlimitPaymentAttemptRecorder;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UnlimitController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private UnlimitPaymentAttemptRecorder $attempts,
    ) {}

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
        $unknown = array_values(array_diff($request->keys(), $allowed));
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

        $this->attempts->record(
            orderId: (int) $order->id,
            userId: $request->user()?->id ? (int) $request->user()->id : null,
            merchantOrderId: (string) $merchantOrderId,
            amount: (float) $amountToPay,
            raw: [],
            gatewayPaymentId: null,
            paymentMethod: 'upi',
            extraAttributes: [
                'billing_name' => $validated['billing_name'],
                'billing_email' => $validated['billing_email'],
                'billing_tel' => $validated['billing_tel'],
                'billing_address' => $validated['billing_address'],
                'billing_city' => $validated['billing_city'],
                'billing_state' => $validated['billing_state'],
                'billing_zip' => $validated['billing_zip'],
                'billing_country' => $validated['billing_country'],
            ],
        );

        $init = $this->payments->initiate($order, 'unlimit', $request->user(), [
            'order_id' => (string) $merchantOrderId,
            'payment_method' => 'upi',
        ]);

        if (! $init->success || ! $init->redirectUrl) {
            return back()->with('error', __('payments.payment_gateway_error_try_again'));
        }

        $this->attempts->record(
            orderId: (int) $order->id,
            userId: $request->user()?->id ? (int) $request->user()->id : null,
            merchantOrderId: (string) $merchantOrderId,
            amount: (float) $amountToPay,
            raw: $init->raw,
            gatewayPaymentId: $init->paymentToken,
            paymentMethod: 'upi',
        );

        return redirect($init->redirectUrl);
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

        $merchantOrderId = (string) (data_get($payload, 'merchant_order.id') ?? '');
        $status = (string) (data_get($payload, 'payment_data.status') ?? '');
        $amount = (float) (data_get($payload, 'payment_data.amount') ?? 0);

        if ($merchantOrderId !== '') {
            $order = Order::where('merchant_order_id', $merchantOrderId)->first();

            $this->attempts->record(
                orderId: (int) ($order?->id ?? 0),
                userId: $order?->user_id ? (int) $order->user_id : null,
                merchantOrderId: $merchantOrderId,
                amount: $amount,
                raw: $payload,
                gatewayPaymentId: (string) (data_get($payload, 'payment_data.id') ?? data_get($payload, 'payment_data.payment_id') ?? ''),
                paymentMethod: (string) (data_get($payload, 'payment_data.method') ?? 'upi'),
                extraAttributes: [
                    'payment_status' => $status !== '' ? $status : null,
                    'raw_callback' => json_encode($payload),
                ],
            );

            if ($order && $status === 'COMPLETED' && ! in_array($order->order_status, ['COMPLETE', 'FAILED'], true)) {
                $order->update(['order_status' => 'PENDING']);

                $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                if ($orderSummary) {
                    $orderSummary->order_status = 'PENDING';
                    $orderSummary->save();
                }
            }
        }

        return ResponsePayload::ok();
    }

    /**
     * Step 3: Return URL after Payment
     */
    public function return(Request $request)
    {
        $merchantOrderId = $request->query('merchant_order_id') ?? $request->input('merchant_order_id');
        $status = $request->query('status') ?? $request->input('status', 'unknown');

        if (! $merchantOrderId) {
            abort(404, __('payments.order_id_required'));
        }

        $order = Order::where('merchant_order_id', $merchantOrderId)->first();

        if (! $order) {
            abort(404, __('payments.order_not_found_or_unauthorized'));
        }

        $amount = $order->grand_payable_amount ?? 0;

        return Inertia::render('Checkout/Processing', [
            'payment_id' => null,
            'order_id' => $order->id,
            'amount' => $amount,
            'status' => strtoupper($status),
            'merchant_order_id' => $merchantOrderId,
        ]);
    }

    /**
     * Step 4: Check payment status
     */
    public function checkTransactionStatus($merchantOrderId)
    {
        $result = $this->payments->queryStatus('unlimit', (string) $merchantOrderId);

        return [
            'merchant_order_id' => (string) $merchantOrderId,
            'status' => (string) ($result->status ?? 'UNKNOWN'),
            'amount' => $result->amount ?? null,
            'currency' => $result->currency ?? null,
            'raw' => $result->raw ?? null,
        ];
    }
}
