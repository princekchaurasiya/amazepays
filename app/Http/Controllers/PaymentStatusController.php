<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentStatusController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->query('status', 'failure');
        if (! in_array($status, ['success', 'failure'], true)) {
            $status = 'failure';
        }
        // Prefer the processing page when checkout order is known.
        if ($status === 'success') {
            $orderId = (int) $request->query('order_id', session('checkout_order_id', 0));
            if ($orderId > 0 && auth()->check()) {
                $order = \App\Models\Order::query()
                    ->whereKey($orderId)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($order) {
                    $st = strtolower((string) ($order->status ?? ''));
                    $terminal = in_array($st, ['fulfilled', 'failed', 'cancelled', 'refunded'], true);
                    if (! $terminal) {
                        return redirect()->route('payment.processing', ['order_id' => $orderId]);
                    }
                }
            }
        }

        $msg = $status === 'success' ? 'Payment Successful' : 'Payment Failed';

        return Inertia::render('Checkout/Status', [
            'status' => $status,
            'msg' => $msg,
        ]);
    }
}
