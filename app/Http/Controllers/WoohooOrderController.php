<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Helpers\ErrorHandler;
use App\Models\QsOrder;
use App\Models\OrderSummary;
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
    public function createOrder(Request $request)
    {
        /*$request->validate(['order_id' => 'nullable|integer|exists:qs_orders,id',]);
        if ($request->order_id) {
            $qsOrderDetails = QsOrder::where('id', $request->order_id)->first();
            if (!$qsOrderDetails) {
                Log::error("No order details found for ID: " . $request->order_id);
                return view("order.order-status", ['transactionStatusMessage' => __("errors.order_not_found"), 'isSuccessful' => false,]);
            }*/
            $qsOrderDetails = QsOrder::latest('id')->first();
            $newRefNo = 'Amzr' . $qsOrderDetails->id;
            $qsOrderDetails->refno = $newRefNo;
            $qsOrderDetails->save();
       /* } else {
            $qsOrderDetails = Session::get("payment_data", null);
        }*/
       // Log::info("Payment data collected from session and stored in \$qsOrderDetails variable is: " . json_encode($qsOrderDetails));
        $isSuccessful = false;
        $transactionStatusMessage = __("errors.default");
        
        if ($qsOrderDetails) {
            $orderCreatedResponse = $this->createWoohooOrderRequest($qsOrderDetails);
            if ($orderCreatedResponse) {
                if (isset($orderCreatedResponse["status"]) && $orderCreatedResponse["status"] == "COMPLETE") {
                    $transactionStatusMessage = __("errors.201");
                    $isSuccessful = true;
                    log::info(111);
                    $this->handleSuccessFullOrder($orderCreatedResponse);
                    log::info(333);
                } elseif (isset($orderCreatedResponse["status_code"]) && $orderCreatedResponse["status_code"] == "400") {
                    try
                    {
                    $this->sendOrderFailureMail($qsOrderDetails);
                    $errorCode = $orderCreatedResponse["errorCode"] ?? "default";
                    $transactionStatusMessage = __("errors." . $errorCode);
                    Log::error("Order creation failed with status 400 and error code: " . $errorCode);
                } catch (\Exception $e) {
                        return ErrorHandler::handleOrderError($e);
                    }
                } elseif (isset($orderCreatedResponse["status_code"]) && $orderCreatedResponse["status_code"] == "500") {
                    try
                    {
                    $this->sendOrderFailureMail($qsOrderDetails);
                    $errorCode = $orderCreatedResponse["errorCode"] ?? "default";
                    $transactionStatusMessage = __("errors." . $errorCode);
                    Log::error("Order creation failed with status 500 and error code: " . $errorCode);
                    } catch (\Exception $e) {
                                    return ErrorHandler::handleOrderError($e);
                                }
                } else {
                    //$this->sendOrderFailureMail($qsOrderDetails);
                    $transactionStatusMessage = __("errors.default");
                    Log::error("Unexpected response from order creation: " . json_encode($orderCreatedResponse));
                }
            } else {
                $transactionStatusMessage = __("errors.default");
                //$this->sendOrderFailureMail($qsOrderDetails);
                Log::error("Order creation request failed. No response received.");
            }
        } else {
            //$this->sendOrderFailureMail($qsOrderDetails);
            $transactionStatusMessage = __("errors.default");
            Log::error("No payment data found in session.");
        }
        Log::info("Session before clearing: " . json_encode(Session::all()));
        Session::forget('payment_data');
        Session::forget('checkout_data');
        session()->forget('session_qs_order_id');
        Log::info('session_qs_order_id after forget:', ['session_qs_order_id' => session('session_qs_order_id')]);
        session()->forget('session_refno');
        Log::info('session_refno after forget:', ['session_refno' => session('session_refno')]);
        return view("order.order-status", compact("transactionStatusMessage", "isSuccessful"));
    }
    public function createWoohooOrderRequest($qsOrderDetails)
    {
        Log::info("******* you are in create Woohoo Order function ***********");
        $billinginfo = Billing::latest()->first();
        $parts = explode(' ', $billinginfo->billing_name, 2);
        $refno = $qsOrderDetails->refno;
        $create_order_request_body_data = ["address" => ["firstname" => $parts[0], "lastname" => $parts[1], "email" => $billinginfo->billing_email, "telephone" => "+91" . $billinginfo->billing_tel, "line1" => $billinginfo->billing_address, "line2" => $billinginfo->billing_address_two, "city" => $billinginfo->billing_city, "region" => $billinginfo->billing_state, "country" => "IN", "postcode" => $billinginfo->billing_zip, "languages" => "Hindi", "billToThis" => true,], "billing" => ["firstname" => $parts[0], "lastname" => $parts[1], "email" => $billinginfo->billing_email, "telephone" => "+91" . $billinginfo->billing_tel, "line1" => $billinginfo->billing_address, "line2" => $billinginfo->billing_address_two, "city" => $billinginfo->billing_city, "region" => $billinginfo->billing_state, "country" => "IN", "postcode" => $billinginfo->billing_zip, "languages" => "Hindi", "billToThis" => true,], "payments" => [["code" => "svc", "amount" => $qsOrderDetails->grand_payable_amount],], "refno" => $refno, "products" => [["sku" => $qsOrderDetails->sku, "price" => $qsOrderDetails->denomination, "qty" => $qsOrderDetails->quantity, "currency" => "356"],], "syncOnly" => $qsOrderDetails->quantity > (int) env("SYNC_ONLY_THRESHOLD") ? false : true, "delivery_mode" => "API",];
        Log::info("########\nWoohoo create order request body data is:\n" . print_r($create_order_request_body_data, true) . "\n#######");
        $requestBody = json_encode($create_order_request_body_data);
        $requestHttpMethod = "post";
        $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/orders";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();
        Log::info("**************** Order Creation Api *************************\n");
        Log::info("Before hitting API, time is " . now() . "\n");
        try {
            Log::info("******* making  Create Order API request with the request body data is as follows ***********");
            $createOrderResponse = Http::acceptJson()->timeout(10)->withHeaders(["Content-Type" => "application/json", "Authorization" => "Bearer " . $bearerToken, "Accept" => "*/*", "dateAtClient" => $dateAtClient, "signature" => $signature,])->send("POST", $absApiUrl, ["body" => $requestBody]);
            Log::info("API Request body is:", ["url" => $absApiUrl, "method" => $requestHttpMethod, "headers" => ["Content-Type" => "application/json", "Authorization" => "Bearer " . $bearerToken, "Accept" => "*/*", "dateAtClient" => $dateAtClient, "signature" => $signature,], "data" => $requestBody,]);
            Log::info("********** We are hitting create order API to check response *******************\n");
            $responseData = $createOrderResponse->json();
            $headers = $createOrderResponse->headers();
            $body = json_decode($createOrderResponse->body(), true);
            Log::info("Get Response from Woohoo server", ["response" => $responseData]);
            Log::info("Woohoo API Response:", ["status_code" => $createOrderResponse->status(), "headers" => $headers, "body" => $body, "order_id" => $responseData["orderId"] ?? null, "amount" => $responseData["payments"][0]["balance"] ?? null,]);
            if ($createOrderResponse->successful()) {
                Log::info("200 response received; now checking if the status is COMPLETE or PROCESSING");
                $orderCreatedResponse = $createOrderResponse->json();
                if (isset($orderCreatedResponse["status"])) {
                    if ($orderCreatedResponse["status"] === "COMPLETE") {
                        return $orderCreatedResponse;
                    } elseif ($orderCreatedResponse["status"] === "PROCESSING") {
                        Log::info("Order is in PROCESSING status");
                        Log::info("Going to the getStatusByReferenceNumber function");
                        $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);
                        if ($statusFunctionResponse && $statusFunctionResponse['status'] === 'COMPLETE') {
                            return $statusFunctionResponse;
                        } else {
                            Log::error("No valid response received from getStatusByReferenceNumber. Returning failure.");
                            return ["transactionStatusMessage" => __("errors.7002"), "status_code" => 500, "status" => null, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false];
                        }
                    }
                } else {
                    Log::error("Order status is neither COMPLETE nor PROCESSING. Exiting with error.");
                    return ["transactionStatusMessage" => __("errors.7002"), "status_code" => 500, "status" => null, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false];
                }
            } else {
                //$this->sendOrderFailureMail($qsOrderDetails);
                $statusCode = $createOrderResponse->status();
                $response = json_decode($createOrderResponse->body(), true);
                $errorResponse = $this->handleErrorResponse($statusCode, $response, $qsOrderDetails, $createOrderResponse);
                return $errorResponse;
            }
        } catch (ConnectionException $e) {
            Log::error("cURL Error: " . $e->getMessage());
            $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);
            if ($statusFunctionResponse && $statusFunctionResponse["status"] == "COMPLETE") {
                Log::info("Status function response is complete. Returning response.");
                return $statusFunctionResponse;
            } else {
                //$this->sendOrderFailureMail($qsOrderDetails);
                Log::info("Status function response is not complete. Returning failure.");
                return ["transactionStatusMessage" => __("errors.7002"), "status_code" => 500, "errorCode" => "7002", "errorMessage" => __("errors.7002"), "defaultErrorMessage" => __("errors.default"), "isSuccessful" => false];
            }
        } catch (\Exception $e) {
            //$this->sendOrderFailureMail($qsOrderDetails);
            Log::error("Unexpected exception: " . $e->getMessage());
            return $this->handleUnexpectedErrorResponse($e);
        }
        catch (\Exception $e) {
                        return ErrorHandler::handleOrderError($e);
                    }
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
        $orderId = $this->updateQsOrder($orderCreatedResponse);
        $order = QsOrder::join("unlimit_payment", "unlimit_payment.order_id", "=", "qs_orders.id")
            ->join("qs_products", "qs_products.sku", "=", "qs_orders.sku")
            ->where("qs_orders.id", $orderId)
            ->select("qs_orders.*", "unlimit_payment.*", "qs_products.*")
            ->first();
            
        // Debug logging for product data
        Log::info("Product data:", [
            'custom_image' => $order->custom_image ?? 'No custom image',
            'images' => $order->images ?? 'No images field',
            'product_id' => $order->id
        ]);

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
                    $images = json_decode($order->images, true);
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
        $cardsArray = json_decode(decrypt($order["cards"], env("ENCRYPTION_KEY")), true);
        
        log::info(888);
        QsOrder::where("id", $orderId)->update(["invoice_number" => $invoiceNumber]);
        
        // Add image URL to logging
        Log::info("Final image URL being used in email: " . ($smallImageUrl ?? 'No image available'));

        $prepareMailDetails = [
            "name" => $order["sender_first_name"],
            "order_id" => $order["woohoo_order_id"],
            "reference_id" => $order["id"],
            "order_date" => $order["created_at"],
            "billing_name" => $order["sender_first_name"],
            "billing_email" => $order["sender_email"],
            "billing_tel" => $order["sender_phone_no"],
            "billing_address" => $order["sender_address_1"] . " " . $order["sender_address_2"] . ", " . $order["sender_city"] . ", " . $order["sender_state"] . " " . $order["sender_post_code"],
            "payment_mode" => $order["payment_mode"],
            "bank_ref_no" => $order["bank_ref_no"],
            "grand_payable_amount" => $order["grand_payable_amount"],
            "gst_number" => $order["gst_number"] ?: "Unregistered",
            "discount" => $order["discounted_amount_value"],
            "amount_payable_after_discount" => $order["amount_payable_after_discount"],
            "contact_person" => $order["sender_first_name"],
            "shipping_address" => $order["sender_address_1"] . " " . $order["sender_address_2"] . ", " . $order["sender_city"] . ", " . $order["sender_state"] . " " . $order["sender_post_code"],
            "invoice_number" => $invoiceNumber,
            "invoice_date" => $invoiceDate,
            "cardSku" => $order["sku"],
            "cardProductName" => $order["name"],
            "shipToName" => $order["receiver_name"] ?? $order["sender_first_name"],
            "shipToEmail" => $order["receiver_email"] ?? $order["sender_email"],
            "shipToContactNo" => $order["receiver_mobile"] ?? $order["sender_phone_no"],
            "quantity" => $order["quantity"],
            "smallImageUrl" => $smallImageUrl,
            "giftSendOption" => $order["gift_send_option"],
            "denomination" => $order["denomination"],
            "discount_percentage" => $order["discount_percentage"],
        ];
        try
        {
        $prepareSmsDetails = ["name" => $order["sender_first_name"], "order_id" => $order["woohoo_order_id"], "reference_id" => $order["id"], "order_date" => $order["created_at"], "billing_name" => $order["sender_first_name"], "order_amount" => $order["amount"], "cardSku" => $order["sku"], "cardProductName" => $order["name"], "shipToName" => $order["receiver_name"] ?? $order["sender_first_name"], "shipToContactNo" => $order["receiver_mobile"] ?? $order["sender_phone_no"], 'grand_payable_amount"' => $order['grand_payable_amount"'], "perOrderQuantity" => $order["quantity"], "giftSendOption" => $order["gift_send_option"], "billing_tel" => $order["sender_phone_no"],];
        if ($order["delivery_mode"] == "both") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        } elseif ($order["delivery_mode"] == "email") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
        } elseif ($order["delivery_mode"] == "mobile") {
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
        $isSuccessful = false;
        $qsOrderUpdate = QsOrder::where("refno", $orderCreatedResponse["refno"])->first();
        if ($qsOrderUpdate) {
            $qsOrderUpdate->update(["woohoo_order_id" => $orderCreatedResponse["orderId"], "order_status" => $orderCreatedResponse["status"], "cards" => encrypt(json_encode($orderCreatedResponse["cards"]), env("ENCRYPTION_KEY")), "order_cancel" => json_encode($orderCreatedResponse["cancel"]), "order_payment" => isset($orderCreatedResponse["payments"]) ? json_encode($orderCreatedResponse["payments"]) : null, "currency" => json_encode($orderCreatedResponse["currency"]), "additionalTxnFields" => isset($orderCreatedResponse["additionalTxnFields"]) ? json_encode($orderCreatedResponse["additionalTxnFields"]) : null,]);
            $existingOrderSummary = OrderSummary::where('order_id', $qsOrderUpdate->id)->first();
            $existingOrderSummary->order_status = $orderCreatedResponse["status"];
            $existingOrderSummary->save();
            return $qsOrderUpdate->id;
        } else {
            $transactionStatusMessage = "Order with ID with referene number not found.";
            Log::error($transactionStatusMessage);
            return view("order.order-status", compact("transactionStatusMessage", "isSuccessful"));
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
            $name = $prepareSmsDetails["shipToName"];
            $orderNumber = $prepareSmsDetails["order_id"];
            $orderAmount = $prepareSmsDetails["order_amount"];
            $destination = $prepareSmsDetails["shipToContactNo"];
            
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
