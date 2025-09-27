<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QsOrder;
use App\Models\QsProduct;
use App\Models\OrderSummary;
use App\Models\UnlimitPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\UnlimitPaymentController;

class ProductPageController extends Controller
{
    public function saveGiftCardFormValues(Request $request)
    {

        $formData = $request->all();
        session(['giftCardFormValues' => $formData]);

        Log::info('Saving gift card form values');
        Log::info('Form Data:', $formData);

        return response()->json(['status' => 'success']);
    }

    public function storePayNowData(Request $request, $slug)
    {
        // Handle GET request - show checkout form
        if ($request->isMethod('get')) {
            return $this->showCheckoutForm($request, $slug);
        }
        
        // Handle POST request - process order and payment
        Log::info('storePayNowData initiated with slug: ' . $slug);

        // Fetch product by slug
        $product = QsProduct::where('url', $slug)->firstOrFail();

        // If sending as a gift, check if recipient is blocked
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

        // Decode the product price
        $product->price = json_decode($product->price);
        Log::info('Decoded product price: ', ['price' => $product->price]);

        // Validation rules for the form
        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {
                    Log::info('Validating denomination', [
                        'denomination' => $value,
                        'product_id' => $product->id,
                        'price' => is_object($product->price) ? json_encode($product->price) : $product->price, // Log as JSON if object
                    ]);

                    Log::info('Debugging product price:', ['type' => gettype($product->price), 'value' => $product->price]);

                    // If $product->price is an object, convert it to an array
                    $priceData = (array) $product->price;

                    // Check for valid price data
                    if (!is_array($priceData)) {
                        Log::warning('Invalid price format', ['price' => $product->price]);
                        $fail('Invalid product price configuration.');
                        return;
                    }

                    // Default to 'RANGE' if type is missing
                    $priceType = $priceData['type'] ?? 'RANGE';

                    // Now handle SLAB or RANGE validation
                    if ($priceType === 'SLAB') {
                        // Validate SLAB type denominations
                        $denominations = $priceData['denominations'] ?? [];
                        if (!in_array((string) $value, $denominations)) {
                            Log::warning('Invalid SLAB denomination', [
                                'denomination' => $value,
                                'allowed' => $denominations
                            ]);
                            $fail('Invalid denomination value. Allowed values are: ' . implode(', ', $denominations));
                        }
                    } elseif ($priceType === 'RANGE') {
                        // Validate RANGE type price range
                        $minPrice = $priceData['min'] ?? $product->minPrice;
                        $maxPrice = $priceData['max'] ?? $product->maxPrice;

                        // Default to the range if missing
                        if ($minPrice === null || $maxPrice === null) {
                            Log::warning('Missing min/max values for RANGE type, using default min/max', [
                                'minPrice' => $minPrice,
                                'maxPrice' => $maxPrice,
                                'product_id' => $product->id
                            ]);
                        }

                        if ($value < $minPrice || $value > $maxPrice) {
                            Log::warning('Denomination out of RANGE', [
                                'denomination' => $value,
                                'min' => $minPrice,
                                'max' => $maxPrice
                            ]);
                            $fail("The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.");
                        }
                    } else {
                        Log::warning('Unknown price type', ['priceType' => $priceType]);
                        $fail('Invalid price configuration.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|string|max:500',
        ];



        $request->merge(['delivery_mode' => 'both']);
        Log::info('Merged delivery mode to request data.');

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()]);
            return back()->withErrors($validator)->withInput();
        }

        // Calculate total purchases for the current month
        $userId = Auth::id();
        $sku = $product->sku;
        Log::info("Calculating total purchases for user $userId and SKU $sku");

        $totalPurchasesThisMonth = QsOrder::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('created_at', '<=', now()->endOfMonth())
            ->where('sku', $sku)
            ->where('order_status', 'COMPLETE')
            ->sum('grand_payable_amount');

        Log::info('Total purchases this month:', ['total' => $totalPurchasesThisMonth]);

        // Check if the SKU limit is set
        $monthlyPurchaseLimit = $product->sku_limits ?? null;
        Log::info('Monthly purchase limit:', ['limit' => $monthlyPurchaseLimit]);

