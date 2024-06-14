<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\QsOrder;
use Carbon\Carbon;
use PDF;
use Mail;
use App\Helpers\CommonHelper;
use Exception;
use Illuminate\Http\Client\ConnectionException;

class WoohooOrderController extends Controller
{
    public function createOrder(Request $request)
    {

        // Retrieve payment data from session
        $qsOrderDetails = Session::get('payment_data');
        $isSuccessful = false;
        $orderCreatedResponse = $this->createWoohooOrderRequest($qsOrderDetails);

        if ($orderCreatedResponse) {
            $statusCode = isset($orderCreatedResponse['status_code']) ? $orderCreatedResponse['status_code'] : null;

            if (isset($orderCreatedResponse['status']) && $orderCreatedResponse['status'] == 'COMPLETE') {
                $transactionStatusMessage = __('errors.201');
                $isSuccessful = true;
                $this->handleSuccessFullOrder($orderCreatedResponse);
            } else {
                // Handle other status codes

                $statusCode = isset($orderCreatedResponse['status']) ? $orderCreatedResponse['status'] : null;

                $transactionStatusMessage = __('errors.' . ($statusCode ?? 'default'));
                Log::info('Order status is not complete yet: ');

            }


        } else {
            // Handle case where $orderCreatedResponse is null
            $transactionStatusMessage = __('errors.500');
            Log::info('Order failed');
        }

        return view('order.order-status', compact('transactionStatusMessage', 'isSuccessful'));
    }

    public function createWoohooOrderRequest($qsOrderDetails)
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
                    'amount' => $qsOrderDetails->grand_payable_amount,
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

        Log::info("######################\nWoohoo create order request body data is:\n" . print_r($create_order_request_body_data, true) . "\n######################");

        $requestBody = json_encode($create_order_request_body_data);
        $requestHttpMethod = 'post';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();
        $refno = $create_order_request_body_data['refno'];

        Log::info('**************** Order Creation Api *************************' . "\n");
        Log::info('Before hitting API, time is ' . now() . "\n");

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
            Log::info('********************** Check order API hit response whether received or not ***************************' . "\n");

            Log::info('Get Response from Woohoo server ' . $createOrderResponse . "\n");

            Log::info('Woohoo API Response:', [
                'status_code' => $createOrderResponse->status(),
            ]);

