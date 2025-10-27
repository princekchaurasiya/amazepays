<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\QsOrder;
use App\Models\UnlimitPayment;

class UPIPaymentController extends Controller
{
    /**
     * Initiate Unlimit UPI payment and redirect customer to Unlimit Hosted Page
     */
    public function store(Request $request)
    {
        $payableAmount = $request->input('payable_amount');

        // Acquire access token
        $tokenResponse = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode(env('UNLIMIT_CODE')),
            ])
            ->post('https://sandbox.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => env('UNLIMIT_SECRET_KEY'),
                'terminal_code' => env('UNLIMIT_PUBLIC_KEY'),
            ]);

        $tokenJson = $tokenResponse->json();
        if (!isset($tokenJson['access_token'])) {
            Log::error('UPI: Token not received from Unlimit API', [
                'response' => $tokenJson,
                'status_code' => $tokenResponse->status(),
            ]);
            return back()->with('error', 'Payment service temporarily unavailable');
        }

        $accessToken = $tokenJson['access_token'];

        // Build Unlimit request (UPI)
        $orderId = (string) Str::uuid();
        $requestId = (string) Str::uuid();

        $payload = [
            'request' => [
                'id' => $requestId,
                'time' => now('UTC')->format('Y-m-d\\TH:i:s.v\\Z'),
            ],
            'merchant_order' => [
                'id' => $orderId,
                'description' => 'Gift Card Payment - ' . $orderId,
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => (float) $payableAmount,
                'currency' => 'INR',
            ],
            'return_urls' => [
                'success_url' => route('woohoo.processing'),
                'decline_url' => 'https://amazepay.toutle.in/',
            ],
        ];

        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post('https://sandbox.in.unlimit.com/api/payments', $payload);

            Log::info('UPI Payment Request', $payload);
            Log::info('UPI Payment Response', ['body' => $resp->body(), 'status' => $resp->status()]);

            $respJson = $resp->json();
            if (isset($respJson['redirect_url'])) {
                // Persist minimal ctx for callback fallback
                session([
                    'unlimit_ctx_upi' => [
                        'merchant_order_id' => $orderId,
                        'amount' => (float) $payableAmount,
                        'initiated_at' => now(),
                    ]
                ]);
                session()->save();

                // Optional: persist a payment row for traceability
                try {
                    $payment = new UnlimitPayment();
                    $payment->user_id = Auth::id();               // satisfy NOT NULL constraint
                    $payment->order_id = $orderId;               // UUID we generated
                    $payment->amount = (float) $payableAmount;    // existing column
                    $payment->currency = 'INR';                   // existing column
                    $payment->payment_mode = 'upi';               // existing column
                    $payment->order_status = 'pending';           // existing column
                    // Optionally stash redirect_url in a notes field for traceability
                    $payment->billing_notes = isset($respJson['redirect_url']) ? $respJson['redirect_url'] : null;
                    $payment->created_at = now();
                    $payment->save();
                } catch (\Throwable $e) {
                    Log::warning('UPI: Failed to persist payment row', ['error' => $e->getMessage()]);
                }

                return redirect()->away($respJson['redirect_url']);
            }

            Log::error('UPI: No redirect URL in response', $respJson ?? []);
            return back()->with('error', 'No redirect URL received from payment provider');
        } catch (\Throwable $e) {
            Log::error('UPI: Exception during initiate', ['error' => $e->getMessage()]);
            return back()->with('error', 'Unable to initiate payment');
        }
    }

    /**
     * Handle customer redirect back from Unlimit for UPI
     */
    public function handleReturn(Request $request)
    {
        $rawBody = $request->getContent();
        $jsonBody = [];
        try { $jsonBody = $rawBody ? json_decode($rawBody, true) ?: [] : []; } catch (\Throwable $e) { $jsonBody = []; }

        Log::info('UPI Return received', [
            'method' => $request->method(),
            'request_data' => $request->all(),
            'json_body' => $jsonBody,
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'raw' => $rawBody,
        ]);

        // Extract with aliases
        $paymentId = $request->input('payment_id') ?? ($jsonBody['payment_id'] ?? $jsonBody['paymentId'] ?? null);
        $merchantOrderId = $request->input('merchant_order_id') ?? ($jsonBody['merchant_order_id'] ?? $jsonBody['merchantOrderId'] ?? $jsonBody['order_id'] ?? null);
        $status = $request->input('status') ?? ($jsonBody['status'] ?? $jsonBody['paymentStatus'] ?? $jsonBody['result'] ?? null);

        $ctx = session('unlimit_ctx_upi', []);
        if (empty($merchantOrderId)) { $merchantOrderId = $ctx['merchant_order_id'] ?? null; }
        if (empty($paymentId)) { $paymentId = $ctx['payment_id'] ?? null; }

        if (empty($merchantOrderId)) { $merchantOrderId = (string) Str::uuid(); }
        if (empty($paymentId)) { $paymentId = \App\Helpers\CommonHelper::generateUniqueId('pay_'); }

        // Link to internal order if present
        $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
        if (!$qsOrder) {
            $sessionOrderId = session('session_qs_order_id');
            if (!empty($sessionOrderId)) { $qsOrder = QsOrder::find($sessionOrderId); }
        }

        // Persist session snapshot for downstream flow
        session(['payment_return_data' => [
            'payment_id' => $paymentId,
            'order_id' => $qsOrder?->id,
            'status' => $status,
            'return_time' => now(),
            'amount' => $request->input('amount') ?? ($jsonBody['amount'] ?? ($ctx['amount'] ?? $qsOrder->grand_payable_amount ?? null))
        ]]);
        session()->save();

        Log::info('UPI: payment_return_data stored', session('payment_return_data'));

        return view('woohoo.redirect-to-woohoo', [
            'payment_id' => $paymentId,
            'order_id' => $merchantOrderId,
            'status' => $status,
        ]);
    }

    /**
     * Webhook for UPI (optional)
     */
    public function webhook(Request $request)
    {
        Log::info('UPI webhook received', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);
        return response()->json(['status' => 'ok']);
    }
}

