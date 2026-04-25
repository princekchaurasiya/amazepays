<?php

namespace App\Http\Controllers;

use App\Enums\ResponseCode;
use App\Models\Billing;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Payment;
use App\Services\Order\WoohooApiService;
use App\Services\Order\WoohooLegacyNotificationService;
use App\Services\Order\WoohooLegacyOrderSyncService;
use App\Support\Http\ResponsePayload;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class WoohooOrderController extends Controller
{
    public function __construct(
        private WoohooApiService $woohooApi,
        private WoohooLegacyNotificationService $legacyNotifications,
        private WoohooLegacyOrderSyncService $woohooOrderSync,
    ) {}

    public function createWoohooOrderRequest(Order $order, $payment)
    {
        Log::info('******* Entered createWoohooOrderRequest ***********', [
            'order_id' => $order->id,
            'merchant_order_id' => $order->merchant_order_id,
            'payment_status_param' => $payment->payment_status ?? ($payment->status ?? null),
        ]);

        // Consolidated Payment model (status) or legacy attribute shapes.
        $rawPaymentStatus = (string) ($payment->payment_status ?? $payment->status ?? 'pending');
        $resolvedStatus = strtolower(trim($rawPaymentStatus));
        if (in_array($resolvedStatus, ['captured', 'paid'], true)) {
            $resolvedStatus = 'success';
        }

        Log::info('Resolved payment status', [
            'order_id' => $order->id,
            'resolved_status' => $resolvedStatus,
        ]);

        if (! in_array($resolvedStatus, ['success', 'completed', 'approved'], true)) {
            Log::info('Skipping Woohoo order creation because payment is not completed', [
                'order_id' => $order->id,
                'payment_status' => $resolvedStatus,
            ]);

            return [
                'success' => false,
                'message' => __('payments.payment_not_completed_yet'),
                'status' => 'FAILED',
            ];
        }

        $validStatuses = ['completed', 'success', 'approved'];
        $normalizedStatus = strtolower(trim($resolvedStatus));
        if (! in_array($normalizedStatus, $validStatuses)) {
            Log::info('Woohoo blocked payment status', [
                'order_id' => $order->id,
                'payment_status' => $resolvedStatus,
            ]);

            return [
                'success' => false,
                'message' => __('payments.payment_not_completed_yet'),
                'status' => 'FAILED',
            ];
        }

        Log::info('Payment verified as COMPLETED, proceeding with Woohoo order creation', [
            'order_id' => $order->id,
            'payment_status' => $resolvedStatus,
        ]);

        // ✅ Step 3: (Your existing Woohoo creation logic below — unchanged)
        $billinginfo = Billing::where('order_id', $order->id)->first()
            ?? Billing::latest()->first();
        $billingName = $billinginfo->billing_name ?? '';
        $parts = preg_split('/\s+/', trim($billingName), 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';
        $refno = $order->refno;

        $checkoutData = session('checkout_data', []);
        Log::info('Checkout data', ['data' => $checkoutData]);
        $productData = session('selected_product');
        Log::info('Product data', ['productData' => $productData]);
        $normalizedSku = $order->sku ?? ($productData['sku'] ?? null);
        $normalizedQuantity = (int) ($order->quantity ?? ($checkoutData['quantity'] ?? 1));
        $normalizedDenomination = (float) ($order->denomination ?? ($checkoutData['denomination'] ?? 0));

        $normalizedAmount = (float) (
            $order->amount_payable_after_discount
            ?? $order->grand_payable_amount
            ?? ($normalizedDenomination > 0 ? $normalizedDenomination * max(1, $normalizedQuantity) : 0)
        );

        // Woohoo expects the original price; do not send discounted amounts.
        $woohooPrice = (float) ($order->price ?? 0);
        if ($woohooPrice <= 0) {
            $woohooPrice = $normalizedDenomination > 0 ? (float) $normalizedDenomination : $normalizedAmount;
        }

        $create_order_request_body_data = [
            'address' => [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $billinginfo->billing_email,
                'telephone' => '+91'.$billinginfo->billing_tel,
                'line1' => $billinginfo->billing_address,
                'line2' => $billinginfo->billing_address_two,
                'city' => $billinginfo->billing_city,
                'region' => $billinginfo->billing_state,
                'country' => 'IN',
                'postcode' => $billinginfo->billing_zip,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'billing' => [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $billinginfo->billing_email,
                'telephone' => '+91'.$billinginfo->billing_tel,
                'line1' => $billinginfo->billing_address,
                'line2' => $billinginfo->billing_address_two,
                'city' => $billinginfo->billing_city,
                'region' => $billinginfo->billing_state,
                'country' => 'IN',
                'postcode' => $billinginfo->billing_zip,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'payments' => [[
                'code' => 'svc',
                'amount' => $woohooPrice,
            ]],
            'refno' => $refno,
            'products' => [[
                'sku' => $normalizedSku,
                'price' => $woohooPrice,
                'qty' => (int) $normalizedQuantity,
                'currency' => 356,
            ]],
            'syncOnly' => $normalizedQuantity > (int) env('SYNC_ONLY_THRESHOLD') ? false : true,
            'delivery_mode' => 'API',
        ];

        try {
            return $this->woohooApi->createOrder($create_order_request_body_data);
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => __('payments.woohoo_connection_issue_try_again')];
        } catch (\Throwable $e) {
            Log::error('Error during Woohoo order creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'order_id' => $order->id ?? null,
                'merchant_order_id' => $order->merchant_order_id ?? null,
            ]);

            return ['success' => false, 'message' => __('payments.woohoo_unexpected_error_contact_support')];
        }
    }

    public function createOrder(Request $request, ?Order $order = null)
    {
        try {
            /**************************************
             * 1. Get Order - handle both route model binding and POST request
             **************************************/
            // If order not provided via route model binding, get from request
            if (! $order) {
                $orderId = $request->input('order_id');

                if (! $orderId) {
                    Log::error('Order ID not provided in resend-order request', [
                        'request_keys' => $request->keys(),
                    ]);

                    return redirect()->route('panel.orders.index')
                        ->with('error', __('payments.admin_order_id_required_try_again'));
                }

                $order = Order::find($orderId);

                if (! $order) {
                    Log::error('Order not found by order_id', [
                        'order_id' => $orderId,
                    ]);

                    return redirect()->route('panel.orders.index')
                        ->with('error', __('payments.admin_order_not_found_verify_try_again'));
                }
            }

            /**************************************
             * 2. Get merchant order ID
             **************************************/
            $merchantOrderId = $order->merchant_order_id;
            Log::info('Merchant Order ID', ['merchant_order_id' => $merchantOrderId, 'order_id' => $order->id]);

            if (! $merchantOrderId) {
                Log::error('Merchant Order ID is null', [
                    'order_id' => $order->id,
                    'order_status' => $order->order_status,
                ]);

                return redirect()->route('panel.orders.index')
                    ->with('error', __('payments.admin_order_missing_merchant_order_id'));
            }

            /**************************************
             * 3. Fetch Unlimit payment record (consolidated payments table)
             **************************************/
            $paymentRecord = Payment::findUnlimitForOrder($order, (string) $merchantOrderId);

            if (! $paymentRecord) {
                Log::error('Unlimit payment row not found', [
                    'merchant_order_id' => $merchantOrderId,
                    'order_id' => $order->id,
                ]);

                return redirect()->route('panel.orders.index')
                    ->with('error', __('payments.admin_payment_record_not_found_for_order'));
            }

            // Gateway callbacks may use "success"; consolidated schema uses captured / authorized.
            $paymentStatus = strtolower((string) ($paymentRecord->payment_status ?? $paymentRecord->status ?? ''));
            if (! $paymentRecord->isSuccessfulForFulfillment()
                && ! in_array($paymentStatus, ['success', 'completed', 'paid', 'approved', 'confirmed'], true)) {
                Log::warning('Payment status not successful', [
                    'payment_status' => $paymentRecord->status,
                    'merchant_order_id' => $merchantOrderId,
                    'order_id' => $order->id,
                ]);

                return redirect()->route('panel.orders.index')
                    ->with('error', __('payments.admin_payment_status_not_successful_current', [
                        'status' => $paymentRecord->status ?? 'Unknown',
                    ]));
            }

            /**************************************
             * 4. Update Refno
             **************************************/
            $order->refno = 'Amzr'.$order->id;
            $order->save();
            Log::info('Updated order refno', ['refno' => $order->refno]);
            session(['checkout_refno' => 'Amzr'.$order->id]);

            /**************************************
             * 5. Call Woohoo API
             **************************************/
            $response = $this->createWoohooOrderRequest($order, $paymentRecord);

            if (! is_array($response)) {
                Log::error('Woohoo returned non-array response', [
                    'response' => $response,
                ]);

                return $this->errorDefault();
            }

            if (empty($response)) {
                Log::error('Woohoo API returned empty response');

                return $this->errorDefault();
            }

            Log::info('Woohoo API Response 2', $response);

            /**************************************
             * 6. Handle Woohoo response safely
             **************************************/
            // Some responses return ["success" => true, "status" => "COMPLETE"]

            $success = $response['success'];
            $status = $response['status'];

            if ($success === true && $status === 'COMPLETE') {

                // Extract cards from Woohoo
                $cards = [];

                if (isset($response['data']['cards']) && is_array($response['data']['cards'])) {
                    $cards = $response['data']['cards'];
                    Log::info('Extracted Woohoo cards', ['count' => count($cards)]);
                }

                // Prepare handler format
                $handlerData = $response['data'] ?? [];
                $handlerData['status'] = $status;

                // Save vouchers
                $this->handleSuccessFullOrder($handlerData);

                // CRITICAL: Update Order and OrderSummary status to COMPLETE after successful Woohoo order creation
                // Note: handleSuccessFullOrder already updates the order via syncOrderFromWoohooResponse, but we ensure status consistency here
                DB::beginTransaction();
                try {
                    // Reload order to get latest woohoo_order_id from syncOrderFromWoohooResponse
                    $order->refresh();

                    $order->order_status = 'COMPLETE';
                    $order->save();

                    // Also update OrderSummary.order_status to maintain consistency
                    $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                    if ($orderSummary) {
                        $orderSummary->fulfilment_status = 'COMPLETE';
                        $orderSummary->save();

                        Log::info('✅ OrderSummary status updated to COMPLETE (resend-order)', [
                            'order_summary_id' => $orderSummary->id,
                            'order_id' => $order->id,
                        ]);
                    } else {
                        Log::warning('⚠️ OrderSummary not found when updating to COMPLETE (resend-order)', [
                            'order_id' => $order->id,
                        ]);
                    }

                    Log::info('✅ Order status updated to COMPLETE (resend-order successful)', [
                        'order_id' => $order->id,
                        'woohoo_order_id' => $order->woohoo_order_id ?? $response['data']['orderId'] ?? 'N/A',
                    ]);

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('❌ Failed to update order status after resend-order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Return success view with required variables
                return Inertia::render('Checkout/OrderStatus', [
                    'transactionStatusMessage' => __('errors.201', [], 'en') ?: 'Order processed successfully! Your gift card details have been sent to your email and SMS.',
                    'isSuccessful' => true,
                    'cardsArray' => $cards ?? [],
                ]);
            }

            /**************************************
             * 7. Error handling for Woohoo API error codes
             **************************************/
            if (($response['status_code'] ?? null) === 400 || ($response['status_code'] ?? null) === 500) {

                $errorCode = $response['errorCode'] ?? 'default';
                Log::error('Woohoo order error', [
                    'status_code' => $response['status_code'] ?? null,
                    'errorCode' => $errorCode,
                    'order_id' => $order->id,
                ]);

                $this->sendOrderFailureMail($order);

                // Update order status to FAILED
                DB::beginTransaction();
                try {
                    $order->order_status = 'FAILED';
                    $order->save();

                    // Also update OrderSummary.order_status to maintain consistency
                    $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                    if ($orderSummary) {
                        $orderSummary->fulfilment_status = 'FAILED';
                        $orderSummary->save();
                    }

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('❌ Failed to update order status to FAILED', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return Inertia::render('Checkout/OrderStatus', [
                    'transactionStatusMessage' => __('errors.'.$errorCode, [], 'en') ?: 'Order processing failed. Please contact support.',
                    'isSuccessful' => false,
                ]);
            }

            /**************************************
             * 8. Anything else → treat as failed
             **************************************/
            Log::error('Unhandled Woohoo response format', [
                'response' => $response,
                'order_id' => $order->id,
            ]);

            // Update order status to FAILED
            DB::beginTransaction();
            try {
                $order->order_status = 'FAILED';
                $order->save();

                // Also update OrderSummary.order_status to maintain consistency
                $orderSummary = OrderSummary::where('order_id', $order->id)->first();
                if ($orderSummary) {
                    $orderSummary->fulfilment_status = 'FAILED';
                    $orderSummary->save();
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('❌ Failed to update order status to FAILED', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->errorDefault();

        } catch (\Throwable $e) {
            Log::error('Woohoo processing error', ['error' => $e->getMessage(), 'file' => $e->getFile(),
                'line' => $e->getLine()]);

            return $this->errorDefault();
        }
    }

    /****************************************
     * Common error response helper
     ****************************************/
    private function errorDefault()
    {
        return Inertia::render('Checkout/OrderStatus', [
            'transactionStatusMessage' => __('errors.default'),
            'isSuccessful' => false,
        ]);
    }

    /**
     * Get user-friendly error message based on HTTP status code
     * Technical details are logged separately
     */
    private function getUserFriendlyErrorMessage($statusCode)
    {
        $messages = [
            400 => 'Invalid request. Please contact support if this issue persists.',
            401 => 'Authentication failed. Please contact support.',
            403 => 'Access denied. Please contact support for assistance.',
            404 => 'Service not found. Please contact support.',
            408 => 'Request timeout. Please try again later.',
            429 => 'Too many requests. Please try again in a few moments.',
            500 => 'Server error. Our team has been notified. Please try again later.',
            502 => 'Service temporarily unavailable. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            504 => 'Request timeout. Please try again later.',
        ];

        return $messages[$statusCode] ?? 'An unexpected error occurred. Our team has been notified. Please contact support if this issue persists.';
    }

    /**
     * Clear session data after voucher processing is complete
     */
    public function clearSessionData()
    {
        Log::info('Clearing session data after voucher processing');
        Session::forget('payment_data');
        Session::forget('checkout_data');
        session()->forget('checkout_order_id');
        session()->forget('checkout_refno');
        session()->forget('payment_return_data');

        return ResponsePayload::ok(null, [
            'message' => 'Session data cleared successfully',
        ]);
    }

    private function handleErrorResponse($statusCode, $response, $order, $createOrderResponse)
    {
        $isSuccessful = false;
        $errorCode = $createOrderResponse['code'] ?? null;
        $defaultErrorMessage = __('errors.default');
        $errorMessage = __('errors.'.$errorCode, [], $defaultErrorMessage);
        if ($errorMessage == 'errors.'.$errorCode) {
            $errorMessage = $defaultErrorMessage;
        }
        Log::error("Error Code: {$errorCode}");
        Log::error("Error Message: {$errorMessage}");
        Log::error("Status Code: {$statusCode}");
        $transactionStatusMessage = $errorMessage;

        return ['transactionStatusMessage' => $transactionStatusMessage, 'status_code' => $statusCode, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, 'defaultErrorMessage' => $defaultErrorMessage, 'isSuccessful' => $isSuccessful];
    }

    private function handleUnexpectedErrorResponse(Exception $exception)
    {
        Log::error('Failed to place order. Exception caught: '.$exception->getMessage(), ['exception' => $exception]);

        return ['transactionStatusMessage' => $exception->getMessage(), 'status_code' => 500, 'errorCode' => '7002', 'errorMessage' => __('errors.7002'), 'defaultErrorMessage' => __('errors.default'), 'isSuccessful' => false];
    }

    private function getStatusByReferenceNumber($refno)
    {
        return $this->woohooApi->getStatusByReferenceNumber((string) $refno);
    }

    public function callCardActivation($cardStatusApiResponseData)
    {
        if (is_object($cardStatusApiResponseData)) {
            $cardStatusApiResponseData = json_decode(json_encode($cardStatusApiResponseData), true);
        }
        if (! is_array($cardStatusApiResponseData)) {
            $cardStatusApiResponseData = [];
        }

        return $this->woohooApi->callCardActivation($cardStatusApiResponseData);
    }

    public function getFinancialYear()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        if ($currentMonth >= 4) {
            $nextYear = $currentYear + 1;

            return $currentYear.'/'.substr($nextYear, -2);
        } else {
            $previousYear = $currentYear - 1;

            return $previousYear.'/'.substr($currentYear, -2);
        }
    }

    public function handleSuccessFullOrder($orderCreatedResponse)
    {
        $this->woohooOrderSync->handleSuccessFullOrder($orderCreatedResponse);
    }

    public function syncOrderFromWoohooResponse($orderCreatedResponse)
    {
        return $this->woohooOrderSync->syncOrderFromWoohooResponse($orderCreatedResponse);
    }

    /**
     * Check transaction status for the redirect page (lightweight check)
     */
    public function checkTransactionStatus(Request $request)
    {
        $merchantOrderId = $request->query('merchant_order_id');
        if (! $merchantOrderId) {
            return ResponsePayload::fail(
                code: ResponseCode::VALIDATION_FAILED,
                details: [
                    'status' => 'error',
                    'message' => 'Merchant order ID missing. Contact support.',
                ],
                httpStatus: 400
            );
        }

        $Order = Order::where('merchant_order_id', $merchantOrderId)->first();
        if (! $Order) {
            return ResponsePayload::fail(
                code: ResponseCode::NOT_FOUND,
                details: [
                    'status' => 'error',
                    'message' => 'Order not found in DB. Contact support.',
                ],
                httpStatus: 404
            );
        }

        // If order already complete
        if ($Order->order_status === 'COMPLETE') {
            return ResponsePayload::ok(null, [
                'status' => 'complete',
                'message' => __('payments.payment_completed_successfully'),
            ]);
        }

        // Fetch latest status from Unlimit API if needed
        $statusResponse = $this->getStatusByReferenceNumberLightweight($Order->refno);
        if ($statusResponse['status'] === 'COMPLETED') {
            $Order->order_status = 'COMPLETE';
            $Order->save();

            // CRITICAL: Also update OrderSummary.order_status to maintain consistency
            $orderSummary = OrderSummary::where('order_id', $Order->id)->first();
            if ($orderSummary) {
                $orderSummary->fulfilment_status = 'COMPLETE';
                $orderSummary->save();
            }

            return ResponsePayload::ok(null, [
                'status' => 'complete',
                'message' => __('payments.payment_completed_successfully'),
            ]);
        }

        return ResponsePayload::ok(null, [
            'status' => 'processing',
            'message' => __('payments.payment_still_processing'),
        ]);
    }

    /**
     * Lightweight status check without retry mechanism for frontend polling
     */
    private function getStatusByReferenceNumberLightweight($refno)
    {
        return $this->woohooApi->getStatusByReferenceNumberLightweight((string) $refno);
    }

    public function sendTransactionMail($prepareMailDetails)
    {
        $this->legacyNotifications->sendTransactionMail(is_array($prepareMailDetails) ? $prepareMailDetails : []);
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

    public function sendOrderFailureMail(Order $order)
    {
        $this->legacyNotifications->sendOrderFailureMail($order);
    }

    public function sendGiftMail($prepareMailDetails, $cardsArray)
    {
        $this->legacyNotifications->sendGiftMail(
            is_array($prepareMailDetails) ? $prepareMailDetails : [],
            is_array($cardsArray) ? $cardsArray : []
        );
    }

    public function sendTransactionalMessage($prepareSmsDetails)
    {
        $this->legacyNotifications->sendTransactionalMessage(is_array($prepareSmsDetails) ? $prepareSmsDetails : []);
    }

    public function sendGiftMessage($prepareSmsDetails, $cardsArray)
    {
        $this->legacyNotifications->sendGiftMessage(
            is_array($prepareSmsDetails) ? $prepareSmsDetails : [],
            is_array($cardsArray) ? $cardsArray : []
        );
    }
}
