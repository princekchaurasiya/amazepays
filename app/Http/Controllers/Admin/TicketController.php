<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('tickets.view');

        $query = SupportTicket::with(['user', 'order', 'assignedTo'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%")
                    ->orWhereHas('user', function ($u) use ($s) {
                        $u->where('mobile', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('name', 'like', "%{$s}%");
                    });
            });
        }

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'priority', 'category']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('tickets.create');

        $prefillUser = null;
        $prefillOrders = collect();
        $prefillOrderId = $request->integer('order_id') ?: null;

        if ($prefillOrderId && ! $request->filled('user_id')) {
            $order = Order::find($prefillOrderId);
            if ($order?->user_id) {
                $request->merge(['user_id' => $order->user_id]);
            }
        }

        if ($request->filled('user_id')) {
            $prefillUser = User::find($request->user_id);
            if ($prefillUser) {
                $prefillOrders = $prefillUser->orders()->latest()->limit(50)->get(['id', 'order_number', 'status']);
            }
        }

        return Inertia::render('Admin/Tickets/Create', [
            'prefillUser' => $prefillUser,
            'prefillOrders' => $prefillOrders,
            'prefillOrderId' => $prefillOrderId,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('tickets.create');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'category' => 'required|in:order_issue,refund,voucher_not_received,payment_issue,other',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        if (! empty($validated['order_id'])) {
            $order = Order::find($validated['order_id']);
            if ($order && (int) $order->user_id !== (int) $validated['user_id']) {
                return back()->withErrors(['order_id' => 'Order does not belong to the selected user.']);
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => $validated['user_id'],
            'order_id' => $validated['order_id'] ?? null,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        audit('ticket.created', $ticket, [], $validated);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket created.');
    }

    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('tickets.view');

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket->load(['user', 'order', 'assignedTo', 'resolvedBy', 'messages.user']),
            'staff' => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin', 'finance']))
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $this->authorize('tickets.update');

        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update($validated);

        audit('ticket.updated', $ticket, [], $validated);

        return back()->with('success', 'Ticket updated.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->authorize('tickets.reply');

        $validated = $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_admin_reply' => true,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        audit('ticket.replied', $ticket, [], ['message' => substr($validated['message'], 0, 200)]);

        return back()->with('success', 'Reply added.');
    }

    public function resolve(Request $request, SupportTicket $ticket)
    {
        $this->authorize('tickets.resolve');

        $validated = $request->validate([
            'resolution_note' => 'required|string|max:5000',
        ]);

        $ticket->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
            'resolution_note' => $validated['resolution_note'],
        ]);

        audit('ticket.resolved', $ticket, [], $validated);

        return back()->with('success', 'Ticket resolved.');
    }

    /**
     * JSON lookup for create form (mobile or email fragment). GET for simple fetch from admin UI.
     */
    public function lookupUser(Request $request)
    {
        $this->authorize('tickets.create');

        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 3) {
            return response()->json(['user' => null, 'orders' => []]);
        }

        $user = User::query()
            ->where('mobile', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->first();

        if (! $user) {
            return response()->json(['user' => null, 'orders' => []]);
        }

        $orders = $user->orders()->latest()->limit(50)->get(['id', 'order_number', 'status']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
            ],
            'orders' => $orders,
        ]);
    }
}
