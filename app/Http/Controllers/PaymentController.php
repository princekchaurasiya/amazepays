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
use App\QsProduct;
use PDF;
use App\Models\GiftCard;

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
        // dd($request);
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

    // cc avenue response handler code

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
        $userOrder->order_id = $data[0]['order_id'];
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
        $userOrder->billing_details = json_encode(
            $billing_details = [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ],
        );
        // dd($data);
        $userOrder->delivery_details = json_encode(
            $delivery_details = [
                'firstname' => $data[11]['billing_name'],
                'email' => $data[18]['billing_email'],
                'telephone' => '+91' . $data[17]['billing_tel'],
                'line1' => $data[12]['billing_address'],
                'city' => $data[13]['billing_city'],
                'region' => $data[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $data[15]['billing_zip'],
            ],
        );
        $userOrder->merchant_params = json_encode(
            $merchant_params = [
                'merchant_param1' => $data[26]['merchant_param1'],
                'merchant_param2' => $data[27]['merchant_param2'],
                'merchant_param3' => $data[28]['merchant_param3'],
                'merchant_param4' => $data[29]['merchant_param4'],
                'merchant_param5' => $data[30]['merchant_param5'],
            ],
        );
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

        $orderData = [
            'order_id' => $data[0]['order_id'],
            'reference_id' => $data[1]['tracking_id'],
            'order_date' => $data[40]['trans_date'],
            'billing_name' => $data[11]['billing_name'],
            'billing_email' => $data[18]['billing_email'],
            'billing_tel' => $data[17]['billing_tel'],
            'billing_address' => $data[12]['billing_address'] . ', ' . $data[13]['billing_city'] . ', ' . $data[14]['billing_state'] . ' ' . $data[15]['billing_zip'] . '. ' . $data[16]['billing_country'],
            'payment_mode' => $data[5]['payment_mode'] . ' - ' . $data[6]['card_name'],
            'bank_ref_no' => $data[2]['bank_ref_no'],
            'order_amount' => 'INR ' . $data[10]['amount'],
            'net_payable' => 'INR ' . $data[10]['amount'],
            'contact_person' => $data[19]['delivery_name'] . ' | ' . $data[25]['delivery_tel'],
            'shipping_address' => $data[20]['delivery_address'] . ', ' . $data[21]['delivery_city'] . ', ' . $data[22]['delivery_state'] . ' ' . $data[23]['delivery_zip'] . '. ' . $data[24]['delivery_country'],
        ];

        $productAllData = QsProduct::all();

        $order = QsOrder::where('order_id', $orderData['order_id'])->first();

        // Retrieve data from the gift_card table to reciver detail purpose
        $giftCardData = GiftCard::where('order_id', $orderData['order_id'])->first();

        $shipToName = $giftCardData->receiver_name;
        $shipToEmail = $giftCardData->receiver_email;
        $shipToContactNo = $giftCardData->receiver_mobile;

        // Decode the JSON fields
        $cardsData = json_decode($order->cards);

        $cardSku = $cardsData[0]->sku;
        $cardProductName = $cardsData[0]->productName;

        // Retrieve the product with the matching sku from the QsProduct table
        $product = QsProduct::where('sku', $cardSku)->first();

        if ($product) {
    // The product with the specified sku was found
    $images = json_decode($product->images, true);
    
    

    if ($images && isset($images['small'])) {
        $smallImageUrl = $images['small'];
        
        // Now you can use $thumbnailUrl in your HTML to display the image
    } else {
        // Handle the case where the thumbnail URL is missing
        dd("thumbnail is missing");
    }
} else {
    // No product found with the specified sku
    // Handle this case according to your requirements
    dd("no product found");
}

        if ($order_status === 'Success') {
            $order->update(['order_status' => 'COMPLETE']);
            $email = Auth::user()->email;
            $name = Auth::user()->name;

            // Generate Invoice Number and Invoice Date
            $invoiceNumber = 'AMZ-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            $invoiceDate = date('d-m-Y');

            // Send mail to the buyer
            $data = [
                'name' => $name,
                'order_id' => $orderData['order_id'],
                'reference_id' => $orderData['reference_id'],
                'order_date' => $orderData['order_date'],
                'billing_name' => $orderData['billing_name'],
                'billing_email' => $orderData['billing_email'],
                'billing_tel' => $orderData['billing_tel'],
                'billing_address' => $orderData['billing_address'],
                'payment_mode' => $orderData['payment_mode'],
                'bank_ref_no' => $orderData['bank_ref_no'],
                'order_amount' => $orderData['order_amount'],
                'net_payable' => $orderData['net_payable'],
                'contact_person' => $orderData['contact_person'],
                'shipping_address' => $orderData['shipping_address'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'cardSku' => $cardSku,
                'cardProductName' => $cardProductName,
                'shipToName' => $shipToName,
                'shipToEmail' => $shipToEmail,
                'shipToContactNo' => $shipToContactNo,
                'perOrderPrice' => $order->price,
                'perOrderQuantity' => $order->qty,
                'smallImageUrl' => $smallImageUrl,
            ];

            

            $pdf = PDF::loadView('layouts.invoice', $data);
            Mail::send(['html' => 'layouts.mail'], $data, function ($message) use ($email, $name, $pdf) {
                $message
                    ->to($email, $name)
                    ->subject('Order Confirmation with Invoice')
                    ->attachData($pdf->output(), 'invoice.pdf');
            });

            $msg = 'Order created successfully!';
            $status = 'success';
        } elseif ($order_status === 'Aborted' || $order_status === 'Failure') {
            $order->update(['order_status' => 'CANCELED']);
            $msg = 'Something went wrong. Please contact the support team if any money got deducted.';
            $status = 'failure';

            // Send a message to the buyer for payment failure or abortion
            $messageData = [
                'name' => $name,
                'msg' => $msg,
            ];
            Mail::send('layouts.payment_failure', $messageData, function ($message) use ($email, $name) {
                $message->to($email, $name)->subject('Payment Failure');
            });
        } else {
            $order->update(['order_status' => 'CANCELED']);
            $msg = 'Something went wrong. Please contact the support team if any money got deducted.';
            $status = 'underProcess';
        }

        return view('paymentFolder.paymentStatus', compact('msg', 'status'));

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
