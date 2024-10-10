<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QsOrder;
use App\Models\QsProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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
        Log::info('storePayNowData initiated with slug: ' . $slug);

        // Fetch product by slug
        $product = QsProduct::where('slug', $slug)->firstOrFail();


        // Retrieve the checkout session data if available
        $checkoutData = session('checkout_data', []);
        Log::info('Retrieved checkout data from session: ', $checkoutData);

        // Validate the product price as either SLAB or RANGE
        $product->price = json_decode($product->price);
        Log::info('Decoded product price: ', ['price' => $product->price]);

        // Validation rules for the form
        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {
                    Log::info('Validating denomination', ['denomination' => $value]);
                    if (is_object($product->price)) {
                        if ($product->price->type === 'SLAB' && !in_array($value, $product->price->denominations)) {
                            Log::warning('Invalid denomination value', ['denomination' => $value]);
                            $fail('Invalid denomination value.');
                        } elseif ($product->price->type === 'RANGE' && ($value < $product->minPrice || $value > $product->maxPrice)) {
                            Log::warning('Denomination out of range', ['denomination' => $value, 'minPrice' => $product->minPrice, 'maxPrice' => $product->maxPrice]);
                            $fail("The denomination must be between ₹{$product->minPrice} and ₹{$product->maxPrice}.");
                        }
                    } else {
                        Log::error('Price information is not available for the product');
                        $fail('Price information is not available.');
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

        // Merge additional request data
        $request->merge(['delivery_mode' => 'both']);
        Log::info('Merged delivery mode to request data.');

        // Validate the request input
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

        // Fetch the monthly purchase limit for the product
        $monthlyPurchaseLimit = $product->sku_limits;
        Log::info('Fetched monthly purchase limit:', ['limit' => $monthlyPurchaseLimit]);

        // Calculate the remaining available limit
        $remainingLimit = $monthlyPurchaseLimit - $totalPurchasesThisMonth;
        Log::info('Calculated remaining limit:', ['remainingLimit' => $remainingLimit]);

        // Check if the remaining limit is 0 or negative
        if ($remainingLimit <= 0) {
            $errorMessage = "You have exceeded your monthly purchase limit of ₹{$monthlyPurchaseLimit} for this product. You have an available limit of ₹0 this month that you can purchase.";
            Log::warning('User exceeded monthly purchase limit.', ['remainingLimit' => $remainingLimit]);
            return back()->withErrors(['message' => $errorMessage])->withInput();
        }

        // Calculate the grand payable amount for the current order
        $grandPayableAmount = $request->quantity * $request->denomination;
        Log::info('Calculated grand payable amount:', ['grandPayableAmount' => $grandPayableAmount]);

        // Check if the current order exceeds the remaining limit
        if ($grandPayableAmount > $remainingLimit) {
            $errorMessage = "Your monthly purchase limit is ₹{$monthlyPurchaseLimit}, and you have already purchased ₹{$totalPurchasesThisMonth} this month. you can only purchase ₹{$remainingLimit} more this month.";
            Log::warning('Order exceeds remaining limit.', ['remainingLimit' => $remainingLimit, 'attemptedAmount' => $grandPayableAmount]);
            return back()->withErrors(['message' => $errorMessage])->withInput();
        }

        // Create a new order since the limit is not exceeded
        Log::info('Creating a new order for user:', ['user_id' => Auth::id()]);
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();
        $qsOrder->denomination = $request->denomination;
        $qsOrder->quantity = $request->quantity;
        $qsOrder->grand_payable_amount = $grandPayableAmount;
        $qsOrder->gift_send_option = $request->gift_send_option;
        $qsOrder->delivery_mode = 'both';
        $qsOrder->receiver_name = $request->receiver_name;
        $qsOrder->receiver_email = $request->receiver_email;
        $qsOrder->receiver_mobile = $request->receiver_mobile;
        $qsOrder->receiver_msg = $request->receiver_msg;
        $qsOrder->save();

        Log::info('Order saved successfully', ['order_id' => $qsOrder->id]);

        // Generate reference number after saving the order
        $qsOrder->refno = 'Amz' . $qsOrder->id;
        $qsOrder->save();

        Log::info('Generated reference number:', ['refno' => $qsOrder->refno]);

        // Store order reference in session for further processing
        session()->put('session_qs_order_id', $qsOrder->id);
        session()->put('session_refno', $qsOrder->refno);

        Log::info('Order ID and reference number stored in session.');

        // Retrieve and prepare the product data for checkout
        $qsProd = QsProduct::where('slug', $slug)->firstOrFail();
        $qsProd['prodData'] = $request->all();
        $qsProd['currency'] = json_decode($qsProd['currency']);
        $qsProd['images'] = json_decode($qsProd->images);



        // Render the checkout view with product and checkout data
        return view('userpanel.checkout', compact('qsProd', 'checkoutData'));
    }

    public function updateSessionData(Request $request)
    {
        Log::info('updateSessionData called with request data:', $request->all());
        $requestData = $request->all();
        session()->put('checkout_data', $requestData);
        Log::info('Checkout data stored in session.');
        return response()->json(['message' => 'Session data updated successfully']);
    }

    public function showCheckoutForm()
    {
        Log::info('showCheckoutForm called.');
        $checkoutData = session('checkout', []);
        Log::info('Checkout data retrieved from session:', $checkoutData);
        return view('checkout', compact('checkoutData'));
    }
}
