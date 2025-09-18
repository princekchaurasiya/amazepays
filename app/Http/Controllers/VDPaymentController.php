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

    // Generate current time with milliseconds and Z suffix in UTC
    $now = Carbon::now('UTC');
    $milliseconds = $now->format('v'); // milliseconds
    $time = $now->format("Y-m-d\TH:i:s.") . $milliseconds . "Z";
    $payableAmount = $request->input('payable_amount');
    
    // Generate unique order ID
    $orderId = (string) Str::uuid();
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
            'success_url' => "https://amazepays.in/evc-details?order_id=" . $orderId . "&request_ref_no=" . $request_ref_no,
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
