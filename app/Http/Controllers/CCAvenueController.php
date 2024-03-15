<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Session;
use Illuminate\Support\Facades\Redirect;
use App\Models\QsOrder;
use App\Models\CcAvenuePayment;
use App\Helpers\CommonHelper;
use Carbon;
use GuzzleHttp\Client;
use Auth;
use DB;
use Mail;
use App\QsProduct;
use PDF;
use App\Models\GiftCard;
use Config;
use Exception;
use Illuminate\Http\Client\ConnectionException;

class CCAvenueController extends Controller
{
    public function processPayment(Request $request)
    {
        Log::info('process payment request');
        Log::info($request);
        $input = $request->all();
        $sessionId = Session::get('session_qs_order_id');
        $qsOrder = QsOrder::where('id', $sessionId)->first();

        if ($qsOrder) {
            $qsOrder->update([
                'sender_first_name' => $request->billing_name,
                'sender_email' => $request->billing_email,
                'sender_phone_no' => $request->billing_tel,
                'sender_post_code' => $request->billing_zip,
                'sender_address_1' => $request->billing_address,
                'sender_address_2' => $request->billing_address_two,
                'sender_city' => $request->billing_city,
                'sender_state' => $request->billing_state,
                'sku' => $request->sku,
                'amount' => $request->amount,
            ]);
        } else {
            // Record with the given session ID not found
            $errorMessage = 'Record not found for session ID';
            Log::info("Record not found for session ID: $sessionId");
            return view('your.view', compact('errorMessage'));
        }

        $input['amount'] = $request['amount'];
        $input['order_id'] = $sessionId;
        $input['currency'] = $request['currency'];
        $input['redirect_url'] = $request['redirect_url'];
        $input['cancel_url'] = $request['cancel_url'];
        $input['language'] = $request['language'];
        $input['merchant_id'] = config('paymentconfig.merchant_id');

        $data = $input;
        return view('paymentFolder.ccavRequestHandler', compact('data'));
    }

    public function encryptCCAvenue($plainText, $key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    public function decryptCCAvenue($encryptedText, $key)
    {
        // $key = hextobin(md5($key));
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    public function pkcs5_padCC($plainText, $blockSize)
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);
        return $plainText . str_repeat(chr($pad), $pad);
    }

    public function hextobin($hexString)
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

