<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Ccavenuekit\ccavRequestHandler;
use App\Ccavenuekit\ccavResponseHandler;
use App\Ccavenuekit\crypto;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\UserPanelController;
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
use Config;

class PaymentController extends Controller
{
    /* This is crypto.php code provided by cc
/*
* @param1 : Plain String
* @param2 : Working key provided by CCAvenue
* @return : Decrypted String
*/

    protected $commonController;

    public function __construct()
    {
        $this->commonController = new CommonController();
    }

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
        $workingKey = config('paymentconfig.working_key');
        $encResponse = $request->encResp; //This is the response sent by the CCAvenue Server
        $rcvdString = $this->decrypt($encResponse, $workingKey);
        // dd($rcvdString); //Crypto Decryption used as per the specified working key.
        $order_status = '';
        $decryptValues = explode('&', $rcvdString);
        $dataSize = sizeof($decryptValues);

        for ($i = 0; $i < $dataSize; $i++) {
            $information = explode('=', $decryptValues[$i]);
            $ccAvenueCollectedDataArray[] = [
                $information[0] => $information[1],
            ];
            if ($i == 3) {
                $order_status = $information[1];
            }
        }

        // cc avenue collected data
        // dd($order_status );
        $qsOrderDetails = QsOrder::where('order_id', $ccAvenueCollectedDataArray[0]['order_id'])->first();
        Auth::loginUsingId($qsOrderDetails['user_id']);
        $newCcAvenueOrder = new CcAvenuePayment();

