<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UnlimitPayment;
use App\Models\QsOrder;
use Exception;

class UnlimitPaymentController extends Controller
{
<<<<<<< HEAD
=======
    public function showPaymentForm(Request $request,$slug)
    {
        $qsProd = QsProduct::where('url', $slug)->firstOrFail();
        $qsProd['prodData'] = $request->all();
        $qsProd['currency'] = json_decode($qsProd['currency']);
        $qsProd['images'] = json_decode($qsProd->images);
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();

        return view('userpanel.checkout', ['qsProd' => $qsProd, 'qsOrder' => $qsOrder]);
    }


     public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode(env('UNLIMIT_CODE')),
            ])
            ->post('https://psp.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => env('UNLIMIT_SECRET_KEY'),
                'terminal_code' => env('UNLIMIT_PUBLIC_KEY'),
            ]);

        $data = $response->json();
        //dd($data);

    if (isset($data['access_token'])) {
        ApiToken::create([
            'access_token' => $data['access_token'],
            'expires_at' => isset($data['expires_in']) 
                ? Carbon::now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        return $data['access_token'];
    }

    // Log the error for debugging but return false instead of JSON response
    Log::error('Token not received from Unlimit API', [
        'response' => $data,
        'status_code' => $response->status()
    ]);
    
    return false;
}
    public function store(Request $request)
    {
        //$discount = session('discounted_amount_value');
        //return view('unlimitpayment', ['discount' => $discount]);
        // Validate input
      /*$validated = $request->validate([
        'amount' => 'required|numeric|min:0.01',
        'card_number' => 'required|string|digits_between:13,19',
        'holder' => 'required|string|max:255',
        'expiration' => 'required|string',
        'cvv' => 'required|digits_between:3,4',
    ]);*/

    // 2. Get Unlimit token (internal call)
    $token = $this->getToken();

    if (!$token) {
        // Return user-friendly error message instead of technical error
        return redirect()->back()->with('error', 'Payment service is temporarily unavailable. Please try again later.');
    }

    // Generate current time with milliseconds and Z suffix in UTC
    $now = Carbon::now('UTC');
    $milliseconds = $now->format('v'); // milliseconds
    $time = $now->format("Y-m-d\TH:i:s.") . $milliseconds . "Z";
    $payableAmount = $request->input('payable_amount');
    
    // Generate unique order ID
    $orderId = (string) Str::uuid();

    $data = [
        'request' => [
            'id' => (string) Str::uuid(),
            'time' => $time,
        ],
        'merchant_order' => [
            'id' => $orderId,
            'description' => "Gift Card Payment - " . $orderId,
        ],
        'payment_method' => 'bankcard',
        'payment_data' => [ 
            'amount' => $payableAmount,
            'currency' => 'INR',
        ],

        'return_urls' => [
            'success_url' => route('unlimit.return'),
            'decline_url' => route('unlimit.return'),
        ],
        // Note: card_account.card was removed based on your earlier error for Payment Page mode
    ];

          /*$token = ApiToken::latest()->first();

    if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
        return response()->json(['error' => 'Token expired or missing.'], 401);
    }*/

   try {
   $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
   //'Accept' => 'application/json',
    'Content-Type' => 'application/json',
])
->post('https://psp.in.unlimit.com/api/payments', $data);

Log::info('Payment Request', $data);
Log::info('Payment Response', ['body' => $response->body(), 'status' => $response->status()]);

    //  return $response->json();
    $responseData = $response->json();

    if (isset($responseData['redirect_url'])) {
        // Store payment information before redirecting
        $this->storePaymentInfo($request, $orderId, $responseData);
        return redirect()->away($responseData['redirect_url']);
    }

    // If no redirect URL, handle the response accordingly
    Log::error('No redirect URL in response', $responseData);
    return response()->json(['error' => 'No redirect URL received', 'response' => $responseData], 400);

} catch (RequestException $e) {

    if (str_contains($e->getMessage(), 'cURL error 35')) {
        Log::error('cURL error 35: Send failure: Connection was aborted');

        return response()->json([
            'error' => 'Connection aborted',
            'message' => 'The connection to the payment provider was unexpectedly closed. Please try again shortly.',
            'code' => 35
        ], 503);
    }

    return response()->json([
        'error' => 'HTTP request failed',
        'message' => $e->getMessage(),
        'response' => $e->response?->body(),
    ], 500);
}        
    }

    public function handleReturnSuccess(Request $request)
    {
        // Log the return request for debugging
        Log::info('Payment return received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        // Get payment information from the request parameters
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');

        // Update payment status if we have payment information
        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
        }

        // Store return data in session for the redirect-to-woohoo blade
        $orderId = (string) Str::uuid(); // Unlimit's UUID

        // Create QsOrder linked to that UUID
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();
        $qsOrder->grand_payable_amount = $request->input('payable_amount');
        // ... set other order fields here ...
        $qsOrder->merchant_order_id = $orderId; // Link the UUID
        $qsOrder->save();
        //Then store this internal ID in the session when the user returns
        // Inside handleReturnSuccess()
        $qsOrder = QsOrder::where('merchant_order_id', $orderId)->first();

        if ($qsOrder) {
    session([
        'payment_return_data' => [
            'payment_id' => $paymentId,
            'order_id' => $qsOrder->id,  // ✅ use internal order ID now
            'status' => $status,
            'return_time' => now()
        ]
    ]);
} else {
    Log::error("No matching QsOrder found for merchant_order_id: " . $orderId);
}


        // Return the redirect-to-woohoo view
        return view('woohoo.redirect-to-woohoo', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => $status
        ]);

    }

