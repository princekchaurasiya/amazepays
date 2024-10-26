<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function index()
    {
        // Retrieve product data from session
        $selectedProductSlug = Session::get('selected_product_slug');

        // Perform any additional logic as needed for checkout view
        return view('checkout', ['selectedProductSlug' => $selectedProductSlug]);

    }

    public function store(Request $request)
    {
        // Validate checkout form data
        $validatedData = $request->validate([
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_tel' => 'required|string|max:10',
            'billing_zip' => 'required|string|max:6',
            'billing_address' => 'required|string|max:255',
            'billing_address_two' => 'nullable|string|max:255',
            'billing_city' => 'required|string|max:255',
            'billing_state' => 'required|string|max:255',
            'billing_country' => 'required|string|max:255',
            'billing_gst_number' => 'nullable|string|max:15',
        ]);

        // Store validated form data in session
        Session::put('billing_data', $validatedData);

        $user = Auth::user();
        $user->update([
            'name' => $validatedData['billing_name'], // You might not want to update the name directly
            'email' => $validatedData['billing_email'],
            'mobile' => $validatedData['billing_tel'],
            'billing_zip' => $validatedData['billing_zip'],
            'billing_address' => $validatedData['billing_address'],
            'billing_city' => $validatedData['billing_city'],
            'billing_state' => $validatedData['billing_state'],
            'billing_country' => $validatedData['billing_country'],
        ]);

        // dd(Session::get('billing_data'));

        // Redirect to payment processing route
        return redirect()->route('payment.process');
    }

    public function placeOrder()
    {
        log::info(8787);
        // Retrieve stored billing data from session
        $billingData = Session::get('billing_data');

        // Perform further actions such as saving to database, sending notifications, etc.

        // Clear the session data after processing
        Session::forget('billing_data');

        // Redirect to a thank you page or any other relevant page
        return redirect()->route('thankyou');
    }
}
