<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    public function credit($user, $amount, $description = null)
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $user->wallet;

            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'credit',
                'reference' => uniqid('CR-'),
                'description' => $description,
            ]);
        });
    }

    public function debit($user, $amount, $description = null)
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $user->wallet;

            if ($wallet->balance < $amount) {
                throw new Exception('Insufficient balance');
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'debit',
                'reference' => uniqid('DB-'),
                'description' => $description,
            ]);
        });
    }
}