        $newCcAvenueOrder->user_id = $qsOrderDetails['user_id'];
        $newCcAvenueOrder->order_id = $ccAvenueCollectedDataArray[0]['order_id'];
        $newCcAvenueOrder->tracking_id = $ccAvenueCollectedDataArray[1]['tracking_id'];
        $newCcAvenueOrder->bank_ref_no = $ccAvenueCollectedDataArray[2]['bank_ref_no'];
        $newCcAvenueOrder->order_status = $ccAvenueCollectedDataArray[3]['order_status'];
        $newCcAvenueOrder->failure_message = $ccAvenueCollectedDataArray[4]['failure_message'];
        $newCcAvenueOrder->payment_mode = $ccAvenueCollectedDataArray[5]['payment_mode'];
        $newCcAvenueOrder->card_name = $ccAvenueCollectedDataArray[6]['card_name'];
        $newCcAvenueOrder->status_code = $ccAvenueCollectedDataArray[7]['status_code'];
        $newCcAvenueOrder->status_message = $ccAvenueCollectedDataArray[8]['status_message'];
        $newCcAvenueOrder->currency = $ccAvenueCollectedDataArray[9]['currency'];
        $newCcAvenueOrder->amount = $ccAvenueCollectedDataArray[10]['amount'];
        $newCcAvenueOrder->billing_details = json_encode(
            $billing_details = [
                'firstname' => $ccAvenueCollectedDataArray[11]['billing_name'],
                'email' => $ccAvenueCollectedDataArray[18]['billing_email'],
                'telephone' => '+91' . $ccAvenueCollectedDataArray[17]['billing_tel'],
                'line1' => $ccAvenueCollectedDataArray[12]['billing_address'],
                'city' => $ccAvenueCollectedDataArray[13]['billing_city'],
                'region' => $ccAvenueCollectedDataArray[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $ccAvenueCollectedDataArray[15]['billing_zip'],
            ],
        );
        // dd($ccAvenueCollectedDataArray);
        $newCcAvenueOrder->delivery_details = json_encode(
            $delivery_details = [
                'firstname' => $ccAvenueCollectedDataArray[11]['billing_name'],
                'email' => $ccAvenueCollectedDataArray[18]['billing_email'],
                'telephone' => '+91' . $ccAvenueCollectedDataArray[17]['billing_tel'],
                'line1' => $ccAvenueCollectedDataArray[12]['billing_address'],
                'city' => $ccAvenueCollectedDataArray[13]['billing_city'],
                'region' => $ccAvenueCollectedDataArray[14]['billing_state'],
                'country' => 'IN',
                'postcode' => $ccAvenueCollectedDataArray[15]['billing_zip'],
            ],
        );
        $newCcAvenueOrder->merchant_params = json_encode(
            $merchant_params = [
                'merchant_param1' => $ccAvenueCollectedDataArray[26]['merchant_param1'],
                'merchant_param2' => $ccAvenueCollectedDataArray[27]['merchant_param2'],
                'merchant_param3' => $ccAvenueCollectedDataArray[28]['merchant_param3'],
                'merchant_param4' => $ccAvenueCollectedDataArray[29]['merchant_param4'],
                'merchant_param5' => $ccAvenueCollectedDataArray[30]['merchant_param5'],
            ],
        );
        $newCcAvenueOrder->vault = $ccAvenueCollectedDataArray[31]['vault'];
        $newCcAvenueOrder->offer_type = $ccAvenueCollectedDataArray[32]['offer_type'];
        $newCcAvenueOrder->offer_code = $ccAvenueCollectedDataArray[33]['offer_code'];
        $newCcAvenueOrder->discount_value = $ccAvenueCollectedDataArray[34]['discount_value'];
        $newCcAvenueOrder->mer_amount = $ccAvenueCollectedDataArray[35]['mer_amount'];
        $newCcAvenueOrder->eci_value = $ccAvenueCollectedDataArray[36]['eci_value'];
        $newCcAvenueOrder->retry = $ccAvenueCollectedDataArray[37]['retry'];
        $newCcAvenueOrder->response_code = $ccAvenueCollectedDataArray[38]['response_code'];
        $newCcAvenueOrder->billing_notes = $ccAvenueCollectedDataArray[39]['billing_notes'];
        $newCcAvenueOrder->trans_date = $ccAvenueCollectedDataArray[40]['trans_date'];
        $newCcAvenueOrder->bin_country = $ccAvenueCollectedDataArray[41]['bin_country'];
        $newCcAvenueOrder->price = $qsOrderDetails['price'];
        $newCcAvenueOrder->qty = $qsOrderDetails['qty'];
        // $newCcAvenueOrder->sku = $qsOrderDetails['sku'];
        // $newCcAvenueOrder->price = 2000;
        // $newCcAvenueOrder->qty = 2;
        // $newCcAvenueOrder->currency_code = 356;
        $newCcAvenueOrder->save();

        $prepareBillingDetails = [
            'order_id' => $ccAvenueCollectedDataArray[0]['order_id'],
            'reference_id' => $ccAvenueCollectedDataArray[1]['tracking_id'],
            'order_date' => $ccAvenueCollectedDataArray[40]['trans_date'],
            'billing_name' => $ccAvenueCollectedDataArray[11]['billing_name'],
            'billing_email' => $ccAvenueCollectedDataArray[18]['billing_email'],
            'billing_tel' => $ccAvenueCollectedDataArray[17]['billing_tel'],
            'billing_address' => $ccAvenueCollectedDataArray[12]['billing_address'] . ', ' . $ccAvenueCollectedDataArray[13]['billing_city'] . ', ' . $ccAvenueCollectedDataArray[14]['billing_state'] . ' ' . $ccAvenueCollectedDataArray[15]['billing_zip'] . '. ' . $ccAvenueCollectedDataArray[16]['billing_country'],
            'payment_mode' => $ccAvenueCollectedDataArray[5]['payment_mode'] . ' - ' . $ccAvenueCollectedDataArray[6]['card_name'],
            'bank_ref_no' => $ccAvenueCollectedDataArray[2]['bank_ref_no'],
            'order_amount' => 'INR ' . $ccAvenueCollectedDataArray[10]['amount'],
            'net_payable' => 'INR ' . $ccAvenueCollectedDataArray[10]['amount'],
            'contact_person' => $ccAvenueCollectedDataArray[19]['delivery_name'] . ' | ' . $ccAvenueCollectedDataArray[25]['delivery_tel'],
            'shipping_address' => $ccAvenueCollectedDataArray[20]['delivery_address'] . ', ' . $ccAvenueCollectedDataArray[21]['delivery_city'] . ', ' . $ccAvenueCollectedDataArray[22]['delivery_state'] . ' ' . $ccAvenueCollectedDataArray[23]['delivery_zip'] . '. ' . $ccAvenueCollectedDataArray[24]['delivery_country'],
        ];

        $productAllData = QsProduct::all();

        $order = QsOrder::where('order_id', $prepareBillingDetails['order_id'])->first();

        // $cardsArray = json_decode($qsOrderDetails['cards'], true);
        $cardsArray = json_decode(decrypt($qsOrderDetails['cards'], env('ENCRYPTION_KEY')), true);

        // Retrieve data from the gift_card table to reciver detail purpose
        $giftCardData = GiftCard::where('order_id', $prepareBillingDetails['order_id'])->first();

        $shipToName = $giftCardData->receiver_name;
        $shipToEmail = $giftCardData->receiver_email;
        $shipToContactNo = $giftCardData->receiver_mobile;
        $giftSendOption = $giftCardData->gift_send_option;
        $deliveryMode = $giftCardData->delivery_mode;
        // dd($deliveryMode);
        // Decode the JSON fields
        $cardsData = json_decode(decrypt($order->cards, env('ENCRYPTION_KEY')));

        // dd($order->cards, $cardsData);
        $cardSku = $cardsData[0]->sku;
        $cardProductName = $cardsData[0]->productName;

        // Retrieve the product with the matching sku from the QsProduct table
        $product = QsProduct::where('sku', $cardSku)->first();
        // dd($product);

        if ($product) {
            // The product with the specified sku was found
            $images = json_decode($product->images, true);

            if ($images && isset($images['small'])) {
                $smallImageUrl = $images['small'];

                // Now you can use $thumbnailUrl in your HTML to display the image
            } else {
                // Handle the case where the thumbnail URL is missing
                dd('thumbnail is missing');
            }
        } else {
            // No product found with the specified sku
            // Handle this case according to your requirements
            dd('no product found');
        }

        if ($order_status === 'Success') {
            $order->update(['order_status' => 'COMPLETE']);
            $email = Auth::user()->email;
            $name = Auth::user()->name;

            // Generate Invoice Number and Invoice Date
            $invoiceNumber = 'AMZ-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            $invoiceDate = date('d-m-Y');

            // Send mail to the buyer
            $prepareMailDetails = [
                'name' => $name,
                'order_id' => $prepareBillingDetails['order_id'],
                'reference_id' => $prepareBillingDetails['reference_id'],
                'order_date' => $prepareBillingDetails['order_date'],
                'billing_name' => $prepareBillingDetails['billing_name'],
                'billing_email' => $prepareBillingDetails['billing_email'],
                'billing_tel' => $prepareBillingDetails['billing_tel'],
                'billing_address' => $prepareBillingDetails['billing_address'],
                'payment_mode' => $prepareBillingDetails['payment_mode'],
                'bank_ref_no' => $prepareBillingDetails['bank_ref_no'],
                'order_amount' => $prepareBillingDetails['order_amount'],
                'net_payable' => $prepareBillingDetails['net_payable'],
                'contact_person' => $prepareBillingDetails['contact_person'],
                'shipping_address' => $prepareBillingDetails['shipping_address'],
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
                'giftSendOption' => $giftSendOption,
            ];
            // dd($prepareMailDetails);
            // Send mail to the buyer
            $prepareSmsDetails = [
                'name' => $name,
                'order_id' => $prepareBillingDetails['order_id'],
                'reference_id' => $prepareBillingDetails['reference_id'],
                'order_date' => $prepareBillingDetails['order_date'],
                'billing_name' => $prepareBillingDetails['billing_name'],
                'order_amount' => $prepareBillingDetails['order_amount'],
                'cardSku' => $cardSku,
                'cardProductName' => $cardProductName,
                'shipToName' => $shipToName,
                'shipToContactNo' => $shipToContactNo,
                'perOrderPrice' => $order->price,
                'perOrderQuantity' => $order->qty,
                'giftSendOption' => $giftSendOption,
                'billing_tel' => $prepareBillingDetails['billing_tel'],
            ];
            // dd($prepareSmsDetails);

            if ($deliveryMode == 'both') {
                $this->sendTransactionMail($prepareMailDetails, $email, $name);
                $this->sendTransactionalMessage($prepareSmsDetails);
                $this->sendGiftMail($prepareMailDetails, $cardsArray, $shipToEmail, $shipToName);
                $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
            }

            if ($deliveryMode == 'email') {
                $this->sendTransactionMail($prepareMailDetails, $email, $name);
                $this->sendTransactionalMessage($prepareSmsDetails);
                $this->sendGiftMail($prepareMailDetails, $cardsArray, $shipToEmail, $shipToName);
            }

            if ($deliveryMode == 'mobile') {
                $this->sendTransactionMail($prepareMailDetails, $email, $name);
                $this->sendTransactionalMessage($prepareSmsDetails);
                $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
            }

            $msg = 'Order created successfully!';
            $status = 'success';
        } elseif ($order_status === 'Aborted' || $order_status === 'Failure') {
            $order->update(['order_status' => 'CANCELED']);
            $msg = 'Something went wrong. Please contact the support team if any money got deducted.';
            $status = 'failure';
            $name = Auth::user()->name;
            $email = Auth::user()->email;
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
    }
    public function sendTransactionMail($prepareMailDetails, $email, $name)
    {
        $pdf = PDF::loadView('layouts.invoice', $prepareMailDetails);
        Mail::send(['html' => 'layouts.mail'], $prepareMailDetails, function ($message) use ($email, $name, $pdf) {
            $message
                ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                ->to($email, $name)
                ->subject(config('companyDefaultValues.default_subject'))
                ->attachData($pdf->output(), 'invoice.pdf');
        });
    }
    public function sendGiftMail($prepareMailDetails, $cardsArray, $shipToEmail, $shipToName)
    {
        // Check if gift name is present before sending gift mail
        if (isset($prepareMailDetails['giftSendOption'])) {
            Mail::send(['html' => 'layouts.giftmail'], ['prepareMailDetails' => $prepareMailDetails, 'cardsArray' => $cardsArray], function ($message) use ($shipToEmail, $shipToName) {
                $message
                    ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                    ->to($shipToEmail, $shipToName)
                    ->subject(config('companyDefaultValues.gift_subject'));
            });
        }
        $msg = 'Order created successfully!';
        $status = 'success';
    }

    public function sendTransactionalMessage($prepareSmsDetails)
    {
        // Extract values from $prepareSmsDetails
        $name = $prepareSmsDetails['name'];
        $orderAmount = $prepareSmsDetails['order_amount'];
        $orderNumber = $prepareSmsDetails['order_id'];
        $productName = $prepareSmsDetails['cardProductName'];
        $destination = $prepareSmsDetails['billing_tel'];

        // Configure SMS API parameters
        $sms_api_url = config('transactionSms.sms_api_url');
        $sms_user_name = config('transactionSms.sms_user_name');
        $sms_user_password = config('transactionSms.sms_user_password');
        $sms_source = config('transactionSms.sms_source');
        $sms_message = 'Hello ' . $name . ', Your order no ' . $orderNumber . ' of ' . $orderAmount . ' is generated successfully. Please check out respected Email for that. Thanks - FRENETIC INDIA.';
        $sms_entity_id = config('transactionSms.sms_entity_id');
        $sms_temp_id = config('transactionSms.sms_temp_id');

        // Construct the API URL with the message
        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";
        // dd($apiUrl);

        // Send the HTTP GET request to the API
        $response = Http::get($apiUrl);

        // Log the response for debugging
        // \Log::info('API Response:', ['response' => $response]);
        // \Log::info('response status:', ['response status' => $response->status()]);
        // \Log::info('API URL IS:', ['API URL' => $apiUrl]);
    }

    public function sendGiftMessage($prepareSmsDetails, $cardsArray)
{
    // Extract values from $prepareSmsDetails
    $name = $prepareSmsDetails['shipToName'];
    $orderNumber = $prepareSmsDetails['order_id'];
    $orderAmount = $prepareSmsDetails['order_amount'];
    $destination = prepareSmsDetails['shipToContactNo'];

    // Configure SMS API parameters
    $sms_api_url = config('giftSms.sms_api_url');
    $sms_user_name = config('giftSms.sms_user_name');
    $sms_user_password = config('giftSms.sms_user_password');
    $sms_source = config('giftSms.sms_source');
    $sms_entity_id = config('giftSms.sms_entity_id');
    $sms_temp_id = config('giftSms.sms_temp_id');

    foreach ($cardsArray as $card) {
        $cardId = $card['cardNumber'];
        $cardPin = $card['cardPin'];
        $cardAmount = $card['amount'];
        $cardActivationCode = $card['activationCode'];
        $cardActivationURL = $card['activationUrl'];
        $cardValidity = date('d-M-Y', strtotime($card['validity']));

        // Build the SMS message for this card
        $sms_message = 'Hello ' . $name . ' You received a gift card and your Card details: ' .
            'Card ID: ' . $cardId .
            ' Card Pin: ' . $cardPin .
            ' Amount ' . $cardAmount . 
            ' Activation Code ' .  $cardActivationCode . 
            ' Activation URL ' . $cardActivationURL . 
            ' Validity ' . $cardValidity .
            ' Please check your respected Email for more information. Thanks - FRENETIC INDIA';

        // Construct the API URL with the message
        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";

        // Send the HTTP GET request to the API for this card
        $response = Http::get($apiUrl);

        // Log the response for debugging
        \Log::info('API Response:', ['response' => $response]);
        \Log::info('response status:', ['response status' => $response->status()]);
        \Log::info('API URL IS:', ['API URL' => $apiUrl]);
    }
}

}
