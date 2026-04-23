<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletFrozenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\SubmitWalletLoadRequest;
use App\Models\Wallet;
use App\Models\WalletLoadRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletLoadRequestService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private WalletLoadRequestService $loadRequestService,
    ) {}

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

    public function storeLoadOnBehalf(SubmitWalletLoadRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('wallets.load_requests.submit_on_behalf');

        $this->loadRequestService->submitForUser($wallet->user, $request);

        return back()->with('success', 'Wallet load request submitted. It will appear in the load requests queue for approval.');
    }

    public function downloadLoadProof(WalletLoadRequest $loadRequest): StreamedResponse
    {
        $this->authorize('wallets.load_requests.view');

        if (! $loadRequest->proof_file) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($loadRequest->proof_file)) {
            abort(404);
        }

        return $disk->response($loadRequest->proof_file);
    }

    public function debit(Request $request, Wallet $wallet)
    {
        $this->authorize('wallets.debit');

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $balanceBefore = (float) $wallet->fresh()->balance;

        try {
            $this->walletService->debit(
                $wallet->user,
                (float) $request->amount,
                $request->description,
                'admin-debit-'.Str::uuid(),
                null,
                null,
            );

            $balanceAfter = (float) $wallet->fresh()->balance;

            audit('wallet.debit', $wallet->fresh(), [
                'balance' => $balanceBefore,
            ], [
                'balance' => $balanceAfter,
                'amount' => (float) $request->amount,
                'description' => $request->description,
            ]);

            return back()->with('success', 'Wallet debited successfully.');
        } catch (InsufficientBalanceException|WalletFrozenException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function freeze(Wallet $wallet)
    {
        $this->authorize('wallets.freeze');

        $before = [
            'is_frozen' => (bool) $wallet->is_frozen,
            'frozen_reason' => $wallet->frozen_reason,
        ];

        $wallet->update(['is_frozen' => true, 'frozen_reason' => 'Admin action']);

        audit('wallet.frozen', $wallet->fresh(), $before, [
            'is_frozen' => true,
            'frozen_reason' => 'Admin action',
        ]);

        return back()->with('success', 'Wallet frozen.');
    }

    public function unfreeze(Wallet $wallet)
    {
        $this->authorize('wallets.freeze');

        $before = [
            'is_frozen' => (bool) $wallet->is_frozen,
            'frozen_reason' => $wallet->frozen_reason,
        ];

        $wallet->update(['is_frozen' => false, 'frozen_reason' => null]);

        audit('wallet.unfrozen', $wallet->fresh(), $before, [
            'is_frozen' => false,
            'frozen_reason' => null,
        ]);

        return back()->with('success', 'Wallet unfrozen.');
    }

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
            ],
        ]);
    }

    public function approveLoad(Request $request, WalletLoadRequest $loadRequest): RedirectResponse
    {
        $this->authorize('wallets.load_requests.approve');

        if ($loadRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'This request cannot be approved.']);
        }

        $request->validate(['note' => 'nullable|string|max:500']);

        $user = $loadRequest->user;
        $previousStatus = $loadRequest->status;
        $balanceBefore = (float) ($user->fresh()->wallet?->balance ?? 0);

        try {
            DB::transaction(function () use ($loadRequest, $request, $user) {
                $this->walletService->credit(
                    $user,
                    (float) $loadRequest->amount,
                    'Wallet load via '.$loadRequest->payment_mode,
                    'wallet-load-'.$loadRequest->id,
                    'load_request',
                    $loadRequest->id,
                );

                $loadRequest->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'admin_note' => $request->input('note'),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $loadRequest->refresh();
        $balanceAfter = (float) $user->fresh()->wallet->balance;

        audit('wallet.load.approved', $loadRequest, [
            'status' => $previousStatus,
        ], [
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        audit('wallet.credit', $user->fresh()->wallet, [
            'balance' => $balanceBefore,
        ], [
            'balance' => $balanceAfter,
            'amount' => (float) $loadRequest->amount,
            'context' => 'wallet_load_approve',
            'load_request_id' => $loadRequest->id,
        ]);

        return back()->with('success', "Load request approved. ₹{$loadRequest->amount} credited.");
    }

    public function rejectLoad(Request $request, WalletLoadRequest $loadRequest): RedirectResponse
    {
        $this->authorize('wallets.load_requests.reject');

        if ($loadRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'This request cannot be rejected.']);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $previousStatus = $loadRequest->status;

        $loadRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->reason,
            'rejected_at' => now(),
        ]);

        $loadRequest->refresh();

        audit('wallet.load.rejected', $loadRequest, [
            'status' => $previousStatus,
        ], [
            'status' => 'rejected',
            'admin_note' => $request->reason,
        ]);

        return back()->with('success', 'Load request rejected.');
    }
}
