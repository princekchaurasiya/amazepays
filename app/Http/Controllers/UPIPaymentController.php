<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnlimitPayment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\ApiToken;
use App\Models\Billing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\QsOrder;

class UPIPaymentController extends Controller
{


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
    Log::error('Token not received from Unlimit API (UPI)', [
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
    $merchantOrderId = (string) Str::uuid();

    $data = [
        'request' => [
            'id' => (string) Str::uuid(),
            'time' => $time,
        ],
        'merchant_order' => [
            'id' => $merchantOrderId,
            'description' => "Unlimit transaction",
        ],
        'payment_method' => 'upi',
        'payment_data' => [ 
            'amount' => $payableAmount,
            'currency' => 'INR',
        ],

        'return_urls' => [
        'success_url' => 'http://amazepays.in/unlimit/return?merchant_order_id={merchant_order_id}&status={status}',
        'decline_url' => 'https://amazepays.in/unlimit/return?merchant_order_id={merchant_order_id}&payment_id={payment_id}&status={status}',
        ],
        // Note: card_account.card was removed based on your earlier error for Payment Page mode
    ];

          /*$token = ApiToken::latest()->first();

    if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
        return response()->json(['error' => 'Token expired or missing.'], 401);
    }*/

 
$prod = session('selected_product');
 

 $existingOrderId = session('session_qs_order_id');

    if (!$existingOrderId) {
        return response()->json([
            'error' => 'No existing order found in session.'
        ], 400);
    }

    // Fetch the existing order
    $order = QsOrder::find($existingOrderId);

    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Update the order instead of creating new one
    $order->woohoo_order_id = 'WH-' . Str::upper(Str::random(6));
    $order->order_status = 'INITIATED';
    $order->merchant_order_id = $merchantOrderId;
    $order->save();


    /** UPDATE UnlimitPayment instead of create */
    $payment = UnlimitPayment::where('order_id', $existingOrderId)->first();

    if ($payment) {
        $payment->merchant_order_id = $merchantOrderId;
        $payment->amount = $payableAmount;
        $payment->payment_status = 'pending';
        $payment->save();
    }

   try {
   $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
   //'Accept' => 'application/json',
    'Content-Type' => 'application/json',
])
->post('https://sandbox.in.unlimit.com/api/payments', $data);

Log::info('Payment Request', $data);
Log::info('Payment Response', ['body' => $response->body(), 'status' => $response->status()]);

    //  return $response->json();
    $responseData = $response->json();

    if (isset($responseData['redirect_url'])) {
    return redirect()->away($responseData['redirect_url']);
        }


} catch (RequestException $e) {
    return response()->json([
        'error' => 'HTTP request failed',
        'message' => $e->getMessage(),
        'response' => $e->response?->body(),
    ], 500);
}        
    }

    public function handleReturnSuccess(Request $request)
{
    return redirect()->route('payment.success')->with('success', 'Payment completed successfully.');
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

}

