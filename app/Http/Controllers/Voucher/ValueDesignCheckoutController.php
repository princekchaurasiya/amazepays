<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

final class ValueDesignCheckoutController extends Controller
{
    public function saveGiftCardFormValues(Request $request)
    {
        abort(410, 'Endpoint retired. Use checkout.session.gift_draft.save.');
    }

    /**
     * Value Design checkout submit (create order + initiate payment).
     */
    public function submit(Request $request)
    {
        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = User::where('mobile', $request->receiver_mobile)->first();
            if ($recipient && ! $recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile,
                ]);

                if ($request->header('X-Inertia')) {
                    return Inertia::render('Error', [
                        'status' => 403,
                        'message' => 'This recipient is not eligible to receive gifts.',
                        'details' => [
                            'blockType' => 'recipient',
                            'phone' => $request->receiver_mobile,
                            'reason' => $recipient->restriction_reason,
                        ],
                    ])->toResponse($request)->setStatusCode(403);
                }

                return response('This recipient is not eligible to receive gifts.', 403, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }
        }

        $checkoutData = session('checkout_data', []);
        $product = null;

        $rules = [
            'denomination' => [
                'required',
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|string|max:500',
            'vd_brand_code' => 'nullable|string|max:128',
        ];

        $validator = Validator::make($request->only(array_keys($rules)), $rules);
        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()]);

            return back()->withErrors($validator)->withInput();
        }

        $userId = Auth::id();
        $totalPurchasesThisMonth = Order::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('created_at', '<=', now()->endOfMonth())
            ->where('order_status', 'COMPLETE')
            ->sum('grand_payable_amount');

        $brand = null;
        $discountPercentage = 0;

        if ($request->vd_brand_code) {
            // Legacy VD brand-code flows are unified onto the product catalog.
            $product = Product::where('name', 'like', '%'.$request->vd_brand_code.'%')
                ->orWhere('sku', $request->vd_brand_code)
                ->first();
        }

        if (isset($product) && $product) {
            $product->price = json_decode($product->price);
            $priceData = (array) $product->price;
            $priceType = $priceData['type'] ?? 'RANGE';

            if ($priceType === 'SLAB') {
                $denominations = $priceData['denominations'] ?? [];
                if (! in_array((string) $request->denomination, $denominations)) {
                    Log::warning('VD Denomination not in SLAB list (Product fallback)', [
                        'denomination' => $request->denomination,
                        'allowed' => $denominations,
                        'brand_code' => $request->vd_brand_code,
                    ]);

                    return back()->withErrors([
                        'denomination' => 'Invalid denomination value. Allowed values are: '.implode(', ', $denominations),
                    ])->withInput();
                }
            } elseif ($priceType === 'RANGE') {
                $minPrice = $priceData['min'] ?? $product->minPrice ?? 0;
                $maxPrice = $priceData['max'] ?? $product->maxPrice ?? 999999;

                if ($request->denomination < $minPrice || $request->denomination > $maxPrice) {
                    Log::warning('VD Denomination out of range (Product fallback)', [
                        'denomination' => $request->denomination,
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                        'brand_code' => $request->vd_brand_code,
                    ]);

                    return back()->withErrors([
                        'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.",
                    ])->withInput();
                }
            }

            $discountPercentage = (float) ($product->discount_percentage ?? 0);
        } else {
            Log::warning('VD Brand/Product not found', [
                'brand_code' => $request->vd_brand_code,
            ]);

            return back()->withErrors([
                'vd_brand_code' => 'Invalid brand code. Please select a valid brand.',
            ])->withInput();
        }

        $grandPayableAmount = $request->quantity * $request->denomination;
        $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
        $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;

        $order = Order::create([
            'user_id' => Auth::id(),
            'vd_brand_code' => $request->vd_brand_code,
            'vd_discount' => $discountPercentage,
            'denomination' => $request->denomination,
            'quantity' => $request->quantity,
            'gift_send_option' => $request->gift_send_option,
            'receiver_name' => $request->receiver_name,
            'receiver_email' => $request->receiver_email,
            'receiver_mobile' => $request->receiver_mobile,
            'receiver_msg' => $request->receiver_msg,
            'grand_payable_amount' => $grandPayableAmount,
            'discounted_amount_value' => $discountAmount,
            'amount_payable_after_discount' => $totalPayableAmountAfterDiscount,
            'order_status' => 'Pending',
        ]);

        $payment = null;
        try {
            $amount = (float) ($order->grand_payable_amount ?? 0);
            $payment = Payment::create([
                'tenant_id' => (int) ($order->tenant_id ?? 1),
                'order_id' => (int) $order->id,
                'user_id' => Auth::id(),
                'gateway' => 'unlimit',
                'environment' => config('app.env') === 'production' ? 'production' : 'sandbox',
                'merchant_order_id' => (string) ($order->order_number ?? $order->id),
                'status' => 'initiated',
                'method_category' => 'upi',
                'method_detail' => 'upi',
                'amount_minor' => (int) round($amount * 100),
                'currency' => 'INR',
                'fee_minor' => 0,
                'tax_on_fee_minor' => 0,
                'settlement_amount_minor' => 0,
                'initiated_at' => now(),
            ]);
        } catch (\Throwable) {
            $payment = null;
        }

        $orderSummary = new OrderSummary;
        $orderSummary->order_id = $order->id;
        $orderSummary->payment_id = $payment?->id;
        $orderSummary->primary_gateway = 'unlimit';
        $orderSummary->product_name = $order->product_name;
        $orderSummary->payment_status = $payment?->status ?? 'initiated';
        $orderSummary->fulfilment_status = $order->order_status;
        $orderSummary->save();

        // Legacy controllers removed in later steps; render the same page for now.
        return Inertia::render('Checkout/Index', [
            'order' => $order,
            'product' => $product,
            'checkoutData' => $checkoutData,
            'grandPayableAmount' => $grandPayableAmount,
            'discountedAmountValue' => $discountAmount,
            'totalPayableAmountAfterDiscount' => $totalPayableAmountAfterDiscount,
            'slug' => $product->url ?? '',
        ]);
    }

    public function updateSession(Request $request)
    {
        $allowed = [
            'vd_discount',
            'vd_brand_code',
            'vd_denomination',
            'vd_quantity',
            'vd_gift_send_option',
            'vd_receiver_name',
            'vd_receiver_email',
            'vd_receiver_mobile',
            'vd_receiver_msg',
        ];

        $data = $request->only($allowed);
        Session::put('vd_checkout_session', $data);

        return response()->json(['message' => __('responses.OK')]);
    }
}
