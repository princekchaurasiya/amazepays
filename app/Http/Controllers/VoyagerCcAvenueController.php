<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerCcAvenueController extends VoyagerBaseController
{
    public function index(Request $request)
{
    // Call the parent index() method to retrieve data
    $response = parent::index($request);
    // dd($response);
    $responseData = $response->getData();
    $responseDataTypeContent = ($responseData['dataTypeContent']);

    // Prepare the modified data to send to the view
    $modifiedData = [];
    
    foreach ($responseDataTypeContent as $payment) {

        $billingDetails = json_decode($payment->billing_details, true);

        $modifiedData[] = [
                'id' => $payment->id,
                'user_id' => $payment->user_id,
                'order_id' => $payment->order_id,
                'tracking_id' => $payment->tracking_id,
                'bank_ref_no' => $payment->bank_ref_no,
                'order_status' => $payment->order_status,
                'failure_message' => $payment->failure_message,
                'payment_mode' => $payment->payment_mode,
                'card_name' => $payment->card_name,
                'status_code' => $payment->status_code,
                'status_message' => $payment->status_message,
                'currency' => $payment->currency,
                'amount' => $payment->amount,
                'firstname' => $billingDetails['firstname'],
                'email' => $billingDetails['email'],
                'contact_no' => $billingDetails['telephone'],
                'Address' => $billingDetails['line1'] . ', ' . $billingDetails['postcode'] . ', ' . $billingDetails['region'],
                // Add other properties as needed
            ]; 
           

    }
  

    // Return the original response
    // Pass the modifiedData to the view using compact
    return $response;
}

}
