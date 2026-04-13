<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderStatusMachine;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin panel -- Order management.
 */
class OrderController extends Controller
{
    public function __construct(
        private WalletService $walletService,
    ) {}

    /** List all orders with search + filters. */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'product', 'orderSummary'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $query->paginate(25),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    /** Show a single order. */
    public function show(Order $order)
    {
        return Inertia::render('Admin/Orders/Show', [
            'order' => $order->load(['user', 'product', 'supportTickets']),
        ]);
    }

    /** Cancel a pending/processing order and refund wallet if applicable. */
    public function cancel(Request $request, Order $order)
    {
        OrderStatusMachine::transition($order->status, 'cancelled');

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason', 'Cancelled by admin'),
        ]);

        if ($order->payment_method === 'wallet') {
            $this->walletService->credit(
                $order->user,
                $order->grand_total,
                "Refund for cancelled order #{$order->order_number}",
                "refund-{$order->id}",
                'order',
                $order->id,
            );
        }

        audit('order.cancelled', $order, ['status' => $order->getOriginal('status')], ['status' => 'cancelled']);

        return back()->with('success', "Order #{$order->order_number} has been cancelled.");
    }

    /** Initiate a refund for a fulfilled order. */
    public function refund(Request $request, Order $order)
    {
        OrderStatusMachine::transition($order->status, 'refund_requested');

        $order->update([
            'status' => 'refund_requested',
            'refund_reason' => $request->input('reason', 'Refund requested by admin'),
        ]);

        audit('order.refund_requested', $order);

        return back()->with('success', "Refund requested for order #{$order->order_number}.");
    }

    /** Approve a pending order for fulfillment. */
    public function approve(Order $order)
    {
        OrderStatusMachine::transition($order->status, 'processing');

        $order->update(['status' => 'processing']);

        audit('order.approved', $order);

        return back()->with('success', "Order #{$order->order_number} approved for processing.");
    }
}
