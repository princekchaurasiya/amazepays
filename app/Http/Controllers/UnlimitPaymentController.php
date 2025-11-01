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
    $token = $this->getToken();

    if (!$token) {
        return redirect()->back()->with('error', 'Payment service is temporarily unavailable. Please try again later.');
    }

    // Generate current time with milliseconds and Z suffix in UTC
    $now = Carbon::now('UTC');
    $milliseconds = $now->format('v');
    $time = $now->format("Y-m-d\TH:i:s.") . $milliseconds . "Z";
    $payableAmount = $request->input('payable_amount');

    // ✅ Generate unique Unlimit merchant order ID
    $orderId = (string) Str::uuid();

    /**
     * ✅ Step 1: Link merchant_order_id to QsOrder before making API call
     */
    try {
        $sessionOrderId = session('session_qs_order_id');
        $checkoutData = session('checkout_data', []);
        if (!empty($sessionOrderId)) {
            $existingOrder = QsOrder::find($sessionOrderId);
            if ($existingOrder) {
                // Link merchant_order_id so callbacks can find it later
                $existingOrder->merchant_order_id = $orderId;

                // Fill missing order details
                $existingOrder->sku = $existingOrder->sku 
                    ?? ($checkoutData['sku'] ?? ($checkoutData['product']['sku'] ?? null));
                $existingOrder->denomination = $existingOrder->denomination 
                    ?? (float) ($checkoutData['denomination'] ?? 0);
                $existingOrder->quantity = $existingOrder->quantity 
                    ?? (int) ($checkoutData['quantity'] ?? 1);
                $existingOrder->grand_payable_amount = $existingOrder->grand_payable_amount 
                    ?? ($payableAmount ?? ($checkoutData['grand_payable_amount'] ?? null));

                $existingOrder->save();

                Log::info('✅ Linked merchant_order_id to QsOrder before Unlimit API call', [
                    'qs_order_id' => $existingOrder->id,
                    'merchant_order_id' => $orderId,
                    'sku' => $existingOrder->sku,
                    'denomination' => $existingOrder->denomination,
                    'quantity' => $existingOrder->quantity,
                    'grand_payable_amount' => $existingOrder->grand_payable_amount,
                ]);
            } else {
                Log::warning('⚠️ session_qs_order_id found but QsOrder missing', [
                    'session_qs_order_id' => $sessionOrderId
                ]);
            }
        } else {
            Log::warning('⚠️ No session_qs_order_id found in store()');
        }
    } catch (\Throwable $e) {
        Log::error('❌ Failed to link merchant_order_id before API call', [
            'error' => $e->getMessage()
        ]);
    }

    /**
     * Step 2: Proceed with Unlimit payment request
     */
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
            'success_url' => route('woohoo.processing'),
            'decline_url' => 'https://amazepay.toutle.in/',
        ],
    ];

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://sandbox.in.unlimit.com/api/payments', $data);

        Log::info('Payment Request', $data);
        Log::info('Payment Response', ['body' => $response->body(), 'status' => $response->status()]);

        $responseData = $response->json();

        if (isset($responseData['redirect_url'])) {
            // ✅ Store payment info linked to correct QsOrder
            $this->storePaymentInfo($request, $orderId, $responseData);
            return redirect()->away($responseData['redirect_url']);
        }

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
           $merchantOrderId = Str::uuid(); 
           $qsOrder->merchant_order_id = $merchantOrderId;
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
                    'order_id' => $merchantOrderId, 
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

        // Return the processing-woohoo view
        return view('woohoo.processing-woohoo', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => $status,
            'amount' => $request->input('amount') ?? $linkedOrder->grand_payable_amount ?? 0
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
  private function storePaymentInfo(Request $request, $merchantOrderId, $responseData)
{
    try {
        $checkoutData = session('checkout_data', []);
        $sessionOrderId = session('session_qs_order_id');
        $existingOrder = $sessionOrderId ? QsOrder::find($sessionOrderId) : null;

        if (!$existingOrder) {
            Log::warning('No existing QsOrder found in session for payment store', [
                'session_qs_order_id' => $sessionOrderId
            ]);
        }

        // Try to find an existing payment record linked to this QsOrder
        $payment = UnlimitPayment::where('order_id', $existingOrder->id ?? null)->first();

        if (!$payment) {
            $payment = new UnlimitPayment();
            $payment->order_id = $existingOrder->id ?? null; // ✅ internal order link
            $payment->user_id = auth()->id();
        }

        // ✅ Always set the Unlimit merchant_order_id separately
        $payment->merchant_order_id = $merchantOrderId;

        $payment->amount = $request->input('payable_amount')
            ?? ($checkoutData['grand_payable_amount'] ?? 0);
        $payment->currency = 'INR';
        $payment->payment_mode = $checkoutData['payment_mode'] ?? 'upi';
        $payment->order_status = 'pending';
        $payment->billing_notes = $responseData['redirect_url'] ?? null;
        $payment->qty = $checkoutData['quantity'] ?? 1;
        $payment->price = $checkoutData['denomination'] ?? $request->input('payable_amount');
        $payment->sku = $checkoutData['sku'] ?? null;
        $payment->unlimit_response = json_encode($responseData);

        $payment->save();

        Log::info('✅ Payment information stored or updated', [
            'internal_order_id' => $payment->order_id,
            'merchant_order_id' => $payment->merchant_order_id,
            'payment_id' => $payment->id,
        ]);
    } catch (\Exception $e) {
        Log::error('❌ Failed to store payment information', [
            'merchant_order_id' => $merchantOrderId,
            'error' => $e->getMessage(),
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
               $payment->payment_status = $status;
                $payment->updated_at = now();
                $payment->save();

                Log::info('Payment status updated', [
                    'order_id' => $orderId,
                    'payment_status' => $status,
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

