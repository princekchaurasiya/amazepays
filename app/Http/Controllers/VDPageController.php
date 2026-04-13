<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Product;
use App\Models\UnlimitPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class VDPageController extends Controller
{
    public function saveGiftCardFormValues(Request $request)
    {
        $formData = $request->only([
            'denomination', 'quantity', 'gift_send_option', 'receiver_name',
            'receiver_email', 'receiver_mobile', 'receiver_msg', 'vd_brand_code',
        ]);
        session(['giftCardFormValues' => $formData]);

        return response()->json(['status' => 'success']);
    }

    public function storePayNowData(Request $request)
    {
        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = User::where('mobile', $request->receiver_mobile)->first();
            if ($recipient && ! $recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile,
                ]);

                return response()->view('errors.self-gift', [
                    'blockType' => 'recipient',
                    'phone' => $request->receiver_mobile,
                    'reason' => $recipient->restriction_reason,
                ], 403);
            }
        }

        // Retrieve the checkout session data if available
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

        // Log::info('Total purchases this month:', ['total' => $totalPurchasesThisMonth]);

        // SECURITY: Fetch Brand to validate denomination and get discount from database only
        $brand = null;
        $discountPercentage = 0;

        if ($request->vd_brand_code) {
            // Find Brand by brand_code (primary lookup)
            $brand = Brand::where('brand_code', $request->vd_brand_code)->first();

            // If not found, try to find Product as fallback
            if (! $brand) {
                $product = Product::where('name', 'like', '%'.$request->vd_brand_code.'%')
                    ->orWhere('sku', $request->vd_brand_code)
                    ->first();
            }
        }

        // SECURITY: Validate denomination against Brand/Product price range
        if ($brand) {
            $minPrice = $brand->min_price ?? 0;
            $maxPrice = $brand->max_price ?? 999999;

            if ($request->denomination < $minPrice || $request->denomination > $maxPrice) {
                Log::warning('VD Denomination out of range', [
                    'denomination' => $request->denomination,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'brand_code' => $request->vd_brand_code,
                ]);

                return back()->withErrors([
                    'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.",
                ])->withInput();
            }

            // SECURITY: Get discount from Brand database only, never from request
            $discountPercentage = (float) ($brand->discount ?? 0);
        } elseif (isset($product) && $product) {
            // SECURITY: Fallback to Product - MUST validate denomination against product price range
            // CRITICAL: Don't skip denomination validation when using Product fallback
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

            // SECURITY: Get discount from Product database only, never from request
            $discountPercentage = (float) ($product->discount_percentage ?? 0);
        } else {
            Log::warning('VD Brand/Product not found', [
                'brand_code' => $request->vd_brand_code,
            ]);

            return back()->withErrors([
                'vd_brand_code' => 'Invalid brand code. Please select a valid brand.',
            ])->withInput();
        }

        // SECURITY: Calculate grand payable amount on backend only
        $grandPayableAmount = $request->quantity * $request->denomination;
        // Log::info('Calculated grand payable amount:', ['amount' => $grandPayableAmount]);

        // SECURITY: Calculate discount and final amount on backend only
        $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
        $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;

        // Create a new order since all validations passed
        // SECURITY: Store discount percentage from database, not from request
        $order = Order::create([
            'user_id' => Auth::id(),
            'vd_brand_code' => $request->vd_brand_code,
            'vd_discount' => $discountPercentage, // Store database value, not request value
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

        $payment = new UnlimitPayment;
        $payment->order_id = $order->id;
        $payment->user_id = Auth::id();
        $payment->mer_amount = $order->grand_payable_amount;
        $payment->price = $order->denomination;
        $payment->qty = $order->quantity;
        $payment->order_status = 'UnPaid';
        $payment->save();

        // Populate the OrderSummary
        $orderSummary = new OrderSummary;
        $orderSummary->order_id = $order->id;
        $orderSummary->payment_id = $payment->id;
        $orderSummary->payment_gateway = 'unlimit'; // Specify payment gateway
        $orderSummary->product_name = $order->product_name;
        $orderSummary->payment_status = $payment->order_status; // payment status
        $orderSummary->order_status = $order->order_status; // Assuming `order_status` exists in Order
        $orderSummary->save();

        $UnlimitPaymentController = new UnlimitPaymentController;
        $UnlimitPaymentController->getToken();
        // $UnlimitPaymentController->store($request);
        $UPIPaymentController = new UPIPaymentController;
        $UPIPaymentController->getToken();
        $UPIPaymentController->store($request);

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
}
