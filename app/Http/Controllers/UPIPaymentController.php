<?php

namespace App\Http\Controllers;

use App\Helpers\CheckoutHelper;
use App\Models\ApiToken;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Product;
use App\Models\UnlimitPayment;
use App\Support\BillingRequirementResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UPIPaymentController extends Controller
{
    public function __construct(
        private BillingRequirementResolver $billingRequirements,
    ) {}

    public function getToken()
    {
        try {
            Log::info('🔄 Requesting token from Unlimit API (UPI)');

            $apiBaseUrl = config('unlimit.api_base_url');
            $authEndpoint = config('unlimit.endpoints.auth_token');
            $authUrl = rtrim($apiBaseUrl, '/').$authEndpoint;
            $timeout = config('unlimit.timeout', 15);
            $retryAttempts = config('unlimit.retry_attempts', 2);
            $retryDelay = config('unlimit.retry_delay', 1000);

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
            $statusCode = $response->status();

            Log::info('Token API Response', [
                'status_code' => $statusCode,
                'has_access_token' => isset($data['access_token']),
                'response_keys' => array_keys($data ?? []),
            ]);

            if (isset($data['access_token'])) {
                try {
                    DB::beginTransaction();

                    ApiToken::create([
                        'access_token' => $data['access_token'],
                        'expires_at' => isset($data['expires_in'])
                            ? Carbon::now()->addSeconds($data['expires_in'])
                            : null,
                    ]);

                    DB::commit();

                    Log::info('✅ Token saved to database successfully');

                    return $data['access_token'];

                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('❌ Failed to save token to database', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Still return token even if DB save fails
                    return $data['access_token'];
                }
            }

            // Log the error for debugging but return false instead of JSON response
            Log::error('❌ Token not received from Unlimit API (UPI)', [
                'response' => $data,
                'status_code' => $statusCode,
                'response_body' => $response->body(),
            ]);

            return false;

        } catch (ConnectionException $e) {
            Log::error('❌ Connection timeout/error while getting token', [
                'message' => $e->getMessage(),
                'timeout' => config('unlimit.timeout', 15),
                'url' => $authUrl ?? 'N/A',
            ]);

            return false;
        } catch (RequestException $e) {
            Log::error('❌ HTTP Exception while getting token', [
                'message' => $e->getMessage(),
                'response' => $e->response?->body(),
                'status' => $e->response?->status(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('❌ Unexpected error while getting token', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function store(Request $request)
    {
        try {
            $unknown = array_values(array_diff(array_keys($request->all()), ['order_id']));
            if ($unknown !== []) {
                return redirect()->back()->withErrors([
                    'unexpected_fields' => 'Unexpected input fields detected: '.implode(', ', $unknown),
                ]);
            }

            $request->validate([
                'order_id' => 'nullable|integer|min:1',
            ]);

            // SECURITY: Require authentication
            if (! Auth::check()) {
                Log::warning('❌ Unauthenticated payment attempt');

                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            $userId = Auth::id();
            // SECURITY: Use order_id from request or session (prefer request)
            $orderId = $request->input('order_id') ?? session('checkout_order_id');

            // SECURITY: Log minimal info (no sensitive data)
            Log::info('🔄 UPI Payment Initiated', [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'order_id' => $orderId ?? 'none',
            ]);

            if (! $orderId) {
                Log::error('❌ No order_id provided');

                return redirect()->back()->with('error', 'Order ID is required.');
            }

            // SECURITY: Fetch order from database and validate ownership
            $order = $this->findOrderForUser((int) $orderId, (int) $userId);

            if (! $order) {
                Log::error('❌ Order not found or unauthorized', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                return redirect()->back()->with('error', 'Order not found or unauthorized access.');
            }

            if ((string) $order->order_status !== 'Pending') {
                Log::warning('❌ Payment attempted for non-pending order', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'order_status' => $order->order_status,
                ]);

                return redirect()->back()->with('error', __('payments.order_processing_exists'));
            }

            $sourceProvider = Product::query()->whereKey($order->product_id)->value('source_provider');
            $requiredBillingFields = $this->billingRequirements->requiredFields((string) $order->payment_method, (string) $sourceProvider);
            $billingSnapshot = $this->resolveBillingSnapshot($order, $requiredBillingFields);
            if (! $billingSnapshot['ready']) {
                return redirect()->back()->with('error', __('checkout.billing_missing_for_payment', [
                    'fields' => implode(', ', $this->billingRequirements->humanize($billingSnapshot['missing'])),
                ]));
            }

            // SECURITY: Get amount from database, not from request
            $payableAmount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);

            if (! is_finite($payableAmount) || $payableAmount <= 0) {
                Log::error('❌ Invalid payable amount from database', [
                    'amount' => $payableAmount,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                return redirect()->back()->with('error', 'Invalid order amount. Please contact support.');
            }

            Log::info('✅ Using amount from database', [
                'order_id' => $orderId,
                'amount' => $payableAmount,
            ]);

            // Get Unlimit token (internal call)
            $token = $this->getToken();

            if (! $token) {
                Log::error('❌ Failed to get payment token');

                return redirect()->back()->with('error', 'Payment service is temporarily unavailable. Please try again later.');
            }

            // Generate current time with milliseconds and Z suffix in UTC
            $now = Carbon::now('UTC');
            $milliseconds = $now->format('v'); // milliseconds
            $time = $now->format("Y-m-d\TH:i:s.").$milliseconds.'Z';
            $merchantOrderId = (string) Str::uuid();

            $data = [
                'request' => [
                    'id' => (string) Str::uuid(),
                    'time' => $time,
                ],
                'merchant_order' => [
                    'id' => $merchantOrderId,
                    'description' => 'Unlimit transaction',
                ],
                'payment_method' => 'upi',
                'payment_data' => [
                    'amount' => $payableAmount,
                    'currency' => 'INR',
                ],
                'return_urls' => [
                    'success_url' => config('app.url').'/unlimit/return?merchant_order_id={merchant_order_id}&status={status}',
                    'decline_url' => config('app.url').'/unlimit/return?merchant_order_id={merchant_order_id}&payment_id={payment_id}&status={status}',
                ],
            ];

            // Use database transaction to ensure data consistency
            DB::beginTransaction();

            try {
                // SECURITY: Verify amount from database matches what we calculated
                $expectedAmount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);

                // Allow small rounding differences (0.01)
                $amountDifference = abs($payableAmount - $expectedAmount);
                if ($amountDifference > 0.01) {
                    DB::rollBack();
                    Log::error('❌ Amount mismatch - potential tampering', [
                        'order_id' => $order->id,
                        'user_id' => $userId,
                        'expected_amount' => $expectedAmount,
                        'received_amount' => $payableAmount,
                        'difference' => $amountDifference,
                        'ip_address' => $request->ip(),
                    ]);

                    return redirect()->back()->with('error', 'Payment amount mismatch. Please refresh and try again.');
                }

                // Use verified amount from database
                $payableAmount = $expectedAmount;

                // Update the order instead of creating new one
                $order->woohoo_order_id = 'WH-'.Str::upper(Str::random(6));
                $order->order_status = 'INITIATED';
                $order->merchant_order_id = $merchantOrderId;
                $order->save();

                Log::info('✅ Order updated', [
                    'order_id' => $order->id,
                    'merchant_order_id' => $merchantOrderId,
                    'woohoo_order_id' => $order->woohoo_order_id,
                ]);

                // SECURITY: Verify payment belongs to user's order
                $payment = UnlimitPayment::where('order_id', $orderId)
                    ->where('user_id', $userId) // CRITICAL: Verify payment belongs to user
                    ->first();

                if ($payment) {
                    $payment->merchant_order_id = $merchantOrderId;
                    $payment->amount = $payableAmount; // Use verified amount from database
                    $payment->payment_status = 'pending';
                    $payment->save();

                    Log::info('✅ Payment record updated', [
                        'payment_id' => $payment->id,
                        'merchant_order_id' => $merchantOrderId,
                        'amount' => $payableAmount,
                    ]);
                } else {
                    $payment = UnlimitPayment::create([
                        'order_id' => $order->id,
                        'user_id' => $userId,
                        'mer_amount' => $order->grand_payable_amount,
                        'price' => $order->denomination,
                        'qty' => $order->quantity,
                        'merchant_order_id' => $merchantOrderId,
                        'amount' => $payableAmount,
                        'payment_status' => 'pending',
                        'order_status' => 'UnPaid',
                    ]);

                    OrderSummary::query()->firstOrCreate(
                        ['order_id' => $order->id],
                        [
                            'payment_id' => $payment->id,
                            'payment_gateway' => 'unlimit',
                            'product_name' => (string) ($order->product_name ?? ''),
                            'payment_status' => (string) ($payment->order_status ?? 'UnPaid'),
                            'order_status' => (string) ($order->order_status ?? 'Pending'),
                        ]
                    );
                }

                DB::commit();

                // SECURITY: Regenerate session after successful payment initiation
                $request->session()->regenerate();

                Log::info('✅ Session regenerated after payment initiation', [
                    'user_id' => $userId,
                    'order_id' => $order->id,
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('❌ Database transaction failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', 'Failed to process order. Please try again.');
            }

            // Make payment API call
            try {
                Log::info('🔄 Sending payment request to Unlimit API', [
                    'merchant_order_id' => $merchantOrderId,
                    'amount' => $payableAmount,
                ]);

                // Use environment-aware API base URL from config (same pattern as getToken method)
                $apiBaseUrl = config('unlimit.api_base_url');
                $paymentsEndpoint = config('unlimit.endpoints.payments', '/api/payments');
                $paymentUrl = rtrim($apiBaseUrl, '/').$paymentsEndpoint;

                Log::info('Payment API URL', [
                    'url' => $paymentUrl,
                    'api_base_url' => $apiBaseUrl,
                    'endpoint' => $paymentsEndpoint,
                ]);

                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$token,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($paymentUrl, $data);

                $statusCode = $response->status();
                $responseData = $response->json();

                Log::info('✅ Payment API Response', [
                    'status_code' => $statusCode,
                    'has_redirect_url' => isset($responseData['redirect_url']),
                    'response_keys' => array_keys($responseData ?? []),
                ]);

                if (isset($responseData['redirect_url'])) {
                    Log::info('✅ Redirecting to payment gateway', [
                        'redirect_url' => $responseData['redirect_url'],
                        'merchant_order_id' => $merchantOrderId,
                    ]);

                    return redirect()->away($responseData['redirect_url']);
                } else {
                    Log::error('❌ No redirect URL in payment response', [
                        'response' => $responseData,
                        'status_code' => $statusCode,
                    ]);

                    return redirect()->back()->with('error', 'Payment gateway did not return a valid redirect URL.');
                }

            } catch (RequestException $e) {
                Log::error('❌ HTTP Exception in payment request', [
                    'message' => $e->getMessage(),
                    'response' => $e->response?->body(),
                    'status' => $e->response?->status(),
                ]);

                return redirect()->back()->with('error', 'Failed to connect to payment gateway. Please try again.');
            } catch (Exception $e) {
                Log::error('❌ Unexpected error in payment request', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.');
            }

        } catch (Exception $e) {
            Log::error('❌ Fatal error in UPI payment store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please contact support.');
        }
    }

    /**
     * Inbound payment status callback (Unlimit-issued UPI uses the same Signature scheme as card).
     */
    public function webhook(Request $request)
    {
        return app(UnlimitPaymentController::class)->webhook($request);
    }

    public function handleReturnSuccess(Request $request)
    {
        return redirect()->route('payment.success')->with('success', 'Payment completed successfully.');
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|min:1',
        ]);

        $userId = (int) Auth::id();
        $order = $this->findOrderForUser((int) $validated['order_id'], $userId);
        if (! $order) {
            abort(404);
        }

        $payableAmount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);

        return Inertia::render('Checkout/Status', [
            'status' => 'success',
            'msg' => 'Payment successful',
            'amount' => $payableAmount,
        ]);
    }

    /**
     * @return array{snapshot: array<string, string>, missing: array<int, string>, ready: bool}
     */
    private function resolveBillingSnapshot(Order $order, array $requiredFields = ['billing_name', 'billing_email']): array
    {
        $cached = $order->id ? CheckoutHelper::getBillingData((int) $order->id) : null;
        $user = Auth::user();

        $snapshot = [
            'billing_name' => trim((string) ($cached['billing_name'] ?? $user?->name ?? '')),
            'billing_email' => trim((string) ($cached['billing_email'] ?? $user?->email ?? '')),
            'billing_tel' => trim((string) ($cached['billing_tel'] ?? $user?->mobile ?? '')),
            'billing_zip' => trim((string) ($cached['billing_zip'] ?? $user?->billing_zip ?? '')),
            'billing_address' => trim((string) ($cached['billing_address'] ?? $user?->billing_address ?? '')),
            'billing_address_two' => trim((string) ($cached['billing_address_two'] ?? $user?->billing_address_two ?? '')),
            'billing_city' => trim((string) ($cached['billing_city'] ?? $user?->billing_city ?? '')),
            'billing_state' => trim((string) ($cached['billing_state'] ?? $user?->billing_state ?? '')),
            'billing_country' => trim((string) ($cached['billing_country'] ?? $user?->billing_country ?? 'IN')),
            'billing_gst_number' => trim((string) ($cached['billing_gst_number'] ?? '')),
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (($snapshot[$field] ?? '') === '') {
                $missing[] = $field;
            }
        }

        return [
            'snapshot' => $snapshot,
            'missing' => $missing,
            'ready' => count($missing) === 0,
        ];
    }

    private function findOrderForUser(int $orderId, int $userId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('user_id', $userId)
            ->first();
    }
}