        // If no limit is set, allow the order to proceed without restriction
        if ($monthlyPurchaseLimit !== null && $monthlyPurchaseLimit != '') {
            $remainingLimit = $monthlyPurchaseLimit - $totalPurchasesThisMonth;

            if ($remainingLimit <= 0) {
                return back()->withErrors([
                    'message' => "You have exceeded your monthly purchase limit of ₹{$monthlyPurchaseLimit}."
                ])->withInput();
            }

            // Calculate the grand payable amount for the current order
            $grandPayableAmount = $request->quantity * $request->denomination;

            if ($grandPayableAmount > $remainingLimit) {
                return back()->withErrors([
                    'message' => "The remaining purchase limit for this product is ₹{$remainingLimit}, but your order total is ₹{$grandPayableAmount}. Please try placing an order within the available limit."
                ])->withInput();
            }
        } else {
            // No limit, proceed to calculate the grand payable amount
            $grandPayableAmount = $request->quantity * $request->denomination;
        }

        // Create a new order since all validations passed
        Log::info('Creating a new order for user:', ['user_id' => Auth::id()]);

        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();
        $qsOrder->sku = $product->sku;
        $qsOrder->product_name = $product->name;
        $qsOrder->denomination = $request->denomination;
        $qsOrder->quantity = $request->quantity;
        $qsOrder->grand_payable_amount = $grandPayableAmount;

        // calculation for discount
        $discountPercentage = $product->discount_percentage;
        $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
        $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;
        $qsOrder->discounted_amount_value = $discountAmount;
        $qsOrder->amount_payable_after_discount = $totalPayableAmountAfterDiscount;
        $qsOrder->gift_send_option = $request->gift_send_option;
        $qsOrder->delivery_mode = 'both';
        $qsOrder->receiver_name = $request->receiver_name;
        $qsOrder->receiver_email = $request->receiver_email;
        $qsOrder->receiver_mobile = $request->receiver_mobile;
        $qsOrder->receiver_msg = $request->receiver_msg;
        $qsOrder->order_status = 'Pending';
        $qsOrder->save();
        Log::info('Order saved successfully', ['order_id' => $qsOrder->id]);
        $qsOrder->refno = 'Amz' . $qsOrder->id;
        $qsOrder->save();

        $payment = new UnlimitPayment();
        $payment->order_id = $qsOrder->id;
        $payment->user_id = Auth::id();
        $payment->mer_amount = $qsOrder->grand_payable_amount;
        $payment->price = $qsOrder->denomination;
        $payment->qty = $qsOrder->quantity;
        $payment->order_status = 'UnPaid';
        $payment->save();

        // Populate the OrderSummary
        $orderSummary = new OrderSummary();
        $orderSummary->order_id = $qsOrder->id;
        $orderSummary->payment_id = $payment->id;
        $orderSummary->product_name = $qsOrder->product_name;
        $orderSummary->payment_status = $payment->order_status; // payment status
        $orderSummary->order_status = $qsOrder->order_status; // Assuming `order_status` exists in QsOrder
        $orderSummary->save();

        Log::info('Generated reference number:', ['refno' => $qsOrder->refno]);

        session()->put('session_qs_order_id', $qsOrder->id);
        session()->put('session_refno', $qsOrder->refno);

        Log::info('Order ID and reference number stored in session.');

        $qsProd = QsProduct::where('url', $slug)->firstOrFail();
        $qsProd['prodData'] = $request->all();
        $qsProd['currency'] = json_decode($qsProd['currency']);
        $qsProd['images'] = json_decode($qsProd->images);

        // Remove payment gateway calls from GET request - these should only be called on form submission
        // Payment gateway initialization will be handled when user clicks submit button

        return view('userpanel.checkout', compact('qsProd', 'checkoutData', 'qsOrder'));
    }



    public function updateSessionData(Request $request)
    {
        Log::info('updateSessionData called with request data:', $request->all());
        $requestData = $request->all();
        session()->put('checkout_data', $requestData);
        Log::info('Checkout data stored in session.');
        return response()->json(['message' => 'Session data updated successfully']);
    }

    public function showCheckoutForm(Request $request, $slug)
    {
        Log::info('showCheckoutForm called for slug: ' . $slug);
        
        // Fetch product by slug
        $product = QsProduct::where('url', $slug)->firstOrFail();
        $product['prodData'] = $request->all();
        $product['currency'] = json_decode($product['currency']);
        $product['images'] = json_decode($product->images);
        
        // Create a new order instance for the form
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();
        
        // Get checkout data from session
        $checkoutData = session('checkout_data', []);
        Log::info('Checkout data retrieved from session:', $checkoutData);
        
        // Remove payment gateway calls from GET request - these should only be called on form submission
        // Payment gateway initialization will be handled when user clicks submit button
        
        return view('userpanel.checkout', compact('product', 'checkoutData', 'qsOrder'));
    }
}
