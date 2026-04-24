<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single web entrypoint for initiating payments from the storefront.
 *
 * Phase 3: consolidates legacy per-method controllers while keeping URLs unchanged.
 */
final class PaymentSessionController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function unlimit(Request $request): RedirectResponse
    {
        $this->rejectUnexpectedFields($request, ['order_id']);

        $order = $this->resolveOrderForUser($request);
        $init = $this->payments->initiate($order, 'unlimit', $request->user(), [
            'payment_method' => 'bankcard',
            'method_category' => 'card',
        ]);

        return $this->redirectToGateway($init->success, $init->redirectUrl, $request);
    }

    public function upi(Request $request): RedirectResponse
    {
        $this->rejectUnexpectedFields($request, ['order_id']);

        $order = $this->resolveOrderForUser($request);
        $init = $this->payments->initiate($order, 'unlimit', $request->user(), [
            'payment_method' => 'upi',
            'method_category' => 'upi',
        ]);

        return $this->redirectToGateway($init->success, $init->redirectUrl, $request);
    }

    public function netbanking(Request $request): RedirectResponse
    {
        $this->rejectUnexpectedFields($request, ['order_id']);

        $order = $this->resolveOrderForUser($request);
        $init = $this->payments->initiate($order, 'unlimit', $request->user(), [
            'payment_method' => 'netbankinginr',
            'method_category' => 'netbanking',
        ]);

        return $this->redirectToGateway($init->success, $init->redirectUrl, $request);
    }

    public function razorpay(Request $request): RedirectResponse
    {
        $this->rejectUnexpectedFields($request, ['order_id']);

        $order = $this->resolveOrderForUser($request);
        $init = $this->payments->initiate($order, 'razorpay', $request->user());

        return $this->redirectToGateway($init->success, $init->redirectUrl, $request);
    }

    /**
     * Storefront Razorpay verification callback (client posts signature fields).
     * Returns to a stable success/failure page. This replaces legacy RazorpayPaymentController@verify.
     */
    public function razorpayVerify(Request $request): RedirectResponse
    {
        $this->rejectUnexpectedFields($request, ['razorpay_signature', 'razorpay_payment_id', 'razorpay_order_id']);

        $payload = $request->validate([
            'razorpay_signature' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
        ]);

        $result = $this->payments->handleGatewayCallback('razorpay', $payload);

        if (! $result->success) {
            return redirect()->route('payment.failed')->with('error', __('payments.payment_failed'));
        }

        return redirect()->route('payment.success')->with('success', __('payments.payment_completed_successfully'));
    }

    private function redirectToGateway(bool $success, ?string $redirectUrl, Request $request): RedirectResponse
    {
        if (! $success || ! $redirectUrl) {
            return redirect()->back()->with('error', __('payments.payment_initiation_failed_try_again'));
        }

        // Prevent open redirect: PaymentService controls the URL but we still enforce it is absolute.
        if (! str_starts_with($redirectUrl, 'http://') && ! str_starts_with($redirectUrl, 'https://')) {
            return redirect()->back()->with('error', __('payments.payment_initiation_failed_try_again'));
        }

        return redirect()->away($redirectUrl);
    }

    private function resolveOrderForUser(Request $request): Order
    {
        if (! Auth::check()) {
            abort(401);
        }

        $validated = $request->validate([
            'order_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $orderId = (int) ($validated['order_id'] ?? session('checkout_order_id', 0));
        if ($orderId <= 0) {
            abort(422, __('payments.checkout_session_expired'));
        }

        $order = Order::query()
            ->whereKey($orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            abort(403);
        }

        return $order;
    }

    /**
     * @param  list<string>  $allowedKeys
     */
    private function rejectUnexpectedFields(Request $request, array $allowedKeys): void
    {
        $allowed = array_fill_keys(array_merge($allowedKeys, ['_token']), true);
        $unexpected = array_values(array_diff($request->keys(), array_keys($allowed)));
        if ($unexpected !== []) {
            abort(422, 'Unexpected input fields: '.implode(', ', $unexpected));
        }
    }
}

