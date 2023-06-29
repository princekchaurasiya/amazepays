<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ccavenuekit\ccavRequestHandler;
use App\Ccavenuekit\ccavResponseHandler;
use App\Ccavenuekit\crypto;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Http;
use Carbon;
use GuzzleHttp\Client;
use Session;
use App\Models\QsOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\CcAvenuePayment;
use DB;
use Mail;


class PaymentController extends Controller
{
    /* This is crypto.php code provided by cc
/*
* @param1 : Plain String
* @param2 : Working key provided by CCAvenue
* @return : Decrypted String
*/
    function encrypt($plainText, $key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    /*
     * @param1 : Encrypted String
     * @param2 : Working key provided by CCAvenue
     * @return : Plain String
     */
    function decrypt($encryptedText, $key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    function hextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = '';
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack('H*', $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }
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

        return view('paymentFolder.ccavRequestHandler', compact('data'));
    }

    public function paymentSuccess()
    {
        return view('paymentFolder.payment-success');
    }

    public function paymentFailed()
    {
        return view('paymentFolder.payment-failed');
    }

    public function responseCcavenue(Request $request)
    {
        $workingKey = config('auth.working_key');

        $encResponse = $request->encResp; //This is the response sent by the CCAvenue Server
        $rcvdString = $this->decrypt($encResponse, $workingKey); //Crypto Decryption used as per the specified working key.
        $order_status = '';
        $decryptValues = explode('&', $rcvdString);
        $dataSize = sizeof($decryptValues);
        // dd($decryptValues);
        for ($i = 0; $i < $dataSize; $i++) {
            $information = explode('=', $decryptValues[$i]);
            $data[] = [
                $information[0] => $information[1],
            ];
            if ($i == 3) {
                $order_status = $information[1];
            }
        }
        $order_details = QsOrder::where('order_id', $data[0]['order_id'])->first();
        Auth::loginUsingId($order_details['user_id']);
        // dd($order_details);
        $userOrder = new CcAvenuePayment();
            $userOrder->user_id = $order_details['user_id'];
            $userOrder->order_id    = $data[0]['order_id'];
            $userOrder->tracking_id = $data[1]['tracking_id'];
            $userOrder->bank_ref_no = $data[2]['bank_ref_no'];
            $userOrder->order_status = $data[3]['order_status'];
            $userOrder->failure_message = $data[4]['failure_message'];
            $userOrder->payment_mode = $data[5]['payment_mode'];
            $userOrder->card_name = $data[6]['card_name'];
            $userOrder->status_code = $data[7]['status_code'];
            $userOrder->status_message = $data[8]['status_message'];
            $userOrder->currency = $data[9]['currency'];
            $userOrder->amount = $data[10]['amount'];
            $userOrder->billing_details = json_encode($billing_details = [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ]);
            $userOrder->delivery_details = json_encode($delivery_details = [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ]);
            $userOrder->merchant_params = json_encode( $merchant_params = [
                'merchant_param1' => $data[26]['merchant_param1'],
                'merchant_param2' => $data[27]['merchant_param2'],
                'merchant_param3' => $data[28]['merchant_param3'],
                'merchant_param4' => $data[29]['merchant_param4'],
                'merchant_param5' => $data[30]['merchant_param5'],
            ]);
            $userOrder->vault = $data[31]['vault'];
            $userOrder->offer_type = $data[32]['offer_type'];
            $userOrder->offer_code = $data[33]['offer_code'];
            $userOrder->discount_value = $data[34]['discount_value'];
            $userOrder->mer_amount = $data[35]['mer_amount'];
            $userOrder->eci_value = $data[36]['eci_value'];
            $userOrder->retry = $data[37]['retry'];
            $userOrder->response_code = $data[38]['response_code'];
            $userOrder->billing_notes = $data[39]['billing_notes'];
            $userOrder->trans_date = $data[40]['trans_date'];
            $userOrder->bin_country = $data[41]['bin_country'];
            $userOrder->price = $order_details['price'];
            $userOrder->qty = $order_details['qty'];
            // $userOrder->sku = $order_details['sku'];
            // $userOrder->price = 2000;
            // $userOrder->qty = 2;
            // $userOrder->currency_code = 356;
            $userOrder->save();
        if ($order_status === 'Success') {
            QsOrder::where('order_id', $data[0]['order_id'])->update(['order_status'=>'COMPLETE']);
            // send mail or sms to buyer
            $data = array('name'=>"Virat Gandhi");
            Mail::send('layouts.mail', $data, function($message) {
                $message->to('shubham.toutle@gmail.com', 'Tutorials Point')->subject
                    ('Laravel Testing Mail with Attachment');
            });
            $msg = "order created successfully!";
            // return redirect()->route('myOrder',compact('msg')); //redirect to order page
            return redirect('my-order')->with('msg', $msg);
        } elseif ($order_status === 'Aborted') {
            QsOrder::where('order_id', $data[0]['order_id'])->update(['order_status'=>'CANCELED']);
            $msg = "Something went wrong. Please contact the support team if any money got deducted.";
            return redirect()->route('myOrder',compact('msg')); //redirect to order page
        } elseif ($order_status === 'Failure') {
            QsOrder::where('order_id', $data[0]['order_id'])->update(['order_status'=>'CANCELED']);
            $msg = "Something went wrong. Please contact the support team if any money got deducted.";
            return redirect()->route('myOrder',compact('msg')); //redirect to order page
        } else {
            QsOrder::where('order_id', $data[0]['order_id'])->update(['order_status'=>'CANCELED']);
            $msg = "Something went wrong. Please contact the support team if any money got deducted.";
            return redirect()->route('myOrder',compact('msg')); //redirect to order page
        }
        // for($i = 0; $i < $dataSize; $i++)
        // {
        //     $information=explode('=',$decryptValues[$i]);
        //         echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
        // }
        // $dataSize=sizeof($decryptValues);
    }

    public function orderCard($respData, $datasize)
    {
        for ($i = 0; $i < $datasize; $i++) {
            $information = explode('=', $respData[$i]);
            $data[] = [
                $information[0] => $information[1],
            ];
            // echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
        }
        $new_data = [
            'tracking_id' => $data[1]['tracking_id'],
            'bank_ref_no' => $data[2]['bank_ref_no'],
            'order_status' => $data[3]['order_status'],
            'failure_message' => $data[4]['failure_message'],
            'payment_mode' => $data[5]['payment_mode'],
            'card_name' => $data[6]['card_name'],
            'status_code' => $data[7]['status_code'],
            'status_message' => $data[8]['status_message'],
            'currency' => $data[9]['currency'],
            'amount' => $data[10]['amount'],
            'billing_details' => [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ],

            'delivery_details' => [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ],

            'merchant_params' => [
                'merchant_param1' => $data[26]['merchant_param1'],
                'merchant_param2' => $data[27]['merchant_param2'],
                'merchant_param3' => $data[28]['merchant_param3'],
                'merchant_param4' => $data[29]['merchant_param4'],
                'merchant_param5' => $data[30]['merchant_param5'],
            ],
            'vault' => $data[31]['vault'],
            'offer_type' => $data[32]['offer_type'],
            'offer_code' => $data[33]['offer_code'],
            'discount_value' => $data[34]['discount_value'],
            'mer_amount' => $data[35]['mer_amount'],
            'eci_value' => $data[36]['eci_value'],
            'retry' => $data[37]['retry'],
            'response_code' => $data[38]['response_code'],
            'billing_notes' => $data[39]['billing_notes'],
            'trans_date' => $data[40]['trans_date'],
            'bin_country' => $data[41]['bin_country'],
        ];
        // dd($new_data);

        $payment_update = CcAvenuePayment::where('order_id', '=', $data[0]['order_id'])->update($new_data);
        if ($payment_update == true) {
            $user_data = CcAvenuePayment::where('order_id', $data[0]['order_id'])->first();
        }
        return $user_data;
    }
}
