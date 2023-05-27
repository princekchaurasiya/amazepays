<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ccavenuekit\ccavRequestHandler;
use App\Ccavenuekit\ccavResponseHandler;
use App\Ccavenuekit\crypto;

class PaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        // Generate a unique order ID or transaction ID
        $orderId = uniqid();

        // Get the form input values
        $billingName = $request->input('billing_name');
        $billingAddress = $request->input('billing_address');
        $billingState = $request->input('billing_state');
        $billingZipCode = $request->input('billing_zip');
        $billingCountry = $request->input('billing_country');
        $billingTelephone = $request->input('billing_tel');
        $billingEmail = $request->input('billing_email');

        // Prepare the data array for checksum calculation
        $data = [
            'merchant_id' => config('paymentconfig.merchant_id'),
            'order_id' => $orderId,
            'currency' => 'INR',
            'amount' => '1', // Change this to the actual payment amount
            'redirect_url' => route('payment-success'),
            'cancel_url' => route('payment-failed'),
            'language' => 'EN',
            'billing_name' => $billingName,
            'billing_address' => $billingAddress,
            'billing_state' => $billingState,
            'billing_zip' => $billingZipCode,
            'billing_country' => $billingCountry,
            'billing_tel' => $billingTelephone,
            'billing_email' => $billingEmail,
        ];
       
        // // Create an instance of ccavRequestHandler
        // $requestHandler = new ccavRequestHandler();

        // // Generate the encrypted data

        // $encryptedData = $requestHandler->encrypt($data, config('auth.working_key'));

        // // Add the encrypted data to the request form
        // $request->merge(['encRequest' => $encryptedData]);

        // Redirect to the CCAvenue gateway
        // return view('paymentFolder.ccavRequestHandler');
        return view('paymentFolder.ccavRequestHandler',compact('data'));
    }

    public function paymentSuccess()
    {
        return view('paymentFolder.payment-success');
    }

    public function paymentFailed()
    {
        return view('paymentFolder.payment-failed');
    }
}
