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

        /**
         * ✅ Step 1: Link merchant_order_id to existing QsOrder before Unlimit API call
         */
        try {
            $sessionOrderId = session('session_qs_order_id');
            $checkoutData = session('checkout_data', []);
            if (!empty($sessionOrderId)) {
                $existingOrder = QsOrder::find($sessionOrderId);
                if ($existingOrder) {
                    $existingOrder->merchant_order_id = $orderId;
                    $existingOrder->grand_payable_amount = $existingOrder->grand_payable_amount
                        ?? $payableAmount
                        ?? ($checkoutData['grand_payable_amount'] ?? null);
                    $existingOrder->sku = $existingOrder->sku
                        ?? ($checkoutData['sku'] ?? ($checkoutData['product']['sku'] ?? null));
                    $existingOrder->denomination = $existingOrder->denomination
                        ?? (float) ($checkoutData['denomination'] ?? 0);
                    $existingOrder->quantity = $existingOrder->quantity
                        ?? (int) ($checkoutData['quantity'] ?? 1);
                    $existingOrder->save();

                    Log::info('✅ Linked merchant_order_id to QsOrder (UPI)', [
                        'qs_order_id' => $existingOrder->id,
                        'merchant_order_id' => $orderId,
                        'grand_payable_amount' => $existingOrder->grand_payable_amount,
                    ]);
                } else {
                    Log::warning('⚠️ session_qs_order_id found but QsOrder missing', [
                        'session_qs_order_id' => $sessionOrderId,
                    ]);
                }
            } else {
                Log::warning('⚠️ No session_qs_order_id found before UPI Unlimit call');
            }
        } catch (\Throwable $e) {
            Log::error('❌ Failed to link merchant_order_id before UPI API call', [
                'error' => $e->getMessage(),
            ]);
        }

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

                /**
                 * ✅ Step 2: Persist (or update) UnlimitPayment record linked to this QsOrder
                 */
                try {
                    $sessionOrderId = session('session_qs_order_id');
                    $existingOrder = $sessionOrderId ? QsOrder::find($sessionOrderId) : null;

                    $payment = UnlimitPayment::firstOrNew([
                        'order_id' => $existingOrder?->id,
                        'payment_mode' => 'upi',
                    ]);

                    $payment->user_id = Auth::id();
                    $payment->merchant_order_id = $orderId;
                    $payment->amount = (float) $payableAmount;
                    $payment->currency = 'INR';
                   $payment->payment_status = 'pending';
                    $payment->billing_notes = $respJson['redirect_url'] ?? null;
                    $payment->unlimit_response = json_encode($respJson);
                    $payment->save();

                    Log::info('✅ UPI Payment stored/updated', [
                        'payment_id' => $payment->id,
                        'order_id' => $existingOrder?->id,
                        'merchant_order_id' => $payment->merchant_order_id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Failed to store UPI payment info', ['error' => $e->getMessage()]);
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
    try {
        $jsonBody = $rawBody ? json_decode($rawBody, true) ?: [] : [];
    } catch (\Throwable $e) {
        $jsonBody = [];
    }

    Log::info('UPI Return received', [
        'method' => $request->method(),
        'request_data' => $request->all(),
        'json_body' => $jsonBody,
    ]);

    $paymentId = $request->input('payment_id')
        ?? ($jsonBody['payment_id'] ?? $jsonBody['paymentId'] ?? null);
    $merchantOrderId = $request->input('merchant_order_id')
        ?? ($jsonBody['merchant_order_id'] ?? $jsonBody['merchantOrderId'] ?? $jsonBody['order_id'] ?? null);
    $status = strtoupper($request->input('status')
        ?? ($jsonBody['status'] ?? $jsonBody['paymentStatus'] ?? $jsonBody['result'] ?? null));

    $ctx = session('unlimit_ctx_upi', []);
    if (empty($merchantOrderId)) $merchantOrderId = $ctx['merchant_order_id'] ?? (string) Str::uuid();
    if (empty($paymentId)) $paymentId = \App\Helpers\CommonHelper::generateUniqueId('pay_' . uniqid());

    $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first()
        ?? QsOrder::find(session('session_qs_order_id'));

    // Store payment return data in session
    session([
        'payment_return_data' => [
            'payment_id' => $paymentId,
            'order_id' => $qsOrder?->id,
            'status' => $status,
            'return_time' => now(),
            'amount' => $request->input('amount')
                ?? ($jsonBody['amount'] ?? ($ctx['amount'] ?? $qsOrder->grand_payable_amount ?? null)),
        ]
    ]);
    session()->save();

    // ✅ Update payment_status in DB
    if ($qsOrder) {
        $payment = UnlimitPayment::where('order_id', $qsOrder->id)->first();
        if ($payment) {
            $payment->payment_status = $status;
            $payment->updated_at = now();
            $payment->save();

            Log::info('✅ Updated UPI payment status', [
                'order_id' => $qsOrder->id,
                'merchant_order_id' => $merchantOrderId,
                'payment_status' => $status,
            ]);

            // ✅ Trigger Woohoo API only if payment COMPLETED
            if ($status === 'COMPLETED') {
                try {
                    $woohoo = new \App\Http\Controllers\WoohooOrderController();
                    $woohoo->createWoohooOrderRequest($qsOrder);

                    Log::info('🎉 Woohoo order triggered successfully after COMPLETED payment', [
                        'merchant_order_id' => $merchantOrderId,
                        'qs_order_id' => $qsOrder->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Failed to trigger Woohoo after COMPLETED payment', [
                        'error' => $e->getMessage(),
                        'merchant_order_id' => $merchantOrderId,
                    ]);
                }
            }
        }
    }

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

    $merchantOrderId = $request->input('merchant_order_id');
    $status = strtoupper($request->input('status'));
    
    if ($merchantOrderId && $status) {
        $qsOrder = QsOrder::where('merchant_order_id', $merchantOrderId)->first();
        if ($qsOrder) {
            $payment = UnlimitPayment::where('order_id', $qsOrder->id)->first();
            if ($payment) {
                $payment->payment_status = $status;
                $payment->updated_at = now();
                $payment->save();
            }

            if ($status === 'COMPLETED') {
                try {
                    $woohoo = new \App\Http\Controllers\WoohooOrderController();
                    $woohoo->createWoohooOrderRequest($qsOrder);
                    Log::info('🎉 Woohoo order triggered via webhook', [
                        'merchant_order_id' => $merchantOrderId,
                        'qs_order_id' => $qsOrder->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ Woohoo trigger failed via webhook', [
                        'error' => $e->getMessage(),
                        'merchant_order_id' => $merchantOrderId,
                    ]);
                }
            }
        }
    }

    return response()->json(['status' => 'ok']);
}

}
