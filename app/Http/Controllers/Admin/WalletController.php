<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\WalletService;
use App\Models\Wallet;
use App\Models\WalletLoadRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request): Response
    {
        $this->authorize('wallets.view');

        $wallets = Wallet::with('user')
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Wallets/Index', [
            'wallets' => $wallets,
        ]);
    }

    public function show(Wallet $wallet): Response
    {
        $this->authorize('wallets.view');

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(30);

        return Inertia::render('Admin/Wallets/Show', [
            'wallet' => $wallet->load('user'),
            'transactions' => $transactions,
        ]);
    }

    public function credit(Request $request, Wallet $wallet)
    {
        $this->authorize('wallets.credit');

        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'description' => 'required|string|max:255',
        ]);

        try {
            $this->walletService->credit(
                $wallet,
                $request->amount,
                'manual_credit',
                'ADMIN-'.uniqid(),
                $request->description
            );

            audit('wallet.manual_credit', $wallet, ['balance_before' => $wallet->balance], [
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            return back()->with('success', 'Wallet credited successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function debit(Request $request, Wallet $wallet)
    {
        $this->authorize('wallets.debit');

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        try {
            $this->walletService->debit(
                $wallet,
                $request->amount,
                'manual_debit',
                'ADMIN-'.uniqid(),
                $request->description
            );

            audit('wallet.manual_debit', $wallet, ['balance_before' => $wallet->balance], [
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            return back()->with('success', 'Wallet debited successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function freeze(Wallet $wallet)
    {
        $this->authorize('wallets.freeze');

        $wallet->update(['is_frozen' => true, 'frozen_reason' => 'Admin action']);
        audit('wallet.frozen', $wallet);

        return back()->with('success', 'Wallet frozen.');
    }

    public function unfreeze(Wallet $wallet)
    {
        $this->authorize('wallets.freeze');

        $wallet->update(['is_frozen' => false, 'frozen_reason' => null]);
        audit('wallet.unfrozen', $wallet);

        return back()->with('success', 'Wallet unfrozen.');
    }

    // Load Request Management
    public function loadRequests(Request $request): Response
    {
        $this->authorize('wallets.load_requests.view');

        $query = WalletLoadRequest::with(['user', 'tenant', 'approvedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();

        return Inertia::render('Admin/Wallets/LoadRequests', [
            'requests' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['status']),
            'counts' => [
                'pending' => WalletLoadRequest::where('status', 'pending')->count(),
                'under_review' => WalletLoadRequest::where('status', 'under_review')->count(),
            ],
        ]);
    }

    public function approveLoad(Request $request, WalletLoadRequest $loadRequest)
    {
        $this->authorize('wallets.load_requests.approve');

        if (! in_array($loadRequest->status, ['pending', 'under_review'])) {
            return back()->withErrors(['error' => 'This request cannot be approved.']);
        }

        $request->validate(['note' => 'nullable|string|max:500']);

        $wallet = $loadRequest->user->wallet;

        $this->walletService->credit(
            $wallet,
            $loadRequest->amount,
            'bank_load',
            'BL-'.$loadRequest->id,
            "Bank load approved by admin. UTR: {$loadRequest->utr_number}"
        );

        $loadRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_note' => $request->note,
        ]);

        audit('wallet.load_approved', $loadRequest, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with('success', "Load request approved. ₹{$loadRequest->amount} credited.");
    }

    public function rejectLoad(Request $request, WalletLoadRequest $loadRequest)
    {
        $this->authorize('wallets.load_requests.reject');

        $request->validate(['reason' => 'required|string|max:500']);

        $loadRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->reason,
            'rejected_at' => now(),
        ]);

        audit('wallet.load_rejected', $loadRequest, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with('success', 'Load request rejected.');
    }
}
