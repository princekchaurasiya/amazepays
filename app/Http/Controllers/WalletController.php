<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\WalletService;

class WalletController extends Controller
{
    public function deposit(Request $request, WalletService $walletService)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1']
        ]);

        $walletService->credit(
            $request->user(),
            $request->amount,
            'Wallet Deposit'
        );

        return response()->json([
            'message' => 'Wallet funded successfully'
        ]);
    }

    public function balance(Request $request)
    {
        $wallet = $request->user()->wallet;

        return response()->json([
            'balance' => $wallet->balance
        ]);
    }

    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet;

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(10);

        return response()->json($transactions);
    }

    public function transactions_filterbytype(Request $request)
    {
        $transactions = $wallet->transactions()
            ->when($request->type, fn ($q) =>
                $q->where('type', $request->type)
            )
            ->latest()
            ->paginate(10);
    }

        public function debit(Request $request, WalletService $walletService)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string']
        ]);

        try {
            $walletService->debit(
                $request->user(),
                $request->amount,
                $request->description ?? 'Wallet Debit'
            );

            return response()->json([
                'message' => 'Debit successful'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    
}

