<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderNotificationService;
use App\Services\Order\WoohooFulfillmentService;
use App\Services\Payment\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class WoohooProcessingController extends Controller
{
    /**
     * Maps legacy return-url statuses to consolidated payment status.
     */
    private const RETURN_STATUS_TO_PAYMENT_STATUS = [
        'success' => 'captured',
        'completed' => 'captured',
        'approved' => 'captured',
        'confirmed' => 'captured',
        'paid' => 'captured',
        'failed' => 'failed',
        'declined' => 'failed',
        'cancelled' => 'cancelled',
    ];

    public function __construct(
        private WoohooFulfillmentService $woohooFulfillment,
        private OrderNotificationService $orderNotifications,
        private PaymentService $payments,
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

            return __('payments.invalid_return_missing_merchant_order_id');
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
        $payment = Payment::query()
            ->where('gateway', 'unlimit')
            ->where('merchant_order_id', $merchantOrderId)
            ->first();

        if (! $payment) {
            Log::error('❌ No payment found for merchant_order_id (return)', [
                'merchant_order_id' => $merchantOrderId,
                'ip_address' => $request->ip(),
            ]);

            return __('payments.unable_verify_payment_contact_support');
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
                return __('payments.unable_verify_payment_contact_support');
            }
        }

        // Update payment status using transaction
        try {
            DB::beginTransaction();

            $newPaymentStatus = self::RETURN_STATUS_TO_PAYMENT_STATUS[$status] ?? 'pending';
            $payment->status = $newPaymentStatus;
            if ($newPaymentStatus === 'captured' && $payment->captured_at === null) {
                $payment->captured_at = now();
            }
            if ($newPaymentStatus === 'failed' && $payment->failed_at === null) {
                $payment->failed_at = now();
            }
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
            if (in_array($status, ['success', 'completed', 'approved', 'confirmed'])) {
                $Order = Order::where('id', $payment->order_id)->first();
                if ($Order) {
                    $Order->status = 'paid';
                    $Order->save();

                    if ($orderSummary && Schema::hasColumn('order_summaries', 'fulfilment_status')) {
                        $orderSummary->fulfilment_status = 'PENDING';
                        $orderSummary->save();
                    }

                    Log::info('✅ Order status updated to paid (awaiting fulfillment)', [
                        'order_id' => $Order->id,
                    ]);
                }
            } elseif (in_array($status, ['failed', 'declined', 'cancelled'])) {
                // Payment failed - set order to FAILED
                $Order = Order::where('id', $payment->order_id)->first();
                if ($Order) {
                    $Order->status = $status === 'cancelled' ? 'cancelled' : 'failed';
                    $Order->save();

                    if ($orderSummary && Schema::hasColumn('order_summaries', 'fulfilment_status')) {
                        $orderSummary->fulfilment_status = $status === 'cancelled' ? 'CANCELLED' : 'FAILED';
                        $orderSummary->save();
                    }

                    Log::info('❌ Order status updated (payment not captured)', [
                        'order_id' => $Order->id,
                        'payment_status' => $status,
                    ]);
                }
            }

            DB::commit();

            Log::info('✅ Payment status updated', [
                'payment_id' => $payment->id,
                'merchant_order_id' => $merchantOrderId,
                'status' => $payment->status,
                'raw_status' => $rawStatus,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to update payment status', [
                'error' => $e->getMessage(),
                'merchant_order_id' => $merchantOrderId,
                'trace' => $e->getTraceAsString(),
            ]);

            return __('payments.failed_update_payment_status_contact_support');
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
                $payment = Payment::query()
                    ->where('gateway', 'unlimit')
                    ->where('merchant_order_id', $merchantOrderId)
                    ->first();
            }

            // Fallback: If no merchant_order_id, try to find by user_id (if authenticated)
            if (! $payment && Auth::check()) {
                $payment = Payment::query()
                    ->where('gateway', 'unlimit')
                    ->where('user_id', Auth::id())
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $payment) {
                return redirect()->route('my-order')
                    ->with('error', __('payments.payment_not_found_contact_support'));
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
            $status = strtolower(trim((string) ($payment->status ?? 'pending')));

            Log::info('Processing Woohoo order creation', [
                'merchant_order_id' => $merchantOrderId,
                'status' => $status,
                'payment_id' => $payment->id,
                'payment_status_before_refresh' => $payment->getOriginal('status') ?? 'N/A',
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
                        $normalized = $statusMap[$rawStatus];
                        $payment->status = self::RETURN_STATUS_TO_PAYMENT_STATUS[$normalized] ?? 'pending';
                        $payment->save();

                        Log::info('✅ Payment status updated from request parameter', [
                            'merchant_order_id' => $merchantOrderId,
                            'raw_status' => $rawStatus,
                            'normalized_status' => $payment->status,
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
                    $payment->status = self::RETURN_STATUS_TO_PAYMENT_STATUS[$verifiedStatus] ?? 'pending';
                    $payment->save();

                    Log::info('✅ Payment status verified and updated from gateway', [
                        'merchant_order_id' => $merchantOrderId,
                        'verified_status' => $payment->status,
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
                    ->with('error', __('payments.order_not_found_contact_support'));
            }

            // 3️⃣ Check status - include all success variations
            if (! in_array($payment->status, ['captured'], true)) {
                Log::warning('⚠️ Payment status not successful', [
                    'merchant_order_id' => $merchantOrderId,
                    'status' => $payment->status,
                    'payment_id' => $payment->id,
                ]);

                return redirect()->route('my-order')
                    ->with('error', __('payments.payment_not_successful_status', [
                        'status' => ucfirst((string) $payment->status),
                    ]));
            }

            Log::info('🔍 Checking woohoo_order_id before API call', [
                'woohoo_order_id' => $Order->woohoo_order_id,
            ]);

            // 4️⃣ Enrich order with billing details (optional, used by Woohoo fulfillment)
            $billing = $Order->billingSnapshot;
            if ($billing) {
                $Order->email = $billing->email ?: ($Order->email ?? null);
                $Order->mobile = $billing->mobile ?: ($Order->mobile ?? null);
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
                    $Order->status = 'completed';
                    $Order->save();

                    // Optional legacy mirror
                    $orderSummary = OrderSummary::where('order_id', $Order->id)->first();
                    if ($orderSummary && Schema::hasColumn('order_summaries', 'fulfilment_status')) {
                        $orderSummary->fulfilment_status = 'COMPLETE';
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
                    $Order->status = 'failed';
                    $Order->save();

                    $orderSummary = OrderSummary::where('order_id', $Order->id)->first();
                    if ($orderSummary && Schema::hasColumn('order_summaries', 'fulfilment_status')) {
                        $orderSummary->fulfilment_status = 'FAILED';
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
                'order' => $Order->only(['id', 'refno', 'woohoo_order_id', 'status', 'grand_payable_amount', 'product_name']),
                'woohoo' => $result,
                'isSuccess' => $isSuccess,
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo processing error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('my-order')
                ->with('error', __('payments.unexpected_error_occurred'));
        }
    }

    /**
     * Show processing status page
     */
    public function showProcessing(Request $request)
    {
        $merchantOrderId = $request->merchant_order_id;
        if (! $merchantOrderId) {
            return redirect()->route('my-order')->with('error', __('payments.missing_payment_reference'));
        }

        $payment = Payment::query()
            ->where('gateway', 'unlimit')
            ->where('merchant_order_id', $merchantOrderId)
            ->first();

        if (! $payment) {
            return redirect()->route('my-order')->with('error', __('payments.payment_record_not_found'));
        }

        $amount = $payment->amount_minor ? ((int) $payment->amount_minor) / 100 : 0;

        return Inertia::render('Checkout/Processing', [
            'order_id' => $payment->order_id,
            'status' => $payment->status ?? 'Processing',
            'amount' => $amount,
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
        $result = $this->payments->queryStatus('unlimit', (string) $merchantOrderId);

        if (! $result->success) {
            Log::warning('Payment status query failed', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $result->error,
            ]);

            return null;
        }

        return match ($result->status) {
            'paid' => 'success',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get Unlimit API token (reuse from existing token or get new one)
     */
    // getUnlimitToken removed: token + status querying are handled by PaymentService/UnlimitGateway.
}
