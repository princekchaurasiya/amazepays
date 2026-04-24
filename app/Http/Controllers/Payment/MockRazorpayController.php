<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class MockRazorpayController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function pay(Request $request, string $merchantOrderId): mixed
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $order = Order::query()->where('order_number', $merchantOrderId)->first();
        abort_if(! $order, 404);

        return Inertia::render('Mock/RazorpayPay', [
            'merchantOrderId' => $merchantOrderId,
            'token' => (string) $request->query('token', ''),
            'amount' => (int) ($order->grand_total_minor ?? 0) / 100,
            'currency' => (string) ($order->currency ?? 'INR'),
            'callbackUrl' => route('mock.razorpay.callback', ['merchantOrderId' => $merchantOrderId]),
        ]);
    }

    /**
     * Initiate mock Razorpay from checkout session.
     */
    public function initiate(Request $request): mixed
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $checkoutOrderId = (int) session('checkout_order_id', 0);
        if ($checkoutOrderId <= 0) {
            return redirect()->back()->with('error', __('payments.checkout_session_expired'));
        }

        $order = Order::query()
            ->whereKey($checkoutOrderId)
            ->where('user_id', Auth::id())
            ->first();

        abort_if(! $order, 403);

        $init = $this->payments->initiate($order, 'mock_razorpay', $request->user());
        if (! $init->success || ! $init->redirectUrl) {
            return redirect()->back()->with('error', __('payments.payment_initiation_failed_try_again'));
        }

        return redirect()->away($init->redirectUrl);
    }

    public function callback(Request $request, string $merchantOrderId): mixed
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $payload = array_merge($request->only(['amount', 'currency', 'token', 'status', 'razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature']), [
            'merchant_order_id' => $merchantOrderId,
        ]);

        $result = $this->payments->handleGatewayCallback('mock_razorpay', $payload);

        return redirect()->route($result->success ? 'payment.success' : 'payment.failed', [
            'amount' => $request->input('amount'),
        ]);
    }
}

