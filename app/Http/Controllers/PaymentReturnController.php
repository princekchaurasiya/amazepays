<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\QsOrder;
use App\Http\Controllers\WoohooOrderController;
use App\Models\UnlimitPayment;
use App\Models\OrderSummary;
use App\Models\Billing;
use App\Models\User;
use Exception;

class WoohooProcessingController extends Controller
{

   public function handleReturn(Request $request)
{
    Log::info("🔄 Unlimit Return Hit", [
        'query'    => $request->query(),
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

    if (!$merchantOrderId) {
        Log::error("❌ Missing merchant_order_id in return URL", [
            'query_params' => $request->query(),
            'input_params' => $request->input(),
            'session_merchant_order_id' => session('merchant_order_id')
        ]);
        return "Invalid return data. Missing merchant order ID.";
    }
    
    // SECURITY: Normalize status (handle "Confirmed", "CONFIRMED", "confirmed", etc.)
    $status = strtolower(trim($rawStatus));
    
    // Map common status variations to standard values
    $statusMap = [
        'confirmed' => 'success',
        'complete' => 'completed',
        'approve' => 'approved',
        'successful' => 'success',
        'paid' => 'success',
    ];
    
    if (isset($statusMap[$status])) {
        $status = $statusMap[$status];
    }
    
    Log::info("📋 Payment return parameters", [
        'merchant_order_id' => $merchantOrderId,
        'raw_status' => $rawStatus,
        'normalized_status' => $status,
        'query_params' => $request->query()->all()
    ]);

    // SECURITY: Fetch payment from DB and verify ownership if user is authenticated
    $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

    if (!$payment) {
        Log::error("❌ No payment found for merchant_order_id (return)", [
            'merchant_order_id' => $merchantOrderId,
            'ip_address' => $request->ip()
        ]);
        return "Unable to verify payment. Please contact support.";
    }

    // SECURITY: If user is authenticated, verify they own this payment
    if (Auth::check()) {
        $order = QsOrder::where('id', $payment->order_id)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$order) {
            Log::warning("⚠️ Unauthorized payment access attempt", [
                'merchant_order_id' => $merchantOrderId,
                'user_id' => Auth::id(),
                'payment_user_id' => $payment->user_id,
                'ip_address' => $request->ip()
            ]);
            // Don't reveal that payment exists, just show generic error
            return "Unable to verify payment. Please contact support.";
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
            
            Log::info("✅ OrderSummary payment status updated", [
                'order_summary_id' => $orderSummary->id,
                'payment_status' => $orderSummaryStatus
            ]);
        }
        
        // Update QsOrder status: Standardized flow - PENDING -> COMPLETE or FAILED
        // After payment success, keep as PENDING until Woohoo order is created
        if (in_array($status, ['success', 'completed', 'approved', 'confirmed'])) {
            $qsOrder = QsOrder::where('id', $payment->order_id)->first();
            if ($qsOrder && !in_array($qsOrder->order_status, ['COMPLETE', 'FAILED'])) {
                // Keep as PENDING (will be updated to COMPLETE after successful Woohoo order creation)
                if (!in_array($qsOrder->order_status, ['PENDING', 'COMPLETE', 'FAILED'])) {
                    $qsOrder->order_status = 'PENDING';
                    $qsOrder->save();
                    
                    // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                    if ($orderSummary) {
                        $orderSummary->order_status = 'PENDING';
                        $orderSummary->save();
                    }
                    
                    Log::info("✅ QsOrder status updated to PENDING (payment successful, awaiting Woohoo order creation)", [
                        'order_id' => $qsOrder->id
                    ]);
                }
            }
        } elseif (in_array($status, ['failed', 'declined', 'cancelled'])) {
            // Payment failed - set order to FAILED
            $qsOrder = QsOrder::where('id', $payment->order_id)->first();
            if ($qsOrder) {
                $qsOrder->order_status = 'FAILED';
                $qsOrder->save();
                
                // CRITICAL: Also update OrderSummary.order_status to maintain consistency
                if ($orderSummary) {
                    $orderSummary->order_status = 'FAILED';
                    $orderSummary->save();
                }
                
                Log::info("❌ QsOrder status updated to FAILED (payment failed)", [
                    'order_id' => $qsOrder->id,
                    'payment_status' => $status
                ]);
            }
        }
        
        DB::commit();
        
        Log::info("✅ Payment status updated", [
            'payment_id' => $payment->id,
            'merchant_order_id' => $merchantOrderId,
            'status' => $status,
            'raw_status' => $rawStatus
        ]);
    } catch (Exception $e) {
        DB::rollBack();
        Log::error("❌ Failed to update payment status", [
            'error' => $e->getMessage(),
            'merchant_order_id' => $merchantOrderId,
            'trace' => $e->getTraceAsString()
        ]);
        return "Failed to update payment status. Please contact support.";
    }

    // SECURITY: Re-authenticate user if they're logged out (session might be lost after external redirect)
    if (!Auth::check() && $payment->user_id) {
        $user = User::find($payment->user_id);
        if ($user) {
            // Use loginUsingId to avoid password verification
            Auth::loginUsingId($user->id, true); // true = remember the user
            // SECURITY: Regenerate session to prevent fixation attacks
            $request->session()->regenerate();
            
            Log::info("✅ Re-authenticated user after payment return", [
                'user_id' => $user->id,
                'merchant_order_id' => $merchantOrderId,
                'session_id' => $request->session()->getId()
            ]);
        } else {
            Log::error("❌ User not found for payment", [
                'user_id' => $payment->user_id,
                'merchant_order_id' => $merchantOrderId
            ]);
        }
    } else if (Auth::check()) {
        // SECURITY: Regenerate session after payment return to prevent session fixation
        $request->session()->regenerate();
        Log::info("✅ User already authenticated, session regenerated", [
            'user_id' => Auth::id(),
            'merchant_order_id' => $merchantOrderId
        ]);
    }

    // Store merchant_order_id in session for later use
    session(['merchant_order_id' => $merchantOrderId]);
    
    // Force save session
    $request->session()->save();

    Log::info("✅ Payment updated via RETURN page", [
        'merchant_order_id' => $merchantOrderId,
        'status' => $status,
        'user_authenticated' => Auth::check(),
        'user_id' => Auth::check() ? Auth::id() : null,
        'session_id' => $request->session()->getId()
    ]);

    // Get order for display
    $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();

    // Create response with view
    $response = response()->view("woohoo.processing-woohoo", [
        "amount" => $payment->amount ?? 0,
        "merchant_order_id" => $merchantOrderId ?? '',
        "status" => $status ?? 'Unknown',
        "payment_id" => $payment->id ?? 'N/A',
        "order_id" => $qsOrder->id ?? $payment->order_id ?? 'N/A'
    ]);
    
    // Ensure session cookie is set with proper attributes for cross-site redirects
    if (Auth::check()) {
        // Set cache control headers
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        // Note: Session cookie is automatically set by Laravel's session middleware
        // The same_site setting in config/session.php controls this behavior
        // We've set it to 'lax' which allows cookies on cross-site GET redirects
        
        Log::info("✅ Session cookie set in response", [
            'session_id' => $request->session()->getId(),
            'user_id' => Auth::id()
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
            if (!$payment && Auth::check()) {
                $payment = UnlimitPayment::where('user_id', Auth::id())
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if (!$payment) {
                return redirect()->route('my-order')
                    ->with('error', 'No payment found. Please contact support.');
            }

            // Re-authenticate user if logged out
            if (!Auth::check() && $payment->user_id) {
                $user = User::find($payment->user_id);
                if ($user) {
                    // Use loginUsingId to avoid password verification
                    Auth::loginUsingId($user->id, true); // true = remember the user
                    // Regenerate session to prevent fixation attacks
                    $request->session()->regenerate();
                    
                    Log::info("✅ Re-authenticated user in createOrder", [
                        'user_id' => $user->id,
                        'merchant_order_id' => $merchantOrderId
                    ]);
                }
            } else if (Auth::check()) {
                // User is already authenticated, just regenerate session
                $request->session()->regenerate();
            }
            
            // Force save session
            $request->session()->save();

            $merchantOrderId = $payment->merchant_order_id;
            $status = strtolower($payment->payment_status);

            Log::info("Processing Woohoo order creation", [
                'merchant_order_id' => $merchantOrderId,
                'status'            => $status,
            ]);

            // SECURITY: If status is still pending, verify with payment gateway API
            if (in_array($status, ['pending', 'unknown', ''])) {
                Log::info("🔄 Payment status is pending, verifying with payment gateway", [
                    'merchant_order_id' => $merchantOrderId
                ]);
                
                $verifiedStatus = $this->verifyPaymentStatusWithGateway($merchantOrderId);
                
                if ($verifiedStatus) {
                    $status = $verifiedStatus;
                    $payment->payment_status = $status;
                    $payment->save();
                    
                    Log::info("✅ Payment status verified and updated from gateway", [
                        'merchant_order_id' => $merchantOrderId,
                        'verified_status' => $status
                    ]);
                }
            }

            // 2️⃣ Fetch QsOrder
            $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)
                ->orderBy('id', 'ASC')
                ->first();

            if (!$qsOrder) {
                Log::error("QsOrder not found", ['merchant_order_id' => $merchantOrderId]);
                return redirect()->route('my-order')
                    ->with('error', 'Order not found. Please contact support.');
            }

            // 3️⃣ Check status - include all success variations
            $successStatuses = ['completed', 'approved', 'success', 'confirmed', 'paid'];
            if (!in_array($status, $successStatuses)) {
                Log::warning("⚠️ Payment status not successful", [
                    'merchant_order_id' => $merchantOrderId,
                    'status' => $status,
                    'payment_id' => $payment->id
                ]);
                return redirect()->route('my-order')
                    ->with('error', 'Payment not successful. Status: ' . ucfirst($status));
            }

            Log::info("🔍 Checking woohoo_order_id before API call", [
                "woohoo_order_id" => $qsOrder->woohoo_order_id,
            ]);

            // 4️⃣ Enrich order with billing details from Payment table (used by WoohooOrderController)
            // Get billing info from UnlimitPayment table, fallback to QsOrder if not available
            if ($payment) {
                $qsOrder->email        = $payment->billing_email ?? $qsOrder->sender_email ?? null;
                $qsOrder->mobile       = $payment->billing_tel ?? $qsOrder->sender_phone_no ?? null;
                $qsOrder->billing_name = $payment->billing_name ?? null;
            }

            Log::info("🔥 Sending Woohoo order request", [
                'qs_order_id'       => $qsOrder->id,
                'merchant_order_id' => $qsOrder->merchant_order_id,
                'amount'            => $qsOrder->amount,
                'email'             => $qsOrder->email,
                'mobile'            => $qsOrder->mobile,
            ]);

            // 5️⃣ Create Woohoo order via dedicated method that returns an array with 'success'
            $woohoo = new WoohooOrderController();
            $result = $woohoo->createWoohooOrderRequest($qsOrder, $payment);

            // Ensure we don't trigger "Undefined array key 'success'"
            $isSuccess = is_array($result) && array_key_exists('success', $result) && $result['success'] === true;

            return view('woohoo.response', [
                'order'    => $qsOrder,
                'woohoo'   => $result,
                'vouchers' => [],
                'isSuccess'=> $isSuccess,
            ]);
        } catch (\Throwable $e) {
            Log::error("Woohoo processing error", [
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
            if (!$merchantOrderId) {
            return redirect()->route('my-order')->with('error', 'Missing payment reference.');
        }

        $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

        if (!$payment) {
            return redirect()->route('my-order')->with('error', 'Payment record not found.');
        }

        return view('woohoo.processing-woohoo', [
            'order_id' => $payment->order_id,
            'status' => $payment->payment_status ?? 'Processing',
            'amount' => $payment->amount ?? 0,
            'merchant_order_id' => $merchantOrderId ?? $payment->merchant_order_id ?? '',
            'payment_id' => $payment->id ?? 'N/A'
        ]);
    }

    /**
     * SECURITY: Verify payment status with Unlimit API
     * This is a fallback if return URL doesn't update status properly
     */
    private function verifyPaymentStatusWithGateway($merchantOrderId)
    {
        try {
            // Get valid token
            $token = $this->getUnlimitToken();
            if (!$token) {
                Log::error('❌ Failed to get token for payment verification');
                return null;
            }

            // Call Unlimit API to check payment status
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->get("https://sandbox.in.unlimit.com/api/payments", [
                    'merchant_order_id' => $merchantOrderId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle array response (list of payments)
                if (isset($data['list']) && is_array($data['list']) && count($data['list']) > 0) {
                    $paymentData = $data['list'][0];
                    $rawStatus = $paymentData['payment_data']['status'] ?? null;
                } else {
                    $rawStatus = $data['payment_data']['status'] ?? null;
                }

                if ($rawStatus) {
                    $status = strtolower(trim($rawStatus));
                    
                    // Normalize status
                    $statusMap = [
                        'confirmed' => 'success',
                        'complete' => 'completed',
                        'approve' => 'approved',
                        'successful' => 'success',
                        'paid' => 'success',
                    ];
                    
                    if (isset($statusMap[$status])) {
                        $status = $statusMap[$status];
                    }
                    
                    Log::info("✅ Payment status verified from gateway", [
                        'merchant_order_id' => $merchantOrderId,
                        'raw_status' => $rawStatus,
                        'normalized_status' => $status
                    ]);
                    
                    return $status;
                }
            }

            Log::warning("⚠️ Could not verify payment status from gateway", [
                'merchant_order_id' => $merchantOrderId,
                'response_status' => $response->status()
            ]);

            return null;

        } catch (Exception $e) {
            Log::error("❌ Error verifying payment status with gateway", [
                'merchant_order_id' => $merchantOrderId,
                'error' => $e->getMessage()
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
            $response = Http::timeout(10)
                ->asForm()
                ->withHeaders([
                    'Authorization' => 'Basic ' . base64_encode(env('UNLIMIT_CODE')),
                ])
                ->post('https://sandbox.in.unlimit.com/api/auth/token', [
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
        } catch (Exception $e) {
            Log::error('❌ Failed to get Unlimit token for verification', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }


}