    public function responseCcavenue(Request $request)
    {
        try {
            $workingKey = config('paymentconfig.working_key');
            $encResponse = $_POST['encResp'];
            $rcvdString = $this->decryptCCAvenue($encResponse, $workingKey); //Crypto Decryption used as per the specified working key.
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

            Log::info('************* CC Avenue Response ***************');
            Log::info($decryptValues);

            $qsOrderDetails = QsOrder::where('id', $ccAvenueCollectedDataArray[0]['order_id'])->first();

            $newCcAvenueOrder = new CcAvenuePayment();
            $newCcAvenueOrder->user_id = $qsOrderDetails['user_id'];
            $newCcAvenueOrder->price = $qsOrderDetails['denomination'];
            $newCcAvenueOrder->qty = $qsOrderDetails['quantity'];

            $commonFields = [
                '0' => 'order_id',
                '1' => 'tracking_id',
                '2' => 'bank_ref_no',
                '3' => 'order_status',
                '4' => 'failure_message',
                '5' => 'payment_mode',
                '6' => 'card_name',
                '7' => 'status_code',
                '8' => 'status_message',
                '9' => 'currency',
                '10' => 'amount',
                '11' => 'billing_name',
                '12' => 'billing_address',
                '13' => 'billing_city',
                '14' => 'billing_state',
                '15' => 'billing_zip',
                '16' => 'billing_country',
                '17' => 'billing_tel',
                '18' => 'billing_email',
                '19' => 'delivery_name',
                '20' => 'delivery_address',
                '21' => 'delivery_city',
                '22' => 'delivery_state',
                '23' => 'delivery_zip',
                '24' => 'delivery_country',
                '25' => 'delivery_tel',
                '26' => 'merchant_param1',
                '27' => 'merchant_param2',
                '28' => 'merchant_param3',
                '29' => 'merchant_param4',
                '30' => 'merchant_param5',
                '31' => 'vault',
                '32' => 'offer_type',
                '33' => 'offer_code',
                '34' => 'discount_value',
                '35' => 'mer_amount',
                '36' => 'eci_value',
                '37' => 'retry',
                '38' => 'response_code',
                '39' => 'billing_notes',
                '40' => 'trans_date',
                '41' => 'bin_country',
            ];

            // Set common fields
            foreach ($commonFields as $index => $field) {
                $newCcAvenueOrder->{$field} = $ccAvenueCollectedDataArray[$index][$field];
            }

            $newCcAvenueOrder->save();

            Log::info('************* CC Avenue Data Stored Successfully ***************');

            if ($order_status === 'Success') {
                $qsOrderDetails = QsOrder::where('id', $ccAvenueCollectedDataArray[0]['order_id'])->first();

                $orderCreatedResponse = $this->createOrderRequest($qsOrderDetails);


                if ($orderCreatedResponse) {
                    if (isset($orderCreatedResponse['status']) && $orderCreatedResponse['status'] == 'COMPLETE') {
                        $isSuccessFullOrder = $this->handleSuccessFullOrder($orderCreatedResponse);
                    } elseif (isset($createOrderResponseData['status']) && $createOrderResponseData['status'] == 'PROCESSING') {
                        $refno = $orderCreatedResponse['refno'];

                        $processingResponse = $this->getStatusByReferenceNumber($refno);
                        if ($processingResponse) {
                            $this->updateQsOrder($processingResponse);
                        } else {
                            return view('order.order-failed');
                        }
                    } else {
                        return view('order.order-failed');
                    }
                } else {
                    return view('order.order-failed');
                }

                // return redirect()->route('create-order', ['qsOrderDetails' => $qsOrderDetails->toArray()]);

                // return view('order.myOrder', compact('orderCreatedResponse'));
                return view('paymentFolder.payment-success');
            } else {
                $errorMessage = 'Payment failed Place new order and try payment again';
                session()->flash('error-message', $errorMessage);
                return redirect()->route('error');
            }
        } catch (\Exception $e) {
            Log::error('Exception in CC Avenue response handling: ' . $e->getMessage());
            return view('order.order-failed');
        }
    }

