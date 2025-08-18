<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;
use App\Models\QsProduct;
use App\Models\OrderSummary;

class VDPageController extends Controller
{
    public function saveGiftCardFormValues(Request $request)
    {

        $formData = $request->all();
        session(['giftCardFormValues' => $formData]);

        Log::info('Saving gift card form values');
        Log::info('Form Data:', $formData);

        return response()->json(['status' => 'success']);
    }

    public function storePayNowData(Request $request)
    {
        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = \App\Models\User::where('mobile', $request->receiver_mobile)->first();
            if ($recipient && !$recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile
                ]);
                return response()->view('errors.self-gift', [
                    'blockType' => 'recipient',
                    'phone' => $request->receiver_mobile,
                    'reason' => $recipient->restriction_reason
                ], 403);
            }
        }

        // Retrieve the checkout session data if available
        $checkoutData = session('checkout_data', []);
        Log::info('Retrieved checkout data from session: ', $checkoutData);

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

            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                Log::warning('Validation failed', ['errors' => $validator->errors()]);
                return back()->withErrors($validator)->withInput();
            }

             $userId = Auth::id();
             $totalPurchasesThisMonth = QsOrder::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('created_at', '<=', now()->endOfMonth())
            ->where('order_status', 'COMPLETE')
            ->sum('grand_payable_amount');

            Log::info('Total purchases this month:', ['total' => $totalPurchasesThisMonth]);

             // Calculate the grand payable amount for the current order
            $grandPayableAmount = $request->quantity * $request->denomination;
            Log::info('Calculated grand payable amount:', ['amount' => $grandPayableAmount]);

            // Create a new order since all validations passed
        Log::info('Creating a new order for user:', ['user_id' => Auth::id()]);
        $order = QsOrder::create([
            'user_id' => Auth::id(),
            'vd_brand_code' => $request->vd_brand_code,
            'vd_discount' => $request->vd_discount,
            'denomination' => $request->denomination,
            'quantity' => $request->quantity,
            'gift_send_option' => $request->gift_send_option,
            'receiver_name' => $request->receiver_name,
            'receiver_email' => $request->receiver_email,
            'receiver_mobile' => $request->receiver_mobile,
            'receiver_msg' => $request->receiver_msg,
            'grand_payable_amount' => $grandPayableAmount,
        ]);
        $discountPercentage = $product->discount_percentage;
        $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
        $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
        $order->discounted_amount_value = $discountAmount;
        $order->amount_payable_after_discount = $totalPayableAmountAfterDiscount;
        $order->order_status = 'Pending';
        $order->save();
        Log::info('Order created successfully', ['order_id' => $order->id]);

        $payment = new UnlimitPayment();
        $payment->order_id = $order->id;
        $payment->user_id = Auth::id();
        $payment->mer_amount = $order->grand_payable_amount;
        $payment->price = $order->denomination;
        $payment->qty = $order->quantity;
        $payment->order_status = 'UnPaid';
        $payment->save();

        // Populate the OrderSummary
        $orderSummary = new OrderSummary();
        $orderSummary->order_id = $order->id;
        $orderSummary->payment_id = $payment->id;
        $orderSummary->product_name = $order->product_name;
        $orderSummary->payment_status = $payment->order_status; // payment status
        $orderSummary->order_status = $order->order_status; // Assuming `order_status` exists in QsOrder
        $orderSummary->save();

        $UnlimitPaymentController = new UnlimitPaymentController();
        $UnlimitPaymentController->getToken();
        //$UnlimitPaymentController->store($request);
        $UPIPaymentController = new UPIPaymentController();
        $UPIPaymentController->getToken();
        $UPIPaymentController->store($request);

        return view('userpanel.checkout', [
            'order' => $order,
            'checkoutData' => $checkoutData,
            'grandPayableAmount' => $grandPayableAmount,
            'discountedAmountValue' => $discountAmount,
            'totalPayableAmountAfterDiscount' => $totalPayableAmountAfterDiscount,
        ]);
        }
    }