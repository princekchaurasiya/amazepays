<?php

namespace App\Services\Wallet;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles wallet credit/debit operations with pessimistic locking.
 *
 * Every read-modify-write cycle acquires a `lockForUpdate()` on the wallet
 * row to prevent concurrent operations from causing overdrafts or double-credits.
 */
class WalletService
{
    /**
     * Credit the user's wallet.
     *
     * @param  string|null  $idempotencyKey  Prevents double-credit if the same key is resubmitted.
     * @return WalletTransaction
     */
    public function credit(
        User $user,
        float $amount,
        string $description = '',
        ?string $idempotencyKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ) {
        return DB::transaction(function () use ($user, $amount, $description, $idempotencyKey, $referenceType, $referenceId) {
            if ($idempotencyKey) {
                $existing = $user->walletTransactions()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing) {
                    Log::info('Wallet credit idempotency hit', ['key' => $idempotencyKey]);

                    return $existing;
                }
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'credit',
                'reference' => 'CR-'.Str::upper(Str::random(8)),
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Debit the user's wallet with balance verification under lock.
     *
     * @return WalletTransaction
     *
     * @throws InsufficientBalanceException when balance < amount
     */
    public function debit(
        User $user,
        float $amount,
        string $description = '',
        ?string $idempotencyKey = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ) {
        return DB::transaction(function () use ($user, $amount, $description, $idempotencyKey, $referenceType, $referenceId) {
            if ($idempotencyKey) {
                $existing = $user->walletTransactions()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing) {
                    Log::info('Wallet debit idempotency hit', ['key' => $idempotencyKey]);

                    return $existing;
                }
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException(
                    required: $amount,
                    available: $wallet->balance,
                );
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'debit',
                'reference' => 'DB-'.Str::upper(Str::random(8)),
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Get the current balance (non-locking read).
     */
    public function balance(User $user): float
    {
        return (float) ($user->wallet?->balance ?? 0);
    }
}
