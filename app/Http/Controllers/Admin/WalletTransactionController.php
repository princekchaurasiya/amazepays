<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletTransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = WalletTransaction::with('wallet.user')
            ->when($request->type, fn ($q) => $q->where('type', $request->type)
            )
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    public function exportCsv()
    {
        $fileName = 'wallet_transactions.csv';

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'User ID',
                'Amount',
                'Type',
                'Reference',
                'Description',
                'Date',
            ]);

            WalletTransaction::with('wallet.user')
                ->orderBy('id')
                ->chunk(500, function ($transactions) use ($handle) {
                    foreach ($transactions as $tx) {
                        fputcsv($handle, [
                            $tx->id,
                            $tx->wallet->user_id,
                            $tx->amount,
                            $tx->type,
                            $tx->reference,
                            $tx->description,
                            $tx->created_at,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
        ]);
    }
}
