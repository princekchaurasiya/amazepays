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
    /**
     * Save gift card form values in session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveGiftCardFormValues(Request $request)
    {
        $formData = $request->all();
        session(['giftCardFormValues' => $formData]);
        return response()->json(['status' => 'success']);
    }

    /**
     * Store payment data and proceed to checkout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function storePayNowData(Request $request, $slug)
    {
        // Validate the request
        $checkoutData = session('checkout_data', []);

        Session::put('selected_product_slug', $slug);

        $validator = Validator::make(
            $request->all(),
            [
                'quantity' => 'required|integer|min:1|max:10',
                'gift_send_option' => 'required|string',
                'delivery_mode' => 'required|string',
                'denomination' => 'required',
            ],
            [
                'quantity.min' => 'The quantity must be at least :min.',
                'quantity.max' => 'The quantity cannot exceed :max.',
            ]
        );

        // Redirect to login if user is not authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Log session data for debugging
        Log::info(session()->all());

        // Create a new order instance and populate its fields
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id(); // Assuming user is authenticated
        $qsOrder->denomination = $request->denomination;
        $qsOrder->quantity = $request->quantity;
        $qsOrder->grand_payable_amount = $request->quantity * $request->denomination; // Calculate total amount
        $qsOrder->gift_send_option = $request->gift_send_option;
        $qsOrder->delivery_mode = $request->delivery_mode;
        $qsOrder->receiver_name = $request->receiver_name;
        $qsOrder->receiver_email = $request->receiver_email;
        $qsOrder->receiver_mobile = $request->receiver_mobile;
        $qsOrder->receiver_msg = $request->receiver_msg;
        $qsOrder->save();

        // Assign the custom reference number
        $qsOrder->refno = 'Amz' . $qsOrder->id;
        $qsOrder->save();



        // Store the order ID and reference number in the session
        session()->put('session_qs_order_id', $qsOrder->id);
        session()->put('session_refno', $qsOrder->refno);

        // Retrieve product details based on the slug
        $qsProd = QsProduct::where('slug', $slug)->first();

        // Handle case where product is not found (optional based on your app logic)
        if (!$qsProd) {
            abort(404); // or handle appropriately
        }

        // Prepare product data for view
        $qsProd['prodData'] = $request->all();
        $qsProd['currency'] = json_decode($qsProd['currency']);
        $qsProd['images'] = json_decode($qsProd->images);

        // Render checkout view
        return view('userpanel.checkout', compact('qsProd', 'checkoutData'));
    }

    public function updateSessionData(Request $request)
    {
        Log::info('updateSessionData called');
        Log::info('Request data: ', $request->all());
        $requestData = $request->all();
        // Store data in session as needed
        session()->put('checkout_data', $requestData);

        Log::info('Session data stored');
        return response()->json(['message' => 'Session data updated successfully']);
    }

    public function showCheckoutForm()
    {
        $checkoutData = session('checkout', []);
        return view('checkout', compact('checkoutData'));
    }
}
