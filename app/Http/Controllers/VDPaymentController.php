<?php

namespace App\Http\Controllers;

use App\Helpers\IdGenerator;
use App\Models\ApiToken;
use App\Models\Order;
use App\Models\UnlimitPayment;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VDPaymentController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode(env('UNLIMIT_CODE')),
            ])
            ->post('https://psp.in.unlimit.com/api/auth/token', [
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

    public function store(Request $request)
    {
        $token = $this->getToken();

        if (! $token || $token instanceof JsonResponse) {
            return response()->json(['error' => 'Token not available'], 401);
        }

        $validated = $request->validate([
            'payable_amount' => 'required|numeric|min:0.01|max:999999',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $payableAmount = (float) $validated['payable_amount'];
        $orderId = null;

        if (! empty($validated['order_id'])) {
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', auth()->id())
                ->first();

            if ($order) {
                if ($order->amount_payable_after_discount !== null) {
                    $expectedAmount = (float) $order->amount_payable_after_discount;
                } elseif ($order->grand_payable_amount !== null) {
                    $expectedAmount = (float) $order->grand_payable_amount;
                } else {
                    $quantity = (int) ($order->quantity ?? 1);
                    $expectedAmount = (float) ($order->denomination ?? 0) * $quantity;
                }

                $amountDifference = abs($payableAmount - $expectedAmount);

                if ($amountDifference > 0.01) {
                    return back()->with('error', 'Payment amount mismatch. Please try again.');
                }

                $payableAmount = $expectedAmount;
                $orderId = (string) $order->id;
            } else {
                return back()->with('error', 'Order not found or unauthorized.');
            }
        }

        if (! $orderId) {
            $orderId = (string) Str::uuid();
        }

        $now = Carbon::now('UTC');
        $milliseconds = $now->format('v');
        $time = $now->format("Y-m-d\TH:i:s.").$milliseconds.'Z';
        $request_ref_no = (string) Str::uuid();

        $data = [
            'request' => [
                'id' => (string) Str::uuid(),
                'time' => $time,
            ],
            'merchant_order' => [
                'id' => $orderId,
                'description' => 'Gift Card Payment - '.$orderId,
            ],
            'payment_method' => 'upi',
            'payment_data' => [
                'amount' => $payableAmount,
                'currency' => 'INR',
            ],

            'return_urls' => [
                'success_url' => 'https://amazepays.in/evc-details/'.$orderId.'/'.$request_ref_no,
                'decline_url' => route('payment.failed'),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])
                ->post('https://psp.in.unlimit.com/api/payments', $data);

            $responseData = $response->json();

            if (isset($responseData['redirect_url'])) {
                $this->storePaymentInfo($orderId, $responseData, $payableAmount);

                return redirect()->away($responseData['redirect_url']);
            }

            return response()->json(['error' => 'No redirect URL received', 'response' => $responseData], 400);

        } catch (RequestException $e) {

            if (str_contains($e->getMessage(), 'cURL error 35')) {
                return response()->json([
                    'error' => 'Connection aborted',
                    'message' => 'The connection to the payment provider was unexpectedly closed. Please try again shortly.',
                    'code' => 35,
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
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');

        if (empty($orderId)) {
            $orderId = (string) Str::uuid();
        }
        if (empty($paymentId)) {
            $paymentId = IdGenerator::generateUniqueId('pay_');
        }

        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
        }

        session([
            'payment_return_data' => [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'status' => $status,
                'return_time' => now(),
            ],
        ]);

        session()->save();

        return Inertia::render('Checkout/Woohoo/AutoSubmit', [
            'submitUrl' => route('woohoo.createOrder'),
        ]);
    }

    public function process(Request $request)
    {
        $payableAmount = $request->input('payable_amount');

        return Inertia::render('Checkout/Status', [
            'status' => 'success',
            'msg' => 'Payment successful',
            'amount' => $payableAmount,
        ]);
    }

    private function storePaymentInfo($orderId, $responseData, float $amount)
    {
        try {
            $payment = new UnlimitPayment;
            $payment->order_id = $orderId;
            $payment->amount = $amount;
            $payment->currency = 'INR';
            $payment->payment_method = 'bankcard';
            $payment->status = 'pending';
            $payment->unlimit_response = json_encode($responseData);
            $payment->created_at = now();
            $payment->save();

        } catch (\Exception $e) {
            //
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
            }

        } catch (\Exception $e) {
            //
        }
    }
}
