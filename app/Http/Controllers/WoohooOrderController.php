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
    $qsOrderDetails = Session::get("payment_data");

    Log::info(
        "Payment data collected from session and stored in \$qsOrderDetails variable is: " .
            json_encode($qsOrderDetails)
    );

    $isSuccessful = false;
    $transactionStatusMessage = __("errors.default"); // Default message for cases where no specific message is set

    // Ensure payment data is present
    if ($qsOrderDetails) {
        $orderCreatedResponse = $this->createWoohooOrderRequest($qsOrderDetails);

        if ($orderCreatedResponse) {
            if (
                isset($orderCreatedResponse["status"]) &&
                $orderCreatedResponse["status"] == "COMPLETE"
            ) {
                $transactionStatusMessage = __("errors.201");
                $isSuccessful = true;
                $this->handleSuccessFullOrder($orderCreatedResponse);

            } elseif (
                isset($orderCreatedResponse["status_code"]) &&
                $orderCreatedResponse["status_code"] == "400"
            ) {
                $errorCode = $orderCreatedResponse["errorCode"] ?? "default";
                $transactionStatusMessage = __("errors." . $errorCode);
                Log::error(
                    "Order creation failed with status 400 and error code: " .
                        $errorCode
                );
            } elseif (
                isset($orderCreatedResponse["status_code"]) &&
                $orderCreatedResponse["status_code"] == "500"
            ) {
                $errorCode = $orderCreatedResponse["errorCode"] ?? "default";
                $transactionStatusMessage = __("errors." . $errorCode);
                Log::error(
                    "Order creation failed with status 500 and error code: " .
                        $errorCode
                );
            } else {
                $transactionStatusMessage = __("errors.default");
                Log::error(
                    "Unexpected response from order creation: " .
                        json_encode($orderCreatedResponse)
                );
            }
        } else {
            $transactionStatusMessage = __("errors.default");
            Log::error("Order creation request failed. No response received.");
        }
    } else {
        $transactionStatusMessage = __("errors.default");
        Log::error("No payment data found in session.");
    }

    Log::info("Session before clearing: " . json_encode(Session::all()));

    // Forget specific session variables related to the order
    Session::forget('session_qs_order_id');
    Session::forget('session_refno');
    Session::forget('payment_data');

    // Optionally, reset the order-related data
    Session::put('session_qs_order_id', null);
    Session::put('session_refno', null);

    Log::info("Session after clearing specific variables: " . json_encode(Session::all()));

    // Return the view with the appropriate status message
    return view(
        "order.order-status",
        compact("transactionStatusMessage", "isSuccessful")
    );
}



    public function createWoohooOrderRequest($qsOrderDetails)
    {
        Log::info(
            "******* you are in createWoohooOrderRequest function ***********"
        );

        // Generate a unique reference number
        $refno = $qsOrderDetails->refno;
        $create_order_request_body_data = [
            "address" => [
                "firstname" => $qsOrderDetails->sender_first_name,
                "lastname" => "test",
                "email" => $qsOrderDetails->sender_email,
                "telephone" => "+91" . $qsOrderDetails->sender_phone_no,
                "line1" => $qsOrderDetails->sender_address_1,
                "line2" => $qsOrderDetails->sender_address_2,
                "city" => $qsOrderDetails->sender_city,
                "region" => $qsOrderDetails->sender_state,
                "country" => "IN",
                "postcode" => $qsOrderDetails->sender_post_code,
                "languages" => "Hindi",
                "billToThis" => true,
            ],
            "billing" => [
                "firstname" => $qsOrderDetails->sender_first_name,
                "lastname" => "test",
                "email" => $qsOrderDetails->sender_email,
                "telephone" => "+91" . $qsOrderDetails->sender_phone_no,
                "line1" => $qsOrderDetails->sender_address_1,
                "line2" => $qsOrderDetails->sender_address_2,
                "city" => $qsOrderDetails->sender_city,
                "region" => $qsOrderDetails->sender_state,
                "country" => "IN",
                "postcode" => $qsOrderDetails->sender_post_code,
                "languages" => "Hindi",
                "billToThis" => true,
            ],
            "payments" => [
                [
                    "code" => "svc",
                    "amount" => $qsOrderDetails->grand_payable_amount,
                ],
            ],
            "refno" => $refno,
            "products" => [
                [
                    "sku" => $qsOrderDetails->sku,
                    "price" => $qsOrderDetails->denomination,
                    "qty" => $qsOrderDetails->quantity,
                    "currency" => "356",
                ],
            ],
            "syncOnly" =>
                $qsOrderDetails->quantity > (int) env("SYNC_ONLY_THRESHOLD")
                    ? false
                    : true,
            "delivery_mode" => "API",
        ];

        Log::info(
            "########\nWoohoo create order request body data is:\n" .
                print_r($create_order_request_body_data, true) .
                "\n#######"
        );

        $requestBody = json_encode($create_order_request_body_data);
        $requestHttpMethod = "post";
        $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/orders";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature(
            $requestBody,
            $requestHttpMethod,
            $absApiUrl,
            $clientSecret
        );
        $dateAtClient = Carbon::now()->toIso8601String();

        Log::info(
            "**************** Order Creation Api *************************" .
                "\n"
        );
        Log::info("Before hitting API, time is " . now() . "\n");

        try {
            Log::info("******* making HTTP request ***********");

            // Make the HTTP request
            $createOrderResponse = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $bearerToken,
                    "Accept" => "*/*",
                    "dateAtClient" => $dateAtClient,
                    "signature" => $signature,
                ])
                ->send("POST", $absApiUrl, [
                    "body" => $requestBody,
                ]);

            Log::info("API Request:", [
                "url" => $absApiUrl,
                "method" => $requestHttpMethod,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $bearerToken,
                    "Accept" => "*/*",
                    "dateAtClient" => $dateAtClient,
                    "signature" => $signature,
                ],
                "data" => $requestBody,
            ]);

            Log::info("\n");
            Log::info(
                "********************** Check order API hit response whether received or not ***************************" .
                    "\n"
            );

            $responseData = $createOrderResponse->json();
            $headers = $createOrderResponse->headers();
            $body = json_decode($createOrderResponse->body(), true); // Assuming the body is JSON

            Log::info("Get Response from Woohoo server", [
                "response" => $responseData,
            ]);

            Log::info("Woohoo API Response:", [
                "status_code" => $createOrderResponse->status(),
                "headers" => $headers,
                "body" => $body,
                // Add specific values if needed
                "order_id" => $responseData["orderId"] ?? null,
                "amount" => $responseData["payments"][0]["balance"] ?? null,
            ]);

            if ($createOrderResponse->successful()) {
                Log::info(
                    "200 response recived now we have to check for status is complete or processing"
                );

                $orderCreatedResponse = $createOrderResponse->json();

                // If status is Complete, check status thrice within 120 seconds
                if (
                    isset($orderCreatedResponse["status"]) &&
                    $orderCreatedResponse["status"] === "COMPLETE"
                ) {
                    return $orderCreatedResponse;
                }

                // If status is PROCESSING, check status thrice within 120 seconds
                elseif (
                    isset($orderCreatedResponse["status"]) &&
                    $orderCreatedResponse["status"] === "PROCESSING"
                ) {
                    Log::info("Order is in PROCESSING status");
                    Log::info(
                        "going for get status by refernce number function"
                    );
                    return $this->getStatusByReferenceNumber($refno);
                }
            } else {
                $statusCode = $createOrderResponse->status();
                $response = json_decode($createOrderResponse->body(), true);
                $errorResponse = $this->handleErrorResponse(
                    $statusCode,
                    $response,
                    $qsOrderDetails,
                    $createOrderResponse
                );
                return $errorResponse;
            }
        } catch (ConnectionException $e) {
            Log::error("cURL Error: " . $e->getMessage());

            $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);

            if (
                $statusFunctionResponse &&
                $statusFunctionResponse["status"] == "COMPLETE"
            ) {
                Log::info(
                    "Status function response is complete. Returning response."
                );
                return $statusFunctionResponse;
            } else {
                Log::info(
                    "Status function response is not complete. Returning false."
                );
                return [
                    "transactionStatusMessage" => __("errors.7002"), // Custom error message for error code 5321
                    "status_code" => 500, // Set specific status code to 400
                    "errorCode" => "7002", // Set specific error code to 5321
                    "errorMessage" => __("errors.7002"), // Error message based on the error code 5321
                    "defaultErrorMessage" => __("errors.default"), // Default error message
                    "isSuccessful" => false, // Indicate failure
                ];
            }
        } catch (\Exception $e) {
            Log::error("Unexpected exception: " . $e->getMessage());
            return $this->handleUnexpectedErrorResponse($e);
        }
    }

    private function handleErrorResponse(
        $statusCode,
        $response,
        $qsOrderDetails,
        $createOrderResponse
    ) {
        $isSuccessful = false;
        $errorCode = $createOrderResponse["code"] ?? null; // Adjusted to use $createOrderResponse
        $defaultErrorMessage = __("errors.default");
        $errorMessage = __("errors." . $errorCode, [], $defaultErrorMessage);

        // Ensure errorMessage defaults to a generic message if the specific one is not found
        if ($errorMessage == "errors." . $errorCode) {
            $errorMessage = $defaultErrorMessage;
        }

        Log::error("Error Code: {$errorCode}");
        Log::error("Error Message: {$errorMessage}");
        Log::error("Status Code: {$statusCode}");

        // Log headers only if $createOrderResponse is not null

        $transactionStatusMessage = $errorMessage;
        // dd($transactionStatusMessage, $statusCode, $errorCode, $errorMessage, $defaultErrorMessage, $isSuccessful );
        return [
            "transactionStatusMessage" => $transactionStatusMessage,
            "status_code" => $statusCode,
            "errorCode" => $errorCode,
            "errorMessage" => $errorMessage,
            "defaultErrorMessage" => $defaultErrorMessage,
            "isSuccessful" => $isSuccessful,
        ];
    }

    private function handleUnexpectedErrorResponse(Exception $exception)
    {
        Log::error(
            "Failed to place order. Exception caught: " .
                $exception->getMessage(),
            ["exception" => $exception]
        );
        return [
            "transactionStatusMessage" => $exception->getMessage(), // Custom error message for error code 5321
            "status_code" => 500, // Set specific status code to 400
            "errorCode" => "7002", // Set specific error code to 5321
            "errorMessage" => __("errors.7002"), // Error message based on the error code 5321
            "defaultErrorMessage" => __("errors.default"), // Default error message
            "isSuccessful" => false, // Indicate failure
        ];
    }

    private function getStatusByReferenceNumber($refno)
    {
        $requestHttpMethod = "GET";
        $absApiUrl =
            "https://" .
            setting("api.woohoo_url") .
            "/rest/v3/order/" .
            $refno .
            "/status";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature(
            "",
            $requestHttpMethod,
            $absApiUrl,
            $clientSecret
        );
        $dateAtClient = Carbon::now()->toIso8601String();

        $retryCount = env("RETRY_COUNT", 0); // Default value is 0 if not set
        $maxRetries = env("MAX_RETRIES", 3); // Default max retries is 3
        $retryInterval = env("RETRY_INTERVAL", 40); // Default interval is 40 seconds

        // Log the values
        Log::info("Retry count from .env: " . $retryCount);
        Log::info("Max retries from .env: " . $maxRetries);
        Log::info("Retry interval from .env: " . $retryInterval);

        $maxTime = $maxRetries * $retryInterval; // Total max time for retries

        Log::info("max time is: " . $maxTime);

        Log::info("0 count starts: " . $retryCount);

        while ($retryCount < $maxRetries) {
            try {
                $retryCount++; // Increment before logging

                // Get current time dynamically for each attempt
                $currentTime = Carbon::now()->toDateTimeString();

                // Log the attempt number and time for every attempt
                Log::info(
                    "Attempting to get status by ref no. Attempt: " .
                        $retryCount .
                        " at " .
                        $currentTime
                );

                // Existing logic for fetching the order status
                $orderStatusResponse = Http::acceptJson()
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

                    Log::info(
                        "Get order status by reference number response from Woohoo server:",
                        [
                            "response" => $cardStatusApiResponseData,
                        ]
                    );

                    // Check if 'status' key exists in the response
                    if (isset($cardStatusApiResponseData["status"])) {
                        if (
                            $cardStatusApiResponseData["status"] === "COMPLETE"
                        ) {
                            Log::info(
                                "Card activation status is COMPLETE. Processing further."
                            );
                            return $this->callCardActivation(
                                $cardStatusApiResponseData
                            );
                        } elseif (
                            $cardStatusApiResponseData["status"] ===
                            "PROCESSING"
                        ) {
                            Log::info(
                                "Order status is PROCESSING. Waiting for $retryInterval seconds before retrying."
                            );
                            sleep($retryInterval);
                        } else {
                            Log::info(
                                "Order status is neither COMPLETE nor PROCESSING. Exiting retry loop."
                            );
                            break;
                        }
                    } else {
                        // Log and return error if 'status' key is missing
                        Log::error(
                            "Undefined array key 'status' in API response."
                        );
                        return [
                            "transactionStatusMessage" => __("errors.7002"),
                            "status_code" => 500,
                            "status" => null,
                            "errorCode" => "7002",
                            "errorMessage" => __("errors.7002"),
                            "defaultErrorMessage" => __("errors.default"),
                            "isSuccessful" => false,
                        ];
                    }
                } else {
                    Log::error("Order status check failed:", [
                        "status_code" => $orderStatusResponse->status(),
                        "response" => $orderStatusResponse->body(),
                    ]);
                    break; // Exit loop on failure
                }
            } catch (ConnectionException $exception) {
                Log::error(
                    "Failed to get order status due to connection issue: " .
                        $exception->getMessage()
                );
                return [
                    "transactionStatusMessage" => $exception->getMessage(),
                    "status_code" => 500,
                    "status" => null,
                    "errorCode" => "7001",
                    "errorMessage" => __("errors.7001"),
                    "defaultErrorMessage" => __("errors.default"),
                    "isSuccessful" => false,
                ];
            } catch (Exception $e) {
                Log::error("An unexpected error occurred: " . $e->getMessage());
                $this->handleUnexpectedErrorResponse($e);
                return [
                    "transactionStatusMessage" => $e->getMessage(),
                    "status_code" => 500,
                    "status" => null,
                    "errorCode" => "7002",
                    "errorMessage" => __("errors.7002"),
                    "defaultErrorMessage" => __("errors.default"),
                    "isSuccessful" => false,
                ];
            }
        }

        Log::warning("Max retries reached. Status check failed.");
        return [
            "transactionStatusMessage" => __("errors.5321"),
            "status_code" => 400,
            "status" => null,
            "errorCode" => "5321",
            "errorMessage" => __("errors.5321"),
            "defaultErrorMessage" => __("errors.default"),
            "isSuccessful" => false,
        ];
    }

    public function callCardActivation($cardStatusApiResponseData)
    {
        Log::info("You are in Card Activation Function");
        $orderId = $cardStatusApiResponseData["orderId"];
        $clientSecret = setting("api.qs_clientSecret"); // Your client secret
        $bearerToken = setting("api.bearer_token"); // Your bearer token
        $apiUrl = "https://" . setting("api.woohoo_url");
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $requestBody = "";
        $requestHttpMethod = "GET";
        $dateAtClient = Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature(
            $requestBody,
            $requestHttpMethod,
            $absApiUrl,
            $clientSecret
        );
        $activatedCardApiResponse = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                "signature" => $signature,
                "dateAtClient" => $dateAtClient,
            ])
            ->get($absApiUrl);

        if ($activatedCardApiResponse->status() == 200) {
            Log::alert("card activation successfull");

            $activatedCardApiResponseData = $activatedCardApiResponse->json();

            // Create a new array by merging the two response data arrays
            $combinedData = array_merge(
                $cardStatusApiResponseData,
                $activatedCardApiResponseData
            );

            return $combinedData;
        } else {
            Log::info("Order failed, response 200 not received");
            return [
                "transactionStatusMessage" => __("errors.7002"), // Custom error message for error code 5321
                "status_code" => 500, // Set specific status code to 400
                "errorCode" => "7002", // Set specific error code to 5321
                "errorMessage" => __("errors.7002"), // Error message based on the error code 5321
                "defaultErrorMessage" => __("errors.default"), // Default error message
                "isSuccessful" => false, // Indicate failure
            ];
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
        $isSuccessful = false;

        Log::info(
            "This is card response data: " . json_encode($orderCreatedResponse)
        );

        $orderId = $this->updateQsOrder($orderCreatedResponse);

        $order = QsOrder::join(
            "cc_avenue_payment",
            "cc_avenue_payment.order_id",
            "=",
            "qs_orders.id"
        )
            ->join("qs_products", "qs_products.sku", "=", "qs_orders.sku")
            ->where("qs_orders.id", $orderId)
            ->select("qs_orders.*", "cc_avenue_payment.*", "qs_products.*")
            ->first();

        $financialYear = $this->getFinancialYear();

        $invoiceNumber = "FRB2C-" . $financialYear . "-" . $orderId;
        $invoiceDate = date("d-m-Y");
        $cardsArray = json_decode(
            decrypt($order["cards"], env("ENCRYPTION_KEY")),
            true
        );
        $images = json_decode($order["images"], true);

        if ($images && isset($images["small"])) {
            $smallImageUrl = $images["small"];
        }

        // Update the qs_orders table with the invoice number
        QsOrder::where("id", $orderId)->update([
            "invoice_number" => $invoiceNumber,
        ]);

        $prepareMailDetails = [
            "name" => $order["sender_first_name"],
            "order_id" => $order["woohoo_order_id"],
            "reference_id" => $order["id"],
            "order_date" => $order["created_at"],
            "billing_name" => $order["sender_first_name"],
            "billing_email" => $order["sender_email"],
            "billing_tel" => $order["sender_phone_no"],
            "billing_address" =>
                $order["sender_address_1"] .
                " " .
                $order["sender_address_2"] .
                ", " .
                $order["sender_city"] .
                ", " .
                $order["sender_state"] .
                " " .
                $order["sender_post_code"],
            "payment_mode" => $order["payment_mode"],
            "bank_ref_no" => $order["bank_ref_no"],
            "grand_payable_amount" => $order["grand_payable_amount"],
            "gst_number" => $order["gst_number"] ?: "Unregistered",
            "discount" => $order["discounted_amount_value"],
            "amount_payable_after_discount" =>
                $order["amount_payable_after_discount"],
            "contact_person" => $order["sender_first_name"],
            "shipping_address" =>
                $order["delivery_mode"] === "email"
                    ? $order["sender_email"]
                    : $order["sender_address_1"] .
                        " " .
                        $order["sender_address_2"] .
                        ", " .
                        $order["sender_city"] .
                        ", " .
                        $order["sender_state"] .
                        " " .
                        $order["sender_post_code"],
            "invoice_number" => $invoiceNumber,
            "invoice_date" => $invoiceDate,
            "cardSku" => $order["sku"],
            "cardProductName" => $order["name"],
            "shipToName" =>
                $order["receiver_name"] ?? $order["sender_first_name"],
            "shipToEmail" => $order["receiver_email"] ?? $order["sender_email"],
            "shipToContactNo" =>
                $order["receiver_mobile"] ?? $order["sender_phone_no"],
            "quantity" => $order["quantity"],
            "smallImageUrl" => $smallImageUrl,
            "giftSendOption" => $order["gift_send_option"],
            "denomination" => $order["denomination"],
            "discount_percentage" => $order["discount_percentage"],
        ];

        $prepareSmsDetails = [
            "name" => $order["sender_first_name"],
            "order_id" => $order["woohoo_order_id"],
            "reference_id" => $order["id"],
            "order_date" => $order["created_at"],
            "billing_name" => $order["sender_first_name"],
            "order_amount" => $order["amount"],
            "cardSku" => $order["sku"],
            "cardProductName" => $order["name"],
            "shipToName" =>
                $order["receiver_name"] ?? $order["sender_first_name"],
            "shipToContactNo" =>
                $order["receiver_mobile"] ?? $order["sender_phone_no"],
            'grand_payable_amount"' => $order['grand_payable_amount"'],
            "perOrderQuantity" => $order["quantity"],
            "giftSendOption" => $order["gift_send_option"],
            "billing_tel" => $order["sender_phone_no"],
        ];

        if ($order["delivery_mode"] == "both") {
            $this->sendTransactionMail($prepareMailDetails);
            // $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
            // $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        } elseif ($order["delivery_mode"] == "email") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMail($prepareMailDetails, $cardsArray);
        } elseif ($order["delivery_mode"] == "mobile") {
            $this->sendTransactionMail($prepareMailDetails);
            $this->sendTransactionalMessage($prepareSmsDetails);
            $this->sendGiftMessage($prepareSmsDetails, $cardsArray);
        }
    }

    public function updateQsOrder($orderCreatedResponse)
    {
        $isSuccessful = false;

        $qsOrderUpdate = QsOrder::where(
            "refno",
            $orderCreatedResponse["refno"]
        )->first();

        // dd($orderCreatedResponse, $qsOrderUpdate);

        if ($qsOrderUpdate) {
            $qsOrderUpdate->update([
                "woohoo_order_id" => $orderCreatedResponse["orderId"],
                "order_status" => $orderCreatedResponse["status"],
                "cards" => encrypt(
                    json_encode($orderCreatedResponse["cards"]),
                    env("ENCRYPTION_KEY")
                ),
                "order_cancel" => json_encode($orderCreatedResponse["cancel"]),
                "order_payment" => isset($orderCreatedResponse["payments"])
                    ? json_encode($orderCreatedResponse["payments"])
                    : null,
                "currency" => json_encode($orderCreatedResponse["currency"]),
                "additionalTxnFields" => isset(
                    $orderCreatedResponse["additionalTxnFields"]
                )
                    ? json_encode($orderCreatedResponse["additionalTxnFields"])
                    : null,
            ]);

            // dd($qsOrderUpdate->id,$qsOrderUpdate );
            return $qsOrderUpdate->id;

            // return ['joinedData' => $order, 'qsOrderUpdate' => $qsOrderUpdate];
        } else {
            $transactionStatusMessage = "Order with ID $refno not found.";
            Log::error($transactionStatusMessage);

            return view(
                "order.order-status",
                compact("transactionStatusMessage", "isSuccessful")
            );
        }
    }

    public function sendTransactionMail($prepareMailDetails)
    {
        $pdf = PDF::loadView("layouts.invoice", $prepareMailDetails);
        Mail::send(
            ["html" => "layouts.mail"],
            compact("prepareMailDetails", "pdf"),
            function ($message) use ($prepareMailDetails, $pdf) {
                $message
                    ->from(
                        config("companyDefaultValues.sendMailFrom"),
                        config("companyDefaultValues.company_name")
                    )
                    ->to(
                        $prepareMailDetails["billing_email"],
                        $prepareMailDetails["billing_name"]
                    )
                    ->subject(config("companyDefaultValues.default_subject"))
                    ->attachData($pdf->output(), "invoice.pdf");
            }
        );
        $msg = "Trasnaction Mail created successfully!";
        $status = "success";
    }

    public function sendGiftMail($prepareMailDetails, $cardsArray)
    {
        Mail::send(
            ["html" => "layouts.giftmail"],
            compact("prepareMailDetails", "cardsArray"),
            function ($message) use ($prepareMailDetails) {
                $message
                    ->from(
                        config("companyDefaultValues.sendMailFrom"),
                        config("companyDefaultValues.company_name")
                    )
                    ->to(
                        $prepareMailDetails["shipToEmail"],
                        $prepareMailDetails["shipToName"]
                    )
                    ->subject(config("companyDefaultValues.gift_subject"));
            }
        );

        $msg = "Gift Mail created successfully!";
        $status = "success";
    }

    public function sendTransactionalMessage($prepareSmsDetails)
    {
        $name = $prepareSmsDetails["name"];
        $orderAmount = $prepareSmsDetails["order_amount"];
        $orderNumber = $prepareSmsDetails["order_id"];
        $productName = $prepareSmsDetails["cardProductName"];
        $destination = $prepareSmsDetails["billing_tel"];
        $sms_api_url = config("transactionSms.sms_api_url");
        $sms_user_name = config("transactionSms.sms_user_name");
        $sms_user_password = config("transactionSms.sms_user_password");
        $sms_source = config("transactionSms.sms_source");
        $sms_message =
            "Hello " .
            $name .
            ", Your order no " .
            $orderNumber .
            " of " .
            $orderAmount .
            " is generated successfully. Please check out respected Email for that. Thanks - FRENETIC INDIA.";
        $sms_entity_id = config("transactionSms.sms_entity_id");
        $sms_temp_id = config("transactionSms.sms_temp_id");

        // Construct the API URL with the message
        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";

        // Send the HTTP GET request to the API
        $response = Http::get($apiUrl);

        // Log the response for debugging
        \Log::info("API Response:", ["response" => $response]);
        \Log::info("response status:", [
            "response status" => $response->status(),
        ]);
        \Log::info("API URL IS:", ["API URL" => $apiUrl]);
    }

    public function sendGiftMessage($prepareSmsDetails, $cardsArray)
    {
        // Extract values from $prepareSmsDetails
        $name = $prepareSmsDetails["shipToName"];
        $orderNumber = $prepareSmsDetails["order_id"];
        $orderAmount = $prepareSmsDetails["order_amount"];
        $destination = $prepareSmsDetails["shipToContactNo"];

        // Configure SMS API parameters
        $sms_api_url = config("giftSms.sms_api_url");
        $sms_user_name = config("giftSms.sms_user_name");
        $sms_user_password = config("giftSms.sms_user_password");
        $sms_source = config("giftSms.sms_source");
        $sms_entity_id = config("giftSms.sms_entity_id");
        $sms_temp_id = config("giftSms.sms_temp_id");

        foreach ($cardsArray as $card) {
            $cardId = $card["cardNumber"];
            $cardPin = $card["cardPin"];
            $cardAmount = $card["amount"];
            $cardActivationCode = $card["activationCode"];
            $cardActivationURL = $card["activationUrl"];
            $cardValidity = date("d-M-Y", strtotime($card["validity"]));

            // Build the SMS message for this card
            $sms_message =
                "Hello " .
                $name .
                " You received a gift card and your Card details: " .
                "Card ID: " .
                $cardId .
                " Card Pin: " .
                $cardPin .
                " Amount " .
                $cardAmount .
                " Activation Code " .
                $cardActivationCode .
                " Activation URL " .
                $cardActivationURL .
                " Validity " .
                $cardValidity .
                " Please check your respected Email for more information. Thanks - FRENETIC INDIA";

            // Construct the API URL with the message
            $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";

            // Send the HTTP GET request to the API for this card
            $response = Http::get($apiUrl);

            // Log the response for debugging
            \Log::info("API Response:", ["response" => $response]);
            \Log::info("response status:", [
                "response status" => $response->status(),
            ]);
            \Log::info("API URL IS:", ["API URL" => $apiUrl]);
        }
    }
}

?>
