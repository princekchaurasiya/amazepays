<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\KGenOrder;
use App\Models\UnlimitPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KGenPaymentController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode(env('UNLIMIT_CODE')),
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

        return response()->json(['error' => 'Token not received', 'response' => $data], 400);
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'variant_id' => 'required|string',
        ]);

        $orderId = $validated['order_id'];
        $payableAmount = $validated['amount'];
        $variantId = $validated['variant_id'];

        $token = $this->getToken();
        if (! $token || $token instanceof JsonResponse) {
            return back()->withErrors(['error' => 'Payment service unavailable']);
        }

        $time = $this->generatePaymentTime();
        $merchantOrderId = 'KGEN_'.$orderId.'_'.Str::uuid();

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
                'decline_url' => route('kgen.payment.failed', ['orderId' => $orderId]),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post('https://sandbox.in.unlimit.com/api/payments', $data);

            $responseData = $response->json();

            if (isset($responseData['redirect_url'])) {
                UnlimitPayment::create([
                    'order_id' => $orderId,
                    'payment_id' => $responseData['payment_id'] ?? null,
                    'merchant_order_id' => $merchantOrderId,
                    'amount' => $payableAmount,
                    'status' => 'INITIATED',
                    'payment_data' => json_encode($responseData),
                ]);

                return redirect()->away($responseData['redirect_url']);
            }

            return back()->withErrors(['error' => 'Payment initiation failed']);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payment service temporarily unavailable']);
        }
    }

    public function handleReturnSuccess(Request $request, $orderId)
    {
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');

        $this->updatePaymentStatus($paymentId, $orderId, $status ?? 'SUCCESS');

        return redirect()->route('kgen.order.success', ['orderId' => $orderId])
            ->with('success', 'Payment successful! Order confirmed.');
    }

    public function handleReturnFailed(Request $request, $orderId)
    {
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
                'payment_status' => $status,
            ]);
        }

        UnlimitPayment::where('order_id', $orderId)
            ->update(['payment_status' => $status]);
    }

    private function processPaidOrder(KGenOrder $order)
    {
        $dpId = env('DP_ID');
        $externalRefId = 'ORDER_'.strtoupper(Str::random(6));

        $response = $this->placeExternalOrder($dpId, $order->variant_id, $externalRefId);
        $data = $response->json();

        $order->update([
            'external_ref' => $externalRefId,
            'api_response' => json_encode($data),
            'status' => $data['status'] ?? 'PROCESSED',
        ]);
    }

    private function generatePaymentTime(): string
    {
        $now = Carbon::now('UTC');
        $milliseconds = $now->format('v');
        $time = $now->format("Y-m-d\TH:i:s.").$milliseconds.'Z';

        return $time;
    }

    private function placeExternalOrder(string $dpId, string $variantId, string $externalRefId)
    {
        return Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
            'Content-Type' => 'application/json',
        ])->post(env('EXLR8_BASE_URL').'/orders/b2b/direct-checkout', [
            'dpID' => $dpId,
            'variantID' => $variantId,
            'externalRefID' => $externalRefId,
        ]);
    }
}