>>>>>>> aac288f (change 1)
    /**
     * 🔹 Webhook from Unlimit (server-to-server)
     */
    public function webhook(Request $request)
    {
        Log::info('🟢 Unlimit webhook received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $paymentId = $request->input('payment_id');
            $orderId = $request->input('merchant_order_id');
            $status   = strtolower($request->input('status', ''));
            $amount   = $request->input('amount');

            if (!$paymentId || !$orderId) {
                Log::warning('⚠️ Webhook missing payment_id or order_id', [
                    'payment_id' => $paymentId,
                    'order_id' => $orderId
                ]);
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Update or insert payment info
            $this->updatePaymentStatus($paymentId, $orderId, $status, $amount);

            // Optionally, trigger Woohoo order automatically
            if (in_array($status, ['success', 'approved', 'completed'])) {
                Log::info('✅ Payment successful, triggering Woohoo order creation', [
                    'merchant_order_id' => $orderId,
                    'payment_id' => $paymentId
                ]);

                try {
                    $woohooProcessor = new \App\Http\Controllers\WoohooProcessingController();
                    $woohooProcessor->createOrderFromWebhook($orderId, $paymentId);
                } catch (Exception $ex) {
                    Log::error('❌ Woohoo order creation via webhook failed', [
                        'merchant_order_id' => $orderId,
                        'error' => $ex->getMessage()
                    ]);
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            Log::error('❌ Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * 🔹 Update payment status when webhook/return is received
     */
    private function updatePaymentStatus($paymentId, $orderId, $status, $amount = null)
    {
        try {
            $payment = UnlimitPayment::where('merchant_order_id', $orderId)
                ->orWhere('order_id', $orderId)
                ->first();

            if (!$payment) {
                Log::info('🆕 Creating new payment record via webhook', [
                    'merchant_order_id' => $orderId,
                    'status' => $status
                ]);

                $payment = new UnlimitPayment();
                $payment->merchant_order_id = $orderId;
                $payment->tracking_id = $paymentId;
                $payment->amount = $amount;
                $payment->currency = 'INR';
                $payment->payment_method = 'bankcard';
                $payment->created_at = now();
            }

            $normalizedStatus = strtolower($status);

            $payment->payment_status = $normalizedStatus;
            $payment->order_status = $normalizedStatus;
            $payment->status_message = 'Updated by Unlimit webhook';
            $payment->updated_at = now();
            $payment->save();

            Log::info('💾 Payment status updated', [
                'merchant_order_id' => $orderId,
                'status' => $normalizedStatus,
                'payment_id' => $payment->id
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to update payment status', [
                'merchant_order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔹 Optional manual processing after user redirect
     */
    public function process(Request $request)
    {
        $payableAmount = $request->input('payable_amount');
        return view('payment.success', ['amount' => $payableAmount]);
    }

    /**
     * 🔹 Helper to store initial payment info before redirecting
     * SECURITY: Validates amount against order database
     */
    private function storePaymentInfo(Request $request, $orderId, $responseData)
    {
        try {
            // SECURITY: If order_id is numeric, validate amount against order
            $amount = null;
            if (is_numeric($orderId)) {
                $order = QsOrder::where('id', $orderId)->first();
                if ($order) {
                    // Use amount from database, not request
                    $amount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);
                }
            }
            
            // Fallback to request amount if order not found (for UUID-based orders)
            if ($amount === null) {
                $amount = (float) ($request->input('payable_amount') ?? 0);
            }
            
            $payment = new UnlimitPayment();
            $payment->order_id = $orderId;
            $payment->merchant_order_id = $responseData['merchant_order_id'] ?? $orderId;
            $payment->amount = $amount; // Use validated amount
            $payment->currency = 'INR';
            $payment->payment_method = 'bankcard';
            $payment->payment_status = 'pending';
            $payment->unlimit_response = json_encode($responseData);
            $payment->created_at = now();
            $payment->save();

            Log::info('💾 Payment information stored', [
                'order_id' => $orderId,
                'amount' => $amount,
                'payment_id' => $payment->id
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to store payment information', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔹 Fetch Woohoo order data for order creation
     */
    public static function getWoohooOrderData($orderId)
{
    $payment = UnlimitPayment::where('order_id', $orderId)->first();
    $qsOrder = QsOrder::find($orderId);

    if (!$payment || !$qsOrder) {
        Log::error('Unable to fetch Woohoo order data', [
            'order_id' => $orderId,
            'payment_found' => (bool) $payment,
            'order_found' => (bool) $qsOrder,
        ]);
        return null;
    }

    // ✅ Always use QsOrder total amount, not UnlimitPayment’s
    return [
        'amount' => $qsOrder->price ?? $payment->amount,
        'currency' => $qsOrder->currency ?? 'INR',
        'sku' => $qsOrder->sku ?? null,
        'qty' => $qsOrder->quantity ?? 1,
    ];
}
}
