<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\ErrorHandler;
use App\Models\QsOrder;
use App\Models\OrderSummary;
use App\Models\UnlimitPayment;
use App\Models\Billing;
use Carbon\Carbon;
use PDF;
use Mail;
use App\Helpers\CommonHelper;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\URL;

class WoohooOrderController extends Controller
{
    public function createWoohooOrderRequest($qsOrderDetails,$payment)
{
    Log::info("******* Entered createWoohooOrderRequest ***********", [
        'qs_order_id' => $qsOrderDetails->id,
        'merchant_order_id' => $qsOrderDetails->merchant_order_id,
        'payment_status_param' => $payment->payment_status ?? null
    ]);

    // Use DB payment status
    $resolvedStatus = strtolower($payment->payment_status);

    Log::info("Resolved payment status", [
        "order_id" => $qsOrderDetails->id,
        "resolved_status" => $resolvedStatus
    ]);

    if (!in_array($resolvedStatus, ['success','completed','approved'])) {
        Log::info("Skipping Woohoo order creation because payment is not completed", [
            "order_id" => $qsOrderDetails->id,
            "payment_status" => $resolvedStatus
        ]);

        return [
            'success' => false,
            'message' => 'Payment not completed yet',
            'status' => 'FAILED'
        ];
    }

    $validStatuses = ['completed', 'success', 'approved'];
    $normalizedStatus = strtolower(trim($resolvedStatus));
        if (!in_array($normalizedStatus, $validStatuses)) {
            Log::info("Woohoo blocked payment status", [
                'order_id' => $qsOrder->id,
                'payment_status' => $resolvedStatus,
            ]);
            return[
            'success' => false,
            'message' => 'Payment not completed yet'
            ];
        }

    Log::info("Payment verified as COMPLETED, proceeding with Woohoo order creation", [
        'order_id' => $qsOrderDetails->id,
        'payment_status' => $resolvedStatus,
    ]);

    // ✅ Step 3: (Your existing Woohoo creation logic below — unchanged)
    $billinginfo = Billing::latest()->first();
    $billingName = $billinginfo->billing_name ?? '';
    $parts = preg_split('/\s+/', trim($billingName), 2);
    $firstName = $parts[0] ?? '';
    $lastName = $parts[1] ?? '';
    $refno = $qsOrderDetails->refno;

    $checkoutData = session('checkout_data', []);
    Log::info("Checkout data", ['data' => $checkoutData]);
    $productData = session('selected_product');
    Log::info("Product data", ['prodData' => $productData]);
    $normalizedSku = $qsOrderDetails->sku ?? ($productData['sku'] ?? null);
    $normalizedQuantity = (int) ($qsOrderDetails->quantity ?? ($checkoutData['quantity'] ?? 1));
    $normalizedDenomination = (float) ($qsOrderDetails->denomination ?? ($checkoutData['denomination'] ?? 0));

    $normalizedAmount = (float) (
        $qsOrderDetails->amount_payable_after_discount
        ?? $qsOrderDetails->grand_payable_amount
        ?? ($normalizedDenomination > 0 ? $normalizedDenomination * max(1, $normalizedQuantity) : 0)
    );

    // Woohoo expects the original price; do not send discounted amounts.
    $woohooPrice = (float) ($qsOrderDetails->price ?? 0);
    if ($woohooPrice <= 0) {
        $woohooPrice = $normalizedDenomination > 0 ? (float) $normalizedDenomination : $normalizedAmount;
    }

    $create_order_request_body_data = [
                "address" => [
            "firstname" => $firstName,
            "lastname" => $lastName,
            "email" => $billinginfo->billing_email,
            "telephone" => "+91" . $billinginfo->billing_tel,
            "line1" => $billinginfo->billing_address,
            "line2" => $billinginfo->billing_address_two,
            "city" => $billinginfo->billing_city,
            "region" => $billinginfo->billing_state,
            "country" => "IN",
            "postcode" => $billinginfo->billing_zip,
            "languages" => "Hindi",
            "billToThis" => true,
        ],
        "billing" => [
            "firstname" => $firstName,
            "lastname" => $lastName,
            "email" => $billinginfo->billing_email,
            "telephone" => "+91" . $billinginfo->billing_tel,
            "line1" => $billinginfo->billing_address,
            "line2" => $billinginfo->billing_address_two,
            "city" => $billinginfo->billing_city,
            "region" => $billinginfo->billing_state,
            "country" => "IN",
            "postcode" => $billinginfo->billing_zip,
            "languages" => "Hindi",
            "billToThis" => true,
        ],
        "payments" => [[
            "code" => "svc",
            "amount" => $woohooPrice,
        ]],
        "refno" => $refno,
        "products" => [[
            "sku" => $normalizedSku,
            "price" => $woohooPrice,
            "qty" => (int) $normalizedQuantity,
            "currency" => 356,
        ]],
        "syncOnly" => $normalizedQuantity > (int) env("SYNC_ONLY_THRESHOLD") ? false : true,
        "delivery_mode" => "API",
    ];

    $woohooTimeout = (int) env('WOOHOO_ORDER_TIMEOUT', 30);
    $woohooRetryAttempts = (int) env('WOOHOO_ORDER_RETRY_ATTEMPTS', 2);
    $woohooRetryDelay = (int) env('WOOHOO_ORDER_RETRY_DELAY_MS', 1500);

    try {
        $requestBody = json_encode($create_order_request_body_data);
        $requestHttpMethod = "post";
        $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/orders";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        Log::info("🚀 Sending Woohoo order API request", [
    'url' => $absApiUrl,
    'headers' => [
        'Authorization' => 'Bearer ' . substr($bearerToken, 0, 10) . '...', // partially masked
        'signature' => $signature,
        'dateAtClient' => $dateAtClient,
    ],
    'request_body' => $create_order_request_body_data,
    ]);


        $createOrderResponse = Http::acceptJson()
            ->timeout($woohooTimeout)
            ->retry($woohooRetryAttempts, $woohooRetryDelay, function ($exception) {
                return $exception instanceof ConnectionException;
            })
            ->withHeaders([
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $bearerToken,
                "Accept" => "*/*",
                "User-Agent" => "Amazepays/1.0 (+https://amazepays.in)",
                "dateAtClient" => $dateAtClient,
                "signature" => $signature,
            ])
            ->send("POST", $absApiUrl, ["body" => $requestBody]);

        $responseData = $createOrderResponse->json();
        Log::info("Woohoo API Response", [
            'status' => $createOrderResponse->status(),
            'response' => $responseData,
        ]);

        if ($createOrderResponse->successful() && isset($responseData["status"]) && $responseData["status"] === "COMPLETE") {
            return ['success' => true, 'status' => 'COMPLETE', 'data' => $responseData];
        }

