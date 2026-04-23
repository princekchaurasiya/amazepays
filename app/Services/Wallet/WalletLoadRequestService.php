<?php

namespace App\Services\Wallet;

use App\Http\Requests\Wallet\SubmitWalletLoadRequest;
use App\Models\User;
use App\Models\WalletLoadRequest;

class WalletLoadRequestService
{
    public function submitForUser(User $user, SubmitWalletLoadRequest $request): WalletLoadRequest
    {
        $validated = $request->validated();
        $path = $request->file('proof')->store('wallet_proofs');

        $loadRequest = WalletLoadRequest::create([
            'user_id' => $user->id,
            'tenant_id' => tenant_id(),
            'amount' => $validated['amount'],
            'payment_mode' => $validated['payment_mode'],
            'reference_no' => $validated['reference_no'] ?? null,
            'proof_file' => $path,
            'status' => 'pending',
        ]);

        audit('wallet.load.requested', $loadRequest, [], array_merge(
            collect($validated)->except(['proof'])->all(),
            [
                'proof_file' => $path,
                'user_id' => $user->id,
                'tenant_id' => tenant_id(),
            ],
        ));

        return $loadRequest;
    }
}
