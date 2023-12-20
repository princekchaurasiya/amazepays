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
            Log::info('this is common fileds');
            Log::debug($commonFields);
            $newCcAvenueOrder->save();

            // $billingDetailsFields = ['11' => 'billing_name', '18' => 'billing_email', '17' => 'billing_tel', '12' => 'billing_address', '13' => 'billing_city', '14' => 'billing_state', '15' => 'billing_zip'];

            // $newCcAvenueOrder->billing_details = json_encode(
            //     array_combine(
            //         array_values($billingDetailsFields), // Use array_values to get the values (indices) as keys
            //         array_map(function ($index) use ($ccAvenueCollectedDataArray) {
            //             return $ccAvenueCollectedDataArray[$index];
            //         }, array_keys($billingDetailsFields)),
            //     ),
            // );

            // // Set delivery details (assuming it's the same as billing details)
            // $newCcAvenueOrder->delivery_details = $newCcAvenueOrder->billing_details;

            // // Set merchant params
            // $merchantParamsFields = ['26' => 'merchant_param1', '27' => 'merchant_param2', '28' => 'merchant_param3', '29' => 'merchant_param4', '30' => 'merchant_param5'];

            // $newCcAvenueOrder->merchant_params = json_encode(
            //     array_combine(
            //         array_values($merchantParamsFields),
            //         array_map(function ($index) use ($ccAvenueCollectedDataArray) {
            //             return $ccAvenueCollectedDataArray[$index];
            //         }, array_keys($merchantParamsFields)),
            //     ),
            // );

            // // Set other fields
            // $otherFields = ['31' => 'vault', '32' => 'offer_type', '33' => 'offer_code', '34' => 'discount_value', '35' => 'mer_amount', '36' => 'eci_value', '37' => 'retry', '38' => 'response_code', '39' => 'billing_notes', '40' => 'trans_date', '41' => 'bin_country'];

            // foreach ($otherFields as $index => $field) {
            //     $newCcAvenueOrder->{$field} = $ccAvenueCollectedDataArray[$index][$field];
            // }

            // $newCcAvenueOrder->save();

            Log::info('************* CC Avenue Data Stored Successfully ***************');

            if ($order_status === 'Success') {

                $qsOrderDetails = QsOrder::where('id', $ccAvenueCollectedDataArray[0]['order_id'])->first();
                $orderCreatedResponse = $this->createOrderRequest($qsOrderDetails);
                return view('order.myOrder', compact('orderCreatedResponse'));

            } else {
                $errorMessage = 'Payment failed Place new order and try payment again';
                session()->flash('error_message', $errorMessage);
                return redirect()->route('error');
            }
        } catch (\Exception $e) {
            Log::error('Exception in CC Avenue response handling: ' . $e->getMessage());
            $errorMessage = $e->getMessage();
            session()->flash('error_message', $errorMessage);
            return redirect()->route('error');
        }
    }

    public function createOrderRequest($qsOrderDetails)
    {
        Log::info('***** Order Details ******');
        Log::info($qsOrderDetails);

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
        Log::info('Before hitting API time is ' . now() . "\n");
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

            if ($createOrderResponse->successful()) {
                // Log::info('Order creation was successful within 10 seconds');

                $createOrderResponseData = $createOrderResponse->json();
                // Log::info($createOrderResponseData);


                // $status = $this->updateQsOrder($createOrderResponseData);
                return $createOrderResponseData;

            } elseif ($createOrderResponse->clientError()) {
                // Log client error and store error message and code in flash
                Log::error('Client error: ' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();
                Session::flash('error', "Client error occurred (Code: $errorCode). Please try again.");
                return redirect()->route('error');
            } elseif ($createOrderResponse->serverError()) {
                Log::error('Server error: ' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();
                $errorMessage = $createOrderResponse->body();
                Session::flash('error', "Server error occurred (Code: $errorCode). $errorMessage");
                return redirect()->route('error');
            } elseif ($createOrderResponse->failed()) {
                Log::error('Order failed check status' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();
                Session::flash('error', "Order creation failed (Code: $errorCode). Please try again.");
                return redirect()->route('error');
            } else {
                // Log unexpected status code and store error message in flash
                Log::error('Unexpected status code: ' . $createOrderResponse);
                $errorCode = $createOrderResponse->status();
                Session::flash('error', "Unexpected error occurred (Code: $errorCode). Please try again.");
                return redirect()->route('error');
            }
        } catch (ConnectionException $e) {
            // Handle the cURL error here
            Log::error('cURL Error happened request broken in between ' . $e->getMessage());
            Log::alert('Curl errors happened Logging before 30-second delay ' . now());
            sleep(30);
            Log::alert('Curl Errors happened Hitting order status API after a 30-second delay that is a total 40-second delay after order creation API Hit ' . now());
            $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);

            // Log and store error message and code in flash
            Log::error('Status function response: ' . $statusFunctionResponse);
            Session::flash('error', 'An error occurred while checking order status. Please try again.');

            return redirect()->route('error');
        }


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
                'order_payment' => json_encode($createOrderResponseData['payments']),
                'currency' => json_encode($createOrderResponseData['currency']),
                'additionalTxnFields' => json_encode($createOrderResponseData['additionalTxnFields']),
            ]);

            return true;
        } else {
            $errorMessage = "Order with ID $refno not found.";
            Log::error($errorMessage);

            Session::flash('error', $errorMessage);

            return Redirect::route('error');
        }
    }
}