        return ['success' => false, 'status_code' => $createOrderResponse->status(), 'response' => $responseData];
    } catch (ConnectionException $e) {
        Log::error('Woohoo API connection issue during order creation', [
            'error' => $e->getMessage(),
            'retries' => $woohooRetryAttempts,
            'timeout' => $woohooTimeout,
        ]);
        return ['success' => false, 'message' => 'Connection to Woohoo API timed out'];
    } catch (\Throwable $e) {
        Log::error('Error during Woohoo order creation', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

 public function createOrder(QsOrder $order)
{
    try {

        /**************************************
         * 1. Get merchant order ID
         **************************************/
        $merchantOrderId = $order->merchant_order_id;
        Log::info("Merchant Order ID", ['merchant_order_id' => $merchantOrderId]);


        /**************************************
         * 2. Fetch Unlimit payment record
         **************************************/
        $paymentRecord = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (!$paymentRecord) {
            Log::error("UnlimitPayment not found", ['merchant_order_id' => $merchantOrderId]);
            return view("order.order-status", [
                "transactionStatusMessage" => __("errors.default"),
                "isSuccessful" => false
            ]);
        }

        // NOTE: Unlimit returns "success" in lowercase – check both.
        if (!in_array(strtolower($paymentRecord->payment_status), ['success', 'completed'])) {
            Log::warning("Payment status not successful", ['payment_status' => $paymentRecord->payment_status]);

            return view("order.order-status", [
                "transactionStatusMessage" => __("errors.default"),
                "isSuccessful" => false
            ]);
        }


        /**************************************
         * 3. Fetch QsOrder details
         **************************************/
        $qsOrderDetails = QsOrder::where('merchant_order_id', $merchantOrderId)->first();

        if (!$qsOrderDetails) {
            Log::error("QsOrder not found", ['merchant_order_id' => $merchantOrderId]);
            return view("order.order-status", [
                "transactionStatusMessage" => __("errors.default"),
                "isSuccessful" => false
            ]);
        }

        /**************************************
         * 4. Update Refno
         **************************************/
        $qsOrderDetails->refno = 'Amzr' . $qsOrderDetails->id;
        $qsOrderDetails->save();
        Log::info("Updated order refno", ['refno' => $qsOrderDetails->refno]);
	session(['session_refno' => 'Amzr' . $qsOrderDetails->id]);

        /**************************************
         * 5. Call Woohoo API
         **************************************/
        $response = $this->createWoohooOrderRequest($qsOrderDetails,$paymentRecord);

	 if (is_object($response)) {
         $response = json_decode(json_encode($response), true);
        }

	     if (!is_array($response)) {
            Log::error("Woohoo API response type invalid", [
                'received_type' => gettype($response),
                'merchant_order_id' => $merchantOrderId,
            ]);
            return $this->errorDefault();
        }

        if (empty($response)) {
            Log::error("Woohoo API returned empty response");
            return $this->errorDefault();
        }

        Log::info("Woohoo API Response", $response);


        /**************************************
         * 6. Handle Woohoo response safely
         **************************************/

        // Some responses return ["success" => true, "status" => "COMPLETE"]
        $success = $response["success"] ?? null;
        $status  = $response["status"] ?? null;

        if ($success === true && $status === "COMPLETE") {

            // Extract cards from Woohoo
            $cards = [];

            if (isset($response["data"]["cards"]) && is_array($response["data"]["cards"])) {
                $cards = $response["data"]["cards"];
                Log::info("Extracted Woohoo cards", ['count' => count($cards)]);
            }

            // Prepare handler format
            $handlerData = $response['data'] ?? [];
            $handlerData['status'] = $status;

            // Save vouchers
            $this->handleSuccessFullOrder($handlerData);

            return view("order.order-status", [
                "transactionStatusMessage" => __("errors.201"),
                "isSuccessful" => true,
                "cards" => $cards
            ]);
        }


        /**************************************
         * 7. Error handling for Woohoo API error codes
         **************************************/
        if (($response["status_code"] ?? null) === 400 || ($response["status_code"] ?? null) === 500) {

            $errorCode = $response["errorCode"] ?? "default";
            Log::error("Woohoo order error", [
                "status_code" => $response["status_code"],
                "errorCode" => $errorCode
            ]);

            $this->sendOrderFailureMail($qsOrderDetails);

            return view("order.order-status", [
                "transactionStatusMessage" => __("errors." . $errorCode),
                "isSuccessful" => false
            ]);
        }


        /**************************************
         * 8. Anything else → treat as failed
         **************************************/
        Log::error("Unhandled Woohoo response format", $response);
        return $this->errorDefault();

    } catch (\Throwable $e) {
        Log::error("Woohoo processing error", ['error' => $e->getMessage()]);
        return $this->errorDefault();
    }
}


/****************************************
 * Common error response helper
 ****************************************/
private function errorDefault()
{
    return view("order.order-status", [
        "transactionStatusMessage" => __("errors.default"),
        "isSuccessful" => false
    ]);
}

    /**
     * Clear session data after voucher processing is complete
     */
    public function clearSessionData()
    {
        Log::info("Clearing session data after voucher processing");
        Session::forget('payment_data');
        Session::forget('checkout_data');
        session()->forget('session_qs_order_id');
        session()->forget('session_refno');
        session()->forget('payment_return_data');
        
        return response()->json(['message' => 'Session data cleared successfully']);
    }


    private function handleErrorResponse($statusCode, $response, $qsOrderDetails, $createOrderResponse)
    {
        $isSuccessful = false;
        $errorCode = $createOrderResponse["code"] ?? null;
        $defaultErrorMessage = __("errors.default");
        $errorMessage = __("errors." . $errorCode, [], $defaultErrorMessage);
        if ($errorMessage == "errors." . $errorCode) {
            $errorMessage = $defaultErrorMessage;
        }
        Log::error("Error Code: {$errorCode}");
        Log::error("Error Message: {$errorMessage}");
        Log::error("Status Code: {$statusCode}");
        $transactionStatusMessage = $errorMessage;
        return ["transactionStatusMessage" => $transactionStatusMessage, "status_code" => $statusCode, "errorCode" => $errorCode, "errorMessage" => $errorMessage, "defaultErrorMessage" => $defaultErrorMessage, "isSuccessful" => $isSuccessful,];
    }
    private function handleUnexpectedErrorResponse(Exception $exception)
    {
        Log::error("Failed to place order. Exception caught: " . $exception->getMessage(), ["exception" => $exception]);
        return ["transactionStatusMessage" => $exception->getMessage(), "status_code" => 500, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
    }
    private function getStatusByReferenceNumber($refno)
    {
        Log::info("********** We are hitting status check API by reference number *******************\n");
        $requestHttpMethod = "GET";
        $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/order/" . $refno . "/status";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature("", $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();
        $retryCount = env("RETRY_COUNT", 0);
        $maxRetries = env("MAX_RETRIES", 10);
        $retryInterval = env("RETRY_INTERVAL", 40);
        Log::info("Retry count: $retryCount");
        Log::info("Max retries: $maxRetries");
        Log::info("Retry interval in seconds: $retryInterval");
        Log::info("Max execution time: " . ($maxRetries * $retryInterval));
        $startTime = Carbon::now();
        while ($retryCount < $maxRetries) {
            try {
                $retryCount++;
                $elapsedSeconds = $startTime->diffInSeconds(Carbon::now());
                Log::info("Attempting to get status by ref no. Attempt: $retryCount after $elapsedSeconds seconds");
                $orderStatusResponse = Http::acceptJson()->withHeaders(["Content-Type" => "application/json", "Authorization" => "Bearer " . $bearerToken, "Accept" => "*/*", "dateAtClient" => $dateAtClient, "signature" => $signature,])->get($absApiUrl);
                if ($orderStatusResponse->successful()) {
                    $cardStatusApiResponseData = $orderStatusResponse->json();
		   // Normalise response to array if it's an object to prevent stdClass errors
                    if (is_object($cardStatusApiResponseData)) {
                        $cardStatusApiResponseData = json_decode(json_encode($cardStatusApiResponseData), true);
                    }
                    
                    Log::info("Response from Woohoo server:", ["response" => $cardStatusApiResponseData]);
                    if (isset($cardStatusApiResponseData["status"])) {
                        if ($cardStatusApiResponseData["status"] === "COMPLETE") {
                            Log::info("Card activation status is COMPLETE returing this data to getstatusbyreferncenumber function");
                            return $this->callCardActivation($cardStatusApiResponseData);
                        } elseif ($cardStatusApiResponseData["status"] === "PROCESSING") {
                            Log::info("Order status is PROCESSING. Waiting for $retryInterval seconds before retrying.");
                            sleep($retryInterval);
                        } else {
                            Log::info("Order status is neither COMPLETE nor PROCESSING. Continuing loop.");
                            Log::info("Total seconds elapsed: $elapsedSeconds");
                            return ["transactionStatusMessage" => __("Order status is neither COMPLETE nor PROCESSING"), "status_code" => 200, "status" => $cardStatusApiResponseData["status"], "errorCode" => null, "errorMessage" => __("Order status is neither COMPLETE nor PROCESSING"), "isSuccessful" => false,];
                        }
                    } else {
                        Log::error("Undefined array key 'status' in API response.");
                        return ["transactionStatusMessage" => __("errors.7002"), "status_code" => 500, "status" => null, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
                    }
                } else {
                    Log::error("Order status check failed:", ["status_code" => $orderStatusResponse->status(), "response" => $orderStatusResponse->body()]);
                    return ["transactionStatusMessage" => __("errors.7003"), "status_code" => $orderStatusResponse->status(), "status" => null, "errorCode" => "7003", "errorMessage" => __("errors.7003"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
                }
            } catch (ConnectionException $exception) {
                Log::error("Failed to get order status due to connection issue: " . $exception->getMessage());
                return ["transactionStatusMessage" => $exception->getMessage(), "status_code" => 500, "status" => null, "errorCode" => "7001", "errorMessage" => __("errors.7001"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
            } catch (Exception $e) {
                Log::error("An unexpected error occurred: " . $e->getMessage());
                $this->handleUnexpectedErrorResponse($e);
                return ["transactionStatusMessage" => $e->getMessage(), "status_code" => 500, "status" => null, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
            }
        }
        $elapsedSeconds = $startTime->diffInSeconds(Carbon::now());
        Log::warning("Max retries reached. Status check failed. Total seconds elapsed: $elapsedSeconds");
        return ["transactionStatusMessage" => __("errors.5321"), "status_code" => 400, "status" => null, "errorCode" => "5321", "errorMessage" => __("errors.5321"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
    }
    public function callCardActivation($cardStatusApiResponseData)
    {
        Log::info("********** We are hitting card activation API by reference number *******************\n");
        $orderId = $cardStatusApiResponseData["orderId"];
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $apiUrl = "https://" . setting("api.woohoo_url");
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $requestBody = "";
        $requestHttpMethod = "GET";
        $dateAtClient = Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        try {
            $activatedCardApiResponse = Http::acceptJson()->withToken($bearerToken)->withHeaders(["signature" => $signature, "dateAtClient" => $dateAtClient,])->get($absApiUrl);
            if ($activatedCardApiResponse->successful()) {
                Log::info("Card activation successful.");
                $activatedCardApiResponseData = $activatedCardApiResponse->json();
                $combinedData = array_merge($cardStatusApiResponseData, $activatedCardApiResponseData);
                return $combinedData;
            } else {
                Log::error("Card activation failed. HTTP status code: " . $activatedCardApiResponse->status(), ["response" => $activatedCardApiResponse->body(),]);
                return ["transactionStatusMessage" => __("errors.7002"), "status_code" => 500, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
            }
        } catch (ConnectionException $exception) {
            Log::error("Failed to activate card due to connection issue: " . $exception->getMessage());
            return ["transactionStatusMessage" => $exception->getMessage(), "status_code" => 500, "errorCode" => "7001", "errorMessage" => __("errors.7001"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
        } catch (Exception $e) {
            Log::error("An unexpected error occurred during card activation: " . $e->getMessage());
            return ["transactionStatusMessage" => $e->getMessage(), "status_code" => 500, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false,];
        }
    }
    public function getFinancialYear()
    {
        $currentYear = date("Y");
        $currentMonth = date("m");
        if ($currentMonth >= 4) {
            $nextYear = $currentYear + 1;
            return $currentYear . "/" . substr($nextYear, -2);
        } else {
            $previousYear = $currentYear - 1;
            return $previousYear . "/" . substr($currentYear, -2);
        }
    }
    public function handleSuccessFullOrder($orderCreatedResponse)
    {
        log::info(777);
        $isSuccessful = false;
        Log::info("This is card response data: " . json_encode($orderCreatedResponse));
        Log::info("About to update QS Order");
        $orderId = $this->updateQsOrder($orderCreatedResponse);
        Log::info("QS Order updated successfully, orderId = " . $orderId);
	/* $orderId = session('session_qsorder_id');
        $order = QsOrder::join("unlimit_payment", "unlimit_payment.order_id", "=", "qs_orders.id")
            ->join("qs_products", "qs_products.sku", "=", "qs_orders.sku")
            ->where("qs_orders.id", $orderId)
            ->select("qs_orders.*", "unlimit_payment.*", "qs_products.*")
            ->first();*/

      $orderId = session('session_qsorder_id');
	/*if (!$qsOrder->sku) {
    	$latestWithSku = QsOrder::whereNotNull('sku')
        ->orderBy('id', 'DESC')
        ->first();

    if ($latestWithSku) {
        $qsOrder->sku = $latestWithSku->sku;
        $qsOrder->save();
    }*/



$order = DB::table('qs_orders')->leftJoin('unlimit_payment', function ($join) {
        $join->on('unlimit_payment.order_id', '=', 'qs_orders.id')
             ->orOn('unlimit_payment.merchant_order_id', '=', 'qs_orders.merchant_order_id');
    })->leftJoin('qs_products', function ($join) {
        $join->on(DB::raw('qs_products.sku COLLATE utf8mb4_unicode_ci'), '=',
            DB::raw("
                CASE 
                    WHEN qs_orders.sku IS NOT NULL 
                        THEN qs_orders.sku COLLATE utf8mb4_unicode_ci
                    ELSE unlimit_payment.sku COLLATE utf8mb4_unicode_ci
                END
            "));
    })->whereNotNull('qs_orders.id')->orWhereNotNull('qs_orders.merchant_order_id')->select(
        'qs_orders.*',
        'unlimit_payment.*',
        'qs_products.*'
    )->first();
$orderData = $order ? json_decode(json_encode($order), true) : [];
Log::info("Encoded value",["order data" => $orderData]);
Log::info("Join data",["join data" => $order]);
 $qsOrder = QsOrder::find($orderId);
if ($qsOrder && !empty($orderCreatedResponse['orderId'])) {
    $order->woohoo_order_id = $orderCreatedResponse['orderId'] ?? null;
    $qsOrder->save();
    Log::info("woohoo_order_id updated", ["id" => $orderId, "woohoo_order_id" => $qsOrder->woohoo_order_id]);
}

Log::info("Join data",['order' => $order]);
Log::info("DEBUG JOIN VALUES", [
    "orderId" => $orderId,
    "qsOrder" => QsOrder::find($orderId),
    "qsOrder_sku" => QsOrder::find($orderId)?->sku,
    "qsOrder_merchant_order_id" => QsOrder::find($orderId)?->merchant_order_id,
    
    "unlimit_payment_exists" => UnlimitPayment::where("merchant_order_id", 
        QsOrder::find($orderId)?->merchant_order_id)->exists(),

    "unlimit_payment_rows" => UnlimitPayment::where("merchant_order_id", 
        QsOrder::find($orderId)?->merchant_order_id)->get(),

    "product_exists" => \DB::table("qs_products")
        ->where("sku", QsOrder::find($orderId)?->sku)
        ->exists(),
]);            
        // Debug logging for product data
       /* Log::info("Product data:", [
            'custom_image' => $order->custom_image ?? 'No custom image',
            'images' => $order->images ?? 'No images field',
            'product_id' => $order->id
        ]);*/

        // Get raw image URL first
        $imageUrl = CommonHelper::getProductImage($order);
        Log::info("Raw image URL from CommonHelper: " . $imageUrl);

        // Convert backslashes to forward slashes in the URL
        $imageUrl = str_replace('\\', '/', $imageUrl);
        Log::info("Image URL after slash conversion: " . $imageUrl);

        // Remove any leading 'storage/' as we'll add it back
        $imageUrl = ltrim($imageUrl, '/');
        $imageUrl = str_replace('storage/', '', $imageUrl);

        // Create the full URL using the LIVE_URL from env
        $liveUrl = rtrim(env('LIVE_URL', 'https://amazepays.in'), '/');
        $smallImageUrl = $liveUrl . '/storage/' . $imageUrl;
        Log::info("Production URL for email using LIVE_URL: " . $smallImageUrl);

        // Verify if image exists and is accessible
        try {
            $ch = curl_init($smallImageUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            Log::info("Image accessibility check response code: " . $responseCode);

            if ($responseCode !== 200) {
                Log::error("Image not accessible at URL: " . $smallImageUrl);
                
                // Try using the images field from product data if custom image is not accessible
                if (!empty($order->images)) {
                    $images = json_decode($orderData['images'], true);
                    if (isset($images['small'])) {
                        $smallImageUrl = $images['small'];
                        Log::info("Using small image from product images: " . $smallImageUrl);
                    } else {
                        $smallImageUrl = null;
                        Log::info("No small image available in product images");
                    }
                } else {
                    $smallImageUrl = null;
                    Log::info("No product images available");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error checking image accessibility: " . $e->getMessage());
            $smallImageUrl = null;
        }

        $financialYear = $this->getFinancialYear();
        $invoiceNumber = "FRB2C-" . $financialYear . "-" . $orderId;
        $invoiceDate = date("d-m-Y");

        // Safely resolve cards; fetch from Woohoo if not yet available
	if (is_object($orderCreatedResponse)) {
        $orderCreatedResponse = json_decode(json_encode($orderCreatedResponse), true);
    }

    Log::info("This is card response data: " . json_encode($orderCreatedResponse));

       // $cardsArray = [];
$cardsArray[] = [
    'cardnumber' => $orderCreatedResponse['cards'][0]['cardNumber'] ?? null,
    'cardpin'    => $orderCreatedResponse['cards'][0]['cardPin'] ?? null,
];

        try {
            if (!empty($orderData["cards"])) {
                 $cardsArray = json_decode(decrypt($orderData["cards"], env("ENCRYPTION_KEY")), true) ?: [];
            }
	/* $encryptedCards = $order->cards ?? null;
            if (!empty($encryptedCards)) {
                $cardsArray = json_decode(decrypt($encryptedCards, env("ENCRYPTION_KEY")), true) ?: [];
            }*/
        } catch (\Throwable $e) {
            Log::warning("Card decrypt failed; will attempt to fetch cards", ["error" => $e->getMessage()]);
            $cardsArray = [];
        }

        if (empty($cardsArray)) {
            try {
               // $woohooOrderId = $order["woohoo_order_id"] ?? null;
		 $woohooOrderId = $order->woohoo_order_id ?? null;
                if ($woohooOrderId) {
                    // Reuse activation fetch to retrieve cards
                    $combined = $this->callCardActivation(["orderId" => $woohooOrderId, "status" => "COMPLETE"]);
                   /* if (is_array($combined) && isset($combined["cards"]) && is_array($combined["cards"])) {
                        $cardsArray = $combined["cards"]; 
                        // Persist freshly fetched cards
                        QsOrder::where("id", $orderId)->update([
                            "cards" => encrypt(json_encode($cardsArray), env("ENCRYPTION_KEY")),
                        ]);
                    }*/
		if (is_object($combined)) {
                        $combined = json_decode(json_encode($combined), true);
                    }
                    
                    // Check if cards exist and extract them safely
                    if (is_array($combined)) {
                        // Handle both 'cards' array and flat card fields
                        if (isset($combined["cards"]) && is_array($combined["cards"])) {
                            $cardsArray = $combined["cards"];
                        } elseif (isset($combined["cardnumber"]) || isset($combined["cardpin"])) {
                            // Handle flat card fields (cardnumber, cardpin, etc.)
                            $singleCard = [
                                'cardNumber'      => $combined['cardnumber'] ?? $combined['cardNumber'] ?? null,
                                'cardPin'         => $combined['cardpin'] ?? $combined['cardPin'] ?? null,
                                'amount'          => $combined['amount'] ?? null,
                                'activationCode'  => $combined['activation_code'] ?? $combined['activationCode'] ?? null,
                                'activationUrl'   => $combined['activation_url'] ?? $combined['activationUrl'] ?? null,
                                'validity'        => $combined['validity'] ?? null,
                            ];
                            if (!empty($singleCard['cardNumber']) || !empty($singleCard['cardPin'])) {
                                $cardsArray = [$singleCard];
                            }
                        }
			if (!empty($cardsArray)) {
                            QsOrder::where("id", $orderId)->update([
                                "cards" => encrypt(json_encode($cardsArray), env("ENCRYPTION_KEY")),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Failed to fetch cards after decrypt failure", ["error" => $e->getMessage(), "woohoo_order_id" => $order["woohoo_order_id"] ?? null]);
            }
        }
        
        log::info(888);
        QsOrder::where("id", $orderId)->update(["invoice_number" => $invoiceNumber]);
        
        // Add image URL to logging
        Log::info("Final image URL being used in email: " . ($smallImageUrl ?? 'No image available'));
	Log::info("Sender First Name",["sender_first_name" => $orderCreatedResponse['cards']['recipientDetails']['firstname']]);
        // Get billing information from the billing table
        $billingInfo = Billing::latest()->first();
        $billingdata = $billingInfo ? json_decode(json_encode($billingInfo), true) : [];
        $prepareMailDetails = [
            "name" => $orderData["sender_first_name"],
            "order_id" => $orderData["woohoo_order_id"],
            "reference_id" => $orderData["id"],
            "order_date" => $orderData["created_at"],
            "billing_name" => $billingdata['billing_name'] ?? $orderData["sender_first_name"],
            "billing_email" => $billingdata['billing_email'] ?? $orderData["sender_email"],
            "billing_tel" => $billingdata['billing_tel'] ?? $orderData["sender_phone_no"],
            "billing_address" => $billingdata['billing_address'] ?? $orderData["sender_address_1"],
            "billing_address_two" => $billingdata['billing_address_two'] ?? $orderData["sender_address_2"],
            "billing_city" => $billingdata['billing_city'] ?? $orderData["sender_city"],
            "billing_state" => $billingdata['billing_state'] ?? $orderData["sender_state"],
            "billing_country" => $billingdata['billing_country'] ?? "India",
            "billing_zip" => $billingdata['billing_zip'] ?? $orderData["sender_post_code"],
            "payment_mode" => $orderData["payment_mode"],
            "bank_ref_no" => $orderData["bank_ref_no"],
            "grand_payable_amount" => $orderData["grand_payable_amount"],
            "gst_number" => $billingdata['billing_gst_number'] ?? $orderData["gst_number"] ?: "Unregistered",
            "discount" => $orderData["discounted_amount_value"],
            "amount_payable_after_discount" => $orderData["amount_payable_after_discount"],
            "contact_person" => $orderData["sender_first_name"],
            "shipping_address" => $orderData["sender_address_1"] . " " . $orderData["sender_address_2"] . ", " . $orderData["sender_city"] . ", " . $order["sender_state"] . " " . $order["sender_post_code"],
            "invoice_number" => $invoiceNumber,
            "invoice_date" => $invoiceDate,
            "cardSku" => $orderData["sku"],
            "cardProductName" => $orderData["name"],
            "shipToName" => $orderData["receiver_name"] ?? $orderData["sender_first_name"],
            "shipToEmail" => $orderData["receiver_email"] ?? $orderData["sender_email"],
            "shipToContactNo" => $orderData["receiver_mobile"] ?? $orderData["sender_phone_no"],
            "quantity" => $orderData["quantity"],
            "smallImageUrl" => $smallImageUrl,
            "giftSendOption" => $orderData["gift_send_option"],
            "denomination" => $orderData["denomination"],
            "discount_percentage" => $orderData["discount_percentage"],
        ];
        try
        {
        // Build robust SMS details with safe fallbacks
        $senderName = $orderData["sender_first_name"] ?? ($billingdata['billing_name'] ?? 'Customer');
        $orderIdForSms = $orderData["woohoo_order_id"] ?? ($orderData["id"] ?? null);
        $orderAmountForSms = $orderData["amount"] ?? ($orderData["grand_payable_amount"] ?? null);
        $productNameForSms = $orderData["name"] ?? ($orderData["sku"] ?? 'Gift Card');
        $billingTelForSms = $billingdata['billing_tel'] ?? $orderData["sender_phone_no"] ?? null;
        $shipToNameForSms = $orderData["receiver_name"] ?? $senderName;
        $shipToContactForSms = $orderData["receiver_mobile"] ?? $billingTelForSms;

        $prepareSmsDetails = [
            "name" => $senderName,
            "order_id" => $orderIdForSms,
            "reference_id" => $orderData["id"] ?? null,
            "order_date" => $orderData["created_at"] ?? null,
            "billing_name" => $billingdata['billing_name'] ?? $senderName,
            "order_amount" => $orderAmountForSms,
            "cardSku" => $orderData["sku"] ?? null,
            "cardProductName" => $productNameForSms,
            "shipToName" => $shipToNameForSms,
            "shipToContactNo" => $shipToContactForSms,
            "grand_payable_amount" => $orderData["grand_payable_amount"] ?? null,
            "perOrderQuantity" => $orderData["quantity"] ?? 1,
            "giftSendOption" => $orderData["gift_send_option"] ?? 'buy_for_self',
            "billing_tel" => $billingTelForSms,
        ];
        if ($orderData["delivery_mode"] == "both") {
            Log::info("Calling Transaction Mail");
            $this->sendTransactionMail($prepareMailDetails);
            Log::info("Calling Gift Mail");
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
            Log::info("Calling Transaction SMS");
            $this->sendTransactionalMessage($prepareSmsDetails);
            Log::info("Calling Gift SMS");
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        } elseif ($orderData["delivery_mode"] == "email") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
        } elseif ($orderData["delivery_mode"] == "mobile") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        }
        log::info("zzzz");
        }
        catch (\Exception $e) {
                        return ErrorHandler::handleOrderError($e);
                    }
    }
    public function updateQsOrder($orderCreatedResponse)
    {  
	$qsOrderId = session('session_qsorder_id');
   	 $refno = session('session_refno');

    Log::info("Reference number", ['ref_no' => $refno, 'qs_order_id' => $qsOrderId]);
	Log::info("Cache order data", ['order_data' => $orderCreatedResponse]);

        $isSuccessful = false;
	if (is_object($orderCreatedResponse)) {
            $orderCreatedResponse = json_decode(json_encode($orderCreatedResponse), true);
        }
	
	 if (!is_array($orderCreatedResponse)) {
            Log::error('updateQsOrder called with invalid response type', [
                'type' => gettype($orderCreatedResponse),
            ]);
            $transactionStatusMessage = "Invalid order response format received from Woohoo.";
            return view("order.order-status", compact("transactionStatusMessage", "isSuccessful"));
        }

	// Build cards payload – handle both legacy 'cards' array and flat single-card fields
        $cardsPayload = [];

        if (isset($orderCreatedResponse['cards']) && is_array($orderCreatedResponse['cards'])) {
            // Normal Woohoo structure: array of cards already present
            $cardsPayload = $orderCreatedResponse['cards'];
        } else {
            // Some responses return a single card as flat fields (e.g. cardnumber, cardpin, etc.)
            $singleCard = [
                'cardNumber'      => $orderCreatedResponse['cardnumber']       ?? $orderCreatedResponse['cardNumber'] ?? null,
                'cardPin'         => $orderCreatedResponse['cardpin']          ?? $orderCreatedResponse['cardPin'] ?? null,
                'amount'          => $orderCreatedResponse['amount']           ?? null,
                'activationCode'  => $orderCreatedResponse['activation_code']   ?? $orderCreatedResponse['activationCode'] ?? null,
                'activationUrl'   => $orderCreatedResponse['activation_url']   ?? $orderCreatedResponse['activationUrl'] ?? null,
                'validity'        => $orderCreatedResponse['validity']         ?? null,
            ];

            // Only push if we have at least a card number or pin to avoid storing an empty card
            if (!empty($singleCard['cardNumber']) || !empty($singleCard['cardPin'])) {
                $cardsPayload[] = $singleCard;
            }
        }

        $qsOrderUpdate = QsOrder::where("refno", $orderCreatedResponse["refno"])->first();
        if ($qsOrderUpdate) {
            $qsOrderUpdate->update(["woohoo_order_id" => $orderCreatedResponse["orderId"], "order_status" => $orderCreatedResponse["status"], "cards" => encrypt(json_encode($orderCreatedResponse["cards"]), env("ENCRYPTION_KEY")), "order_cancel" => json_encode($orderCreatedResponse["cancel"]), "order_payment" => isset($orderCreatedResponse["payments"]) ? json_encode($orderCreatedResponse["payments"]) : null, "currency" => json_encode($orderCreatedResponse["currency"]), "additionalTxnFields" => isset($orderCreatedResponse["additionalTxnFields"]) ? json_encode($orderCreatedResponse["additionalTxnFields"]) : null,]);
            $existingOrderSummary = OrderSummary::where('order_id', $qsOrderUpdate->id)->first();
            if ($existingOrderSummary) {
            $existingOrderSummary->order_status = $orderCreatedResponse["status"];
            $existingOrderSummary->save();
            } else {
                    Log::warning("OrderSummary not found for order_id: " . $qsOrderUpdate->id);
                }
            return $qsOrderUpdate->id;
        } else {
            $transactionStatusMessage = "Order with ID with referene number not found.";
            Log::error($transactionStatusMessage);
            return view("order.order-status", compact("transactionStatusMessage", "isSuccessful"));
            //Log::error("QS Order not found for refno: " . $orderCreatedResponse["refno"]);
            //return false;
        }
    }
    /**
     * Check transaction status for the redirect page (lightweight check)
     */
   public function checkTransactionStatus(Request $request)
{
    $merchantOrderId = $request->query('merchant_order_id');
    if (!$merchantOrderId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Merchant order ID missing. Contact support.'
        ], 400);
    }

    $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
    if (!$qsOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Order not found in DB. Contact support.'
        ], 404);
    }

    // If order already complete
    if ($qsOrder->order_status === 'COMPLETE') {
        return response()->json([
            'status' => 'complete',
            'message' => 'Payment completed successfully'
        ]);
    }

    // Fetch latest status from Unlimit API if needed
    $statusResponse = $this->getStatusByReferenceNumberLightweight($qsOrder->refno);
    if ($statusResponse['status'] === 'COMPLETED') {
        $qsOrder->order_status = 'COMPLETE';
        $qsOrder->save();
        return response()->json([
            'status' => 'complete',
            'message' => 'Payment completed successfully'
        ]);
    }

    return response()->json([
        'status' => 'processing',
        'message' => 'Payment is still processing'
    ]);
}
 /**
     * Lightweight status check without retry mechanism for frontend polling
     */
    private function getStatusByReferenceNumberLightweight($refno)
    {
        try {
            $requestHttpMethod = "GET";
            $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/order/" . $refno . "/status";
            $clientSecret = setting("api.qs_clientSecret");
            $bearerToken = setting("api.bearer_token");
            $signature = CommonHelper::generateSignature("", $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon::now()->toIso8601String();

            Log::info("Lightweight status check for refno: $refno");
            
            $orderStatusResponse = Http::acceptJson()
                ->timeout(10) // Short timeout for lightweight check
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $bearerToken,
                    "Accept" => "*/*",
                    "dateAtClient" => $dateAtClient,
                    "signature" => $signature,
                ])
                ->get($absApiUrl);

            if ($orderStatusResponse->successful()) {
                $cardStatusApiResponseData = $orderStatusResponse->json();
		 // Normalise response to array if it's an object to prevent stdClass errors
                if (is_object($cardStatusApiResponseData)) {
                    $cardStatusApiResponseData = json_decode(json_encode($cardStatusApiResponseData), true);
                }
                
                Log::info("Lightweight status response:", ["response" => $cardStatusApiResponseData]);
                
                if (isset($cardStatusApiResponseData["status"])) {
                    if ($cardStatusApiResponseData["status"] === "COMPLETE") {
                        Log::info("Status check: COMPLETE");
                        return $this->callCardActivation($cardStatusApiResponseData);
                    } elseif ($cardStatusApiResponseData["status"] === "PROCESSING") {
                        Log::info("Status check: PROCESSING");
                        return [
                            'status' => 'PROCESSING',
                            'message' => 'Transaction is still processing'
                        ];
                    } else {
                        Log::info("Status check: Other status - " . $cardStatusApiResponseData["status"]);
                        return [
                            'status' => $cardStatusApiResponseData["status"],
                            'message' => 'Transaction status: ' . $cardStatusApiResponseData["status"]
                        ];
                    }
                }
            } else {
                Log::warning("Status check API failed: " . $orderStatusResponse->status());
                return [
                    'status' => 'error',
                    'message' => 'API call failed'
                ];
            }

            return [
                'status' => 'unknown',
                'message' => 'Unknown status'
            ];

        } catch (\Exception $e) {
            Log::error("Lightweight status check error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Status check failed'
            ];
        }
    }

    public function sendTransactionMail($prepareMailDetails)
    {
        $recipientEmail = $prepareMailDetails["billing_email"] ?? null;
        $recipientName = $prepareMailDetails["billing_name"] ?? null;

        // Validate recipient email before sending
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Transaction mail not sent: invalid or empty recipient email", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'prepare_mail_details' => $prepareMailDetails
            ]);
            return;
        }

        try {
            // Log attempt to send transaction mail
            Log::info("Attempting to send transaction mail", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A'
            ]);

            $pdf = PDF::loadView("layouts.invoice", $prepareMailDetails);
            
            Mail::send(["html" => "layouts.mail"], compact("prepareMailDetails", "pdf"), function ($message) use ($prepareMailDetails, $pdf) {
                $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
                    ->to($prepareMailDetails["billing_email"], $prepareMailDetails["billing_name"])
                    ->subject(config("companyDefaultValues.default_subject"))
                    ->attachData($pdf->output(), "invoice.pdf");
            });

            // Log successful mail sent
            Log::info("Transaction mail sent successfully", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A'
            ]);

            $msg = "Transaction Mail created successfully!";
            $status = "success";

        } catch (\Exception $e) {
            // Log detailed error information
            Log::error("Failed to send transaction mail", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'stack_trace' => $e->getTraceAsString()
            ]);

            $msg = "Failed to send transaction mail: " . $e->getMessage();
            $status = "error";
        }
    }


    // public function sendInvoiceTestMail()
    // {
    //     // Generate the PDF without any dynamic data (static template)
    //     $pdf = PDF::loadView("layouts.invoice2");

    //     // Use static email and subject
     //   $recipientEmail = "prince.toutle@gmail.com";
    //     $recipientName = "Prince";

    //     // Send the email
    //     Mail::send(["html" => "layouts.mail2"], compact("pdf"), function ($message) use ($pdf, $recipientEmail, $recipientName) {
    //         $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
    //             ->to($recipientEmail, $recipientName)
    //             ->subject(config("companyDefaultValues.default_subject"))
    //             ->attachData($pdf->output(), "invoice.pdf");
    //     });

    //     // Return response
    //     return response()->json([
    //         "message" => "Test transaction mail sent successfully!",
    //         "status" => "success",
    //     ]);
    // }


    // public function viewTestMail()
    // {
    //     return view ('layouts.invoice2');
    // }





    public function sendOrderFailureMail($qsOrderDetails)
    {
        // Fetch the recipient emails from the .env file
        $orderFailureAdminEmail = env('ORDER_FAILURE_ADMIN_EMAIL');
        $orderFailureITAdminEmail = env('ORDER_FAILURE_IT_ADMIN_EMAIL');

        // Fallback: Try config if env is missing/invalid
        if (empty($orderFailureAdminEmail) || !filter_var($orderFailureAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureAdminEmail = config('companyDefaultValues.company_email');
        }
        if (empty($orderFailureAdminEmail) || !filter_var($orderFailureAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureAdminEmail = 'itsupport@amazepays.in'; // last-resort fallback
        }
        if (empty($orderFailureITAdminEmail) || !filter_var($orderFailureITAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureITAdminEmail = 'itsupport@amazepays.in';
        }
        if (empty($orderFailureITAdminEmail) || !filter_var($orderFailureITAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $orderFailureITAdminEmail = 'itsupport@amazepays.in'; // last-resort fallback
        }

        // Validate email configuration
        if (empty($orderFailureAdminEmail) || !filter_var($orderFailureAdminEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Order failure mail not sent: invalid or empty admin email", [
                'admin_email' => $orderFailureAdminEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
            ]);
            return;
        }
        if (empty($orderFailureITAdminEmail) || !filter_var($orderFailureITAdminEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Order failure mail not sent: invalid or empty IT admin email", [
                'it_admin_email' => $orderFailureITAdminEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
            ]);
            return;
        }

        // Validate from email configuration
        $fromEmail = config("companyDefaultValues.sendMailFrom");
        if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Order failure mail not sent: invalid or empty from email", [
                'from_email' => $fromEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
            ]);
            return;
        }

        // Log email configuration
        Log::info("Order failure mail configuration", [
            'admin_email' => $orderFailureAdminEmail,
            'it_admin_email' => $orderFailureITAdminEmail,
            'from_email' => $fromEmail,
            'company_name' => config("companyDefaultValues.company_name"),
            'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
        ]);

        try {
            // Log attempt to send order failure mail
            Log::info("Attempting to send order failure mail", [
                'admin_email' => $orderFailureAdminEmail,
                'it_admin_email' => $orderFailureITAdminEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
            ]);

            // Send the email using Blade template
            Mail::send('email.order-failure', [
                'orderDetails' => $qsOrderDetails  // Pass order details to the view
            ], function ($message) use ($orderFailureAdminEmail, $orderFailureITAdminEmail) {
                $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
                    ->to($orderFailureAdminEmail) // Main recipient (admin)
                    ->cc($orderFailureITAdminEmail)   // IT admin in CC
                    ->subject("Order Failure Notification");
            });

            // Log the email sent information
            Log::info("Order Failure email sent successfully", [
                'admin_email' => $orderFailureAdminEmail,
                'it_admin_email' => $orderFailureITAdminEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            // Log any errors during the email sending process
            Log::error("Failed to send Order Failure email", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'admin_email' => $orderFailureAdminEmail,
                'it_admin_email' => $orderFailureITAdminEmail,
                'order_id' => $qsOrderDetails['order_id'] ?? 'N/A',
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }


    public function sendGiftMail($prepareMailDetails, $cardsArray)
    {
        
        $recipientEmail = $prepareMailDetails["billing_email"] ?? null;
        $recipientName = $prepareMailDetails["billing_name"] ?? null;
        Log::info("This is prepare mail details: " . json_encode($prepareMailDetails));
        Log::info('Recipient details', [
                'email' => $recipientEmail,
                'name'  => $recipientName,
            ]);

        // Validate recipient email before sending
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Gift mail not sent: invalid or empty recipient email", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'cards_count' => count($cardsArray),
                'prepare_mail_details' => $prepareMailDetails
            ]);
            return;
        }

        try {
            // Log attempt to send gift mail
            Log::info("Attempting to send gift mail", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'cards_count' => count($cardsArray)
            ]);

            Mail::send(["html" => "layouts.giftmail"], compact("prepareMailDetails", "cardsArray"), function ($message) use ($prepareMailDetails) {
                $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
                    ->to($prepareMailDetails["billing_email"], $prepareMailDetails["billing_name"])
                    ->subject(config("companyDefaultValues.gift_subject"));
            });

            // Log successful gift mail sent
            Log::info("Gift mail sent successfully", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'cards_count' => count($cardsArray)
            ]);

            $msg = "Gift Mail created successfully!";
            $status = "success";

        } catch (\Exception $e) {
            // Log detailed error information
            Log::error("Failed to send gift mail", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'cards_count' => count($cardsArray),
                'stack_trace' => $e->getTraceAsString()
            ]);

            $msg = "Failed to send gift mail: " . $e->getMessage();
            $status = "error";
        }
    }
    public function sendTransactionalMessage($prepareSmsDetails)
    {
        
        try {
            $name = $prepareSmsDetails["name"];
            $orderAmount = $prepareSmsDetails["order_amount"];
            $orderNumber = $prepareSmsDetails["order_id"];
            $productName = $prepareSmsDetails["cardProductName"];
            $destination = $prepareSmsDetails["billing_tel"];
            
            // Validate phone number before sending SMS
            if (empty($destination) || !preg_match('/^[6-9]\d{9}$/', $destination)) {
                Log::error("Transactional SMS not sent: invalid or empty phone number", [
                    'destination_number' => $destination,
                    'recipient_name' => $name,
                    'order_number' => $orderNumber,
                    'prepare_sms_details' => $prepareSmsDetails
                ]);
                return;
            }
            
            // Log SMS configuration and attempt
            Log::info("Attempting to send transactional SMS", [
                'recipient_name' => $name,
                'order_amount' => $orderAmount,
                'order_number' => $orderNumber,
                'product_name' => $productName,
                'destination_number' => $destination
            ]);

            $sms_api_url = config("transactionSms.sms_api_url");
            $sms_user_name = config("transactionSms.sms_user_name");
            $sms_user_password = config("transactionSms.sms_user_password");
            $sms_source = config("transactionSms.sms_source");
            $sms_message = "Hello " . $name . ", Your order no " . $orderNumber . " of " . $orderAmount . " is generated successfully. Please check out respected Email for that. Thanks - FRENETIC INDIA.";
            $sms_entity_id = config("transactionSms.sms_entity_id");
            $sms_temp_id = config("transactionSms.sms_temp_id");
            $sms_tmid = config("transactionSms.sms_tmid");

            $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id&tmid=$sms_tmid";
            
            $response = Http::get($apiUrl);
            
            // Log SMS API response
            Log::info("Transactional SMS API response", [
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'api_url' => $apiUrl,
                'order_number' => $orderNumber,
                'destination_number' => $destination
            ]);

            if ($response->successful()) {
                Log::info("Transactional SMS sent successfully", [
                    'order_number' => $orderNumber,
                    'destination_number' => $destination,
                    'response_status' => $response->status()
                ]);
            } else {
                Log::error("Transactional SMS failed", [
                    'order_number' => $orderNumber,
                    'destination_number' => $destination,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Exception occurred while sending transactional SMS", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'order_number' => $prepareSmsDetails["order_id"] ?? 'N/A',
                'destination_number' => $prepareSmsDetails["billing_tel"] ?? 'N/A',
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }





    public function sendGiftMessage($prepareSmsDetails, $cardsArray)
    {
        
        try {
            $name = $prepareSmsDetails["shipToName"] ?? ($prepareSmsDetails["billing_name"] ?? 'Customer');
            $orderNumber = $prepareSmsDetails["order_id"] ?? 'N/A';
            $orderAmount = $prepareSmsDetails["order_amount"] ?? ($prepareSmsDetails["grand_payable_amount"] ?? null);
            // Prefer recipient mobile; fall back to billing tel if missing
            $destination = $prepareSmsDetails["shipToContactNo"] ?? $prepareSmsDetails["billing_tel"] ?? null;
            
            // Validate phone number before sending SMS
            if (empty($destination) || !preg_match('/^[6-9]\d{9}$/', $destination)) {
                Log::error("Gift SMS not sent: invalid or empty phone number", [
                    'destination_number' => $destination,
                    'recipient_name' => $name,
                    'order_number' => $orderNumber,
                    'cards_count' => count($cardsArray),
                    'prepare_sms_details' => $prepareSmsDetails
                ]);
                return;
            }
            
            // Log SMS configuration and attempt
            Log::info("Attempting to send gift SMS", [
                'recipient_name' => $name,
                'order_amount' => $orderAmount,
                'order_number' => $orderNumber,
                'destination_number' => $destination,
                'cards_count' => count($cardsArray)
            ]);

            $sms_api_url = config("giftSms.sms_api_url");
            $sms_user_name = config("giftSms.sms_user_name");
            $sms_user_password = config("giftSms.sms_user_password");
            $sms_source = config("giftSms.sms_source");
            $sms_entity_id = config("giftSms.sms_entity_id");
            $sms_temp_id = config("giftSms.sms_temp_id");
            $sms_tmid = config("giftSms.sms_tmid");
            
            foreach ($cardsArray as $index => $card) {
                try {
                    $cardId = $card["cardNumber"];
                    $cardPin = $card["cardPin"];
                    $cardAmount = $card["amount"];
                    $cardActivationCode = $card["activationCode"];
                    $cardActivationURL = $card["activationUrl"];
                    $cardValidity = date("d-M-Y", strtotime($card["validity"]));
                    $sms_message = "Hello " . $name . " You received a gift card and your Card details: " . "Card ID: " . $cardId . " Card Pin: " . $cardPin . " Amount " . $cardAmount . " Activation Code " . $cardActivationCode . " Activation URL " . $cardActivationURL . " Validity " . $cardValidity . " Please check your respected Email for more information. Thanks - FRENETIC INDIA";
                    $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id&tmid=$sms_tmid";
                    
                    // Log individual card SMS attempt
                    Log::info("Sending gift SMS for card", [
                        'card_index' => $index + 1,
                        'card_id' => $cardId,
                        'order_number' => $orderNumber,
                        'destination_number' => $destination
                    ]);
                    
                    $response = Http::get($apiUrl);
                    
                    // Log SMS API response for each card
                    Log::info("Gift SMS API response for card", [
                        'card_index' => $index + 1,
                        'card_id' => $cardId,
                        'response_status' => $response->status(),
                        'response_body' => $response->body(),
                        'api_url' => $apiUrl,
                        'order_number' => $orderNumber,
                        'destination_number' => $destination
                    ]);

                    if ($response->successful()) {
                        Log::info("Gift SMS sent successfully for card", [
                            'card_index' => $index + 1,
                            'card_id' => $cardId,
                            'order_number' => $orderNumber,
                            'destination_number' => $destination,
                            'response_status' => $response->status()
                        ]);
                    } else {
                        Log::error("Gift SMS failed for card", [
                            'card_index' => $index + 1,
                            'card_id' => $cardId,
                            'order_number' => $orderNumber,
                            'destination_number' => $destination,
                            'response_status' => $response->status(),
                            'response_body' => $response->body()
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Exception occurred while sending gift SMS for card", [
                        'card_index' => $index + 1,
                        'card_id' => $card["cardNumber"] ?? 'N/A',
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'order_number' => $orderNumber,
                        'destination_number' => $destination,
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Exception occurred while sending gift SMS", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'order_number' => $prepareSmsDetails["order_id"] ?? 'N/A',
                'destination_number' => $prepareSmsDetails["shipToContactNo"] ?? 'N/A',
                'cards_count' => count($cardsArray),
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
