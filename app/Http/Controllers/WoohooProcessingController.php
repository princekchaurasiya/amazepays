<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\Billing;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\UnlimitPayment;
use App\Models\User;
use App\Services\Order\OrderNotificationService;
use App\Services\Order\WoohooFulfillmentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WoohooProcessingController extends Controller
{
    public function __construct(
        private WoohooFulfillmentService $woohooFulfillment,
        private OrderNotificationService $orderNotifications,
    ) {}

    public function handleReturn(Request $request)
    {
        Log::info('🔄 Unlimit Return Hit', [
            'query' => $request->query(),
            'full_url' => $request->fullUrl(),
            'session_id' => $request->session()->getId(),
            'user_authenticated_before' => Auth::check(),
            'user_id_before' => Auth::check() ? Auth::id() : null,
            'cookies' => $request->cookies->all(),
        ]);

        $merchantOrderId = $request->query('merchant_order_id')
            ?? $request->input('merchant_order_id')
            ?? session('merchant_order_id');
        $rawStatus = $request->query('status') ?? $request->input('status') ?? 'Unknown';

        if (! $merchantOrderId) {
            Log::error('❌ Missing merchant_order_id in return URL', [
                'query_params' => $request->query(),
                'input_params' => $request->input(),
                'session_merchant_order_id' => session('merchant_order_id'),
            ]);

            return 'Invalid return data. Missing merchant order ID.';
        }

        // SECURITY: Normalize status (handle "Confirmed", "CONFIRMED", "confirmed", etc.)
        $status = strtolower(trim($rawStatus));

        // Map common status variations to standard values
        $statusMap = [
            'confirmed' => 'success',
            'complete' => 'completed',
            'approve' => 'success',
            'approved' => 'success',  // Map approved to success
            'successful' => 'success',
            'paid' => 'success',
        ];

        if (isset($statusMap[$status])) {
            $status = $statusMap[$status];
        }

        Log::info('📋 Payment return parameters', [
            'merchant_order_id' => $merchantOrderId,
            'raw_status' => $rawStatus,
            'normalized_status' => $status,
            'query_params' => $request->query(),
        ]);

        // SECURITY: Fetch payment from DB and verify ownership if user is authenticated
        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (! $payment) {
            Log::error('❌ No payment found for merchant_order_id (return)', [
                'merchant_order_id' => $merchantOrderId,
                'ip_address' => $request->ip(),
            ]);

            return 'Unable to verify payment. Please contact support.';
        }

        // SECURITY: If user is authenticated, verify they own this payment
        if (Auth::check()) {
            $order = Order::where('id', $payment->order_id)
                ->where('user_id', Auth::id())
                ->first();

            if (! $order) {
                Log::warning('⚠️ Unauthorized payment access attempt', [
                    'merchant_order_id' => $merchantOrderId,
                    'user_id' => Auth::id(),
                    'payment_user_id' => $payment->user_id,
                    'ip_address' => $request->ip(),
                ]);

                // Don't reveal that payment exists, just show generic error
                return 'Unable to verify payment. Please contact support.';
            }
        }

        // Update payment status using transaction
        try {
            DB::beginTransaction();

            $payment->payment_status = $status;
            $payment->save();

            // Also update OrderSummary payment_status
            $orderSummary = OrderSummary::where('order_id', $payment->order_id)
                ->where('payment_id', $payment->id)
                ->first();

            if ($orderSummary) {
                // Map payment status to order summary status
                $orderSummaryStatus = in_array($status, ['success', 'completed', 'approved', 'confirmed'])
                    ? 'Paid'
                    : (in_array($status, ['failed', 'declined', 'cancelled']) ? 'Failed' : 'UnPaid');

                $orderSummary->payment_status = $orderSummaryStatus;
                $orderSummary->save();

                Log::info('✅ OrderSummary payment status updated', [
                    'order_summary_id' => $orderSummary->id,
                    'payment_status' => $orderSummaryStatus,
                ]);
            }

            // Update Order status: Standardized flow - PENDING -> COMPLETE or FAILED
            // After payment success, keep as PENDING until Woohoo order is created
            if (in_array($status, ['success', 'completed', 'approved', 'confirmed'])) {
                $Order = Order::where('id', $payment->order_id)->first();
                if ($Order && ! in_array($Order->order_status, ['COMPLETE', 'FAILED'])) {
                    $Order->status = 'paid';
                    // Keep as PENDING (will be updated to COMPLETE after successful Woohoo order creation)
                    if (! in_array($Order->order_status, ['PENDING', 'COMPLETE', 'FAILED'])) {
                        $Order->order_status = 'PENDING';
                    }
                    $Order->save();

                    // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                    if ($orderSummary) {
                        $orderSummary->order_status = 'PENDING';
                        $orderSummary->save();
                    }

                    Log::info('✅ Order status updated to PENDING (payment successful, awaiting Woohoo order creation)', [
                        'order_id' => $Order->id,
                    ]);
                }
            } elseif (in_array($status, ['failed', 'declined', 'cancelled'])) {
                // Payment failed - set order to FAILED
                $Order = Order::where('id', $payment->order_id)->first();
                if ($Order) {
                    $Order->status = 'failed';
                    $Order->order_status = 'FAILED';
                    $Order->save();

                    // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                    if ($orderSummary) {
                        $orderSummary->order_status = 'FAILED';
                        $orderSummary->save();
                    }

                    Log::info('❌ Order status updated to FAILED (payment failed)', [
                        'order_id' => $Order->id,
                        'payment_status' => $status,
                    ]);
                }
            }

            DB::commit();

            Log::info('✅ Payment status updated', [
                'payment_id' => $payment->id,
                'merchant_order_id' => $merchantOrderId,
                'status' => $status,
                'raw_status' => $rawStatus,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to update payment status', [
                'error' => $e->getMessage(),
                'merchant_order_id' => $merchantOrderId,
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Failed to update payment status. Please contact support.';
        }

        // SECURITY: Re-authenticate user if they're logged out (session might be lost after external redirect)
        if (! Auth::check() && $payment->user_id) {
            $user = User::find($payment->user_id);
            if ($user) {
                // Use loginUsingId to avoid password verification
                Auth::loginUsingId($user->id, true); // true = remember the user
                // SECURITY: Regenerate session to prevent fixation attacks
                $request->session()->regenerate();

                Log::info('✅ Re-authenticated user after payment return', [
                    'user_id' => $user->id,
                    'merchant_order_id' => $merchantOrderId,
                    'session_id' => $request->session()->getId(),
                ]);
            } else {
                Log::error('❌ User not found for payment', [
                    'user_id' => $payment->user_id,
                    'merchant_order_id' => $merchantOrderId,
                ]);
            }
        } elseif (Auth::check()) {
            // SECURITY: Regenerate session after payment return to prevent session fixation
            $request->session()->regenerate();
            Log::info('✅ User already authenticated, session regenerated', [
                'user_id' => Auth::id(),
                'merchant_order_id' => $merchantOrderId,
            ]);
        }

        // Store merchant_order_id in session for later use
        session(['merchant_order_id' => $merchantOrderId]);

        // Force save session
        $request->session()->save();

        Log::info('✅ Payment updated via RETURN page', [
            'merchant_order_id' => $merchantOrderId,
            'status' => $status,
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::check() ? Auth::id() : null,
            'session_id' => $request->session()->getId(),
        ]);

        // Get order for display
        $Order = Order::where('merchant_order_id', $merchantOrderId)->first();

        $response = Inertia::render('Checkout/Processing', [
            'amount' => $payment->amount ?? 0,
            'merchant_order_id' => $merchantOrderId ?? '',
            'status' => $status ?? 'Unknown',
            'payment_id' => $payment->id ?? 'N/A',
            'order_id' => $Order->id ?? $payment->order_id ?? null,
        ])->toResponse($request);

        // Ensure session cookie is set with proper attributes for cross-site redirects
        if (Auth::check()) {
            // Set cache control headers
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');

            // Note: Session cookie is automatically set by Laravel's session middleware
            // The same_site setting in config/session.php controls this behavior
            // We've set it to 'lax' which allows cookies on cross-site GET redirects

            Log::info('✅ Session cookie set in response', [
                'session_id' => $request->session()->getId(),
                'user_id' => Auth::id(),
            ]);
        }

        return $response;
    }

    /**
     * Process the Woohoo order creation after successful payment
     */
    public function createOrder(Request $request)
    {
        try {
            // Get merchant_order_id from request or session
            $merchantOrderId = $request->input('merchant_order_id')
                ?? $request->query('merchant_order_id')
                ?? session('merchant_order_id');

            // 1️⃣ Find payment by merchant_order_id (more reliable than auth()->id())
            $payment = null;
            if ($merchantOrderId) {
                $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();
            }

            // Fallback: If no merchant_order_id, try to find by user_id (if authenticated)
            if (! $payment && Auth::check()) {
                $payment = UnlimitPayment::where('user_id', Auth::id())
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if (! $payment) {
                return redirect()->route('my-order')
                    ->with('error', 'No payment found. Please contact support.');
            }

            // Re-authenticate user if logged out
            if (! Auth::check() && $payment->user_id) {
                $user = User::find($payment->user_id);
                if ($user) {
                    // Use loginUsingId to avoid password verification
                    Auth::loginUsingId($user->id, true); // true = remember the user
                    // Regenerate session to prevent fixation attacks
                    $request->session()->regenerate();

                    Log::info('✅ Re-authenticated user in createOrder', [
                        'user_id' => $user->id,
                        'merchant_order_id' => $merchantOrderId,
                    ]);
                }
            } elseif (Auth::check()) {
                // User is already authenticated, just regenerate session
                $request->session()->regenerate();
            }

            // Force save session
            $request->session()->save();

            $merchantOrderId = $payment->merchant_order_id;

            // Refresh payment from database to get latest status (in case it was updated by handleReturn)
            $payment->refresh();
            $status = strtolower(trim($payment->payment_status ?? 'pending'));

            Log::info('Processing Woohoo order creation', [
                'merchant_order_id' => $merchantOrderId,
                'status' => $status,
                'payment_id' => $payment->id,
                'payment_status_before_refresh' => $payment->getOriginal('payment_status') ?? 'N/A',
            ]);

            // SECURITY: If status is still pending, try to get status from request parameters first
            // (in case handleReturn wasn't called but we have status in URL)
            if (in_array($status, ['pending', 'unknown', ''])) {
                $requestStatus = $request->query('status') ?? $request->input('status');
                if ($requestStatus) {
                    $rawStatus = strtolower(trim($requestStatus));
                    $statusMap = [
                        'confirmed' => 'success',
                        'complete' => 'completed',
                        'approve' => 'success',
                        'approved' => 'success',
                        'successful' => 'success',
                        'paid' => 'success',
                    ];

                    if (isset($statusMap[$rawStatus])) {
                        $status = $statusMap[$rawStatus];
                        $payment->payment_status = $status;
                        $payment->save();

                        Log::info('✅ Payment status updated from request parameter', [
                            'merchant_order_id' => $merchantOrderId,
                            'raw_status' => $rawStatus,
                            'normalized_status' => $status,
                        ]);
                    }
                }
            }

            // SECURITY: If status is still pending, verify with payment gateway API
            if (in_array($status, ['pending', 'unknown', ''])) {
                Log::info('🔄 Payment status is still pending, verifying with payment gateway', [
                    'merchant_order_id' => $merchantOrderId,
                ]);

                $verifiedStatus = $this->verifyPaymentStatusWithGateway($merchantOrderId, $payment);

                if ($verifiedStatus) {
                    $status = $verifiedStatus;
                    $payment->payment_status = $status;
                    $payment->save();

                    Log::info('✅ Payment status verified and updated from gateway', [
                        'merchant_order_id' => $merchantOrderId,
                        'verified_status' => $status,
                    ]);
                }
            }

            // 2️⃣ Fetch Order
            $Order = Order::where('merchant_order_id', $merchantOrderId)
                ->orderBy('id', 'ASC')
                ->first();

            if (! $Order) {
                Log::error('Order not found', ['merchant_order_id' => $merchantOrderId]);

                return redirect()->route('my-order')
                    ->with('error', 'Order not found. Please contact support.');
            }

            // 3️⃣ Check status - include all success variations
            $successStatuses = ['completed', 'approved', 'success', 'confirmed', 'paid'];
            if (! in_array($status, $successStatuses)) {
                Log::warning('⚠️ Payment status not successful', [
                    'merchant_order_id' => $merchantOrderId,
                    'status' => $status,
                    'payment_id' => $payment->id,
                ]);

                return redirect()->route('my-order')
                    ->with('error', 'Payment not successful. Status: '.ucfirst($status));
            }

            Log::info('🔍 Checking woohoo_order_id before API call', [
                'woohoo_order_id' => $Order->woohoo_order_id,
            ]);

            // 4️⃣ Enrich order with billing details (optional, used by Woohoo fulfillment)
            // Use billing info from payment record (UnlimitPayment has billing fields)
            if ($payment && ($payment->billing_email || $payment->billing_tel || $payment->billing_name)) {
                $Order->email = $payment->billing_email ?? null;
                $Order->mobile = $payment->billing_tel ?? null;
                $Order->billing_name = $payment->billing_name ?? null;
            }

            Log::info('🔥 Sending Woohoo order request', [
                'order_id' => $Order->id,
                'merchant_order_id' => $Order->merchant_order_id,
                'amount' => $Order->amount,
                'email' => $Order->email,
                'mobile' => $Order->mobile,
            ]);

            // 5️⃣ Create Woohoo order via fulfillment service (delegates to legacy controller)
            $result = $this->woohooFulfillment->createWoohooOrderRequest($Order, $payment);

            // Ensure we don't trigger "Undefined array key 'success'"
            $isSuccess = is_array($result) && array_key_exists('success', $result) && $result['success'] === true;

            // 6️⃣ Update order status based on Woohoo order creation result
            DB::beginTransaction();
            try {
                if ($isSuccess) {
                    // CRITICAL: Call handleSuccessFullOrder to save cards and send emails/SMS
                    // This was missing and caused first-time orders to not send emails/SMS!
                    $handlerData = $result['data'] ?? [];
                    $handlerData['status'] = $result['status'] ?? 'COMPLETE';
                    $this->woohooFulfillment->handleSuccessFullOrder($handlerData);

                    // Woohoo order created successfully - update to COMPLETE
                    $Order->refresh(); // Reload to get latest data from handleSuccessFullOrder
                    $Order->order_status = 'COMPLETE';
                    $Order->save();

                    // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                    $orderSummary = OrderSummary::where('order_id', $Order->id)->first();
                    if ($orderSummary) {
                        $orderSummary->order_status = 'COMPLETE';
                        $orderSummary->save();

                        Log::info('✅ OrderSummary status updated to COMPLETE', [
                            'order_summary_id' => $orderSummary->id,
                            'order_id' => $Order->id,
                        ]);
                    } else {
                        Log::warning('⚠️ OrderSummary not found when updating to COMPLETE', [
                            'order_id' => $Order->id,
                        ]);
                    }

                    Log::info('✅ Order status updated to COMPLETE (Woohoo order created successfully)', [
                        'order_id' => $Order->id,
                        'woohoo_order_id' => $Order->woohoo_order_id,
                    ]);
                } else {
                    // Woohoo order creation failed - update to FAILED
                    $Order->order_status = 'FAILED';
                    $Order->save();

                    // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                    $orderSummary = OrderSummary::where('order_id', $Order->id)->first();
                    if ($orderSummary) {
                        $orderSummary->order_status = 'FAILED';
                        $orderSummary->save();

                        Log::info('✅ OrderSummary status updated to FAILED', [
                            'order_summary_id' => $orderSummary->id,
                            'order_id' => $Order->id,
                        ]);
                    }

                    Log::error('❌ Order status updated to FAILED (Woohoo order creation failed)', [
                        'order_id' => $Order->id,
                        'woohoo_result' => $result,
                    ]);

                    $Order->loadMissing('user');
                    if ($Order->user) {
                        $this->orderNotifications->sendOrderFailure(
                            $Order,
                            'Woohoo order creation failed after successful payment.'
                        );
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('❌ Failed to update order status after Woohoo order creation', [
                    'order_id' => $Order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return Inertia::render('Checkout/Woohoo/Response', [
                'order' => $Order->only(['id', 'refno', 'woohoo_order_id', 'order_status', 'grand_payable_amount', 'product_name']),
                'woohoo' => $result,
                'isSuccess' => $isSuccess,
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo processing error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('my-order')
                ->with('error', 'Unexpected error occurred.');
        }
    }

    /**
     * Show processing status page
     */
    public function showProcessing(Request $request)
    {
        /*$paymentReturnData = session('payment_return_data');

         if (!$paymentReturnData) {
             return redirect()->route('my-order')->with('error', 'No payment data found.');
        }

         return view('woohoo.processing-woohoo', [
             'order_id' => $paymentReturnData['order_id'] ?? 'N/A',
             'status' => $paymentReturnData['status'] ?? 'Processing',
             'amount' => $paymentReturnData['amount'] ?? 0
         ]);*/

        $merchantOrderId = $request->merchant_order_id;
        if (! $merchantOrderId) {
            return redirect()->route('my-order')->with('error', 'Missing payment reference.');
        }

        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (! $payment) {
            return redirect()->route('my-order')->with('error', 'Payment record not found.');
        }

        return Inertia::render('Checkout/Processing', [
            'order_id' => $payment->order_id,
            'status' => $payment->payment_status ?? 'Processing',
            'amount' => $payment->amount ?? 0,
            'merchant_order_id' => $merchantOrderId ?? $payment->merchant_order_id ?? '',
            'payment_id' => $payment->id ?? 'N/A',
        ]);
    }

    /**
     * SECURITY: Verify payment status with Unlimit API
     * This is a fallback if return URL doesn't update status properly
     */
    private function verifyPaymentStatusWithGateway($merchantOrderId, $payment = null)
    {
        try {
            // Get valid token
            $token = $this->getUnlimitToken();
            if (! $token) {
                Log::error('❌ Failed to get token for payment verification');

                return null;
            }

            // Call Unlimit API to check payment status
            $apiBaseUrl = config('unlimit.api_base_url');
            $paymentsEndpoint = config('unlimit.endpoints.payments');
            $apiUrl = rtrim($apiBaseUrl, '/').$paymentsEndpoint;

            $timeout = config('unlimit.timeout', 15);
            $retryAttempts = config('unlimit.retry_attempts', 2);
            $retryDelay = config('unlimit.retry_delay', 1000);

            Log::info('🔄 Verifying payment status with Unlimit API', [
                'api_url' => $apiUrl,
                'merchant_order_id' => $merchantOrderId,
                'timeout' => $timeout,
                'retry_attempts' => $retryAttempts,
            ]);

            // Unlimit API may require request_id or payment_id instead of merchant_order_id
            // Since we don't have request_id, try using payment_id if available, otherwise skip API verification
            $requestParams = [];
            if ($payment && isset($payment->id)) {
                // Try using payment ID as request_id
                $requestParams['request_id'] = (string) $payment->id;
            } else {
                // Fallback: use merchant_order_id (may fail if API requires request_id)
                $requestParams['merchant_order_id'] = $merchantOrderId;
            }

            $response = Http::timeout($timeout)
                ->retry($retryAttempts, $retryDelay, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ])
                ->get($apiUrl, $requestParams);

            if ($response->successful()) {
                $data = $response->json();

                // Log the full response for debugging
                Log::info('🔍 Unlimit API response structure', [
                    'merchant_order_id' => $merchantOrderId,
                    'response_keys' => array_keys($data ?? []),
                    'has_list' => isset($data['list']),
                    'list_count' => isset($data['list']) ? count($data['list']) : 0,
                    'full_response' => $data,
                ]);

                // Handle array response (list of payments)
                if (isset($data['list']) && is_array($data['list']) && count($data['list']) > 0) {
                    $paymentData = $data['list'][0];
                    $rawStatus = $paymentData['payment_data']['status']
                        ?? $paymentData['status']
                        ?? $paymentData['payment_status']
                        ?? null;
                } else {
                    // Try multiple possible response structures
                    $rawStatus = $data['payment_data']['status']
                        ?? $data['status']
                        ?? $data['payment_status']
                        ?? null;
                }

                if ($rawStatus) {
                    $status = strtolower(trim($rawStatus));

                    // Normalize status
                    $statusMap = [
                        'confirmed' => 'success',
                        'complete' => 'completed',
                        'approve' => 'success',
                        'approved' => 'success',  // Map approved to success
                        'successful' => 'success',
                        'paid' => 'success',
                    ];

                    if (isset($statusMap[$status])) {
                        $status = $statusMap[$status];
                    }

                    Log::info('✅ Payment status verified from gateway', [
                        'merchant_order_id' => $merchantOrderId,
                        'raw_status' => $rawStatus,
                        'normalized_status' => $status,
                    ]);

                    return $status;
                }
            }

            Log::warning('⚠️ Could not verify payment status from gateway', [
                'merchant_order_id' => $merchantOrderId,
                'response_status' => $response->status(),
            ]);

            return null;

        } catch (ConnectionException $e) {
            Log::error('❌ Connection timeout/error verifying payment status', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $e->getMessage(),
                'timeout' => config('unlimit.timeout', 15),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('❌ Error verifying payment status with gateway', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Unlimit API token (reuse from existing token or get new one)
     */
    private function getUnlimitToken()
    {
        // Try to get valid token from database
        $token = ApiToken::where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($token) {
            return $token->access_token;
        }

        // Get new token
        try {
            $apiBaseUrl = config('unlimit.api_base_url');
            $authEndpoint = config('unlimit.endpoints.auth_token');
            $authUrl = rtrim($apiBaseUrl, '/').$authEndpoint;
            $timeout = config('unlimit.timeout', 15);
            $retryAttempts = config('unlimit.retry_attempts', 2);
            $retryDelay = config('unlimit.retry_delay', 1000);

            Log::info('🔄 Requesting new token from Unlimit API', [
                'api_url' => $authUrl,
                'timeout' => $timeout,
                'retry_attempts' => $retryAttempts,
            ]);

            $response = Http::timeout($timeout)
                ->retry($retryAttempts, $retryDelay, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->asForm()
                ->withHeaders([
                    'Authorization' => 'Basic '.base64_encode(env('UNLIMIT_CODE')),
                ])
                ->post($authUrl, [
                    'grant_type' => 'password',
                    'password' => env('UNLIMIT_SECRET_KEY'),
                    'terminal_code' => env('UNLIMIT_PUBLIC_KEY'),
                ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                ApiToken::create([
                    'access_token' => $data['access_token'],
                    'expires_at' => isset($data['expires_in'])
                        ? Carbon::now()->addSeconds($data['expires_in'])
                        : null,
                ]);

                return $data['access_token'];
            }
        } catch (ConnectionException $e) {
            Log::error('❌ Connection timeout/error getting Unlimit token', [
                'error' => $e->getMessage(),
                'timeout' => config('unlimit.timeout', 15),
                'url' => $authUrl ?? 'N/A',
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to get Unlimit token for verification', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