            if ($createOrderResponse->successful()) {
                Log::info('Order creation was successful within 10 seconds');

                $orderCreatedResponse = $createOrderResponse->json();

                // Log the full Woohoo API response
                Log::info('Woohoo API Response: ' . json_encode($orderCreatedResponse));

                // Check for the "PROCESSING" status
                if (isset($orderCreatedResponse['status']) && $orderCreatedResponse['status'] === 'PROCESSING') {
                    Log::info('Order is in PROCESSING status');
                    // You can add more processing logic here if needed
                    return view('order.order-status', ['transactionStatusMessage' => 'Your order is currently being processed.']);
                }
                return $orderCreatedResponse;
            } else {
                return $this->handleErrorResponse($createOrderResponse, $sku);
            }
        } catch (ConnectionException $e) {
            Log::error('cURL Error: lets go to getStatusByReferenceNumber' . $e->getMessage());
            sleep(30);

            $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);

            if ($statusFunctionResponse && $statusFunctionResponse['status'] == 'COMPLETE') {
                Log::info('Status function response is complete. Returning response.');
                return $statusFunctionResponse;
            } else {
                Log::info('Status function response is not complete. Returning false.');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Unexpected exception: unexpected exception while hitting the create order api by woohoo' . $e->getMessage());
            return $this->handleUnexpectedErrorResponse($e);
        }
    }

    private function handleErrorResponse($createOrderResponse, $sku)
    {
        $statusCode = $createOrderResponse->status();

        // Handle specific error codes based on the status code received from the API
        switch ($statusCode) {
            case 400:
                $transactionStatusMessage = __('errors.400');
                break;
            case 401:
                $transactionStatusMessage = __('errors.401');
                break;
            case 403:
                $transactionStatusMessage = __('errors.403');
                break;
            // Add cases for other error codes as needed
            default:
                $transactionStatusMessage = __('errors.default');
                break;
        }

        // Log the error message
        Log::error("Error $statusCode: " . $transactionStatusMessage);

        // Perform actions based on the error code
        if (in_array($statusCode, ['5305', '5307', '5308', '5321'])) {
            Log::info("Redirecting to checkout page due to error $statusCode for SKU $sku with message: $transactionStatusMessage");
            return redirect()
                ->route('checkoutPage', ['sku' => $sku])
                ->withErrors(['error' => $transactionStatusMessage]);
        } else {
            Log::info("Displaying order status page due to error $statusCode with message: $transactionStatusMessage");
            return view('order.order-status', ['transactionStatusMessage' => $transactionStatusMessage]);
        }
    }

    private function handleUnexpectedErrorResponse($exception)
    {
        $transactionStatusMessage = __('error.technical_error');
        Log::error('Unexpected Error: ' . $exception->getMessage());
        return view('order.order-status', ['transactionStatusMessage' => $transactionStatusMessage]);
    }

    private function getErrorMessage($statusCode)
    {
        $message = __('error.' . $statusCode);
        if ($message === 'error.' . $statusCode) {
            $message = __('error.default');
        }
        return $message;
    }

    public function getStatusByReferenceNumber($refno)
{
    Log::info('Starting getStatusByReferenceNumber for reference number: ' . $refno);
    $attempt = 1;
    $max_retries = 2; // Change this to the desired number of retries
    $retry_interval = 40; // Retry interval in seconds
    $requestHttpMethod = 'GET';
    $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/order/' . $refno . '/status';
    $clientSecret = setting('api.qs_clientSecret');
    $bearerToken = setting('api.bearer_token');
    $requestBody = '';
    $dateAtClient = Carbon::now()->toIso8601String();
    $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

    while ($attempt <= $max_retries) {
        Log::info('Attempt ' . $attempt . ' to fetch status from API.');
        try {
            $cardStatusApiResponse = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'signature' => $signature,
                    'dateAtClient' => $dateAtClient,
                ])
                ->get($absApiUrl);

            Log::info('API request sent for attempt ' . $attempt . '. URL: ' . $absApiUrl);
            Log::info('API response status code: ' . $cardStatusApiResponse->status());

            if ($cardStatusApiResponse->status() == 200) {
                $cardStatusApiResponseData = json_decode($cardStatusApiResponse->getBody(), true);

                Log::info('API response body: ' . json_encode($cardStatusApiResponseData));

                if ($cardStatusApiResponseData['status'] === 'COMPLETE') {
                    Log::info('Card activation status is COMPLETE. Processing further.');
                    $activatedCardReponseFromFunction = $this->callCardActivation($cardStatusApiResponseData);
                    return $activatedCardReponseFromFunction;
                } elseif ($cardStatusApiResponseData['status'] === 'PROCESSING') {
                    Log::info('Card activation status is PROCESSING. Waiting for ' . $retry_interval . ' seconds before retrying.');
                    sleep($retry_interval);
                } else {
                    Log::info('Card activation status is neither PROCESSING nor COMPLETE. Status: ' . $cardStatusApiResponseData['status']);
                    return false;
                }
            } else {
                Log::info('Order failed, response 200 not received. Response status: ' . $cardStatusApiResponse->status());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred during attempt ' . $attempt . ': ' . $e->getMessage());
            return false;
        }

        $attempt++;
    }

    Log::info('Max retries reached (' . $max_retries . ') without reaching a complete status.');
    return false;
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
        $dateAtClient = Carbon::now()->toIso8601String();
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

    public function getFinancialYear()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        if ($currentMonth >= 4) {
            $nextYear = $currentYear + 1;
            return $currentYear . '/' . substr($nextYear, -2);
        } else {
            $previousYear = $currentYear - 1;
            return $previousYear . '/' . substr($currentYear, -2);
        }
    }

    public function handleSuccessFullOrder($orderCreatedResponse)
    {
        $isSuccessful = false;

        Log::info('This is card response data: ' . json_encode($orderCreatedResponse));

        $orderId = $this->updateQsOrder($orderCreatedResponse);

        $order = QsOrder::join('cc_avenue_payment', 'cc_avenue_payment.order_id', '=', 'qs_ordered.id')->join('qs_products', 'qs_products.sku', '=', 'qs_ordered.sku')->where('qs_ordered.id', $orderId)->select('qs_ordered.*', 'cc_avenue_payment.*', 'qs_products.*')->first();

        $financialYear = $this->getFinancialYear();

        $invoiceNumber = 'FRB2C-' . $financialYear . '-' . $orderId;
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
            'grand_payable_amount' => $order['grand_payable_amount'],
            'gst_number' => $order['gst_number'] ?: 'Unregistered',
            'discount' => $order['discounted_amount_value'],
            'amount_payable_after_discount' => $order['amount_payable_after_discount'],
            'contact_person' => $order['sender_first_name'],
            'shipping_address' => $order['delivery_mode'] === 'email' ? $order['sender_email'] : $order['sender_address_1'] . ' ' . $order['sender_address_2'] . ', ' . $order['sender_city'] . ', ' . $order['sender_state'] . ' ' . $order['sender_post_code'],
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'cardSku' => $order['sku'],
            'cardProductName' => $order['name'],
            'shipToName' => $order['receiver_name'] ?? $order['sender_first_name'],
            'shipToEmail' => $order['receiver_email'] ?? $order['sender_email'],
            'shipToContactNo' => $order['receiver_mobile'] ?? $order['sender_phone_no'],
            'quantity' => $order['quantity'],
            'smallImageUrl' => $smallImageUrl,
            'giftSendOption' => $order['gift_send_option'],
            'denomination' => $order['denomination'],
        ];

        $prepareSmsDetails = [
            'name' => $order['sender_first_name'],
            'order_id' => $order['woohoo_order_id'],
            'reference_id' => $order['id'],
            'order_date' => $order['created_at'],
            'billing_name' => $order['sender_first_name'],
            'order_amount' => $order['amount'],
            'cardSku' => $order['sku'],
            'cardProductName' => $order['name'],
            'shipToName' => $order['receiver_name'] ?? $order['sender_first_name'],
            'shipToContactNo' => $order['receiver_mobile'] ?? $order['sender_phone_no'],
            'grand_payable_amount"' => $order['grand_payable_amount"'],
            'perOrderQuantity' => $order['quantity'],
            'giftSendOption' => $order['gift_send_option'],
            'billing_tel' => $order['sender_phone_no'],
        ];

        if ($order['delivery_mode'] == 'both') {
            $this->sendTransactionMail($prepareMailDetails);
            // $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
            // $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
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

    public function updateQsOrder($orderCreatedResponse)
    {
        $isSuccessful = false;
        $qsOrderUpdate = QsOrder::where('id', $orderCreatedResponse['refno'])->first();

        // dd($orderCreatedResponse, $qsOrderUpdate);

        if ($qsOrderUpdate) {
            $qsOrderUpdate->update([
                'woohoo_order_id' => $orderCreatedResponse['orderId'],
                'order_status' => $orderCreatedResponse['status'],
                'cards' => encrypt(json_encode($orderCreatedResponse['cards']), env('ENCRYPTION_KEY')),
                'order_cancel' => json_encode($orderCreatedResponse['cancel']),
                'order_payment' => isset($orderCreatedResponse['payments']) ? json_encode($orderCreatedResponse['payments']) : null,
                'currency' => json_encode($orderCreatedResponse['currency']),
                'additionalTxnFields' => isset($orderCreatedResponse['additionalTxnFields']) ? json_encode($orderCreatedResponse['additionalTxnFields']) : null,
            ]);

            // dd($qsOrderUpdate->id,$qsOrderUpdate );
            return $qsOrderUpdate->id;

            // return ['joinedData' => $order, 'qsOrderUpdate' => $qsOrderUpdate];
        } else {
            $transactionStatusMessage = "Order with ID $refno not found.";
            Log::error($transactionStatusMessage);

            return view('order.order-status', compact('transactionStatusMessage', 'isSuccessful'));
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
}
