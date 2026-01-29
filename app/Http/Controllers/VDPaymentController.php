<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnlimitPayment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\ApiToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\CommonHelper;

class VDPaymentController extends Controller
{
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

        //return response()->json(['message' => 'Token saved.']);
        return $data['access_token'];

    }

    return response()->json(['error' => 'Token not received', 'response' => $data], 400);
}

public function store(Request $request)
    {
         $token = $this->getToken();

    if (!$token) {
        return response()->json(['error' => 'Token not available'], 401);
    }

    // SECURITY: Validate payable_amount from request
    $request->validate([
        'payable_amount' => 'required|numeric|min:0.01|max:999999',
    ]);
    
    $payableAmount = (float) $request->input('payable_amount');
    $orderId = null;
    
    // SECURITY: If order_id is provided, validate amount against order
    if ($request->has('order_id') && $request->order_id) {
        $order = \App\Models\QsOrder::where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->first();
        
        if ($order) {
            // CRITICAL: Never fall back to 0 or denomination alone - calculate from denomination * quantity if needed
            if ($order->amount_payable_after_discount !== null) {
                $expectedAmount = (float) $order->amount_payable_after_discount;
            } elseif ($order->grand_payable_amount !== null) {
                $expectedAmount = (float) $order->grand_payable_amount;
            } else {
                // Fallback: calculate from denomination * quantity (never use denomination alone or 0)
                $quantity = (int) ($order->quantity ?? 1);
                $expectedAmount = (float) ($order->denomination ?? 0) * $quantity;
                
                Log::warning('⚠️ VD Payment: Using calculated amount from denomination * quantity', [
                    'order_id' => $order->id,
                    'denomination' => $order->denomination,
                    'quantity' => $quantity,
                    'calculated_amount' => $expectedAmount
                ]);
            }
            
            $amountDifference = abs($payableAmount - $expectedAmount);
            
            if ($amountDifference > 0.01) {
                Log::error('❌ VD Payment amount mismatch - potential tampering', [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'expected_amount' => $expectedAmount,
                    'received_amount' => $payableAmount,
                    'difference' => $amountDifference,
                    'ip_address' => $request->ip()
                ]);
                return back()->with('error', 'Payment amount mismatch. Please try again.');
            }
            
            // Use order amount from database (more secure)
            $payableAmount = $expectedAmount;
            $orderId = (string) $order->id;
        } else {
            Log::warning('VD Payment: Order not found or unauthorized', [
                'order_id' => $request->order_id,
                'user_id' => auth()->id(),
            ]);
            return back()->with('error', 'Order not found or unauthorized.');
        }
    }
    
    // Generate unique order ID if not set (EVC flow or new order)
    if (!$orderId) {
        $orderId = (string) Str::uuid();
    }
    
    // Generate current time with milliseconds and Z suffix in UTC
    $now = Carbon::now('UTC');
    $milliseconds = $now->format('v'); // milliseconds
    $time = $now->format("Y-m-d\TH:i:s.") . $milliseconds . "Z";
    $request_ref_no = (string) Str::uuid();

    $data = [
        'request' => [
            'id' => (string) Str::uuid(),
            'time' => $time,
        ],
        'merchant_order' => [
            'id' => $orderId,
            'description' => "Gift Card Payment - " . $orderId,
        ],
        'payment_method' => 'upi',
        'payment_data' => [ 
            'amount' => $payableAmount,
            'currency' => 'INR',
        ],

        'return_urls' => [
            'success_url' => "https://amazepays.in/evc-details/" . $orderId . "/" . $request_ref_no,
            'decline_url' => route('payment.failed')
        ],
         ];

          try {
   $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
   //'Accept' => 'application/json',
    'Content-Type' => 'application/json',
])
->post('https://psp.in.unlimit.com/api/payments', $data);

Log::info('Payment Request', $data);
Log::info('Payment Response', ['body' => $response->body(), 'status' => $response->status()]);
Log::info('Order ID', ['order_id' => $orderId]);

$responseData = $response->json();

    if (isset($responseData['redirect_url'])) {
        // Store payment information before redirecting
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
            'headers' => $request->headers->all()
        ]);

        // Get payment information from the request parameters
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');

        // Fallbacks to ensure non-null IDs
        if (empty($orderId)) {
            $orderId = (string) \Illuminate\Support\Str::uuid();
            Log::warning('VD: merchant_order_id missing in return; generated a UUID fallback', ['generated_order_id' => $orderId]);
        }
        if (empty($paymentId)) {
            $paymentId = CommonHelper::generateUniqueId('pay_');
            Log::warning('VD: payment_id missing in return; generated a unique fallback', ['generated_payment_id' => $paymentId]);
        }

        // Update payment status if we have payment information
        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
        }

        // Store return data in session for the redirect-to-woohoo blade
        session([
            'payment_return_data' => [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'status' => $status,
                'return_time' => now()
            ]
        ]);
        
        // Ensure session is saved immediately
        session()->save();
        
        Log::info('VD Payment return data stored in session', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'status' => $status
        ]);

        // Return the redirect-to-woohoo view
        return view('woohoo.redirect-to-woohoo', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => $status
        ]);
    }

    public function process(Request $request)
    {
        // Get the payable amount from request
        $payableAmount = $request->input('payable_amount');
        return view('payment.success', ['amount' => $payableAmount]);
    }

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
}
