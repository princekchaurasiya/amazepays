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

class KGenPaymentController extends Controller
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

public function initiate(Request $request)
    {
        $orderId = $request->input('order_id');
        $payableAmount = $request->input('amount');
        $variantId = $request->input('variant_id');

        if (!$orderId || !$payableAmount || !$variantId) {
            return back()->withErrors(['error' => 'Missing order details']);
        }

        // Validate pending order exists
        $order = KGenOrder::where('id', $orderId)
            ->where('status', 'PENDING_PAYMENT')
            ->first();

        if (!$order) {
            return back()->withErrors(['error' => 'Invalid or already processed order']);
        }

        $token = $this->getToken();
        if (!$token) {
            return back()->withErrors(['error' => 'Payment service unavailable']);
        }

        $time = $this->generatePaymentTime();
        $merchantOrderId = 'KGEN_' . $orderId . '_' . Str::uuid();

        $data = [
            'request' => [
                'id' => (string) Str::uuid(),
                'time' => $time,
            ],
            'merchant_order' => [
                'id' => $merchantOrderId,
                'description' => "KGen Order #{$orderId} - {$variantId}",
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => (float) $payableAmount,
                'currency' => 'INR',
            ],
            'return_urls' => [
                'success_url' => route('kgen.payment.success', ['orderId' => $orderId]),
                'decline_url' => route('kgen.payment.failed', ['orderId' => $orderId])
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post('https://psp.in.unlimit.com/api/payments', $data);

            Log::info('KGen Payment initiated', [
                'order_id' => $orderId,
                'amount' => $payableAmount,
                'response_status' => $response->status()
            ]);

            $responseData = $response->json();

            if (isset($responseData['redirect_url'])) {
                // Store payment info
                UnlimitPayment::create([
                    'order_id' => $orderId,
                    'payment_id' => $responseData['payment_id'] ?? null,
                    'merchant_order_id' => $merchantOrderId,
                    'amount' => $payableAmount,
                    'status' => 'INITIATED',
                    'payment_data' => json_encode($responseData)
                ]);

                return redirect()->away($responseData['redirect_url']);
            }

            Log::error('No redirect URL', ['response' => $responseData]);
            return back()->withErrors(['error' => 'Payment initiation failed']);

        } catch (\Exception $e) {
            Log::error('Payment initiation error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Payment service temporarily unavailable']);
        }
    }

public function handleReturnSuccess(Request $request, $orderId)
    {
        Log::info('Payment success callback', [
            'order_id' => $orderId,
            'request_data' => $request->all()
        ]);

        $paymentId = $request->input('payment_id');
        $status = $request->input('status');

        // Update payment and order status
        $this->updatePaymentStatus($paymentId, $orderId, $status ?? 'SUCCESS');

        // Redirect to order confirmation
        return redirect()->route('kgen.order.success', ['orderId' => $orderId])
            ->with('success', 'Payment successful! Order confirmed.');
    }

    public function handleReturnFailed(Request $request, $orderId)
    {
        Log::info('Payment failed callback', [
            'order_id' => $orderId,
            'request_data' => $request->all()
        ]);

        $this->updatePaymentStatus($request->input('payment_id'), $orderId, 'FAILED');

        return redirect()->route('kgen.order.failed', ['orderId' => $orderId])
            ->withErrors(['error' => 'Payment failed. Please try again.']);
    }

    private function updatePaymentStatus($paymentId, $orderId, $status)
    {
        $order = KGenOrder::find($orderId);
        if ($order) {
            $order->update([
                'status' => $status === 'SUCCESS' ? 'PAID' : 'PAYMENT_FAILED',
                'payment_status' => $status
            ]);

            // Trigger actual order placement if payment successful
            if ($status === 'SUCCESS') {
                $this->processPaidOrder($order);
            }
        }

        UnlimitPayment::where('order_id', $orderId)
            ->update(['status' => $status]);
    }

    private function processPaidOrder(KGenOrder $order)
    {
        // Now call the original order placement logic
        // This would be your existing placeExternalOrder logic
        $dpId = env('DP_ID');
        $externalRefId = 'ORDER_' . strtoupper(Str::random(6));

        $response = $this->placeExternalOrder($dpId, $order->variant_id, $externalRefId);
        $data = $response->json();

        $order->update([
            'external_ref' => $externalRefId,
            'api_response' => json_encode($data),
            'status' => $data['status'] ?? 'PROCESSED'
        ]);
    }

}