    public function createOrderRequest($qsOrderDetails)
    {
        $create_order_request_body_data = [
            'address' => [
                'firstname' => $qsOrderDetails->sender_first_name,
                'lastname' => 'test',
                'email' => $qsOrderDetails->sender_email,
                'telephone' => '+91' . $qsOrderDetails->sender_phone_no,
                'line1' => $qsOrderDetails->sender_address_1,
                'line2' => $qsOrderDetails->sender_address_2,
                'city' => $qsOrderDetails->sender_city,
                'region' => $qsOrderDetails->sender_state,
                'country' => 'IN',
                'postcode' => $qsOrderDetails->sender_post_code,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'billing' => [
                'firstname' => $qsOrderDetails->sender_first_name,
                'lastname' => 'test',
                'email' => $qsOrderDetails->sender_email,
                'telephone' => '+91' . $qsOrderDetails->sender_phone_no,
                'line1' => $qsOrderDetails->sender_address_1,
                'line2' => $qsOrderDetails->sender_address_2,
                'city' => $qsOrderDetails->sender_city,
                'region' => $qsOrderDetails->sender_state,
                'country' => 'IN',
                'postcode' => $qsOrderDetails->sender_post_code,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'payments' => [
                [
                    'code' => 'svc',
                    'amount' => $qsOrderDetails->amount, // take from selected front end
                ],
            ],
            'refno' => $qsOrderDetails->id,

            'products' => [
                [
                    'sku' => $qsOrderDetails->sku,
                    'price' => $qsOrderDetails->denomination,
                    'qty' => $qsOrderDetails->quantity,
                    'currency' => '356',
                ],
            ],
            'syncOnly' => $qsOrderDetails->quantity > 10 ? false : true,
            'delivery_mode' => 'API',
        ];

        $sku = $qsOrderDetails->sku;

        Log::info('######################');
        Log::info($create_order_request_body_data);

        $requestBody = json_encode($create_order_request_body_data);
        $requestHttpMethod = 'post';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $refno = $create_order_request_body_data['refno'];

        Log::info('**************** Order Creation Api *************************' . "\n");
        Log::info('Before prince hitting API time is ' . now() . "\n");
        try {
            // Make the HTTP request
            $createOrderResponse = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->send('POST', $absApiUrl, [
                    'body' => $requestBody,
                ]);

            Log::info('API Request:', [
                'url' => $absApiUrl,
                'method' => $requestHttpMethod,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ],
                'data' => $requestBody,
            ]);

            Log::info("\n");
            Log::info('********************** check order api hit response whether received or not ***************************' . "\n");

            Log::info('Get Response from woohoo server ' . $createOrderResponse . "\n");

            Log::info('Woohoo API Response:', [
                'status_code' => $createOrderResponse->status(),
            ]);

            if ($createOrderResponse->successful()) {
                Log::info('Order creation was successful within 10 seconds');

                $createOrderResponseData = $createOrderResponse->json();

                return $createOrderResponseData;
            } elseif ($createOrderResponse->clientError()) {
                $errorCode = $createOrderResponse->status();

                switch ($errorCode) {
                    case 400:
                        // Request data is invalid
                        Log::error('Error 400: Request data is invalid.');
                        // Flash an error message to the session
                        session()->flash('error', 'Request data is invalid.');
                        // Redirect to the desired route without using the return statement
                        return redirect()->route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $sku]);
                    case 5035:
                        // Payment svc is not available
                        Log::error('Error 5035: Payment svc is not available.');
                        // Handle accordingly
                        break;
                    case 5036:
                        // Payment amount mismatch
                        Log::error('Error 5036: Payment amount is not matching the uploaded value.');
                        // Handle accordingly
                        break;
                    case 5037:
                        // Payment svc model is not available
                        Log::error('Error 5037: Payment svc model is not available.');
                        // Handle accordingly
                        break;
                    case 5038:
                        // Payment svc exceeds limitations
                        Log::error('Error 5038: Payment svc exceeds limitations.');
                        // Handle accordingly
                        break;
                    case 5080:
                        // Payment amazon is restricted
                        Log::error('Error 5080: Payment amazon is restricted.');
                        // Handle accordingly
                        break;
                    // Add more cases for other error codes
                    default:
                        Log::error("Client error: Unexpected error with code $errorCode");
                        return redirect()->route('error');
                }

                return $this->$createOrderResponse->status();
            } elseif ($createOrderResponse->serverError()) {
                $errorCode = $createOrderResponse->status();
                $errorMessage = $createOrderResponse->body();

                switch ($errorCode) {
                    case 5305:
                        // Requested store is in-active
                        Log::error('Error 5305: Requested store is in-active.');
                        // Handle accordingly
                        return redirect()
                            ->route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $sku])
                            ->withErrors(['error' => 'Requested store is in-active.']);
                    case 5307:
                        // Denomination not available
                        Log::error('Error 5307: Denomination is not available.');
                        // Handle accordingly
                        return redirect()
                            ->route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $sku])
                            ->withErrors(['error' => 'Denomination is not available.']);
                    case 5308:
                        // Unable to process order due to product restrictions
                        Log::error('Error 5308: Unable to process your order as some of the products are restricted for your account.');
                        // Handle accordingly
                        return redirect()
                            ->route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $sku])
                            ->withErrors(['error' => 'Unable to process your order. Some products are restricted for your account.']);
                    // Add more cases for other error codes
                    default:
                        Log::error("Server error: Unexpected error with code $errorCode - $errorMessage");
                        return redirect()->route('error');
                }
                return $this->$createOrderResponse->status();
            } elseif ($createOrderResponse->failed()) {
                Log::error('Order failed check status' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();

                switch ($errorCode) {
                    case 5321:
                        // Order cannot be processed
                        Log::error('Error 5321: Order cannot be processed.');
                        // Handle accordingly
                        return redirect()
                            ->route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $sku])
                            ->withErrors(['error' => 'Order cannot be processed.']);
                    // Add more cases for other error codes
                    default:
                        Log::error("Unexpected error: Order creation failed with code $errorCode");
                        return redirect()->route('error');
                }

                return redirect()->route('error');
                return $this->$createOrderResponse->status();
            } else {
                // Log unexpected status code and store error message in flash
                Log::error('Unexpected status code: ' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();
                Session::flash('error', "Unexpected error occurred (Code: $errorCode). Please try again.");
                return redirect()->route('error');
                return $this->$createOrderResponse->status();
            }
        } catch (ConnectionException $e) {
            // Handle the cURL error here
            Log::error('cURL Error happened request broken in between ' . $e->getMessage());

            Log::alert('Curl errors happened Logging before 30-second delay ' . now());
            sleep(30);
            Log::alert('Curl Errors happened Hitting order status API after a 30-second delay that is a total 40-second delay after order creation API Hit ' . now());
            $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);

            if ($statusFunctionResponse && $statusFunctionResponse['status'] == 'COMPLETE') {
                return $statusFunctionResponse;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Unexpected exception in CC Avenue response handling: ' . $e->getMessage());
            $errorMessage = $e->getMessage();
            session()->flash('error-message', $errorMessage);
            return redirect()->route('error');
        }
    }

    public function getStatusByReferenceNumber($refno)
    {

        Log::info($refno);
        Log::info('You are in get Status Function');
        Log::info('attempt 1 has happened, go for step 2');
        $attempt = 1;
        $max_retries = 2; // Change this to the desired number of retries
        $retry_interval = 40; // Retry interval in seconds
        $requestHttpMethod = 'GET';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/order/' . $refno . '/status';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $requestBody = '';
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

        while ($attempt <= $max_retries) {
            $cardStatusApiResponse = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'signature' => $signature,
                    'dateAtClient' => $dateAtClient,
                ])
                ->get($absApiUrl);

            if ($cardStatusApiResponse->status() == 200) {
                $cardStatusApiResponseData = json_decode($cardStatusApiResponse->getBody(), true);

                if ($cardStatusApiResponseData['status'] === 'COMPLETE') {
                    // $orderId = $cardStatusApiResponseData['orderId'];
                    $activatedCardReponseFromFunction = $this->callCardActivation($cardStatusApiResponseData);
                    return $activatedCardReponseFromFunction;
                } elseif ($cardStatusApiResponseData['status'] === 'PROCESSING') {
                    // If the order is still processing, wait for the retry interval
                    sleep($retry_interval);
                } else {
                    Log::info('Anything other than processing or complete status');
                    return false;
                }
            } else {
                Log::info('Order failed, response 200 not received');
                return false;
            }

            $attempt++;
        }

        Log::info('Max retries reached without reaching a complete status.');
        return false;

        // You can handle the case where the maximum number of retries is reached without a complete status here.
    }

    public function callCardActivation($cardStatusApiResponseData)
    {
        Log::info('You are in Card Activation Function');
        $orderId = $cardStatusApiResponseData['orderId'];
        $clientSecret = setting('api.qs_clientSecret'); // Your client secret
        $bearerToken = setting('api.bearer_token'); // Your bearer token
        $apiUrl = 'https://' . setting('api.woohoo_url');
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $requestBody = '';
        $requestHttpMethod = 'GET';
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $activatedCardApiResponse = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'signature' => $signature,
                'dateAtClient' => $dateAtClient,
            ])
            ->get($absApiUrl);

        if ($activatedCardApiResponse->status() == 200) {
            Log::alert('card activation successfull');

            $activatedCardApiResponseData = $activatedCardApiResponse->json();

            // Create a new array by merging the two response data arrays
            $combinedData = array_merge($cardStatusApiResponseData, $activatedCardApiResponseData);

            return $combinedData;
        } else {
            Log::info('Order failed, response 200 not received');
            return false;
        }
    }


    public function cardDetails($orderID)
    {

        $clientSecret = setting('api.qs_clientSecret'); // Your client secret
        $bearerToken = setting('api.bearer_token'); // Your bearer token
        $apiUrl = 'https://' . setting('api.woohoo_url');
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderID}/cards";
        $requestBody = '';
        $requestHttpMethod = 'GET';
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

        $activatedCardApiResponse = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'signature' => $signature,
                'dateAtClient' => $dateAtClient,
            ])
            ->get($absApiUrl);

            Log::alert('card activation successfull');

            $activatedCardApiResponseData = $activatedCardApiResponse->json();



            dd($activatedCardApiResponseData);


    }







    public function updateQsOrder($createOrderResponseData)
    {
        $qsOrderUpdate = QsOrder::where('id', $createOrderResponseData['refno'])->first();

        if ($qsOrderUpdate) {
            $qsOrderUpdate->update([
                'woohoo_order_id' => $createOrderResponseData['orderId'],
                'order_status' => $createOrderResponseData['status'],
                'cards' => encrypt(json_encode($createOrderResponseData['cards']), env('ENCRYPTION_KEY')),
                'order_cancel' => json_encode($createOrderResponseData['cancel']),
                'order_payment' => isset($createOrderResponseData['payments']) ? json_encode($createOrderResponseData['payments']) : null,
                'currency' => json_encode($createOrderResponseData['currency']),
                'additionalTxnFields' => isset($createOrderResponseData['additionalTxnFields']) ? json_encode($createOrderResponseData['additionalTxnFields']) : null,
            ]);

            return $qsOrderUpdate->id;

            // return ['joinedData' => $order, 'qsOrderUpdate' => $qsOrderUpdate];
        } else {
            $errorMessage = "Order with ID $refno not found.";
            Log::error($errorMessage);

            Session::flash('error', $errorMessage);
            return Redirect::route('error');
        }
    }

    public function sendTransactionMail($prepareMailDetails)
    {
        $pdf = PDF::loadView('layouts.invoice', $prepareMailDetails);
        Mail::send(['html' => 'layouts.mail'], compact('prepareMailDetails', 'pdf'), function ($message) use ($prepareMailDetails, $pdf) {
            $message
                ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                ->to($prepareMailDetails['billing_email'], $prepareMailDetails['billing_name'])
                ->subject(config('companyDefaultValues.default_subject'))
                ->attachData($pdf->output(), 'invoice.pdf');
        });
        $msg = 'Trasnaction Mail created successfully!';
        $status = 'success';
    }

    public function sendGiftMail($prepareMailDetails, $cardsArray)
    {
        Mail::send(['html' => 'layouts.giftmail'], compact('prepareMailDetails', 'cardsArray'), function ($message) use ($prepareMailDetails) {
            $message
                ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'))
                ->to($prepareMailDetails['shipToEmail'], $prepareMailDetails['shipToName'])
                ->subject(config('companyDefaultValues.gift_subject'));
        });

        $msg = 'Gift Mail created successfully!';
        $status = 'success';
    }

    public function sendTransactionalMessage($prepareSmsDetails)
    {
        $name = $prepareSmsDetails['name'];
        $orderAmount = $prepareSmsDetails['order_amount'];
        $orderNumber = $prepareSmsDetails['order_id'];
        $productName = $prepareSmsDetails['cardProductName'];
        $destination = $prepareSmsDetails['billing_tel'];
        $sms_api_url = config('transactionSms.sms_api_url');
        $sms_user_name = config('transactionSms.sms_user_name');
        $sms_user_password = config('transactionSms.sms_user_password');
        $sms_source = config('transactionSms.sms_source');
        $sms_message = 'Hello ' . $name . ', Your order no ' . $orderNumber . ' of ' . $orderAmount . ' is generated successfully. Please check out respected Email for that. Thanks - FRENETIC INDIA.';
        $sms_entity_id = config('transactionSms.sms_entity_id');
        $sms_temp_id = config('transactionSms.sms_temp_id');

        // Construct the API URL with the message
        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";

        // Send the HTTP GET request to the API
        $response = Http::get($apiUrl);

        // Log the response for debugging
        \Log::info('API Response:', ['response' => $response]);
        \Log::info('response status:', ['response status' => $response->status()]);
        \Log::info('API URL IS:', ['API URL' => $apiUrl]);
    }

    public function sendGiftMessage($prepareSmsDetails, $cardsArray)
    {
        // Extract values from $prepareSmsDetails
        $name = $prepareSmsDetails['shipToName'];
        $orderNumber = $prepareSmsDetails['order_id'];
        $orderAmount = $prepareSmsDetails['order_amount'];
        $destination = $prepareSmsDetails['shipToContactNo'];

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
            $sms_message = 'Hello ' . $name . ' You received a gift card and your Card details: ' . 'Card ID: ' . $cardId . ' Card Pin: ' . $cardPin . ' Amount ' . $cardAmount . ' Activation Code ' . $cardActivationCode . ' Activation URL ' . $cardActivationURL . ' Validity ' . $cardValidity . ' Please check your respected Email for more information. Thanks - FRENETIC INDIA';

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

    public function handleSuccessFullOrder($createOrderResponseData)
    {
        Log::info($createOrderResponseData);
        $orderId = $this->updateQsOrder($createOrderResponseData);

        $order = QsOrder::join('cc_avenue_payment', 'cc_avenue_payment.order_id', '=', 'qs_ordered.id')
            ->join('qs_products', 'qs_products.sku', '=', 'qs_ordered.sku')
            ->where('qs_ordered.id', $orderId)
            ->select('qs_ordered.*', 'cc_avenue_payment.*', 'qs_products.*')
            ->first();

        $invoiceNumber = 'AMZ-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $invoiceDate = date('d-m-Y');
        $cardsArray = json_decode(decrypt($order['cards'], env('ENCRYPTION_KEY')), true);
        $images = json_decode($order['images'], true);

        if ($images && isset($images['small'])) {
            $smallImageUrl = $images['small'];
        }

        $prepareMailDetails = [
            'name' => $order['sender_first_name'],
            'order_id' => $order['woohoo_order_id'],
            'reference_id' => $order['id'],
            'order_date' => $order['created_at'],
            'billing_name' => $order['sender_first_name'],
            'billing_email' => $order['sender_email'],
            'billing_tel' => $order['sender_phone_no'],
            'billing_address' => $order['sender_address_1'] . ' ' . $order['sender_address_2'] . ', ' . $order['sender_city'] . ', ' . $order['sender_state'] . ' ' . $order['sender_post_code'],
            'payment_mode' => $order['payment_mode'],
            'bank_ref_no' => $order['bank_ref_no'],
            'order_amount' => $order['amount'],

            'net_payable' => $order['amount'],
            'contact_person' => $order['sender_first_name'],
            'shipping_address' => $order['delivery_mode'] === 'email' ? $order['sender_email'] : $order['sender_address_1'] . ' ' . $order['sender_address_2'] . ', ' . $order['sender_city'] . ', ' . $order['sender_state'] . ' ' . $order['sender_post_code'],
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'cardSku' => $order['sku'],
            'cardProductName' => $order['sku'],
            'shipToName' => $order['receiver_name'] ?? $order['sender_first_name'],
            'shipToEmail' => $order['receiver_email'] ?? $order['sender_email'],
            'shipToContactNo' => $order['receiver_mobile'] ?? $order['sender_phone_no'],
            'perOrderPrice' => $order['amount'],
            'perOrderQuantity' => $order['quantity'],
            'smallImageUrl' => $smallImageUrl,
            'giftSendOption' => $order['gift_send_option'],
        ];

        $prepareSmsDetails = [
            'name' => $order['sender_first_name'],
            'order_id' => $order['woohoo_order_id'],
            'reference_id' => $order['id'],
            'order_date' => $order['created_at'],
            'billing_name' => $order['sender_first_name'],
            'order_amount' => $order['amount'],
            'cardSku' => $order['sku'],
            'cardProductName' => $order['sku'],
            'shipToName' => $order['receiver_name'] ?? $order['sender_first_name'],
            'shipToContactNo' => $order['receiver_mobile'] ?? $order['sender_phone_no'],
            'perOrderPrice' => $order['amount'],
            'perOrderQuantity' => $order['quantity'],
            'giftSendOption' => $order['gift_send_option'],
            'billing_tel' => $order['sender_phone_no'],
        ];

        if ($order['delivery_mode'] == 'both') {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        } elseif ($order['delivery_mode'] == 'email') {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
        } elseif ($order['delivery_mode'] == 'mobile') {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        }
    }
}
