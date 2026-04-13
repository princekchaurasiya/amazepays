<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\WalletLoadRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 -- Wallet endpoints.
 */
class WalletController extends Controller
{
    use ApiResponse;

    /** Get the user's current wallet balance. */
    public function balance(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        return $this->ok('Wallet balance retrieved.', [
            'balance' => $wallet?->balance ?? 0,
            'currency' => 'INR',
            'is_frozen' => $wallet?->is_frozen ?? false,
        ]);
    }

    /** List wallet transactions with pagination. */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return $this->ok('No transactions found.', ['transactions' => []]);
        }

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return $this->paginated($transactions);
    }

    /** Submit a wallet load request (bank transfer / UPI proof). */
    public function requestLoad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:500000',
            'payment_method' => 'required|in:bank_transfer,upi,neft,rtgs',
            'utr_number' => 'required|string|max:100',
            'bank_reference' => 'nullable|string|max:100',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store(
                'wallet-proofs/'.$request->user()->id,
                'private'
            );
        }

        $loadRequest = WalletLoadRequest::create([
            'user_id' => $request->user()->id,
            'tenant_id' => tenant_id(),
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'utr_number' => $validated['utr_number'],
            'bank_reference' => $validated['bank_reference'] ?? null,
            'payment_proof_path' => $proofPath,
            'status' => 'pending',
        ]);

        return $this->created('Load request submitted. It will be reviewed within 2-4 business hours.', [
            'request_id' => $loadRequest->id,
            'amount' => $loadRequest->amount,
            'status' => $loadRequest->status,
        ]);
    }

    /** Check the status of a wallet load request. */
    public function loadRequestStatus(Request $request, WalletLoadRequest $loadRequest): JsonResponse
    {
        if ($loadRequest->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        return $this->ok('Load request status.', [
            'id' => $loadRequest->id,
            'amount' => $loadRequest->amount,
            'status' => $loadRequest->status,
            'created_at' => $loadRequest->created_at,
            'updated_at' => $loadRequest->updated_at,
        ]);
    }
}
