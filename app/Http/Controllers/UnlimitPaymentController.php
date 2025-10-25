<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnlimitPayment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\ApiToken;
use App\Models\QsProduct;
use App\Models\QsOrder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnlimitPaymentController extends Controller
{
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
            ->post('https://sandbox.in.unlimit.com/api/auth/token', [
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
->post('https://sandbox.in.unlimit.com/api/payments', $data);

Log::info('Payment Request', $data);
Log::info('Payment Response', ['body' => $response->body(), 'status' => $response->status()]);

    // Link merchant_order_id to an existing QsOrder from session, and hydrate fields
    try {
        $sessionOrderId = session('session_qs_order_id');
        $checkoutData = session('checkout_data', []);
        if (!empty($sessionOrderId)) {
            $existingOrder = QsOrder::find($sessionOrderId);
            if ($existingOrder) {
                $existingOrder->merchant_order_id = $orderId;
                // Fill sku/denomination/quantity if missing
                if (empty($existingOrder->sku)) {
                    $existingOrder->sku = $checkoutData['sku']
                        ?? ($checkoutData['product']['sku'] ?? null)
                        ?? ($checkoutData['qsProd']['sku'] ?? null);
                }
                if (empty($existingOrder->denomination) && isset($checkoutData['denomination'])) {
                    $existingOrder->denomination = (float) $checkoutData['denomination'];
                }
                if (empty($existingOrder->quantity) && isset($checkoutData['quantity'])) {
                    $existingOrder->quantity = (int) $checkoutData['quantity'];
                }
                // Prefer provided payable amount; otherwise compute
                $computedAmount = ($existingOrder->denomination && $existingOrder->quantity)
                    ? (float) ($existingOrder->denomination * $existingOrder->quantity)
                    : null;
                if (empty($existingOrder->grand_payable_amount)) {
                    $existingOrder->grand_payable_amount = $payableAmount ?? ($checkoutData['grand_payable_amount'] ?? $computedAmount);
                }
                $existingOrder->save();
                Log::info('Linked merchant_order_id to existing QsOrder', [
                    'qs_order_id' => $existingOrder->id,
                    'merchant_order_id' => $orderId,
                    'sku' => $existingOrder->sku,
                    'denomination' => $existingOrder->denomination,
                    'quantity' => $existingOrder->quantity,
                    'grand_payable_amount' => $existingOrder->grand_payable_amount,
                ]);
            }
        }
    } catch (\Throwable $e) {
        Log::warning('Failed to link merchant_order_id to existing order', ['error' => $e->getMessage()]);
    }

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
            'headers' => $request->headers->all(),
            'query' => $request->query()
        ]);

        // Get payment information from the request parameters (with robust fallbacks)
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');

        // Ensure we always have non-null IDs
        if (empty($orderId)) {
            $orderId = (string) Str::uuid();
            Log::warning('merchant_order_id missing in return; generated a UUID fallback', ['generated_order_id' => $orderId]);
        }
        if (empty($paymentId)) {
            $paymentId = \App\Helpers\CommonHelper::generateUniqueId('pay_');
            Log::warning('payment_id missing in return; generated a unique fallback', ['generated_payment_id' => $paymentId]);
        }

        // Update payment status if we have payment information
        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
        }

        // Store return data in session for the redirect-to-woohoo blade
        // Ensure $orderId has value even if request param is missing
        $orderId = $orderId ?: (string) Str::uuid(); // Unlimit's UUID or generated fallback

        // Prefer linking to an existing order created earlier in the flow
        $linkedOrder = QsOrder::where('merchant_order_id', $orderId)->first();
        if (!$linkedOrder) {
            $sessionOrderId = session('session_qs_order_id');
            if (!empty($sessionOrderId)) {
                $linkedOrder = QsOrder::find($sessionOrderId);
            }
        }

        if ($linkedOrder) {
            // Update missing fields on the existing order
            $checkoutData = session('checkout_data', []);
            if (empty($linkedOrder->merchant_order_id)) {
                $linkedOrder->merchant_order_id = $orderId;
            }
            if (empty($linkedOrder->sku)) {
                $linkedOrder->sku = $checkoutData['sku']
                    ?? ($checkoutData['product']['sku'] ?? null)
                    ?? ($checkoutData['qsProd']['sku'] ?? null);
            }
            if (empty($linkedOrder->denomination) && isset($checkoutData['denomination'])) {
                $linkedOrder->denomination = (float) $checkoutData['denomination'];
            }
            if (empty($linkedOrder->quantity) && isset($checkoutData['quantity'])) {
                $linkedOrder->quantity = (int) $checkoutData['quantity'];
            }
            $computedAmount = ($linkedOrder->denomination && $linkedOrder->quantity)
                ? (float) ($linkedOrder->denomination * $linkedOrder->quantity)
                : null;
            if (empty($linkedOrder->grand_payable_amount)) {
                $linkedOrder->grand_payable_amount = $request->input('payable_amount')
                    ?? ($checkoutData['grand_payable_amount'] ?? $computedAmount);
            }
            if (empty($linkedOrder->order_status)) {
                $linkedOrder->order_status = 'Pending';
            }
            $linkedOrder->save();

            Log::info('Updated existing QsOrder on return', [
                'order_id' => $linkedOrder->id,
                'merchant_order_id' => $linkedOrder->merchant_order_id,
                'sku' => $linkedOrder->sku,
                'denomination' => $linkedOrder->denomination,
                'quantity' => $linkedOrder->quantity,
                'grand_payable_amount' => $linkedOrder->grand_payable_amount,
            ]);
        } else {
            // Create a new order only as a last resort
            $checkoutData = session('checkout_data', []);
            $qsOrder = new QsOrder();
            $qsOrder->user_id = Auth::id();
            $qsOrder->sku = $checkoutData['sku']
                ?? ($checkoutData['product']['sku'] ?? null)
                ?? ($checkoutData['qsProd']['sku'] ?? null);
            $qsOrder->denomination = isset($checkoutData['denomination']) ? (float) $checkoutData['denomination'] : null;
            $qsOrder->quantity = isset($checkoutData['quantity']) ? (int) $checkoutData['quantity'] : 1;
            $computedAmount = ($qsOrder->denomination && $qsOrder->quantity)
                ? (float) ($qsOrder->denomination * $qsOrder->quantity)
                : null;
            $qsOrder->grand_payable_amount = $request->input('payable_amount')
                ?? ($checkoutData['grand_payable_amount'] ?? $computedAmount);
            $qsOrder->order_status = 'Pending';
            $qsOrder->merchant_order_id = $orderId;
            $qsOrder->save();

            Log::info('Created QsOrder from return + session checkout_data', [
                'order_id' => $qsOrder->id,
                'sku' => $qsOrder->sku,
                'denomination' => $qsOrder->denomination,
                'quantity' => $qsOrder->quantity,
                'grand_payable_amount' => $qsOrder->grand_payable_amount,
            ]);
        }
        //Then store this internal ID in the session when the user returns
        // Inside handleReturnSuccess()
        $qsOrder = QsOrder::where('merchant_order_id', $orderId)->first();

        if ($qsOrder) {
            // Store payment return data in session with proper persistence
            session([
                'payment_return_data' => [
                    'payment_id' => $paymentId,
                    'order_id' => $qsOrder->id,  // ✅ use internal order ID now
                    'status' => $status,
                    'return_time' => now(),
                    'amount' => $request->input('amount')
                ]
            ]);
            
            // Ensure session is saved immediately
            session()->save();
            
            Log::info('Payment return data stored in session', [
                'order_id' => $qsOrder->id,
                'payment_id' => $paymentId,
                'status' => $status
            ]);
        } else {
            Log::error("No matching QsOrder found for merchant_order_id: " . $orderId);
        }

        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
            
            // If payment is successful, you might want to trigger order creation
            if ($status === 'success' || $status === 'approved') {
                Log::info('Payment successful, ready for order creation', [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId
                ]);
            }
        }

        // Return the redirect-to-woohoo view
        return view('woohoo.redirect-to-woohoo', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => $status
        ]);

    }

    /**
     * Handle webhook notifications from Unlimit
     */
    public function webhook(Request $request)
    {
        Log::info('Unlimit webhook received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        // Verify webhook signature if Unlimit provides one
        // For now, we'll process the webhook data
        
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');
        $amount = $request->input('amount');

        if ($status === 'success' || $status === 'approved') {
    Log::info('Payment successful, ready for order creation', [
        'order_id' => $orderId,
        'payment_id' => $paymentId
    ]);

    try {
        $qsOrder = QsOrder::where('id', $orderId)->first();
        if ($qsOrder) {
            $woohooController = new \App\Http\Controllers\WoohooOrderController();
            $woohooController->createWoohooOrderRequest($qsOrder);

            Log::info('Woohoo order created via webhook', ['order_id' => $orderId]);
        } else {
            Log::warning('No matching QsOrder found for webhook order_id', ['order_id' => $orderId]);
        }
    } catch (\Exception $e) {
        Log::error('Woohoo order creation failed in webhook', [
            'order_id' => $orderId,
            'error' => $e->getMessage(),
        ]);
    }
}


        return response()->json(['status' => 'success'], 200);
    }

public function process(Request $request)
    {
        // Get the payable amount from request
        $payableAmount = $request->input('payable_amount');

        // Debug or use the amount
        // dd($payableAmount);

        // Proceed with your custom logic (e.g., saving to DB, initiating a new payment method, etc.)
        
        return view('payment.success', ['amount' => $payableAmount]);
    }

    /**
     * Store payment information in the database
     */
    private function storePaymentInfo(Request $request, $orderId, $responseData)
    {
        try {
            $payment = new UnlimitPayment();
            $payment->order_id = $orderId;
            $payment->amount = $request->input('payable_amount');
            $payment->currency = 'INR';
            $payment->payment_method = 'bankcard';
            $payment->status = 'pending';
            $payment->unlimit_response = json_encode($responseData);
            $payment->created_at = now();
            $payment->save();

            Log::info('Payment information stored', [
                'order_id' => $orderId,
                'amount' => $request->input('payable_amount'),
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store payment information', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update payment status when return is received
     */
    private function updatePaymentStatus($paymentId, $orderId, $status)
    {
        try {
            $payment = UnlimitPayment::where('order_id', $orderId)->first();
            
            if ($payment) {
                $payment->status = $status;
                $payment->updated_at = now();
                $payment->save();

                Log::info('Payment status updated', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'payment_id' => $payment->id
                ]);
            } else {
                Log::warning('Payment not found for order', ['order_id' => $orderId]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update payment status', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public static function getWoohooOrderData($orderId)
{
    $payment = UnlimitPayment::where('order_id', $orderId)->first();
    $qsOrder = QsOrder::find($orderId);

    if (!$payment || !$qsOrder) {
        Log::error('Unable to fetch Woohoo order data', [
            'order_id' => $orderId,
            'payment_found' => $payment ? true : false,
            'order_found' => $qsOrder ? true : false,
        ]);
        return null;
    }

    return [
        'amount' => $payment->amount,
        'currency' => $payment->currency ?? 'INR',
        'sku' => $qsOrder->sku ?? null,
        'qty' => $qsOrder->quantity ?? 1,
    ];
}
}

